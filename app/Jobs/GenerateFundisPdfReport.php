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

class GenerateFundisPdfReport implements ShouldQueue
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
            Log::info('Starting GenerateFundisPdfReport job', [
                'batch_id' => $this->batchId,
                'total_fundis' => count($this->fundiIds),
            ]);

            $rows = $this->buildRows();

            $title = 'Mafundi | Magic Builders';
            $date = date('D, M/d/Y');
            $html = view('documents.fundis', compact('title', 'date', 'rows'))->render();

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            $publicPath = "downloads/fundis_report_{$this->batchId}.pdf";
            Storage::disk('public')->put($publicPath, $pdf->output());

            Log::info('GenerateFundisPdfReport job completed successfully', [
                'batch_id' => $this->batchId,
                'row_count' => count($rows),
                'peak_memory_bytes' => memory_get_peak_usage(true),
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateFundisPdfReport job failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'peak_memory_bytes' => memory_get_peak_usage(true),
            ]);

            throw $e;
        }
    }

    protected function buildRows(): array
    {
        $rows = [];

        Fundi::whereIn('id', $this->fundiIds)
            ->with('ward.district.region')
            ->withMafundiReportStats()
            ->chunk(500, function ($fundis) use (&$rows) {
                foreach ($fundis as $fundi) {
                    $rows[] = [
                        'name' => $fundi->full_name,
                        'address' => $fundi->address,
                        'primary_phone' => $fundi->primary_phone,
                        'transaction_phone' => $fundi->transaction_phone,
                        'gender' => ucfirst($fundi->gender),
                        'ranking' => $fundi->fundi_ranking,
                        'sites' => $fundi->site_visits_count ?? '0',
                        'bags' => $fundi->bags_total ?? '0',
                        'status' => $fundi->approval_status,
                    ];
                }
            });

        return $rows;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateFundisPdfReport job failed permanently', [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'peak_memory_bytes' => memory_get_peak_usage(true),
        ]);
    }
}
