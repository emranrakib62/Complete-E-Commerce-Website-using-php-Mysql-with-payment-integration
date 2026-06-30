<?php
require_once __DIR__ . '/../includes/functions.php';
$slug = trim($_GET['slug'] ?? '');
$category = Category::findBySlug($slug);
if (!$category) { http_response_code(404); $pageTitle='Category Not Found'; require __DIR__.'/../includes/header.php'; echo '<main class="container py-5"><div class="empty-state">Category not found.</div></main>'; require __DIR__.'/../includes/footer.php'; exit; }
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$filters = ['active_only'=>true, 'category_slug'=>$slug, 'q'=>trim($_GET['q'] ?? ''), 'min_price'=>$_GET['min_price'] ?? '', 'max_price'=>$_GET['max_price'] ?? ''];
$total = Product::count($filters);
$products = Product::search($filters, $perPage, ($page-1)*$perPage);
$totalPages = max(1, (int)ceil($total/$perPage));
$pageTitle = $category['category_name'];
require __DIR__ . '/../includes/header.php';
?>
<main class="container py-4">
    <div class="mb-4"><h1 class="section-title"><?= e($category['category_name']) ?></h1><p class="text-muted"><?= (int)$total ?> active products in this category.</p></div>
    <form method="get" class="card form-card p-3 mb-4 no-loader">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <div class="row g-3 align-items-end"><div class="col-md-6"><label class="form-label">Search in category</label><input name="q" class="form-control" value="<?= e($filters['q']) ?>"></div><div class="col-md-2"><label class="form-label">Min</label><input type="number" step="0.01" name="min_price" class="form-control" value="<?= e((string)$filters['min_price']) ?>"></div><div class="col-md-2"><label class="form-label">Max</label><input type="number" step="0.01" name="max_price" class="form-control" value="<?= e((string)$filters['max_price']) ?>"></div><div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div></div>
    </form>
    <div class="row g-4"><?php if ($products): foreach ($products as $product) echo render_product_card($product); else: ?><div class="col-12"><div class="empty-state">No products found.</div></div><?php endif; ?></div>
    <?php if ($totalPages > 1): ?><nav class="mt-4"><ul class="pagination justify-content-center"><?php for($i=1;$i<=$totalPages;$i++): $query=$_GET; $query['page']=$i; ?><li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query($query) ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
