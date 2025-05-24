<div class="site-visit-forms">
    <nav>
        <div class="nav nav-tabs-custom" id="nav-tab" role="tablist">
            <button class="nav-link @if($activeTab === 'details') active @endif"
                    wire:click="switchTab('details')" type="button">
                Site Details
            </button>
            <button class="nav-link @if($activeTab === 'photos') active @endif"
                    wire:click="switchTab('photos')" type="button">
                Photos ({{ $siteVisit->photos->count() }})
            </button>
            <button class="nav-link @if($activeTab === 'products') active @endif"
                    wire:click="switchTab('products')" type="button">
                Products ({{ $siteVisit->products->count() }})
            </button>
            <button class="nav-link @if($activeTab === 'fundis') active @endif"
                    wire:click="switchTab('fundis')" type="button">
                Fundis ({{ $siteVisit->fundis->count() }})
            </button>
            @if ($siteVisit->status === 'rejected' && $siteVisit->rejection_reason)
                <button class="nav-link text-danger @if($activeTab === 'rejection') active @endif"
                        wire:click="switchTab('rejection')" type="button">
                    Rejection Details
                </button>
            @endif
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        @if($activeTab === 'details')
            @livewire('site-visit.details', ['siteVisit' => $siteVisit], key('details-'.$siteVisit->id))
        @elseif($activeTab === 'photos')
            @livewire('site-visit.photos', ['siteVisit' => $siteVisit], key('photos-'.$siteVisit->id))
        @elseif($activeTab === 'products')
            @livewire('site-visit.products', ['siteVisit' => $siteVisit], key('products-'.$siteVisit->id))
        @elseif($activeTab === 'fundis')
            @livewire('site-visit.fundis', ['siteVisit' => $siteVisit], key('fundis-'.$siteVisit->id))
        @elseif($activeTab === 'rejection' && $siteVisit->status === 'rejected')
            <div class="tab-pane fade show active">
                <div class="rejection-section">
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start">
                            <i class="fa-solid fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading">Site Visit Rejected</h5>
                                <p class="mb-0">{{ $siteVisit->rejection_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
