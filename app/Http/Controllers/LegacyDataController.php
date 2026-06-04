<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLegacyImportJob;
use App\Models\Fundi;
use App\Models\LegacyFundi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     * Import legacy data with queue processing
     */
    public function import(Request $request)
    {
        $request->validate([
            'legacy_file' => 'required|file|mimes:csv,xlsx,xls|max:20480', // 20MB max
            'auto_match' => 'boolean',
        ]);

        try {
            $file = $request->file('legacy_file');
            $autoMatch = $request->boolean('auto_match');

            // Store the file temporarily
            $filePath = $file->store('imports', 'local');

            // Generate unique job ID
            $jobId = 'import_'.uniqid().'_'.Auth::id();

            // Dispatch the job
            ProcessLegacyImportJob::dispatch(
                $filePath,
                Auth::id(),
                $autoMatch,
                $jobId
            );

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Import started successfully. You can monitor the progress below.',
            ]);
        } catch (\Exception $e) {
            Log::error('Legacy import dispatch failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to start import: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check import progress
     */
    public function importProgress($jobId)
    {
        $progress = cache()->get("import_progress_{$jobId}");

        if (! $progress) {
            return response()->json([
                'success' => false,
                'message' => 'Import progress not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => LegacyFundi::count(),
            'matched' => LegacyFundi::matched()->count(),
            'unmatched' => LegacyFundi::unmatched()->count(),
            'potential' => LegacyFundi::withPotentialMatches()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Link a legacy fundi with an existing fundi
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

            if (! $legacyFundi->linkWith($fundi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This fundi is already linked to another legacy record.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Legacy fundi linked successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Legacy link failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Link operation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unlink a legacy fundi
     */
    public function unlink(Request $request)
    {
        $request->validate([
            'legacy_id' => 'required|exists:legacy_fundis,id',
        ]);

        try {
            $legacyFundi = LegacyFundi::findOrFail($request->legacy_id);

            if (! $legacyFundi->unlink()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No linked fundi found for this legacy record.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Legacy fundi unlinked successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Legacy unlink failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unlink operation failed: '.$e->getMessage(),
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
                'matches' => $matches->map(function ($match) {
                    return [
                        'fundi' => $match['fundi']->load('ward.district.region'),
                        'match_type' => $match['match_type'],
                        'confidence' => $match['confidence'],
                        'details' => $match['details'],
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get potential matches: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run bulk auto-matching
     */
    public function bulkAutoMatch(Request $request)
    {
        $request->validate([
            'confidence_threshold' => 'integer|min:70|max:100',
        ]);

        try {
            $threshold = $request->get('confidence_threshold', 90);
            $matchedCount = $this->runBulkAutoMatch($threshold);

            return response()->json([
                'success' => true,
                'message' => "Successfully auto-matched {$matchedCount} legacy records.",
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk auto-match failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bulk auto-match failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel import job
     */
    public function cancelImport(Request $request)
    {
        $request->validate([
            'job_id' => 'required|string',
        ]);

        $jobId = $request->job_id;

        // Mark as cancelled in cache
        cache()->put("import_progress_{$jobId}", [
            'job_id' => $jobId,
            'user_id' => Auth::id(),
            'percentage' => 0,
            'message' => 'Import cancelled by user.',
            'status' => 'cancelled',
            'stats' => [],
            'updated_at' => now()->toISOString(),
        ], now()->addHour());

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled successfully.',
        ]);
    }

    /**
     * Private method to run auto-matching
     */
    private function runBulkAutoMatch(int $threshold = 90): int
    {
        $unmatchedLegacy = LegacyFundi::unmatched()->get();
        $matchedCount = 0;

        foreach ($unmatchedLegacy as $legacy) {
            $matches = $legacy->findPotentialMatches();
            $highConfidenceMatch = $matches->where('confidence', '>=', $threshold)->first();

            if ($highConfidenceMatch && $highConfidenceMatch['match_type'] === 'nida') {
                if ($legacy->autoLink()) {
                    $matchedCount++;
                }
            }
        }

        return $matchedCount;
    }

    /**
     * Export analysis data
     */
    public function exportAnalysis()
    {
        $filename = 'legacy_fundis_analysis_'.date('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Legacy ID',
                'Name',
                'Primary Phone',
                'NIDA Number',
                'Region',
                'District',
                'Ward',
                'Match Status',
                'Matched Fundi ID',
                'Potential Matches Count',
                'Created At',
            ]);

            // Data
            LegacyFundi::with('matchedFundi')->chunk(500, function ($legacyFundis) use ($handle) {
                foreach ($legacyFundis as $legacy) {
                    fputcsv($handle, [
                        $legacy->id,
                        $legacy->name,
                        $legacy->primary_phone,
                        $legacy->nida_number ?? 'N/A',
                        $legacy->region ?? 'N/A',
                        $legacy->district ?? 'N/A',
                        $legacy->ward ?? 'N/A',
                        ucfirst($legacy->match_status),
                        $legacy->matchedFundi?->id ?? 'N/A',
                        $legacy->getPotentialMatchesCount(),
                        $legacy->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
