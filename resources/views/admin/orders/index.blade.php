@extends('layouts.app')

@section('title', 'Orders — Admin')

@section('content')
<div class="admin-container animate-fade-in">
    <h2>Orders</h2>
    @if(empty($orders))
        <p>No orders yet.</p>
    @else
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $id => $order)
                    <tr>
                        <td>{{ Str::limit($order['id'], 12) }}</td>
                        <td>{{ $order['customer']['name'] ?? '' }}</td>
                        <td>${{ number_format($order['total'] ?? 0, 2) }}</td>
                        <td><span class="status-badge status-{{ $order['status'] ?? 'pending' }}">{{ ucfirst($order['status'] ?? 'pending') }}</span></td>
                        <td>{{ $order['created_at'] ?? '' }}</td>
                        <td><a href="/admin/orders/{{ $order['id'] }}" class="btn btn-sm">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
