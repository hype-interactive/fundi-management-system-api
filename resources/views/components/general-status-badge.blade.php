@php
    $badgeClass = match($status) {
        'active' => 'bg-success',
        'inactive' => 'bg-secondary',
        'suspended' => 'bg-danger',
        default => 'bg-secondary'
    };

    $icon = match($status) {
        'active' => 'fa-check-circle',
        'inactive' => 'fa-pause-circle',
        'suspended' => 'fa-ban',
        default => 'fa-question-circle'
    };
@endphp

<span class="status-badge {{ $badgeClass }}">
    <i class="fa-solid {{ $icon }} me-1"></i>
    {{ ucfirst($status) }}
</span>
