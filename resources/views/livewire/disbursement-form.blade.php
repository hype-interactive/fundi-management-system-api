<div class="disbursement-form">
    <!-- Step Indicator -->
    <div class="step-indicator mb-4">
        <div class="row">
            <div class="col-4">
                <div class="step {{ $step >= 1 ? 'active' : '' }}">
                    <div class="step-number">1</div>
                    <div class="step-title">Basic Info</div>
                </div>
            </div>
            <div class="col-4">
                <div class="step {{ $step >= 2 ? 'active' : '' }}">
                    <div class="step-number">2</div>
                    <div class="step-title">Select Fundis</div>
                </div>
            </div>
            <div class="col-4">
                <div class="step {{ $step >= 3 ? 'active' : '' }}">
                    <div class="step-number">3</div>
                    <div class="step-title">Review</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Basic Information -->
    @if ($step == 1)
        <div class="step-content">
            <h5 class="mb-3">Disbursement Information</h5>

            <div class="form-group">
                <label for="budget_allocation_id">Budget Allocation <span class="text-danger">*</span></label>
                <select wire:model="budget_allocation_id" class="form-control" id="budget_allocation_id">
                    <option value="">Select Budget Allocation</option>
                    @foreach ($budget_allocations as $budget)
                        <option value="{{ $budget->id }}">
                            {{ $budget->title }} ({{ number_format($budget->remaining_amount) }} TZS available)
                        </option>
                    @endforeach
                </select>
                @error('budget_allocation_id')
                    <span class="error text-danger"><small>{{ $message }}</small></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="title">Disbursement Title <span class="text-danger">*</span></label>
                <input type="text" wire:model="title" class="form-control" id="title"
                    placeholder="Enter disbursement title">
                @error('title')
                    <span class="error text-danger"><small>{{ $message }}</small></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea wire:model="description" class="form-control" id="description" rows="3"
                    placeholder="Enter description (optional)"></textarea>
                @error('description')
                    <span class="error text-danger"><small>{{ $message }}</small></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="disbursement_date">Disbursement Date <span class="text-danger">*</span></label>
                <input type="date" wire:model="disbursement_date" class="form-control" id="disbursement_date">
                @error('disbursement_date')
                    <span class="error text-danger"><small>{{ $message }}</small></span>
                @enderror
            </div>
        </div>
    @endif

    <!-- Step 2: Select Fundis -->
    @if ($step == 2)
        <div class="step-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Select Fundis</h5>
                <div class="selected-count">
                    <span class="badge badge-primary">{{ count($selected_fundis) }} selected</span>
                </div>
            </div>

            <!-- Search Fundis -->
            <div class="form-group">
                <div class="search-fundis">
                    <input type="text" wire:model.live.debounce.300ms="search_fundi" class="form-control"
                        placeholder="Search fundis by name or phone...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>

            <!-- Available Fundis -->
            <div class="fundis-list" style="max-height: 400px; overflow-y: auto;">
                @forelse($available_fundis as $fundi)
                    <div class="fundi-item {{ isset($selected_fundis[$fundi->id]) ? 'selected' : '' }}"
                        wire:click="toggleFundi({{ $fundi->id }})">
                        <div class="fundi-checkbox">
                            <input type="checkbox" {{ isset($selected_fundis[$fundi->id]) ? 'checked' : '' }}>
                        </div>
                        <div class="fundi-info">
                            <div class="fundi-name">{{ $fundi->full_name }}</div>
                            <div class="fundi-phone">{{ $fundi->primary_phone }}</div>
                            <small class="text-muted">{{ $fundi->ward->name ?? 'N/A' }}</small>
                        </div>
                        @if (isset($selected_fundis[$fundi->id]))
                            <div class="fundi-amount">
                                <input type="number"
                                    wire:change="updateAmount({{ $fundi->id }}, $event.target.value)"
                                    value="{{ $selected_fundis[$fundi->id]['amount'] }}"
                                    class="form-control form-control-sm" min="1000" step="1000"
                                    onclick="event.stopPropagation()">
                                <small>TZS</small>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-users fa-2x mb-2"></i>
                        <p>No fundis found</p>
                    </div>
                @endforelse
            </div>

            <!-- Bulk Actions -->
            @if (count($selected_fundis) > 0)
                <div class="bulk-actions mt-3 p-3 bg-light rounded">
                    <h6>Bulk Actions</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="number" class="form-control" placeholder="Amount" id="bulkAmount"
                                    min="1000" step="1000">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="button"
                                        onclick="document.querySelector('[wire\\:click=\'bulkSetAmount(\\' + document.getElementById(\'bulkAmount\').value + \')\']').click()">
                                        Set All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-secondary"
                                wire:click="$set('selected_fundis', [])">
                                Clear All
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Step 3: Review -->
    @if ($step == 3)
        <div class="step-content">
            <h5 class="mb-3">Review Disbursement</h5>

            <!-- Summary -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Disbursement Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Title:</strong> {{ $title }}
                        </div>
                        <div class="col-md-6">
                            <strong>Budget:</strong>
                            @if ($budget_allocation_id)
                                {{ $budget_allocations->find($budget_allocation_id)->title ?? 'N/A' }}
                            @endif
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Total Recipients:</strong> {{ count($selected_fundis) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Total Amount:</strong> {{ number_format($total_amount) }} TZS
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Disbursement Date:</strong> {{ $disbursement_date }}
                        </div>
                        <div class="col-md-6">
                            @if ($budget_allocation_id)
                                @php
                                    $budget = $budget_allocations->find($budget_allocation_id);
                                    $remaining = $budget ? $budget->remaining_amount - $total_amount : 0;
                                @endphp
                                <strong>Remaining Budget:</strong>
                                <span class="{{ $remaining >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($remaining) }} TZS
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipients List -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recipients ({{ count($selected_fundis) }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fundi</th>
                                    <th>Phone</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selected_fundis as $fundi)
                                    <tr>
                                        <td>{{ $fundi['name'] }}</td>
                                        <td>{{ $fundi['phone'] }}</td>
                                        <td class="text-right">{{ number_format($fundi['amount']) }} TZS</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Buttons -->
    <div class="step-navigation mt-4">
        <div class="d-flex justify-content-between">
            <div>
                @if ($step > 1)
                    <button type="button" class="btn btn-outline-secondary" wire:click="previousStep">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Previous
                    </button>
                @endif
            </div>
            <div>
                @if ($step < 3)
                    <button type="button" class="btn btn-primary" wire:click="nextStep">
                        Next<i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                @else
                    <button type="button" class="btn btn-success" wire:click="createDisbursement"
                        {{ $saving ? 'disabled' : '' }}>
                        @if ($saving)
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>Creating...
                        @else
                            <i class="fa-solid fa-paper-plane mr-2"></i>Create Disbursement
                        @endif
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Add hidden wire:click for bulk amount -->
    <div wire:click="bulkSetAmount($event.target.dataset.amount)" style="display: none;"></div>
</div>
