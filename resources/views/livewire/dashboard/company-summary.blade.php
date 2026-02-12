<div wire:poll.keep-alive>
    <div class="notifications">
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-people-carry-box"></i>
            </div>
            <div class="title">
                <div class="count">{{ number_format($this->fundi_count) }}</div>
                Mafundi
            </div>
        </div>
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-user-secret"></i>
            </div>
            <div class="title">
                <div class="count">{{ number_format($this->admin_count) }}</div>
                Administrators
            </div>
        </div>
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div class="title">
                <div class="count">{{ number_format($this->operator_count) }}</div>
                Operators
            </div>
        </div>
        @include('components.charts.chart-summary')
    </div>

</div>
