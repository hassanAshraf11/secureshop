# SecureShop — Team Organization & Responsibilities

**Course:** CET431 – Software Security (Fall 2026)  
**Institution:** Elsewedy University of Technology - Polytechnic of Egypt  
**Project:** SecureShop E-Commerce System  
**Submission:** Phase 1 (Week 9 / Week 10)

---

## 1. Team Structure Overview

Per course requirements, the team is composed of 6 members split evenly into:
* **Blue Team (Defensive & Development):** Responsible for designing system architecture, implementing the 8 functional features, establishing security defenses within the application code, and fixing vulnerabilities.
* **Red Team (Offensive & Security Assessment):** Responsible for threat modeling, entry-point enumeration, executing attacks and penetration testing against SecureShop, reporting findings, and collecting evidence.

---

## 2. Team Roster & Roles

| Member Name | Team Assignment | Primary Roles & Responsibilities |
| :--- | :--- | :--- |
| **Youssef Hamada** | **Red Team** | • Red Team Lead & Attack Planning<br>• Penetration testing on Authentication, Email OTP & Session Hijacking<br>• Writing Attack Scenarios & Red Team Finding Reports |
| **Hassan Ashraf** | **Red Team** | • Offensive testing on Business Logic, Concurrency & Checkout<br>• Client-side trust tampering & Coupon replay exploit testing<br>• Attack Evidence collection & verification against course topics |
| *[Team Member 5]* | **Red Team** | • Input validation attacks (SQL Injection, XSS, CSRF, Parameter Tampering)<br>• Access control bypass testing on Admin & Customer routes<br>• Assisting in Red Team Penetration Test Report documentation |
| **Omar Ghallap** | **Blue Team** | • Blue Team Lead & Backend Security Architecture<br>• Implementing Server-side transactional security (Row Locking, Piastre calculations)<br>• Session management, HMAC challenge verification & Access Control Middleware |
| **Ahmed Ebada** | **Blue Team** | • Frontend Architecture, UI/UX (Blade & Bootstrap Responsive Views)<br>• Database Schema design, Migrations & Relational integrity<br>• Implementing Catalogue, Shopping Cart & Newsletter features |
| *[Team Member 6]* | **Blue Team** | • Implementing User Account management & Admin control features<br>• Automated test suite development (PHPUnit Feature tests & Concurrency suites)<br>• Code refactoring & Patching vulnerabilities reported by the Red Team |

> *Note: If your team consists of 4 members approved by the course instructor, the responsibilities of Member 5 and Member 6 are shared among the respective Red and Blue team members.*

---

## 3. Team Responsibilities by Phase

### Phase 1 (Week 9 Submission — Requirements & Working MVP):
* **Blue Team:**
  1. Build first working version of SecureShop (Laravel 12 + MySQL 8.4 + Docker).
  2. Implement functional features: Auth (Register/Login/Email OTP), Catalogue, Cart, Checkout, Profile, and Admin.
  3. Integrate core security defenses: Server-side price calculation, DB row locking for concurrency, rate limiting, and password hashing/HMAC nonces.
  4. Prepare Architecture Overview and Database Schema.
* **Red Team:**
  1. Define Threat Model (Assets, Attackers, Entry Points).
  2. Map out the attack surface across all 8 features.
  3. Validate implemented defenses against initial course attack techniques.
  4. Initialize the Security Coverage Matrix with attack scenarios and root causes.

### Phase 2 (Week 14 Submission — Penetration Testing & Final Defense):
* **Red Team:**
  1. Execute timed Red Team penetration test against SecureShop.
  2. Document all findings: payload used, attack request, observed result, and CVSS severity.
  3. Compile the comprehensive *Attack Evidence Report* and *Red Team Penetration Test Report*.
* **Blue Team:**
  1. Analyze findings submitted by the Red Team.
  2. Develop and commit code patches for valid vulnerabilities.
  3. Update test suites to prevent regression and ensure all automated tests pass cleanly.
* **Whole Team:**
  1. Live demonstration (10–15 minutes) and individual Q&A defense.
