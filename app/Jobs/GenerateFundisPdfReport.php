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

class GenerateFundisPdfReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public $tries = 1;

    public $maxExceptions = 1;

    /**
     * Mirrors the on-screen default columns. Declared as a property default
     * (not just a constructor default) so a job payload serialised before
     * this parameter existed still deserialises correctly - unserialize()
     * restores property state directly, it does not re-run the constructor.
     */
    public const DEFAULT_COLUMNS = ['name', 'primary_phone', 'address', 'ranking', 'sites', 'bags'];

    protected $fundiIds;

    protected $userId;

    protected $batchId;

    protected $columns = self::DEFAULT_COLUMNS;

    public function __construct(array $fundiIds, int $userId, string $batchId, array $columns = self::DEFAULT_COLUMNS)
    {
        $this->fundiIds = $fundiIds;
        $this->userId = $userId;
        $this->batchId = $batchId;
        $this->columns = ! empty($columns) ? $columns : self::DEFAULT_COLUMNS;
    }

    /**
     * Single source of truth for every offerable column: its header label,
     * how to read it off a hydrated+eager-loaded Fundi, and the pixel width
     * to set on the <thead> cell (DomPDF ignores <colgroup>/<col> widths
     * under table-layout: fixed, so widths must live on the first rendered
     * row - matching documents/operator-mafundi.blade.php's pattern). Narrow
     * columns get a fixed width; name/address stay flexible and share
     * whatever room is left, same as that template.
     */
    public static function columnDefinitions(): array
    {
        return [
            'name' => ['label' => 'Name', 'width' => null, 'value' => fn ($f) => $f->full_name],
            'gender' => ['label' => 'Gender', 'width' => '52px', 'value' => fn ($f) => ucfirst($f->gender)],
            'primary_phone' => ['label' => 'Primary Phone', 'width' => '95px', 'value' => fn ($f) => $f->primary_phone],
            'transaction_phone' => ['label' => 'Trans. Phone', 'width' => '95px', 'value' => fn ($f) => $f->transaction_phone],
            'nida_number' => ['label' => 'NIDA Number', 'width' => '90px', 'value' => fn ($f) => $f->nida_number ?: 'N/A'],
            'ranking' => ['label' => 'Ranking', 'width' => '70px', 'value' => fn ($f) => $f->fundi_ranking],
            'address' => ['label' => 'Address', 'width' => null, 'value' => fn ($f) => $f->address],
            'approval_status' => ['label' => 'Status', 'width' => '60px', 'value' => fn ($f) => $f->approval_status],
            'registrar' => ['label' => 'Registrar', 'width' => '110px', 'value' => fn ($f) => $f->creator->full_name ?? 'N/A'],
            'approver' => ['label' => 'Approver', 'width' => '110px', 'value' => fn ($f) => $f->approver->full_name ?? 'N/A'],
            'created_at' => ['label' => 'Created At', 'width' => '70px', 'value' => fn ($f) => $f->created_at->format('Y-m-d')],
            'approved_at' => ['label' => 'Approved At', 'width' => '70px', 'value' => fn ($f) => $f->approved_at?->format('Y-m-d') ?? 'N/A'],
            'sites' => ['label' => 'Sites', 'width' => '40px', 'value' => fn ($f) => $f->site_visits_count ?? '0'],
            'bags' => ['label' => 'Putty Bags', 'width' => '60px', 'value' => fn ($f) => $f->bags_total ?? '0'],
        ];
    }

    public function handle(): void
    {
        try {
            Log::info('Starting GenerateFundisPdfReport job', [
                'batch_id' => $this->batchId,
                'total_fundis' => count($this->fundiIds),
            ]);

            $cap = config('fundis_export.pdf_row_cap');

            if (count($this->fundiIds) <= $cap) {
                $this->renderSinglePdf();
            } else {
                $this->renderChunkedZip($cap);
            }

            Log::info('GenerateFundisPdfReport job completed successfully', [
                'batch_id' => $this->batchId,
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

    /**
     * At or below the cap: a single PDF, same as before chunking existed.
     */
    protected function renderSinglePdf(): void
    {
        $rows = $this->buildRows($this->fundiIds);
        $pdfContent = $this->renderPdf($rows);

        $publicPath = "downloads/fundis_report_{$this->batchId}.pdf";
        Storage::disk('public')->put($publicPath, $pdfContent);

        Log::info('GenerateFundisPdfReport rendered single PDF', [
            'batch_id' => $this->batchId,
            'row_count' => count($rows),
            'peak_memory_bytes' => memory_get_peak_usage(true),
        ]);
    }

    /**
     * Above the cap: split by district, then by the same cap within any
     * district that itself exceeds it, rendering and writing each chunk to
     * disk before starting the next so peak memory stays flat regardless of
     * total export size. The ZIP is assembled from disk afterward.
     */
    protected function renderChunkedZip(int $cap): void
    {
        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $chunkFiles = [];
        $failedChunks = [];

        foreach ($this->districtBuckets() as $label => $fundiIds) {
            $parts = array_chunk($fundiIds, $cap);
            $partCount = count($parts);

            foreach ($parts as $partIndex => $partIds) {
                $fileName = $partCount > 1
                    ? "fundis_{$label}_part".($partIndex + 1).'.pdf'
                    : "fundis_{$label}.pdf";

                try {
                    $rows = $this->buildRows($partIds);
                    $pdfContent = $this->renderPdf($rows);

                    $chunkPath = "{$tempDir}/{$this->batchId}_{$fileName}";
                    file_put_contents($chunkPath, $pdfContent);
                    $chunkFiles[] = ['path' => $chunkPath, 'name' => $fileName];

                    unset($pdfContent, $rows);
                    gc_collect_cycles();

                    Log::info('GenerateFundisPdfReport chunk rendered', [
                        'batch_id' => $this->batchId,
                        'file' => $fileName,
                        'row_count' => count($partIds),
                        'peak_memory_bytes' => memory_get_peak_usage(true),
                    ]);
                } catch (\Throwable $e) {
                    $failedChunks[] = $fileName;
                    Log::error('GenerateFundisPdfReport chunk failed', [
                        'batch_id' => $this->batchId,
                        'file' => $fileName,
                        'error' => $e->getMessage(),
                        'peak_memory_bytes' => memory_get_peak_usage(true),
                    ]);
                }
            }
        }

        $this->assembleZip($tempDir, $chunkFiles, $failedChunks);
    }

    /**
     * Group this batch's fundi ids by district name, sorted alphabetically,
     * with fundis lacking a resolvable district bucketed separately rather
     * than dropped. Ordering within each bucket follows the id order the
     * query returns, matching the single-file export's ordering.
     */
    protected function districtBuckets(): array
    {
        $rows = Fundi::whereIn('fundis.id', $this->fundiIds)
            ->join('wards', 'fundis.ward_id', '=', 'wards.id')
            ->leftJoin('districts', 'wards.district_id', '=', 'districts.id')
            ->orderByRaw('districts.name IS NULL, districts.name asc')
            ->get(['fundis.id as fundi_id', 'districts.name as district_name']);

        $buckets = [];
        foreach ($rows as $row) {
            $label = $row->district_name ? $this->slugify($row->district_name) : 'unassigned';
            $buckets[$label][] = $row->fundi_id;
        }

        return $buckets;
    }

    protected function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    protected function buildRows(array $fundiIds): array
    {
        $definitions = self::columnDefinitions();
        $columns = array_intersect_key($definitions, array_flip($this->columns));

        $eagerLoad = ['ward.district.region'];
        if (isset($columns['registrar'])) {
            $eagerLoad[] = 'creator';
        }
        if (isset($columns['approver'])) {
            $eagerLoad[] = 'approver';
        }

        $rows = [];

        Fundi::whereIn('id', $fundiIds)
            ->with($eagerLoad)
            ->withMafundiReportStats()
            ->chunk(500, function ($fundis) use (&$rows, $columns) {
                foreach ($fundis as $fundi) {
                    $row = [];
                    foreach ($columns as $key => $definition) {
                        $row[$key] = $definition['value']($fundi);
                    }
                    $rows[] = $row;
                }
            });

        return $rows;
    }

    protected function renderPdf(array $rows): string
    {
        $definitions = self::columnDefinitions();
        $columns = array_intersect_key($definitions, array_flip($this->columns));

        $html = view('documents.fundis', [
            'title' => 'Mafundi | Magic Builders',
            'date' => date('D, M/d/Y'),
            'rows' => $rows,
            'columns' => $columns,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->output();
    }

    /**
     * Assemble the ZIP from the chunk files already written to disk, then
     * clean them up. A status file records any failed chunks so the UI can
     * surface partial success rather than silently shipping an incomplete
     * ZIP as if it were whole.
     */
    protected function assembleZip(string $tempDir, array $chunkFiles, array $failedChunks): void
    {
        $zipFileName = "fundis_report_{$this->batchId}.zip";
        $zipPath = "{$tempDir}/{$zipFileName}";

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot create zip file at: {$zipPath}");
        }

        foreach ($chunkFiles as $file) {
            $zip->addFile($file['path'], $file['name']);
        }

        $zip->close();

        $downloadsDir = storage_path('app/public/downloads');
        if (! file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        Storage::disk('public')->put("downloads/{$zipFileName}", file_get_contents($zipPath));

        foreach ($chunkFiles as $file) {
            @unlink($file['path']);
        }
        @unlink($zipPath);

        Storage::disk('public')->put(
            "downloads/fundis_report_{$this->batchId}_status.json",
            json_encode(['file_count' => count($chunkFiles), 'failed_chunks' => $failedChunks])
        );

        Log::info('GenerateFundisPdfReport rendered chunked ZIP', [
            'batch_id' => $this->batchId,
            'file_count' => count($chunkFiles),
            'failed_chunks' => $failedChunks,
        ]);
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
