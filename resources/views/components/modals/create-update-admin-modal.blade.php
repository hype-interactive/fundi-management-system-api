<div wire:ignore.self class="modal fade" id="createEditAdmin" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="exampleModalLabel">{{ $action_text }} Administrator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="createOrUpdateAdmin">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inputName">First Name</label>
                            <input type="text" class="form-control" wire:model="fname">
                            @error('fname')
                                <span class="error text-danger"><small>This field is required</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputPhone">Second Name</label>
                            <input type="text" class="form-control" wire:model="lname">
                            @error('lname')
                                <span class="error text-danger"><small>This field is required</small></span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inputSubward">Phone</label>
                            <input type="phone" class="form-control" wire:model="phone">
                            @error('phone')
                                <span class="error text-danger"><small>This field is required</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputRoad">Email</label>
                            <input type="email" class="form-control" wire:model="email">
                            @error('email')
                                <span class="error text-danger"><small>This field is required</small></span>
                            @enderror
                        </div>
                    </div>
                    {{-- <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inputHNo">Password</label>
                            <input type="password" class="form-control" wire:model="rpassword">
                            @error('rpassword')
                                <span class="error text-danger"><small>Password is required</small></span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputAdmin">Confirm Password</label>
                            <input type="password" class="form-control" wire:model="password">
                            @error('password')
                                <span class="error text-danger"><small>Password is required, must match the
                                        previous</small></span>
                            @enderror
                        </div>
                    </div> --}}
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inputRole">Role</label>
                            <select wire:model="role" id="inputRole" class="form-control">
                                <option value="">-- Select Role --</option>
                                <option value="2">Administrator</option>
                                <option value="1">Super User</option>
                            </select>
                            @error('role')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputAdmin">District</label>
                            @include('components.selects.select_district')
                            @error('district')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-neg" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom">{{ $action_text }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
