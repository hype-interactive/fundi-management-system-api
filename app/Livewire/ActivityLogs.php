<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ActivityLog;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ActivityLogs extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $users;

    #[Url]
    public $user_id;

    #[Url]
    public $log_type;

    #[Url]
    public $model_type;

    #[Url]
    public $from_date;

    #[Url]
    public $to_date;

    public function updatedUserId()
    {
        $this->resetPage();
    }

    public function updatedLogType()
    {
        $this->resetPage();
    }

    public function updatedModelType()
    {
        $this->resetPage();
    }

    public function updatedFromDate()
    {
        $this->resetPage();
    }

    public function updatedToDate()
    {
        $this->resetPage();
    }

    public function fetch()
    {
        $query = ActivityLog::with('user');

        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        }

        if ($this->log_type) {
            $query->where('log_type', $this->log_type);
        }

        if ($this->model_type) {
            $query->where('model_type', $this->model_type);
        }

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
        }

        return $query->latest('created_at')->paginate(100);
    }

    public function render()
    {
        // Get unique users from activity logs
        $this->users = ActivityLog::with('user')
            ->whereNotNull('user_id')
            ->get()
            ->unique('user_id')
            ->pluck('user')
            ->filter(); // Remove null values

        return view('livewire.activity-logs', ['logs' => $this->fetch()]);
    }
}
