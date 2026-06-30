<?php
require_once __DIR__ . '/../includes/functions.php';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$filters = [
    'active_only' => true,
    'q' => trim($_GET['q'] ?? ''),
    'category_id' => (int)($_GET['category_id'] ?? 0) ?: null,
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
];
$total = Product::count($filters);
$products = Product::search($filters, $perPage, ($page - 1) * $perPage);
$totalPages = max(1, (int)ceil($total / $perPage));
$categories = Category::all();
if (($_GET['ajax'] ?? '') === '1') {
    if ($products) {
        foreach ($products as $product) echo render_product_card($product);
    } else {
        echo '<div class="col-12"><div class="empty-state">No products matched your search.</div></div>';
    }
    exit;
}
$pageTitle = 'Shop';
require __DIR__ . '/../includes/header.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div><h1 class="section-title mb-1">Shop Products</h1><p class="text-muted mb-0">Search, filter and add products to cart.</p></div>
        <span class="badge badge-soft fs-6"><?= (int)$total ?> products found</span>
    </div>
    <form method="get" action="<?= url('products/shop.php') ?>" class="card form-card p-3 mb-4 no-loader">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4"><label class="form-label">Search</label><input data-ajax-product-search name="q" class="form-control" value="<?= e($filters['q']) ?>" placeholder="Product name or description"></div>
            <div class="col-lg-3"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">All Categories</option><?php foreach ($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)$filters['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label">Min Price</label><input type="number" min="0" step="0.01" name="min_price" class="form-control" value="<?= e((string)$filters['min_price']) ?>"></div>
            <div class="col-lg-2"><label class="form-label">Max Price</label><input type="number" min="0" step="0.01" name="max_price" class="form-control" value="<?= e((string)$filters['max_price']) ?>"></div>
            <div class="col-lg-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
        </div>
    </form>
    <div class="row g-4" data-product-results>
        <?php if ($products): foreach ($products as $product) echo render_product_card($product); else: ?>
            <div class="col-12"><div class="empty-state">No products matched your search.</div></div>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4 no-print"><ul class="pagination justify-content-center">
        <?php for ($i=1; $i <= $totalPages; $i++): $query = $_GET; $query['page']=$i; unset($query['ajax']); ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query($query) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
