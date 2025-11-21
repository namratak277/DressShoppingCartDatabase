<?php
require_once __DIR__ . '/../../includes/header.php';

requireAdmin();

$products = getAllProducts(null);

// delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $token = $_POST['csrf_token'] ?? null;
    if (! verify_csrf_token($token)) {
        $error_message = 'Invalid CSRF token';
    } else {
        $id = intval($_POST['product_id']);
        if (deleteProductById($id)) {
            header('Location: products.php');
            exit;
        } else {
            $error_message = "Failed to delete product.";
        }
    }
}
?>

<style>
    .admin-container {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    h2 {
        margin: 0;
        color: #333;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
    }
    
    .products-table th {
        background-color: #f8f9fa;
        padding: 1rem;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
    }
    
    .products-table td {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .product-image-tiny {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
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
    
    .btn-danger {
        background-color: #e74c3c;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .btn-danger:hover {
        background-color: #c0392b;
    }
    
    .btn-small {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .actions-cell {
        display: flex;
        gap: 0.5rem;
    }
    
    .empty-products {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    
    .stock-low {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .stock-ok {
        color: #27ae60;
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h2>Product Management</h2>
        <a href="product_form.php" class="btn btn-success">Add New Product</a>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (empty($products)): ?>
        <div class="empty-products">
            <h3>No products found</h3>
            <p>Start by adding your first product.</p>
            <br>
            <a href="product_form.php" class="btn btn-success">Add Product</a>
        </div>
    <?php else: ?>
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <div class="product-image-tiny">
                                <?php if ($product['image'] && public_image_exists($product['image'])): ?>
                                    <img src="<?php echo public_image_url($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.7rem;">No img</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                        <td>
                            <span class="<?php echo $product['stock'] < 10 ? 'stock-low' : 'stock-ok'; ?>">
                                <?php echo $product['stock']; ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <div class="actions-cell">
                                <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-small">Edit</a>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <a href="../public/index.php" class="btn">View Store</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
