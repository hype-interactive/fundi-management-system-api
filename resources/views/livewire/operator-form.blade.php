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
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse" data-target="#accountInfo"
                aria-expanded="true" aria-controls="accountInfo">
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
                                @foreach ($roles as $role)
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
        @if ($show_password || !$editing)
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
                        @if ($editing)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Leave password fields empty to keep the current password unchanged.
                            </div>
                        @endif
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">
                                    Password
                                    @if (!$editing)
                                        <span class="text-danger">*</span>
                                    @endif
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
                                    @if (!$editing)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    wire:model="password_confirmation" placeholder="Confirm password">
                                @error('password_confirmation')
                                    <span class="error text-danger"><small>{{ $message }}</small></span>
                                @enderror
                            </div>
                        </div>
                        @if (!$editing)
                            <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Warning for operators with multiple districts (backward compatibility) -->
        @if ($editing && $this->has_multiple_districts)
            <div class="alert alert-warning mb-3">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                    <div>
                        <strong>Multiple Districts Detected</strong>
                        <p class="mb-2">This operator currently has multiple districts assigned:</p>
                        <ul class="mb-2">
                            @foreach ($this->current_districts as $district)
                                <li>{{ $district->name }} ({{ $district->region->name ?? 'Unknown Region' }})</li>
                            @endforeach
                        </ul>
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            After updating, this operator will only be assigned to the single district selected below.
                            The other districts will be removed from their assignment.
                        </small>
                    </div>
                </div>
            </div>
        @endif

        <!-- Location Assignment - MODIFIED FOR SINGLE DISTRICT -->
        <div class="card mb-3">
            <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                data-target="#locationInfo" aria-expanded="false" aria-controls="locationInfo">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-map-marker-alt mr-2"></i>Location Assignment
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div id="locationInfo" class="collapse">
                <div class="card-body">
                    <!-- Region Selection -->
                    <div class="form-group">
                        <label for="selected_region">Region <span class="text-danger">*</span></label>
                        <select wire:model.live="selected_region" id="selected_region" class="form-control">
                            <option value="">-- Select Region --</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                        @error('selected_region')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>

                    <!-- District Selection - CHANGED TO SINGLE SELECT WITH BACKWARD COMPATIBILITY INFO -->
                    @if ($selected_region && $districts->count() > 0)
                        <div class="form-group mt-4">
                            <label for="selected_district">Assigned District <span
                                    class="text-danger">*</span></label>
                            <select wire:model="selected_district" id="selected_district" class="form-control">
                                <option value="">-- Select District --</option>
                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Select the district this operator will manage in
                                {{ $regions->where('id', $selected_region)->first()->name ?? 'the selected region' }}
                                @if ($editing && $this->has_multiple_districts)
                                    <br><strong>Note:</strong> This will replace all currently assigned districts.
                                @endif
                            </small>
                            @error('selected_district')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror

                            @if ($selected_district)
                                <div class="mt-3">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        District selected:
                                        {{ $districts->where('id', $selected_district)->first()->name ?? '' }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    @elseif($selected_region && $districts->count() === 0)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No districts available in the selected region for your access level.
                        </div>
                    @endif

                    @if (!$selected_region)
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            Please select a region first to see available districts.
                        </div>
                    @endif
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
                    <x-image-upload wire:model="profile_photo" label="Profile Photo" :existing="$existing_photo_url"
                        :existing-public-id="$existing_photo_public_id" :required="false" remove-method="removePhoto" width="150" height="150"
                        max-size="5MB" />
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
