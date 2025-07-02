<div>
    <div class="page products-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="custom-header">
                    <h2>Products Management</h2>
                    <div class="actions">
                        <button class="btn btn-custom mr-4" wire:click="refresh" type="button" data-toggle="modal"
                            data-target="#createEditProduct">
                            <i class="fa-solid fa-add"></i>Create New Product
                        </button>
                    </div>
                </div>
                <div class="body pt-5">
                    <div class="row">
                        @forelse ($products as $item)
                            <div class="col-md-6 col-lg-4">
                                @include('components.product_card', ['product' => $item])
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    <i class="fa-solid fa-info-circle"></i>
                                    No products found. Create your first product to get started.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="section-right profile">
                    <h6>Product Information:</h6>
                    <div class="board mb-5">
                        @if ($is_editing && $product)
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="label">Name</div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="title">{{ $product->name }}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="label">Description</div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="title">{{ $product->description ?: 'No description' }}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="label">Visibility</div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="title">
                                        <span
                                            class="badge badge-{{ $product->visibility === 'visible' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($product->visibility) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="label">Availability</div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="title">
                                        <span
                                            class="badge badge-{{ $product->availability === 'available' ? 'success' : 'warning' }}">
                                            {{ ucfirst($product->availability) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if ($product->photo_url)
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="label">Photo</div>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="title">
                                            <img src="{{ $getThumbnail($product->photo_public_id, 150, 150) ?: $product->photo_url }}"
                                                alt="{{ $product->name }}" class="img-thumbnail"
                                                style="max-height: 100px;">
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="label">Created</div>
                                </div>
                                <div class="col-sm-9">
                                    <div class="title">{{ $product->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-box-open fa-3x mb-3"></i>
                                <p>Select a product to view details</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('components.modals.create-update-product-modal')
    </div>
</div>
