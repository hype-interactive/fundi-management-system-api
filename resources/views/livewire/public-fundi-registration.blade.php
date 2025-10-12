<div class="container py-5">
    @if (!$is_authenticated)
        {{-- PIN Authentication Screen --}}
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-custom text-white text-center">
                        <h4 class="mb-0">
                            <i></i>Fundi Registration
                        </h4>
                    </div>
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-lock fa-3x text-custom mb-3"></i>
                        </div>

                        <form wire:submit.prevent="verifyPin">
                            <div class="form-group">
                                <label for="pin">Registration PIN <span class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-lg text-center" id="pin"
                                    wire:model="pin" placeholder="Enter PIN" maxlength="10" autofocus>
                                @error('pin')
                                    <span class="error text-danger"><small>{{ $message }}</small></span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-custom btn-block btn-lg mt-4">
                                <i ></i>Continue
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Contact your administrator if you don't have a PIN
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($registration_complete)
        {{-- Registration Success Screen --}}
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                        <h3 class="text-success mb-3">Registration Successful!</h3>
                        <p class="lead">Fundi has been registered successfully.</p>

                        @if ($registered_fundi_id)
                            <div class="alert alert-info mt-4">
                                <strong>Reference ID:</strong>
                                {{ \App\Models\Fundi::find($registered_fundi_id)->unique_id ?? 'N/A' }}
                            </div>
                            
                        @endif

                        <div class="mt-5">
                            <button wire:click="registerAnother" class="btn btn-custom btn-lg mr-2">
                                <i class="mr-2 mb-3"></i>Register Another
                            </button>
                            <button wire:click="logout" class="btn btn-outline-secondary btn-lg">
                                <i class="mr-2"></i>Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Registration Form --}}
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-custom text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-user-plus mr-2"></i>Register Here
                            </h4>
                            <button wire:click="logout" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-sign-out-alt mr-1"></i>Logout
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form wire:submit.prevent="registerFundi">
                            {{-- Personal Information --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user mr-2"></i>Personal Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="fname">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fname"
                                                wire:model="fname">
                                            @error('fname')
                                                <span class="error text-danger"><small>{{ $message }}</small></span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="mname">Middle Name</label>
                                            <input type="text" class="form-control" id="mname"
                                                wire:model="mname">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="lname">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="lname"
                                                wire:model="lname">
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
                                            <input type="text" class="form-control" id="nida_number"
                                                wire:model="nida_number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Contact Information --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-phone mr-2"></i>Contact Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="primary_phone">Primary Phone <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">+255</span>
                                                </div>
                                                <input type="text"
                                                    class="form-control @error('primary_phone') is-invalid @enderror"
                                                    id="primary_phone" wire:model.blur="primary_phone"
                                                    placeholder="712345678" maxlength="9">
                                            </div>
                                            @error('primary_phone')
                                                <span class="text-danger">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Enter 9 digits without the country code
                                            </small>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="transaction_phone">Transaction Phone</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">+255</span>
                                                </div>
                                                <input type="text"
                                                    class="form-control @error('transaction_phone') is-invalid @enderror"
                                                    id="transaction_phone" wire:model.blur="transaction_phone"
                                                    placeholder="612345678" maxlength="9">
                                            </div>
                                            @error('transaction_phone')
                                                <span class="text-danger">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Phone used for mobile money transactions
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Location & Classification --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-map-marker-alt mr-2"></i>Location & Classification
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="district">District <span class="text-danger">*</span></label>
                                            <select id="district"
                                                class="form-control @error('district') is-invalid @enderror"
                                                wire:model.live="district">
                                                <option value="">-- Select District --</option>
                                                @foreach ($districts as $dist)
                                                    <option value="{{ $dist->id }}">{{ $dist->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('district')
                                                <span class="invalid-feedback">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> Select district first to load wards
                                            </small>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="ward_id">Ward <span class="text-danger">*</span></label>
                                            <select id="ward_id"
                                                class="form-control @error('ward_id') is-invalid @enderror"
                                                wire:model.live="ward_id" {{ empty($wards) ? 'disabled' : '' }}>
                                                <option value="">-- Select Ward --</option>
                                                @foreach ($wards as $ward)
                                                    <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('ward_id')
                                                <span class="invalid-feedback">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                            @if (empty($wards) && $district)
                                                <small class="form-text text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> No wards available for
                                                    selected district
                                                </small>
                                            @elseif (empty($wards))
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-arrow-up"></i> Please select a district first
                                                </small>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label for="ranking">Ranking <span class="text-danger">*</span></label>
                                            <select id="ranking"
                                                class="form-control @error('ranking') is-invalid @enderror"
                                                wire:model="ranking">
                                                <option value="">-- Select Ranking --</option>
                                                <option value="fundi_kuu">Fundi Mkuu</option>
                                                <option value="fundi_msaidizi">Fundi</option>
                                            </select>
                                            @error('ranking')
                                                <span class="invalid-feedback">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Profile Photo --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-camera mr-2"></i>Profile Photo (Optional)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="profile_photo"
                                                wire:model="profile_photo">
                                            <label class="custom-file-label" for="profile_photo">Choose
                                                file...</label>
                                        </div>
                                        <small class="form-text text-muted">Upload a clear photo. Maximum size:
                                            2MB.</small>
                                        @error('profile_photo')
                                            <span class="error text-danger"><small>{{ $message }}</small></span>
                                        @enderror

                                        @if ($profile_photo)
                                            <div class="mt-3 text-center">
                                                <img src="{{ $profile_photo->temporaryUrl() }}" alt="Preview"
                                                    class="img-thumbnail" style="max-height: 150px;">
                                                <p class="mt-2 text-muted">Photo preview</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-custom btn-lg" {{ $saving ? 'disabled' : '' }}>
                                    @if ($saving)
                                        <span class="spinner-border spinner-border-sm mr-2"></span>
                                        Processing...
                                    @else
                                        <i></i>Submit
                                    @endif
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #01aef3 0%, #1c1a6d 50%, #1c1a6d 100%);
            min-height: 100vh;
            color: #333;
        }
        .bg-custom {
            background-color: #007bff;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
        }

        .btn-custom:hover {
            background-color: #0056b3;
            color: white;
        }

        .text-custom {
            color: #007bff;
        }
    </style>
</div>
