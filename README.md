# ExamGuard (Laravel + MySQL + Tailwind)

Online examination monitoring platform built with **Laravel 13**, **MySQL**, **Tailwind CSS**, and vanilla **JavaScript**.

The original Node.js / SQLite version is in `legacy/`.

## Requirements (XAMPP)

- [XAMPP](https://www.apachefriends.org/) with **Apache** and **MySQL** started in the Control Panel
- Composer (PHP dependency manager)
- Node.js 20+ (for building Tailwind CSS)

## XAMPP setup

### 1. Start services in XAMPP Control Panel

- Start **Apache** (optional if you use `php artisan serve`)
- Start **MySQL** (required)

### 2. Create the database (if needed)

The app can create it automatically, or use **phpMyAdmin** (`http://localhost/phpmyadmin`):

```sql
CREATE DATABASE examguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configure `.env`

XAMPP defaults (already set in this project):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=examguard
DB_USERNAME=root
DB_PASSWORD=
```

If you set a MySQL root password in XAMPP, add it to `DB_PASSWORD`.

### 4. Install and prepare the app

Open a terminal in the project folder:

```powershell
composer install
npm install
npm run build
php artisan migrate --seed
```

### 5. Run the app

```powershell
php artisan serve
```

Open **http://localhost:8000**

For CSS hot-reload during development, run `npm run dev` in a second terminal.

### Alternative: Apache via XAMPP htdocs

You can also place or symlink the project under `C:\xampp\htdocs\examguard` and point the Apache document root to `public/`, but `php artisan serve` is simpler for local development.

## Seed accounts

| Role | Email | Password |
|------|-------|----------|
| Professor | `professor@examguard.local` | `Professor123!` |
| Student | `student@examguard.local` | `Student123!` |

## Create additional users

```powershell
php artisan examguard:create-user "Full Name" email@example.com "StrongPassword" student
```

## Project structure

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/Api/` | REST API (auth, classes, exams, attempts) |
| `app/Models/` | Eloquent models |
| `database/migrations/` | MySQL schema |
| `resources/views/` | Blade HTML templates |
| `resources/css/app.css` | Tailwind theme and components |
| `public/js/` | Frontend JavaScript |
| `public/storage/serve.php` | Serves uploads when symlinks are unavailable (InfinityFree) |
| `app/Support/PublicStorageUrl.php` | Avatar and snapshot URL helper |
| `routes/web.php` | Pages and `/api/*` routes |

## Notes

- Authentication uses Laravel sessions with CSRF protection.
- Webcam/tab monitoring runs client-side via MediaPipe (`public/js/monitoring.js`).
- Warning counts are derived server-side from `violation_events` (authoritative at submit and for max-warning lock).
- Profile photos and violation snapshots are stored under `storage/app/public/` and served at `/storage/...` (via `storage:link` locally, or `public/storage/serve.php` on InfinityFree).
- After max proctoring warnings, students cannot re-enter the exam (`violation_exceeded`).

## Deployment

| Target | Guide |
|--------|--------|
| **InfinityFree** (free) | [INFINITYFREE.md](INFINITYFREE.md) — build locally, FTP upload |
| **BSCpE rubric / defense** | [PROJECT_RUBRIC_DOCUMENTATION.md](PROJECT_RUBRIC_DOCUMENTATION.md) |
| **Email (SMTP)** | [docs/SMTP.md](docs/SMTP.md) — Brevo or SendGrid for verification |
| **VPS / Railway / Render** | [DOCUMENTATION.md §17](DOCUMENTATION.md#17-deployment-notes) |
