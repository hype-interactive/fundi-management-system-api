<div wire:poll.keep-alive>
    {{-- Bulk Actions Bar --}}
    @if (count($selectedFundis) > 0)
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3" role="alert">
            <div>
                <i class="fa-solid fa-check-double me-2"></i>
                <strong>{{ count($selectedFundis) }}</strong> fundi(s) selected
            </div>
            <div>
                <button wire:click="bulkApprove" class="btn btn-success btn-sm me-2">
                    <i class="fa-solid fa-check me-1"></i>
                    Approve Selected
                </button>
            </div>
        </div>
    @endif

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <div class="custom-table">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th scope="col" style="width: 50px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input"
                            @if (count($fundis) === 0) disabled @endif>
                    </th>
                    <th scope="col">
                        <a wire:click="$refresh">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </a>
                    </th>
                    <th scope="col">Name</th>
                    <th scope="col">Primary Phone</th>
                    <th scope="col">Trans. Phone</th>
                    <th scope="col">Ward</th>
                    <th scope="col">District</th>
                    <th scope="col">Ranking</th>
                    <th scope="col">Registrar</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fundis as $fundi)
                    <tr>
                        <td>
                            <input type="checkbox" wire:model.live="selectedFundis" value="{{ $fundi->id }}"
                                class="form-check-input">
                        </td>
                        <th scope="row">
                            <a href="{{ route('fundi', [$fundi]) }}" class="avatar-circle">
                                <i class="fa-solid fa-user"></i>
                            </a>
                        </th>
                        <td>
                            <a href="{{ route('fundi', $fundi) }}" class="text-decoration-none">
                                <div class="fundi-name">
                                    <span class="fw-semibold">{{ $fundi->fname }} {{ $fundi->lname }}</span>
                                    @if ($fundi->mname)
                                        <small class="text-muted d-block">{{ $fundi->mname }}</small>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->primary_phone }}</div>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->transaction_phone ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->ward->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->ward->district->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="centered">
                                @include('components.ranking-badge', ['ranking' => $fundi->ranking])
                            </div>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->creator->full_name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="centered">
                                <div class="d-flex">
                                    <i class="fa-solid fa-check text-success cursor-pointer"
                                        wire:click="approve({{ $fundi->id }})"></i>
                                    <i class="fa-solid fa-xmark text-danger ml-2 cursor-pointer"
                                        wire:click="reject({{ $fundi->id }})"></i>
                                    <i class="fa-solid fa-trash text-danger ml-2 cursor-pointer"
                                        wire:click="delete({{ $fundi->id }})"></i>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fa-solid fa-users me-2"></i>
                            No unapproved fundis found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper d-flex justify-content-center mt-3">
        {{ $fundis->links() }}
    </div>
</div>
