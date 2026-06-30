<?php
declare(strict_types=1);

require_once __DIR__ . '/db_connect.php';

const APP_SECRET_KEY = 'CHANGE_THIS_TO_A_LONG_RANDOM_64_CHARACTER_SECRET_KEY_BEFORE_PRODUCTION';
const PRODUCT_UPLOAD_DIR = __DIR__ . '/../assets/uploads/products/';
const PRODUCT_UPLOAD_WEB = 'assets/uploads/products/';

function app_base_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $segments = array_values(array_filter(explode('/', trim($dir, '/'))));
    $sectionDirs = ['admin', 'customer', 'cart', 'products', 'auth'];
    if ($segments && in_array(end($segments), $sectionDirs, true)) {
        array_pop($segments);
    }
    return $segments ? '/' . implode('/', $segments) : '';
}

function url(string $path = ''): string
{
    return app_base_url() . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float|string|null $amount): string
{
    return setting('currency_symbol', '৳') . number_format((float)$amount, 2);
}

function setting(string $key, string $default = ''): string
{
    global $pdo;
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function refresh_settings_cache(): void
{
    // Static cache is per request; this function exists for readability after updates.
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function safe_back_url(string $defaultPath): string
{
    $fallback = url($defaultPath);
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref === '') return $fallback;
    $refHost = parse_url($ref, PHP_URL_HOST);
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($refHost === null || $refHost === '' || hash_equals($currentHost, $refHost)) {
        return $ref;
    }
    return $fallback;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid CSRF token. Please refresh and try again.');
    }
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?: '';
    $text = trim($text, '-');
    return $text !== '' ? $text : bin2hex(random_bytes(4));
}

function unique_slug(PDO $pdo, string $table, string $column, string $base, ?int $ignoreId = null): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE {$column} = ?" . ($ignoreId ? ' AND id <> ?' : '') . ' LIMIT 1';
        $params = $ignoreId ? [$candidate, $ignoreId] : [$candidate];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i++;
    }
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin' && !empty($_SESSION['admin_id']);
}

function is_customer(): bool
{
    return ($_SESSION['role'] ?? '') === 'customer' && !empty($_SESSION['customer_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        set_flash('warning', 'Please login as administrator.');
        redirect('auth/login.php?role=admin');
    }
}

function require_customer(): void
{
    if (!is_customer()) {
        set_flash('warning', 'Please login to continue.');
        redirect('auth/login.php?role=customer');
    }
}

function current_admin(): ?array
{
    global $pdo;
    if (!is_admin()) return null;
    static $admin = null;
    if ($admin === null) {
        $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

function current_customer(): ?array
{
    global $pdo;
    if (!is_customer()) return null;
    static $customer = null;
    if ($customer === null) {
        $stmt = $pdo->prepare('SELECT id, name, email, phone, address, created_at FROM customers WHERE id = ?');
        $stmt->execute([$_SESSION['customer_id']]);
        $customer = $stmt->fetch() ?: null;
    }
    return $customer;
}

function remember_cookie_name(): string
{
    return 'remember_ecommerce_user';
}

function sign_remember_value(string $role, int $id, int $expires): string
{
    $payload = $role . '|' . $id . '|' . $expires;
    return $payload . '|' . hash_hmac('sha256', $payload, APP_SECRET_KEY);
}

function set_remember_cookie(string $role, int $id): void
{
    $expires = time() + (86400 * 30);
    setcookie(remember_cookie_name(), sign_remember_value($role, $id, $expires), [
        'expires' => $expires,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(): void
{
    setcookie(remember_cookie_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function auto_login_from_cookie(): void
{
    global $pdo;
    if (is_admin() || is_customer() || empty($_COOKIE[remember_cookie_name()])) return;
    $parts = explode('|', $_COOKIE[remember_cookie_name()]);
    if (count($parts) !== 4) return;
    [$role, $id, $expires, $signature] = $parts;
    if (!ctype_digit($id) || !ctype_digit($expires) || (int)$expires < time()) return;
    $payload = $role . '|' . $id . '|' . $expires;
    $expected = hash_hmac('sha256', $payload, APP_SECRET_KEY);
    if (!hash_equals($expected, $signature)) return;
    if ($role === 'admin') {
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE id = ?');
        $stmt->execute([(int)$id]);
        if ($stmt->fetch()) {
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_id'] = (int)$id;
        }
    } elseif ($role === 'customer') {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
        $stmt->execute([(int)$id]);
        if ($stmt->fetch()) {
            $_SESSION['role'] = 'customer';
            $_SESSION['customer_id'] = (int)$id;
        }
    }
}

auto_login_from_cookie();

function log_activity(string $role, ?int $actorId, string $type, string $message): void
{
    global $pdo;
    try {
        $stmt = $pdo->prepare('INSERT INTO activity_logs (actor_role, actor_id, action_type, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$role, $actorId, $type, mb_substr($message, 0, 255)]);
    } catch (Throwable $e) {
        // Logging must never break the purchase/admin flow.
    }
}

function upload_product_image(array $file, ?string $oldImage = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $oldImage;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Image size must be 2MB or less.');
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }
    if (!is_dir(PRODUCT_UPLOAD_DIR)) {
        mkdir(PRODUCT_UPLOAD_DIR, 0755, true);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = PRODUCT_UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded image.');
    }
    if ($oldImage && is_file(PRODUCT_UPLOAD_DIR . $oldImage)) {
        @unlink(PRODUCT_UPLOAD_DIR . $oldImage);
    }
    return $filename;
}

function product_image_url(?string $image): string
{
    if ($image && is_file(PRODUCT_UPLOAD_DIR . $image)) {
        return url(PRODUCT_UPLOAD_WEB . $image);
    }
    return 'https://placehold.co/600x450?text=Product';
}

final class Auth
{
    public static function registerCustomer(array $data): array
    {
        global $pdo;
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $password = (string)($data['password'] ?? '');
        $confirm = (string)($data['confirm_password'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2) $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (!preg_match('/^[0-9+\-\s]{7,30}$/', $phone)) $errors[] = 'Valid phone number is required.';
        if (mb_strlen($address) < 5) $errors[] = 'Address is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email already exists.';
        if ($errors) return [false, $errors];

        $stmt = $pdo->prepare('INSERT INTO customers (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $address, password_hash($password, PASSWORD_DEFAULT)]);
        log_activity('customer', (int)$pdo->lastInsertId(), 'registration', 'New customer registered: ' . $email);
        return [true, []];
    }

    public static function login(string $role, string $email, string $password, bool $remember = false): bool
    {
        global $pdo;
        $email = strtolower(trim($email));
        $table = $role === 'admin' ? 'admins' : 'customers';
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['role'] = $role;
        if ($role === 'admin') {
            $_SESSION['admin_id'] = (int)$user['id'];
            unset($_SESSION['customer_id']);
        } else {
            $_SESSION['customer_id'] = (int)$user['id'];
            unset($_SESSION['admin_id']);
        }
        if ($remember) set_remember_cookie($role, (int)$user['id']);
        log_activity($role, (int)$user['id'], 'login', ucfirst($role) . ' logged in: ' . $email);
        return true;
    }

    public static function logout(): void
    {
        $role = $_SESSION['role'] ?? 'system';
        $id = $_SESSION['admin_id'] ?? $_SESSION['customer_id'] ?? null;
        log_activity($role, $id ? (int)$id : null, 'logout', ucfirst($role) . ' logged out.');
        $_SESSION = [];
        clear_remember_cookie();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
}

final class Category
{
    public static function all(): array
    {
        global $pdo;
        return $pdo->query('SELECT c.*, COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.category_name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name): void
    {
        global $pdo;
        $name = trim($name);
        if ($name === '') throw new InvalidArgumentException('Category name is required.');
        $slug = unique_slug($pdo, 'categories', 'category_slug', $name);
        $stmt = $pdo->prepare('INSERT INTO categories (category_name, category_slug) VALUES (?, ?)');
        $stmt->execute([$name, $slug]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'category_create', 'Created category: ' . $name);
    }

    public static function update(int $id, string $name): void
    {
        global $pdo;
        $name = trim($name);
        if ($name === '') throw new InvalidArgumentException('Category name is required.');
        $slug = unique_slug($pdo, 'categories', 'category_slug', $name, $id);
        $stmt = $pdo->prepare('UPDATE categories SET category_name = ?, category_slug = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $id]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'category_update', 'Updated category: ' . $name);
    }

    public static function delete(int $id): void
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'category_delete', 'Deleted category ID: ' . $id);
    }
}

final class Product
{
    public static function create(array $data, array $imageFile): void
    {
        global $pdo;
        $name = trim($data['product_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 0);
        $price = (float)($data['price'] ?? 0);
        $discount = ($data['discount_price'] ?? '') !== '' ? (float)$data['discount_price'] : null;
        $stock = max(0, (int)($data['stock_quantity'] ?? 0));
        $status = in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active';
        if ($name === '' || $description === '' || $categoryId <= 0 || $price < 0) {
            throw new InvalidArgumentException('Please provide valid product details.');
        }
        if ($discount !== null && $discount > $price) {
            throw new InvalidArgumentException('Discount price cannot be greater than regular price.');
        }
        $image = upload_product_image($imageFile);
        $slug = unique_slug($pdo, 'products', 'product_slug', $name);
        $stmt = $pdo->prepare('INSERT INTO products (category_id, product_name, product_slug, description, price, discount_price, stock_quantity, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$categoryId, $name, $slug, $description, $price, $discount, $stock, $image, $status]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'product_create', 'Created product: ' . $name);
    }

    public static function update(int $id, array $data, array $imageFile): void
    {
        global $pdo;
        $old = self::find($id);
        if (!$old) throw new RuntimeException('Product not found.');
        $name = trim($data['product_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 0);
        $price = (float)($data['price'] ?? 0);
        $discount = ($data['discount_price'] ?? '') !== '' ? (float)$data['discount_price'] : null;
        $stock = max(0, (int)($data['stock_quantity'] ?? 0));
        $status = in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active';
        if ($name === '' || $description === '' || $categoryId <= 0 || $price < 0) {
            throw new InvalidArgumentException('Please provide valid product details.');
        }
        if ($discount !== null && $discount > $price) {
            throw new InvalidArgumentException('Discount price cannot be greater than regular price.');
        }
        $image = upload_product_image($imageFile, $old['image']);
        $slug = unique_slug($pdo, 'products', 'product_slug', $name, $id);
        $stmt = $pdo->prepare('UPDATE products SET category_id=?, product_name=?, product_slug=?, description=?, price=?, discount_price=?, stock_quantity=?, image=?, status=? WHERE id=?');
        $stmt->execute([$categoryId, $name, $slug, $description, $price, $discount, $stock, $image, $status, $id]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'product_update', 'Updated product: ' . $name);
    }

    public static function delete(int $id): void
    {
        global $pdo;
        $product = self::find($id);
        if ($product && $product['image'] && is_file(PRODUCT_UPLOAD_DIR . $product['image'])) {
            @unlink(PRODUCT_UPLOAD_DIR . $product['image']);
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'product_delete', 'Deleted product ID: ' . $id);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.*, c.category_name, c.category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.*, c.category_name, c.category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.product_slug = ? AND p.status = "active"');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function search(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        global $pdo;
        [$where, $params] = self::buildWhere($filters);
        $sql = 'SELECT p.*, c.category_name, c.category_slug FROM products p JOIN categories c ON c.id = p.category_id ' . $where . ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $value) $stmt->bindValue($i++, $value);
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function count(array $filters = []): int
    {
        global $pdo;
        [$where, $params] = self::buildWhere($filters);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id ' . $where);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private static function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];
        if (!empty($filters['active_only'])) {
            $conditions[] = 'p.status = ?';
            $params[] = 'active';
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(p.product_name LIKE ? OR p.description LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 'p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['category_slug'])) {
            $conditions[] = 'c.category_slug = ?';
            $params[] = $filters['category_slug'];
        }
        if (($filters['min_price'] ?? '') !== '') {
            $conditions[] = 'COALESCE(p.discount_price, p.price) >= ?';
            $params[] = (float)$filters['min_price'];
        }
        if (($filters['max_price'] ?? '') !== '') {
            $conditions[] = 'COALESCE(p.discount_price, p.price) <= ?';
            $params[] = (float)$filters['max_price'];
        }
        if (($filters['status'] ?? '') !== '') {
            $conditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }

    public static function latest(int $limit = 8): array
    {
        return self::search(['active_only' => true], $limit, 0);
    }

    public static function featured(int $limit = 8): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.*, c.category_name, c.category_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE p.status="active" ORDER BY (p.discount_price IS NOT NULL) DESC, p.created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function lowStock(int $threshold = 5): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.*, c.category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.stock_quantity <= ? ORDER BY p.stock_quantity ASC');
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }
}

final class Cart
{
    public static function add(int $customerId, int $productId, int $qty = 1): void
    {
        global $pdo;
        $product = Product::find($productId);
        if (!$product || $product['status'] !== 'active') throw new RuntimeException('Product is not available.');
        if ((int)$product['stock_quantity'] <= 0) throw new RuntimeException('Product is out of stock.');
        $qty = max(1, min($qty, (int)$product['stock_quantity']));
        $stmt = $pdo->prepare('INSERT INTO cart_items (customer_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)');
        $stmt->execute([$customerId, $productId, $qty, (int)$product['stock_quantity']]);
    }

    public static function update(int $customerId, int $productId, int $qty): void
    {
        global $pdo;
        if ($qty <= 0) {
            self::remove($customerId, $productId);
            return;
        }
        $product = Product::find($productId);
        if (!$product) throw new RuntimeException('Product not found.');
        $qty = min($qty, (int)$product['stock_quantity']);
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE customer_id = ? AND product_id = ?');
        $stmt->execute([$qty, $customerId, $productId]);
    }

    public static function remove(int $customerId, int $productId): void
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE customer_id = ? AND product_id = ?');
        $stmt->execute([$customerId, $productId]);
    }

    public static function clear(int $customerId): void
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE customer_id = ?');
        $stmt->execute([$customerId]);
    }

    public static function items(int $customerId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT ci.*, p.product_name, p.product_slug, p.price, p.discount_price, p.stock_quantity, p.image, p.status FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.customer_id = ? ORDER BY ci.id DESC');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function totals(int $customerId): array
    {
        $items = self::items($customerId);
        $subtotal = 0.0;
        foreach ($items as $item) {
            $price = (float)($item['discount_price'] ?? $item['price']);
            $subtotal += $price * (int)$item['quantity'];
        }
        return ['items' => $items, 'subtotal' => $subtotal, 'count' => count($items)];
    }
}

final class Wishlist
{
    public static function add(int $customerId, int $productId): void
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (customer_id, product_id) VALUES (?, ?)');
        $stmt->execute([$customerId, $productId]);
    }

    public static function remove(int $customerId, int $productId): void
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?');
        $stmt->execute([$customerId, $productId]);
    }

    public static function items(int $customerId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT w.*, p.product_name, p.product_slug, p.price, p.discount_price, p.stock_quantity, p.image, p.status FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.customer_id = ? ORDER BY w.id DESC');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
}

final class CouponService
{
    public static function validate(?string $code, float $subtotal): array
    {
        global $pdo;
        $code = strtoupper(trim((string)$code));
        if ($code === '') return [null, 0.0, null];
        $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ? AND status = "active" LIMIT 1');
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();
        if (!$coupon) return [null, 0.0, 'Invalid coupon code.'];
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < strtotime(date('Y-m-d'))) return [null, 0.0, 'Coupon has expired.'];
        if ($coupon['usage_limit'] !== null && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) return [null, 0.0, 'Coupon usage limit reached.'];
        if ($subtotal < (float)$coupon['minimum_order']) return [null, 0.0, 'Minimum order for this coupon is ' . money((float)$coupon['minimum_order']) . '.'];
        $discount = $coupon['discount_type'] === 'percent' ? ($subtotal * (float)$coupon['discount_value'] / 100) : (float)$coupon['discount_value'];
        $discount = min($discount, $subtotal);
        return [$coupon, $discount, null];
    }

    public static function all(): array
    {
        global $pdo;
        return $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
    }

    public static function save(array $data): void
    {
        global $pdo;
        $id = (int)($data['id'] ?? 0);
        $code = strtoupper(trim($data['code'] ?? ''));
        $type = in_array($data['discount_type'] ?? 'fixed', ['fixed', 'percent'], true) ? $data['discount_type'] : 'fixed';
        $value = (float)($data['discount_value'] ?? 0);
        $minimum = (float)($data['minimum_order'] ?? 0);
        $limit = ($data['usage_limit'] ?? '') !== '' ? (int)$data['usage_limit'] : null;
        $expires = ($data['expires_at'] ?? '') !== '' ? $data['expires_at'] : null;
        $status = in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active';
        if (!preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) throw new InvalidArgumentException('Coupon code must be 3-50 uppercase letters/numbers.');
        if ($value <= 0) throw new InvalidArgumentException('Discount value is required.');
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE coupons SET code=?, discount_type=?, discount_value=?, minimum_order=?, usage_limit=?, expires_at=?, status=? WHERE id=?');
            $stmt->execute([$code, $type, $value, $minimum, $limit, $expires, $status, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO coupons (code, discount_type, discount_value, minimum_order, usage_limit, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$code, $type, $value, $minimum, $limit, $expires, $status]);
        }
    }

    public static function delete(int $id): void
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = ?');
        $stmt->execute([$id]);
    }
}

final class OrderService
{
    public static function place(int $customerId, string $billingAddress, string $paymentMethod, ?string $couponCode): int
    {
        global $pdo;
        $cart = Cart::totals($customerId);
        if (!$cart['items']) throw new RuntimeException('Your cart is empty.');
        $billingAddress = trim($billingAddress);
        if (mb_strlen($billingAddress) < 5) throw new RuntimeException('Billing address is required.');
        $validMethods = ['Cash on Delivery', 'SSLCommerz', 'Bkash', 'Nagad', 'Card'];
        if (!in_array($paymentMethod, $validMethods, true)) $paymentMethod = 'Cash on Delivery';
        [$coupon, $discount, $couponError] = CouponService::validate($couponCode, (float)$cart['subtotal']);
        if ($couponError) throw new RuntimeException($couponError);
        $total = max(0, (float)$cart['subtotal'] - $discount);
        $pdo->beginTransaction();
        try {
            foreach ($cart['items'] as $item) {
                $stockStmt = $pdo->prepare('SELECT stock_quantity, status FROM products WHERE id = ? FOR UPDATE');
                $stockStmt->execute([(int)$item['product_id']]);
                $stock = $stockStmt->fetch();
                if (!$stock || $stock['status'] !== 'active' || (int)$stock['stock_quantity'] < (int)$item['quantity']) {
                    throw new RuntimeException('Insufficient stock for ' . $item['product_name']);
                }
            }
            $stmt = $pdo->prepare('INSERT INTO orders (customer_id, total_amount, payment_method, billing_address, coupon_code, discount_amount) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$customerId, $total, $paymentMethod, $billingAddress, $coupon['code'] ?? null, $discount]);
            $orderId = (int)$pdo->lastInsertId();
            foreach ($cart['items'] as $item) {
                $price = (float)($item['discount_price'] ?? $item['price']);
                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                $stmt->execute([$orderId, (int)$item['product_id'], (int)$item['quantity'], $price]);
                $stmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?');
                $stmt->execute([(int)$item['quantity'], (int)$item['product_id']]);
            }
            $transaction = strtoupper(substr($paymentMethod, 0, 3)) . '-' . date('YmdHis') . '-' . $orderId;
            $paymentStatus = in_array($paymentMethod, ['Cash on Delivery', 'SSLCommerz'], true) ? 'Pending' : 'Paid';
            $stmt = $pdo->prepare('INSERT INTO payments (order_id, transaction_id, amount, payment_status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $transaction, $total, $paymentStatus]);
            if ($coupon) {
                $stmt = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?');
                $stmt->execute([(int)$coupon['id']]);
            }
            Cart::clear($customerId);
            log_activity('customer', $customerId, 'order_place', 'Placed order #' . $orderId);
            $pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function customerOrders(int $customerId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function all(?string $status = null, ?string $q = null): array
    {
        global $pdo;
        $where = [];
        $params = [];
        if ($status) { $where[] = 'o.order_status = ?'; $params[] = $status; }
        if ($q) { $where[] = '(c.name LIKE ? OR c.email LIKE ? OR o.id = ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = ctype_digit($q) ? (int)$q : 0; }
        $sql = 'SELECT o.*, c.name customer_name, c.email customer_email, p.payment_status, p.transaction_id FROM orders o JOIN customers c ON c.id=o.customer_id LEFT JOIN payments p ON p.order_id=o.id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.order_date DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $orderId, ?int $customerId = null): ?array
    {
        global $pdo;
        $sql = 'SELECT o.*, c.name customer_name, c.email customer_email, c.phone customer_phone FROM orders o JOIN customers c ON c.id=o.customer_id WHERE o.id = ?';
        $params = [$orderId];
        if ($customerId) { $sql .= ' AND o.customer_id = ?'; $params[] = $customerId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch();
        if (!$order) return null;
        $stmt = $pdo->prepare('SELECT oi.*, p.product_name, p.product_slug, p.image FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id = ?');
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $order['payment'] = $stmt->fetch() ?: null;
        return $order;
    }

    public static function updateStatus(int $orderId, string $status): void
    {
        global $pdo;
        $valid = ['Pending','Processing','Shipped','Delivered','Cancelled'];
        if (!in_array($status, $valid, true)) throw new InvalidArgumentException('Invalid order status.');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT order_status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $oldStatus = $stmt->fetchColumn();
            if (!$oldStatus) throw new RuntimeException('Order not found.');

            $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll();

            if ($status === 'Cancelled' && $oldStatus !== 'Cancelled') {
                foreach ($items as $item) {
                    $stockStmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
                    $stockStmt->execute([(int)$item['quantity'], (int)$item['product_id']]);
                }
            }

            if ($oldStatus === 'Cancelled' && $status !== 'Cancelled') {
                foreach ($items as $item) {
                    $stockStmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
                    $stockStmt->execute([(int)$item['product_id']]);
                    $stock = $stockStmt->fetchColumn();
                    if ($stock === false || (int)$stock < (int)$item['quantity']) {
                        throw new RuntimeException('Cannot reactivate order because stock is insufficient.');
                    }
                    $stockStmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?');
                    $stockStmt->execute([(int)$item['quantity'], (int)$item['product_id']]);
                }
            }

            $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
            $stmt->execute([$status, $orderId]);
            if ($status === 'Delivered') {
                $stmt = $pdo->prepare('UPDATE payments SET payment_status = "Paid" WHERE order_id = ?');
                $stmt->execute([$orderId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'order_status', 'Order #' . $orderId . ' changed to ' . $status);
    }
}


final class PaymentGateway
{
    public static function token(int $orderId, int $customerId): string
    {
        return hash_hmac('sha256', $orderId . '|' . $customerId, APP_SECRET_KEY);
    }

    public static function verifyToken(int $orderId, int $customerId, string $token): bool
    {
        return hash_equals(self::token($orderId, $customerId), $token);
    }

    public static function gatewayUrl(int $orderId, int $customerId): string
    {
        return url('payment/sslcommerz_init.php?order_id=' . $orderId . '&token=' . urlencode(self::token($orderId, $customerId)));
    }

    public static function customerOrder(int $orderId, int $customerId): array
    {
        $order = OrderService::find($orderId, $customerId);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ($order['payment_method'] !== 'SSLCommerz') {
            throw new RuntimeException('This order was not created for gateway payment.');
        }
        return $order;
    }

    public static function markPaid(int $orderId, int $customerId, ?string $gatewayTransactionId = null): void
    {
        global $pdo;
        $order = self::customerOrder($orderId, $customerId);
        $transactionId = $gatewayTransactionId ?: ('SSLCZ-' . date('YmdHis') . '-' . $orderId . '-' . random_int(1000, 9999));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE payments SET transaction_id = ?, payment_status = "Paid", payment_date = CURRENT_TIMESTAMP WHERE order_id = ?');
            $stmt->execute([$transactionId, $orderId]);
            $stmt = $pdo->prepare('UPDATE orders SET order_status = CASE WHEN order_status = "Pending" THEN "Processing" ELSE order_status END WHERE id = ?');
            $stmt->execute([$orderId]);
            self::recordLog($orderId, 'SSLCommerz Demo', $transactionId, 'success', ['amount' => (float)$order['total_amount'], 'customer_id' => $customerId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        log_activity('customer', $customerId, 'payment_success', 'Payment completed for order #' . $orderId);
    }

    public static function markFailed(int $orderId, int $customerId, string $eventType = 'failed'): void
    {
        global $pdo;
        $order = self::customerOrder($orderId, $customerId);
        $transactionId = $order['payment']['transaction_id'] ?? ('SSLCZ-FAILED-' . date('YmdHis') . '-' . $orderId);
        $stmt = $pdo->prepare('UPDATE payments SET transaction_id = ?, payment_status = "Failed", payment_date = CURRENT_TIMESTAMP WHERE order_id = ?');
        $stmt->execute([$transactionId, $orderId]);
        self::recordLog($orderId, 'SSLCommerz Demo', $transactionId, $eventType, ['amount' => (float)$order['total_amount'], 'customer_id' => $customerId]);
        log_activity('customer', $customerId, 'payment_' . $eventType, 'Payment ' . $eventType . ' for order #' . $orderId);
    }

    public static function retryPayment(int $orderId, int $customerId): void
    {
        global $pdo;
        self::customerOrder($orderId, $customerId);
        $transactionId = 'SSL-' . date('YmdHis') . '-' . $orderId . '-' . random_int(1000, 9999);
        $stmt = $pdo->prepare('UPDATE payments SET transaction_id = ?, payment_status = "Pending", payment_date = CURRENT_TIMESTAMP WHERE order_id = ?');
        $stmt->execute([$transactionId, $orderId]);
        self::recordLog($orderId, 'SSLCommerz Demo', $transactionId, 'retry', ['customer_id' => $customerId]);
    }

    public static function recordLog(int $orderId, string $gatewayName, ?string $transactionId, string $eventType, array $payload = []): void
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare('INSERT INTO payment_gateway_logs (order_id, gateway_name, transaction_id, event_type, payload) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$orderId, $gatewayName, $transactionId, $eventType, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        } catch (Throwable $e) {
            // Keep checkout/payment flow working even if an older database has not yet run the optional migration.
        }
    }
}

final class ReportService
{
    public static function dashboardStats(): array
    {
        global $pdo;
        return [
            'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'categories' => (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'customers' => (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
            'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'revenue' => (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status <> "Cancelled"')->fetchColumn(),
        ];
    }

    public static function salesByPeriod(string $period): array
    {
        global $pdo;
        $formats = [
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-W%v',
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
        ];
        $format = $formats[$period] ?? $formats['monthly'];
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(order_date, ?) label, COUNT(*) orders, COALESCE(SUM(total_amount),0) revenue FROM orders WHERE order_status <> 'Cancelled' GROUP BY label ORDER BY label DESC LIMIT 12");
        $stmt->execute([$format]);
        return array_reverse($stmt->fetchAll());
    }

    public static function recentOrders(int $limit = 8): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT o.*, c.name customer_name FROM orders o JOIN customers c ON c.id=o.customer_id ORDER BY o.order_date DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function bestSelling(int $limit = 10): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT p.product_name, SUM(oi.quantity) sold_qty, SUM(oi.quantity*oi.price) revenue FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN orders o ON o.id=oi.order_id WHERE o.order_status <> "Cancelled" GROUP BY p.id ORDER BY sold_qty DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function activity(int $limit = 10): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

final class CustomerService
{
    public static function search(?string $q = null): array
    {
        global $pdo;
        if ($q) {
            $stmt = $pdo->prepare('SELECT id, name, email, phone, address, created_at FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY created_at DESC');
            $like = '%' . $q . '%';
            $stmt->execute([$like, $like, $like]);
            return $stmt->fetchAll();
        }
        return $pdo->query('SELECT id, name, email, phone, address, created_at FROM customers ORDER BY created_at DESC')->fetchAll();
    }

    public static function delete(int $id): void
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
        $stmt->execute([$id]);
        log_activity('admin', $_SESSION['admin_id'] ?? null, 'customer_delete', 'Deleted customer ID: ' . $id);
    }

    public static function updateProfile(int $id, array $data): void
    {
        global $pdo;
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        if ($name === '' || $phone === '' || $address === '') throw new InvalidArgumentException('All fields are required.');
        $stmt = $pdo->prepare('UPDATE customers SET name=?, phone=?, address=? WHERE id=?');
        $stmt->execute([$name, $phone, $address, $id]);
        if (!empty($data['password'])) {
            if (strlen((string)$data['password']) < 6) throw new InvalidArgumentException('Password must be at least 6 characters.');
            $stmt = $pdo->prepare('UPDATE customers SET password=? WHERE id=?');
            $stmt->execute([password_hash((string)$data['password'], PASSWORD_DEFAULT), $id]);
        }
        log_activity('customer', $id, 'profile_update', 'Customer updated profile.');
    }
}

function render_product_card(array $product): string
{
    $price = (float)($product['discount_price'] ?? $product['price']);
    $regular = (float)$product['price'];
    ob_start();
    ?>
    <div class="col-sm-6 col-lg-4 col-xl-3 product-result">
        <div class="card product-card h-100 shadow-sm border-0">
            <a href="<?= url('products/product_details.php?slug=' . urlencode($product['product_slug'])) ?>" class="product-img-wrap">
                <img src="<?= e(product_image_url($product['image'] ?? null)) ?>" class="card-img-top" alt="<?= e($product['product_name']) ?>">
                <?php if (!empty($product['discount_price'])): ?><span class="badge bg-danger product-badge">Sale</span><?php endif; ?>
            </a>
            <div class="card-body d-flex flex-column">
                <small class="text-muted"><?= e($product['category_name'] ?? '') ?></small>
                <h6 class="card-title mt-1"><a class="text-decoration-none text-dark" href="<?= url('products/product_details.php?slug=' . urlencode($product['product_slug'])) ?>"><?= e($product['product_name']) ?></a></h6>
                <div class="mt-auto d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-bold text-primary"><?= money($price) ?></span>
                        <?php if (!empty($product['discount_price'])): ?><small class="text-muted text-decoration-line-through ms-1"><?= money($regular) ?></small><?php endif; ?>
                    </div>
                    <span class="small <?= (int)$product['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?>"><?= (int)$product['stock_quantity'] > 0 ? 'In stock' : 'Out' ?></span>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <form method="post" action="<?= url('cart/cart.php?action=add') ?>" class="flex-fill">
                        <?= csrf_input() ?>
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button class="btn btn-primary btn-sm w-100" <?= (int)$product['stock_quantity'] <= 0 ? 'disabled' : '' ?>><i class="bi bi-cart-plus"></i></button>
                    </form>
                    <form method="post" action="<?= url('customer/wishlist.php?action=add') ?>">
                        <?= csrf_input() ?>
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-heart"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    return (string)ob_get_clean();
}

function order_status_badge(string $status): string
{
    $map = [
        'Pending' => 'warning',
        'Processing' => 'info',
        'Shipped' => 'primary',
        'Delivered' => 'success',
        'Cancelled' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e($status) . '</span>';
}
