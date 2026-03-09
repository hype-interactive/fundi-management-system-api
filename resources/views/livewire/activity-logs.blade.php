<div class="logs-wrapper">
    <div class="body">
        <div class="filter mt-2">
            <div class="row g-1">
                <div class="col"><select wire:model.live="user_id" class="form-select form-select-sm">
                    <option value="">User</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name ?? $user->fname }}</option>
                    @endforeach
                </select></div>
                <div class="col"><select wire:model.live="log_type" class="form-select form-select-sm">
                    <option value="">Log Type</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="accessed">Accessed</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="other">Other</option>
                </select></div>
                <div class="col"><select wire:model.live="model_type" class="form-select form-select-sm">
                    <option value="">Model Type</option>
                    <option value="User">User</option>
                    <option value="Product">Product</option>
                    <option value="Order">Order</option>
                    <option value="Transaction">Transaction</option>
                    <option value="Setting">Setting</option>
                </select></div>
                <div class="col"><input type="date" wire:model.live="from_date" class="form-control form-control-sm" /></div>
                <div class="col"><input type="date" wire:model.live="to_date" class="form-control form-control-sm" /></div>
            </div>
        </div>

        <div class="paper mt-2">
            <table class="table table-sm table-borderless mb-0">
                <thead class="thead-sticky">
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/y H:i') }}</td>
                            <td>{{ $log->user ? ($log->user->name ?? $log->user->fname) : 'System' }}</td>
                            <td class="capitalize">{{ $log->log_type }}</td>
                            <td class="text-monospace">{{ $log->description }}</td>
                            <td><small>{{ $log->ip_address }}</small></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-2">
            {{ $logs->links() }}
        </div>
    </div>
</div>