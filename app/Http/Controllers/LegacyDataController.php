<?php

namespace App\Http\Controllers;

use App\Models\LegacyFundi;
use App\Models\Fundi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LegacyFundiImport;

class LegacyDataController extends Controller
{
    /**
     * Display the legacy fundis management page
     */
    public function index()
    {
        return view('pages.legacy-fundis');
    }

    /**
     * Import legacy data from file
     */
    public function import(Request $request)
    {
        dd('Importing legacy data...');
        $request->validate([
            'legacy_file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
            'auto_match' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('legacy_file');
            $autoMatch = $request->boolean('auto_match');

            // Import the data
            $import = new LegacyFundiImport();
            Excel::import($import, $file);

            $importedCount = $import->getImportedCount();

            // Run auto-matching if requested
            $matchedCount = 0;
            if ($autoMatch) {
                $matchedCount = $this->runAutoMatch();
            }

            DB::commit();

            $message = "Successfully imported {$importedCount} legacy records.";
            if ($matchedCount > 0) {
                $message .= " Auto-matched {$matchedCount} records.";
            }

            return redirect()->route('legacy-fundis.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Legacy import failed: ' . $e->getMessage());

            return redirect()->route('legacy-fundis.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Run automatic matching for high confidence matches
     */
    private function runAutoMatch()
    {
        $legacyFundis = LegacyFundi::whereNotIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        $matchedCount = 0;

        foreach ($legacyFundis as $legacy) {
            $matches = $legacy->findPotentialMatches();
            $highConfidenceMatch = $matches->where('confidence', '>=', 90)->first();

            if ($highConfidenceMatch && $highConfidenceMatch['match_type'] === 'nida') {
                $fundi = $highConfidenceMatch['fundi'];

                // Double check that this fundi isn't already linked
                if (!$fundi->legacy_match) {
                    $fundi->update([
                        'legacy_match' => true,
                        'legacy_id' => $legacy->id,
                    ]);
                    $matchedCount++;
                }
            }
        }

        return $matchedCount;
    }

    /**
     * Get legacy data statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => LegacyFundi::count(),
            'matched' => LegacyFundi::whereIn('id', function($query) {
                $query->select('legacy_id')
                    ->from('fundis')
                    ->whereNotNull('legacy_id');
            })->count(),
            'unmatched' => 0,
            'potential' => 0,
        ];

        // Calculate potential matches (this might be slow for large datasets)
        $unmatched = LegacyFundi::whereNotIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        foreach ($unmatched as $legacy) {
            $potentialCount = $legacy->findPotentialMatches()->count();
            if ($potentialCount > 0) {
                $stats['potential']++;
            } else {
                $stats['unmatched']++;
            }
        }

        return response()->json($stats);
    }

    /**
     * Manual link operation
     */
    public function link(Request $request)
    {
        $request->validate([
            'legacy_id' => 'required|exists:legacy_fundis,id',
            'fundi_id' => 'required|exists:fundis,id',
        ]);

        try {
            $legacyFundi = LegacyFundi::findOrFail($request->legacy_id);
            $fundi = Fundi::findOrFail($request->fundi_id);

            // Check if fundi is already linked
            if ($fundi->legacy_match) {
                return response()->json([
                    'success' => false,
                    'message' => 'This fundi is already linked to another legacy record.'
                ], 400);
            }

            $fundi->update([
                'legacy_match' => true,
                'legacy_id' => $legacyFundi->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Legacy fundi linked successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Legacy link failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Link operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual unlink operation
     */
    public function unlink(Request $request)
    {
        $request->validate([
            'legacy_id' => 'required|exists:legacy_fundis,id',
        ]);

        try {
            $fundi = Fundi::where('legacy_id', $request->legacy_id)->first();

            if (!$fundi) {
                return response()->json([
                    'success' => false,
                    'message' => 'No linked fundi found for this legacy record.'
                ], 404);
            }

            $fundi->update([
                'legacy_match' => false,
                'legacy_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Legacy fundi unlinked successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Legacy unlink failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unlink operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get potential matches for a legacy fundi
     */
    public function potentialMatches($id)
    {
        try {
            $legacyFundi = LegacyFundi::findOrFail($id);
            $matches = $legacyFundi->findPotentialMatches();

            return response()->json([
                'success' => true,
                'legacy_fundi' => $legacyFundi,
                'matches' => $matches->map(function($match) {
                    return [
                        'fundi' => $match['fundi']->load('ward.district.region'),
                        'match_type' => $match['match_type'],
                        'confidence' => $match['confidence'],
                        'details' => $match['details'],
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get potential matches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkMatch(Request $request)
    {
        $request->validate([
            'confidence_threshold' => 'required|integer|min:70|max:100',
        ]);

        try {
            $threshold = $request->confidence_threshold;
            $matchedCount = 0;

            $legacyFundis = LegacyFundi::whereNotIn('id', function($query) {
                $query->select('legacy_id')
                    ->from('fundis')
                    ->whereNotNull('legacy_id');
            })->get();

            foreach ($legacyFundis as $legacy) {
                $matches = $legacy->findPotentialMatches();
                $highConfidenceMatch = $matches->where('confidence', '>=', $threshold)->first();

                if ($highConfidenceMatch) {
                    $fundi = $highConfidenceMatch['fundi'];

                    if (!$fundi->legacy_match) {
                        $fundi->update([
                            'legacy_match' => true,
                            'legacy_id' => $legacy->id,
                        ]);
                        $matchedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully matched {$matchedCount} legacy records with {$threshold}% confidence threshold."
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk match failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bulk match failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export unmatched legacy data
     */
    public function exportUnmatched()
    {
        $unmatchedLegacy = LegacyFundi::whereNotIn('id', function($query) {
            $query->select('legacy_id')
                ->from('fundis')
                ->whereNotNull('legacy_id');
        })->get();

        return response()->streamDownload(function () use ($unmatchedLegacy) {
            $headers = [
                'Legacy ID',
                'Name',
                'Gender',
                'Primary Phone',
                'Transaction Phone',
                'NIDA Number',
                'Region',
                'District',
                'Ward',
                'Ranking',
                'Status',
                'Potential Matches Count',
                'Created At'
            ];

            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($unmatchedLegacy as $legacy) {
                $potentialMatches = $legacy->findPotentialMatches();

                fputcsv($handle, [
                    $legacy->id,
                    $legacy->name,
                    ucfirst($legacy->gender),
                    $legacy->primary_phone,
                    $legacy->transaction_phone,
                    $legacy->nida_number,
                    $legacy->region,
                    $legacy->district,
                    $legacy->ward,
                    $legacy->ranking === 'fundi_kuu' ? 'Fundi Mkuu' : 'Fundi',
                    ucfirst($legacy->status),
                    $potentialMatches->count(),
                    $legacy->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, 'unmatched_legacy_fundis_' . date('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
