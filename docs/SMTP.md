# Email (SMTP) — Brevo & SendGrid

ExamGuard requires verified email before login (`MustVerifyEmail`). Registration, login resend, and email changes all use Laravel's mail system. Configure **SMTP** so verification emails reach real inboxes (required on InfinityFree and any host without local mail).

---

## Quick setup

1. Create a free account at **Brevo** or **SendGrid** (pick one).
2. Verify a **sender email** (your address or domain).
3. Copy SMTP credentials into `.env` (see templates below).
4. Test locally:

```powershell
php artisan examguard:test-mail you@example.com
```

5. Register a new user on the site and confirm the verification email arrives.

**InfinityFree (no SSH):** upload `deploy/infinityfree/public-mail-test.php` as `public/mail-test.php`, add `MAIL_TEST_SECRET=some-long-random-string` to `.env`, then visit:

`https://yoursite.example/mail-test.php?key=some-long-random-string&to=you@example.com`

Delete `mail-test.php` after a successful send.

**Important:** `APP_URL` in `.env` must match your live site URL (including `https://`). Verification links and avatar/snapshot URLs are built from `APP_URL`. Use a single `https://` prefix — not `https://https://...`.

---

## Option A — Brevo (recommended)

Free tier: ~300 emails/day. Good for class demos and small deployments.

### 1. Brevo account

1. Sign up at [brevo.com](https://www.brevo.com/).
2. **Senders & IP** → **Senders** → add and verify your email (or domain).
3. **SMTP & API** → **SMTP** → create an **SMTP key** (not your login password).

### 2. `.env` (Brevo)

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-login-email@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS="your-verified-sender@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

| Variable | Value |
|----------|--------|
| `MAIL_USERNAME` | Email you use to log into Brevo |
| `MAIL_PASSWORD` | SMTP key from Brevo dashboard |
| `MAIL_FROM_ADDRESS` | Must be a **verified sender** in Brevo |

---

## Option B — SendGrid

Free tier: ~100 emails/day.

### 1. SendGrid account

1. Sign up at [sendgrid.com](https://sendgrid.com/).
2. **Settings** → **API Keys** → create key with **Mail Send** permission.
3. **Settings** → **Sender Authentication** → verify a single sender email (or domain).

### 2. `.env` (SendGrid)

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_FROM_ADDRESS="your-verified-sender@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

| Variable | Value |
|----------|--------|
| `MAIL_USERNAME` | Literally the string `apikey` |
| `MAIL_PASSWORD` | Your SendGrid API key |
| `MAIL_FROM_ADDRESS` | Must match **verified sender** in SendGrid |

---

## Local development

Keep `MAIL_MAILER=log` in local `.env` to avoid sending real mail during dev. Verification links are written to `storage/logs/laravel.log`.

To test real SMTP locally, switch to `MAIL_MAILER=smtp` and run:

```powershell
php artisan examguard:test-mail your@email.com
```

---

## InfinityFree

InfinityFree cannot send mail with PHP `mail()`. Use Brevo or SendGrid SMTP in `dist/infinityfree/.env` before FTP upload.

**Free InfinityFree DNS** only supports A, CNAME (subdomains), MX, and SPF — not arbitrary TXT records. You **cannot** verify `noreply@examguard.site.je` in Brevo using InfinityFree DNS alone. Use **Option A** below.

### Option A — Verified personal sender (recommended on free hosting)

1. Brevo → **Senders** → add your real email (Gmail, school, etc.) → click the confirmation link Brevo sends.
2. Brevo → **SMTP & API** → **SMTP** → generate an **SMTP key**.
3. Paste into server `.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-login-email@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS="same-email-you-verified-in-brevo@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

4. Set `APP_URL=https://examguard.site.je` (single `https://`).
5. Re-upload `.env` via FTP / file manager.
6. Test with `mail-test.php` (see Quick setup) or register a new account.

### Option B — Domain sender (`noreply@examguard.site.je`)

Add Brevo DNS records at your **domain registrar** (not InfinityFree free DNS), then use that address as `MAIL_FROM_ADDRESS`.

See [INFINITYFREE.md](../INFINITYFREE.md) for full deploy steps.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| **Email not received** | Check spam; confirm sender is verified in Brevo/SendGrid |
| **Authentication failed** | Brevo: use SMTP key, not account password. SendGrid: username must be `apikey` |
| **From address rejected** | `MAIL_FROM_ADDRESS` must match verified sender |
| **Verification link goes to localhost** | Set `APP_URL` to production HTTPS URL |
| **Connection timeout** | Try port `587` + `MAIL_SCHEME=smtp`; some hosts block port 25 |
| **Still using log driver** | Ensure `MAIL_MAILER=smtp` and re-upload `.env` on InfinityFree |

Check Laravel log after failed send:

```text
storage/logs/laravel.log
```

---

## What sends email in ExamGuard

| Event | Trigger |
|-------|---------|
| Register | `AuthController::register()` → `sendEmailVerificationNotification()` |
| Login (unverified) | Resend + block until verified |
| Resend button | `POST /api/email/resend` |
| Change email in settings | `updateProfile()` clears verification and resends |

All use the same `config/mail.php` / `.env` SMTP settings.
