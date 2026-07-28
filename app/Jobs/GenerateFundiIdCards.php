<?php

namespace App\Jobs;

use App\Models\Fundi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateFundiIdCards implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public $tries = 1;

    public $maxExceptions = 1;

    protected $fundiIds;

    protected $userId;

    protected $batchId;

    public function __construct(array $fundiIds, int $userId, string $batchId)
    {
        $this->fundiIds = $fundiIds;
        $this->userId = $userId;
        $this->batchId = $batchId;
    }

    public function handle(): void
    {
        try {
            Log::info('Starting GenerateFundiIdCards job', [
                'batch_id' => $this->batchId,
                'total_fundis' => count($this->fundiIds),
            ]);

            $zipFileName = "fundi_ids_{$this->batchId}.zip";
            $tempDir = storage_path('app/temp');
            $zipPath = "{$tempDir}/{$zipFileName}";

            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
                Log::info("Created temp directory: {$tempDir}");
            }

            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Cannot create zip file at: {$zipPath}");
            }

            Log::info('Zip file created successfully');

            $successCount = 0;
            $failCount = 0;
            $skippedCount = 0;

            foreach (array_chunk($this->fundiIds, 50) as $chunkIndex => $chunk) {
                Log::info("Processing chunk {$chunkIndex}", ['chunk_size' => count($chunk)]);

                $fundis = Fundi::with(['ward.district.region'])
                    ->whereIn('id', $chunk)
                    ->get();

                foreach ($fundis as $fundi) {
                    try {
                        // Re-check the fundi still exists — it may have been soft-deleted after
                        // this chunk was loaded but before its turn in this loop.
                        if (! Fundi::whereKey($fundi->id)->exists()) {
                            $skippedCount++;
                            Log::warning("Skipping Fundi {$fundi->id} - deleted after chunk load, before card generation");

                            continue;
                        }

                        // Ensure QR code exists
                        if (! $fundi->qr_code_path) {
                            $fundi->generateQrCodeQuietly();
                        }

                        // Get absolute paths
                        $qrCodePath = storage_path('app/public/'.$fundi->qr_code_path);
                        $logoPath = public_path('images/logo.png');

                        // Convert images to base64
                        $qrCodeBase64 = null;
                        if (file_exists($qrCodePath)) {
                            $qrCodeData = file_get_contents($qrCodePath);
                            $qrCodeType = pathinfo($qrCodePath, PATHINFO_EXTENSION) === 'svg' ? 'svg+xml' : pathinfo($qrCodePath, PATHINFO_EXTENSION);
                            $qrCodeBase64 = 'data:image/'.$qrCodeType.';base64,'.base64_encode($qrCodeData);
                        }

                        $logoBase64 = null;
                        if (file_exists($logoPath)) {
                            $logoData = file_get_contents($logoPath);
                            $logoBase64 = 'data:image/png;base64,'.base64_encode($logoData);
                        }

                        $data = [
                            'company_name' => 'MAGIC BUILDERS FOUNDATION',
                            'id_number' => $fundi->unique_id ?? 'FNS-01X251',
                            'name' => $fundi->full_name,
                            'gender' => $fundi->gender ?? 'N/A',
                            'address' => $fundi->short_address ?? 'Sinza, Kinondoni, Dar Es Salaam',
                            'registered' => $fundi->created_at ? $fundi->created_at->format('d/m/Y') : date('d/m/Y'),
                            'expire' => $fundi->created_at ? $fundi->created_at->addYears(5)->format('d/m/Y') : date('d/m/Y', strtotime('+5 years')),
                            'photo' => $fundi->profile_photo_url ?? public_path('images/profile.jpg'),
                            'logo' => $logoBase64, // Base64 encoded
                            'qrcode' => $qrCodeBase64,
                        ];

                        // IMPORTANT: Use the EXACT same configuration as the controller
                        $pdf = Pdf::loadView('documents.id-card', $data);

                        // Set paper BEFORE any other options
                        $pdf->setPaper(config('idcard.paper_size'), config('idcard.paper_orientation'));

                        // Set all options explicitly
                        $pdf->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'isFontSubsettingEnabled' => true,
                            'defaultFont' => 'Arial',
                            'dpi' => 96,
                            'defaultPaperSize' => 'custom',
                        ]);

                        $pdfContent = $pdf->output();

                        // Sanitize filename
                        $fileName = $this->sanitizeFileName($fundi);

                        // Add to zip
                        $zip->addFromString($fileName, $pdfContent);

                        $successCount++;

                        // Log first PDF generation for debugging
                        if ($successCount === 1) {
                            Log::info('First PDF generated successfully', [
                                'fundi_id' => $fundi->id,
                                'pdf_size' => strlen($pdfContent).' bytes',
                            ]);
                        }
                    } catch (\Exception $e) {
                        $failCount++;
                        Log::error("Failed to generate ID for Fundi {$fundi->id}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }

            $zip->close();

            Log::info("Zip file closed. Success: {$successCount}, Failed: {$failCount}, Skipped: {$skippedCount}");

            $downloadsDir = storage_path('app/public/downloads');
            if (! file_exists($downloadsDir)) {
                mkdir($downloadsDir, 0755, true);
                Log::info("Created downloads directory: {$downloadsDir}");
            }

            $publicPath = "downloads/{$zipFileName}";
            Storage::disk('public')->put($publicPath, file_get_contents($zipPath));

            Log::info("Zip file moved to public storage: {$publicPath}");

            if (file_exists($zipPath)) {
                unlink($zipPath);
                Log::info('Temp file deleted');
            }

            Log::info('GenerateFundiIdCards job completed successfully', [
                'batch_id' => $this->batchId,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'skipped_count' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateFundiIdCards job failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function sanitizeFileName(Fundi $fundi): string
    {
        $fname = preg_replace('/[^A-Za-z0-9_\-]/', '', $fundi->fname);
        $lname = preg_replace('/[^A-Za-z0-9_\-]/', '', $fundi->lname);

        return "FND{$fundi->unique_id}_{$fname}_{$lname}.pdf";
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateFundiIdCards job failed permanently', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
