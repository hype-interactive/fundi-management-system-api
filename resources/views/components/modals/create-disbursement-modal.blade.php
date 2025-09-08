<div>
    <div wire:ignore.self class="modal fade" id="createDisbursementModal" tabindex="-1" aria-labelledby="createDisbursementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="createDisbursementModalLabel">Create Disbursement Batch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @livewire('disbursement-form')
                </div>
            </div>
        </div>
    </div>
</div>
