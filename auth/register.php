<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_customer()) redirect('customer/dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    [$ok, $errors] = Auth::registerCustomer($_POST);
    if ($ok) {
        set_flash('success', 'Registration successful. Please login.');
        redirect('auth/login.php?role=customer');
    }
    foreach ($errors as $error) set_flash('danger', $error);
}
$pageTitle = 'Customer Registration';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <div class="auth-card card form-card">
        <div class="card-body p-4 p-lg-5">
            <h3 class="fw-bold mb-1">Create Customer Account</h3>
            <p class="text-muted mb-4">Register to shop, manage wishlist, and track orders.</p>
            <form method="post" novalidate>
                <?= csrf_input() ?>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>"></div>
                   
                    <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                </div>
                <button class="btn btn-primary w-100 mt-4">Register</button>
            </form>
            <p class="text-center mt-3 mb-0">Already have an account? <a href="<?= url('auth/login.php') ?>">Login</a></p>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
