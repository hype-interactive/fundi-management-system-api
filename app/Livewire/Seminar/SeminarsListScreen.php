<?php

namespace App\Livewire\Seminar;

use Livewire\Component;

class SeminarsListScreen extends Component
{

    public $showMobileFilters = false;
    public $search_key= '';
    public $status = 'all';
    public $date_from = null;
    public $date_to = null;
     public $regions = [];
    public $districts = [];
    public $wards = [];

    public $status_options = [
        'all' => 'All',
        'upcoming' => 'Upcoming',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function render()
    {
        return view('livewire.seminar.seminars-list-screen');
    }
}
