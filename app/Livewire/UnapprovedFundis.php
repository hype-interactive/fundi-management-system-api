<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\LocationTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnapprovedFundis extends Component
{
    use WithPagination, LocationTrait;

    public $district;
    public $selectedFundis = [];
    public $selectAll = false;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['refreshFundis' => '$refresh'];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedFundis = $this->fetch()->pluck('id')->toArray();
        } else {
            $this->selectedFundis = [];
        }
    }

    public function approve(int $fundi_id)
    {
        $fundi = Fundi::find($fundi_id);

        if (!$fundi) {
            session()->flash('error', 'Fundi not found');
            return;
        }

        // Check for duplicate
        if (Fundi::where([
                ['fname', $fundi->fname],
                ['lname', $fundi->lname],
                ['primary_phone', $fundi->primary_phone],
                ['ward_id', $fundi->ward_id],
            ])->where('id', '!=', $fundi_id)
            ->whereNotNull('approved_at')
            ->count() > 0) {
            session()->flash('message', 'Fundi already exists');
            $fundi->delete();
            return;
        }

        $fundi->update([
            "approved_at" => now(),
            "approved_by" => Auth::id(),
            "status" => "active"
        ]);

        // addLog('edit', "Fundi [" . $fundi_id . "] approved");
    }

    public function delete($fundi_id)
    {
        try {
            Fundi::where('id', $fundi_id)->delete();
            // TODO: Add log for action
        } catch (\Throwable $th) {
            session()->flash('error', 'Failed to delete fundis');
        }
    }

    public function mount()
    {
        $currentUser = currentUser();

        // Set default district based on user permissions
        if ($currentUser && !($currentUser->role->name === 'admin' || $currentUser->role->name === 'root')) {
            $this->district = $currentUser->primary_district->id ?? null;
        }
    }

    public function fetch()
    {
        $query = Fundi::with(['ward.district', 'creator'])
            ->whereNull('approved_at');

        // Apply district filter if set
        if ($this->district) {
            $query->whereHas('ward', function ($q) {
                $q->whereHas('district', function ($sq) {
                    $sq->where('id', $this->district);
                });
            });
        }

        return $query->latest('created_at')->paginate(10);
    }

    public function render()
    {
        return view('livewire.unapproved-fundis', [
            'fundis' => $this->fetch()
        ]);
    }
}
