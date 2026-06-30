<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();
require_post_csrf();

$customerId = (int)$_SESSION['customer_id'];
$orderId = (int)($_POST['order_id'] ?? 0);
$token = (string)($_POST['token'] ?? '');
$transactionId = trim((string)($_POST['transaction_id'] ?? ''));

try {
    if ($orderId <= 0 || !PaymentGateway::verifyToken($orderId, $customerId, $token)) {
        throw new RuntimeException('Invalid payment verification token.');
    }
    PaymentGateway::markPaid($orderId, $customerId, $transactionId ?: null);
    set_flash('success', 'Payment successful. Your order is now processing.');
    redirect('customer/order_history.php?order_id=' . $orderId);
} catch (Throwable $e) {
    set_flash('danger', $e->getMessage());
    redirect('customer/order_history.php');
}
