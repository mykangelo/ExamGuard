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
9. [AJAX and JSON](#9-ajax-and-json)
10. [Frontend architecture](#10-frontend-architecture)
11. [Proctoring system](#11-proctoring-system)
12. [Exam lifecycle](#12-exam-lifecycle)
13. [Key code flows](#13-key-code-flows)
14. [Configuration](#14-configuration)
15. [Testing](#15-testing)
16. [Legacy folder](#16-legacy-folder)
17. [Deployment notes](#17-deployment-notes)
18. [InfinityFree (free hosting)](#18-infinityfree-free-hosting)

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
│   ├── Support/              # PublicStorageUrl helper
│   ├── Providers/
│   └── Services/             # Business logic (notifications)
├── bootstrap/app.php         # Middleware aliases, app bootstrap
├── config/                   # Laravel + proctoring.php
├── database/
│   ├── migrations/           # Schema evolution
│   └── seeders/
├── public/
│   ├── js/                   # Frontend application scripts
│   ├── storage/
│   │   ├── serve.php         # Serves uploads when storage:link unavailable
│   │   └── .htaccess
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
| `public/js/api-client.js` | Central `ExamGuardApi` fetch wrapper — see [§9](#9-ajax-and-json) |
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

- **Monolithic Laravel app** — pages and JSON API share authentication and models. Client-server contract is documented in [§9 AJAX and JSON](#9-ajax-and-json).
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
| **Settings** | Profile photo, password, notifications, workspace defaults (`professor-settings.js` binds UI to `#settingsView`) |
| **Help** | FAQ-style help content |

**Sidebar live widget** (`professor-sidebar-live-widget.js`) — shows active exam when students have recent heartbeats.

### Student dashboard (`/student`)

| View | Purpose |
|------|---------|
| **Home** | Upcoming exams, live banner, activity stream, quick actions |
| **Calendar** | Month/week schedule with exam pills |
| **Exam room** | Enter exam key to access unassigned exams |
| **Results** | Submitted attempt scores |
| **Settings** | Profile photo, password, notifications (`student-settings.js`, `settings-shared.js`) |
| **Class** | Per-class exam list |

**Student notifications** (`student-notifications.js`): in-app bell for `exam_assigned`, `class_joined`, `exam_deleted`, `class_deleted`. Joining a class shows a toast (not `alert()`) and creates a `class_joined` notification.

**Violation lock:** when `warning_count >= warning_limit` on an unsubmitted attempt, the exam shows **Violations exceeded** on the dashboard and API blocks re-entry (`403`, `code: violation_exceeded`). See [§11 Proctoring](#11-proctoring-system).

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

## 9. AJAX and JSON

ExamGuard uses **AJAX** in the classic sense: the browser calls Laravel `/api/*` routes with **`fetch()`**, receives **JSON**, and updates the page **without a full reload**. There is no jQuery, Axios, or React data layer — one central wrapper handles almost everything.

### How AJAX is handled and used

**The end-to-end pattern:**

```
Blade page loads (HTML)  →  JS calls ExamGuardApi.*()  →  fetch("/api/...")  →  Laravel returns JSON  →  JS updates the DOM
```

ExamGuard is **not** a single-page application framework app (no React/Vue router). It uses a **hybrid model**:

| Phase | What happens |
|-------|----------------|
| **First paint** | Blade renders the layout, tables, forms, and inline CSS. Some data is embedded server-side (e.g. professor exam table on first load). |
| **After load** | JavaScript calls `/api/*` to load or change data. The DOM is updated from JSON — no full page reload for most dashboard actions. |
| **Mutations** | Create, update, delete, submit, and proctoring events are sent as JSON POST/PUT/DELETE requests. |
| **Polling** | Live professor views and exam heartbeats re-call the API on a timer (no WebSockets). |

All API traffic is **same-origin AJAX** (`fetch`) with the **Laravel session cookie** (not JWT).

**What we deliberately do *not* use for API calls:** jQuery `$.ajax`, Axios, Inertia, Livewire, or a separate API server. The only exceptions are a few **raw `fetch` calls** for email resend and session sanity checks (see [Where AJAX is used](#where-ajax-is-used-by-feature)).

### Mental model (five rules)

1. **One client** — `window.ExamGuardApi` in `public/js/api-client.js` wraps every authenticated call.
2. **One auth model** — session cookie + `X-CSRF-TOKEN` header (no bearer tokens).
3. **One data format** — JSON request bodies and JSON responses (`Content-Type` / `Accept: application/json`).
4. **One route file** — `routes/web.php` maps `/api/*` to `app/Http/Controllers/Api/*.php`.
5. **Per-feature DOM updates** — each JS file (`student.js`, `take-exam.js`, etc.) owns rendering after JSON returns; there is no global state store.

### Architecture diagram

```
┌──────────────────────────────────────────────────────────────────┐
│ Browser                                                           │
│                                                                   │
│  Blade page (HTML)          public/js/*.js                        │
│       │                            │                              │
│       │  first paint               │  ExamGuardApi.request()      │
│       └────────────────────────────┼──► fetch("/api/...")         │
│                                    │     credentials: same-origin │
│                                    │     X-CSRF-TOKEN header      │
│                                    │     Accept: application/json │
└────────────────────────────────────┼──────────────────────────────┘
                                     │ HTTP
┌────────────────────────────────────▼──────────────────────────────┐
│ Laravel  routes/web.php  →  app/Http/Controllers/Api/*.php        │
│          response()->json([...])  ←  Model::*Array() serializers │
└──────────────────────────────────────────────────────────────────┘
```

### Central API client (`public/js/api-client.js`)

Almost every authenticated AJAX call goes through **`window.ExamGuardApi`**.

| Mechanism | Detail |
|-----------|--------|
| Transport | Native **`fetch()`** (no jQuery, no Axios) |
| Auth | `credentials: "same-origin"` — sends session cookie |
| CSRF | `X-CSRF-TOKEN` from `<meta name="csrf-token">` in `layouts/app.blade.php` |
| Request body | `Content-Type: application/json` + `JSON.stringify(payload)` for POST/PUT/DELETE |
| Response | `Accept: application/json`; body parsed with `response.json()` |
| Errors | Non-2xx → thrown `Error` with `.status`, `.code`, `.errors`, optional `.exam`, lockout fields |
| File upload | `uploadRequest()` — `FormData` for avatar only (no `Content-Type` header; browser sets multipart boundary) |

Core `request()` helper:

```javascript
async function request(path, options = {}) {
  const response = await fetch(path, {
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-CSRF-TOKEN": csrfToken(),
      ...(options.headers || {}),
    },
    ...options,
  });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) throw enrichedError(result);
  return result;
}
```

`ExamGuardApi` exposes one method per endpoint (login, `exams()`, `submitExam()`, `liveSessions()`, etc.). See [§7 API reference](#7-api-reference) for the full route list.

### Calling the API from JavaScript

Every feature script imports nothing — it calls methods on the global `ExamGuardApi` object.

**Read data (GET):**

```javascript
const { exams } = await ExamGuardApi.exams();
const { user } = await ExamGuardApi.me();
const { exam } = await ExamGuardApi.exam(examId);
```

**Write data (POST / PUT / DELETE):**

```javascript
await ExamGuardApi.joinClass(classCode);
await ExamGuardApi.createExam(payload);
await ExamGuardApi.submitExam(examId, { answers, startedAt });
```

POST and PUT bodies are built with `JSON.stringify(...)` inside `api-client.js` — callers pass plain JavaScript objects.

**Parallel load (student dashboard):**

```javascript
// student.js — loadDashboard()
const [{ user }, { classes }, { exams }] = await Promise.all([
  ExamGuardApi.me(),
  ExamGuardApi.classes(),
  ExamGuardApi.exams(),
]);
state.user = user;
state.classes = classes || [];
state.exams = exams || [];
// then render home, calendar, results from state
```

**Indirect API call (proctoring):**

`monitoring.js` does not call `fetch` directly. During an exam, `take-exam.js` sets `window.ExamGuardSession`, and monitoring code calls:

```javascript
await window.ExamGuardSession.reportViolation({
  type: 'tab_switch',
  snapshot: base64Image,
  occurredAt: new Date().toISOString(),
});
```

That wrapper internally calls `ExamGuardApi.reportViolation(...)`.

### Error handling in the UI

When the server returns a non-2xx status, `request()` throws a standard `Error` enriched with API fields:

| Property | Meaning |
|----------|---------|
| `error.message` | User-facing text (SQL errors are sanitized to a generic message) |
| `error.status` | HTTP status code (401, 403, 409, 429, …) |
| `error.errors` | Laravel validation object `{ field: ["message"] }` — first field message is shown |
| `error.locked_out`, `error.retry_after` | Login brute-force lockout (`login.js` shows countdown) |
| `error.needs_verification`, `error.email` | Unverified email — redirect to verify flow |

UI scripts catch these and surface them via `ExamGuardDialog` (alerts/toasts), inline form errors, or empty-state messages. Example from `professor-exams.js`:

```javascript
try {
  await ExamGuardApi.deleteExam(examId);
  dialog()?.toast('Exam deleted.', 'success');
} catch (error) {
  await dialog()?.alert({
    type: 'error',
    title: 'Unable to delete',
    message: error.message || 'This exam could not be deleted.',
  });
}
```

### Typical AJAX flows by area

#### Session guard (every dashboard page)

`auth-guard.js` runs immediately when professor or student Blade loads:

```javascript
try {
  const { user } = await ExamGuardApi.me();
  if (requiredRole && user.role !== requiredRole) redirectForRole(user.role);
} catch {
  location.replace('/login');
}
```

If the session cookie is missing or expired, the user never sees dashboard data — they go to login.

#### Student dashboard — mostly AJAX-driven

Unlike the professor exams table, the **student UI loads almost all data via AJAX** on init. `student.js` → `loadDashboard()` fetches user, classes, and exams in parallel, then renders home, calendar, class views, and results from in-memory `state`. Joining a class or entering an exam key triggers additional POST requests without reloading the page.

#### Professor dashboard — mixed (Blade + AJAX)

| Concern | Approach |
|---------|----------|
| Initial exam table | **Server-rendered** — `PageController::professor()` passes `$exams` to Blade |
| Row actions (duplicate, delete, schedule, close) | **AJAX** — `professor-exams.js`; DOM row removed or page reloaded after delete |
| Create / edit exam | **AJAX** — `create-exam.js` sends nested exam JSON to `POST` / `PUT /api/exams` |
| Classes and assignments | **AJAX** — `professor-classes.js` |
| Live sessions + sidebar widget | **AJAX polling every 4s** — `ExamGuardApi.liveSessions()` |
| Notifications | **AJAX on demand** — when user opens the bell panel |

#### Take exam — continuous AJAX

`take-exam.js` drives the full exam session over the API:

1. `GET /api/exams/{id}` — questions + existing `in_progress` attempt (resume skips preflight).
2. `POST .../attempts/start` — create or resume attempt; sets `window.ExamGuardSession`.
3. `POST .../heartbeat` — every **20 seconds** while the exam runs (keeps professor live view accurate).
4. `POST .../violations` — proctoring events from `monitoring.js`.
5. `POST .../attempts` — submit `{ answers, startedAt }`; show success modal → redirect to results.

#### Auth pages

`login.js` and `register.js` POST credentials via `ExamGuardApi.login()` / `register()`, then `location.replace()` to the role dashboard on success. No dashboard data is pre-fetched on those pages.

### Server-side JSON (`app/Http/Controllers/Api/`)

All `/api/*` handlers return **`Illuminate\Http\JsonResponse`** via `response()->json(...)`.

| Pattern | Example |
|---------|---------|
| Success wrapper | `{ "exams": [...] }`, `{ "user": {...} }`, `{ "attempt": {...} }` |
| Simple ack | `{ "ok": true }` |
| Error | `{ "error": "Human-readable message." }` with 4xx/5xx status |
| Validation | Laravel validation → `{ "message": "...", "errors": { "field": ["..."] } }` — client reads first field error |
| Auth extras | Login may return `locked_out`, `retry_after`, `needs_verification`, `email` |

**Role middleware** (`EnsureRole`) returns JSON for API routes:

```php
if ($request->expectsJson() || $request->is('api/*')) {
    return response()->json(['error' => 'Not authorized.'], 403);
}
```

**Exception rendering:** `bootstrap/app.php` renders validation/auth failures as JSON when the path is `api/*` or the request `expectsJson()`.

Controllers do **not** return raw Eloquent models. They use explicit serializers on models:

| Method | Model | Used for |
|--------|-------|----------|
| `toAuthArray()` | `User` | Login, `/api/auth/me`, profile updates |
| `toProfessorArray()` | `Exam` | Professor exam list/detail (includes correct answers) |
| `toStudentArray()` | `Exam` | Student exam list/detail (hides answers until submit) |
| `toArrayWithAnswers()` | `Question` | Professor question payload |
| `toArrayForStudent()` | `Question` | Student take-exam payload |
| `attemptPayload()` | `ExamAttempt` | Start session, submit, heartbeat context |
| `toArray()` | `ViolationEvent` | Violation reports and professor feeds |

### JSON stored in MySQL

Some columns hold JSON blobs; Eloquent **casts** them to PHP arrays, and API serializers expose them as JSON fields:

| Column | Table | Purpose |
|--------|-------|---------|
| `preferences` | `users` | Notification and UI preferences from settings |
| `answers` | `exam_attempts` | Submitted multiple-choice indices |
| `proctoring_triggers_json` | `exams` | Per-exam proctoring trigger config |
| `meta_json` | `violation_events` | Optional client metadata on violations |

### Client-only JSON (`sessionStorage`)

Not sent to the server unless saved via an API call:

| Key / usage | File | Purpose |
|-------------|------|---------|
| `examguard:create-exam-work` | `create-exam.js` | Draft exam builder state between steps |
| `examguard:pending-exam-key` | `student.js` | Exam key entered before dashboard load completes |
| `examguard:take-exam-id` | `route-state.js` | Preserve `examId` across navigation |
| Professor view boot | `professor.js` | Last active sidebar view |

### Where AJAX is used (by feature)

#### Authentication (guest pages)

| File | Endpoints | Behavior |
|------|-----------|----------|
| `login.js` | `POST /api/auth/login` | JSON body `{ email, password, role, website }`; redirects on success |
| `register.js` | `POST /api/auth/register` | Same pattern; may return `needs_verification` |
| `login.js`, `register.js`, `verify-email.blade.php` | `POST /api/email/resend` | **Raw `fetch`** (not `ExamGuardApi`) — resend verification email |

#### Protected shell (all dashboards)

| File | Endpoints | Behavior |
|------|-----------|----------|
| `auth-guard.js` | `GET /api/auth/me` | Runs on load; redirects to `/login` if session invalid |
| `professor.js`, `student.js` | `POST /api/auth/logout` | Logout then redirect |

#### Professor dashboard

| File | Endpoints | Behavior |
|------|-----------|----------|
| `PageController::professor()` | *(none on first load)* | **Server-rendered** exam table HTML from Blade |
| `professor-exams.js` | `duplicate`, `delete`, `schedule`, `close` | Row actions; DOM updated or page reload after delete |
| `create-exam.js` | `GET/POST/PUT /api/exams`, `GET /api/classes` | Full exam CRUD payload as nested JSON (title, questions, choices, proctoring settings) |
| `professor-classes.js` | `professor/classes`, `classes`, `assignments` | Load classes + exams; create class; assign exam |
| `professor-live-sessions.js` | `GET /api/professor/live-sessions`, `attempts/{id}/violations` | **Poll every 4s** while view active |
| `professor-sidebar-live-widget.js` | `GET /api/professor/live-sessions` | **Poll every 4s** in sidebar |
| `professor-proctoring.js` | `GET /api/professor/violations` | Violation records list (filter by severity query param) |
| `professor-notifications.js` | `GET/PUT /api/professor/notifications` | Load on panel open; mark read |
| `professor-settings.js`, `settings-shared.js` | `me`, `profile`, `password`, `preferences`, `avatar`, `logout-all`, `account` | Settings forms |

#### Student dashboard

| File | Endpoints | Behavior |
|------|-----------|----------|
| `student.js` | `me`, `classes`, `exams` | **`loadDashboard()`** — `Promise.all` on page load; renders home, calendar, results from JSON |
| `student.js` | `POST /api/classes/join` | Join class by code |
| `student.js` | `POST /api/exams/access-by-key` | Exam room key entry |
| `student-notifications.js` | `GET/PUT /api/student/notifications` | Load on panel open |
| `student-settings.js` | Same auth endpoints as professor settings | Profile / password / preferences |

#### Take exam

| File | Endpoints | Behavior |
|------|-----------|----------|
| `take-exam.js` | `GET /api/exams/{id}` | Load exam + questions + existing `in_progress` attempt |
| `take-exam.js` | `POST .../attempts/start` | Start or resume session |
| `take-exam.js` | `POST .../heartbeat` | **Every 20s** while exam active |
| `take-exam.js` | `POST .../violations` | Proctoring events from `monitoring.js` via `ExamGuardSession.reportViolation()` |
| `take-exam.js` | `POST .../attempts` | Submit `{ answers: [...], startedAt }` |
| `take-exam.js` | `GET /api/auth/me`, `GET /` | **Raw `fetch`** — session sanity check before starting |
| `monitoring.js` | *(indirect)* | Calls `window.ExamGuardSession.reportViolation({ type, snapshot, ... })` — no direct `fetch` |

### Polling and recurring AJAX

Real-time features use **short polling**, not WebSockets or Server-Sent Events:

| Interval | File | Endpoint | Purpose |
|----------|------|----------|---------|
| 4s | `professor-live-sessions.js`, `professor-sidebar-live-widget.js` | `GET /api/professor/live-sessions` | Live student sessions (heartbeat window ~18s server-side) |
| 20s | `take-exam.js` | `POST .../heartbeat` | Keep attempt alive for professor live view |
| On demand | `student-notifications.js`, `professor-notifications.js` | notifications APIs | When user opens notification panel |
| 1s | `take-exam.js` | *(local only)* | Exam countdown timer — **not** an API call |

### What is *not* AJAX

These parts of the app do **not** load or save data through `/api/*`:

| Area | How it works instead |
|------|----------------------|
| Marketing pages (`/`, `/tour`, `/pricing`, …) | Static Blade + Vite CSS only |
| Professor exam table (first visit) | PHP renders rows in Blade from `PageController` |
| Exam countdown during take-exam | Local `setInterval` in `take-exam.js` |
| Draft exam builder mid-step | `sessionStorage` until user saves via `createExam()` |
| CSS, fonts, MediaPipe CDN | Static assets — not API calls |

Login and register **submit** via AJAX but do not pre-load API data before the user acts.

### Example JSON payloads

**Create / update exam** (`create-exam.js` → `POST|PUT /api/exams`):

```json
{
  "title": "Midterm",
  "instructions": "...",
  "timeLimit": 60,
  "warningLimit": 3,
  "maxWarningAction": "auto_submit",
  "proctoringTriggers": { "tab_switch": true, "no_face": true },
  "questions": [
    {
      "prompt": "Question text",
      "choices": ["A", "B", "C", "D"],
      "correctChoice": 2
    }
  ]
}
```

**Submit attempt** (`take-exam.js` → `POST /api/exams/{id}/attempts`):

```json
{
  "answers": [0, 2, 1, 3],
  "startedAt": "2026-06-29T14:00:00.000Z"
}
```

**Report violation** (`monitoring.js` → `POST .../violations`):

```json
{
  "type": "tab_switch",
  "severity": "minor",
  "message": "Tab switch detected",
  "snapshot": "data:image/jpeg;base64,...",
  "occurredAt": "2026-06-29T14:05:00.000Z"
}
```

**Response** includes authoritative server count:

```json
{
  "event": { "id": 12, "type": "tab_switch", "severity": "minor", "message": "..." },
  "warningCount": 2
}
```

### Legacy reference

`legacy/api-client.js` and `legacy/server.js` used the same **`fetch` + `JSON.stringify`** pattern against a Node SQLite server. The current Laravel app mirrors that contract under `/api/*`.

---

## 10. Frontend architecture

> For AJAX/JSON transport, serializers, polling, and per-file API usage, see [§9 AJAX and JSON](#9-ajax-and-json).

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

### API client (summary)

All HTTP calls use `window.ExamGuardApi` in `public/js/api-client.js`. See [§9](#9-ajax-and-json) for headers, error handling, upload flow, and endpoint mapping.

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

## 11. Proctoring system

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
6. At max warnings: sets attempt `status` to `disconnected` and blocks further session activity.
7. Returns updated `warningCount` to client.

### Max warnings & violation lock

Configured per exam (`warning_limit`, `max_warning_action`). When `warning_count >= warning_limit`:

| Layer | Behavior |
|-------|----------|
| **Server** | `ExamAttempt::isViolationLocked()` — true for unsubmitted attempts at/above limit |
| **API** | `AttemptController::start()`, `ExamController::show()`, `accessByKey()` return `403` + `code: violation_exceeded` |
| **Student UI** | No live banner / “Enter now”; stream shows **Violations exceeded**; `goToExam()` blocked |
| **Take exam** | `renderExam()` and resume path show blocked screen |

Client shows escalation UI during the session; on max warnings the session ends per `max_warning_action` (disconnect / notify).

### Demo mode

`config/proctoring.php` → `PROCTORING_DEMO_MODE=true` shows disclaimer that browser monitoring is assistive only.

---

## 12. Exam lifecycle

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

## 13. Key code flows

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
StudentNotificationService::notifyExamAssigned()   — exam assigned to class
StudentNotificationService::notifyClassJoined()    — student joins class (new enrollment)
StudentNotificationService::notifyClassDeleted() — professor deletes class
  → creates student_notifications rows
  → student-notifications.js loads GET /api/student/notifications
  → join class also shows ExamGuardDialog.toast() on student dashboard
```

Notification types: `exam_assigned`, `class_joined`, `exam_deleted`, `class_deleted`.

---

## 14. Configuration

### Environment (`.env`)

| Variable | Purpose |
|----------|---------|
| `DB_*` | MySQL connection |
| `APP_URL` | Application URL |
| `PROCTORING_DEMO_MODE` | Show demo proctoring disclaimer |
| `SEED_PROFESSOR_PASSWORD` | Override seed professor password |
| `SEED_STUDENT_PASSWORD` | Override seed student password |
| `MAIL_*` | SMTP for email verification (Brevo / SendGrid) — see [docs/SMTP.md](docs/SMTP.md) |

### Email verification (SMTP)

Registration and login require a verified email. Configure **Brevo** or **SendGrid** SMTP in production:

```powershell
php artisan examguard:test-mail you@example.com
```

Full provider setup: **[docs/SMTP.md](docs/SMTP.md)**.

### Files

| File | Purpose |
|------|---------|
| `config/database.php` | DB drivers |
| `config/session.php` | Session driver (file/database) |
| `config/proctoring.php` | Proctoring demo flag |
| `config/filesystems.php` | `public` disk for avatars, snapshots |

### Storage

**Local development** (symlink):

```powershell
php artisan storage:link
```

Links `public/storage` → `storage/app/public` for avatars and violation snapshots.

**Production without symlinks** (InfinityFree, etc.):

| File | Role |
|------|------|
| `app/Support/PublicStorageUrl.php` | `PublicStorageUrl::for($path)` → `asset('storage/'.$path)` |
| `public/.htaccess` | `RewriteRule ^storage/(.*)$ storage/serve.php?path=$1` when file not on disk |
| `public/storage/serve.php` | Streams files from `storage/app/public/` with correct MIME type |

`User::toAuthArray()` exposes `avatarUrl`. `ViolationEvent::toArray()` exposes `snapshotUrl`.

Do **not** use Apache `RewriteRule` to `../../storage/app/public/` on InfinityFree — it returns HTTP 500.

### Settings UI (`settings-shared.js`)

Shared helpers used by professor and student settings:

| Helper | Purpose |
|--------|---------|
| `bindPasswordToggles()` | Show/hide password fields (eye icon) |
| `bindAvatarUpload()` | Click avatar → file picker → `POST /api/auth/avatar` |
| `renderAvatarButton()` | Settings dialog avatar preview |
| `renderNavAvatarButton()` | Top-nav circular avatar |
| `bindDangerZone()` | Log out all devices, delete account |

Professor settings bind to `#settingsView` (profile was merged into Settings tab).

---

## 15. Testing

```powershell
php artisan test
# or
composer test
```

Feature tests live in `tests/Feature/`. Example: `ProctoringWarningCountTest.php` verifies warning count is derived from violation events at submit time.

---

## 16. Legacy folder

`legacy/` contains the original **Node.js + SQLite + static HTML** prototype:

- `legacy/app.js`, `legacy/api-client.js`, `legacy/*.html`

Not used at runtime. Kept for reference when comparing old vs Laravel implementation.

---

## 17. Deployment notes

### Minimum production checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`.
2. Configure real MySQL credentials.
3. Run `php artisan migrate --force`.
4. Run `npm run build` and `php artisan config:cache`.
5. Point web server document root to `public/`.
6. Enable HTTPS for camera/mic permissions.
7. Run `php artisan storage:link` (or deploy `public/storage/serve.php` on hosts without symlinks).
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

## 18. InfinityFree (free hosting)

ExamGuard can be deployed to **InfinityFree** (free PHP 8.3 + MySQL) by building locally and uploading via FTP. InfinityFree has no SSH, no `artisan`, no symlinks, and no foreign keys on MySQL — the repo includes scripts and config to work around that.

**Full guide:** [INFINITYFREE.md](INFINITYFREE.md)

Quick start:

```powershell
.\scripts\build-infinityfree.ps1
php artisan key:generate --show
# Configure dist/infinityfree/.env from .env.infinityfree.example
php artisan migrate:fresh --seed
# Export DB, then:
.\scripts\strip-fk-from-sql.ps1 -InputFile database\examguard-export.sql -OutputFile database\examguard-infinityfree.sql
# Import SQL in InfinityFree phpMyAdmin, FTP upload dist/infinityfree/* to htdocs/
```

| Repo file | Purpose |
|-----------|---------|
| `INFINITYFREE.md` | Step-by-step deployment guide |
| `scripts/build-infinityfree.ps1` | Build upload package → `dist/infinityfree/` |
| `scripts/strip-fk-from-sql.ps1` | Strip FK constraints for InfinityFree MySQL |
| `deploy/infinityfree/htdocs.htaccess` | Document root → `public/` |
| `deploy/infinityfree/public-storage.htaccess` | Copied to `public/storage/.htaccess` |
| `public/storage/serve.php` | Serve uploads without symlinks |
| `deploy/infinityfree/fix-auto-increment.sql` | Fix `AUTO_INCREMENT` after phpMyAdmin import |
| `.env.infinityfree.example` | Production env template (`QUEUE_CONNECTION=sync`, etc.) |

---

## Quick reference — file to feature map

| Feature | Primary files |
|---------|----------------|
| Routes | `routes/web.php` |
| API / AJAX | `public/js/api-client.js`, `app/Http/Controllers/Api/*.php` — [§9](#9-ajax-and-json) |
| Exam CRUD | `app/Http/Controllers/Api/ExamController.php` |
| Attempts / violations | `app/Http/Controllers/Api/AttemptController.php` |
| Live proctoring API | `app/Http/Controllers/Api/ProctoringController.php` |
| Professor UI | `resources/views/pages/professor.blade.php`, `public/js/professor*.js` |
| Student UI | `resources/views/pages/student.blade.php`, `public/js/student.js` |
| Take exam | `resources/views/pages/take-exam.blade.php`, `public/js/take-exam.js` |
| Face / tab monitoring | `public/js/monitoring.js` |
| Settings UI | `partials/*-settings.blade.php`, `settings-shared.js`, `professor-settings.js`, `student-settings.js` |
| Public file URLs | `app/Support/PublicStorageUrl.php`, `public/storage/serve.php` |
| Models | `app/Models/*.php` |

---

*Last updated: June 2026 — matches commit `5fa7da2` and Laravel 13 codebase.*
