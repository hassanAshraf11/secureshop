# SecureShop

A local CET431 electronics store built with Laravel 12, PHP 8.4, MySQL 8.4, Blade, Bootstrap and Mailpit. Prices are displayed in Egyptian pounds and stored as integer piastres.

SecureShop is a course prototype for local development and security demonstrations. Do not deploy it as a public production store. Its seeded accounts and local email inbox are demonstration tools, and its email sign-in code is a course-project substitution for authenticator-app TOTP that may need instructor approval.

## Requirements

Docker runs the PHP application, MySQL and Mailpit, so PHP, Composer and Node.js do not need to be installed on the host.

### Windows 10/11

1. Install and start Docker Desktop, with the WSL 2 engine enabled.
2. Install an Ubuntu WSL distribution and enable Docker Desktop's WSL integration for it.
3. Open the Ubuntu terminal (not Command Prompt or PowerShell), clone or open this repository inside the WSL Linux filesystem, and continue with the Linux steps below.

Keeping the project inside the WSL filesystem (for example, under `~/projects`) avoids slow file access and Linux permission issues from `/mnt/c`.

### Linux

Install Docker Engine and the Docker Compose v2 plugin for your distribution. Ensure the user running setup has permission to run `docker`. Then continue with Quick start.

## Quick start (Linux or Ubuntu on WSL)

From the repository directory, with Docker running:

```sh
./bin/setup
```

This builds the app image, creates a local `.env` and application key if needed, starts the services, applies migrations, and loads demo data. The first build needs internet access. No host PHP, Composer or Node installation is needed.

Open:

- Store: <http://localhost:8431>
- Mailpit test inbox: <http://localhost:8025>

The web and Mailpit ports bind to loopback. Docker keeps application and database files in named volumes.

Useful commands:

```sh
./bin/compose ps       # Show service status
./bin/compose logs -f   # Follow service logs
./bin/compose stop      # Pause services and retain data
./bin/compose start     # Resume services
./bin/setup             # Build, start and seed again
```

`./bin/setup` is for a first local installation and applies database seeders. For a clean local reset, remove the project volumes with `./bin/compose down -v`, then run setup again. This deletes local demo data.

## Demo accounts

| Account | Email | Starting credit |
| --- | --- | --- |
| Customer | customer@secureshop.test | 1,000 EGP |
| Second customer | second@secureshop.test | 1,000 EGP |
| Administrator | admin@secureshop.test | 1,000 EGP |

All demo accounts use `SecureShop-Demo-431`; their email addresses are pre-confirmed. Sign-in sends a six-digit one-time code to the local Mailpit inbox. These credentials are public demo data: use only on your private local instance and never reuse them outside the prototype. The admin panel is under **Account → Manage shop**.

## Try registration and email sign-in codes

1. Open **Account → Create an account**. Enter a new email and a password of at least 12 characters. Local Mailpit accepts test addresses.
2. In Mailpit, open **Confirm your SecureShop email**, follow the link and click **Confirm email**. The confirmation link expires after 30 minutes.
3. Sign in with the password. Open Mailpit, then enter the latest six-digit code before its 60-second expiry. Codes are one-use; a new code invalidates the previous one. Five incorrect attempts trigger a five-minute cooldown.
4. To change email, save the new address under **Account** and confirm the message sent to it. Until confirmation, sign in with the original address.

This implementation uses an email-delivered one-time code, not authenticator-app TOTP. Check the course requirement with the instructor before submission.

## Rehearse the acceptance example

On a fresh seed, sign in as the customer, add one **Forma 68 Mechanical Keyboard** (500 EGP) to the cart and apply **WELCOME10** at checkout. Review 500 EGP subtotal, 50 EGP discount and 450 EGP due. Pay once. The receipt shows 550 EGP remaining and stays available under **Account → Order history**. A different signed-in customer should receive a 404 for that receipt.

Newsletter signup sends a separate unique, 10% one-use code to Mailpit. The verified subscriber can use it; repeat signup resends the original coupon. Administrators can change prices and stock, deactivate products or users, and configure coupon terms. Existing receipts retain historical purchase details.

## Tests

```sh
./bin/compose exec -T app php vendor/bin/phpunit
./bin/test-mysql
```

The first command uses in-memory SQLite and skips the MySQL locking test. The second starts a dedicated MySQL test service whose database is held in temporary storage, separate from demo data. Tests use an in-memory mail transport. Mailpit delivery is checked separately.

The repository's latest recorded full-suite run has failures; see [verification record](docs/verification.md) before treating a security behavior as verified. A passing test proves only the behavior asserted by that test.

## GitHub and local data

- `.env` contains this installation's generated key and settings. Keep it private; `.env.example` is the template.
- Do not commit `.env`, `vendor/`, local logs, database files, or credentials for real accounts. The project's `.gitignore` excludes `.env` and generated files.
- The bundled `.local-tools/` fallback is machine-specific and is not included in a Git clone. Install Docker Compose v2 instead.
To publish this folder after creating an empty repository on GitHub, run these commands from the project directory and replace the placeholder URL with your repository URL:

```sh
git init -b main
git add .
git status --short   # Check that .env and other private files are not staged
git commit -m "Prepare SecureShop project"
git remote add origin https://github.com/YOUR-ACCOUNT/YOUR-REPOSITORY.git
git push -u origin main
```

If Git is already initialized, skip `git init -b main`; check `git remote -v` before adding a remote. The current local folder has no Git repository or GitHub remote configured. Review the security coverage and verification documents before presenting their results; pending or failed checks are not evidence of working protection.

## Course material

- [Team organization & responsibilities](docs/team.md)
- [System Architecture & Lifecycle](docs/architecture.md)
- [Database Schema & ERD](docs/erd.md)
- [Threat Model & Attack Surface](docs/threat-model.md)
- [Security coverage matrix](docs/security-coverage.md)
- [Checks performed and pending work](docs/verification.md)
- [Lab 4 and 5 admin-login proposals](docs/admin-login-labs.md)

Product and hero images are original AI-generated demo assets. Bootstrap is distributed under the MIT license. Bricolage Grotesque is bundled under the SIL Open Font License in `public/fonts/OFL.txt`.
