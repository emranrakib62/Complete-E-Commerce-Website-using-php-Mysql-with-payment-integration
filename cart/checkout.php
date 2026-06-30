<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();

$customer = current_customer();
$customerId = (int)$customer['id'];
$cart = Cart::totals($customerId);
$couponCode = trim($_POST['coupon_code'] ?? $_GET['coupon_code'] ?? '');
[$coupon, $discount, $couponError] = CouponService::validate($couponCode, (float)$cart['subtotal']);
if ($couponError && $couponCode) {
    set_flash('warning', $couponError);
}
$total = max(0, (float)$cart['subtotal'] - $discount);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['place_order'] ?? '') === '1') {
    require_post_csrf();
    try {
        $paymentMethod = $_POST['payment_method'] ?? 'Cash on Delivery';
        $orderId = OrderService::place($customerId, $_POST['billing_address'] ?? '', $paymentMethod, $_POST['coupon_code'] ?? null);

        if ($paymentMethod === 'SSLCommerz') {
            set_flash('info', 'Order created. Complete payment to confirm processing.');
            redirect('payment/sslcommerz_init.php?order_id=' . $orderId . '&token=' . urlencode(PaymentGateway::token($orderId, $customerId)));
        }

        set_flash('success', 'Order placed successfully.');
        redirect('customer/order_history.php?order_id=' . $orderId);
    } catch (Throwable $e) {
        set_flash('danger', $e->getMessage());
    }
}

$pageTitle = 'Checkout';
require __DIR__ . '/../includes/header.php';
?>
<main class="container py-4">
    <h1 class="section-title mb-4">Checkout</h1>
    <?php if (!$cart['items']): ?>
        <div class="empty-state">Your cart is empty. <a href="<?= url('products/shop.php') ?>">Shop now</a>.</div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card form-card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Billing Details</h5>
                        <form method="post">
                            <?= csrf_input() ?>
                            <input type="hidden" name="place_order" value="1">
                            <div class="mb-3">
                                <label class="form-label">Billing Address</label>
                                <textarea name="billing_address" rows="4" class="form-control" required><?= e($_POST['billing_address'] ?? $customer['address']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Cash on Delivery">Cash on Delivery</option>
                                    <option value="SSLCommerz">SSLCommerz Demo Payment Gateway</option>
                                    <option value="Bkash">Manual bKash</option>
                                    <option value="Nagad">Manual Nagad</option>
                                    <option value="Card">Manual Card</option>
                                </select>
                                <div class="form-text">For localhost/XAMPP testing, choose SSLCommerz Demo Payment Gateway and complete the demo payment screen.</div>
                            </div>
                            <input type="hidden" name="coupon_code" value="<?= e($couponCode) ?>">
                            <button class="btn btn-primary btn-lg w-100">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card dashboard-card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Order Summary</h5>
                        <?php foreach ($cart['items'] as $item): $price = (float)($item['discount_price'] ?? $item['price']); ?>
                            <div class="d-flex justify-content-between small mb-2">
                                <span><?= e($item['product_name']) ?> × <?= (int)$item['quantity'] ?></span>
                                <strong><?= money($price * (int)$item['quantity']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <form method="get" class="d-flex gap-2 mb-3 no-loader">
                            <input name="coupon_code" class="form-control" placeholder="Coupon code" value="<?= e($couponCode) ?>">
                            <button class="btn btn-outline-primary">Apply</button>
                        </form>
                        <div class="d-flex justify-content-between"><span>Subtotal</span><strong><?= money($cart['subtotal']) ?></strong></div>
                        <div class="d-flex justify-content-between text-success"><span>Discount <?= $coupon ? '(' . e($coupon['code']) . ')' : '' ?></span><strong>-<?= money($discount) ?></strong></div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5"><span>Total</span><strong><?= money($total) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
