<?php
$activePage = $activePage ?? '';
$adminLinks = [
    'dashboard' => ['Dashboard', 'bi-speedometer2', 'admin/dashboard.php'],
    'products' => ['Products', 'bi-box-seam', 'admin/manage_products.php'],
    'add_product' => ['Add Product', 'bi-plus-circle', 'admin/add_product.php'],
    'categories' => ['Categories', 'bi-tags', 'admin/categories.php'],
    'orders' => ['Orders', 'bi-receipt', 'admin/manage_orders.php'],
    'users' => ['Customers', 'bi-people', 'admin/manage_users.php'],
    'reports' => ['Reports', 'bi-bar-chart', 'admin/reports.php'],
    'settings' => ['Settings', 'bi-gear', 'admin/settings.php'],
];
$customerLinks = [
    'dashboard' => ['Dashboard', 'bi-speedometer2', 'customer/dashboard.php'],
    'profile' => ['Profile', 'bi-person', 'customer/profile.php'],
    'orders' => ['Order History', 'bi-bag-check', 'customer/order_history.php'],
    'wishlist' => ['Wishlist', 'bi-heart', 'customer/wishlist.php'],
    'cart' => ['Cart', 'bi-cart3', 'cart/cart.php'],
];
$links = is_admin() ? $adminLinks : $customerLinks;
?>
<div class="list-group sidebar-nav shadow-sm">
    <?php foreach ($links as $key => [$label, $icon, $href]): ?>
        <a class="list-group-item list-group-item-action <?= $activePage === $key ? 'active' : '' ?>" href="<?= url($href) ?>">
            <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>
