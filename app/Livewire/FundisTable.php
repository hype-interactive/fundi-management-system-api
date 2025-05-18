<?php

namespace App\Livewire;

use App\Models\Fundi;
use App\Traits\LocationTrait;
use Livewire\Component;
use Livewire\WithPagination;

class FundisTable extends Component
{
    use WithPagination, LocationTrait;

    protected $paginationTheme = 'bootstrap';

    public $search_key = '';
    public $perPage = 10;

    public $district = null;
    public $ward = null;
    public $gender = null;
    public $ranking = null;
    public $approval_status = null;
    public $created_by = null;
    public $approved_by = null;
    public $legacy_match = null;

    public $districts = [];
    public $wards = [];
    public $gender_options = [];
    public $ranking_options = [];
    public $approval_status_options = [];
    public $creators = [];
    public $approvers = [];

    protected $listeners = [
        'downloadFundiPDF' => 'downloadFundiPDF',
    ];

    public function fetch()
    {
        return $this->getFundiQuery()->paginate($this->perPage);
    }

    public function getFundiQuery()
    {
        $this->getData();

        return Fundi::with('ward')
            ->whereHas('ward', function ($query) {
                $query->whereHas('district', function ($subQuery) {
                    $subQuery->where('id', $this->district);
                });
            })
            // ->where('status', 1)
            ->when($this->search_key, function ($query, $search_key) {
                $query->whereRaw("concat(fname, ' ', mname, ' ', lname) like ?", ['%' . $search_key . '%'])
                    ->orWhere('primary_phone', 'like', '%' . $search_key . '%')
                    ->orWhere('transaction_phone', 'like', '%' . $search_key . '%');
            })
            ->when($this->ward, function ($query, $ward) {
                $query->where('ward_id', $ward);
            })
            ->when($this->gender, function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->when($this->ranking, function ($query, $ranking) {
                $query->where('ranking', $ranking);
            })
            ->when($this->approval_status, function ($query, $approval_status) {
                if ($approval_status == 'approved') {
                    $query->whereNotNull('approved_at');
                } elseif ($approval_status == 'pending') {
                    $query->whereNull('approved_at');
                }
            })
            ->when($this->created_by, function ($query, $created_by) {
                $query->where('created_by', $created_by);
            })
            ->when($this->approved_by, function ($query, $approved_by) {
                $query->where('approved_by', $approved_by);
            })
            ->when($this->legacy_match, function ($query, $legacy_match) {
                $query->where('legacy_match', $legacy_match);
            })
            ->orderBy('fname', 'asc')
            ->latest('created_at');
    }

    public function getData()
    {
        $this->districts = $this->getDistricts();
        $this->wards = $this->getWards($this->district);
        $this->gender_options = ['male' => 'Male', 'female' => 'Female'];
        $this->ranking_options = ['fundi_kuu' => 'Fundi Mkuu', 'fundi_msaidizi' => 'Fundi'];
        $this->approval_status_options = ['approved' => 'Approved', 'pending' => 'Pending Approval'];
        $this->creators = $this->getUsers('created_by');
        $this->approvers = $this->getUsers('approved_by');
    }

    /**
     * Get users who have created or approved fundis
     */
    private function getUsers(string $type)
    {
        return \App\Models\User::whereIn('id', function ($query) use ($type) {
            $query->select($type)
                ->from('fundis')
                ->whereNotNull($type)
                ->distinct();
        })->get();
    }

    /**
     * Download the PDF of fundis
     */
    public function downloadFundiPDF()
    {
        set_time_limit(300); // execution time to 5min
        ini_set("memory_limit", "1000M");

        return response()->streamDownload(function () {
            // Get the fundis query
            $fundis = $this->getFundiQuery()->get();

            // Create an array of rows
            $rows = [];
            foreach ($fundis as $fundi) {
                $rows[] = [
                    'name' => $fundi->full_name,
                    'address' => $fundi->ward->name . ', ' . $fundi->ward->district->name,
                    'phone' => $fundi->primary_phone,
                    'gender' => ucfirst($fundi->gender),
                    'ranking' => $this->ranking_options[$fundi->ranking] ?? 'N/A',
                    'status' => $fundi->approved_at ? 'Approved' : 'Pending',
                ];
            }

            $title = 'Fundis | Mafundi';
            $date = date('D, M/d/Y');
            $html_view = view('documents.fundis', compact('title', 'date', 'rows'))->render();
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html_view);

            $pdf->setPaper('A4', 'landscape');

            echo $pdf->stream();
        }, 'magic_builders_mafundi.pdf');
    }

    public function mount()
    {
        // If district is not already set, get the user's primary district, fallback to Dar es Salaam
        $this->district = currentUser()?->primary_district?->id ?? 2;
    }

    public function refresh()
    {
        $this->reset();
    }

    public function render()
    {
        return view('livewire.fundis-table', [
            'fundis' => $this->fetch(),
        ]);
    }
}
