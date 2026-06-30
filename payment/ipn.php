<?php
require_once __DIR__ . '/../includes/functions.php';

// Local demo IPN endpoint. A real merchant gateway can POST order_id, customer_id,
// token, transaction_id and status=VALID|FAILED|CANCELLED to this endpoint.
$payload = array_merge($_GET, $_POST);
$orderId = (int)($payload['order_id'] ?? 0);
$customerId = (int)($payload['customer_id'] ?? 0);
$token = (string)($payload['token'] ?? '');
$status = strtoupper((string)($payload['status'] ?? ''));
$transactionId = trim((string)($payload['transaction_id'] ?? ''));

header('Content-Type: application/json');
try {
    if ($orderId <= 0 || $customerId <= 0 || !PaymentGateway::verifyToken($orderId, $customerId, $token)) {
        throw new RuntimeException('Invalid IPN request.');
    }
    if ($status === 'VALID' || $status === 'SUCCESS' || $status === 'PAID') {
        PaymentGateway::markPaid($orderId, $customerId, $transactionId ?: null);
        echo json_encode(['status' => 'ok', 'message' => 'Payment marked paid']);
        exit;
    }
    PaymentGateway::markFailed($orderId, $customerId, strtolower($status ?: 'ipn_failed'));
    echo json_encode(['status' => 'ok', 'message' => 'Payment marked failed']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
