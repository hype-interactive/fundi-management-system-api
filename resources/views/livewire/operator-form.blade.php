<div class="m-2">
    <form wire:submit.prevent="createOrUpdateOperator">
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
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse" data-target="#contactInfo"
                aria-expanded="true" aria-controls="contactInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-envelope mr-2"></i>Contact Information
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="contactInfo" class="collapse show">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" wire:model="email"
                                placeholder="operator@example.com">
                            @error('email')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+255</span>
                                </div>
                                <input type="text" class="form-control" id="phone" wire:model="phone"
                                    placeholder="712345678">
                            </div>
                            @error('phone')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#accountInfo" aria-expanded="true" aria-controls="accountInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-user-cog mr-2"></i>Account Information
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="accountInfo" class="collapse show">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="role_id">Role <span class="text-danger">*</span></label>
                            <select id="role_id" class="form-control" wire:model="role_id">
                                <option value="">-- Select Role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select id="status" class="form-control" wire:model="status">
                                <option value="">-- Select Status --</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                            @error('status')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Information -->
        @if($show_password || !$editing)
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#passwordInfo" aria-expanded="{{ $mode === 'create' ? 'true' : 'false' }}"
                aria-controls="passwordInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-lock mr-2"></i>
                        {{ $editing ? 'Change Password (Optional)' : 'Password Information' }}
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="passwordInfo" class="collapse {{ $mode === 'create' ? 'show' : '' }}">
                <div class="card-body">
                    @if($editing)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Leave password fields empty to keep the current password unchanged.
                        </div>
                    @endif
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password">
                                Password
                                @if(!$editing)<span class="text-danger">*</span>@endif
                            </label>
                            <input type="password" class="form-control" id="password" wire:model="password"
                                placeholder="{{ $editing ? 'Enter new password' : 'Enter password' }}">
                            @error('password')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="password_confirmation">
                                Confirm Password
                                @if(!$editing)<span class="text-danger">*</span>@endif
                            </label>
                            <input type="password" class="form-control" id="password_confirmation"
                                wire:model="password_confirmation" placeholder="Confirm password">
                            @error('password_confirmation')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                    @if(!$editing)
                        <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- District Assignment -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#districtInfo" aria-expanded="false" aria-controls="districtInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-map-marker-alt mr-2"></i>District Assignment
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="districtInfo" class="collapse">
                <div class="card-body">
                    <div class="form-group">
                        <label>Assigned Districts <span class="text-danger">*</span></label>
                        <small class="form-text text-muted mb-3">Select one or more districts this operator will manage</small>

                        <div class="row">
                            @foreach($districts as $district)
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                            id="district_{{ $district->id }}"
                                            wire:model="selected_districts"
                                            value="{{ $district->id }}">
                                        <label class="custom-control-label" for="district_{{ $district->id }}">
                                            {{ $district->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('selected_districts')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror

                        @if(count($selected_districts) > 0)
                            <div class="mt-3">
                                <small class="text-success">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    {{ count($selected_districts) }} district(s) selected
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Photo Upload -->
        {{-- <div class="card mb-3">
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
                        <small class="form-text text-muted">Upload a clear photo of the operator. Maximum size: 2MB.</small>
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
        </div> --}}
        {{-- Using the reusable image upload component --}}
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
                    <x-image-upload
                        wire:model="profile_photo"
                        label="Profile Photo"
                        :existing="$existing_photo_url"
                        :existing-public-id="$existing_photo_public_id"
                        :required="false"
                        remove-method="removePhoto"
                        width="150"
                        height="150"
                        max-size="5MB"
                    />
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
                        Changes will be saved when you click Update
                    </small>
                </div>
                <button type="submit" class="btn btn-custom">
                    <i class="fas fa-save mr-2"></i>{{ $action_text }}
                </button>
            </div>
        @endif
    </form>
</div>
