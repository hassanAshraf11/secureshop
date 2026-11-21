# Verification record

## Completed

- Rebuilt the Docker app and applied migration 2026_11_21_000002_add_email_login_otp.
- php -l app/Http/Controllers/AuthController.php — passed.
- php artisan route:list --path=email-code — confirmed GET and POST routes.
- Manual browser flow — signed in with a pre-verified demo customer, received a six-digit sign-in message in Mailpit, submitted the current code, and reached Account. This confirms delivery and successful session authentication.
- Source review confirms codes are stored as HMACs, the random challenge nonce is hashed in the database and bound to the browser session, successful codes are consumed, and code attempts are limited.

## Automated tests

./bin/compose exec -T app php vendor/bin/phpunit tests/Feature/IdentityTest.php did not pass: all five tests failed, primarily with HTTP 419 CSRF responses. The test suite still needs a test-harness fix and a clean rerun before those behaviors are considered verified.

## Pending

| Check | Status | How to run |
| --- | --- | --- |
| 60-second expiration | Pending | Wait 60 seconds before submitting a code |
| Replay and cross-session reuse rejection | Pending | Re-submit a consumed code and try it from another session |
| Five-attempt cooldown | Pending | Submit incorrect codes five times |
| Full PHPUnit suite | Pending | ./bin/compose exec -T app php vendor/bin/phpunit |
| MySQL concurrency suite | Pending | ./bin/test-mysql |
| Re-run shopping acceptance and cross-account receipt checks | Pending | Follow README rehearsal steps |

The email-code flow replaces TOTP per the user's instruction; the original course brief called for authenticator-app TOTP, so instructor approval is still needed if that requirement is enforced. Existing legacy TOTP database columns are retained but unused. The formal Red Team exercise remains the team's responsibility.
