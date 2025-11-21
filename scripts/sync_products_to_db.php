<?php
// Bulk sync products.json -> Postgres (image_url and basic fields)
// Usage: php -f scripts/sync_products_to_db.php

require_once __DIR__ . '/../app/Http/includes/functions.php';

echo "Loading products.json...\n";
$path = storage_path_file('products.json');
if (! file_exists($path)) {
    echo "products.json not found at: $path\n";
    exit(1);
}
$products = json_decode(file_get_contents($path), true);
if (! is_array($products)) {
    echo "No products found in JSON.\n";
    exit(1);
}

// DB config (reuse same env names as helpers)
$host = getenv('PG_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('PG_PORT') ?: getenv('DB_PORT') ?: '5432';
$db   = getenv('PG_DB') ?: getenv('DB_DATABASE') ?: '';
$user = getenv('PG_USER') ?: getenv('DB_USERNAME') ?: '';
$pass = getenv('PG_PASS') ?: getenv('DB_PASSWORD') ?: '';

if (empty($db) || empty($user)) {
    echo "Postgres config not found in environment (PG_DB/DB_DATABASE and PG_USER/DB_USERNAME required).\n";
    exit(1);
}

// Normalize host
$parsed = @parse_url($host);
if ($parsed !== false && ! empty($parsed['host'])) {
    $host = $parsed['host'];
} else {
    $host = preg_replace('#^https?://#i', '', $host);
    $host = rtrim($host, '/');
}

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    echo "Failed to connect to Postgres: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Connected to Postgres. Scanning products (" . count($products) . ")...\n";
$updated = 0; $inserted = 0; $skipped = 0; $errors = 0;

foreach ($products as $p) {
    $sku = $p['sku'] ?? null;
    $id = $p['id'] ?? null;
    $image = $p['image'] ?? null;
    if (empty($image)) {
        echo "- Product " . ($sku ?: $id) . " has no image; skipping.\n";
        $skipped++;
        continue;
    }

    // If file exists in public/images, prefer using that filename
    $imageBasename = basename($image);
    $publicPath = realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $imageBasename;
    if (! file_exists($publicPath)) {
        // try if image already path-like in JSON; accept it but warn
        echo "- Warning: image file not found for product " . ($sku ?: $id) . ": $imageBasename\n";
    }

    try {
        if ($sku) {
            // Try update by sku
            $stmt = $pdo->prepare('UPDATE products SET image_url = :image WHERE sku = :sku');
            $stmt->execute([':image' => $imageBasename, ':sku' => $sku]);
            if ($stmt->rowCount() > 0) {
                echo "- Updated product by SKU $sku -> image: $imageBasename\n";
                $updated++;
                continue;
            }
            // not found -> insert
            $insert = $pdo->prepare('INSERT INTO products (sku, "name", description, price, image_url, stock_quantity) VALUES (:sku, :name, :description, :price, :image, :stock)');
            $insert->execute([
                ':sku' => $sku,
                ':name' => $p['name'] ?? '',
                ':description' => $p['description'] ?? '',
                ':price' => isset($p['price']) ? (float)$p['price'] : 0,
                ':image' => $imageBasename,
                ':stock' => isset($p['stock']) ? (int)$p['stock'] : 0,
            ]);
            echo "- Inserted product SKU $sku with image $imageBasename\n";
            $inserted++;
        } elseif ($id) {
            $stmt = $pdo->prepare('UPDATE products SET image_url = :image WHERE id = :id');
            $stmt->execute([':image' => $imageBasename, ':id' => $id]);
            if ($stmt->rowCount() > 0) {
                echo "- Updated product by ID $id -> image: $imageBasename\n";
                $updated++;
            } else {
                echo "- No DB row matched ID $id; skipping.\n";
                $skipped++;
            }
        } else {
            echo "- Product missing SKU and ID; skipping.\n";
            $skipped++;
        }
    } catch (Exception $e) {
        echo "- Error syncing product " . ($sku ?: $id) . ": " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\nSummary: updated=$updated inserted=$inserted skipped=$skipped errors=$errors\n";

return 0;
