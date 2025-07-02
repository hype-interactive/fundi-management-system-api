<!-- Create/Edit Product Modal -->
<div class="modal fade" id="createEditProduct" tabindex="-1" role="dialog" aria-labelledby="createEditProductLabel"
    aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditProductLabel">{{ $action_text }} Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form wire:submit.prevent="createOrUpdateProduct">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" id="name" placeholder="Enter product name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="photo">Product Photo</label>
                                <input type="file" class="form-control-file @error('photo') is-invalid @enderror"
                                    wire:model="photo" id="photo" accept="image/*">
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                @if ($photo)
                                    <div class="mt-2">
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="img-thumbnail"
                                            style="max-height: 100px;">
                                        <small class="text-muted d-block">Preview of new image</small>
                                    </div>
                                @elseif ($current_photo_url)
                                    <div class="mt-2">
                                        <img src="{{ $this->getThumbnail($current_photo_public_id, 150, 150) ?: $current_photo_url }}"
                                            alt="Current image" class="img-thumbnail" style="max-height: 100px;">
                                        <small class="text-muted d-block">Current image</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" wire:model="description" id="description"
                            rows="3" placeholder="Enter product description (optional)"></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="visibility">Visibility <span class="text-danger">*</span></label>
                                <select class="form-control @error('visibility') is-invalid @enderror"
                                    wire:model="visibility" id="visibility">
                                    <option value="public">Public</option>
                                    <option value="private">Private</option>
                                </select>
                                @error('visibility')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Hidden products won't be shown to users
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="availability">Availability <span class="text-danger">*</span></label>
                                <select class="form-control @error('availability') is-invalid @enderror"
                                    wire:model="availability" id="availability">
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                                @error('availability')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Unavailable products can't be selected
                                </small>
                            </div>
                        </div>
                    </div>

                    @if ($is_editing)
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle"></i>
                            You are editing an existing product. Leave the photo field empty to keep the current image.
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> {{ $action_text }} Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
