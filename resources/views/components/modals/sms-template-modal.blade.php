<div wire:ignore.self class="modal fade" id="smsTemplateModal" tabindex="-1" aria-labelledby="smsTemplateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    @if ($editingTemplate)
                        Edit Template
                    @else
                        Create New Template
                    @endif
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="saveTemplate">
                    <div class="mb-3">
                        <label for="templateTitle" class="form-label">Template Title</label>
                        <input type="text" class="form-control @error('templateTitle') is-invalid @enderror"
                            id="templateTitle" wire:model="templateTitle" placeholder="Enter template title">
                        @error('templateTitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="templateContent" class="form-label">Template Content</label>
                        <textarea class="form-control @error('templateContent') is-invalid @enderror" id="templateContent"
                            wire:model="templateContent" rows="6" placeholder="Enter your SMS template content..." maxlength="1000"></textarea>
                        <div class="form-text">
                            <span class="text-muted">{{ strlen($templateContent) }}/1000 characters</span>
                        </div>
                        @error('templateContent')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary">
                            @if ($editingTemplate)
                                Update Template
                            @else
                                Save Template
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
