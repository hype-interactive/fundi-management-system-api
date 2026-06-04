<?php

namespace App\Console\Commands;

use App\Models\Fundi;
use App\Models\LegacyFundi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ManageLegacyFundis extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'legacy:manage
                            {action : Action to perform (stats|match|unlink|clean|export)}
                            {--confidence=90 : Confidence threshold for matching (70-100)}
                            {--match-type=all : Type of matching (all|nida|phone|name)}
                            {--limit=100 : Limit number of records to process}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompts}';

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

        $this->info('🛠️  Legacy Fundis Management Tool');
        $this->info('================================');
        $this->newLine();

        switch ($action) {
            case 'stats':
                return $this->showStats();
            case 'match':
                return $this->runMatching();
            case 'unlink':
                return $this->unlinkMatches();
            case 'clean':
                return $this->cleanData();
            case 'export':
                return $this->exportAnalysis();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: stats, match, unlink, clean, export');

                return 1;
        }
    }

    /**
     * Show comprehensive statistics
     */
    private function showStats(): int
    {
        $this->info('📊 Legacy Fundis Statistics');
        $this->info('===========================');

        // Basic counts
        $total = LegacyFundi::count();
        $matched = $this->getMatchedCount();
        $unmatched = $total - $matched;

        // Current fundis count
        $currentFundis = Fundi::count();
        $linkedFundis = Fundi::where('legacy_match', true)->count();

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Legacy Records', number_format($total), '100%'],
                ['Matched to Current Fundis', number_format($matched), $this->percentage($matched, $total)],
                ['Unmatched Legacy Records', number_format($unmatched), $this->percentage($unmatched, $total)],
                ['Current Fundis', number_format($currentFundis), '-'],
                ['Current Fundis with Legacy Link', number_format($linkedFundis), $this->percentage($linkedFundis, $currentFundis)],
            ]
        );

        // Analyze unmatched records for potential matches
        if ($unmatched > 0) {
            $this->info("\n🔍 Analyzing Potential Matches...");
            $this->analyzeUnmatched(min($unmatched, 1000)); // Analyze up to 1000 for performance
        }

        // Show data quality metrics
        $this->showDataQuality();

        // Show region distribution
        $this->showRegionDistribution();

        return 0;
    }

    /**
     * Analyze unmatched records for potential matches
     */
    private function analyzeUnmatched(int $limit): void
    {
        $unmatched = LegacyFundi::whereNotIn('id', function ($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->limit($limit)->get();

        $analysis = [
            'with_potential' => 0,
            'no_matches' => 0,
            'high_confidence' => 0,
            'medium_confidence' => 0,
            'low_confidence' => 0,
        ];

        $progressBar = $this->output->createProgressBar($unmatched->count());
        $progressBar->start();

        foreach ($unmatched as $legacy) {
            $matches = $legacy->findPotentialMatches();

            if ($matches->isEmpty()) {
                $analysis['no_matches']++;
            } else {
                $analysis['with_potential']++;

                $bestMatch = $matches->first();
                $confidence = $bestMatch['confidence'];

                if ($confidence >= 90) {
                    $analysis['high_confidence']++;
                } elseif ($confidence >= 70) {
                    $analysis['medium_confidence']++;
                } else {
                    $analysis['low_confidence']++;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->table(
            ['Match Analysis', 'Count'],
            [
                ['Records with Potential Matches', number_format($analysis['with_potential'])],
                ['Records with No Matches', number_format($analysis['no_matches'])],
                ['High Confidence (90%+)', number_format($analysis['high_confidence'])],
                ['Medium Confidence (70-89%)', number_format($analysis['medium_confidence'])],
                ['Low Confidence (<70%)', number_format($analysis['low_confidence'])],
            ]
        );

        if ($analysis['high_confidence'] > 0) {
            $this->info("\n💡 You have {$analysis['high_confidence']} high-confidence matches ready for auto-linking!");
            $this->info('   Run: php artisan legacy:manage match --confidence=90');
        }
    }

    /**
     * Show data quality metrics
     */
    private function showDataQuality(): void
    {
        $this->info("\n📈 Data Quality Metrics");
        $this->info('=======================');

        $withNames = LegacyFundi::whereNotNull('name')->where('name', '!=', '')->count();
        $withPhones = LegacyFundi::whereNotNull('primary_phone')->where('primary_phone', '!=', '')->count();
        $withNida = LegacyFundi::whereNotNull('nida_number')->where('nida_number', '!=', '')->count();
        $withLocation = LegacyFundi::whereNotNull('region')->where('region', '!=', '')->count();
        $total = LegacyFundi::count();

        $this->table(
            ['Data Field', 'Records with Data', 'Percentage'],
            [
                ['Names', number_format($withNames), $this->percentage($withNames, $total)],
                ['Phone Numbers', number_format($withPhones), $this->percentage($withPhones, $total)],
                ['NIDA Numbers', number_format($withNida), $this->percentage($withNida, $total)],
                ['Location Info', number_format($withLocation), $this->percentage($withLocation, $total)],
            ]
        );
    }

    /**
     * Show region distribution
     */
    private function showRegionDistribution(): void
    {
        $this->info("\n🗺️  Region Distribution");
        $this->info('======================');

        $regions = LegacyFundi::select('region', DB::raw('COUNT(*) as count'))
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        if ($regions->isNotEmpty()) {
            $data = $regions->map(function ($region) {
                return [$region->region, number_format($region->count)];
            })->toArray();

            $this->table(['Region', 'Count'], $data);
        } else {
            $this->info('No region data available');
        }
    }

    /**
     * Run matching operations
     */
    private function runMatching(): int
    {
        $confidence = (int) $this->option('confidence');
        $matchType = $this->option('match-type');
        $limit = (int) $this->option('limit');
        $isDryRun = $this->option('dry-run');

        $this->info('🔗 Running Legacy Matching');
        $this->info('==========================');
        $this->info("Confidence threshold: {$confidence}%");
        $this->info("Match type: {$matchType}");
        $this->info("Limit: {$limit} records");
        $this->info('Mode: '.($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        if (! $this->option('force') && ! $isDryRun) {
            if (! $this->confirm('This will create permanent links between legacy and current fundis. Continue?')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        $unmatched = LegacyFundi::whereNotIn('id', function ($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->limit($limit)->get();

        if ($unmatched->isEmpty()) {
            $this->info('No unmatched legacy records found.');

            return 0;
        }

        $this->info("Processing {$unmatched->count()} unmatched records...");
        $progressBar = $this->output->createProgressBar($unmatched->count());
        $progressBar->start();

        $matched = 0;
        $matchDetails = [];

        foreach ($unmatched as $legacy) {
            $matches = $legacy->findPotentialMatches();

            // Filter by match type if specified
            if ($matchType !== 'all') {
                $matches = $matches->where('match_type', $matchType);
            }

            $bestMatch = $matches->where('confidence', '>=', $confidence)->first();

            if ($bestMatch) {
                $fundi = $bestMatch['fundi'];

                if (! $fundi->legacy_match) {
                    if (! $isDryRun) {
                        $fundi->update([
                            'legacy_match' => true,
                            'legacy_id' => $legacy->id,
                        ]);

                        // Clear cache
                        Cache::forget("legacy_fundi_{$legacy->id}_potential_matches_count");
                    }

                    $matched++;
                    $matchDetails[] = [
                        'legacy_id' => $legacy->id,
                        'legacy_name' => $legacy->name,
                        'fundi_id' => $fundi->id,
                        'fundi_name' => $fundi->full_name,
                        'confidence' => $bestMatch['confidence'],
                        'type' => $bestMatch['match_type'],
                    ];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($matched > 0) {
            $this->info("✅ Successfully matched {$matched} legacy records!");

            if ($this->option('verbose') && count($matchDetails) > 0) {
                $this->newLine();
                $this->info('Match Details (showing first 10):');
                $this->table(
                    ['Legacy ID', 'Legacy Name', 'Fundi ID', 'Fundi Name', 'Confidence', 'Type'],
                    array_slice($matchDetails, 0, 10)
                );

                if (count($matchDetails) > 10) {
                    $this->info('... and '.(count($matchDetails) - 10).' more matches');
                }
            }
        } else {
            $this->warn('No matches found with current criteria.');
            $this->info("💡 Try lowering the confidence threshold or using 'all' match types.");
        }

        return 0;
    }

    /**
     * Unlink legacy matches
     */
    private function unlinkMatches(): int
    {
        $limit = (int) $this->option('limit');
        $isDryRun = $this->option('dry-run');

        $this->info('🔓 Unlinking Legacy Matches');
        $this->info('===========================');

        if (! $this->option('force')) {
            $matchedCount = Fundi::where('legacy_match', true)->count();
            $this->warn("This will unlink ALL {$matchedCount} legacy matches!");

            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        $matchedFundis = Fundi::where('legacy_match', true)->limit($limit)->get();

        if ($matchedFundis->isEmpty()) {
            $this->info('No linked legacy records found.');

            return 0;
        }

        $this->info("Unlinking {$matchedFundis->count()} matches...");
        $progressBar = $this->output->createProgressBar($matchedFundis->count());
        $progressBar->start();

        $unlinked = 0;

        foreach ($matchedFundis as $fundi) {
            if (! $isDryRun) {
                $fundi->update([
                    'legacy_match' => false,
                    'legacy_id' => null,
                ]);

                // Clear related caches
                if ($fundi->legacy_id) {
                    Cache::forget("legacy_fundi_{$fundi->legacy_id}_potential_matches_count");
                }
            }

            $unlinked++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ Unlinked {$unlinked} legacy matches!");

        return 0;
    }

    /**
     * Clean legacy data
     */
    private function cleanData(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🧹 Cleaning Legacy Data');
        $this->info('=======================');

        if (! $this->option('force') && ! $isDryRun) {
            if (! $this->confirm('This will permanently remove duplicate and invalid records. Continue?')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        $cleaned = 0;

        // Clean phone number duplicates
        $this->info('Cleaning phone number duplicates...');
        $phoneGroups = LegacyFundi::select('primary_phone', DB::raw('COUNT(*) as count'))
            ->whereNotNull('primary_phone')
            ->where('primary_phone', '!=', '')
            ->groupBy('primary_phone')
            ->having('count', '>', 1)
            ->get();

        foreach ($phoneGroups as $group) {
            $duplicates = LegacyFundi::where('primary_phone', $group->primary_phone)
                ->orderBy('created_at')
                ->get();

            $toRemove = $duplicates->skip(1); // Keep the first one

            foreach ($toRemove as $duplicate) {
                if (! $isDryRun) {
                    $duplicate->delete();
                }
                $cleaned++;
            }
        }

        // Clean NIDA duplicates
        $this->info('Cleaning NIDA number duplicates...');
        $nidaGroups = LegacyFundi::whereNotNull('nida_number')
            ->where('nida_number', '!=', '')
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
                if (! $isDryRun) {
                    $duplicate->delete();
                }
                $cleaned++;
            }
        }

        // Clean invalid phone numbers
        $this->info('Cleaning invalid phone numbers...');
        $invalidPhones = LegacyFundi::where(function ($query) {
            $query->whereNull('primary_phone')
                ->orWhere('primary_phone', '')
                ->orWhere('primary_phone', 'not like', '255%')
                ->orWhereRaw('LENGTH(primary_phone) != 12');
        })->get();

        foreach ($invalidPhones as $invalid) {
            if (! $isDryRun) {
                $invalid->delete();
            }
            $cleaned++;
        }

        $this->info("✅ Cleaned {$cleaned} records!");

        if ($isDryRun) {
            $this->warn('🔍 This was a dry run - no actual changes were made.');
        }

        return 0;
    }

    /**
     * Export analysis data
     */
    private function exportAnalysis(): int
    {
        $this->info('📊 Exporting Analysis Data');
        $this->info('===========================');

        $filename = storage_path('app/legacy_analysis_'.date('Y-m-d_H-i-s').'.csv');

        $this->info('Generating analysis report...');

        $handle = fopen($filename, 'w');

        // Headers
        fputcsv($handle, [
            'Legacy ID',
            'Name',
            'Primary Phone',
            'NIDA Number',
            'Region',
            'District',
            'Ward',
            'Status',
            'Match Status',
            'Matched Fundi ID',
            'Matched Fundi Name',
            'Potential Matches Count',
            'Best Match Confidence',
            'Best Match Type',
            'Created At',
        ]);

        // Get all legacy fundis with their match status
        LegacyFundi::with('matchedFundi')->chunk(500, function ($legacyFundis) use ($handle) {
            foreach ($legacyFundis as $legacy) {
                $potentialMatches = $legacy->findPotentialMatches();
                $bestMatch = $potentialMatches->first();

                fputcsv($handle, [
                    $legacy->id,
                    $legacy->name,
                    $legacy->primary_phone,
                    $legacy->nida_number ?? 'N/A',
                    $legacy->region ?? 'N/A',
                    $legacy->district ?? 'N/A',
                    $legacy->ward ?? 'N/A',
                    ucfirst($legacy->status),
                    ucfirst($legacy->match_status),
                    $legacy->matchedFundi?->id ?? 'N/A',
                    $legacy->matchedFundi?->full_name ?? 'N/A',
                    $potentialMatches->count(),
                    $bestMatch ? $bestMatch['confidence'] : 'N/A',
                    $bestMatch ? $bestMatch['match_type'] : 'N/A',
                    $legacy->created_at->format('Y-m-d H:i:s'),
                ]);
            }
        });

        fclose($handle);

        $this->info("✅ Analysis exported to: {$filename}");
        $this->info('📁 File size: '.$this->formatBytes(filesize($filename)));

        return 0;
    }

    /**
     * Helper methods
     */
    private function getMatchedCount(): int
    {
        return LegacyFundi::whereIn('id', function ($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->count();
    }

    private function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return number_format(($value / $total) * 100, 1).'%';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}
