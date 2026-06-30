<?php
require_once __DIR__ . '/../includes/functions.php';
require_customer();
$customerId = (int)$_SESSION['customer_id'];
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    require_post_csrf();
    try {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($action === 'add') {
            Cart::add($customerId, $productId, (int)($_POST['quantity'] ?? 1));
            set_flash('success', 'Product added to cart.');
        } elseif ($action === 'update') {
            Cart::update($customerId, $productId, (int)($_POST['quantity'] ?? 1));
            set_flash('success', 'Cart updated.');
        } elseif ($action === 'remove') {
            Cart::remove($customerId, $productId);
            set_flash('success', 'Item removed from cart.');
        }
    } catch (Throwable $e) { set_flash('danger', $e->getMessage()); }
    header('Location: ' . safe_back_url('cart/cart.php')); exit;
}
$cart = Cart::totals($customerId);
$pageTitle = 'Shopping Cart';
$activePage = 'cart';
require __DIR__ . '/../includes/header.php';
?>
<main class="container account-shell"><div class="row g-4"><aside class="col-lg-3 no-print"><?php require __DIR__.'/../includes/sidebar.php'; ?></aside><section class="col-lg-9">
    <h1 class="section-title mb-4">Shopping Cart</h1>
    <?php if (!$cart['items']): ?><div class="empty-state"><i class="bi bi-cart-x fs-1 d-block mb-2"></i>Your cart is empty. <a href="<?= url('products/shop.php') ?>">Continue shopping</a>.</div><?php else: ?>
    <div class="card table-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Product</th><th>Price</th><th style="width:160px">Quantity</th><th>Total</th><th></th></tr></thead><tbody>
        <?php foreach ($cart['items'] as $item): $price=(float)($item['discount_price'] ?? $item['price']); ?>
        <tr><td><div class="d-flex align-items-center gap-3"><img class="cart-thumb" src="<?= e(product_image_url($item['image'])) ?>"><div><a class="fw-semibold text-dark text-decoration-none" href="<?= url('products/product_details.php?slug='.urlencode($item['product_slug'])) ?>"><?= e($item['product_name']) ?></a><div class="small text-muted">Stock: <?= (int)$item['stock_quantity'] ?></div></div></div></td><td><?= money($price) ?></td><td><form method="post" action="<?= url('cart/cart.php?action=update') ?>" class="d-flex gap-2 no-loader"><?= csrf_input() ?><input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>"><input type="number" min="1" max="<?= (int)$item['stock_quantity'] ?>" name="quantity" value="<?= (int)$item['quantity'] ?>" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-primary">Update</button></form></td><td><?= money($price*(int)$item['quantity']) ?></td><td><form method="post" action="<?= url('cart/cart.php?action=remove') ?>" class="confirm-form no-loader"><?= csrf_input() ?><input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div></div>
    <div class="card dashboard-card mt-4 p-4 ms-auto" style="max-width:420px"><div class="d-flex justify-content-between"><span>Subtotal</span><strong><?= money($cart['subtotal']) ?></strong></div><hr><a class="btn btn-primary w-100" href="<?= url('cart/checkout.php') ?>">Proceed to Checkout</a></div>
    <?php endif; ?>
</section></div></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
