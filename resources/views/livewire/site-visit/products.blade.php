<div class="tab-pane fade show active">
    <div class="products-section">
        @if ($siteVisit->products->count() > 0)
            <div class="products-list">
                @foreach ($siteVisit->products as $product)
                    <div class="product-item">
                        <div class="product-info">
                            <h6 class="product-name">{{ $product->name }}</h6>
                            <div class="product-details">
                                <span class="quantity">
                                    <i class="fa-solid fa-boxes me-1"></i>
                                    Quantity: {{ $product->pivot->quantity }}
                                </span>
                                @if ($product->pivot->notes)
                                    <span class="notes">
                                        <i class="fa-solid fa-sticky-note me-1"></i>
                                        {{ $product->pivot->notes }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if ($product->price)
                            <div class="product-price">
                                <span class="price">TSh {{ number_format($product->price) }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-box fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Products Listed</h5>
                <p class="text-muted">No products were associated with this site visit.</p>
            </div>
        @endif
    </div>
</div>
