<?php

namespace App\Livewire\SiteVisit;

use Livewire\Component;
use App\Models\SiteVisit;

class Products extends Component
{
    public SiteVisit $siteVisit;

    public function mount(SiteVisit $siteVisit)
    {
        $this->siteVisit = $siteVisit;
    }

    public function render()
    {
        return view('livewire.site-visit.products');
    }
}
