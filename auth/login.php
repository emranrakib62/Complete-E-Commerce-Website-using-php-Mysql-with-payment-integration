<?php
require_once __DIR__ . '/../includes/functions.php';
$role = ($_GET['role'] ?? $_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $role = ($_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);
    if (Auth::login($role, $email, $password, $remember)) {
        set_flash('success', 'Login successful.');
        redirect($role === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php');
    }
    set_flash('danger', 'Invalid email or password.');
}
$pageTitle = ucfirst($role) . ' Login';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="auth-card card form-card">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex gap-2 mb-4">
                
                
            </div>
            <h3 class="fw-bold mb-1"><?= ucfirst($role) ?> Login</h3>
            <?php if ($role === 'admin'): ?><p class="small text-muted">Default admin: admin@shop.com / admin123</p><?php endif; ?>
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="role" value="<?= e($role) ?>">
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label" for="remember">Remember login</label></div>
                <button class="btn btn-primary w-100">Login</button>
            </form>
            <?php if ($role === 'customer'): ?><p class="text-center mt-3 mb-0">New here? <a href="<?= url('auth/register.php') ?>">Create account</a></p><?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
