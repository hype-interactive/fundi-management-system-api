<div wire:poll.keep-alive>
    <div class="notifications mt-6">
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
                <i class="fas fa-location-arrow"></i>
            </div>
            <div class="title">
                <span class="count">{{ number_format($this->sites_count) }}</span>
                <span>Total Visits</span>
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
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-user-secret"></i>
            </div>
            <div class="title">
                <div class="count">{{ number_format($this->admin_count) }}</div>
                Administrators
            </div>
        </div>
    </div>
</div>
