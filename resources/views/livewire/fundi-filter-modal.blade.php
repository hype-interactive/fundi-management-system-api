<div>
    <div wire:ignore.self class="modal fade" id="fundiFilterModal" tabindex="-1" aria-labelledby="fundiFilterModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="fundiFilterModalLabel">Filter Mafundi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Region</label>
                            @include('components.selects.select_region')
                        </div>
                        <div class="col-md-4" wire:key="district-wrap-{{ $region }}">
                            <label class="form-label small mb-1">District</label>
                            @include('components.selects.select_district')
                        </div>
                        <div class="col-md-4" wire:key="ward-wrap-{{ $region }}-{{ $district }}">
                            <label class="form-label small mb-1">Ward</label>
                            @include('components.selects.select_ward')
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small mb-1">Gender</label>
                            @include('components.selects.select_gender')
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Ranking</label>
                            @include('components.selects.select_ranking')
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Registrar</label>
                            @include('components.selects.select_created_by')
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Min Site Visits</label>
                            <input type="number" min="0" wire:model="site_visit_min" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Max Site Visits</label>
                            <input type="number" min="0" wire:model="site_visit_max" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Min Putty Bags</label>
                            <input type="number" min="0" wire:model="bags_min" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Max Putty Bags</label>
                            <input type="number" min="0" wire:model="bags_max" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small mb-1">Registered from</label>
                            <input type="date" wire:model="date_from" class="form-control form-control-sm"
                                max="{{ $date_to ?: now()->toDateString() }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Registered to</label>
                            <input type="date" wire:model="date_to" class="form-control form-control-sm"
                                min="{{ $date_from }}" max="{{ now()->toDateString() }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" wire:click="clear" class="btn btn-outline-secondary btn-sm">
                        Clear
                    </button>
                    <button type="button" wire:click="apply" data-dismiss="modal" class="btn btn-primary btn-sm">
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
