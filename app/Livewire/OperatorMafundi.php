<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class OperatorMafundi extends Component
{
    use WithPagination;

    public User $operator;

    protected $paginationTheme = 'bootstrap';

    public function mount(User $operator)
    {
        $this->operator = $operator->load(['role', 'districts']);
    }

    public function refresh()
    {
        $this->resetPage();
    }

    /**
     * Build operator + mafundi payload for reports (mirrors detail page).
     */
    protected function buildReportData(): array
    {
        $fundis = $this->operator->createdFundis()
            ->with(['ward.district.region'])
            ->latest('created_at')
            ->get();

        return [
            'name' => $this->operator->full_name,
            'phone' => $this->operator->phone,
            'email' => $this->operator->email,
            'status' => ucfirst($this->operator->status),
            'role' => $this->operator->role?->display_name ?? $this->operator->role?->name ?? 'N/A',
            'districts' => $this->operator->districts->pluck('name')->join(', '),
            'fundis' => $fundis->map(function ($fundi) {
                return [
                    'name' => $fundi->full_name,
                    'primary_phone' => $fundi->primary_phone ?: 'N/A',
                    'transaction_phone' => $fundi->transaction_phone ?: 'N/A',
                    'address' => $fundi->address,
                ];
            })->all(),
        ];
    }

    public function downloadMafundiPDF()
    {
        set_time_limit(300);
        ini_set('memory_limit', '1000M');

        $operatorName = str_replace(' ', '_', strtolower($this->operator->full_name));

        return response()->streamDownload(function () {
            $operators = [$this->buildReportData()];
            $title = "Operator Mafundi - {$this->operator->full_name} | Magic Builders";
            $date = date('D, M/d/Y');
            $filters = null;

            $html_view = view('documents.operator-mafundi', compact('title', 'date', 'operators', 'filters'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);
            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, "operator_mafundi_{$operatorName}_".date('Y-m-d_H-i-s').'.pdf');
    }

    public function exportToExcel()
    {
        $operatorName = str_replace(' ', '_', strtolower($this->operator->full_name));

        return response()->streamDownload(function () {
            $data = $this->buildReportData();
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Operator Report']);
            fputcsv($handle, ['Name', $data['name']]);
            fputcsv($handle, ['Phone', $data['phone'] ?: 'N/A']);
            fputcsv($handle, ['Email', $data['email'] ?: 'N/A']);
            fputcsv($handle, ['Status', $data['status']]);
            fputcsv($handle, ['Role', $data['role']]);
            fputcsv($handle, ['Districts', $data['districts'] ?: 'N/A']);
            fputcsv($handle, ['Total Mafundi', count($data['fundis'])]);
            fputcsv($handle, []);
            fputcsv($handle, ['#', 'Name', 'Primary Phone', 'Transaction Phone', 'Address']);

            foreach ($data['fundis'] as $index => $fundi) {
                fputcsv($handle, [
                    $index + 1,
                    $fundi['name'],
                    $fundi['primary_phone'],
                    $fundi['transaction_phone'],
                    $fundi['address'],
                ]);
            }

            fclose($handle);
        }, "operator_mafundi_{$operatorName}_".date('Y-m-d_H-i-s').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('livewire.operator-mafundi', [
            'fundis' => $this->operator->createdFundis()
                ->with(['ward.district.region'])
                ->latest('created_at')
                ->paginate(12),
        ]);
    }
}
