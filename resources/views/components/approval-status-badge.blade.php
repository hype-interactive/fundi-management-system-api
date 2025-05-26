@if ($rejected_at)
    <span class="badge bg-danger status-badge" title="Rejected on {{ \Carbon\Carbon::parse($rejected_at)->format('M d, Y H:i') }}">
        <i class="fa-solid fa-times-circle me-1"></i>
        Rejected
    </span>
@elseif ($approved_at)
    <span class="badge bg-success status-badge" title="Approved on {{ \Carbon\Carbon::parse($approved_at)->format('M d, Y H:i') }}">
        <i class="fa-solid fa-check-circle me-1"></i>
        Approved
    </span>
@else
    <span class="badge bg-warning status-badge" title="Awaiting review">
        <i class="fa-solid fa-clock me-1"></i>
        Pending
    </span>
@endif
