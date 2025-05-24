<div>
    @if($show)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-hidden="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title @if($type === 'delete') text-danger @elseif($type === 'approve') text-success @else text-warning @endif">
                            {{ $title }}
                        </h5>
                        <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{ $message }}</p>

                        @if($itemName)
                            <p>Item: <strong>{{ $itemName }}</strong></p>
                        @endif

                        @if(!empty($itemDetails))
                            @foreach($itemDetails as $label => $value)
                                <p>{{ $label }}: <strong>{{ $value }}</strong></p>
                            @endforeach
                        @endif

                        @if($type === 'reject')
                            <hr>
                            <div class="form-group">
                                <label for="rejectionReason">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea
                                    wire:model="rejectionReason"
                                    class="form-control @error('rejectionReason') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Please provide a reason for rejection...">
                                </textarea>
                                @error('rejectionReason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if($type === 'delete')
                            <hr>
                            <p class="text-danger"><strong>Warning:</strong></p>
                            <p class="text-danger">This action cannot be undone. Please make sure you want to proceed.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="button" class="btn @if($type === 'delete') btn-danger @elseif($type === 'approve') btn-success @else btn-warning @endif" wire:click="confirm">
                            @if($type === 'delete')
                                Delete
                            @elseif($type === 'approve')
                                Approve
                            @elseif($type === 'reject')
                                Reject
                            @else
                                Confirm
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
