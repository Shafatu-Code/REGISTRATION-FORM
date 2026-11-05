<?php
declare(strict_types=1);

/** Extension challenge: JSON list of NON-sensitive student info, searchable by matric number. */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'student_registration';
const DB_USER = 'root';
const DB_PASS = '';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = isset($_GET['q']) && is_string($_GET['q']) ? strtoupper(trim($_GET['q'])) : '';
if (strlen($q) > 15) {
    $q = substr($q, 0, 15);
}

try {
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
    // escape LIKE wildcards typed by the user
    $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $stmt = $pdo->prepare(
        'SELECT full_name, matric_number, department, level, DATE(created_at) AS registered
         FROM students WHERE matric_number LIKE :q
         ORDER BY created_at DESC LIMIT 100'
    );
    $stmt->execute([':q' => $like]);
    echo json_encode(['success' => true, 'rows' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    error_log('Summary query failed: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'The student list is unavailable right now.']);
}
