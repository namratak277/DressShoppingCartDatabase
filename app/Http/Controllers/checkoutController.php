<?php
include '../includes/header.php';

$cart_items = getCartItems();
$cart_total = getCartTotal();

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$error_message = '';
$success = false;
$order_id = null;

// checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $token = $_POST['csrf_token'] ?? null;
    if (! verify_csrf_token($token)) {
        $error_message = 'Invalid CSRF token';
    } else {
        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $customer_email = sanitize($_POST['customer_email'] ?? '');
        $customer_address = sanitize($_POST['customer_address'] ?? '');
        $customer_phone = sanitize($_POST['customer_phone'] ?? '');

        // Validation
        if (empty($customer_name) || empty($customer_email) || empty($customer_address)) {
            $error_message = "Please fill in all required fields.";
        } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // check stock availability
            $stock_error = false;
            foreach ($cart_items as $item) {
                $stock = isset($item['stock']) ? (int)$item['stock'] : PHP_INT_MAX;
                if ((int)$item['quantity'] > $stock) {
                    $stock_error = true;
                    $error_message = "Sorry, {$item['name']} has insufficient stock.";
                    break;
                }
            }

            if (! $stock_error) {
                $customer_data = [
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'address' => $customer_address,
                    'phone' => $customer_phone,
                ];

                $order_id = createOrder($customer_data, $cart_items, $cart_total);

                if ($order_id) {
                    clearCart();
                    $success = true;
                } else {
                    $error_message = "Failed to place order. Please try again.";
                }
            }
        }
    }
}
?>

<style>
    .checkout-container {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 800px;
        margin: 0 auto;
    }
    
    h2 {
        margin-bottom: 1.5rem;
        color: #333;
    }
    
    h3 {
        margin: 1.5rem 0 1rem 0;
        color: #555;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: bold;
        color: #333;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.7rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        font-family: inherit;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .required {
        color: #e74c3c;
    }
    
    .order-summary {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 4px;
        margin-bottom: 2rem;
    }
    
    .order-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .order-total {
        font-size: 1.3rem;
        font-weight: bold;
        margin-top: 1rem;
        text-align: right;
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
    
    .checkout-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }
    
    .success-message {
        text-align: center;
        padding: 2rem;
    }
    
    .success-icon {
        font-size: 4rem;
        color: #27ae60;
        margin-bottom: 1rem;
    }
</style>

<div class="checkout-container">
    <?php if ($success): ?>
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h2>Order Placed Successfully!</h2>
            <p>Thank you for your order. Your order ID is #<?php echo $order_id; ?>.</p>
            <p>We will send a confirmation email to your address shortly.</p>
            <br>
            <a href="index.php" class="btn">Continue Shopping</a>
            <a href="orders.php" class="btn btn-secondary">View Orders</a>
        </div>
    <?php else: ?>
        <h2>Checkout</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <h3>Order Summary</h3>
        <div class="order-summary">
            <?php foreach ($cart_items as $item): ?>
                <div class="order-item">
                    <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                    <span><?php echo formatPrice($item['subtotal']); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="order-total">
                Total: <?php echo formatPrice($cart_total); ?>
            </div>
        </div>
        
        <h3>Customer Information</h3>
        <form method="post">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="customer_name">Full Name <span class="required">*</span></label>
                <input type="text" id="customer_name" name="customer_name" 
                       value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="customer_email">Email Address <span class="required">*</span></label>
                <input type="email" id="customer_email" name="customer_email" 
                       value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="customer_phone">Phone Number</label>
                <input type="tel" id="customer_phone" name="customer_phone" 
                       value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="customer_address">Shipping Address <span class="required">*</span></label>
                <textarea id="customer_address" name="customer_address" required><?php echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : ''; ?></textarea>
            </div>
            
            <div class="checkout-actions">
                <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
