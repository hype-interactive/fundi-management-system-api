<?php

namespace App\Http\Controllers;

use App\Models\Fundi;
use App\Services\FundiListPdf;
use App\Services\FundiListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads for the fundis list page.
 *
 * These are plain routes, not Livewire actions, because Livewire holds a
 * download in memory and sends it base64-encoded inside its JSON response.
 */
class FundiExportController extends Controller
{
    private const CHUNK_SIZE = 500;

    public function __construct(private FundiListQuery $fundis) {}

    public function excel(Request $request): StreamedResponse
    {
        $query = $this->fundis->forExcel($this->filters($request));

        return response()->streamDownload(function () use ($query) {
            set_time_limit(0);

            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Name',
                'Gender',
                'Primary Phone',
                'Transaction Phone',
                'NIDA Number',
                'Ward',
                'District',
                'Region',
                'Ranking',
                'Status',
                'Registered By',
                'Approved By',
                'Created At',
                'Approved At',
            ]);

            $this->eachChunk($query, function ($fundis) use ($handle) {
                foreach ($fundis as $fundi) {
                    fputcsv($handle, [
                        $fundi->full_name,
                        ucfirst($fundi->gender),
                        $fundi->primary_phone,
                        $fundi->transaction_phone,
                        $fundi->nida_number,
                        $fundi->ward->name ?? 'N/A',
                        $fundi->ward->district->name ?? 'N/A',
                        $fundi->ward->district->region->name ?? 'N/A',
                        $fundi->fundi_ranking,
                        $fundi->approval_status,
                        $fundi->creator->full_name ?? 'N/A',
                        $fundi->approver->full_name ?? 'N/A',
                        $fundi->created_at->format('Y-m-d H:i:s'),
                        $fundi->approved_at ? $fundi->approved_at->format('Y-m-d H:i:s') : 'N/A',
                    ]);
                }

                flush();
            });

            fclose($handle);
        }, 'fundis_export_'.date('Y-m-d_H-i-s').'.csv', [
            'Content-Type' => 'text/csv',
            // Send rows to the browser as they are written instead of buffering in nginx.
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function pdf(Request $request): Response|RedirectResponse
    {
        $filters = $this->filters($request);

        if ($this->fundis->build($filters)->count() > FundiListPdf::MAX_ROWS) {
            return back(fallback: route('fundis'))->with('alert', [
                'type' => 'error',
                'title' => 'Too many fundis',
                'message' => 'A PDF can hold up to '.number_format(FundiListPdf::MAX_ROWS).' fundis. Please narrow your filters or use Export Excel.',
            ]);
        }

        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $pdf = new FundiListPdf('Mafundi | Magic Builders', date('D, M/d/Y'));

        $this->eachChunk($this->fundis->forPdf($filters), function ($fundis) use ($pdf) {
            foreach ($fundis as $fundi) {
                $pdf->addRow([
                    $fundi->full_name,
                    $this->location($fundi),
                    $fundi->primary_phone,
                    $fundi->transaction_phone,
                    ucfirst($fundi->gender),
                    $fundi->sites_count,
                    $fundi->bags_total,
                ]);
            }
        });

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                'magic_builders_mafundi.pdf'
            ),
        ]);
    }

    /**
     * Walk the results newest first without an OFFSET, which slows down with every chunk.
     */
    private function eachChunk(Builder $query, callable $callback): void
    {
        $query->reorder()->chunkByIdDesc(self::CHUNK_SIZE, $callback, 'fundis.id', 'id');
    }

    private function location(Fundi $fundi): string
    {
        return collect([
            $fundi->ward?->name,
            $fundi->ward?->district?->name,
            $fundi->ward?->district?->region?->name,
        ])->filter()->implode(', ');
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'search_key' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'integer'],
            'district' => ['nullable', 'integer'],
            'ward' => ['nullable', 'integer'],
            'gender' => ['nullable', 'string', 'max:20'],
            'site_visit_count' => ['nullable', 'in:0,1-5,5+'],
            'approval_status' => ['nullable', 'in:approved,pending,rejected'],
            'created_by' => ['nullable', 'integer'],
            'approved_by' => ['nullable', 'integer'],
            'legacy_match' => ['nullable', 'string', 'max:20'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
    }
}
