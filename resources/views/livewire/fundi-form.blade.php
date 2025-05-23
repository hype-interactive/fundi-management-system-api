<div class="m-2">
    <form wire:submit.prevent="createOrUpdateFundi">
        <!-- Personal Information -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse" data-target="#personalInfo"
                aria-expanded="true" aria-controls="personalInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-user mr-2"></i>Personal Information
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="personalInfo" class="collapse show">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="fname">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fname" wire:model="fname">
                            @error('fname')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label for="mname">Middle Name</label>
                            <input type="text" class="form-control" id="mname" wire:model="mname">
                            @error('mname')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lname">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lname" wire:model="lname">
                            @error('lname')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="gender">Gender <span class="text-danger">*</span></label>
                            <select id="gender" class="form-control" wire:model="gender">
                                <option value="">-- Select Gender --</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            @error('gender')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="nida_number">NIDA Number</label>
                            <input type="text" class="form-control" id="nida_number" wire:model="nida_number">
                            @error('nida_number')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse" data-target="#contactInfo"
                aria-expanded="true" aria-controls="contactInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-phone mr-2"></i>Contact Information
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="contactInfo" class="collapse show">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="primary_phone">Primary Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+255</span>
                                </div>
                                <input type="text" class="form-control" id="primary_phone" wire:model="primary_phone"
                                    placeholder="712345678">
                            </div>
                            @error('primary_phone')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="transaction_phone">Transaction Phone</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+255</span>
                                </div>
                                <input type="text" class="form-control" id="transaction_phone"
                                    wire:model="transaction_phone" placeholder="612345678">
                            </div>
                            <small class="form-text text-muted">Phone used for mobile money transactions</small>
                            @error('transaction_phone')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location & Classification -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#locationInfo" aria-expanded="true" aria-controls="locationInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-map-marker-alt mr-2"></i>Location & Classification
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="locationInfo" class="collapse show">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="district">District <span class="text-danger">*</span></label>
                            @include('components.selects.select_district')
                            @error('district')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ward_id">Ward <span class="text-danger">*</span></label>
                            @include('components.selects.select_ward')
                            @error('ward_id')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ranking">Ranking <span class="text-danger">*</span></label>
                            <select id="ranking" class="form-control" wire:model="ranking">
                                <option value="">-- Select Ranking --</option>
                                <option value="fundi_kuu">Fundi Mkuu</option>
                                <option value="fundi_msaidizi">Fundi</option>
                            </select>
                            @error('ranking')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select id="status" class="form-control" wire:model="status">
                                <option value="">-- Select Status --</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            @error('status')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Photo Upload -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#photoInfo" aria-expanded="false" aria-controls="photoInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-camera mr-2"></i>Profile Photo (Optional)
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="photoInfo" class="collapse">
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_photo"
                                wire:model="profile_photo">
                            <label class="custom-file-label" for="profile_photo">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Upload a clear photo of the fundi. Maximum size:
                            2MB.</small>
                        @error('profile_photo')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror

                        @if ($profile_photo)
                            <div class="mt-3">
                                <div class="preview-box text-center">
                                    <img src="{{ $profile_photo->temporaryUrl() }}" alt="Preview"
                                        class="img-thumbnail" style="max-height: 150px; max-width: 150px;">
                                    <p class="mt-2 text-muted">New photo preview</p>
                                </div>
                            </div>
                        @elseif($editing && $existing_photo)
                            <div class="mt-3">
                                <div class="preview-box text-center">
                                    <img src="{{ $existing_photo }}" alt="Existing Photo" class="img-thumbnail"
                                        style="max-height: 150px; max-width: 150px;">
                                    <p class="mt-2 text-muted">Current photo</p>
                                    <button type="button" class="btn btn-sm btn-danger mt-2"
                                        wire:click="removePhoto">
                                        <i class="fa fa-trash"></i> Remove Photo
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#additionalInfo" aria-expanded="false" aria-controls="additionalInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>Additional Information
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="additionalInfo" class="collapse">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="legacy_match"
                                    wire:model="legacy_match">
                                <label class="custom-control-label" for="legacy_match">Legacy Match</label>
                            </div>
                            <small class="form-text text-muted">Check if this fundi exists in legacy system</small>
                        </div>
                        @if ($legacy_match)
                            <div class="form-group col-md-6">
                                <label for="legacy_id">Legacy ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="legacy_id" wire:model="legacy_id">
                                @error('legacy_id')
                                    <span class="error text-danger"><small>{{ $message }}</small></span>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        @if ($mode === 'create')
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" class="btn btn-custom">
                    <i class="fas fa-save mr-2"></i>{{ $action_text }}
                </button>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-light rounded">
                <div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Changes will be saved automatically when you click Update
                    </small>
                </div>
                <button type="submit" class="btn btn-custom">
                    <i class="fas fa-save mr-2"></i>{{ $action_text }}
                </button>
            </div>
        @endif
    </form>
</div>
