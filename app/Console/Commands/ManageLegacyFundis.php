<?php

namespace App\Console\Commands;

use App\Models\Fundi;
use App\Models\LegacyFundi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageLegacyFundis extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'legacy:manage
                            {action : The action to perform (stats|auto-match|clean|reset)}
                            {--confidence=90 : Confidence threshold for auto-matching}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Manage legacy fundi data and matching operations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'stats':
                $this->showStats();
                break;
            case 'auto-match':
                $this->autoMatch();
                break;
            case 'clean':
                $this->cleanDuplicates();
                break;
            case 'reset':
                $this->resetMatches();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: stats, auto-match, clean, reset");
                return 1;
        }

        return 0;
    }

    /**
     * Show legacy data statistics
     */
    private function showStats()
    {
        $this->info('Legacy Fundis Statistics');
        $this->info('========================');

        $total = LegacyFundi::count();
        $matched = LegacyFundi::whereIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->count();

        $unmatched = LegacyFundi::whereNotIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        $potential = 0;
        $noMatches = 0;

        $this->info("Analyzing potential matches...");
        $bar = $this->output->createProgressBar($unmatched->count());

        foreach ($unmatched as $legacy) {
            $matches = $legacy->findPotentialMatches();
            if ($matches->count() > 0) {
                $potential++;
            } else {
                $noMatches++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Legacy Records', $total, '100%'],
                ['Matched', $matched, $total > 0 ? round(($matched / $total) * 100, 1) . '%' : '0%'],
                ['With Potential Matches', $potential, $total > 0 ? round(($potential / $total) * 100, 1) . '%' : '0%'],
                ['No Matches Found', $noMatches, $total > 0 ? round(($noMatches / $total) * 100, 1) . '%' : '0%'],
            ]
        );

        // Show matching distribution
        $this->info("\nMatching Analysis by Type:");
        $matchTypes = ['nida' => 0, 'phone' => 0, 'name' => 0];

        foreach ($unmatched->take(100) as $legacy) { // Sample first 100 for performance
            $matches = $legacy->findPotentialMatches();
            foreach ($matches as $match) {
                $matchTypes[$match['match_type']]++;
            }
        }

        $this->table(
            ['Match Type', 'Count (Sample)'],
            [
                ['NIDA Matches', $matchTypes['nida']],
                ['Phone Matches', $matchTypes['phone']],
                ['Name Matches', $matchTypes['name']],
            ]
        );
    }

    /**
     * Run automatic matching
     */
    private function autoMatch()
    {
        $confidence = $this->option('confidence');
        $dryRun = $this->option('dry-run');

        $this->info("Running auto-match with {$confidence}% confidence threshold");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        $unmatched = LegacyFundi::whereNotIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        $this->info("Processing {$unmatched->count()} unmatched legacy records...");
        $bar = $this->output->createProgressBar($unmatched->count());

        $matched = 0;
        $potentialMatches = [];

        foreach ($unmatched as $legacy) {
            $matches = $legacy->findPotentialMatches();
            $highConfidenceMatch = $matches->where('confidence', '>=', $confidence)->first();

            if ($highConfidenceMatch) {
                $fundi = $highConfidenceMatch['fundi'];

                if (!$fundi->legacy_match) {
                    if (!$dryRun) {
                        $fundi->update([
                            'legacy_match' => true,
                            'legacy_id' => $legacy->id,
                        ]);
                    }
                    $matched++;

                    $potentialMatches[] = [
                        'legacy_id' => $legacy->id,
                        'legacy_name' => $legacy->name,
                        'fundi_id' => $fundi->id,
                        'fundi_name' => $fundi->full_name,
                        'confidence' => $highConfidenceMatch['confidence'],
                        'type' => $highConfidenceMatch['match_type']
                    ];
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($matched > 0) {
            $this->info("Successfully matched {$matched} legacy records!");

            if ($this->option('verbose') && count($potentialMatches) > 0) {
                $this->table(
                    ['Legacy ID', 'Legacy Name', 'Fundi ID', 'Fundi Name', 'Confidence', 'Type'],
                    array_slice($potentialMatches, 0, 10) // Show first 10
                );

                if (count($potentialMatches) > 10) {
                    $this->info("... and " . (count($potentialMatches) - 10) . " more matches");
                }
            }
        } else {
            $this->warn("No matches found with {$confidence}% confidence threshold");
        }
    }

    /**
     * Clean duplicate legacy records
     */
    private function cleanDuplicates()
    {
        $this->info("Cleaning duplicate legacy records...");
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        // Find duplicates by phone number
        $phoneGroups = LegacyFundi::select('primary_phone', DB::raw('COUNT(*) as count'))
            ->groupBy('primary_phone')
            ->having('count', '>', 1)
            ->get();

        $duplicatesRemoved = 0;

        foreach ($phoneGroups as $group) {
            $duplicates = LegacyFundi::where('primary_phone', $group->primary_phone)
                ->orderBy('created_at')
                ->get();

            // Keep the first one, remove the rest
            $toRemove = $duplicates->skip(1);

            foreach ($toRemove as $duplicate) {
                $this->info("Removing duplicate: {$duplicate->name} ({$duplicate->primary_phone})");

                if (!$dryRun) {
                    $duplicate->delete();
                }
                $duplicatesRemoved++;
            }
        }

        // Find duplicates by NIDA
        $nidaGroups = LegacyFundi::whereNotNull('nida_number')
            ->select('nida_number', DB::raw('COUNT(*) as count'))
            ->groupBy('nida_number')
            ->having('count', '>', 1)
            ->get();

        foreach ($nidaGroups as $group) {
            $duplicates = LegacyFundi::where('nida_number', $group->nida_number)
                ->orderBy('created_at')
                ->get();

            $toRemove = $duplicates->skip(1);

            foreach ($toRemove as $duplicate) {
                $this->info("Removing NIDA duplicate: {$duplicate->name} ({$duplicate->nida_number})");

                if (!$dryRun) {
                    $duplicate->delete();
                }
                $duplicatesRemoved++;
            }
        }

        $this->info("Removed {$duplicatesRemoved} duplicate records");
    }

    /**
     * Reset all legacy matches
     */
    private function resetMatches()
    {
        $this->warn("This will unlink ALL legacy matches!");

        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        $matchedFundis = Fundi::where('legacy_match', true)->get();

        $this->info("Resetting {$matchedFundis->count()} legacy matches...");

        foreach ($matchedFundis as $fundi) {
            if (!$dryRun) {
                $fundi->update([
                    'legacy_match' => false,
                    'legacy_id' => null,
                ]);
            }
        }

        $this->info("All legacy matches have been reset!");
    }
}
