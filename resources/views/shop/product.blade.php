@extends('layouts.app')

@section('title', $product['name'] . ' — Dress Shop')

@section('content')
<div class="breadcrumb animate-fade-in">
    <a href="/">Home</a> <span>/</span> <span>{{ $product['name'] }}</span>
</div>

<div class="product-detail animate-fade-in">
    <div class="product-gallery">
        @if(!empty($product['image']))
            <img src="/images/{{ rawurlencode($product['image']) }}"
                 alt="{{ $product['name'] }}"
                 class="product-main-image">
        @else
            <div class="product-no-image">No image available</div>
        @endif
    </div>

    <div class="product-info">
        @if(!empty($product['sku']))
            <span class="product-sku">SKU: {{ $product['sku'] }}</span>
        @endif
        <h1>{{ $product['name'] }}</h1>
        <p class="product-price">${{ number_format($product['price'], 2) }}</p>

        @if(!empty($product['description']))
            <div class="product-description">
                <h3>Description</h3>
                <p>{!! nl2br(e($product['description'])) !!}</p>
            </div>
        @endif

        @if(!empty($product['attributes']))
            <div class="product-attributes">
                <h3>Details</h3>
                <dl>
                    @foreach($product['attributes'] as $key => $value)
                        <div class="attribute-pair">
                            <dt>{{ ucfirst($key) }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        @if(isset($product['stock']))
            <div class="product-stock {{ $product['stock'] < 5 ? 'low' : 'ok' }}">
                @if($product['stock'] < 5)
                    Only {{ $product['stock'] }} left in stock — order soon!
                @else
                    In Stock ({{ $product['stock'] }} available)
                @endif
            </div>
        @endif

        <form method="post" action="/cart/add" class="add-to-cart-form">
            @csrf
            <input type="hidden" name="id" value="{{ $product['id'] }}">
            <div class="quantity-selector">
                <label for="quantity">Quantity</label>
                <div class="quantity-controls">
                    <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1"
                           max="{{ $product['stock'] ?? 99 }}">
                    <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Add to Cart</button>
        </form>

        <a href="/" class="back-link">← Back to all dresses</a>
    </div>
</div>

<script>
function changeQty(delta) {
    var input = document.getElementById('quantity');
    var newVal = parseInt(input.value) + delta;
    var max = parseInt(input.max) || 99;
    if (newVal >= 1 && newVal <= max) input.value = newVal;
}
</script>
@endsection
