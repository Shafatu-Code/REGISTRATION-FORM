<?php
declare(strict_types=1);

/**
 * register.php - server side of the Student Registration form.
 * Author: Yogyu Mohammed
 *
 * GET  register.php?token=1 -> JSON CSRF token
 * POST register.php         -> validate, then insert (JSON answer for script.js,
 *                              plain HTML page if JavaScript is disabled)
 */

session_start();

/* ---- Database settings (XAMPP defaults - change only on your own machine) ---- */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'student_registration';
const DB_USER = 'root';
const DB_PASS = '';

const DEPARTMENTS = [
    'Software Engineering', 'Computer Science', 'Information Systems',
    'Networks and Telecommunications', 'Electrical Engineering', 'Civil Engineering',
];
const LEVELS  = [100, 200, 300, 400, 500];
const GENDERS = ['female', 'male', 'prefer_not_to_say'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Secure connection: exceptions on, real prepared statements. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

/** Send the result as JSON (script.js) or as a simple HTML page (no JavaScript). */
function respond(bool $ok, string $message, array $errors = [], int $status = 200): void
{
    http_response_code($status);

    if (is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['success' => $ok, 'message' => $message, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="card">
    <h1>STUDENT REGISTRATION</h1>
    <div class="alert <?= $ok ? 'alert-success' : 'alert-error' ?>" role="alert"><?= e($message) ?></div>
    <?php if ($errors): ?>
        <ul class="error-list">
            <?php foreach ($errors as $msg): ?><li><?= e((string) $msg) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <p class="footer-link"><a href="index.html">Back to the form</a></p>
</main>
</body>
</html>
    <?php
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function input_str(array $in, string $key): string
{
    $v = $in[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

/** Validate every field. Returns [cleanValues, errors]. */
function validate_registration(array $in): array
{
    $errors = [];
    $clean  = [];

    // Full name
    $name = preg_replace('/\s+/u', ' ', input_str($in, 'full_name')) ?? '';
    $clean['full_name'] = $name;
    if ($name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
    } elseif (!preg_match('/^\p{L}[\p{L} \'.\-]*$/u', $name)) {
        $errors['full_name'] = 'Use letters, spaces, hyphens, apostrophes or dots only.';
    }

    // Email
    $email = mb_strtolower(input_str($in, 'email'));
    $clean['email'] = $email;
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address, e.g. student@example.com.';
    }

    // Phone: Cameroonian mobile, 9 digits starting with 6
    $rawPhone = input_str($in, 'phone');
    $clean['phone'] = $rawPhone;
    $digits = preg_replace('/[\s\-().]/', '', $rawPhone);
    if ($rawPhone === '') {
        $errors['phone'] = 'Phone number is required.';
    } elseif (preg_match('/^(?:\+237|00237|237)?(6\d{8})$/', $digits, $m)) {
        $clean['phone'] = $m[1];
    } else {
        $errors['phone'] = 'Enter a valid phone number: 9 digits starting with 6, e.g. 6XXXXXXXX.';
    }

    // Matriculation number: 2-4 letters + 4-digit year + 3 digits (e.g. SE2026001)
    $matric = strtoupper(input_str($in, 'matric_number'));
    $clean['matric_number'] = $matric;
    if ($matric === '') {
        $errors['matric_number'] = 'Matriculation number is required.';
    } elseif (!preg_match('/^[A-Z]{2,4}(20\d{2})\d{3}$/', $matric, $m)) {
        $errors['matric_number'] = 'Format: 2-4 letters, a 4-digit year, then 3 digits, e.g. SE2026001.';
    } elseif ((int) $m[1] > (int) date('Y')) {
        $errors['matric_number'] = 'The year in the matriculation number cannot be in the future.';
    }

    // Date of birth
    $dobRaw = input_str($in, 'date_of_birth');
    $clean['date_of_birth'] = $dobRaw;
    if ($dobRaw === '') {
        $errors['date_of_birth'] = 'Date of birth is required.';
    } else {
        $dob = DateTimeImmutable::createFromFormat('!Y-m-d', $dobRaw);
        if (!$dob || $dob->format('Y-m-d') !== $dobRaw) {
            $errors['date_of_birth'] = 'Enter a valid date (DD / MM / YYYY).';
        } elseif ($dob > new DateTimeImmutable('today')) {
            $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
        } elseif ($dob < new DateTimeImmutable('1900-01-01')) {
            $errors['date_of_birth'] = 'Date of birth is not realistic. Check the year.';
        }
    }

    // Department
    $dept = input_str($in, 'department');
    $clean['department'] = $dept;
    if ($dept === '') {
        $errors['department'] = 'Select a department.';
    } elseif (!in_array($dept, DEPARTMENTS, true)) {
        $errors['department'] = 'Select a department from the list.';
    }

    // Level
    $level = input_str($in, 'level');
    $clean['level'] = $level;
    if ($level === '') {
        $errors['level'] = 'Select a level.';
    } elseif (!ctype_digit($level) || !in_array((int) $level, LEVELS, true)) {
        $errors['level'] = 'Select a level from the list.';
    }

    // Gender
    $gender = input_str($in, 'gender');
    $clean['gender'] = $gender;
    if ($gender === '') {
        $errors['gender'] = 'Select a gender option.';
    } elseif (!in_array($gender, GENDERS, true)) {
        $errors['gender'] = 'Select one of the listed options.';
    }

    // Password: minimum 8 characters (max 72 for bcrypt), a letter and a number
    $pw  = is_string($in['password'] ?? null) ? $in['password'] : '';
    $pw2 = is_string($in['confirm_password'] ?? null) ? $in['confirm_password'] : '';
    if ($pw === '') {
        $errors['password'] = 'Password is required.';
    } elseif (mb_strlen($pw) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($pw) > 72) {
        $errors['password'] = 'Password must not exceed 72 characters.';
    } elseif (!preg_match('/\p{L}/u', $pw) || !preg_match('/\d/', $pw)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }
    if ($pw2 === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif (!hash_equals($pw, $pw2)) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    $clean['password'] = $pw;   // only used for hashing

    // Accuracy checkbox
    if (($in['confirm_accuracy'] ?? '') !== '1') {
        $errors['confirm_accuracy'] = 'You must confirm that the information is accurate.';
    }

    return [$clean, $errors];
}

function find_duplicates(PDO $pdo, string $email, string $matric): array
{
    $errors = [];
    $st = $pdo->prepare(
        'SELECT email = :e AS dup_email, matric_number = :m AS dup_matric
         FROM students WHERE email = :e2 OR matric_number = :m2'
    );
    $st->execute([':e' => $email, ':m' => $matric, ':e2' => $email, ':m2' => $matric]);
    foreach ($st->fetchAll() as $row) {
        if ($row['dup_email'])  { $errors['email'] = 'This email address is already registered.'; }
        if ($row['dup_matric']) { $errors['matric_number'] = 'This matriculation number is already registered.'; }
    }
    return $errors;
}

/* =============================== ROUTING ================================== */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['token'])) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['token' => csrf_token()]);
        exit;
    }
    header('Location: index.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.', [], 405);
}

// 1. CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    respond(false, 'Your session expired. Please submit the form again.', [], 403);
}

// 2. Server-side validation (nothing is inserted unless this passes)
[$clean, $errors] = validate_registration($_POST);
if ($errors) {
    respond(false, 'Please correct the highlighted fields and try again.', $errors, 422);
}

// 3. Duplicate check + insert with a parameterised query
try {
    $pdo    = db();
    $errors = find_duplicates($pdo, $clean['email'], $clean['matric_number']);
    if ($errors) {
        respond(false, 'These details are already registered.', $errors, 409);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO students
            (full_name, email, phone, matric_number, date_of_birth,
             department, level, gender, password_hash)
         VALUES
            (:full_name, :email, :phone, :matric, :dob,
             :department, :level, :gender, :hash)'
    );
    $stmt->execute([
        ':full_name'  => $clean['full_name'],
        ':email'      => $clean['email'],
        ':phone'      => $clean['phone'],
        ':matric'     => $clean['matric_number'],
        ':dob'        => $clean['date_of_birth'],
        ':department' => $clean['department'],
        ':level'      => (int) $clean['level'],
        ':gender'     => $clean['gender'],
        ':hash'       => password_hash($clean['password'], PASSWORD_DEFAULT),
    ]);

    unset($_SESSION['csrf']);   // token is single-use
    respond(true, 'Registration successful. Welcome, ' . $clean['full_name'] . '!');
} catch (PDOException $ex) {
    if (($ex->errorInfo[1] ?? 0) === 1062) {     // duplicate key (two submissions at once)
        $msg  = $ex->getMessage();
        $dups = [];
        if (str_contains($msg, 'uq_students_email')) {
            $dups['email'] = 'This email address is already registered.';
        } elseif (str_contains($msg, 'uq_students_matric')) {
            $dups['matric_number'] = 'This matriculation number is already registered.';
        }
        respond(false, 'These details are already registered.', $dups, 409);
    }
    error_log('Registration insert failed: ' . $ex->getMessage());   // details only in the log
    respond(false, 'Sorry, we could not save your registration. Please try again later.', [], 500);
} catch (Throwable $ex) {
    error_log('Registration error: ' . $ex->getMessage());
    respond(false, 'Sorry, we could not save your registration. Please try again later.', [], 500);
}
