<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
$featured = Product::featured(8);
$latest = Product::latest(8);
$categories = Category::all();
require __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <section class="hero p-4 p-lg-5 mb-5 position-relative">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark mb-3">Secure Dynamic E-Commerce</span>
                <h1 class="display-5 fw-bold"><?= e(setting('promo_title', 'Mega Sale is Live')) ?></h1>
                <p class="lead mb-4"><?= e(setting('promo_subtitle', 'Order quality products with secure checkout and fast processing.')) ?></p>
                <a class="btn btn-light btn-lg" href="<?= url('products/shop.php') ?>"><i class="bi bi-shop me-1"></i> Shop Now</a>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block"><i class="bi bi-bag-heart promo-icon"></i></div>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="section-title">Categories</h2><a href="<?= url('products/shop.php') ?>" class="btn btn-outline-primary btn-sm">View Shop</a></div>
        <div class="row g-3">
            <?php if ($categories): foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-3"><a class="card dashboard-card text-decoration-none text-dark p-3 h-100" href="<?= url('products/category.php?slug=' . urlencode($cat['category_slug'])) ?>"><div class="d-flex justify-content-between align-items-center"><div><h6 class="mb-1"><?= e($cat['category_name']) ?></h6><small class="text-muted"><?= (int)$cat['product_count'] ?> products</small></div><i class="bi bi-arrow-right-circle fs-4 text-primary"></i></div></a></div>
            <?php endforeach; else: ?>
                <div class="col-12"><div class="empty-state">No categories yet.</div></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="section-title mb-3">Featured Products</h2>
        <div class="row g-4">
            <?php if ($featured): foreach ($featured as $product) echo render_product_card($product); else: ?>
                <div class="col-12"><div class="empty-state"><i class="bi bi-box fs-1 d-block mb-2"></i>No products found. Admin can add products from the dashboard.</div></div>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <h2 class="section-title mb-3">Latest Products</h2>
        <div class="row g-4">
            <?php foreach ($latest as $product) echo render_product_card($product); ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
