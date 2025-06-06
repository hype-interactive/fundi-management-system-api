<!-- Chart Summary Component -->
<div class="notifications">
    <div class="tile">
        <div class="icon">
            <i class="fas fa-eye"></i>
        </div>
        <div class="title">
            <span class="count">{{ array_sum($siteVisitData['data'] ?? []) }}</span>
            <span>Total Visits</span>
        </div>
    </div>

    <div class="tile">
        <div class="icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="title">
            <span class="count">
                @php
                    $fundiData = $this->fundiData['data'] ?? [];
                    $growth = 0;
                    if (count($fundiData) >= 2) {
                        $lastMonth = end($fundiData);
                        $prevMonth = prev($fundiData);
                        if ($prevMonth > 0) {
                            $growth = round((($lastMonth - $prevMonth) / $prevMonth) * 100, 1);
                        }
                    }
                @endphp
                {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
            </span>
            <span>Growth Rate</span>
        </div>
    </div>

    <div class="tile">
        <div class="icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="title">
            <span class="count">
                {{ count($fundiData['data'] ?? []) > 0 ? round(array_sum($fundiData['data']) / count($fundiData['data']), 1) : 0 }}
            </span>
            <span>Avg/Month</span>
        </div>
    </div>
</div>

<div class="divider"></div>
