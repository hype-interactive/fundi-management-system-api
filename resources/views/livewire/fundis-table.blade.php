<div>
    <div class="filter mb-1">
        <div class="row">
            <div class="col-md-3">
                <div class="search">
                    <input wire:model.live.debounce.300ms="search_key" type="text" id="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    @if (isRoot())
                        <div class="col">
                            <div class="select">
                                @include('components.select_district')
                            </div>
                        </div>
                    @endif
                    <div class="col">
                        <div class="select">
                            @include('components.select_ward')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="custom-table">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th scope="col">
                        <a wire:click="refresh">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </a>
                    </th>
                    <th scope="col">Name</th>
                    <th scope="col">Primary Phone</th>
                    <th scope="col">Transaction Phone</th>
                    <th scope="col">Registered By</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fundis as $fundi)
                    <tr>
                        <th scope="row"> <a href="{{ route('fundi', [$fundi]) }}" class="avatar-circle"><i
                                    class="fa-solid fa-user"></i></a>
                        </th>
                        <td>
                            <a href="{{ route('fundi', $fundi) }}">
                                <div class="centered">{{ $fundi->fname }}
                                    {{ $fundi->lname }}</div>
                            </a>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->primary_phone }}</div>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->transaction_phone }}</div>
                        </td>
                        <td>
                            <div class="centered">{{ $fundi->creator->full_name }}</div>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pages">
        {{ $fundis->links() }}
    </div>
</div>
