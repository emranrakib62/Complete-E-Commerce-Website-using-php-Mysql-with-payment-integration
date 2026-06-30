<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();

$customerId = (int)$_SESSION['customer_id'];
$orderId = (int)($_GET['order_id'] ?? 0);
$order = $orderId ? OrderService::find($orderId, $customerId) : null;
$orders = OrderService::customerOrders($customerId);
$pageTitle = 'Order History';
$activePage = 'orders';
require __DIR__ . '/../includes/header.php';
?>
<main class="container account-shell">
    <div class="row g-4">
        <aside class="col-lg-3 no-print"><?php require __DIR__ . '/../includes/sidebar.php'; ?></aside>
        <section class="col-lg-9">
            <h1 class="section-title mb-4 no-print">Order History</h1>
            <?php if ($order): ?>
                <?php $paymentStatus = $order['payment']['payment_status'] ?? 'Pending'; ?>
                <div class="invoice-box shadow-sm print-area">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h3 class="fw-bold">Invoice #<?= (int)$order['id'] ?></h3>
                            <p class="text-muted mb-0">Date: <?= e(date('d M Y h:i A', strtotime($order['order_date']))) ?></p>
                        </div>
                        <div class="text-end">
                            <h5><?= e(setting('site_name', 'ShopEase')) ?></h5>
                            <p class="small mb-0"><?= e(setting('site_email')) ?><br><?= e(setting('site_phone')) ?></p>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-7">
                            <strong>Bill To</strong>
                            <p class="mb-0"><?= e($order['customer_name']) ?><br><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']) ?><br><?= nl2br(e($order['billing_address'])) ?></p>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <strong>Status</strong>
                            <div><?= order_status_badge($order['order_status']) ?></div>
                            <p class="mt-2 mb-0">
                                Payment: <?= e($paymentStatus) ?><br>
                                Method: <?= e($order['payment_method']) ?><br>
                                <?php if (!empty($order['payment']['transaction_id'])): ?>Transaction: <?= e($order['payment']['transaction_id']) ?><?php endif; ?>
                            </p>
                            <?php if ($order['payment_method'] === 'SSLCommerz' && in_array($paymentStatus, ['Pending', 'Failed'], true) && $order['order_status'] !== 'Cancelled'): ?>
                                <a class="btn btn-success btn-sm mt-2 no-print" href="<?= PaymentGateway::gatewayUrl((int)$order['id'], $customerId) ?>"><i class="bi bi-credit-card me-1"></i>Pay Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light"><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php $sub = 0; foreach ($order['items'] as $item): $line = (float)$item['price'] * (int)$item['quantity']; $sub += $line; ?>
                                    <tr><td><?= e($item['product_name']) ?></td><td><?= (int)$item['quantity'] ?></td><td><?= money($item['price']) ?></td><td><?= money($line) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ms-auto" style="max-width:340px">
                        <div class="d-flex justify-content-between"><span>Subtotal</span><strong><?= money($sub) ?></strong></div>
                        <div class="d-flex justify-content-between text-success"><span>Discount</span><strong>-<?= money($order['discount_amount']) ?></strong></div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5"><span>Total</span><strong><?= money($order['total_amount']) ?></strong></div>
                    </div>
                    <button class="btn btn-primary mt-4 no-print" data-print><i class="bi bi-printer me-1"></i>Print Invoice</button>
                    <a class="btn btn-outline-secondary mt-4 no-print" href="<?= url('customer/order_history.php') ?>">Back to Orders</a>
                </div>
            <?php else: ?>
                <div class="card table-card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Order</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($orders as $item): ?>
                                    <tr>
                                        <td>#<?= (int)$item['id'] ?></td>
                                        <td><?= money($item['total_amount']) ?></td>
                                        <td><?= e($item['payment_method']) ?></td>
                                        <td><?= order_status_badge($item['order_status']) ?></td>
                                        <td><?= e(date('d M Y', strtotime($item['order_date']))) ?></td>
                                        <td><a class="btn btn-sm btn-outline-primary" href="?order_id=<?= (int)$item['id'] ?>">View Invoice</a></td>
                                    </tr>
                                <?php endforeach; if (!$orders): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
