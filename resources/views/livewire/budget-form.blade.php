<div class="m-2">
    <form wire:submit.prevent="createOrUpdateBudget">
        <!-- Budget Information -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-wallet mr-2"></i>Budget Information
                </h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="title">Budget Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" wire:model="title"
                        placeholder="Enter budget title">
                    @error('title')
                        <span class="error text-danger"><small>{{ $message }}</small></span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" wire:model="description" rows="3"
                        placeholder="Enter budget description (optional)"></textarea>
                    @error('description')
                        <span class="error text-danger"><small>{{ $message }}</small></span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="total_amount">Total Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="total_amount" wire:model="total_amount"
                                placeholder="0.00" min="1000" step="0.01">
                            <div class="input-group-append">
                                <span class="input-group-text">TZS</span>
                            </div>
                        </div>
                        @error('total_amount')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>

                    @if ($editing)
                        <div class="form-group col-md-4">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select id="status" class="form-control" wire:model="status">
                                <option value="active">Active</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('status')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fas fa-times mr-2"></i>Cancel
            </button>
            <button type="submit" class="btn btn-custom" {{ $saving ? 'disabled' : '' }}>
                @if ($saving)
                    <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                @else
                    <i class="fas fa-save mr-2"></i>{{ $action_text }}
                @endif
            </button>
        </div>
    </form>
</div>
