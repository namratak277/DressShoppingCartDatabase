<?php
require_once __DIR__ . '/../../includes/header.php';

requireAdmin();

$editing = false;
$product = null;
$error = null;

if (isset($_GET['id'])) {
    $editing = true;
    $product = getProductById($_GET['id']);
    if (! $product) {
        $error = 'Product not found';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (! verify_csrf_token($token)) {
        $error = 'Invalid CSRF token';
    } else {
            $data = [
                'sku' => sanitize($_POST['sku'] ?? ''),
                'name' => sanitize($_POST['name'] ?? ''),
                'description' => sanitize($_POST['description'] ?? ''),
                'price' => (float)($_POST['price'] ?? 0),
                'image' => sanitize($_POST['image'] ?? ''),
                'stock' => (int)($_POST['stock'] ?? 0),
                'attributes' => [],
            ];

            if (! empty($_POST['attributes'])) {
            $attrRaw = array_map('trim', explode(',', $_POST['attributes']));
            foreach ($attrRaw as $a) {
                if (stripos($a, ':') !== false) {
                    list($k, $v) = array_map('trim', explode(':', $a, 2));
                    if ($k !== '') $data['attributes'][$k] = $v;
                }
            }
        }

        if (! $editing) {
            // image upload: doesn't work
            if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['image_file']['tmp_name'];
                $orig = $_FILES['image_file']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif'];
                if (in_array($ext, $allowed, true)) {
                    $root = dirname(__DIR__, 4);
                    $dstDir = $root . '/public/images';
                    if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);
                    $safe = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                    $newName = $safe . '-' . time() . '.' . $ext;
                    $dst = $dstDir . '/' . $newName;
                    if (move_uploaded_file($tmp, $dst)) {
                        $data['image'] = $newName;
                    }
                }
            }

            $newId = addProduct($data);

            // new products sync to postgres
            if (function_exists('getProductsFromPostgres')) {
                try {
                    syncProductToPostgres(array_merge($data, ['id' => $newId]));
                } catch (Throwable $e) {                }
            }

            header('Location: products.php');
            exit;
        } else {
            $id = $_POST['id'] ?? null;

            // image upload if it doesn't work
            if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['image_file']['tmp_name'];
                $orig = $_FILES['image_file']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif'];
                if (in_array($ext, $allowed, true)) {
                    $root = dirname(__DIR__, 4);
                    $dstDir = $root . '/public/images';
                    if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);
                    $safe = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($orig, PATHINFO_FILENAME));
                    $newName = $safe . '-' . time() . '.' . $ext;
                    $dst = $dstDir . '/' . $newName;
                    if (move_uploaded_file($tmp, $dst)) {
                        $data['image'] = $newName;
                    }
                }
            }

            if ($id && updateProductById($id, $data)) {
                // updated products sync to postgres
                if (function_exists('getProductsFromPostgres')) {
                    try {
                        $syncData = $data;
                        $syncData['id'] = $id;
                        syncProductToPostgres($syncData);
                    } catch (Throwable $e) {
                        // ignore DB errors
                    }
                }
                header('Location: products.php');
                exit;
            } else {
                $error = 'Failed to save product';
            }
        }
    }
}

//insert product to postgres database
function syncProductToPostgres(array $data): bool
{
    $host = getenv('PG_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('PG_PORT') ?: getenv('DB_PORT') ?: '5432';
    $db   = getenv('PG_DB') ?: getenv('DB_DATABASE') ?: '';
    $user = getenv('PG_USER') ?: getenv('DB_USERNAME') ?: '';
    $pass = getenv('PG_PASS') ?: getenv('DB_PASSWORD') ?: '';

    if (empty($db) || empty($user)) return false;

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

        $sku = $data['sku'] ?? null;
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $price = isset($data['price']) ? (float)$data['price'] : 0.0;
        $image = $data['image'] ?? null; // store filename or path
        $stock = isset($data['stock']) ? (int)$data['stock'] : 0;

        if ($sku) {
            // update SKU
            $updateSql = 'UPDATE products SET "name" = :name, description = :description, price = :price, image_url = :image, stock_quantity = :stock WHERE sku = :sku';
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([':name'=>$name, ':description'=>$description, ':price'=>$price, ':image'=>$image, ':stock'=>$stock, ':sku'=>$sku]);
            if ($stmt->rowCount() > 0) return true;

            // Insert 
            $insertSql = 'INSERT INTO products (sku, "name", description, price, image_url, stock_quantity) VALUES (:sku, :name, :description, :price, :image, :stock)';
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([':sku'=>$sku, ':name'=>$name, ':description'=>$description, ':price'=>$price, ':image'=>$image, ':stock'=>$stock]);
            return true;
        } elseif (isset($data['id'])) {
            // If SKU not present, try update by id
            $id = $data['id'];
            $updateSql = 'UPDATE products SET sku = :sku, "name" = :name, description = :description, price = :price, image_url = :image, stock_quantity = :stock WHERE id = :id';
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([':sku'=>$sku, ':name'=>$name, ':description'=>$description, ':price'=>$price, ':image'=>$image, ':stock'=>$stock, ':id'=>$id]);
            return $stmt->rowCount() > 0;
        }
    } catch (Exception $e) {
    }
    return false;
}
?>

<style>
.form-row { max-width:720px; margin:0 auto; background:white; padding:1.5rem; border-radius:8px }
.form-row label{display:block;margin-bottom:0.3rem;font-weight:bold}
.form-row input, .form-row textarea{width:100%;padding:0.6rem;border:1px solid #ddd;border-radius:4px}
.form-actions{display:flex;gap:0.5rem;justify-content:flex-end;margin-top:1rem}
</style>

<div class="form-row">
    <h2><?php echo $editing ? 'Edit Product' : 'Add Product'; ?></h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
        <?php endif; ?>

        <label>SKU</label>
        <input name="sku" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">

        <label>Name</label>
        <input name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">

        <label>Price (e.g. 49.99)</label>
        <input name="price" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>">

        <label>Stock</label>
        <input name="stock" value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>">

        <label>Image filename (or upload file)</label>
        <input name="image" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
        <div style="margin-top:0.5rem">
            <input type="file" name="image_file" accept="image/*">
        </div>

        <label>Description</label>
        <textarea name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>

        <label>Attributes (comma separated key:value, e.g. size:S,color:blue)</label>
        <input name="attributes" value="<?php echo isset($product['attributes']) ? implode(',', array_map(function($k,$v){return "$k:$v";}, array_keys($product['attributes'] ?? []), array_values($product['attributes'] ?? []))) : ''; ?>">

        <div class="form-actions">
            <a href="products.php" class="btn">Cancel</a>
            <button class="btn btn-success" type="submit"><?php echo $editing ? 'Save' : 'Create'; ?></button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
