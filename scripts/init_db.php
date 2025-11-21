<?php

$root = dirname(__DIR__);
$dbDir = $root . '/database';
$dbFile = $dbDir . '/database.sqlite';

if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0755, true)) {
        fwrite(STDERR, "Failed to create database directory: $dbDir\n");
        exit(1);
    }
}

if (file_exists($dbFile)) {
    echo "Removing existing database at $dbFile\n";
    unlink($dbFile);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $create = <<<SQL
CREATE TABLE products (
  id INTEGER PRIMARY KEY,
  sku TEXT UNIQUE,
  name TEXT,
  image TEXT,
  price REAL,
  stock INTEGER,
  description TEXT,
  attributes TEXT,
  created_at TEXT
);
SQL;
    $pdo->exec($create);

    $jsonFile = $root . '/storage/app/products.json';
    if (!file_exists($jsonFile)) {
        fwrite(STDERR, "products.json not found at $jsonFile\n");
        exit(1);
    }

    $products = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($products)) {
        fwrite(STDERR, "Failed to decode products.json\n");
        exit(1);
    }

    $stmt = $pdo->prepare('INSERT INTO products (id, sku, name, image, price, stock, description, attributes, created_at) VALUES (:id, :sku, :name, :image, :price, :stock, :description, :attributes, :created_at)');

    $count = 0;
    foreach ($products as $p) {
        $attrs = isset($p['attributes']) ? json_encode($p['attributes']) : json_encode(new stdClass());
        $stmt->execute([
            ':id' => $p['id'] ?? null,
            ':sku' => $p['sku'] ?? null,
            ':name' => $p['name'] ?? null,
            ':image' => $p['image'] ?? null,
            ':price' => $p['price'] ?? 0,
            ':stock' => $p['stock'] ?? 0,
            ':description' => $p['description'] ?? null,
            ':attributes' => $attrs,
            ':created_at' => $p['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
        $count++;
    }

    echo "Created SQLite DB at $dbFile and inserted $count products.\n";
    echo "Open your browser to the site root or run PHP built-in server:\n";
    echo "  php -S 127.0.0.1:8080 -t public\n";

} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
