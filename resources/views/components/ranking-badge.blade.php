@php
    $rankings = [
        'fundi_kuu' => [
            'label' => 'Fundi Mkuu',
            'color' => 'primary',
            'icon' => 'fa-crown'
        ],
        'fundi_msaidizi' => [
            'label' => 'Fundi',
            'color' => 'secondary',
            'icon' => 'fa-tools'
        ]
    ];

    $rankingData = $rankings[$ranking] ?? [
        'label' => 'Unknown',
        'color' => 'light',
        'icon' => 'fa-question'
    ];
@endphp

<span class="badge bg-{{ $rankingData['color'] }} ranking-badge">
    <i class="fa-solid {{ $rankingData['icon'] }} me-1"></i>
    {{ $rankingData['label'] }}
</span>
