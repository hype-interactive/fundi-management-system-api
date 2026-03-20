<div>
    <div class="filter mb-3">
        <div class="row align-items-center">
            <div class="col-md-2">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" placeholder="Search operators...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row g-2">
                    @if (isRoot())
                        <div class="col">
                            @include('components.selects.select_region', ['regions' => $regions])
                        </div>
                        <div class="col">
                            @include('components.selects.select_district', ['districts' => $districts])
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-4 ">
                <div class="d-flex justify-content-end gap-2">
                    <button wire:click="refresh" class="mr-2 btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
                <button wire:click="exportToExcel" class="mr-2 btn btn-sm btn-outline-success">
                    <i class="fa-solid fa-file-csv me-1"></i>Export
                </button>
                <button wire:click="downloadOperatorPDF" class="mr-2 btn btn-sm btn-outline-danger">
                    <i class="fa-solid fa-file-pdf me-1"></i>PDF
                </button>
                </div>
                
            </div>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="row g-3">
        @forelse ($operators as $operator)
            @php
                $todayVisits = $operator->siteVisits()->whereDate('created_at', today())->count();
                $todayFundis = $operator->createdFundis()
                ->whereDate('created_at', today())
                ->count();
                $weekVisits = $operator
                    ->siteVisits()
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count();
                $weekFundis = $operator
                    ->createdFundis()
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count();
                $monthVisits = $operator
                    ->siteVisits()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
                $monthFundis = $operator
                    ->createdFundis()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
                $totalVisits = $operator->siteVisits()->count();
                $totalFundis = $operator->createdFundis()->count();
            @endphp

            <div class="col-md-4 col-lg-4" wire:key="op-{{ $operator->id }}">
                <div class="op-card text-decoration-none">

                    {{-- Name + Status --}}
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <a href="{{ route('operator', $operator) }}"
                                class="fw-semibold text-blue lh-sm">{{ $operator->fname }} {{ $operator->lname }}</a>

                        </div>
                        @if ($operator->phone)
                            <small class="text-muted">
                                {{ $operator->phone }}
                            </small>
                        @endif
                        <span
                            class="badge {{ $operator->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-2">
                            {{ ucfirst($operator->status) }}
                        </span>
                    </div>

                    <hr class="my-2 opacity-25">

                    <table class="op-mini-table w-100 mt-2">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Sites</th>
                                <th>Fundis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="op-row-label">Today</td>
                                <td>{{ $todayVisits }}</td>
                                <td>{{ $todayFundis }}</td>
                            </tr>
                            <tr>
                                <td class="op-row-label">Week</td>
                                <td>{{ $weekVisits }}</td>
                                <td>{{ $weekFundis }}</td>
                            </tr>
                            <tr>
                                <td class="op-row-label">Month</td>
                                <td>{{ $monthVisits }}</td>
                                <td>{{ $monthFundis }}</td>
                            </tr>
                            <tr>
                                <td class="op-row-label">Total</td>
                                <td>{{ number_format($totalVisits) }}</td>
                                <td>{{ number_format($totalFundis) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-solid fa-user-tie fa-2x mb-2 d-block opacity-25"></i>
                No operators found.
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $operators->links() }}
    </div>
</div>
