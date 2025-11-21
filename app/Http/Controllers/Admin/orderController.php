<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class OrderController extends Controller
{
    protected string $dressesFile = 'dresses.json';
    protected string $ordersFile  = 'orders.json';

    // dresses list
    public function dresses(Request $request)
    {
        $dresses = $this->loadDresses();

        if ($request->wantsJson()) {
            return response()->json(array_values($dresses));
        }

        return view('shop.dresses', ['dresses' => $dresses]);
    }

    // add to cart
    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'quantity' => 'sometimes|integer|min:1'
        ]);

        $dresses = $this->loadDresses();
        $dress = $dresses[$data['id']] ?? null;

        if (! $dress) {
            return redirect()->back()->with('error', 'Dress not found');
        }

        $qty = $data['quantity'] ?? 1;

        $cart = session()->get('cart', []);
        if (isset($cart[$data['id']])) {
            $cart[$data['id']]['quantity'] += $qty;
        } else {
            $cart[$data['id']] = [
                'id' => $dress['id'],
                'name' => $dress['name'],
                'price' => $dress['price'],
                'quantity' => $qty,
            ];
        }

        session(['cart' => $cart]);

        return redirect()->back()->with('success', 'Added to cart');
    }

    // see cart
    public function cart(Request $request)
    {
        $cart = session()->get('cart', []);

        if ($request->wantsJson()) {
            return response()->json(array_values($cart));
        }

        return view('shop.cart', ['cart' => $cart]);
    }

    // checkout
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'address' => 'required|string',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Cart is empty');
        }

        $total = array_reduce($cart, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);

        $orders = $this->loadOrders();

        $order = [
            'id' => (string) Str::uuid(),
            'customer' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'address' => $data['address'],
            ],
            'items' => array_values($cart),
            'total' => $total,
            'status' => 'pending',
            'created_at' => now()->toDateTimeString(),
        ];

        $orders[$order['id']] = $order;
        $this->saveOrders($orders);

        // clear cart
        session()->forget('cart');

        if ($request->wantsJson()) {
            return response()->json(['order' => $order], 201);
        }

        return redirect()->route('admin.orders.index')->with('success', 'Order placed');
    }

    // Admins list orders
    public function orders(Request $request)
    {
        $orders = $this->loadOrders();

        if ($request->wantsJson()) {
            return response()->json(array_values($orders));
        }

        return view('admin.orders.index', ['orders' => $orders]);
    }

    // Admins show single order
    public function showOrder(Request $request, string $id)
    {
        $orders = $this->loadOrders();
        $order = $orders[$id] ?? null;

        if (! $order) {
            abort(404, 'Order not found');
        }

        if ($request->wantsJson()) {
            return response()->json($order);
        }

        return view('admin.orders.show', ['order' => $order]);
    }

    // Admins update order status
    public function updateStatus(Request $request, string $id)
    {
        $data = $request->validate(['status' => 'required|string']);

        $orders = $this->loadOrders();
        if (! isset($orders[$id])) {
            return redirect()->back()->with('error', 'Order not found');
        }

        $orders[$id]['status'] = $data['status'];
        $orders[$id]['updated_at'] = now()->toDateTimeString();
        $this->saveOrders($orders);

        return redirect()->back()->with('success', 'Order updated');
    }

    //  Helpers 
    protected function loadDresses(): array
    {
        if (! Storage::exists($this->dressesFile)) {
            // sample dress seed
            $sample = [
                'dress-1' => ['id' => 'dress-1', 'name' => 'Red Evening Dress', 'price' => 149.99, 'image' => '/images/dress-red.jpg'],
                'dress-2' => ['id' => 'dress-2', 'name' => 'Blue Summer Dress', 'price' => 89.50, 'image' => '/images/dress-blue.jpg'],
                'dress-3' => ['id' => 'dress-3', 'name' => 'Green Cocktail Dress', 'price' => 129.00, 'image' => '/images/dress-green.jpg'],
            ];
            $this->saveDresses($sample);
            return $sample;
        }

        $json = Storage::get($this->dressesFile);
        $arr = json_decode($json, true) ?: [];
        return $arr;
    }

    protected function saveDresses(array $dresses): void
    {
        Storage::put($this->dressesFile, json_encode($dresses, JSON_PRETTY_PRINT));
    }

    protected function loadOrders(): array
    {
        if (! Storage::exists($this->ordersFile)) {
            $this->saveOrders([]);
            return [];
        }

        $json = Storage::get($this->ordersFile);
        $arr = json_decode($json, true) ?: [];
        return $arr;
    }

    protected function saveOrders(array $orders): void
    {
        Storage::put($this->ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
    }
}
