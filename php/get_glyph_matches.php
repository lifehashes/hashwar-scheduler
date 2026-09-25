<?php
header('Content-Type: application/json');

include_once __DIR__ . '/../../../priv/db_conf_laniakea.php';

$glyph = $_GET['glyph'] ?? '';
$tournamentId = $_GET['tournament_id'] ?? '';

if (!$glyph) {
    echo json_encode(['error' => 'Missing glyph parameter']);
    exit;
}

if (!isset($pdo)) {
    echo json_encode(['error' => 'Database connection failed ($pdo not set)']);
    exit;
}

try {
    $glyphParam = urldecode($_GET['glyph'] ?? '');
    $cleanGlyph = trim(preg_replace('/\s+/', ' ', str_replace("\xC2\xA0", ' ', $glyphParam)));

    // Added LEFT JOINs on series_participants to retrieve tactical stance (MODE)
    $stmt = $pdo->prepare("
        SELECT 
            m.id AS match_id,
            m.match_designation,
            m.p1_glyph_name AS p1,
            m.p2_glyph_name AS p2,
            sp1.MODE AS p1_mode,
            sp2.MODE AS p2_mode,
            mr.round_number,
            COALESCE(mr.p1_final_score, 0) AS p1_score,
            COALESCE(mr.p2_final_score, 0) AS p2_score
        FROM matches m
        LEFT JOIN series_participants sp1 
            ON sp1.tournament_id = m.tournament_id 
           AND LOWER(TRIM(sp1.glyph_name)) = LOWER(TRIM(m.p1_glyph_name))
        LEFT JOIN series_participants sp2 
            ON sp2.tournament_id = m.tournament_id 
           AND LOWER(TRIM(sp2.glyph_name)) = LOWER(TRIM(m.p2_glyph_name))
        LEFT JOIN match_rounds mr ON m.id = mr.match_id
        WHERE m.tournament_id = :tournament_id
          AND (
            LOWER(TRIM(m.p1_glyph_name)) = LOWER(:glyph1)
            OR LOWER(TRIM(m.p2_glyph_name)) = LOWER(:glyph2)
          )
        ORDER BY m.id ASC, mr.round_number ASC
    ");

    $stmt->execute([
        ':tournament_id' => $tournamentId,
        ':glyph1'        => $cleanGlyph,
        ':glyph2'        => $cleanGlyph
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matches = [];
    foreach ($rows as $row) {
        $mId = $row['match_id'];
        if (!isset($matches[$mId])) {
            $matches[$mId] = [
                'match_id'    => $mId,
                'designation' => $row['match_designation'],
                'p1'          => $row['p1'],
                'p2'          => $row['p2'],
                'p1_mode'     => $row['p1_mode'] ?? '',
                'p2_mode'     => $row['p2_mode'] ?? '',
                'rounds'      => []
            ];
        }
        if ($row['round_number'] !== null) {
            $matches[$mId]['rounds'][] = [
                'round'    => $row['round_number'],
                'p1_score' => $row['p1_score'],
                'p2_score' => $row['p2_score']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'matches' => array_values($matches)
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}