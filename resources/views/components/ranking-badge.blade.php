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

<span class="text-{{ $rankingData['color'] }} ranking-badge">
    {{ $rankingData['label'] }}
</span>
