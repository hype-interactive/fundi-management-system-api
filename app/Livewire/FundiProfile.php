<?php

namespace App\Livewire;

use App\Models\Fundi;
use Livewire\Component;

class FundiProfile extends Component
{
    public $fundi;
    public $transactions;
    public $siteVisits;
    public $legacyMatch = false;
    public $legacyData = null;

    public function mount(Fundi $fundi)
    {
        $this->fundi = $fundi;
        $this->loadTransactions();
        $this->loadSiteVisits();
        $this->checkLegacyMatch();
    }

    public function loadTransactions()
    {
        // Get the latest 3 transactions for the sidebar
        $this->transactions = $this->fundi->transactions()
            ->latest()
            ->take(3)
            ->get();
    }

    public function loadSiteVisits()
    {
        // Get the latest 3 site visits for the sidebar
        $this->siteVisits = $this->fundi->siteVisits()
            ->latest()
            ->take(3)
            ->get();
    }

    public function checkLegacyMatch()
    {
        if ($this->fundi->legacy_match) {
            $this->legacyMatch = true;
            // In a real application, you would fetch legacy data here
            // For now we'll just simulate it
            $this->legacyData = [
                'legacy_id' => $this->fundi->legacy_id,
                'registration_date' => '2021-05-15', // Example date
            ];

            // Dispatch browser event to notify about legacy match
            $this->dispatch('fundiLegacyMatch');
        }
    }

    public function render()
    {
        return view('livewire.fundi-profile');
    }
}
