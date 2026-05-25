@extends('layouts.app')

@section('title', 'Dress Shop — Browse Our Collection')

@section('content')
<section class="hero animate-fade-in">
    <h1>Discover Your Perfect Dress</h1>
    <p>Explore our curated collection of beautiful dresses for every occasion.</p>
</section>

@if(count($dresses) === 0)
    <div class="empty-state animate-fade-in">
        <div class="empty-icon">👗</div>
        <h2>No dresses available</h2>
        <p>Check back soon — we're adding new styles all the time!</p>
    </div>
@else
    <div class="product-grid">
        @foreach($dresses as $dress)
            <div class="item">
                <div class="media">
                    @if(!empty($dress['image']))
                        <a href="/product/{{ $dress['id'] }}">
                            <img src="/images/{{ rawurlencode($dress['image']) }}"
                                 alt="{{ $dress['name'] }}"
                                 loading="lazy">
                        </a>
                    @else
                        <a href="/product/{{ $dress['id'] }}" class="no-image-placeholder">
                            <span>No image</span>
                        </a>
                    @endif
                    @if(!empty($dress['attributes']['color']))
                        <span class="color-tag">{{ $dress['attributes']['color'] }}</span>
                    @endif
                </div>
                <div class="body">
                    <div class="meta-row">
                        @if(!empty($dress['sku']))
                            <span class="sku">{{ $dress['sku'] }}</span>
                        @endif
                        <h3>
                            <a href="/product/{{ $dress['id'] }}">{{ $dress['name'] }}</a>
                        </h3>
                        <div class="price">${{ number_format($dress['price'], 2) }}</div>
                    </div>
                    @if(!empty($dress['description']))
                        <p class="description">{{ Str::limit($dress['description'], 80) }}</p>
                    @endif
                    <div class="card-footer">
                        @if(!empty($dress['attributes']))
                            <div class="attributes">
                                @foreach($dress['attributes'] as $key => $value)
                                    <span class="attribute-tag">{{ ucfirst($key) }}: {{ $value }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if(isset($dress['stock']))
                            <span class="stock {{ $dress['stock'] < 5 ? 'low' : 'ok' }}">
                                {{ $dress['stock'] < 5 ? 'Only ' . $dress['stock'] . ' left!' : 'In Stock' }}
                            </span>
                        @endif
                    </div>
                    <div class="actions">
                        <a class="btn btn-outline" href="/product/{{ $dress['id'] }}">View Details</a>
                        <form method="post" action="/cart/add" class="inline-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ $dress['id'] }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
