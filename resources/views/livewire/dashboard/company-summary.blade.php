<div wire:poll.keep-alive>
    <div class="notifications">
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-user-secret"></i>
            </div>
            <div class="title">
                <div class="count">{{ $this->admin_count }}</div>
                Administrators
            </div>
        </div>
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div class="title">
                <div class="count">{{ $this->operator_count }}</div>
                Operators
            </div>
        </div>
        <div class="tile">
            <div class="icon">
                <i class="fa-solid fa-people-carry-box"></i>
            </div>
            <div class="title">
                <div class="count">{{ $this->fundi_count }}</div>
                Mafundi
            </div>
        </div>
    </div>
</div>
