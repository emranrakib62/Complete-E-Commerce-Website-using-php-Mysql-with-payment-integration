<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();

$customerId = (int)$_SESSION['customer_id'];
$orderId = (int)($_GET['order_id'] ?? 0);
$token = (string)($_GET['token'] ?? '');

try {
    if ($orderId <= 0 || !PaymentGateway::verifyToken($orderId, $customerId, $token)) {
        throw new RuntimeException('Invalid payment request.');
    }
    $order = PaymentGateway::customerOrder($orderId, $customerId);
    if (($order['payment']['payment_status'] ?? '') === 'Paid') {
        set_flash('success', 'Payment already completed.');
        redirect('customer/order_history.php?order_id=' . $orderId);
    }
    if (in_array(($order['payment']['payment_status'] ?? ''), ['Failed'], true)) {
        PaymentGateway::retryPayment($orderId, $customerId);
        $order = PaymentGateway::customerOrder($orderId, $customerId);
    }
} catch (Throwable $e) {
    set_flash('danger', $e->getMessage());
    redirect('customer/order_history.php');
}

$transactionId = $order['payment']['transaction_id'] ?? ('SSL-' . date('YmdHis') . '-' . $orderId);
$pageTitle = 'SSLCommerz Demo Payment';
require __DIR__ . '/../includes/header.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card form-card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="h3 fw-bold mb-1">SSLCommerz Demo Gateway</h1>
                            <p class="text-muted mb-0">Local sandbox payment screen for order #<?= (int)$order['id'] ?></p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary fs-6">Demo Mode</span>
                    </div>

                    <div class="alert alert-info small">
                        This demo gateway works on localhost without a merchant account. For live payment collection, replace demo mode with your real merchant gateway credentials and public callback URLs.
                    </div>

                    <div class="border rounded-4 p-3 mb-4 bg-light">
                        <div class="d-flex justify-content-between mb-2"><span>Merchant</span><strong><?= e(setting('site_name', 'ShopEase')) ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Transaction ID</span><strong><?= e($transactionId) ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Customer</span><strong><?= e($order['customer_name']) ?></strong></div>
                        <div class="d-flex justify-content-between fs-4"><span>Payable Amount</span><strong><?= money($order['total_amount']) ?></strong></div>
                    </div>

                    <form method="post" action="<?= url('payment/success.php') ?>" class="mb-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="order_id" value="<?= (int)$orderId ?>">
                        <input type="hidden" name="token" value="<?= e($token) ?>">
                        <input type="hidden" name="transaction_id" value="<?= e($transactionId) ?>">
                        <button class="btn btn-success btn-lg w-100"><i class="bi bi-shield-check me-1"></i> Demo Pay Successfully</button>
                    </form>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <form method="post" action="<?= url('payment/fail.php') ?>">
                                <?= csrf_input() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$orderId ?>">
                                <input type="hidden" name="token" value="<?= e($token) ?>">
                                <button class="btn btn-outline-danger w-100">Demo Payment Failed</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="post" action="<?= url('payment/cancel.php') ?>">
                                <?= csrf_input() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$orderId ?>">
                                <input type="hidden" name="token" value="<?= e($token) ?>">
                                <button class="btn btn-outline-secondary w-100">Cancel Payment</button>
                            </form>
                        </div>
                    </div>
                    <a class="btn btn-link w-100 mt-3" href="<?= url('customer/order_history.php?order_id=' . $orderId) ?>">Back to invoice</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
