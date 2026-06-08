<div>
    <div class="page admins-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="custom-header">
                    <h2>Administration</h2>
                    <div class="actions">
                        <button class="btn btn-custom mr-4" wire:click="refresh" type="button" data-toggle="modal"
                            data-target="#createEditAdmin"> <i class="fa-solid fa-add"></i>Create New</button>
                    </div>
                </div>
                <div class="body pt-5">
                    <div class="row">
                        @foreach ($admins as $item)
                            <div class="col-md-6">
                                @include('components.admin_card')
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="section-right profile ">
                    <h6>Administrator's Information:</h6>
                    <div class="board  mb-5">
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="label">Name</div>
                            </div>
                            <div class="  col-sm-9 ">
                                <div class="title">{{ ucfirst($admin->fname) }} {{ ucfirst($admin->lname) }}</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="label">Phone</div>
                            </div>
                            <div class="col-sm-9">
                                <div class="title">{{ $admin->phone }}</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="label">Email</div>
                            </div>
                            <div class="col-sm-9">
                                <div class="title">{{ $admin->email }}</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="label">Role</div>
                            </div>
                            <div class="col-sm-9">
                                <div class="title">{{ $role_name }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('components.modals.create-update-admin-modal')
    </div>
</div>
