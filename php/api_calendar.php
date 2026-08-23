<?php
// 1. Session Setup & Database Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
header('Content-Type: application/json');

// Relative path aligned with yearly-workbench.php
require_once __DIR__ . '/../../../priv/db_conf_laniakea.php';

$method = $_SERVER['REQUEST_METHOD'];

// 2. GET Request Handler (Public or Authorized Read)
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, designation, weight_class AS weightClass, start_date AS startDate, end_date AS endDate, color FROM weekly_entries ORDER BY start_date ASC");
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($entries);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database fetch error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. Security Guard for Write Operations (POST / DELETE)
$allowed_operators = ['hash0'];
$is_authorized = isset($_SESSION['username']) && in_array($_SESSION['username'], $allowed_operators, true);

if (!$is_authorized) {
    http_response_code(403);
    echo json_encode(['error' => 'UNAUTHORIZED_OPERATOR']);
    exit;
}

// Robust CSRF Token Retrieval (Checks Apache headers & $_SERVER environment)
$providedToken = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $providedToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
}

if (empty($providedToken) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $providedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'INVALID_CSRF_TOKEN']);
    exit;
}

// 4. POST Request Handler (Create & Update)
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $id          = !empty($data['id']) ? (int)$data['id'] : null;
    $designation = trim($data['designation'] ?? '');
    $weightClass = trim($data['weightClass'] ?? '');
    $startDate   = $data['startDate'] ?? '';
    $endDate     = $data['endDate'] ?? '';
    $color       = $data['color'] ?? '#42f485';

    if (!$designation || !$weightClass || !$startDate || !$endDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        if ($id) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE weekly_entries 
                                   SET designation = ?, weight_class = ?, start_date = ?, end_date = ?, color = ? 
                                   WHERE id = ?");
            $stmt->execute([$designation, $weightClass, $startDate, $endDate, $color, $id]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO weekly_entries (designation, weight_class, start_date, end_date, color) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$designation, $weightClass, $startDate, $endDate, $color]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database operation failed: ' . $e->getMessage()]);
    }
    exit;
}

// 5. DELETE Request Handler
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID specified']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM weekly_entries WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Delete operation failed: ' . $e->getMessage()]);
    }
    exit;
}

// Fallback for unsupported methods
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);