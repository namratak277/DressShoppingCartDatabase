<?php
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$orders = getAllOrders(null);
?>
<style>
    .orders-container{background:#fff;padding:1.5rem;border-radius:8px}
</style>
<div class="orders-container">
    <h2>Orders</h2>
    <?php if (empty($orders)): ?>
        <p>No orders yet</p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <thead><tr><th>ID</th><th>Customer</th><th>Total</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?php echo htmlspecialchars($o['id']); ?></td>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? ($o['customer']['name'] ?? '')); ?></td>
                    <td><?php echo formatPrice($o['total_amount'] ?? $o['total'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($o['created_at'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
