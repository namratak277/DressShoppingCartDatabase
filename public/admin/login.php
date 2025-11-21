<?php
chdir(__DIR__ . '/../../app/Http');
require_once __DIR__ . '/../../app/Http/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (! verify_csrf_token($token)) {
        $error = 'Invalid CSRF token';
    } else {
        $pw = $_POST['password'] ?? '';
        if (admin_login($pw)) {
            header('Location: /admin/products.php');
            exit;
        }
        $error = 'Invalid password';
    }
}

require_once __DIR__ . '/../../app/Http/includes/header.php';
?>
<div style="max-width:720px;margin:2rem auto;background:white;padding:1.5rem;border-radius:8px">
    <h2>Admin Login</h2>
    <?php if ($error): ?><div style="color:red;margin-bottom:1rem"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom:1rem">
            <label>Password:</label>
            <input type="password" name="password" style="width:100%;padding:0.6rem;border:1px solid #ddd;border-radius:4px">
        </div>
        <div style="text-align:right">
            <button class="btn btn-success" type="submit">Login</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../app/Http/includes/footer.php'; ?>
