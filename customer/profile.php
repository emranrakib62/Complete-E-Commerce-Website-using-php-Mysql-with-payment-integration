<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();
$customer = current_customer();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { require_post_csrf(); try { CustomerService::updateProfile((int)$customer['id'], $_POST); set_flash('success','Profile updated.'); redirect('customer/profile.php'); } catch(Throwable $e){ set_flash('danger',$e->getMessage()); } }
$pageTitle='Profile'; $activePage='profile'; require __DIR__ . '/../includes/header.php';
?>
<main class="container account-shell"><div class="row g-4"><aside class="col-lg-3"><?php require __DIR__.'/../includes/sidebar.php'; ?></aside><section class="col-lg-9"><h1 class="section-title mb-4">Profile Management</h1><div class="card form-card"><div class="card-body p-4"><form method="post"><?= csrf_input() ?><div class="row g-3"><div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= e($customer['name']) ?>" required></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value="<?= e($customer['email']) ?>" disabled></div><div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($customer['phone']) ?>" required></div><div class="col-md-6"><label class="form-label">New Password <small class="text-muted">optional</small></label><input type="password" name="password" class="form-control"></div><div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="4" required><?= e($customer['address']) ?></textarea></div></div><button class="btn btn-primary mt-4">Save Profile</button></form></div></div></section></div></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
