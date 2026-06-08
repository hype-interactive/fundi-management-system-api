@if ($rejected_at)
    <span class="text-danger" title="Rejected on {{ \Carbon\Carbon::parse($rejected_at)->format('M d, Y H:i') }}">
        Rejected
    </span>
@elseif ($approved_at)
    <span class="text-success" title="Approved on {{ \Carbon\Carbon::parse($approved_at)->format('M d, Y H:i') }}">
        Approved
    </span>
@else
    <span class="text-warning" title="Awaiting review">
        Pending
    </span>
@endif
