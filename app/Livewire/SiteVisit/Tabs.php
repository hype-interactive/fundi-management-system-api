<?php

namespace App\Livewire\SiteVisit;

use App\Models\SiteVisit;
use Livewire\Component;

class Tabs extends Component
{
    public SiteVisit $siteVisit;

    public string $activeTab = 'details';

    protected $listeners = [
        'tabChanged',
        'visitStatusChanged' => '$refresh',
    ];

    public function mount(SiteVisit $siteVisit)
    {
        $this->siteVisit = $siteVisit;
        $this->siteVisit->load(['photos', 'products', 'fundis', 'operator', 'ward.district.region', 'approver']);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->dispatch('tabChanged', $tab);
    }

    public function render()
    {
        return view('livewire.site-visit.tabs');
    }
}
