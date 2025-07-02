<div class="product-card" wire:click="setProduct({{ $product }})" data-toggle="modal" data-target="#createEditProduct">
    <div class="card-header">
        @if ($product->photo_url)
            <div class="product-image">
                <img src="{{ $getThumbnail($product->photo_public_id, 300, 200) ?: $product->photo_url }}"
                    alt="{{ $product->name }}" class="img-fluid">
            </div>
        @else
            <div class="product-image no-image">
                <i class="fa-solid fa-image fa-3x"></i>
            </div>
        @endif

        <div class="product-badges">
            <span class="badge badge-{{ $product->visibility === 'visible' ? 'success' : 'secondary' }}">
                {{ $product->visibility === 'visible' ? 'Visible' : 'Hidden' }}
            </span>
            <span class="badge badge-{{ $product->availability === 'available' ? 'primary' : 'warning' }}">
                {{ $product->availability === 'available' ? 'Available' : 'Unavailable' }}
            </span>
        </div>
    </div>

    <div class="card-body">
        <h5 class="product-name">{{ $product->name }}</h5>
        <p class="product-description">
            {{ $product->description ? Str::limit($product->description, 80) : 'No description available' }}
        </p>

        <div class="product-meta">
            <small class="text-muted">
                <i class="fa-solid fa-calendar-alt"></i>
                Created {{ $product->created_at->diffForHumans() }}
            </small>
        </div>
    </div>

    <div class="card-footer">
        <div class="product-actions">
            <button type="button" class="btn btn-sm btn-outline-primary"
                wire:click.stop="setProduct({{ $product }})" data-toggle="modal" data-target="#createEditProduct">
                <i class="fa-solid fa-edit"></i> Edit
            </button>

            <button type="button"
                class="btn btn-sm btn-outline-{{ $product->visibility === 'visible' ? 'secondary' : 'success' }}"
                wire:click.stop="toggleVisibility({{ $product->id }})"
                title="{{ $product->visibility === 'visible' ? 'Hide Product' : 'Show Product' }}">
                <i class="fa-solid fa-{{ $product->visibility === 'visible' ? 'eye-slash' : 'eye' }}"></i>
            </button>

            <button type="button"
                class="btn btn-sm btn-outline-{{ $product->availability === 'available' ? 'warning' : 'primary' }}"
                wire:click.stop="toggleAvailability({{ $product->id }})"
                title="{{ $product->availability === 'available' ? 'Mark Unavailable' : 'Mark Available' }}">
                <i
                    class="fa-solid fa-{{ $product->availability === 'available' ? 'times-circle' : 'check-circle' }}"></i>
            </button>

            <button type="button" class="btn btn-sm btn-outline-danger"
                wire:click.stop="deleteProduct({{ $product->id }})"
                onclick="return confirm('Are you sure you want to delete this product?')" title="Delete Product">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>
</div>
