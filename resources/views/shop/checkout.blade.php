@extends('layouts.app')

@section('title', 'Checkout — Dress Shop')

@section('content')
<div class="checkout-container animate-fade-in">
    @if(isset($order))
        <div class="success-message">
            <div class="success-icon">&#10003;</div>
            <h2>Order Placed Successfully!</h2>
            <p>Thank you for your order.</p>
            <p>Your order ID is <strong>#{{ $order['id'] }}</strong>.</p>
            <p>We will send a confirmation email shortly.</p>
            <a href="/" class="btn btn-primary" style="margin-top:1.5rem">Continue Shopping</a>
        </div>
    @else
        <h2>Checkout</h2>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="checkout-grid">
            <div class="checkout-form-section">
                <h3>Customer Information</h3>
                <form method="post" action="/checkout" id="checkout-form">
                    @csrf
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div class="form-group">
                        <label for="address">Shipping Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" required>{{ old('address') }}</textarea>
                    </div>
                    <div class="checkout-actions">
                        <a href="/cart" class="btn btn-outline">Back to Cart</a>
                        <button type="submit" class="btn btn-success">Place Order</button>
                    </div>
                </form>
            </div>

            <div class="order-summary-section">
                <h3>Order Summary</h3>
                <div class="order-summary">
                    @php $total = 0; @endphp
                    @foreach($cart as $item)
                        @php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; @endphp
                        <div class="order-item">
                            <span>{{ $item['name'] }} &times; {{ $item['quantity'] }}</span>
                            <span>${{ number_format($subtotal, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="order-total">
                        <span>Total</span>
                        <span>${{ number_format($total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
