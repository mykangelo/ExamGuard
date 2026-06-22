# ExamGuard database integration

Requires Node.js 22.5 or newer. No third-party packages are required.

## Run

```powershell
npm start
```

Open `http://localhost:3000`. SQLite creates `examguard.db` on first start using `schema.sql`.

Development seed accounts:

- Professor: `professor@examguard.local` / `Professor123!`
- Student: `student@examguard.local` / `Student123!`

Set `SEED_PROFESSOR_PASSWORD` and `SEED_STUDENT_PASSWORD` before first production start. Create additional database users with:

```powershell
npm run create-user -- "Full Name" email@example.com "StrongPassword" student
```

## Integration map

- `schema.sql`: users, sessions, classrooms, enrollments, exams, questions, choices, assignments, and attempts.
- `server.js`: static server, SQLite initialization, authentication/session handling, role authorization, and CRUD APIs.
- `api-client.js`: minimal browser API binding used by the unchanged ExamGuard pages.
- `login.js` and `auth-guard.js`: database login, role routing, protected pages, and logout.
- `create-exam.js`: professor exam/question creation through `/api/exams`.
- `professor-classes.js`: class CRUD and exam assignments.
- `student.js`: authenticated enrollment and assigned-exam retrieval.
- `take-exam.js`: assigned exam loading and server-graded attempt submission.
- `professor.js`: database-backed participation totals and results.

Authentication passwords use `scrypt` with per-user salts. Sessions use random tokens; only token hashes are stored in SQLite. Cookies are HttpOnly and SameSite Strict, with Secure enabled when `NODE_ENV=production`.
