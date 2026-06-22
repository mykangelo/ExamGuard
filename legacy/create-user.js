const path = require("path");
const fs = require("fs");
const crypto = require("crypto");
const { DatabaseSync } = require("node:sqlite");
const [name, email, password, role] = process.argv.slice(2);
if (!name || !email || !password || !["professor", "student"].includes(role) || password.length < 10) {
  console.error('Usage: npm run create-user -- "Full Name" email@example.com "StrongPassword" professor|student');
  process.exit(1);
}
const db = new DatabaseSync(path.join(__dirname, "examguard.db"));
db.exec(fs.readFileSync(path.join(__dirname, "schema.sql"), "utf8"));
const salt = crypto.randomBytes(16).toString("hex");
const hash = crypto.scryptSync(password, salt, 64).toString("hex");
try { db.prepare("INSERT INTO users (name,email,password_hash,password_salt,role) VALUES (?,?,?,?,?)").run(name.trim(), email.trim().toLowerCase(), hash, salt, role); console.log(`Created ${role}: ${email}`); }
catch (error) { console.error(error.message); process.exitCode = 1; }
