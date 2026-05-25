@extends('layouts.app')

@section('title', 'Shopping Cart — Dress Shop')

@section('content')
<div class="cart-container animate-fade-in">
    <h2>Shopping Cart</h2>

    @if(empty($cart))
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Browse our collection and find something you love.</p>
            <a href="/" class="btn btn-primary">Browse Dresses</a>
        </div>
    @else
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($cart as $id => $item)
                    @php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; @endphp
                    <tr>
                        <td>
                            <div class="cart-product">
                                <div class="cart-product-image">
                                    @if(!empty($item['image']))
                                        <img src="/images/{{ rawurlencode($item['image']) }}" alt="{{ $item['name'] }}">
                                    @else
                                        <span class="no-img-sm">No image</span>
                                    @endif
                                </div>
                                <strong>{{ $item['name'] }}</strong>
                            </div>
                        </td>
                        <td>${{ number_format($item['price'], 2) }}</td>
                        <td>
                            <form method="post" action="/cart/update" class="qty-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $id }}">
                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="quantity-input">
                                <button type="submit" class="btn btn-sm">Update</button>
                            </form>
                        </td>
                        <td><strong>${{ number_format($subtotal, 2) }}</strong></td>
                        <td>
                            <form method="post" action="/cart/remove">
                                @csrf
                                <input type="hidden" name="id" value="{{ $id }}">
                                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="cart-total">Total: ${{ number_format($total, 2) }}</div>
        </div>

        <div class="cart-actions">
            <a href="/" class="btn btn-outline">Continue Shopping</a>
            <a href="/checkout" class="btn btn-success">Proceed to Checkout</a>
        </div>
    @endif
</div>
@endsection
