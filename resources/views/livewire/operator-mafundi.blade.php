<div>
    <div class="d-none d-md-block">
            <table class="table">
                <thead class="thead-light">
                    <tr>
                        {{-- <th scope="col">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input"
                                @if (count($fundis) === 0) disabled @endif>
                        </th> --}}
                        <th scope="col">
                            <a wire:click="refresh">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </a>
                        </th>
                        <th scope="col">Name</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Address</th>
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
            <!-- Pagination -->
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $fundis->links() }}
    </div>
        </div>
</div>
