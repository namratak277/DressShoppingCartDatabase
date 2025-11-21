<?php
include '../includes/header.php';

$cart_items = getCartItems();
$cart_total = getCartTotal();

// add to cart, remove, update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (! verify_csrf_token($token)) {
        $error_message = 'Invalid CSRF token';
    } else {
        if (isset($_POST['add_to_cart'])) {
            $id = $_POST['id'] ?? $_POST['product_id'] ?? null;
            $qty = max(1, intval($_POST['quantity'] ?? 1));

            $p = null;
            if (function_exists('getProductFromPostgres')) {
                $p = getProductFromPostgres($id);
            }
            if (! $p) {
                $p = getProductById($id);
            }

            if ($p) {
                $key = $p['id'];
                $cart = $_SESSION['cart'] ?? [];
                if (isset($cart[$key])) {
                    $cart[$key]['quantity'] += $qty;
                } else {
                    $cart[$key] = ['id' => $p['id'], 'name' => $p['name'], 'price' => $p['price'], 'quantity' => $qty, 'image' => $p['image'] ?? null, 'stock' => $p['stock'] ?? 0];
                }
                $_SESSION['cart'] = $cart;
                header('Location: cart.php');
                exit;
            }
        }

        if (isset($_POST['remove_item'])) {
            $product_id = $_POST['product_id'];
            if (removeFromCart($product_id)) {
                header('Location: cart.php');
                exit;
            }
        }

        if (isset($_POST['update_quantity'])) {
            $product_id = $_POST['product_id'];
            $quantity = intval($_POST['quantity']);
            updateCartQuantity($product_id, $quantity);
            header('Location: cart.php');
            exit;
        }
    }
}
?>

<style>
    .cart-container {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    h2 {
        margin-bottom: 1.5rem;
        color: #333;
    }
    
    .cart-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
    }
    
    .cart-table th {
        background-color: #f8f9fa;
        padding: 1rem;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
    }
    
    .cart-table td {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .product-image-small {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .quantity-input {
        width: 70px;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
    }
    
    .btn {
        display: inline-block;
        padding: 0.7rem 1.5rem;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s;
    }
    
    .btn:hover {
        background-color: #2980b9;
    }
    
    .btn-danger {
        background-color: #e74c3c;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .btn-danger:hover {
        background-color: #c0392b;
    }
    
    .btn-success {
        background-color: #27ae60;
    }
    
    .btn-success:hover {
        background-color: #229954;
    }
    
    .btn-secondary {
        background-color: #95a5a6;
    }
    
    .btn-secondary:hover {
        background-color: #7f8c8d;
    }
    
    .cart-summary {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 4px;
        margin-bottom: 1.5rem;
    }
    
    .cart-total {
        font-size: 1.5rem;
        font-weight: bold;
        text-align: right;
        margin-bottom: 1rem;
    }
    
    .cart-actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }
    
    .empty-cart {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    
    .empty-cart-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    
    .update-btn {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
    }
    
    .update-btn:hover {
        background-color: #2980b9;
    }
</style>

<div class="cart-container">
    <h2>Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Start shopping to add items to your cart.</p>
            <br>
            <a href="index.php" class="btn">Browse Products</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div class="product-image-small">
                                    <?php if ($item['image'] && public_image_exists($item['image'])): ?>
                                        <img src="<?php echo public_image_url($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.8rem;">No Image</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <br>
                                    <small style="color: #666;">Stock: <?php echo $item['stock']; ?> available</small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td>
                            <form method="post" style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock']; ?>" class="quantity-input" required>
                                <button type="submit" name="update_quantity" class="update-btn">Update</button>
                            </form>
                        </td>
                        <td><strong><?php echo formatPrice($item['subtotal']); ?></strong></td>
                        <td>
                            <form method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="cart-summary">
            <div class="cart-total">
                Total: <?php echo formatPrice($cart_total); ?>
            </div>
        </div>
        
        <div class="cart-actions">
            <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
