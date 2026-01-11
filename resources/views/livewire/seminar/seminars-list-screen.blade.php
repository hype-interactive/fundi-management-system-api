<div>
    <!-- Enhanced Filters Section with Two Rows -->
    <div class="filter m-3 {{ !$showMobileFilters ? 'd-none d-md-block' : '' }}">

            
            <!-- Primary Location Filters -->
            <div class="col-md-12 col-12">
                <div class="row g-2">
                    <div class="col-md-2 col-12">
                <div class="search position-relative">
                    <input wire:model.live.debounce.300ms="search_key" type="text" id="search"
                        placeholder="Search site name, operator...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    @if ($search_key)
                        <button wire:click="$set('search_key', '')" class="btn btn-sm position-absolute"
                            style="right: 35px; top: 50%; transform: translateY(-50%); border: none; background: none;">
                            <i class="fa-solid fa-times text-muted"></i>
                        </button>
                    @endif
                </div>
            </div>
                <div class="col-lg-2 col-md-4 col-6">
                            <div class="select">
                                @include('components.selects.select_region')
                            </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                            <div class="select">
                                @include('components.selects.select_district')
                            </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-6">
                        <div class="select">
                            <select wire:model.live="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                @foreach ($status_options as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Date Filters -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="select">
                            <input wire:model.live="date_from" type="date" class="form-select form-select-sm"
                                placeholder="From Date" title="From Date">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6 ">
                        <div class="select">
                            <input wire:model.live="date_to" type="date" class="form-select form-select-sm"
                                placeholder="To Date" title="To Date">
                        </div>
                    </div>
                </div>


            </div>

    </div>

    <!-- Desktop Actions -->
    {{-- <div class="results-summary mb-2 d-none d-md-flex justify-content-between align-items-center">
        <div class="d-flex gap-2 align-items-center">
            <button wire:click="toggleCompactView"
                class="btn btn-sm {{ $compactView ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fa-solid fa-{{ $compactView ? 'expand' : 'compress' }}"></i>
                {{ $compactView ? 'Expand' : 'Compact' }}
            </button>

            <small class="text-muted ms-2">
                Showing {{ $siteVisits->count() }} of {{ $siteVisits->total() }} site visits
                @if ($search_key || $status || $operator_id || $visit_date_from || $visit_date_to)
                    (filtered)
                @endif
            </small>
        </div>

        <div class="d-flex gap-2">
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrows-rotate me-1"></i>
                Clear Filters
            </button>
            <button wire:click="exportToExcel" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i>
                Export Excel
            </button>
            <button wire:click="downloadSiteVisitPDF" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-file-pdf me-1"></i>
                Download PDF
            </button>
        </div>
    </div> --}}
</div>
