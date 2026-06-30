<?php
require_once __DIR__ . '/../includes/functions.php';
$product = Product::findBySlug(trim($_GET['slug'] ?? ''));
if (!$product) { http_response_code(404); $pageTitle='Product Not Found'; require __DIR__.'/../includes/header.php'; echo '<main class="container py-5"><div class="empty-state">Product not found or inactive.</div></main>'; require __DIR__.'/../includes/footer.php'; exit; }
$related = Product::search(['active_only'=>true, 'category_id'=>(int)$product['category_id']], 4, 0);
$pageTitle = $product['product_name'];
require __DIR__ . '/../includes/header.php';
?>
<main class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li><li class="breadcrumb-item"><a href="<?= url('products/shop.php') ?>">Shop</a></li><li class="breadcrumb-item active"><?= e($product['product_name']) ?></li></ol></nav>
    <div class="card form-card p-3 p-lg-4 mb-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-6"><img src="<?= e(product_image_url($product['image'])) ?>" alt="<?= e($product['product_name']) ?>" class="img-fluid rounded-4 w-100 shadow-sm"></div>
            <div class="col-lg-6">
                <span class="badge bg-primary mb-2"><?= e($product['category_name']) ?></span>
                <h1 class="fw-bold"><?= e($product['product_name']) ?></h1>
                <div class="fs-3 fw-bold text-primary mb-2"><?= money((float)($product['discount_price'] ?? $product['price'])) ?> <?php if ($product['discount_price']): ?><small class="text-muted text-decoration-line-through fs-6"><?= money((float)$product['price']) ?></small><?php endif; ?></div>
                <p class="text-muted"><?= nl2br(e($product['description'])) ?></p>
                <div class="mb-3"><strong>Stock:</strong> <span class="<?= (int)$product['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?>"><?= (int)$product['stock_quantity'] ?> available</span></div>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" action="<?= url('cart/cart.php?action=add') ?>" class="d-flex gap-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="number" name="quantity" min="1" max="<?= (int)$product['stock_quantity'] ?>" value="1" class="form-control" style="width:100px">
                        <button class="btn btn-primary" <?= (int)$product['stock_quantity'] <= 0 ? 'disabled' : '' ?>><i class="bi bi-cart-plus me-1"></i>Add to Cart</button>
                    </form>
                    <form method="post" action="<?= url('customer/wishlist.php?action=add') ?>">
                        <?= csrf_input() ?><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><button class="btn btn-outline-danger"><i class="bi bi-heart me-1"></i>Wishlist</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <h3 class="section-title mb-3">Related Products</h3>
    <div class="row g-4"><?php foreach ($related as $item) { if ((int)$item['id'] !== (int)$product['id']) echo render_product_card($item); } ?></div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
