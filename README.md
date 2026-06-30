# ShopEase Native PHP E-Commerce System

## Setup
1. Copy `ecommerce_system` into your web server root, for example `htdocs/ecommerce_system`.
2. Create/import the database by running `database/schema.sql` in phpMyAdmin or MySQL CLI.
3. Update database credentials in `includes/db_connect.php` if your MySQL username/password is different.
4. Ensure `assets/uploads/products` is writable by the web server.
5. Open `/ecommerce_system/index.php`.

## Default Admin
- Email: admin@shop.com
- Password: admin123

## Database Name
`ecommerce_db`

## Payment Gateway
This build includes a fully working localhost demo payment gateway flow using `SSLCommerz Demo Payment Gateway`.

Test flow:
1. Register/login as a customer.
2. Add product to cart.
3. Checkout.
4. Select `SSLCommerz Demo Payment Gateway`.
5. Click `Place Order`.
6. On the gateway screen, click `Demo Pay Successfully`.
7. Payment status becomes `Paid` and order status becomes `Processing`.

Files added for gateway:
- `payment/sslcommerz_init.php`
- `payment/success.php`
- `payment/fail.php`
- `payment/cancel.php`
- `payment/ipn.php`

If you already imported an older database before receiving this gateway version, run:
`database/payment_gateway_update.sql`

## Notes
- Backend uses native PHP OOP with PDO and prepared statements.
- CSRF, XSS-safe output, password hashing, upload validation, session auth, remember login, coupons, reports, invoices, inventory alerts, and AJAX product search are included.
- Change `APP_SECRET_KEY` in `includes/functions.php` before production.
- For real live SSLCommerz/bKash/Nagad payment, you need merchant credentials and public callback URLs. Localhost cannot receive live gateway callbacks without a tunnel or hosted domain.
