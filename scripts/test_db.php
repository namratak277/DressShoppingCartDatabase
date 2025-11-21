<?php
// Quick DB connectivity test used by development.
require_once __DIR__ . '/../app/Http/includes/functions.php';

$cfg = [
    'host' => getenv('PG_HOST') ?: getenv('DB_HOST'),
    'port' => getenv('PG_PORT') ?: getenv('DB_PORT'),
    'dbname' => getenv('PG_DB') ?: getenv('DB_DATABASE'),
    'user' => getenv('PG_USER') ?: getenv('DB_USERNAME'),
    'pass' => getenv('PG_PASS') ?: getenv('DB_PASSWORD'),
];

if (empty($cfg['dbname']) || empty($cfg['user'])) {
    echo "Missing DB configuration. Check .env or PG_*/DB_* environment variables.\n";
    exit(1);
}

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $cfg['host'] ?: '127.0.0.1', $cfg['port'] ?: '5432', $cfg['dbname']);
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query('SELECT id, sku, "name" as name, price, image_url FROM public.products ORDER BY id LIMIT 5');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Connected to Postgres. Sample rows:\n";
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    exit(0);
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(2);
}
