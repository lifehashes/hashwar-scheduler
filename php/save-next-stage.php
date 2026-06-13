<?php
include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';

// Decode the JSON data sent from the frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['participants'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert new entry into 'series'
    /*
    $stmt = $pdo->prepare("INSERT INTO series (designation, type) VALUES (?, ?)");
    $stmt->execute(['Weekly Series ' . date('Y-m-d'), 'WEEKLY']);
    $seriesId = $pdo->lastInsertId();
    */

    // 2. Map glyphs to 'series_participants'
    $stmt = $pdo->prepare("INSERT INTO series_participants 
        (series_id, tournament_id, glyph_name, group_label, phase_id, status) 
        VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($data['participants'] as $p) {
        $stmt->execute([
            $data['series'], 
            0,               // this is the 'dummy' tournament entry - it will be updated later by the hashwar engine
            $p['name'],      // glyph_name
            $p['group'],     // group_label (A-D)
            $p['phase'],     // phase_id
            'active'         // status
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'series_id' => $seriesId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}