<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>">
            <i class="bi bi-bag-check-fill me-1"></i>
            <?= e(setting('site_name', 'ShopEase')) ?>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <!-- Left Menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('index.php') ?>">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= url('products/shop.php') ?>">Shop</a>
                </li>

                <?php $navCategories = Category::all(); ?>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
        Categories
    </a>

    <ul class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
        <?php foreach ($navCategories as $navCat): ?>
            <li>
                <a class="dropdown-item" href="<?= url('products/category.php?slug=' . urlencode($navCat['category_slug'])) ?>">
                    <?= e($navCat['category_name']) ?>
                </a>
            </li>
        <?php endforeach; ?>

        <li><hr class="dropdown-divider"></li>

        <li>
            <a class="dropdown-item fw-bold" href="<?= url('products/shop.php') ?>">
                View All Products
            </a>
        </li>
    </ul>
</li>
            </ul>

            <!-- Search Form -->
            <form class="d-flex me-lg-3" action="<?= url('products/shop.php') ?>" method="get">
                <input class="form-control form-control-sm me-2" name="q" type="search" placeholder="Search products">
                <button class="btn btn-outline-light btn-sm" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <!-- Right Menu -->
            <ul class="navbar-nav ms-auto">

                <?php if (is_customer()): ?>

                    <?php $cartTotal = Cart::totals((int)$_SESSION['customer_id']); ?>

                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= url('cart/cart.php') ?>">
                            <i class="bi bi-cart3"></i> Cart
                            <span class="badge rounded-pill bg-primary">
                                <?= (int)$cartTotal['count'] ?>
                            </span>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= e(current_customer()['name'] ?? 'Account') ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= url('customer/dashboard.php') ?>">Dashboard</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('customer/profile.php') ?>">Profile</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('customer/order_history.php') ?>">Orders</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('customer/wishlist.php') ?>">Wishlist</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('auth/logout.php') ?>">Logout</a>
                            </li>
                        </ul>
                    </li>

                <?php elseif (is_admin()): ?>

                    <!-- Admin link will show only after admin login -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('admin/dashboard.php') ?>">
                            <i class="bi bi-speedometer2"></i> Admin Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('auth/logout.php') ?>">
                            Logout
                        </a>
                    </li>

                <?php else: ?>

                    <!-- Visitor/Customer public view -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('auth/login.php?role=customer') ?>">
                            Login
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-lg-2" href="<?= url('auth/register.php') ?>">
                            Register
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>