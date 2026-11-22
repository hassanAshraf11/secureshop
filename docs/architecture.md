# SecureShop — System Architecture & Design

SecureShop is a security-hardened e-commerce prototype built with **Laravel 12**, **PHP 8.4**, **MySQL 8.4**, **Blade**, **Bootstrap 5**, and **Mailpit**, running entirely containerized via **Docker Compose**.

---

## 1. System Architecture Diagram

```mermaid
flowchart TD
    subgraph ClientTier ["1. Client Tier (Browser)"]
        Browser["🌐 Web Browser (Desktop / Mobile)"]
        UI["Blade Templates & Bootstrap 5 (shop.js)"]
    end

    subgraph EntryTier ["2. Web Entry & Security Middleware Tier"]
        WebServer["Web Server / PHP 8.4 Runtime (Port 8431)"]
        Router["Router (routes/web.php)"]
        
        subgraph MiddlewarePipeline ["Security Middleware Pipeline"]
            MW_Headers["BrowserHeaders Middleware<br>(No-Store, X-Frame-Options, CSP)"]
            MW_Web["Web Middleware Group<br>(Session, EncryptCookies, CSRF Verify)"]
            MW_Throttle["Throttle Middleware<br>(Rate Limiting: auth & sensitive actions)"]
            MW_Access["ShopAccess Middleware<br>(Active, Verified, Completed 2FA, Role: Admin)"]
        end
    end

    subgraph AppTier ["3. Application & Controller Tier"]
        AuthController["AuthController<br>• Register & Login<br>• Email Confirm & OTP Verify"]
        StoreController["StoreController<br>• Catalogue & Details<br>• Newsletter Subscription"]
        CartController["CartController<br>• Cart Management<br>• Checkout Review & Pay"]
        AccountController["AccountController<br>• Profile & Email Change<br>• Order History"]
        AdminController["AdminController<br>• Product, User & Coupon CRUD"]
    end

    subgraph ServiceTier ["4. Domain & Security Services Tier"]
        CheckoutSvc["Checkout Service (Checkout.php)<br>• Server-side piastre calculations<br>• Signed quote tickets<br>• ACID Payment & Row Locking"]
        AddressSvc["Addresses Service (Addresses.php)<br>• Email normalization<br>• HMAC OTP generation & verification"]
        CatalogSvc["Catalog Service (Catalog.php)<br>• Query filtering & safe sorting"]
    end

    subgraph DataTier ["5. Data & Local Services Tier (Internal Docker Network)"]
        MySQL[("🗄️ MySQL 8.4 Database<br>• users, products, cart_items<br>• coupons, orders, order_items<br>• Transactions & lockForUpdate")]
        Mailpit["📨 Mailpit Test SMTP (Port 8025 / 1025)<br>• Capture verification links<br>• Capture 6-digit login OTP codes"]
    end

    %% Connections
    Browser <-->|HTTPS / HTTP Requests + Cookies| WebServer
    WebServer --> Router
    Router --> MW_Headers
    MW_Headers --> MW_Web
    MW_Web --> MW_Throttle
    MW_Throttle --> MW_Access
    
    MW_Access -->|Auth Routes| AuthController
    MW_Access -->|Store Routes| StoreController
    MW_Access -->|Cart & Checkout Routes| CartController
    MW_Access -->|User Account Routes| AccountController
    MW_Access -->|Admin Routes| AdminController

    AuthController --> AddressSvc
    StoreController --> CatalogSvc
    CartController --> CheckoutSvc
    
    AddressSvc --> MySQL
    AddressSvc -->|Send OTP / Links via SMTP| Mailpit
    CheckoutSvc -->|ACID Transactions & Row Locks| MySQL
    CatalogSvc --> MySQL
    AccountController --> MySQL
    AdminController --> MySQL

    classDef client fill:#e1f5fe,stroke:#0288d1,stroke-width:2px;
    classDef mw fill:#fff3e0,stroke:#f57c00,stroke-width:2px;
    classDef app fill:#e8f5e9,stroke:#388e3c,stroke-width:2px;
    classDef svc fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px;
    classDef data fill:#fce4ec,stroke:#c2185b,stroke-width:2px;

    class Browser,UI client;
    class WebServer,Router,MW_Headers,MW_Web,MW_Throttle,MW_Access mw;
    class AuthController,StoreController,CartController,AccountController,AdminController app;
    class CheckoutSvc,AddressSvc,CatalogSvc svc;
    class MySQL,Mailpit data;
```

---

## 2. Request & Security Lifecycle (End-to-End Sequence)

The diagram below details the path of a sensitive user request (e.g., checkout & payment with concurrency protection):

```mermaid
sequenceDiagram
    autonumber
    actor User as Client (Browser)
    participant MW as Middleware (CSRF + ShopAccess)
    participant Cart as CartController
    participant Svc as Checkout Service
    participant DB as MySQL Database
    participant Mail as Mailpit SMTP

    User->>MW: POST /checkout/pay (with CSRF token + Session)
    MW->>MW: Verify CSRF token & check 2FA authenticated session
    MW->>Cart: Forward valid request
    Cart->>Svc: pay(user, purchase_key, ticket)
    
    rect rgb(240, 248, 255)
        note over Svc,DB: Atomic Database Transaction with Pessimistic Locking
        Svc->>DB: BEGIN TRANSACTION
        Svc->>DB: SELECT * FROM users WHERE id = ? FOR UPDATE (Row Lock)
        Svc->>DB: Check idempotent purchase_key (prevent replay)
        Svc->>DB: SELECT * FROM products WHERE id IN (...) FOR UPDATE
        Svc->>DB: SELECT * FROM coupons WHERE id = ? FOR UPDATE
        Svc->>Svc: Verify quote fingerprint, balance >= total, stock >= qty
        Svc->>DB: Deduct balance, decrement stock, increment coupon used
        Svc->>DB: INSERT INTO orders & order_items (Immutable snapshot)
        Svc->>DB: DELETE FROM cart_items WHERE user_id = ?
        Svc->>DB: COMMIT TRANSACTION
    end

    Svc-->>Cart: Return Order ID
    Cart-->>User: HTTP 303 Redirect to /orders/{id} (Receipt)
```

---

## 3. Component Responsibilities

| Path / Component | Core Responsibility | Security Capabilities |
| :--- | :--- | :--- |
| `routes/web.php` | Routing definition for public, guest, authenticated and admin routes | Explicit route grouping, middleware attachment, parameter constraints (`whereNumber`) |
| `app/Http/Middleware/BrowserHeaders.php` | HTTP response headers | `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` |
| `app/Http/Middleware/ShopAccess.php` | Authentication and authorization gate | Requires active account status, verified email, completed OTP factor, and admin role checks |
| `app/Http/Controllers/AuthController.php` | Registration, login, confirmation, and OTP verification | Password hashing (Bcrypt/Argon2), HMAC token verification, login cooldown throttles |
| `app/Services/Addresses.php` | Email normalization & token lifecycle | Normalizes emails, hashes one-time tokens, constant-time hash comparisons |
| `app/Services/Checkout.php` | Pricing, checkout quotes, payment transaction | Server-side integer-piastre arithmetic, cryptographically signed quote tickets, row locking (`FOR UPDATE`) |
| `app/Http/Controllers/StoreController.php` | Public store catalogue & newsletter | Input allowlisting for sorting, safe parameterized Eloquent queries |
| `app/Http/Controllers/CartController.php` | Shopping cart and orders | Session-scoped cart queries (prevents IDOR), Post/Redirect/Get pattern |
| `app/Http/Controllers/AdminController.php` | Administrative management panel | Role-restricted management of products, users, and coupons |
| `database/migrations/` | Relational database schema | Strict Foreign Key constraints, composite unique indexes, cascades |
| `resources/views/` | Blade templates | Automatic output escaping `{{ $var }}` (XSS prevention), CSRF directive `@csrf` |

---

## 4. Key Security Architectural Mechanisms

### 4.1 Authentication & Multi-Factor (Email OTP)
1. **Password Verification:** Passwords must meet minimum complexity (>= 12 characters) and are verified using constant-time hash checks.
2. **Email OTP Factor:** Upon successful password verification, an unauthenticated session challenge is created with a 60-second lifetime. A random 6-digit code is generated; only its HMAC hash is stored in the database.
3. **Throttling & Cooldown:** 5 failed code attempts lock the factor for 5 minutes.
4. **Session Regeneration:** Upon completing the second factor, the session ID is regenerated to prevent session fixation attacks.

### 4.2 Financial Integrity & Concurrency Control
1. **Integer Piastre Storage:** All monetary values are represented as integers (piastres) avoiding floating-point rounding inaccuracies.
2. **Server-Side Pricing:** The client cannot submit totals. All totals are derived dynamically on the server from locked database records.
3. **Pessimistic Concurrency (Row Locking):** When paying, the user record, cart items, products, and coupon are locked using `SELECT ... FOR UPDATE` within an isolated ACID transaction.
4. **Idempotent Purchase Keys:** Each checkout session issues a cryptographically random UUID `purchase_key`. Resubmitting the same key returns the existing order rather than charging the customer twice.

### 4.3 Containerized Isolation
1. **Docker Compose Network:** MySQL and Mailpit communicate over an isolated internal network.
2. **Loopback Binding:** Only port `8431` (Storefront) and port `8025` (Mailpit Web UI) are exposed to the host machine, strictly bound to loopback `127.0.0.1`.
