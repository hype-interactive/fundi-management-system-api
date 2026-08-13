<div>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="text-muted small">
            <i class="fa-solid fa-users me-1"></i>
            {{ $fundis->total() }} mafundi registered
        </div>
        <div class="d-flex gap-2">
            <button wire:click="exportToExcel" class="btn btn-sm btn-outline-success" wire:loading.attr="disabled">
                <i class="fa-solid fa-file-csv me-1"></i>
                <span wire:loading.remove wire:target="exportToExcel">Export</span>
                <span wire:loading wire:target="exportToExcel">Exporting...</span>
            </button>
            <button wire:click="downloadMafundiPDF" class="btn btn-sm btn-outline-danger" wire:loading.attr="disabled">
                <i class="fa-solid fa-file-pdf me-1"></i>
                <span wire:loading.remove wire:target="downloadMafundiPDF">PDF</span>
                <span wire:loading wire:target="downloadMafundiPDF">Generating...</span>
            </button>
        </div>
    </div>

    <div class="d-none d-md-block">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th scope="col">
                        <a wire:click="refresh" href="javascript:void(0)">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </a>
                    </th>
                    <th scope="col">Name</th>
                    <th scope="col">Gender</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Address</th>
                    <th scope="col">Visits</th>
                    <th scope="col">Putty Bags</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fundis as $fundi)
                    <tr>
                        <th scope="row">
                            <a href="{{ route('fundi', [$fundi]) }}" class="avatar-circle">
                                <i class="fa-solid fa-user"></i>
                            </a>
                        </th>
                        <td>
                            <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none">
                                <div class="fundi-name">
                                    <span class="fw-semibold">{{ $fundi->fname }} {{ $fundi->lname }}</span>
                                </div>
                            </a>
                        </td>
                        <td>{{ $fundi->gender ? ucfirst($fundi->gender) : 'N/A' }}</td>
                        <td>
                            <div class="contact-info">
                                <div class="phone-number">{{ $fundi->primary_phone }}</div>
                                @if ($fundi->transaction_phone && $fundi->transaction_phone !== $fundi->primary_phone)
                                    <small class="text-muted">{{ $fundi->transaction_phone }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            {{ $fundi->address }}
                        </td>
                        <td>{{ $fundi->site_visits_count ?? 0 }}</td>
                        <td>{{ $fundi->bags_total ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fa-solid fa-users me-2"></i>
                            No fundis found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrapper d-flex justify-content-center mt-3">
            {{ $fundis->links() }}
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="d-md-none">
        @forelse ($fundis as $fundi)
            <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none text-dark">
                <div class="border rounded p-3 mb-2">
                    <div class="fw-semibold">{{ $fundi->fname }} {{ $fundi->lname }}</div>
                    <div class="small text-muted">{{ $fundi->gender ? ucfirst($fundi->gender) : 'N/A' }} · {{ $fundi->primary_phone }}</div>
                    <div class="small">{{ $fundi->address }}</div>
                    <div class="small text-muted mt-1">Visits: {{ $fundi->site_visits_count ?? 0 }} · Putty Bags: {{ $fundi->bags_total ?? 0 }}</div>
                </div>
            </a>
        @empty
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-users me-2"></i>
                No fundis found matching your criteria.
            </div>
        @endforelse
        <div class="pagination-wrapper d-flex justify-content-center mt-3">
            {{ $fundis->links() }}
        </div>
    </div>
</div>
