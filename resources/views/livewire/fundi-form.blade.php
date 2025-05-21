<div class="m-2">
    <form wire:submit.prevent="createOrUpdateFundi">
        <!-- Personal Information -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Personal Information</h6>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="fname">First Name</label>
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
                        <label for="lname">Last Name</label>
                        <input type="text" class="form-control" id="lname" wire:model="lname">
                        @error('lname')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="gender">Gender</label>
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

        <!-- Contact Information -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Contact Information</h6>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="primary_phone">Primary Phone</label>
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

        <!-- Location & Classification -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Location & Classification</h6>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="district">District</label>
                        @include('components.selects.select_district')
                        @error('district')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ward_id">Ward</label>
                        @include('components.selects.select_ward')
                        @error('ward_id')
                            <span class="error text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="ranking">Ranking</label>
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
                        <label for="status">Status</label>
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

        <!-- Profile Photo Upload -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Profile Photo (Optional)</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="profile_photo"
                            wire:model="profile_photo">
                        <label class="custom-file-label" for="profile_photo">Choose file...</label>
                    </div>
                    <small class="form-text text-muted">Upload a clear photo of the fundi. Maximum size: 2MB.</small>
                    @error('profile_photo')
                        <span class="error text-danger"><small>{{ $message }}</small></span>
                    @enderror

                    @if ($profile_photo)
                        <div class="mt-2">
                            <div class="preview-box">
                                <img src="{{ $profile_photo->temporaryUrl() }}" alt="Preview" class="img-thumbnail"
                                    style="max-height: 150px;">
                            </div>
                        </div>
                    @elseif($editing && $existing_photo)
                        <div class="mt-2">
                            <div class="preview-box">
                                <img src="{{ $existing_photo }}" alt="Existing Photo" class="img-thumbnail"
                                    style="max-height: 150px;">
                                <button type="button" class="btn btn-sm btn-danger mt-1" wire:click="removePhoto">
                                    <i class="fa fa-trash"></i> Remove Photo
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Additional Information</h6>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="legacy_match"
                                wire:model="legacy_match">
                            <label class="custom-control-label" for="legacy_match">Legacy Match</label>
                        </div>
                    </div>
                    @if ($legacy_match)
                        <div class="form-group col-md-6">
                            <label for="legacy_id">Legacy ID</label>
                            <input type="text" class="form-control" id="legacy_id" wire:model="legacy_id">
                            @error('legacy_id')
                                <span class="error text-danger"><small>{{ $message }}</small></span>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">{{ $action_text }}</button>
        </div>
    </form>
</div>
