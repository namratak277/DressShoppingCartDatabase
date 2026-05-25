<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected string $dressesFile = 'dresses.json';
    protected string $ordersFile  = 'orders.json';

    public function dresses(Request $request)
    {
        $dresses = $this->loadDresses();

        if ($request->has('search')) {
            $query = strtolower(trim($request->input('search')));
            $dresses = array_filter($dresses, function ($d) use ($query) {
                $haystack = strtolower(
                    ($d['name'] ?? '') . ' ' .
                    ($d['description'] ?? '') . ' ' .
                    ($d['sku'] ?? '') . ' ' .
                    ($d['attributes']['color'] ?? '') . ' ' .
                    ($d['attributes']['size'] ?? '')
                );
                return str_contains($haystack, $query);
            });
        }

        if ($request->wantsJson()) {
            return response()->json(array_values($dresses));
        }

        return view('shop.dresses', ['dresses' => $dresses]);
    }

    public function showProduct(Request $request, string $id)
    {
        $dresses = $this->loadDresses();
        $product = $dresses[$id] ?? null;

        if (! $product) {
            abort(404, 'Product not found');
        }

        return view('shop.product', ['product' => $product]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'quantity' => 'sometimes|integer|min:1',
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
                'id'       => $dress['id'],
                'name'     => $dress['name'],
                'price'    => $dress['price'],
                'image'    => $dress['image'] ?? '',
                'quantity' => $qty,
            ];
        }

        session(['cart' => $cart]);

        return redirect('/cart')->with('success', $dress['name'] . ' added to cart!');
    }

    public function cart(Request $request)
    {
        $cart = session()->get('cart', []);

        if ($request->wantsJson()) {
            return response()->json(array_values($cart));
        }

        return view('shop.cart', ['cart' => $cart]);
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$data['id']])) {
            $cart[$data['id']]['quantity'] = $data['quantity'];
            session(['cart' => $cart]);
        }

        return redirect('/cart');
    }

    public function removeFromCart(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
        ]);

        $cart = session()->get('cart', []);
        unset($cart[$data['id']]);
        session(['cart' => $cart]);

        return redirect('/cart')->with('success', 'Item removed from cart.');
    }

    public function checkoutForm(Request $request)
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        return view('shop.checkout', ['cart' => $cart]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:30',
            'address' => 'required|string',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect('/cart')->with('error', 'Cart is empty');
        }

        $total = array_reduce($cart, fn ($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);

        $orders = $this->loadOrders();

        $order = [
            'id'         => (string) Str::uuid(),
            'customer'   => [
                'name'    => $data['name'],
                'email'   => $data['email'],
                'phone'   => $data['phone'] ?? '',
                'address' => $data['address'],
            ],
            'items'      => array_values($cart),
            'total'      => $total,
            'status'     => 'pending',
            'created_at' => now()->toDateTimeString(),
        ];

        $orders[$order['id']] = $order;
        $this->saveOrders($orders);

        session()->forget('cart');

        if ($request->wantsJson()) {
            return response()->json(['order' => $order], 201);
        }

        return view('shop.checkout', ['cart' => [], 'order' => $order]);
    }

    public function orders(Request $request)
    {
        $orders = $this->loadOrders();

        if ($request->wantsJson()) {
            return response()->json(array_values($orders));
        }

        return view('admin.orders.index', ['orders' => $orders]);
    }

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

        return redirect()->back()->with('success', 'Order status updated.');
    }

    protected function loadDresses(): array
    {
        if (! Storage::exists($this->dressesFile)) {
            $sample = $this->defaultDresses();
            $this->saveDresses($sample);
            return $sample;
        }

        $json = Storage::get($this->dressesFile);
        return json_decode($json, true) ?: [];
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
        return json_decode($json, true) ?: [];
    }

    protected function saveOrders(array $orders): void
    {
        Storage::put($this->ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
    }

    protected function defaultDresses(): array
    {
        return [
            'dress-1'  => ['id' => 'dress-1',  'name' => 'Red Evening Dress',        'sku' => 'DR-001', 'price' => 149.99, 'stock' => 12, 'image' => 'dress-red.jpg',        'description' => 'A stunning red evening dress perfect for formal occasions and galas.', 'attributes' => ['color' => 'Red', 'size' => 'M']],
            'dress-2'  => ['id' => 'dress-2',  'name' => 'Blue Summer Dress',         'sku' => 'DR-002', 'price' => 89.50,  'stock' => 20, 'image' => 'dress-blue.jpg',       'description' => 'Light and breezy blue summer dress ideal for warm weather outings.', 'attributes' => ['color' => 'Blue', 'size' => 'S']],
            'dress-3'  => ['id' => 'dress-3',  'name' => 'Green Cocktail Dress',      'sku' => 'DR-003', 'price' => 129.00, 'stock' => 8,  'image' => 'dress-green.jpg',      'description' => 'Elegant green cocktail dress with a flattering silhouette.', 'attributes' => ['color' => 'Green', 'size' => 'L']],
            'dress-4'  => ['id' => 'dress-4',  'name' => 'Black Classic Dress',       'sku' => 'DR-004', 'price' => 179.99, 'stock' => 15, 'image' => 'dress-black.jpg',      'description' => 'Timeless black dress that works for any occasion. A wardrobe essential.', 'attributes' => ['color' => 'Black', 'size' => 'M']],
            'dress-5'  => ['id' => 'dress-5',  'name' => 'White Bridal Dress',        'sku' => 'DR-005', 'price' => 299.99, 'stock' => 4,  'image' => 'dress-white.jpg',      'description' => 'Gorgeous white bridal dress with delicate lace detailing.', 'attributes' => ['color' => 'White', 'size' => 'S']],
            'dress-6'  => ['id' => 'dress-6',  'name' => 'Pink Party Dress',          'sku' => 'DR-006', 'price' => 109.00, 'stock' => 18, 'image' => 'dress-pink.jpg',       'description' => 'Fun and vibrant pink party dress for nights out on the town.', 'attributes' => ['color' => 'Pink', 'size' => 'M']],
            'dress-7'  => ['id' => 'dress-7',  'name' => 'Yellow Sundress',           'sku' => 'DR-007', 'price' => 79.99,  'stock' => 22, 'image' => 'dress-yellow.jpg',     'description' => 'Cheerful yellow sundress perfect for picnics and beach days.', 'attributes' => ['color' => 'Yellow', 'size' => 'L']],
            'dress-8'  => ['id' => 'dress-8',  'name' => 'Lilac Garden Dress',        'sku' => 'DR-008', 'price' => 119.50, 'stock' => 10, 'image' => 'dress-lilac.jpg',      'description' => 'Romantic lilac dress with flowy fabric, ideal for garden parties.', 'attributes' => ['color' => 'Lilac', 'size' => 'S']],
            'dress-9'  => ['id' => 'dress-9',  'name' => 'Dark Purple Gala Dress',    'sku' => 'DR-009', 'price' => 199.00, 'stock' => 6,  'image' => 'dress-darkpurple.jpg', 'description' => 'Regal dark purple gala dress that commands attention.', 'attributes' => ['color' => 'Purple', 'size' => 'M']],
            'dress-10' => ['id' => 'dress-10', 'name' => 'Pattern Bohemian Dress',    'sku' => 'DR-010', 'price' => 99.00,  'stock' => 14, 'image' => 'dress-pattern.jpg',    'description' => 'Unique patterned bohemian dress with artistic flair.', 'attributes' => ['color' => 'Multi', 'size' => 'L']],
            'dress-11' => ['id' => 'dress-11', 'name' => 'Ocean Blue Maxi Dress',     'sku' => 'DR-011', 'price' => 139.00, 'stock' => 9,  'image' => 'dress-blue-1763584748.jpg', 'description' => 'Flowing ocean blue maxi dress for elegant seaside evenings.', 'attributes' => ['color' => 'Blue', 'size' => 'M']],
            'dress-12' => ['id' => 'dress-12', 'name' => 'Sky Blue Cocktail Dress',   'sku' => 'DR-012', 'price' => 125.00, 'stock' => 11, 'image' => 'dress-blue-1763586247.jpg', 'description' => 'Chic sky blue cocktail dress with modern cut and style.', 'attributes' => ['color' => 'Blue', 'size' => 'S']],
        ];
    }
}
