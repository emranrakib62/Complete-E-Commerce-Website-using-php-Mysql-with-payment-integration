<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();
$customerId = (int)$_SESSION['customer_id'];
$orders = OrderService::customerOrders($customerId);
$wishlist = Wishlist::items($customerId);
$cart = Cart::totals($customerId);
$pageTitle = 'Customer Dashboard'; $activePage='dashboard';
require __DIR__ . '/../includes/header.php';
?>
<main class="container account-shell"><div class="row g-4"><aside class="col-lg-3"><?php require __DIR__.'/../includes/sidebar.php'; ?></aside><section class="col-lg-9"><h1 class="section-title mb-4">My Dashboard</h1><div class="row g-3 mb-4"><div class="col-md-4"><div class="card dashboard-card p-3"><div class="d-flex gap-3 align-items-center"><div class="icon"><i class="bi bi-bag"></i></div><div><small class="text-muted">Orders</small><h3 class="mb-0"><?= count($orders) ?></h3></div></div></div></div><div class="col-md-4"><div class="card dashboard-card p-3"><div class="d-flex gap-3 align-items-center"><div class="icon"><i class="bi bi-heart"></i></div><div><small class="text-muted">Wishlist</small><h3 class="mb-0"><?= count($wishlist) ?></h3></div></div></div></div><div class="col-md-4"><div class="card dashboard-card p-3"><div class="d-flex gap-3 align-items-center"><div class="icon"><i class="bi bi-cart"></i></div><div><small class="text-muted">Cart Items</small><h3 class="mb-0"><?= (int)$cart['count'] ?></h3></div></div></div></div></div><div class="card table-card"><div class="card-header bg-white fw-bold">Recent Orders</div><div class="table-responsive"><table class="table mb-0 align-middle"><thead class="table-light"><tr><th>Order</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody><?php foreach (array_slice($orders,0,6) as $order): ?><tr><td>#<?= (int)$order['id'] ?></td><td><?= money($order['total_amount']) ?></td><td><?= order_status_badge($order['order_status']) ?></td><td><?= e(date('d M Y', strtotime($order['order_date']))) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('customer/order_history.php?order_id='.(int)$order['id']) ?>">View</a></td></tr><?php endforeach; if(!$orders): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?></tbody></table></div></div></section></div></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
