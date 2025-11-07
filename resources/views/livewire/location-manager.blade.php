<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- View Switcher Tabs --}}
    <ul class="nav nav-tabs mb-4 mt-5 w-full" role="tablist">
        <li class="nav nav-tabs-custom w-1/2" role="presentation">
            <button wire:click="switchView('districts')" class="nav-link {{ $view === 'districts' ? 'active' : '' }}"
                type="button">
                Districts
            </button>
        </li>
        <li class="nav nav-tabs-custom w-1/2" role="presentation">
            <button wire:click="switchView('wards')" class="nav-link {{ $view === 'wards' ? 'active' : '' }}"
                type="button">
                Wards
            </button>
        </li>
    </ul>
    {{-- Filters Section --}}
    <div class="filter mb-3">
        <div class="row">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search" type="text" id="search"
                        placeholder="Search {{ $view }}...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row g-2">
                    @if ($view === 'districts')
                        <div class="col-md-4">
                            <select wire:model.live="filterRegionId" class="form-control">
                                <option value="">-- All Regions --</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-md-4">
                            <select wire:model.live="filterDistrictId" class="form-control">
                                <option value="">-- All Districts --</option>
                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-8 d-flex justify-content-end gap-2">
                        <button wire:click="refresh" class="btn btn-outline-secondary btn-sm mr-3">
                            <i class="fa-solid fa-arrows-rotate me-1"></i>
                            Clear Filters
                        </button>
                        @if ($view === 'districts')
                            <button wire:click="createDistrict" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-plus me-1"></i>
                                Add District
                            </button>
                        @else
                            <button wire:click="createWard" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-plus me-1"></i>
                                Add Ward
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Results Count --}}
    <div class="results-summary mb-2">
        <small class="text-muted">
            Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            {{ $view }}
        </small>
    </div>

    {{-- Table Container --}}
    <div class="custom-table">
        <!-- Desktop Table -->
        <div class="d-none d-md-block">
            <table class="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">
                            <a wire:click="refresh" style="cursor: pointer;">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        </th>
                        @if ($view === 'districts')
                            <th scope="col">District Name</th>
                            <th scope="col" class="d-lg-table-cell d-none">Region</th>
                            <th scope="col" class="d-lg-table-cell d-none">Wards Count</th>
                        @else
                            <th scope="col">Ward Name</th>
                            <th scope="col" class="d-lg-table-cell d-none">District</th>
                            <th scope="col" class="d-lg-table-cell d-none">Region</th>
                        @endif
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <th scope="row">
                                <div class="avatar-circle">
                                    <i class="fa-solid fa-{{ $view === 'districts' ? 'map' : 'map-pin' }}"></i>
                                </div>
                            </th>
                            @if ($view === 'districts')
                                <td>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="text-muted">{{ $item->region->name }}</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="badge bg-primary">{{ $item->wards->count() }} wards</span>
                                </td>
                            @else
                                <td>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="text-muted">{{ $item->district->name }}</span>
                                </td>
                                <td class="d-lg-table-cell d-none">
                                    <span class="text-muted">{{ $item->district->region->name }}</span>
                                </td>
                            @endif
                            <td class="text-end">
                                @if ($view === 'districts')
                                    <button wire:click="editDistrict({{ $item->id }})"
                                        class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button wire:click="deleteDistrict({{ $item->id }})"
                                        wire:confirm="Are you sure you want to delete this district?"
                                        class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @else
                                    <button wire:click="editWard({{ $item->id }})"
                                        class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button wire:click="deleteWard({{ $item->id }})"
                                        wire:confirm="Are you sure you want to delete this ward?"
                                        class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i
                                    class="fa-solid fa-{{ $view === 'districts' ? 'map' : 'map-pin' }} fa-2x mb-2 d-block"></i>
                                No {{ $view }} found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="mobile-cards d-md-none">
            @forelse($items as $item)
                <div class="card mb-3" wire:key="mobile-{{ $item->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">
                                    <i class="fa-solid fa-{{ $view === 'districts' ? 'map' : 'map-pin' }}"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">{{ $item->name }}</h6>
                                    @if ($view === 'districts')
                                        <small class="text-muted">
                                            <i class="fa-solid fa-location-dot me-1"></i>{{ $item->region->name }}
                                        </small>
                                    @else
                                        <small class="text-muted">
                                            <i class="fa-solid fa-map me-1"></i>{{ $item->district->name }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                @if ($view === 'districts')
                                    <button wire:click="editDistrict({{ $item->id }})"
                                        class="btn btn-outline-primary" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button wire:click="deleteDistrict({{ $item->id }})"
                                        wire:confirm="Are you sure?" class="btn btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @else
                                    <button wire:click="editWard({{ $item->id }})"
                                        class="btn btn-outline-primary" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button wire:click="deleteWard({{ $item->id }})" wire:confirm="Are you sure?"
                                        class="btn btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if ($view === 'districts')
                            <div class="mt-2">
                                <span class="badge bg-primary">{{ $item->wards->count() }} wards</span>
                            </div>
                        @else
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fa-solid fa-location-dot me-1"></i>{{ $item->district->region->name }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="fa-solid fa-{{ $view === 'districts' ? 'map' : 'map-pin' }} fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No {{ $view }} found</h5>
                    <p class="text-muted">Try adjusting your search criteria.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $items->links() }}
    </div>

    {{-- District Modal --}}
    @if ($showDistrictModal)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1"
            wire:click="closeDistrictModal">
            <div class="modal-dialog modal-dialog-centered" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-map me-2"></i>
                            {{ $isEditingDistrict ? 'Edit District' : 'Add New District' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" wire:click="closeDistrictModal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="districtName" class="form-label">District Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" wire:model="districtName"
                                class="form-control @error('districtName') is-invalid @enderror" id="districtName"
                                placeholder="Enter district name">
                            @error('districtName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="districtRegion" class="form-label">Region <span
                                    class="text-danger">*</span></label>
                            <select wire:model="districtRegionId"
                                class="form-control @error('districtRegionId') is-invalid @enderror"
                                id="districtRegion">
                                <option value="">-- Select Region --</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('districtRegionId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDistrictModal">
                            <i class="fa-solid fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveDistrict">
                            <i class="fa-solid fa-{{ $isEditingDistrict ? 'save' : 'plus' }} me-1"></i>
                            {{ $isEditingDistrict ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Ward Modal --}}
    @if ($showWardModal)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1"
            wire:click="closeWardModal">
            <div class="modal-dialog modal-dialog-centered" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-map-pin me-2"></i>
                            {{ $isEditingWard ? 'Edit Ward' : 'Add New Ward' }}
                        </h5>
                        <button type="button" class="close" data-dismiss="" wire:click="closeWardModal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="wardName" class="form-label">Ward Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" wire:model="wardName"
                                class="form-control @error('wardName') is-invalid @enderror" id="wardName"
                                placeholder="Enter ward name">
                            @error('wardName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="wardDistrict" class="form-label">District <span
                                    class="text-danger">*</span></label>
                            <select wire:model="wardDistrictId"
                                class="form-control @error('wardDistrictId') is-invalid @enderror" id="wardDistrict">
                                <option value="">-- Select District --</option>
                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}">
                                        {{ $district->name }} ({{ $district->region->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('wardDistrictId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeWardModal">
                            <i class="fa-solid fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveWard">
                            <i class="fa-solid fa-{{ $isEditingWard ? 'save' : 'plus' }} me-1"></i>
                            {{ $isEditingWard ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
