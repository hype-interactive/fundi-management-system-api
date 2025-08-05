@php
    $statusConfig = [
        'pending' => ['color' => 'warning', 'icon' => 'clock'],
        'processing' => ['color' => 'primary', 'icon' => 'cog fa-spin'],
        'sent' => ['color' => 'info', 'icon' => 'paper-plane'],
        'completed' => ['color' => 'success', 'icon' => 'check-circle'],
        'failed' => ['color' => 'danger', 'icon' => 'times-circle']
    ];
    $config = $statusConfig[$status] ?? ['color' => 'secondary', 'icon' => 'question'];
@endphp

<span class="badge badge-{{ $config['color'] }}">
    <i class="fa-solid fa-{{ $config['icon'] }} mr-1"></i>
    {{ ucfirst($status) }}
</span>
