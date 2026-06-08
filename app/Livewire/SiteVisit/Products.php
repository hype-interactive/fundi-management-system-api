<?php

namespace App\Livewire\SiteVisit;

use App\Models\SiteVisit;
use Livewire\Component;

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
