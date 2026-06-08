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
                                {{-- <option value="pending">Pending</option> --}}
                            </select>
                            @error('status')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Information Section -->
        @if (!$editing)
            {{-- NEW OPERATOR: Always show password section --}}
            <div class="card mb-3">
                <div class="card-header bg-light" style="cursor: pointer;" data-toggle="collapse"
                    data-target="#passwordInfo" aria-expanded="true" aria-controls="passwordInfo">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-lock mr-2"></i>Password Setup <span class="text-danger">*</span>
                        </h6>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div id="passwordInfo" class="collapse show">
                    <div class="card-body">
                        <!-- Password Type Selection -->
                        <div class="form-group">
                            <label class="font-weight-bold">Password Setup Method</label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="password_type"
                                        id="manual_password" wire:click="$set('use_default_password', false)"
                                        {{ !$use_default_password ? 'checked' : '' }}>
                                    <label class="form-check-label" for="manual_password">
                                        <i class="fas fa-keyboard mr-1"></i>Set Custom Password
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="password_type"
                                        id="default_password" wire:click="$set('use_default_password', true)"
                                        {{ $use_default_password ? 'checked' : '' }}>
                                    <label class="form-check-label" for="default_password">
                                        <i class="fas fa-magic mr-1"></i>Generate Default Password
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if (!$use_default_password)
                            {{-- Manual Password Entry --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Custom Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password"
                                            wire:model="password" placeholder="Enter custom password">
                                        @error('password')
                                            <span class="error text-danger"><small>{{ $message }}</small></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password_confirmation">Confirm Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password_confirmation"
                                            wire:model="password_confirmation" placeholder="Confirm password">
                                        @error('password_confirmation')
                                            <span class="error text-danger"><small>{{ $message }}</small></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Password must be at least 8 characters long and contain a mix of letters, numbers, and
                                symbols.
                            </small>
                        @else
                            {{-- Default Password Info --}}
                            <div class="alert alert-info">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-magic mr-2 mt-1"></i>
                                    <div>
                                        <strong>Default Password Generation</strong>
                                        <p class="mb-2">A secure default password will be automatically generated for
                                            this operator.</p>
                                        <ul class="mb-2 small">
                                            <li>Password format: <code>Operator@[Year][RandomNumber]</code></li>
                                            <li>Example: <code>Operator@2025123</code></li>
                                            <li>Login credentials will be sent via SMS and email</li>
                                            <li>Operator can change password after first login</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Notification Info --}}
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="mb-2">
                                <i class="fas fa-paper-plane mr-2"></i>Automatic Notifications
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-sms text-primary mr-2"></i>
                                        <small><strong>SMS Message</strong> to operator's phone number</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-envelope text-success mr-2"></i>
                                        <small><strong>Email Message</strong> to operator's email address</small>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">
                                Both messages will contain web app login (email + password) and mobile app login (phone
                                + password) credentials.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- EXISTING OPERATOR: Optional password change section --}}
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-lock mr-2"></i>Password Management
                        </h6>
                        <div class="btn-group btn-group-sm" role="group">
                            @if (!$show_password)
                                <button type="button" class="btn btn-outline-primary"
                                    wire:click="togglePasswordChange">
                                    <i class="fas fa-edit mr-1"></i>Change Password
                                </button>
                            @endif
                            {{-- <button type="button" class="btn btn-outline-warning" wire:click="forcePasswordReset">
                                <i class="fas fa-refresh mr-1"></i>Reset Password
                            </button>
                            <button type="button" class="btn btn-outline-info" wire:click="resendCredentials">
                                <i class="fas fa-paper-plane mr-1"></i>Resend Credentials
                            </button> --}}
                        </div>
                    </div>
                </div>

                @if ($show_password)
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Changing the password will automatically send new login credentials to the operator via SMS
                            and email.
                        </div>

                        <!-- Password Type Selection for existing operator -->
                        <div class="form-group">
                            <label class="font-weight-bold">Password Change Method</label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="existing_password_type"
                                        wire:click="$set('use_default_password', false)"
                                        {{ !$use_default_password ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        <i class="fas fa-keyboard mr-1"></i>Set Custom Password
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="existing_password_type"
                                        wire:click="$set('use_default_password', true)"
                                        {{ $use_default_password ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        <i class="fas fa-magic mr-1"></i>Generate New Default Password
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if (!$use_default_password)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password"
                                            wire:model="password" placeholder="Enter new password">
                                        @error('password')
                                            <span class="error text-danger"><small>{{ $message }}</small></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password_confirmation">Confirm New Password <span
                                                class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password_confirmation"
                                            wire:model="password_confirmation" placeholder="Confirm new password">
                                        @error('password_confirmation')
                                            <span class="error text-danger"><small>{{ $message }}</small></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-success">
                                <i class="fas fa-magic mr-2"></i>
                                A new secure default password will be generated and sent to the operator.
                            </div>
                        @endif

                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary mr-2" wire:click="togglePasswordChange">
                                <i class="fas fa-times mr-1"></i>Cancel
                            </button>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-muted">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Password management options are available above. Current password remains unchanged unless
                            modified.
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Location Assignment -->
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

                    <!-- District Selection -->
                    @if ($selected_region && $districts->count() > 0)
                        <div class="form-group mt-4">
                            <label for="selected_districts">Assigned Districts <span
                                    class="text-danger">*</span></label>
                            <div class="districts-checkboxes"
                                style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;">
                                @foreach ($districts as $district)
                                    <div class="form-check">
                                        <input type="checkbox" wire:model="selected_districts"
                                            value="{{ $district->id }}" class="form-check-input"
                                            id="district_{{ $district->id }}">
                                        <label class="form-check-label" for="district_{{ $district->id }}">
                                            {{ $district->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">
                                Select one or more districts this operator will manage in
                                {{ $regions->where('id', $selected_region)->first()->name ?? 'the selected region' }}.
                            </small>
                            @error('selected_districts')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                            @error('selected_districts.*')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror

                            {{-- Show selected districts count --}}
                            @if (count($selected_districts) > 0)
                                <div class="mt-3">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        {{ count($selected_districts) }} district(s) selected:
                                        @foreach ($districts->whereIn('id', $selected_districts) as $district)
                                            <span class="badge badge-primary mr-1">{{ $district->name }}</span>
                                        @endforeach
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
                    <i class="fas fa-save mr-2"></i>{{ $action_text }} & Send Credentials
                </button>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-light rounded">
                <div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Changes will be saved when you click Update
                        @if ($show_password)
                            <br><strong>Note:</strong> Password change will trigger credential notifications
                        @endif
                    </small>
                </div>
                <button type="submit" class="btn btn-custom">
                    <i class="fas fa-save mr-2"></i>{{ $action_text }}
                    @if ($show_password && $password_changed)
                        & Send New Credentials
                    @endif
                </button>
            </div>
        @endif
    </form>
</div>
