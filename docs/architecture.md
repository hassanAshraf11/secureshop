# How SecureShop fits together

The browser receives server-rendered Blade views. Laravel's web middleware handles cookies, sessions, request validation and CSRF. The catalogue works without a JavaScript build. The small script in `public/shop.js` applies the selected sort and fills the email-verification form from the link fragment. Bootstrap, the font and product images are bundled locally.

| Path | Responsibility |
| --- | --- |
| `routes/web.php` | Public, guest, signed-in and admin route groups |
| `app/Http/Controllers/AuthController.php`, `app/Services/Addresses.php` | Password checks, email ownership and email OTP challenges |
| `app/Http/Middleware/ShopAccess.php` | Active account, verified address, completed second factor and admin role |
| `app/Http/Controllers/StoreController.php`, `CartController.php` | Catalogue, newsletter, customer-owned cart, checkout and receipts |
| `app/Services/Checkout.php` | Quote generation, server-side prices and the payment transaction |
| `app/Http/Controllers/AccountController.php`, `AdminController.php` | Profile changes, order history and shop management |
| `database/migrations/`, `database/seeders/` | Relational constraints, initial products, accounts and coupon |
| `resources/views/`, `public/` | Templates, responsive styling, local assets and form behaviour |
| `tests/Feature/` | HTTP feature tests and a two-process MySQL checkout test |

## Identity

Registration sets a customer role and grants 100,000 piastres through database defaults. A random, expiring confirmation key is emailed through SMTP. Its hash is stored in MySQL. Opening the link displays a form. Submitting the form confirms the address and consumes the key. A profile email change uses a pending address until confirmation. The database enforces uniqueness at the final write.

A valid password for a verified account creates a random six-digit code, stores only its HMAC, and emails it to the verified address. The database stores a hash of a random challenge nonce; the browser session holds the nonce and account ID. The code and challenge expire after 60 seconds. Verification locks the user row, checks the session binding and expiry, compares hashes in constant time, then consumes the code before authenticating. Five incorrect codes invoke a five-minute account cooldown. Logout invalidates the session and rotates the CSRF token.

## Payment

1. Cart rows belong to the authenticated user. A quantity update locks that user first. One cart row exists per user and product.
2. The review page calculates integer-piastre totals from MySQL. The app rounds percentage discounts down to a whole piastre once on the subtotal. It issues an authenticated encrypted ticket with the user ID, a unique purchase key, the displayed cart/discount fingerprint and a 20-minute expiry.
3. Payment locks the user row and looks for an existing order under that purchase key. A duplicate returns the earlier order ID. Product rows and the selected coupon are locked before stock, eligibility, credit and the quote fingerprint are checked.
4. One transaction writes the order and its item snapshots, deducts the balance, reduces stock, records the redemption, increments usage and empties the cart. A failed check rolls the transaction back.
5. A successful POST redirects to the receipt with HTTP 303. Reading a receipt queries the authenticated customer's order and its immutable snapshots. Its remaining credit is the balance immediately after that payment.

## Coupon lifecycle

Newsletter addresses are normalized and unique. A repeat signup emails the original code with its original expiry and usage. Newsletter codes require the same verified email at payment. All coupons permit one redemption per account, plus a configurable global ceiling. Selecting a coupon previews the discount. Payment consumes it. Removing a coupon or abandoning checkout keeps its uses available.

## Local services

The app speaks to MySQL on the private Compose network and sends SMTP to Mailpit. Only the storefront and Mailpit inbox are published to loopback. Named volumes persist the database and Laravel runtime storage between restarts. Legacy TOTP columns are retained but unused. This is a local course demonstration with known seeded credentials.
