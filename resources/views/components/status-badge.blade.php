@php
    $statusColors = [
        'active' => 'success',
        'cancelled' => 'danger',
        'completed' => 'info',
        'pending' => 'warning',
        'processing' => 'primary',
        'failed' => 'danger',
        'partial' => 'warning'
    ];
    $color = $statusColors[$status] ?? 'secondary';
@endphp

<span class="badge badge-{{ $color }}">
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
