# 🚀 SaraJane E‑commerce – Deployment Checklist

Use this checklist to ensure a smooth, secure deployment to production (Render + Aiven MySQL).  
Tick off each item before and after deployment.

---

## 📋 Pre‑Deployment Preparation

- [ ] **Code is fully committed** to GitHub (or your Git repository) with the latest changes.
- [ ] **Local development works** – all features (cart, checkout, admin) function correctly.
- [ ] **`.env` file is properly configured** locally and **not committed** (add to `.gitignore`).
- [ ] **`.env.example`** is up to date and committed (shows all required environment variables).
- [ ] **Database schema** (`database_schema.sql`) is committed and matches production.
- [ ] **All hardcoded secrets are removed** from source code (passwords, API keys, email credentials).
- [ ] **Unsafe card payment collection has been removed** – only safe payment methods (Cash on Delivery / PayPal placeholder) remain.
- [ ] **Security headers** are considered (add `X-Frame-Options`, `X-Content-Type-Options` if needed).
- [ ] **Error handling** is set to production mode (don’t display errors, log them).
- [ ] **`APP_ENV`** environment variable is set to `production` in the target environment.

---

## 🌐 Environment Setup

### Render Web Service
- [ ] Web service created with **Docker** environment (or Render’s native PHP with custom Dockerfile).
- [ ] **Environment variables** are set (see `.env.example`):
  - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
  - `DB_CA_PATH` (if using Aiven with CA certificate)
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` (for email)
  - `SITE_URL` – must be the production URL (e.g., `https://sarajane-ecommerce.onrender.com/`)
  - `SITE_NAME`, `ADMIN_EMAIL`
  - `APP_ENV=production`
- [ ] **Build** – Dockerfile builds without errors (check logs).
- [ ] **Health check endpoint** is configured (e.g., `/` or `/health`).
- [ ] **Auto‑deploy** is enabled (or manual deployment trigger is understood).

### Database (Aiven MySQL)
- [ ] MySQL service is **running** (status = `Running`).
- [ ] **CA certificate** (`ca.pem`) is downloaded and either:
  - Uploaded to the repository (committed) and referenced via `DB_CA_PATH`, or
  - Configured in the `database.php` to be read from a secure location.
- [ ] **Firewall / IP whitelist** – allow Render’s egress IP addresses (if restricted; for Aiven, usually not needed).
- [ ] **Database schema is imported** – all tables exist (`products`, `users`, `orders`, etc.).
- [ ] **Test data** (if any) is removed or anonymised for production.

### Email (SMTP)
- [ ] SMTP credentials are set in environment variables (use Gmail App Password or other provider).
- [ ] Test email sent successfully (e.g., contact form, order confirmation).

---

## 🔒 Security & Compliance

- [ ] **No sensitive data is logged** (passwords, card details).
- [ ] **HTTPS is enforced** – Render provides HTTPS by default.
- [ ] **Strong admin password** – change default admin credentials.
- [ ] **CSRF protection** is enabled on all forms (login, register, contact, admin actions).
- [ ] **Rate limiting** is active for login, registration, and contact forms.
- [ ] **File uploads** are restricted to allowed types and sizes.
- [ ] **PHP error display** is off (`display_errors = Off` in production).
- [ ] **Session settings** – secure, httponly, SameSite=Lax (already set in `session.php`).
- [ ] **Input validation & sanitisation** is used everywhere (`htmlspecialchars`, `filter_var`).

---

## 🧪 Testing

### Functional Tests
- [ ] Homepage loads correctly (products, categories, carousel).
- [ ] Product listing and filtering work.
- [ ] Add to cart (from product page and card) works.
- [ ] Cart page: quantity update, remove item, total updates.
- [ ] Checkout flow:
  - [ ] Shipping information form validates required fields.
  - [ ] Shipping method selection works.
  - [ ] Payment method selection (Cash on Delivery / PayPal) works.
  - [ ] Order is created in the database.
  - [ ] Stock is **not deducted** before payment (or according to your logic).
  - [ ] Order confirmation email is sent.
- [ ] User registration and email verification work.
- [ ] Login / logout / account update work.
- [ ] Wishlist (favorites) add/remove works.
- [ ] Admin area:
  - [ ] Dashboard statistics display.
  - [ ] Product management (add, edit, delete, image upload).
  - [ ] Order management (view, update status, send email).
  - [ ] Newsletter subscriber list and export.
  - [ ] User management (role change, delete).
- [ ] Newsletter subscription (footer form) works.
- [ ] Contact form sends message and stores in database.
- [ ] Search and category filters work.
- [ ] Review posting (if enabled) works.

### Error & Edge Cases
- [ ] Empty cart redirects properly.
- [ ] Out‑of‑stock products cannot be added to cart (or show warning).
- [ ] Invalid login attempts trigger rate limit.
- [ ] CSRF token mismatch shows error (not a blank page).
- [ ] Database connection failure shows safe message (not raw error).

### Performance & Load
- [ ] Page load time is acceptable (use Render logs / browser DevTools).
- [ ] Images are optimised (use compression tools).
- [ ] No unnecessary database queries (check with logging).

---

## 📦 Post‑Deployment Tasks

- [ ] **Set `APP_ENV=production`** – verify that error messages are generic.
- [ ] **Turn on monitoring** – set up Render logs, optionally add Uptime Robot or similar.
- [ ] **Backup strategy** – ensure automated database backups are enabled (Aiven provides backups).
- [ ] **Set up custom domain** (if not using `onrender.com`):
  - Add domain in Render → update DNS (CNAME or A record).
  - Update `SITE_URL` environment variable accordingly.
- [ ] **Test email sending** again from the live environment.
- [ ] **Verify that uploads directory is writable** (if any file uploads are kept locally; for Render ephemeral storage, consider cloud storage later).
- [ ] **Check SSL certificate** – Render provides auto‑renewing Let’s Encrypt.

---

## 🔄 Rollback Plan

If something goes wrong after deployment:

- [ ] **GitHub** – revert to the last stable commit (`git revert` or `git reset`).
- [ ] **Render** – use the **“Redeploy previous deployment”** button in the dashboard.
- [ ] **Database** – restore from Aiven’s latest backup (available in the service → Backups tab).
- [ ] **Environment variables** – keep a record of the previous settings.
- [ ] **Communication** – inform users/stakeholders about downtime if needed.

---

## ✅ Final Sign‑Off

| Role | Name | Date | Signature (or check) |
|------|------|------|----------------------|
| Developer |         |      | ☐ |
| QA / Tester |        |      | ☐ |
| Project Owner |       |      | ☐ |

---

**Deployment Date:** _______________  
**Deployed Version:** _______________  
**Environment:** Production