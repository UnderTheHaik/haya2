# Haya2 — local abaya boutique

A separate WordPress/WooCommerce shop alongside the Under the Haik Astro blog. Nothing is published remotely.

## Open the shop

Store: http://127.0.0.1:8088/

Admin: http://127.0.0.1:8088/wp-admin/

The generated admin login is saved locally in `work/shop-runtime/admin-credentials.txt` (ignored by Git).

From the project directory in PowerShell:

```powershell
./shop/setup.ps1 # First setup, or refresh theme/plugin and seed configuration
./shop/start.ps1 # Run the shop; Ctrl+C stops it
```

If the shop is already running, do not start a second server on port 8088. A background server started during development records its PID in `work/shop-runtime/server.pid`.

## Included

- Eight abaya products with the eight supplied photographs, optimized for web use.
- Everyday, Occasion, Signature and Light tones collections.
- AED prices, collection/price/size/colour/stock filters and sorting.
- S/M/L/XL variations, per-size stock; Midnight bloom XL demonstrates an unavailable size.
- WooCommerce cart, guest checkout, order history and account pages.
- `HAYA10`: 10% off, minimum AED 50, 100 total redemptions, no coupon stacking.
- UAE shipping: AED 25; free from AED 300 **after discounts**. Paid shipping is hidden when free delivery applies.
- Responsive layouts, photo credits, sample delivery/returns policy and size-guide page.

Names, prices, sizing, delivery terms and stock are samples. Product photographs are supplied Unsplash images; credits are in `images.json` and the shop footer's Photo credits page.

## Test payments

Choose **Test payment — no charge** at checkout, then select **Approved** or **Declined**. Approved payments create paid WooCommerce orders, reduce variation stock and empty the cart. Declined payments display a retry message. No card data is requested, no funds move, and outbound WordPress emails are suppressed locally.

The official WooCommerce Stripe plugin is installed, disabled, and set to test mode. Provider-backed payment testing requires a Stripe sandbox account and test credentials configured under WooCommerce → Settings → Payments → Stripe. No Stripe transaction has been tested. The local simulation is available without an account.

## Local storage

The setup downloads official PHP for Windows, WordPress, WooCommerce, the WordPress SQLite integration and the WooCommerce Stripe plugin into ignored `work/shop-runtime/`. It listens only on `127.0.0.1`.

The SQLite database is `work/shop-runtime/wordpress/wordpress/wp-content/database/.ht.sqlite`. Back up the whole runtime directory while the server is stopped to retain uploads, users, orders and stock.

This SQLite installation is for local evaluation. WooCommerce's MySQL row-lock stock reservations are disabled **only for local SQLite** because their SQL is unsupported by the adapter. Stock validation and reduction remain active; simultaneous-order reservation behaviour is not provided. Scheduled WordPress jobs are disabled locally. A hosted WooCommerce deployment should use MySQL/MariaDB and its normal cron configuration.

The previous mixed-category demo products were moved to WordPress Trash, preserving their order history. Setup does not reset stock or orders for the current abaya catalogue.

## Verification

```powershell
./work/shop-runtime/php/php.exe ./shop/verify.php
# Optionally check a newly paid test order and stock of 7 after buying one item:
./work/shop-runtime/php/php.exe ./shop/verify.php ORDER_ID
```

Browser verification covers mobile and desktop presentation, combined filters, size selection, cart, coupons, approved/declined local checkout and inventory reduction.
