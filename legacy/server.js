const http = require("http");
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const { DatabaseSync } = require("node:sqlite");

const ROOT = __dirname;
const PORT = Number(process.env.PORT || 3000);
const db = new DatabaseSync(path.join(ROOT, "examguard.db"));
db.exec(fs.readFileSync(path.join(ROOT, "schema.sql"), "utf8"));

function hashPassword(password, salt = crypto.randomBytes(16).toString("hex")) {
  return { salt, hash: crypto.scryptSync(password, salt, 64).toString("hex") };
}
function seedUser(name, email, password, role) {
  if (db.prepare("SELECT id FROM users WHERE email = ?").get(email)) return;
  const value = hashPassword(password);
  db.prepare("INSERT INTO users (name,email,password_hash,password_salt,role) VALUES (?,?,?,?,?)").run(name, email, value.hash, value.salt, role);
}
seedUser("ExamGuard Professor", "professor@examguard.local", process.env.SEED_PROFESSOR_PASSWORD || "Professor123!", "professor");
seedUser("ExamGuard Student", "student@examguard.local", process.env.SEED_STUDENT_PASSWORD || "Student123!", "student");

const json = (response, status, body) => { response.writeHead(status, { "Content-Type": "application/json" }); response.end(JSON.stringify(body)); };
const fail = (response, status, message) => json(response, status, { error: message });
function body(request) {
  return new Promise((resolve, reject) => { let data = ""; request.on("data", (chunk) => { data += chunk; if (data.length > 1e6) request.destroy(); }); request.on("end", () => { try { resolve(data ? JSON.parse(data) : {}); } catch { reject(new Error("Invalid JSON.")); } }); });
}
function cookies(request) { return Object.fromEntries((request.headers.cookie || "").split(";").filter(Boolean).map((item) => item.trim().split(/=(.*)/s).slice(0, 2))); }
function currentUser(request) {
  const token = cookies(request).eg_session; if (!token) return null;
  const tokenHash = crypto.createHash("sha256").update(token).digest("hex");
  return db.prepare("SELECT u.id,u.name,u.email,u.role FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at > datetime('now')").get(tokenHash) || null;
}
function requireRole(request, response, role) { const user = currentUser(request); if (!user) { fail(response, 401, "Authentication required."); return null; } if (role && user.role !== role) { fail(response, 403, "Not authorized."); return null; } return user; }
function code() { let value; do { value = crypto.randomBytes(4).toString("hex").slice(0, 6).toUpperCase(); } while (db.prepare("SELECT id FROM classrooms WHERE class_code=?").get(value)); return value; }
function examQuestions(examId, includeAnswers) {
  const questions = db.prepare("SELECT id,position,prompt,explanation,correct_choice FROM questions WHERE exam_id=? ORDER BY position").all(examId);
  return questions.map((question) => ({ id: question.id, question: question.prompt, choices: db.prepare("SELECT choice_text FROM choices WHERE question_id=? ORDER BY position").all(question.id).map((item) => item.choice_text), ...(includeAnswers ? { correctAnswer: question.correct_choice, explanation: question.explanation } : {}) }));
}
function professorExam(exam) { return { id: exam.id, title: exam.title, instructions: exam.instructions, timeLimit: exam.time_limit, warningLimit: exam.warning_limit, questions: examQuestions(exam.id, true) }; }

async function api(request, response, url) {
  const method = request.method;
  if (method === "POST" && url.pathname === "/api/auth/login") {
    const input = await body(request); const user = db.prepare("SELECT * FROM users WHERE email=? AND role=?").get(input.email || "", input.role || "");
    if (!user) return fail(response, 401, "Invalid email, password, or role.");
    const candidate = hashPassword(input.password || "", user.password_salt).hash;
    if (!crypto.timingSafeEqual(Buffer.from(candidate, "hex"), Buffer.from(user.password_hash, "hex"))) return fail(response, 401, "Invalid email, password, or role.");
    const token = crypto.randomBytes(32).toString("hex"); const tokenHash = crypto.createHash("sha256").update(token).digest("hex");
    db.prepare("DELETE FROM sessions WHERE expires_at <= datetime('now')").run();
    db.prepare("INSERT INTO sessions (user_id,token_hash,expires_at) VALUES (?,?,datetime('now','+8 hours'))").run(user.id, tokenHash);
    response.setHeader("Set-Cookie", `eg_session=${token}; HttpOnly; SameSite=Strict; Path=/; Max-Age=28800${process.env.NODE_ENV === "production" ? "; Secure" : ""}`);
    return json(response, 200, { user: { id: user.id, name: user.name, email: user.email, role: user.role } });
  }
  if (method === "POST" && url.pathname === "/api/auth/logout") { const token = cookies(request).eg_session; if (token) db.prepare("DELETE FROM sessions WHERE token_hash=?").run(crypto.createHash("sha256").update(token).digest("hex")); response.setHeader("Set-Cookie", "eg_session=; HttpOnly; SameSite=Strict; Path=/; Max-Age=0"); return json(response, 200, { ok: true }); }
  if (method === "GET" && url.pathname === "/api/auth/me") { const user = requireRole(request, response); if (user) json(response, 200, { user }); return; }

  if (url.pathname === "/api/classes" && method === "GET") {
    const user = requireRole(request, response); if (!user) return;
    const classes = user.role === "professor" ? db.prepare("SELECT id,name,subject,class_code AS code FROM classrooms WHERE professor_id=? ORDER BY created_at DESC").all(user.id) : db.prepare("SELECT c.id,c.name,c.subject,c.class_code AS code FROM classrooms c JOIN enrollments e ON e.classroom_id=c.id WHERE e.student_id=?").all(user.id);
    return json(response, 200, { classes });
  }
  if (url.pathname === "/api/classes" && method === "POST") { const user = requireRole(request, response, "professor"); if (!user) return; const input = await body(request); if (!input.name?.trim() || !input.subject?.trim()) return fail(response, 400, "Name and subject are required."); const result = db.prepare("INSERT INTO classrooms (professor_id,name,subject,class_code) VALUES (?,?,?,?)").run(user.id, input.name.trim(), input.subject.trim(), code()); return json(response, 201, { classroom: db.prepare("SELECT id,name,subject,class_code AS code FROM classrooms WHERE id=?").get(result.lastInsertRowid) }); }
  if (url.pathname === "/api/classes/join" && method === "POST") { const user = requireRole(request, response, "student"); if (!user) return; const input = await body(request); const classroom = db.prepare("SELECT id,name,subject,class_code AS code FROM classrooms WHERE class_code=?").get(String(input.code || "").trim().toUpperCase()); if (!classroom) return fail(response, 404, "Class code not found."); db.prepare("INSERT OR IGNORE INTO enrollments (classroom_id,student_id) VALUES (?,?)").run(classroom.id, user.id); return json(response, 200, { classroom }); }
  const classDelete = url.pathname.match(/^\/api\/classes\/(\d+)$/);
  if (classDelete && method === "DELETE") { const user = requireRole(request, response, "professor"); if (!user) return; db.prepare("DELETE FROM classrooms WHERE id=? AND professor_id=?").run(Number(classDelete[1]), user.id); return json(response, 200, { ok: true }); }

  if (url.pathname === "/api/exams" && method === "GET") {
    const user = requireRole(request, response); if (!user) return;
    if (user.role === "professor") return json(response, 200, { exams: db.prepare("SELECT * FROM exams WHERE professor_id=? ORDER BY created_at DESC").all(user.id).map(professorExam) });
    const rows = db.prepare("SELECT DISTINCT x.* FROM exams x JOIN exam_assignments a ON a.exam_id=x.id JOIN enrollments e ON e.classroom_id=a.classroom_id WHERE e.student_id=? ORDER BY x.created_at DESC").all(user.id);
    const attempts = db.prepare("SELECT exam_id,score,total,warning_count AS warningCount,submitted_at AS submittedAt FROM exam_attempts WHERE student_id=?").all(user.id);
    return json(response, 200, { exams: rows.map((exam) => ({ id: exam.id, title: exam.title, instructions: exam.instructions, timeLimit: exam.time_limit, warningLimit: exam.warning_limit, questionCount: db.prepare("SELECT COUNT(*) count FROM questions WHERE exam_id=?").get(exam.id).count, attempt: attempts.find((item) => item.exam_id === exam.id) || null })) });
  }
  if (url.pathname === "/api/exams" && method === "POST") {
    const user = requireRole(request, response, "professor"); if (!user) return; const input = await body(request);
    if (!input.title?.trim() || !input.instructions?.trim() || !Number.isInteger(input.timeLimit) || input.timeLimit < 1 || !Array.isArray(input.questions) || !input.questions.length) return fail(response, 400, "Complete exam details and at least one question are required.");
    db.exec("BEGIN"); try { const result = db.prepare("INSERT INTO exams (professor_id,title,instructions,time_limit,warning_limit) VALUES (?,?,?,?,?)").run(user.id, input.title.trim(), input.instructions.trim(), input.timeLimit, Number(input.warningLimit) || 3); input.questions.forEach((question, index) => { if (!question.question?.trim() || !Array.isArray(question.choices) || question.choices.length !== 4 || !question.explanation?.trim() || !Number.isInteger(question.correctAnswer)) throw new Error("Each question requires four choices, an answer, and explanation."); const q = db.prepare("INSERT INTO questions (exam_id,position,prompt,explanation,correct_choice) VALUES (?,?,?,?,?)").run(result.lastInsertRowid, index, question.question.trim(), question.explanation.trim(), question.correctAnswer); question.choices.forEach((choice, choiceIndex) => db.prepare("INSERT INTO choices (question_id,position,choice_text) VALUES (?,?,?)").run(q.lastInsertRowid, choiceIndex, String(choice).trim())); }); if (input.classId) { const owned = db.prepare("SELECT id FROM classrooms WHERE id=? AND professor_id=?").get(input.classId, user.id); if (!owned) throw new Error("Class not found."); db.prepare("INSERT INTO exam_assignments (exam_id,classroom_id) VALUES (?,?)").run(result.lastInsertRowid, input.classId); } db.exec("COMMIT"); return json(response, 201, { exam: professorExam(db.prepare("SELECT * FROM exams WHERE id=?").get(result.lastInsertRowid)) }); } catch (error) { db.exec("ROLLBACK"); return fail(response, 400, error.message); }
  }
  const examRoute = url.pathname.match(/^\/api\/exams\/(\d+)$/);
  if (examRoute && method === "GET") { const user = requireRole(request, response, "student"); if (!user) return; const exam = db.prepare("SELECT DISTINCT x.* FROM exams x JOIN exam_assignments a ON a.exam_id=x.id JOIN enrollments e ON e.classroom_id=a.classroom_id WHERE x.id=? AND e.student_id=?").get(Number(examRoute[1]), user.id); if (!exam) return fail(response, 404, "Exam not assigned."); return json(response, 200, { exam: { id: exam.id, title: exam.title, instructions: exam.instructions, timeLimit: exam.time_limit, warningLimit: exam.warning_limit, questions: examQuestions(exam.id, false) } }); }
  if (examRoute && method === "DELETE") { const user = requireRole(request, response, "professor"); if (!user) return; db.prepare("DELETE FROM exams WHERE id=? AND professor_id=?").run(Number(examRoute[1]), user.id); return json(response, 200, { ok: true }); }
  if (url.pathname === "/api/assignments" && method === "POST") { const user = requireRole(request, response, "professor"); if (!user) return; const input = await body(request); const valid = db.prepare("SELECT x.id FROM exams x JOIN classrooms c ON c.professor_id=x.professor_id WHERE x.id=? AND c.id=? AND x.professor_id=?").get(input.examId, input.classId, user.id); if (!valid) return fail(response, 404, "Exam or class not found."); db.prepare("INSERT OR IGNORE INTO exam_assignments (exam_id,classroom_id) VALUES (?,?)").run(input.examId, input.classId); return json(response, 201, { ok: true }); }
  const attemptRoute = url.pathname.match(/^\/api\/exams\/(\d+)\/attempts$/);
  if (attemptRoute && method === "POST") { const user = requireRole(request, response, "student"); if (!user) return; const examId = Number(attemptRoute[1]); const assigned = db.prepare("SELECT x.id FROM exams x JOIN exam_assignments a ON a.exam_id=x.id JOIN enrollments e ON e.classroom_id=a.classroom_id WHERE x.id=? AND e.student_id=?").get(examId, user.id); if (!assigned) return fail(response, 403, "Exam not assigned."); if (db.prepare("SELECT id FROM exam_attempts WHERE exam_id=? AND student_id=?").get(examId, user.id)) return fail(response, 409, "Exam already submitted."); const input = await body(request); const questions = db.prepare("SELECT correct_choice FROM questions WHERE exam_id=? ORDER BY position").all(examId); const answers = Array.isArray(input.answers) ? input.answers : []; const score = questions.reduce((sum, item, index) => sum + (answers[index] === item.correct_choice ? 1 : 0), 0); const result = db.prepare("INSERT INTO exam_attempts (exam_id,student_id,score,total,warning_count,answers_json,started_at) VALUES (?,?,?,?,?,?,?)").run(examId, user.id, score, questions.length, Math.max(0, Number(input.warningCount) || 0), JSON.stringify(answers), input.startedAt || new Date().toISOString()); return json(response, 201, { attempt: { id: result.lastInsertRowid, score, total: questions.length } }); }
  if (url.pathname === "/api/professor/dashboard" && method === "GET") { const user = requireRole(request, response, "professor"); if (!user) return; const summary = { enrolledStudents: db.prepare("SELECT COUNT(DISTINCT e.student_id) count FROM enrollments e JOIN classrooms c ON c.id=e.classroom_id WHERE c.professor_id=?").get(user.id).count, submissions: db.prepare("SELECT COUNT(*) count FROM exam_attempts a JOIN exams x ON x.id=a.exam_id WHERE x.professor_id=?").get(user.id).count, warnings: db.prepare("SELECT COALESCE(SUM(a.warning_count),0) count FROM exam_attempts a JOIN exams x ON x.id=a.exam_id WHERE x.professor_id=?").get(user.id).count }; const attempts = db.prepare("SELECT a.id,u.name studentName,x.title examTitle,a.score,a.total,a.warning_count warningCount,a.submitted_at submittedAt FROM exam_attempts a JOIN users u ON u.id=a.student_id JOIN exams x ON x.id=a.exam_id WHERE x.professor_id=? ORDER BY a.submitted_at DESC").all(user.id); return json(response, 200, { summary, attempts }); }
  if (url.pathname === "/api/professor/classes" && method === "GET") { const user = requireRole(request, response, "professor"); if (!user) return; const classes = db.prepare("SELECT id,name,subject,class_code code FROM classrooms WHERE professor_id=?").all(user.id).map((item) => ({ ...item, students: db.prepare("SELECT u.id,u.name,u.email FROM users u JOIN enrollments e ON e.student_id=u.id WHERE e.classroom_id=?").all(item.id), exams: db.prepare("SELECT x.id,x.title FROM exams x JOIN exam_assignments a ON a.exam_id=x.id WHERE a.classroom_id=?").all(item.id) })); return json(response, 200, { classes }); }
  fail(response, 404, "API endpoint not found.");
}

const mime = { ".html": "text/html; charset=utf-8", ".css": "text/css", ".js": "text/javascript", ".json": "application/json" };
http.createServer(async (request, response) => {
  try { const url = new URL(request.url, `http://${request.headers.host || "localhost"}`); if (url.pathname.startsWith("/api/")) return await api(request, response, url); const relative = url.pathname === "/" ? "index.html" : decodeURIComponent(url.pathname.slice(1)); const target = path.resolve(ROOT, relative); if (!target.startsWith(ROOT) || !fs.existsSync(target) || fs.statSync(target).isDirectory()) return fail(response, 404, "Not found."); response.writeHead(200, { "Content-Type": mime[path.extname(target)] || "application/octet-stream" }); fs.createReadStream(target).pipe(response); } catch (error) { console.error(error); fail(response, 500, "Internal server error."); }
}).listen(PORT, () => console.log(`ExamGuard running at http://localhost:${PORT}`));
