<?php
include __DIR__ . '/../app/Http/includes/header.php';
require_once __DIR__ . '/../app/Http/includes/functions.php';

 $id = $_GET['id'] ?? null;
 $product = null;
 if ($id) {
     // Try Postgres first
     $product = getProductFromPostgres($id);
     // Fallback to file-backed JSON
     if (! $product) {
         $product = getProductById($id);
     }
 }
if (! $product) {
    http_response_code(404);
    echo '<div style="max-width:800px;margin:2rem auto;background:white;padding:1.5rem;border-radius:8px">';
    echo '<h2>Product not found</h2>';
    echo '<a href="/">Back to products</a>';
    echo '</div>';
    include __DIR__ . '/../app/Http/includes/footer.php';
    exit;
}

$inCart = isset($_SESSION['cart'][$product['id']]);
?>

<style>
.product-detail{max-width:900px;margin:2rem auto;background:white;padding:1.5rem;border-radius:8px}
.product-grid{display:flex;gap:1.5rem}
.product-grid img{width:420px;height:320px;object-fit:cover;border-radius:8px}
</style>

<div class="product-detail">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem">
        <div style="flex:0 0 420px">
            <?php if ($product['image'] && file_exists(__DIR__ . '/images/' . $product['image'])): ?>
                <img src="/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <div style="width:420px;height:320px;background:#f2f2f2;display:flex;align-items:center;justify-content:center">No image</div>
            <?php endif; ?>
        </div>
        <div style="flex:1">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku'] ?? ''); ?></p>
            <p style="font-size:1.2rem;font-weight:700;margin:0.5rem 0"><?php echo formatPrice($product['price']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>

            <?php if (!empty($product['attributes'])): ?>
                <ul>
                    <?php foreach ($product['attributes'] as $k => $v): ?>
                        <li><?php echo htmlspecialchars($k); ?>: <?php echo htmlspecialchars($v); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="/cart.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                <label>Quantity</label>
                <input type="number" name="quantity" value="1" min="1" style="width:80px;margin-right:0.5rem">
                <button class="btn btn-success" type="submit" name="add_to_cart">Add to cart</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/Http/includes/footer.php'; ?>
