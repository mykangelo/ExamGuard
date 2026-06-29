# ExamGuard — Codebase Documentation

ExamGuard is an online examination and proctoring platform. Professors create exams, assign them to classes, and monitor live sessions. Students join classes, take timed exams, and receive notifications. Browser-based proctoring detects tab switches, face presence, and similar events (demo mode — not production-hardened security).

---

## Table of contents

1. [Technology stack](#1-technology-stack)
2. [Prerequisites and setup](#2-prerequisites-and-setup)
3. [Project structure](#3-project-structure)
4. [Architecture overview](#4-architecture-overview)
5. [Database schema](#5-database-schema)
6. [Authentication and authorization](#6-authentication-and-authorization)
7. [API reference](#7-api-reference)
8. [Application features](#8-application-features)
9. [Frontend architecture](#9-frontend-architecture)
10. [Proctoring system](#10-proctoring-system)
11. [Exam lifecycle](#11-exam-lifecycle)
12. [Key code flows](#12-key-code-flows)
13. [Configuration](#13-configuration)
14. [Testing](#14-testing)
15. [Legacy folder](#15-legacy-folder)
16. [Deployment notes](#16-deployment-notes)

---

## 1. Technology stack

### Backend

| Technology | Version | Purpose |
|------------|---------|---------|
| **PHP** | ^8.3 | Server runtime |
| **Laravel** | ^13.8 | Web framework (routing, ORM, auth, migrations) |
| **MySQL** | 8.x (via XAMPP or Docker) | Primary database |
| **Composer** | Latest | PHP dependency manager |

### Frontend

| Technology | Purpose |
|------------|---------|
| **Blade** | Server-rendered HTML templates |
| **Tailwind CSS v4** | Utility CSS (marketing pages, shared components via Vite) |
| **Vite** | Asset bundler for `resources/css/app.css` |
| **Vanilla JavaScript** | All dashboard and exam UI logic (`public/js/`) |
| **Tabler Icons** | Icon font (CDN) on professor/student dashboards |
| **MediaPipe Tasks Vision** | Client-side face detection during exams (CDN) |
| **Plus Jakarta Sans / Space Grotesk** | Google Fonts |

### What is *not* used

- No React, Vue, or Angular SPA framework
- No separate REST API server — API routes live in the same Laravel app
- No Redis/queue required for core features (queue worker is optional in `composer dev`)

### PHP dependencies (`composer.json`)

- `laravel/framework` — core framework
- `laravel/tinker` — REPL for debugging

### Node dependencies (`package.json`)

- `vite`, `laravel-vite-plugin` — build pipeline
- `tailwindcss`, `@tailwindcss/vite` — CSS framework
- `concurrently` — runs server + Vite + queue in `composer dev`

---

## 2. Prerequisites and setup

### Requirements

- PHP 8.3+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`
- Composer
- Node.js 20+
- MySQL (XAMPP or Docker)

### Quick start

```powershell
composer install
npm install
npm run build
cp .env.example .env   # or copy manually on Windows
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open **http://localhost:8000**

### Seed accounts

| Role | Email | Default password |
|------|-------|------------------|
| Professor | `professor@examguard.local` | `Professor123!` |
| Student | `student@examguard.local` | `Student123!` |

Create more users:

```powershell
php artisan examguard:create-user "Full Name" email@example.com "Password123!" student
php artisan examguard:create-user "Prof Name" prof@example.com "Password123!" professor
```

### Development (all services)

```powershell
composer dev
```

Runs: `php artisan serve`, queue listener, log tail (`pail`), and `npm run dev`.

---

## 3. Project structure

```
ExamGuard/
├── app/
│   ├── Console/Commands/     # Artisan commands (CreateUser)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/          # JSON API controllers
│   │   │   ├── PageController.php
│   │   │   └── EmailVerificationController.php
│   │   └── Middleware/
│   │       └── EnsureRole.php
│   ├── Models/               # Eloquent models
│   ├── Providers/
│   └── Services/             # Business logic (notifications)
├── bootstrap/app.php         # Middleware aliases, app bootstrap
├── config/                   # Laravel + proctoring.php
├── database/
│   ├── migrations/           # Schema evolution
│   └── seeders/
├── public/
│   ├── js/                   # Frontend application scripts
│   └── index.php             # Web entry point
├── resources/
│   ├── css/app.css           # Tailwind entry (Vite)
│   └── views/
│       ├── layouts/          # app.blade.php, auth.blade.php
│       ├── pages/            # Full pages (professor, student, take-exam)
│       └── partials/         # Reusable Blade fragments
├── routes/web.php            # All HTTP routes (pages + /api/*)
├── tests/Feature/
├── legacy/                   # Original Node/SQLite prototype (reference only)
└── DOCUMENTATION.md          # This file
```

### Important paths

| Path | Role |
|------|------|
| `routes/web.php` | Single route file for pages and API |
| `public/js/api-client.js` | Central `ExamGuardApi` fetch wrapper |
| `resources/views/pages/professor.blade.php` | Professor SPA-like shell (all views in one page) |
| `resources/views/pages/student.blade.php` | Student dashboard shell |
| `resources/views/pages/take-exam.blade.php` | Timed exam + preflight + proctoring |
| `public/js/take-exam.js` | Exam session, timer, submit, modals |
| `public/js/monitoring.js` | Camera, face detection, violation reporting |
| `config/proctoring.php` | Demo mode flag |

---

## 4. Architecture overview

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                               │
│  Blade HTML + inline CSS + public/js/*.js                   │
│  ExamGuardApi → fetch(/api/...) with CSRF + session cookie  │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP (same origin)
┌──────────────────────────▼──────────────────────────────────┐
│                    Laravel 13 (PHP)                          │
│  routes/web.php → Controllers → Models → MySQL               │
│  Session auth (cookie) · Role middleware (professor/student) │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                         MySQL                                │
│  users, classrooms, exams, attempts, violation_events, ...    │
└─────────────────────────────────────────────────────────────┘
```

### Design patterns

- **Monolithic Laravel app** — pages and JSON API share authentication and models.
- **Fat views, modular JS** — large Blade files embed CSS; behavior split across focused JS files.
- **Session-based auth** — not JWT; `credentials: "same-origin"` on all API calls.
- **Role-based access** — `professor` and `student` roles on `users.role`; middleware `role:professor`.
- **Server-authoritative proctoring** — client reports violation *types*; server assigns severity, message, and counts.

---

## 5. Database schema

### Core tables (initial migration)

| Table | Description |
|-------|-------------|
| `users` | Accounts with `role` (`professor` \| `student`), `preferences` JSON, `avatar_path` |
| `classrooms` | Professor-owned classes with unique 6-char `class_code` |
| `enrollments` | Student ↔ classroom membership |
| `exams` | Title, instructions, time/warning limits, lifecycle status, schedule, exam key |
| `questions` | Ordered prompts per exam |
| `choices` | Multiple-choice options per question |
| `exam_assignments` | Exam ↔ classroom assignment |
| `exam_attempts` | One attempt per student per exam; score, answers, warnings, timestamps |

### Extended tables (later migrations)

| Table / columns | Description |
|-----------------|-------------|
| `violation_events` | Authoritative proctoring log per attempt (type, severity, snapshot path) |
| `student_notifications` | In-app notifications for students |
| `exams.proctoring_triggers_json` | Per-exam trigger configuration |
| `exams.max_warning_action` | `notify`, `auto_submit`, or `lock` on max warnings |
| `exams.opens_at`, `closes_at`, `closed_at`, `exam_key`, `status` | Scheduling and access |

### Entity relationships (simplified)

```
User (professor) ──< Classroom ──< Enrollment >── User (student)
       │
       └──< Exam ──< Question ──< Choice
              │
              ├──< ExamAssignment >── Classroom
              └──< ExamAttempt ──< ViolationEvent
                        └── User (student)
```

---

## 6. Authentication and authorization

### Login flow

1. User submits email/password/role to `POST /api/auth/login`.
2. `AuthController` validates credentials and role match.
3. Laravel session is created; subsequent requests use session cookie.
4. CSRF token from `<meta name="csrf-token">` sent as `X-CSRF-TOKEN` header.

### Middleware

| Alias | Class | Behavior |
|-------|-------|----------|
| `auth` | Laravel default | Requires logged-in user |
| `guest` | Laravel default | Login/register pages only |
| `role:professor` | `EnsureRole` | 403 JSON or redirect if role mismatch |

Registered in `bootstrap/app.php`:

```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureRole::class,
]);
```

### Profile and account

- `PUT /api/auth/profile` — name, email, department
- `PUT /api/auth/password` — change password
- `POST /api/auth/avatar` — upload profile image to `storage/app/public/avatars`
- `DELETE /api/auth/account` — delete account

---

## 7. API reference

All `/api/*` routes require `auth` middleware unless noted.

### Auth

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/login` | Login (throttled) |
| POST | `/api/auth/register` | Register (throttled) |
| POST | `/api/auth/logout` | End session |
| GET | `/api/auth/me` | Current user |

### Classes

| Method | Path | Role | Description |
|--------|------|------|-------------|
| GET | `/api/classes` | student | List enrolled classes |
| POST | `/api/classes/join` | student | Join via class code |
| GET | `/api/professor/classes` | professor | Professor's classes |
| POST | `/api/classes` | professor | Create class |
| DELETE | `/api/classes/{id}` | professor | Delete class |

### Exams

| Method | Path | Role | Description |
|--------|------|------|-------------|
| GET | `/api/exams` | both | List exams (role-filtered) |
| GET | `/api/exams/{id}` | both | Exam detail + questions |
| POST | `/api/exams` | professor | Create exam |
| PUT | `/api/exams/{id}` | professor | Update draft exam |
| DELETE | `/api/exams/{id}` | professor | Delete exam |
| POST | `/api/exams/{id}/duplicate` | professor | Clone exam |
| POST | `/api/exams/{id}/close` | professor | Close exam |
| PUT | `/api/exams/{id}/schedule` | professor | Set open/close times |
| POST | `/api/exams/access-by-key` | student | Enter exam via key |

### Attempts and proctoring

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/exams/{id}/attempts/start` | Start or resume in-progress attempt |
| POST | `/api/exams/{id}/attempts/{attempt}/heartbeat` | Keep session alive |
| POST | `/api/exams/{id}/attempts/{attempt}/violations` | Report violation (server logs event) |
| POST | `/api/exams/{id}/attempts` | Submit answers, compute score |

### Professor analytics

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/professor/live-sessions` | Active exam sessions (heartbeat window) |
| GET | `/api/professor/violations` | Violation records |
| GET | `/api/professor/attempts/{id}/violations` | Violations for one attempt |
| GET | `/api/professor/notifications` | Professor notifications |
| GET | `/api/professor/dashboard` | Dashboard stats |

### Student

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/student/notifications` | Student notifications |
| PUT | `/api/student/notifications/read` | Mark notifications read |

### Assignments

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/assignments` | Assign exam to class |

---

## 8. Application features

### Public / marketing

| Route | Page |
|-------|------|
| `/` | Home (redirects if logged in) |
| `/login`, `/register` | Authentication |
| `/tour`, `/pricing`, `/faq`, `/contact` | Marketing |

Uses Tailwind via Vite (`resources/css/app.css`).

### Professor dashboard (`/professor`)

Single-page shell with sidebar navigation. Views toggled via `data-view` attributes and `professor.js`.

| View | Purpose |
|------|---------|
| **Exams** | Table of all exams, filters, row actions (edit, duplicate, schedule, close, delete) |
| **Create exam** | Multi-phase exam builder (`create-exam.js`, embedded form) |
| **Classes** | Manage classrooms (`professor-classes.js`) |
| **Overall results** | Cross-exam performance summary |
| **Proctoring** | Live sessions table (`professor-live-sessions.js`) |
| **Violations** | Students with proctoring flags |
| **Settings** | Profile, password, notifications, workspace defaults |
| **Help** | FAQ-style help content |

**Sidebar live widget** (`professor-sidebar-live-widget.js`) — shows active exam when students have recent heartbeats.

### Student dashboard (`/student`)

| View | Purpose |
|------|---------|
| **Home** | Upcoming exams, quick actions |
| **Calendar** | Month/week schedule with exam pills |
| **Exam room** | Enter exam key to access unassigned exams |
| **Results** | Submitted attempt scores |
| **Settings** | Profile, password, notifications |
| **Class** | Per-class exam list |

Data loaded via `ExamGuardApi.exams()`, `ExamGuardApi.classes()`, etc. in `student.js`.

### Take exam (`/take-exam?examId=`)

1. **Preflight gate** — camera, mic, browser, network checks (fresh start only).
2. **Resume** — if attempt already `in_progress`, skips preflight and restores timer from `startedAt`.
3. **Session** — `POST attempts/start`, heartbeat every 20s, `window.ExamGuardSession` for violations.
4. **Proctoring** — `monitoring.js` face/tab/audio monitoring.
5. **Submit** — styled modals for unanswered questions and success; redirects to `/student#results`.

### Exam room (`/exam-room`)

Legacy-style monitoring panel for entering exams by key (simpler flow than full take-exam page).

---

## 9. Frontend architecture

### Global objects

| Object | File | Purpose |
|--------|------|---------|
| `window.ExamGuardApi` | `api-client.js` | All HTTP API calls |
| `window.ExamGuardProfessor` | `professor.js` | View switching, exam detail navigation |
| `window.ExamGuardExams` | `professor-exams.js` | Row menus, exam actions |
| `window.ExamGuardDialog` | `professor-dialog.js` | Toasts and confirm dialogs |
| `window.ExamGuardRoute` | `route-state.js` | Persist `examId` in sessionStorage for take-exam |
| `window.ExamGuardSession` | `take-exam.js` | Active attempt + `reportViolation()` |
| `window.ExamGuardProctoring` | `take-exam.js` | Max-warning action and triggers |

### API client pattern

```javascript
// public/js/api-client.js
async function request(path, options = {}) {
  const response = await fetch(path, {
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken(),
      ...
    },
    ...options,
  });
  // throws Error with .status, .code on failure
}
```

### Professor view switching

```javascript
// professor.js — views are hidden/shown with CSS classes
function switchView(viewName, options = {}) {
  views.forEach(v => v.classList.toggle('active', v.dataset.view === viewName));
  navLinks.forEach(link => link.classList.toggle('active', link.dataset.view === viewName));
}
```

### Blade layouts

- **`layouts/app.blade.php`** — Dashboard pages; includes CSRF meta, Vite CSS, footer, `@stack('scripts')`.
- **`layouts/auth.blade.php`** — Login/register centered layout.

### CSS strategy

- **Marketing pages** — Tailwind utility classes + `app.css` components (`.eg-btn-primary`, `.eg-panel`).
- **Professor/student dashboards** — Large inline `<style>` blocks in Blade files (`.pg-*`, `.sd-*` prefixes).
- **Take-exam** — Inline styles in `take-exam.blade.php` (`.te-*` prefix).

---

## 10. Proctoring system

### Client side (`monitoring.js`)

Loads **MediaPipe Face Landmarker** from CDN. Monitors:

| Event type | Trigger |
|------------|---------|
| `tab_switch` | `visibilitychange` / blur |
| `mouse_leave` | Pointer leaves exam window |
| `no_face` / `multiple_faces` | Face landmarker results |
| `fullscreen_exit` | Fullscreen API |
| `copy_attempt`, `paste_attempt`, `context_menu` | Blocked interactions |

Violations call `window.ExamGuardSession.reportViolation({ type, snapshot, ... })`.

### Server side (`AttemptController::reportViolation`)

1. Validates attempt is in-progress and owned by user.
2. Maps `type` → base severity (`minor`, `moderate`, `critical`).
3. Auto-escalates repeated minor violations of same type.
4. Stores `ViolationEvent` with optional snapshot image in `storage/app/public/violation-snapshots/`.
5. Syncs `warning_count` on attempt from events (authoritative count).
6. Returns updated `warningCount` to client.

### Max warnings

Configured per exam (`warning_limit`, `max_warning_action`). Client shows escalation UI and may auto-submit or lock based on `max_warning_action`.

### Demo mode

`config/proctoring.php` → `PROCTORING_DEMO_MODE=true` shows disclaimer that browser monitoring is assistive only.

---

## 11. Exam lifecycle

### Status values

| Status | Meaning |
|--------|---------|
| `draft` | Editable, not visible to students |
| `scheduled` | `opens_at` in the future |
| `active` | Students can start attempts |
| `closed` | No new attempts; professor closed or past `closes_at` |

`Exam::displayStatus()` computes effective status from `status`, `opens_at`, `closes_at`, `closed_at`.

### Typical professor flow

1. Create exam (draft) → add questions → assign class.
2. Publish / schedule → generates `exam_key`, sets `opens_at`/`closes_at`.
3. Monitor via **Proctoring** tab during live sessions.
4. Review results in exam detail or **Overall results**.
5. Close exam when finished.

### Typical student flow

1. Join class with code OR enter exam key in exam room.
2. See exam on dashboard/calendar when active.
3. **Take exam** → preflight → answer questions → submit.
4. View score on results tab.

---

## 12. Key code flows

### Start exam session (student)

```
take-exam.js: renderExam()
  → GET /api/exams/{id}          (includes existing attempt if in_progress)
  → if no attempt: runPreflightChecks()
  → on proceed: startSession()
      → POST /api/exams/{id}/attempts/start
      → sets window.ExamGuardSession
      → beginExamRuntime() (timer, monitoring)
```

`AttemptController::start()` reuses existing in-progress attempt or creates a new one.

### Submit exam

```
take-exam.js: finishExam()
  → collect radio answers per question index
  → POST /api/exams/{id}/attempts  { answers, startedAt }
  → AttemptController::store() scores against correct_choice
  → show success modal → redirect /student#results
```

### Live sessions (professor)

```
professor-sidebar-live-widget.js / professor-live-sessions.js
  → GET /api/professor/live-sessions (poll every 4s)
  → ProctoringController filters in_progress attempts
     with heartbeat within ~18 seconds
```

### Student notifications

```
StudentNotificationService::notifyExamAssigned()
  → triggered when exam assigned to class
  → creates student_notifications rows
  → student-notifications.js polls GET /api/student/notifications
```

---

## 13. Configuration

### Environment (`.env`)

| Variable | Purpose |
|----------|---------|
| `DB_*` | MySQL connection |
| `APP_URL` | Application URL |
| `PROCTORING_DEMO_MODE` | Show demo proctoring disclaimer |
| `SEED_PROFESSOR_PASSWORD` | Override seed professor password |
| `SEED_STUDENT_PASSWORD` | Override seed student password |

### Files

| File | Purpose |
|------|---------|
| `config/database.php` | DB drivers |
| `config/session.php` | Session driver (file/database) |
| `config/proctoring.php` | Proctoring demo flag |
| `config/filesystems.php` | `public` disk for avatars, snapshots |

### Storage

```powershell
php artisan storage:link
```

Links `public/storage` → `storage/app/public` for avatars and violation snapshots.

---

## 14. Testing

```powershell
php artisan test
# or
composer test
```

Feature tests live in `tests/Feature/`. Example: `ProctoringWarningCountTest.php` verifies warning count is derived from violation events at submit time.

---

## 15. Legacy folder

`legacy/` contains the original **Node.js + SQLite + static HTML** prototype:

- `legacy/app.js`, `legacy/api-client.js`, `legacy/*.html`

Not used at runtime. Kept for reference when comparing old vs Laravel implementation.

---

## 16. Deployment notes

### Minimum production checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`.
2. Configure real MySQL credentials.
3. Run `php artisan migrate --force`.
4. Run `npm run build` and `php artisan config:cache`.
5. Point web server document root to `public/`.
6. Enable HTTPS for camera/mic permissions.
7. Run `php artisan storage:link`.
8. Review `PROCTORING_DEMO_MODE` and security expectations.

### Roles

There is **no admin panel**. User management via:

```powershell
php artisan examguard:create-user ...
```

Or direct database / phpMyAdmin access.

### Cache busting

Dashboard scripts use query strings (`?v=9`) on script tags in Blade `@push('scripts')`. Bump version after JS changes.

---

## Quick reference — file to feature map

| Feature | Primary files |
|---------|----------------|
| Routes | `routes/web.php` |
| Exam CRUD | `app/Http/Controllers/Api/ExamController.php` |
| Attempts / violations | `app/Http/Controllers/Api/AttemptController.php` |
| Live proctoring API | `app/Http/Controllers/Api/ProctoringController.php` |
| Professor UI | `resources/views/pages/professor.blade.php`, `public/js/professor*.js` |
| Student UI | `resources/views/pages/student.blade.php`, `public/js/student.js` |
| Take exam | `resources/views/pages/take-exam.blade.php`, `public/js/take-exam.js` |
| Face / tab monitoring | `public/js/monitoring.js` |
| Settings UI | `partials/*-settings.blade.php`, `settings-shared.js` |
| Models | `app/Models/*.php` |

---

*Last updated: June 2026 — matches commit `5fa7da2` and Laravel 13 codebase.*
