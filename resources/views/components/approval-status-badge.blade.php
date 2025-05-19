@if($approved_at)
    <span class="badge bg-success status-badge">
        <i class="fa-solid fa-check-circle me-1"></i>
        Approved
    </span>
@else
    <span class="badge bg-warning status-badge">
        <i class="fa-solid fa-clock me-1"></i>
        Pending
    </span>
@endif
