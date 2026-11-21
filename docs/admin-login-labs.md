# Labs 4 and 5 · proposed administrator authentication

**Status: awaiting instructor clarification.** The working administrator sign-in uses the same verified email, password, session-bound email OTP and active-account checks as customer sign-in, followed by a server-side role check. The features below are proposals. The application collects no typing or location samples and has no simulated passkey verifier.

| Lab topic | Proposed integration | Decisions for the instructor | Evidence still pending |
| --- | --- | --- | --- |
| Lab 4 · typing pattern | Enroll consenting administrators with several trusted samples. Calculate timing intervals and compare later samples with the enrolled distribution. Treat the result as a risk signal. | Enrollment count, threshold, step-up action, accessibility fallback, retention and deletion | Genuine-device enrollment, false accept/reject measurements and fallback review |
| Lab 5 · passkey | Register a WebAuthn credential after strong authentication. Verify a fresh server-issued challenge, RP ID, origin, signature and user-verification flag. Store only the public credential data. | Whether passkeys replace email OTP or add a factor, allowed authenticators and recovery | Real authenticator enrollment and verified assertion, expired and repeated challenge tests |
| Lab 5 · location | Compare a documented source of location with an agreed permitted area. Request consent and record source and accuracy. Label any future demonstration coordinates as simulated. | Permitted area, accuracy threshold, treatment of missing or untrusted locations | Agreed boundary cases and review of privacy controls |
| Lab 5 · permitted login hours | Evaluate an administrator policy against server time in an explicitly configured time zone. | Allowed hours, time zone, overnight windows, daylight-saving transitions and exceptions | Boundary-time tests with a controlled clock |

Resolve the questions with the instructor before integrating these features into the authentication flow. The team owns the formal Red Team exercise and its evidence.
