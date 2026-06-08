{{-- resources/views/livewire/fundi-duplicates-table.blade.php --}}
<div>
    <div class="row mb-3">
    <div class="col-md-3 col-6 mb-2">
        <div class="card border-danger shadow-sm {{ $duplicateType === 'primary_phone' ? 'bg-danger text-white' : '' }}"
             wire:click="$set('duplicateType', 'primary_phone')"
             style="cursor: pointer;">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold">
                    {{ number_format($stats['primary_phone']) }}
                </h5>
                <small class="mb-0">Primary Phone</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-warning shadow-sm {{ $duplicateType === 'transaction_phone' ? 'bg-warning text-dark' : '' }}"
             wire:click="$set('duplicateType', 'transaction_phone')"
             style="cursor: pointer;">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold">
                    {{ number_format($stats['transaction_phone']) }}
                </h5>
                <small class="mb-0">Transaction Phone</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-info shadow-sm {{ $duplicateType === 'name_ward' ? 'bg-info text-white' : '' }}"
             wire:click="$set('duplicateType', 'name_ward')"
             style="cursor: pointer;">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold">
                    {{ number_format($stats['name_ward']) }}
                </h5>
                <small class="mb-0">Name + Ward</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-secondary shadow-sm {{ $duplicateType === 'nida_number' ? 'bg-secondary text-white' : '' }}"
             wire:click="$set('duplicateType', 'nida_number')"
             style="cursor: pointer;">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold">
                    {{ number_format($stats['nida_number']) }}
                </h5>
                <small class="mb-0">NIDA</small>
            </div>
        </div>
    </div>
</div>




    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">
                @switch($duplicateType)
                    @case('primary_phone')
                        Primary Phone Duplicates
                    @break

                    @case('transaction_phone')
                        Transaction Phone Duplicates
                    @break

                    @case('name_ward')
                        Name + Ward Duplicates
                    @break

                    @case('nida_number')
                        NIDA Duplicates
                    @break
                @endswitch
            </h5>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="refresh" class="btn btn-outline-secondary btn-sm mr-2">
                Refresh
            </button>
            @if ($stats[$duplicateType] > 0)
                <button wire:click="bulkDeleteAllDuplicates" class="btn btn-danger btn-sm">
                    Delete All Duplicates (Keep Oldest)
                </button>
            @endif
        </div>
    </div>

    <!-- Duplicates Table -->
    <div class="custom-table">
        <div class="card-body p-0">
            @if ($duplicates->count() > 0)
                <div class="table table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>
                                    @switch($duplicateType)
                                        @case('primary_phone')
                                            Primary Phone
                                        @break

                                        @case('transaction_phone')
                                            Transaction Phone
                                        @break

                                        @case('name_ward')
                                            Name + Ward
                                        @break

                                        @case('nida_number')
                                            NIDA Number
                                        @break
                                    @endswitch
                                </th>
                                <th class="text-center" style="width: 100px;">Count</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($duplicates as $index => $duplicate)
                                @php
                                    $key = md5($duplicate->duplicate_key);
                                    $isExpanded = in_array($key, $showDetails);
                                @endphp
                                <tr class="{{ $isExpanded ? 'table-active' : '' }}">
                                    <td class="d-lg-table-cell d-none">{{ $duplicates->firstItem() + $index }}</td>
                                    <td class="d-lg-table-cell d-none">
                                        <button wire:click="toggleDetails('{{ $key }}')"
                                            class="btn btn-link p-0 text-decoration-none">
                                            <i
                                                class="fa-solid fa-chevron-{{ $isExpanded ? 'down' : 'right' }} me-2"></i>
                                            @if ($duplicateType === 'name_ward')
                                                <strong>{{ $duplicate->fname }} {{ $duplicate->lname }}</strong>
                                                <span class="text-muted ms-2">
                                                    ({{ $duplicate->fundis->first()?->ward?->name ?? 'N/A' }})
                                                </span>
                                            @else
                                                <span>{{ $duplicate->duplicate_key }}</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $duplicate->count > 2 ? 'danger' : 'warning' }}">
                                            {{ $duplicate->count }} records
                                        </span>
                                    </td>
                                    <td>
                                        <button
                                            wire:click="keepOldestDeleteRest('{{ $duplicate->duplicate_key }}', '{{ $duplicate->duplicate_field }}')"
                                            class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-broom me-1"></i>
                                            Keep Oldest
                                        </button>
                                    </td>
                                </tr>
                                @if ($isExpanded)
                                    <tr>
                                        <td colspan="4" class="p-0">
                                            <div class="bg-light p-3">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-secondary" >
                                                            <th style="width: 60px;">ID</th>
                                                            <th>Name</th>
                                                            <th>Primary Phone</th>
                                                            <th>Trans. Phone</th>
                                                            <th>Ward</th>
                                                            <th>Status</th>
                                                            <th>Created</th>
                                                            <th style="width: 120px;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($duplicate->fundis as $fundi)
                                                            <tr class="{{ $loop->first ? 'table-success' : '' }}">
                                                                <td>
                                                                    {{ $fundi->id }}
                                                                </td>
                                                                <td>
                                                                    <a href="{{ route('fundi', $fundi) }}"
                                                                        target="_blank" class="text-decoration-none">
                                                                        {{ $fundi->fname }} {{ $fundi->mname }}
                                                                        {{ $fundi->lname }}
                                                                    </a>
                                                                </td>
                                                                <td>{{ $fundi->primary_phone }}</td>
                                                                <td>{{ $fundi->transaction_phone ?? '-' }}</td>
                                                                <td>
                                                                    <small>
                                                                        {{ $fundi->ward->name ?? 'N/A' }},
                                                                        {{ $fundi->ward->district->name ?? '' }}
                                                                    </small>
                                                                </td>
                                                                <td>
                                                                    @include(
                                                                        'components.approval-status-badge',
                                                                        [
                                                                            'approved_at' => $fundi->approved_at,
                                                                            'rejected_at' => $fundi->rejected_at,
                                                                        ]
                                                                    )
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">
                                                                        {{ $fundi->created_at->format('M d, Y') }}
                                                                        <br>
                                                                        {{ $fundi->creator->full_name ?? 'N/A' }}
                                                                    </small>
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <a href="{{ route('fundi', $fundi) }}"
                                                                            class="text-primary mr-2"
                                                                            target="_blank" title="View">
                                                                            View
                                                                        </a>
                                                                        @if (!$loop->first)

                                                                            <a
                                                                                wire:click="deleteFundi({{ $fundi->id }})"
                                                                                class="text-danger"
                                                                                >
                                                                                Delete
                                                                            </a>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <h5 class="text-muted">No Duplicates Found</h5>
                    <p class="text-muted">
                        There are no duplicate records for
                        @switch($duplicateType)
                            @case('primary_phone')
                                primary phone numbers
                            @break

                            @case('transaction_phone')
                                transaction phone numbers
                            @break

                            @case('name_ward')
                                name + ward combinations
                            @break

                            @case('nida_number')
                                NIDA numbers
                            @break
                        @endswitch
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination -->
    @if ($duplicates->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $duplicates->links() }}
        </div>
    @endif
</div>
