<?php
session_start();

// 1. Load database configuration
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php'; 

// 2. Load the Calculator class
require_once __DIR__ . '/../php/score_calculator.php';

// Set response header upfront
header('Content-Type: application/json');

// 3. AUTHENTICATION & AUTHORIZATION GUARD
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'UNAUTHENTICATED']);
    exit;
}

$allowed_operators = ['hash0'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['username'], $allowed_operators, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'ACCESS_DENIED // UNAUTHORIZED_OPERATOR']);
    exit;
}

// 4. Execute Calculation
try {
    $calculator = new ScoreCalculator($pdo); 
    $result = $calculator->recalculate(); 

    // Safely extract summary or fallback to entire result object
    $summaryData = is_array($result) && isset($result['summary']) ? $result['summary'] : $result;

    echo json_encode([
        'success' => true, 
        'message' => 'Scores recalculated successfully.',
        'data'    => $summaryData
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}