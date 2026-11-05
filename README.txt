================================================================================
 README - STUDENT REGISTRATION (HTML + CSS + JavaScript + PHP + MySQL)
================================================================================
 Author :BAH SHAFATU YEWONG
 Leve2  : Degree Year 3 - Software Engineering
 Task   : Practical Lab - PHP Form Validation & Database Insertion (20 marks)
 Runs on: XAMPP (Apache + MySQL/MariaDB + PHP 8.0 or newer)

--------------------------------------------------------------------------------
 1. FOLDER CONTENT
--------------------------------------------------------------------------------
 StudentRegistration/
 |-- index.html      the registration form (semantic HTML)            -> Task 1
 |-- css/style.css   responsive styling, mobile + desktop             -> Task 1
 |-- js/script.js    client-side checks + sends the form (POST)
 |-- register.php    SERVER-SIDE validation + secure insert           -> Tasks 2, 4
 |-- database.sql    SQL commands: create database and table          -> Task 3
 |-- summary.html    extension: list + search by matric number
 |-- summary.php     extension: JSON data (non-sensitive fields only)
 |-- README.txt      this file

--------------------------------------------------------------------------------
 2. SETUP ON XAMPP
--------------------------------------------------------------------------------
 1. Start Apache and MySQL in the XAMPP Control Panel.
 2. Copy the whole folder to  C:\xampp\htdocs\
 3. Open http://localhost/phpmyadmin -> "SQL" tab -> paste the content of
    database.sql (or use Import) -> Go.
 4. Open  http://localhost/YogyuMohammed_StudentRegistration/index.html
    (always through http://localhost, never by double-clicking the file,
     otherwise PHP does not run).
 5. If your MySQL user/password are not the XAMPP defaults (root / empty),
    change the DB_* constants at the top of register.php and summary.php.
    Do not submit real passwords.

--------------------------------------------------------------------------------
 3. SQL COMMANDS (also in database.sql)
--------------------------------------------------------------------------------
 CREATE DATABASE IF NOT EXISTS student_registration
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 USE student_registration;
 CREATE TABLE IF NOT EXISTS students (
     id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
     full_name       VARCHAR(100)  NOT NULL,
     email           VARCHAR(150)  NOT NULL,
     phone           CHAR(9)       NOT NULL,
     matric_number   VARCHAR(15)   NOT NULL,
     date_of_birth   DATE          NOT NULL,
     department      VARCHAR(60)   NOT NULL,
     level           SMALLINT UNSIGNED NOT NULL,
     gender          ENUM('female','male','prefer_not_to_say') NOT NULL,
     password_hash   VARCHAR(255)  NOT NULL,
     created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     CONSTRAINT uq_students_email   UNIQUE (email),
     CONSTRAINT uq_students_matric  UNIQUE (matric_number),
     CONSTRAINT chk_students_level  CHECK (level IN (100,200,300,400,500))
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

 Design notes: id = primary key; email and matric_number are UNIQUE (no
 duplicates, even under simultaneous submissions); password_hash stores the
 one-way bcrypt hash, never the password; the "information is accurate"
 checkbox is not stored because the form cannot be sent without it, so every
 row would contain the same value.

--------------------------------------------------------------------------------
 4. VALIDATION RULES (enforced by PHP; JavaScript repeats them for convenience)
--------------------------------------------------------------------------------
 Full name        required, 2-100 chars, letters / spaces / - ' . only
 Email            required, valid format, max 150, stored lower-case
 Phone            required, Cameroonian mobile: 9 digits starting with 6
                  (optional +237 / 237 / 00237 prefix)
 Matric number    required. DOCUMENTED FORMAT: 2-4 capital letters + 4-digit
                  year (2000..current year) + 3 digits, e.g. SE2026001
 Date of birth    required, real date, NOT in the future
 Department       required, must be in the server-side list
 Level            required, one of 100, 200, 300, 400, 500
 Gender           required, female / male / prefer_not_to_say
 Password         required, minimum 8 characters (max 72), a letter + a number
 Confirm password must match
 Accuracy box     must be ticked
 Errors appear under each field. Because the page does not reload, the values
 the user typed are kept (passwords too, only in the browser, never sent back).

--------------------------------------------------------------------------------
 5. SECURE INSERTION
--------------------------------------------------------------------------------
 - Form is sent with HTTP POST; a record is inserted only after ALL checks pass.
 - PDO + exceptions + real prepared statements (parameterised queries).
 - password_hash() / PASSWORD_DEFAULT = secure one-way hash.
 - Duplicate email / matric: checked before the insert AND enforced by UNIQUE
   keys (error 1062 is caught).
 - Success message after insert; a safe generic message if saving fails (the
   real error is only written to the PHP error log).
 - Extras: CSRF token kept in the PHP session, output escaped (htmlspecialchars
   in PHP, textContent in JavaScript).
 - Without JavaScript the form still posts to register.php, which then shows a
   simple result page.

--------------------------------------------------------------------------------
 6. WHY BROWSER-ONLY VALIDATION IS INSUFFICIENT
--------------------------------------------------------------------------------
 HTML attributes and JavaScript run on the user's computer, which the user
 controls. They can be bypassed by disabling JavaScript, editing the page in
 DevTools, or sending a hand-made request (curl, Postman) straight to
 register.php. Browser checks only give quick feedback. Only server-side
 validation is a security boundary: it protects data integrity (invalid dates,
 tampered <select> values, duplicates), blocks malicious input (SQL injection,
 XSS) and treats every client the same.
 To SEE this during the demonstration, open js/script.js and set
     const CLIENT_VALIDATION = false;
 The browser then sends the raw input and the PHP errors are displayed.

--------------------------------------------------------------------------------
 7. DEMONSTRATION CHECKLIST (take a screenshot of each)
--------------------------------------------------------------------------------
 A. VALID: Ada Nfor | ada.nfor@example.com | 677123456 | SE2026001 | 12/05/2004
    | Software Engineering | Level 300 | Female | Secure123 (x2) | box ticked
    -> green success message + one new row in phpMyAdmin.
 B. INVALID 1: submit the empty form -> error under every field.
 C. INVALID 2: email "ada@", phone "12345", matric "2026SE001", future date of
    birth -> field-specific errors.
 D. INVALID 3: password "abc123", confirm "abc124" -> "at least 8 characters"
    and "do not match".
 E. SERVER-SIDE PROOF: set CLIENT_VALIDATION = false and repeat B or C -> the
    same errors now come from PHP.
 F. DUPLICATE: repeat A with the same email and matric number -> "already
    registered" errors, no second row.
 Verify in phpMyAdmin (SQL tab):
    SELECT id, full_name, email, phone, matric_number, department, level,
           gender, created_at, LEFT(password_hash, 7) AS hash_prefix
    FROM students;
 hash_prefix starts with $2y$ = the password is hashed.

--------------------------------------------------------------------------------
 8. EXTENSION CHALLENGE
--------------------------------------------------------------------------------
 summary.html lists name, matric number, department, level and registration
 date (no email, phone, date of birth or password) and supports searching by
 matriculation number (full number or its beginning).

--------------------------------------------------------------------------------
 9. SUBMISSION CHECKLIST
--------------------------------------------------------------------------------
 [ ] This folder (no real passwords or secrets)
 [ ] SQL export of the structure (database.sql, or phpMyAdmin: Export > Structure)
 [ ] Screenshots: form, mobile view, each validation state, success, DB row
 [ ] This README
================================================================================
