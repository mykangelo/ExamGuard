# ExamGuard on InfinityFree

Deploy ExamGuard to [InfinityFree](https://www.infinityfree.com/) free PHP hosting. InfinityFree supports **PHP 8.3** and **MySQL**, which matches this project — but it has **no SSH**, **no `artisan`**, **no symlinks**, and **no cron jobs**. You build everything on your PC and upload via FTP.

---

## Before you start

### What works on InfinityFree

| Feature | Status |
|---------|--------|
| Laravel + session auth | Yes (with root `.htaccess` → `public/`) |
| MySQL database | Yes (import SQL via phpMyAdmin) |
| AJAX `/api/*` | Yes |
| HTTPS + camera proctoring | Yes (enable SSL in control panel) |
| Avatar / snapshot uploads | Yes (`public/storage/serve.php` — no `storage:link` required) |

### Hard limitations

| Limitation | Impact on ExamGuard |
|------------|---------------------|
| No SSH / Artisan | Run `migrate`, `build`, `key:generate` **locally** |
| No symlinks | Use `public/storage/serve.php` + `public/.htaccess` rewrite (see [File uploads](#file-uploads-avatars--violation-snapshots)) |
| No foreign keys on MySQL | Import a **stripped** SQL dump (see below) |
| No cron / queue workers | Set `QUEUE_CONNECTION=sync` |
| CPU / inode limits | Large `vendor/` folder — first upload can take 30–60+ min |
| No PHP `mail()` | Use **Brevo** or **SendGrid SMTP** in `.env` — see [docs/SMTP.md](docs/SMTP.md) |

> **Honest recommendation:** InfinityFree is fine for a **demo or class project**. For reliable production exams with many students, use Railway, Render, or a VPS instead.

---

## Step 1 — Create InfinityFree account

1. Sign up at [infinityfree.com](https://www.infinityfree.com/).
2. Create a hosting account and note your **FTP host**, **username**, and **password** (Control Panel → FTP Details).
3. Create a **MySQL database** (Control Panel → MySQL Databases). Save:
   - Database host (e.g. `sql123.infinityfree.com`)
   - Database name
   - Username
   - Password
4. Enable **HTTPS** (Control Panel → SSL Certificates) once your site is live.

---

## Step 2 — Build the deploy package (on your PC)

From the project root in PowerShell:

```powershell
.\scripts\build-infinityfree.ps1
```

This script:

1. Runs `composer install --no-dev`
2. Runs `npm ci` and `npm run build` (Vite assets → `public/build/`)
3. Copies the app to `dist/infinityfree/` (ready to upload)
4. Adds `htdocs/.htaccess`, `public/storage/serve.php`, and `public/storage/.htaccess` for InfinityFree
5. Creates writable `storage/` and `bootstrap/cache/` folders

### Generate `APP_KEY` locally

```powershell
php artisan key:generate --show
```

Copy the `base64:...` value into `dist/infinityfree/.env` (create from `.env.infinityfree.example`).

### Configure `.env` in `dist/infinityfree/`

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...your-key...
APP_URL=https://yoursite.infinityfreeapp.com
```

Use a **single** `https://` prefix — not `https://https://...` (breaks verification links and image URLs).

```env
DB_HOST=sqlXXX.infinityfree.com
DB_DATABASE=if0_XXXXXXX_examguard
DB_USERNAME=if0_XXXXXXX
DB_PASSWORD=your_password

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=file

# Email — Brevo or SendGrid SMTP (required for registration verification)
# Full guide: docs/SMTP.md
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-login-email@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS="your-verified-sender@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Step 2b — Configure email (Brevo or SendGrid)

InfinityFree does not send email on its own. For **register / verify email** to work:

1. Create a free [Brevo](https://www.brevo.com/) or [SendGrid](https://sendgrid.com/) account.
2. Verify a **sender email** in their dashboard.
3. Copy SMTP credentials into `dist/infinityfree/.env` (templates in `.env.infinityfree.example`).
4. Test locally before upload:

```powershell
php artisan examguard:test-mail your@email.com
```

5. Ensure `APP_URL` matches your live HTTPS site — verification links use this URL.

**Detailed setup:** [docs/SMTP.md](docs/SMTP.md)

Demo seed accounts (`professor@examguard.local` / `student@examguard.local`) are pre-verified and work without email. **New registrations** need SMTP.

---

## Step 3 — Prepare the database (locally)

InfinityFree MySQL **does not allow foreign key constraints**. Run migrations locally, then export without FK lines.

### 3a. Migrate and seed locally

```powershell
php artisan migrate:fresh --seed
```

Demo logins (email pre-verified for hosting without SMTP):

| Role | Email | Password |
|------|-------|----------|
| Professor | `professor@examguard.local` | `Professor123!` |
| Student | `student@examguard.local` | `Student123!` |

### 3b. Export SQL

Using XAMPP phpMyAdmin: select database → **Export** → SQL → download.

Or with mysqldump (adjust credentials):

```powershell
mysqldump -u root examguard > database\examguard-export.sql
```

### 3c. Strip foreign keys

```powershell
.\scripts\strip-fk-from-sql.ps1 -InputFile database\examguard-export.sql -OutputFile database\examguard-infinityfree.sql
```

Import `database/examguard-infinityfree.sql` in InfinityFree phpMyAdmin (Control Panel → phpMyAdmin → Import).

---

## Step 4 — Upload via FTP

Use [FileZilla](https://filezilla-project.org/) or the InfinityFree file manager.

1. Connect to FTP.
2. Open the remote **`htdocs`** folder.
3. Upload **all contents** of `dist/infinityfree/` into `htdocs/`:
   - `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `vendor/`
   - `.env` (must be uploaded — hidden file)
   - `.htaccess` (from build — routes to `public/`)

**Upload tips:**

- Upload can take a long time because of `vendor/` (tens of thousands of files).
- If upload fails partway, resume or re-upload missing folders.
- Ensure `.env` is present in `htdocs/` (enable "show hidden files" in FileZilla).

### Folder permissions

If you get 500 errors about logs or cache, set these folders to **755** or **775** via FTP:

- `storage/`
- `storage/framework/`
- `storage/logs/`
- `bootstrap/cache/`

---

## Step 5 — Verify the site

1. Visit `https://yoursite.infinityfreeapp.com`
2. You should see the ExamGuard home page.
3. Log in as professor or student (seed accounts above).
4. Test student dashboard, create a draft exam, assign to class.
5. For take-exam camera: must use **HTTPS** URL.

### Health check

Visit `https://yoursite.infinityfreeapp.com/up` — should return a healthy status response.

### File uploads (avatars & violation snapshots)

InfinityFree **cannot** use `php artisan storage:link` (symlinks disabled). Rewriting `/storage/...` directly to `../../storage/app/public/` causes **HTTP 500**.

The app serves uploads through a small PHP script:

| File | Purpose |
|------|---------|
| `public/.htaccess` | Routes missing `/storage/*` files to `serve.php` |
| `public/storage/serve.php` | Reads files from `storage/app/public/` safely |
| `public/storage/.htaccess` | Disables directory listing only (no external rewrite) |

**Quick test** (replace path with a real file from FTP):

```
https://yoursite.example/storage/serve.php?path=avatars/12/your-file.png
https://yoursite.example/storage/serve.php?path=violation-snapshots/5/your-file.jpeg
```

If `serve.php?path=...` works but `/storage/...` does not, re-upload `public/.htaccess`.

Uploaded files live on the server at `htdocs/storage/app/public/avatars/` and `htdocs/storage/app/public/violation-snapshots/`.

### Production smoke test

After each deploy, verify:

| Area | What to check |
|------|----------------|
| **Auth** | Login, register (no HTTP 500), verification email |
| **Professor** | Create/publish exam, live sessions, violation snapshots display |
| **Student** | Join class → toast + bell notification (not browser `alert`) |
| **Proctoring** | Max warnings → disconnect → home shows **Violations exceeded**, cannot re-enter |
| **Settings** | Avatar upload, password show/hide toggle, profile save |
| **Images** | Avatar and snapshot URLs load (not empty icon / InfinityFree 500 page) |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| **500 error** | Set `APP_DEBUG=true` temporarily in `.env`; check `storage/logs/laravel.log` via FTP |
| **Blank page** | Missing `.env` or `APP_KEY`; vendor not fully uploaded |
| **404 on all routes** | Root `.htaccess` missing or `mod_rewrite` — confirm `htdocs/.htaccess` exists |
| **Database connection error** | Wrong `DB_HOST` — use InfinityFree hostname, not `localhost` |
| **#1142 REFERENCES denied** | Re-import SQL after running `strip-fk-from-sql.ps1` |
| **symlink() disabled** | Use `public/storage/serve.php` (build script copies it). Do **not** rewrite to `../../storage/...` in `.htaccess` |
| **Avatars / violation snapshots broken (empty image icon or HTTP 500)** | Upload `public/storage/serve.php`, `public/storage/.htaccess`, and updated `public/.htaccess`; confirm files exist under `storage/app/public/` on the server. Test with `/storage/serve.php?path=...` |
| **Professor settings: avatar / password toggle not working** | Upload `professor-settings.js`, `settings-shared.js` (v3+); hard refresh |
| **Student avatar shows broken icon** | Upload `student.js`, `student-settings.js`, `settings-shared.js`; ensure `serve.php` works |
| **Exam re-entry after max violations** | Upload `AttemptController.php`, `ExamController.php`, `ExamAttempt.php`, `student.js`, `take-exam.js` |
| **Unstyled marketing pages** | `public/build/` missing — re-run `npm run build` and re-upload `public/build/` |
| **Sign up: Server Error / 500** | Run `deploy/infinityfree/fix-auto-increment.sql` in phpMyAdmin — import often drops `AUTO_INCREMENT` on `id` columns |
| **Field 'id' doesn't have a default value** | Same fix — `ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;` |
| **Verification email not sent** | `MAIL_MAILER=smtp`, verified sender, test with `php artisan examguard:test-mail` |
| **Composer platform error** | Server is PHP 8.3 — run `composer update --no-dev` locally (see `composer.json` `platform.php`), rebuild, re-upload **`vendor/composer/platform_check.php`** (must say `80300`, not `80401`) |
| **Autoload file > 1 MB** | Re-run build with `composer install --no-dev` and `"optimize-autoloader": false` in `composer.json` if needed |

---

## Updating after code changes

1. Edit code locally.
2. Re-run `.\scripts\build-infinityfree.ps1`
3. Upload changed files via FTP (or full re-upload if unsure).
4. If migrations changed, export a new stripped SQL dump and re-import (or run migrations locally and export data only).
5. After JS changes, bump `?v=` on script tags in Blade or hard-refresh browsers (Ctrl+F5).
6. If uploads break, always re-upload `public/.htaccess`, `public/storage/serve.php`, and `public/storage/.htaccess` together.

---

## Files in this repo for InfinityFree

| Path | Purpose |
|------|---------|
| `INFINITYFREE.md` | This guide |
| `.env.infinityfree.example` | Production env template |
| `deploy/infinityfree/htdocs.htaccess` | Route web root → `public/` |
| `deploy/infinityfree/public-storage.htaccess` | Copied to `public/storage/.htaccess` (no external rewrite) |
| `public/storage/serve.php` | Serves avatars and violation snapshots without symlinks |
| `deploy/infinityfree/fix-auto-increment.sql` | Fix missing `AUTO_INCREMENT` after SQL import |
| `app/Support/PublicStorageUrl.php` | Builds `/storage/...` URLs for avatars and snapshots |
| `scripts/build-infinityfree.ps1` | Local build → `dist/infinityfree/` |
| `scripts/strip-fk-from-sql.ps1` | Remove FK constraints from SQL dump |
| `docs/SMTP.md` | Brevo / SendGrid SMTP setup for email verification |

---

## Related docs

- [DOCUMENTATION.md](DOCUMENTATION.md) — full codebase reference
- [§9 AJAX and JSON](DOCUMENTATION.md#9-ajax-and-json) — how the API works in production
