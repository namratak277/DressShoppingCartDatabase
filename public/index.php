<?php
// Public product listing using user's home layout
require_once __DIR__ . '/../app/Http/includes/header.php';

// Prefer Postgres when available; fall back to the existing JSON file-backed helpers.
$products = getProductsFromPostgres();
// If Postgres loader returns null, do not fall back to file-backed data.
if (! is_array($products)) {
    // Render header and a helpful message, then stop — avoid showing any hard-coded/demo products.
    echo '<div style="padding:2rem;background:#fff6f6;border:1px solid #ffd6d6;margin:1rem">';
    echo '<h2 style="color:#b02a37">Database Not Configured</h2>';
    echo '<p>The homepage requires a Postgres database connection. No database connection was detected or the query failed.</p>';
    echo '<p>To populate the database run the provided SQL import (for example using <code>psql</code>) or set the Postgres environment variables and try again.</p>';
    echo '<ul style="margin-left:1.2rem"><li>Set env vars: <code>PG_HOST</code>, <code>PG_PORT</code>, <code>PG_DB</code>, <code>PG_USER</code>, <code>PG_PASS</code></li><li>Import: <code>psql -d yourdb -f storage/app/import_all_tables_postgres.sql</code></li></ul>';
    echo '<p>If you prefer file-backed demo data temporarily, visit <code>/admin</code> to manage products.</p>';
    echo '</div>';
    include __DIR__ . '/../app/Http/includes/footer.php';
    exit;
}
?>
<div class="Homepage">
    <h1>Welcome to Our Website</h1>
    <p>Explore our amazing dresses and designs!</p>

    <?php
    // Render products in rows of 3 to match user's HTML
    $chunks = array_chunk($products, 3);
    foreach ($chunks as $row):
    ?>
    <div class="row">
        <?php foreach ($row as $p): ?>
            <div class="item">
                <div class="media">
                    <?php if (!empty($p['image']) && file_exists(__DIR__ . '/images/' . $p['image'])): ?>
                        <a href="/product.php?id=<?php echo urlencode($p['id']); ?>">
                            <img src="/images/<?php echo rawurlencode($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        </a>
                    <?php else: ?>
                        <a href="/product.php?id=<?php echo urlencode($p['id']); ?>">
                            <div style="width:100%;height:220px;display:flex;align-items:center;justify-content:center;background:#f5f5f8">No image</div>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="body">
                    <div class="meta-row">
                        <div class="sku"><?php echo htmlspecialchars($p['sku'] ?? ''); ?></div>
                        <h3><a href="/product.php?id=<?php echo urlencode($p['id']); ?>" style="color:inherit;text-decoration:none"><?php echo htmlspecialchars($p['name']); ?></a></h3>
                        <div class="price"><?php echo formatPrice($p['price'] ?? 0); ?></div>
                    </div>
                    <div class="description"><?php echo htmlspecialchars($p['description'] ?? ''); ?></div>
                    <div class="attributes">
                        <?php if (!empty($p['attributes']) && is_array($p['attributes'])): ?>
                            <?php foreach ($p['attributes'] as $k => $v): ?>
                                <span style="margin-right:8px;font-size:12px;color:#666"><?php echo htmlspecialchars($k . ': ' . $v); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <a class="btn" href="/product.php?id=<?php echo urlencode($p['id']); ?>">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../app/Http/includes/footer.php'; ?>
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
