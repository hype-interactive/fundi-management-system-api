<div class="logs-wrapper">
    <div class="body">
        <div class="filter mt-3">
            <div class="row">
                <div class="col">
                    <div class="select">
                        <select wire:model.live="user_id">
                            <option value="">User</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name ?? $user->fname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="select">
                        <select wire:model.live="log_type">
                            <option value="">Log Type</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                            <option value="accessed">Accessed</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="select">
                        <select wire:model.live="model_type">
                            <option value="">Model Type</option>
                            <option value="User">User</option>
                            <option value="Product">Product</option>
                            <option value="Order">Order</option>
                            <option value="Transaction">Transaction</option>
                            <option value="Setting">Setting</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="select">
                        <input type="date" wire:model.live="from_date" placeholder="From Date" />
                    </div>
                </div>
                <div class="col">
                    <div class="select">
                        <input type="date" wire:model.live="to_date" placeholder="To Date" />
                    </div>
                </div>
            </div>
        </div>

        <div class="paper mt-3">
            <div class="custom-table">
                <table class="table table-sm">
                    <thead class="thead-light thead-sticky">
                        <tr>
                            <th scope="col">Time</th>
                            <th scope="col">Actor</th>
                            <th scope="col">Log Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="{{ getBorderStyle($log) ?? '' }}">
                                    <div class="centered">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y h:i A') }}
                                    </div>
                                </td>
                                <td>
                                    <div class="centered">
                                        {{ $log->user ? $log->user->name ?? $log->user->fname : 'System' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="centered text-semi-bold {{ getLogTypeColor($log) ?? '' }} capitalize">
                                        {{ $log->log_type }}
                                    </div>
                                </td>
                                <td>
                                    <div class="centered text-monospace {{ getLogTypeColor($log) ?? '' }}">
                                        {{ $log->description }}
                                        @php
                                            $props = is_array($log->properties)
                                                ? $log->properties
                                                : json_decode($log->properties, true) ?? [];
                                        @endphp
                                        @if (count($props) > 0)
                                            <button class="btn btn-sm btn-link p-0 ml-2" data-toggle="collapse"
                                                data-target="#properties-{{ $log->id }}" aria-expanded="false">
                                                <i class="fa fa-info-circle"></i>
                                            </button>
                                            <div class="collapse mt-2" id="properties-{{ $log->id }}">
                                                <small class="text-muted">
                                                    <pre>{{ json_encode($props, JSON_PRETTY_PRINT) }}</pre>
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="centered">
                                        <small class="text-muted">{{ $log->ip_address }}</small>
                                        @if ($log->user_agent)
                                            <br>
                                            <small class="text-muted" title="{{ $log->user_agent }}">
                                                {{ Str::limit($log->user_agent, 30) }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrapper d-flex justify-content-center mt-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
