@extends('layouts.app')

@section('title', 'Order #' . Str::limit($order['id'], 12) . ' — Admin')

@section('content')
<div class="admin-container animate-fade-in">
    <h2>Order #{{ Str::limit($order['id'], 12) }}</h2>

    <div class="order-detail-grid">
        <div>
            <h3>Customer</h3>
            <p><strong>Name:</strong> {{ $order['customer']['name'] ?? '' }}</p>
            <p><strong>Email:</strong> {{ $order['customer']['email'] ?? '' }}</p>
            <p><strong>Address:</strong> {{ $order['customer']['address'] ?? '' }}</p>
        </div>
        <div>
            <h3>Status</h3>
            <form method="post" action="/admin/orders/{{ $order['id'] }}/status">
                @csrf
                <select name="status" class="form-select">
                    @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ ($order['status'] ?? '') === $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem">Update</button>
            </form>
        </div>
    </div>

    <h3>Items</h3>
    <table class="orders-table">
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
        <tbody>
            @foreach($order['items'] ?? [] as $item)
                <tr>
                    <td>{{ $item['name'] ?? '' }}</td>
                    <td>{{ $item['quantity'] ?? 1 }}</td>
                    <td>${{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td>${{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align:right;font-size:1.3rem;font-weight:700;margin-top:1rem">
        Total: ${{ number_format($order['total'] ?? 0, 2) }}
    </p>

    <a href="/admin/orders" class="btn btn-outline" style="margin-top:1rem">← Back to Orders</a>
</div>
@endsection
