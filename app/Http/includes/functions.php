<?php
$projectRoot = realpath(__DIR__ . '/../../../');
$envFile = $projectRoot !== false ? $projectRoot . DIRECTORY_SEPARATOR . '.env' : __DIR__ . '/../../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        $v = preg_replace('/^\"(.*)\"$/', '$1', $v);
        $v = preg_replace("/^'(.*)'$/", '$1', $v);
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

if (PHP_SAPI !== 'cli' && ! headers_sent() && session_status() === PHP_SESSION_NONE) {
    session_start();
}

$storageDir = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app';
if ($storageDir === false) {
    $storageDir = __DIR__ . '/../../../storage/app';
}

if (! is_dir($storageDir)) {
    @mkdir($storageDir, 0777, true);
}

function storage_path_file(string $name): string
{
    $base = realpath(__DIR__ . '/../../../') ?: (__DIR__ . '/../../../');
    return rtrim($base, "\/\\") . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $name;
}

function formatPrice($amount): string
{
    return '$' . number_format((float)$amount, 2);
}

function ensureProducts(): void
{
    $path = storage_path_file('products.json');
    if (! file_exists($path)) {
        $sample = [
            ['id' => 1, 'image' => 'dress-red.jpg', 'name' => 'Red Evening Dress', 'price' => 149.99, 'stock' => 12, 'created_at' => date('c')],
            ['id' => 2, 'image' => 'dress-blue.jpg', 'name' => 'Blue Summer Dress', 'price' => 89.50, 'stock' => 20, 'created_at' => date('c')],
            ['id' => 3, 'image' => 'dress-green.jpg', 'name' => 'Green Cocktail Dress', 'price' => 129.00, 'stock' => 8, 'created_at' => date('c')],
        ];
        file_put_contents($path, json_encode($sample, JSON_PRETTY_PRINT));
    }
}

function getAllProducts($conn = null): array
{
    ensureProducts();
    $path = storage_path_file('products.json');
    $json = @file_get_contents($path) ?: '[]';
    $arr = json_decode($json, true) ?: [];
    return $arr;
}

function deleteProductById(int $id): bool
{
    $products = getAllProducts();
    $found = false;
    foreach ($products as $i => $p) {
        if ((int)$p['id'] === $id) {
            $found = true;
            array_splice($products, $i, 1);
            break;
        }
    }
    if ($found) {
        file_put_contents(storage_path_file('products.json'), json_encode($products, JSON_PRETTY_PRINT));
    }
    return $found;
}

function saveProducts(array $products): bool
{
    return (bool) file_put_contents(storage_path_file('products.json'), json_encode($products, JSON_PRETTY_PRINT));
}

function getProductById($id)
{
    $products = getAllProducts();
    foreach ($products as $p) {
        if ((string)$p['id'] === (string)$id) return $p;
    }
    return null;
}

function getProductBySKU(string $sku)
{
    $products = getAllProducts();
    foreach ($products as $p) {
        if (isset($p['sku']) && strcasecmp($p['sku'], $sku) === 0) return $p;
    }
    return null;
}

function nextProductId(): int
{
    $products = getAllProducts();
    $max = 0;
    foreach ($products as $p) {
        $id = isset($p['id']) ? (int)$p['id'] : 0;
        if ($id > $max) $max = $id;
    }
    return $max + 1;
}

function addProduct(array $data)
{
    $products = getAllProducts();
    $id = nextProductId();
    $now = date('c');
    $product = array_merge([
        'id' => $id,
        'sku' => $data['sku'] ?? sprintf('SKU%04d', $id),
        'name' => $data['name'] ?? 'Untitled',
        'description' => $data['description'] ?? '',
        'price' => (float)($data['price'] ?? 0),
        'image' => $data['image'] ?? '',
        'stock' => (int)($data['stock'] ?? 0),
        'attributes' => $data['attributes'] ?? [],
        'created_at' => $now,
    ], $data);

    $products[] = $product;
    saveProducts($products);
    return $id;
}

function updateProductById($id, array $data): bool
{
    $products = getAllProducts();
    $found = false;
    foreach ($products as $i => $p) {
        if ((string)$p['id'] === (string)$id) {
            $found = true;
            $products[$i] = array_merge($p, $data);
            $products[$i]['id'] = $p['id'];
            $products[$i]['updated_at'] = date('c');
            break;
        }
    }
    if ($found) {
        saveProducts($products);
    }
    return $found;
}

//Image helpers
function public_images_dir(): string
{
    $base = realpath(__DIR__ . '/../../../public');
    if ($base === false) $base = __DIR__ . '/../../../public';
    return rtrim($base, "\/\\") . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
}

function public_image_exists(string $name): bool
{
    if (trim($name) === '') return false;
    return file_exists(public_images_dir() . $name);
}

function public_image_url(string $name): string
{
    return '/images/' . rawurlencode($name);
}

//Load postgres products
function getProductsFromPostgres(array $cfg = []): ?array
{
    $host = $cfg['host'] ?? getenv('PG_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
    $port = $cfg['port'] ?? getenv('PG_PORT') ?: getenv('DB_PORT') ?: '5432';
    $db   = $cfg['dbname'] ?? getenv('PG_DB') ?: getenv('DB_DATABASE') ?: '';
    $user = $cfg['user'] ?? getenv('PG_USER') ?: getenv('DB_USERNAME') ?: '';
    $pass = $cfg['pass'] ?? getenv('PG_PASS') ?: getenv('DB_PASSWORD') ?: '';
//not enough info to connect
    if (empty($db) || empty($user)) {
        return null;
    }

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

        $sql = 'SELECT id, sku, "name" as name, description, price, image_url as image, "size", color, stock_quantity FROM products ORDER BY id ASC';
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $attrs = [];
            if (isset($r['size']) && $r['size'] !== null) $attrs['size'] = (string)$r['size'];
            if (isset($r['color']) && $r['color'] !== null) $attrs['color'] = (string)$r['color'];

            $out[] = [
                'id' => $r['id'],
                'sku' => $r['sku'] ?? null,
                'name' => $r['name'] ?? '',
                'description' => $r['description'] ?? '',
                'price' => isset($r['price']) ? (float)$r['price'] : 0.0,
                'image' => isset($r['image']) && $r['image'] !== null ? basename($r['image']) : '',
                'stock' => isset($r['stock_quantity']) ? (int)$r['stock_quantity'] : (isset($r['stock']) ? (int)$r['stock'] : 0),
                'attributes' => $attrs,
            ];
        }
        return $out;
    } catch (Exception $e) {
        return null;
    }
}

//get product by id or sku
function getProductFromPostgres($idOrSku, array $cfg = []): ?array
{
    $host = $cfg['host'] ?? getenv('PG_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
    $port = $cfg['port'] ?? getenv('PG_PORT') ?: getenv('DB_PORT') ?: '5432';
    $db   = $cfg['dbname'] ?? getenv('PG_DB') ?: getenv('DB_DATABASE') ?: '';
    $user = $cfg['user'] ?? getenv('PG_USER') ?: getenv('DB_USERNAME') ?: '';
    $pass = $cfg['pass'] ?? getenv('PG_PASS') ?: getenv('DB_PASSWORD') ?: '';

    if (empty($db) || empty($user)) return null;

    $parsed = @parse_url($host);
    if ($parsed !== false && ! empty($parsed['host'])) {
        $host = $parsed['host'];
    } else {
        $host = preg_replace('#^https?://#i', '', $host);
        $host = rtrim($host, '/');
    }

    try {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        if (is_numeric($idOrSku)) {
            $sql = 'SELECT id, sku, "name" as name, description, price, image_url as image, "size", color, stock_quantity FROM products WHERE id = :id LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => (int)$idOrSku]);
        } else {
            $sql = 'SELECT id, sku, "name" as name, description, price, image_url as image, "size", color, stock_quantity FROM products WHERE sku = :sku LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sku' => (string)$idOrSku]);
        }

        $r = $stmt->fetch();
        if (! $r) return null;

        $attrs = [];
        if (isset($r['size']) && $r['size'] !== null) $attrs['size'] = (string)$r['size'];
        if (isset($r['color']) && $r['color'] !== null) $attrs['color'] = (string)$r['color'];

        return [
            'id' => $r['id'],
            'sku' => $r['sku'] ?? null,
            'name' => $r['name'] ?? '',
            'description' => $r['description'] ?? '',
            'price' => isset($r['price']) ? (float)$r['price'] : 0.0,
            'image' => isset($r['image']) && $r['image'] !== null ? basename($r['image']) : '',
            'stock' => isset($r['stock_quantity']) ? (int)$r['stock_quantity'] : (isset($r['stock']) ? (int)$r['stock'] : 0),
            'attributes' => $attrs,
        ];
    } catch (Exception $e) {
        return null;
    }
}

function getCartItems($conn = null): array
{
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    foreach ($cart as $id => $it) {
        $price = isset($it['price']) ? (float)$it['price'] : 0.0;
        $quantity = isset($it['quantity']) ? (int)$it['quantity'] : 1;
        $subtotal = $price * $quantity;
        $items[] = array_merge($it, ['subtotal' => $subtotal]);
    }
    return $items;
}

function getCartTotal($conn = null): float
{
    $items = getCartItems();
    $total = 0.0;
    foreach ($items as $it) {
        $total += $it['subtotal'];
    }
    return $total;
}

function removeFromCart($productId): bool
{
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

function updateCartQuantity($productId, $quantity): bool
{
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = max(1, (int)$quantity);
        return true;
    }
    return false;
}

function clearCart(): void
{
    unset($_SESSION['cart']);
}

function createOrder(array $customer_data, array $cart_items, $cart_total, $conn = null)
{
    $orders = [];
    $path = storage_path_file('orders.json');
    if (file_exists($path)) {
        $orders = json_decode(file_get_contents($path), true) ?: [];
    }

    $orderId = uniqid('ord-');
    $order = [
        'id' => $orderId,
        'customer_name' => $customer_data['name'] ?? '',
        'customer_email' => $customer_data['email'] ?? '',
        'customer_phone' => $customer_data['phone'] ?? '',
        'customer_address' => $customer_data['address'] ?? '',
        'items' => array_map(function ($it) { return [
            'id' => $it['id'] ?? null,
            'name' => $it['name'] ?? '',
            'price' => $it['price'] ?? 0,
            'quantity' => $it['quantity'] ?? 1,
            'subtotal' => isset($it['price'], $it['quantity']) ? $it['price'] * $it['quantity'] : 0,
        ]; }, $cart_items),
        'total_amount' => (float)$cart_total,
        'status' => 'pending',
        'created_at' => date('c'),
    ];

    $orders[] = $order;
    file_put_contents($path, json_encode($orders, JSON_PRETTY_PRINT));

    return $orderId;
}

function sanitize($value)
{
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    $v = trim((string)$value);
    $v = htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $v;
}

function getAllOrders($conn = null): array
{
    $path = storage_path_file('orders.json');
    if (! file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
    }
    $arr = json_decode(file_get_contents($path), true) ?: [];
    return $arr;
}

function getOrderById($conn, $id)
{
    $orders = getAllOrders();
    foreach ($orders as $order) {
        if ($order['id'] === $id) return $order;
    }
    return null;
}

//CSRF helpers
function csrf_token(): string
{
    if (! isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token): bool
{
    if (! isset($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

//Admin helpers
function admin_config_path(): string
{
    return storage_path_file('admin.json');
}

function ensure_admin_config(): void
{
    $path = admin_config_path();
    if (! file_exists($path)) {
        $default = ['password_hash' => password_hash('admin', PASSWORD_DEFAULT)];
        file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT));
    }
}

function admin_login(string $password): bool
{
    ensure_admin_config();
    $cfg = json_decode(file_get_contents(admin_config_path()), true) ?: [];
    $hash = $cfg['password_hash'] ?? '';
    if ($hash && password_verify($password, $hash)) {
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

function is_admin(): bool
{
    return ! empty($_SESSION['is_admin']);
}

function requireAdmin(): void
{
    if (! is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_logout(): void
{
    unset($_SESSION['is_admin']);
}
