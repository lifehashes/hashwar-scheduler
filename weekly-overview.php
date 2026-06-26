<?php

    include_once __DIR__ . '/../../priv/db_conf_laniakea.php';
    $series_id = isset($_GET['series_id']) && is_numeric($_GET['series_id']) ? (int)$_GET['series_id'] : null;

    /* First Draw (assigning 32 Glyphs to 4 groups) */
    if ($series_id){
        $query = $pdo->prepare("SELECT 
            sp.glyph_name, 
            sp.group_label,
            g.ITERATIONS, 
            g.PEAK, 
            g.BIN, 
            g.HASH,
            g.OWNER
        FROM `series_participants` sp
        INNER JOIN `GLYPHREG` g ON sp.glyph_name = g.BATTLE_NAME
        WHERE sp.series_id = ? AND sp.phase_id = '1'
        ORDER BY sp.id;");
        $query->execute([$series_id]);
        $firstDraw = $query->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // no parameter provided
    }

    /* Phase 1 (Group Phase) */
    if ($series_id){
        $query = $pdo->prepare("SELECT 
            sp.glyph_name, sp.group_label, 
            SUM(
                CASE 
                    WHEN m.p1_glyph_name = sp.glyph_name THEN mr.p1_final_score 
                    WHEN m.p2_glyph_name = sp.glyph_name THEN mr.p2_final_score 
                    ELSE 0 
                END
            ) AS total_score
        FROM series_participants sp
        JOIN matches m ON sp.tournament_id = m.tournament_id 
            AND (sp.glyph_name = m.p1_glyph_name OR sp.glyph_name = m.p2_glyph_name)
        JOIN match_rounds mr ON m.id = mr.match_id
        WHERE sp.series_id = ? AND sp.phase_id='1'
        GROUP BY sp.glyph_name
        ORDER BY sp.group_label, total_score DESC;");
        $query->execute([$series_id]);
        $phase1 = $query->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // no parameter provided
    }

    /* Common data retrieval function for phase 2 and 3 */
    function getTournamentMatches($pdo, $seriesId, $phaseId) {
        $sql = "SELECT 
                    m.tournament_id, 
                    m.id AS match_id,
                    sp.group_label,
                    m.p1_glyph_name, 
                    m.p2_glyph_name,
                    CASE WHEN mr.total_p1 > mr.total_p2 THEN 1 ELSE 0 END AS p1_win,
                    CASE WHEN mr.total_p2 > mr.total_p1 THEN 1 ELSE 0 END AS p2_win,
                    mr.total_p1,
                    mr.total_p2
                FROM `matches` m
                INNER JOIN (
                    SELECT DISTINCT tournament_id, group_label 
                    FROM `series_participants` 
                    WHERE series_id = :series_id AND phase_id = :phase_id
                ) sp ON m.tournament_id = sp.tournament_id
                LEFT JOIN (
                    SELECT 
                        match_id, 
                        SUM(p1_final_score) AS total_p1, 
                        SUM(p2_final_score) AS total_p2
                    FROM `match_rounds`
                    GROUP BY match_id
                ) mr ON m.id = mr.match_id
                ORDER BY sp.group_label ASC, m.id ASC";

        $query = $pdo->prepare($sql);
        $query->execute([
            'series_id' => $seriesId,
            'phase_id'  => $phaseId
        ]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Phase 2 (Redemption Day) */
    if ($series_id){
        $phase2 = getTournamentMatches($pdo, $series_id, '2');
    } else {
        // no parameter provided
    }

    /* Final Draw (assigning the 16 contestants randomly to match brackets) */
    if ($series_id){
         $query = $pdo->prepare("SELECT 
            sp.glyph_name, 
            g.ITERATIONS, 
            g.PEAK, 
            g.BIN, 
            g.HASH,
            g.OWNER
        FROM `series_participants` sp
        INNER JOIN `GLYPHREG` g ON sp.glyph_name = g.BATTLE_NAME
        WHERE sp.series_id = ? AND sp.phase_id = '3'
        ORDER BY sp.id;");
         $query->execute([$series_id]);
         $finalDraw = $query->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // no parameter provided
    }

    /* Phase 3 (Final Tournament) */
    if ($series_id){
        $phase3 = getTournamentMatches($pdo, $series_id, '3');
    } else {
        // no parameter provided
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR // NETWORK CONSOLE</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- PRIMARY ENCOMPASSING OUTER FRAME -->
    <div class="outer-frame">
        
        <!-- HEADER READOUTS -->
        <header>
            <div class="title">Hashwar Series // Week <span id="weekNumber">-</span></div>
            <div id="gamestatus">Weekly Overview (<span id="week-start">dd.mm.yyyy</span> - <span id="week-end">dd.mm.yyyy</span>)</div>
        </header>

        <!-- CONTROL PANEL WORKSPACE -->
        <div class="console-container">
            
            <!-- Hidden State Flags -->
            <input type="radio" name="console-tabs" id="tab-1" class="tab-state" checked>
            <input type="radio" name="console-tabs" id="tab-2" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-3" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-4" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-5" class="tab-state">

            <!-- GRAPHIC RICH FILING CONTAINER LABELS -->
            <div class="tab-headers">
                
                <!-- CARD TAB 1 -->
                <label for="tab-1" class="tab-card">
                    <span class="tab-card-title">GROUP DRAWS</span>
                    <span class="tab-card-meta">Sunday</span>
                    <div class="tab-card-graphic">
                        <svg width="120" height="80" viewBox="0 0 120 80">
                            <rect x="10" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="39" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="68" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="97" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="10" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <rect x="39" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <rect x="68" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <rect x="97" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <path d="M 21 28 L 21 45 M 50 28 L 50 45 M 79 28 L 79 45" stroke="#444" stroke-width="1" fill="none"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 2 -->
                <label for="tab-2" class="tab-card">
                    <span class="tab-card-title">GROUP PHASE</span>
                    <span class="tab-card-meta">Monday - Thursday</span>
                    <div class="tab-card-graphic">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <polygon points="55,10 87,23 100,55 87,87 55,100 23,87 10,55 23,23" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                            <path d="M 55 10 L 100 55 M 55 10 L 55 100 M 55 10 L 10 55 M 87 23 L 87 87 M 87 23 L 23 87 M 100 55 L 10 55 M 23 23 L 87 87" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                            <circle cx="55" cy="10" r="4.5" fill="#42f485"/> <circle cx="87" cy="23" r="4.5" fill="#42f485"/> <circle cx="100" cy="55" r="4.5" fill="#42f485"/> <circle cx="87" cy="87" r="4.5" fill="#f4d042"/> <circle cx="55" cy="100" r="4.5" fill="#f4d042"/> <circle cx="23" cy="87" r="4.5" fill="#f4d042"/> <circle cx="10" cy="55" r="4.5" fill="#f4d042"/> <circle cx="23" cy="23" r="4.5" fill="#ef4444"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 3 -->
                <label for="tab-3" class="tab-card">
                    <span class="tab-card-title">REDEMPTION DAY & FINAL DRAW</span>
                    <span class="tab-card-meta">Friday</span>
                    <div class="tab-card-graphic">
                        <svg width="110" height="95" viewBox="0 0 110 95">

                            <text x="100" y="80" font-size="12" fill="#c0c0c0" font-family="monospace" font-weight="bold" text-anchor="end" opacity="0.8">x4</text>

                            <path d="M 10 15 L 30 15 L 30 30 L 55 30 M 10 45 L 30 45 L 30 30" stroke="rgba(255,255,255,0.2)" stroke-width="1" fill="none"/>
                            <path d="M 100 15 L 80 15 L 80 30 L 55 30 M 100 45 L 80 45 L 80 30" stroke="rgba(255,255,255,0.2)" stroke-width="1" fill="none"/>
                            <path d="M 55 30 L 55 65" stroke="var(--accent-green)" stroke-width="1.5" fill="none"/>
                            
                            <circle cx="10" cy="15" r="3.5" fill="#f4d042"/>
                            <circle cx="10" cy="45" r="3.5" fill="#f4d042"/>
                            <circle cx="100" cy="15" r="3.5" fill="#f4d042"/>
                            <circle cx="100" cy="45" r="3.5" fill="#f4d042"/>
                            
                            <polygon points="55,62 62,75 48,75" fill="#42f485"/>
                            <circle cx="55" cy="82" r="5" fill="#42f485" class="winner-pulse-glow"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 4 -->
                <label for="tab-4" class="tab-card">
                    <span class="tab-card-title">FINAL DRAWS</span>
                    <span class="tab-card-meta">Friday</span>
                    <div class="tab-card-graphic">
                        <svg width="120" height="80" viewBox="0 0 120 80">
                            <rect x="10" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="39" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="68" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="97" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                            <rect x="10" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <rect x="39" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <rect x="68" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <rect x="97" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                            <path d="M 21 28 L 21 45 M 50 28 L 50 45 M 79 28 L 79 45" stroke="#444" stroke-width="1" fill="none"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 5 -->
                <label for="tab-5" class="tab-card">
                    <span class="tab-card-title">CHAMPIONSHIP</span>
                    <span class="tab-card-meta">Saturday</span>
                    <div class="tab-card-graphic">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <path d="M 5 10 L 16 10 L 16 15 L 30 15 M 5 20 L 16 20 L 16 15" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 5 30 L 16 30 L 16 35 L 30 35 M 5 40 L 16 40 L 16 35" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 30 15 L 42 15 L 42 25 L 50 25 M 30 35 L 42 35 L 42 25" stroke="rgba(255,255,255,0.25)" stroke-width="0.9" fill="none"/>
                            
                            <path d="M 5 55 L 16 55 L 16 60 L 30 60 M 5 65 L 16 65 L 16 60" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 5 75 L 16 75 L 16 80 L 30 80 M 5 85 L 16 85 L 16 80" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 30 60 L 42 60 L 42 70 L 50 70 M 30 80 L 42 80 L 42 70" stroke="rgba(255,255,255,0.25)" stroke-width="0.9" fill="none"/>

                            <path d="M 50 25 L 53 25 L 53 45 L 55 45 M 50 70 L 53 70 L 53 45" stroke="rgba(255,255,255,0.25)" stroke-width="1.2" fill="none" opacity="0.8"/>

                            <path d="M 105 10 L 94 10 L 94 15 L 80 15 M 105 20 L 94 20 L 94 15" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 105 30 L 94 30 L 94 35 L 80 35 M 105 40 L 94 40 L 94 35" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 80 15 L 68 15 L 68 25 L 60 25 M 80 35 L 68 35 L 68 25" stroke="rgba(255,255,255,0.25)" stroke-width="0.9" fill="none"/>
                            
                            <path d="M 105 55 L 94 55 L 94 60 L 80 60 M 105 65 L 94 65 L 94 60" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 105 75 L 94 75 L 94 80 L 80 80 M 105 85 L 94 85 L 94 80" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" fill="none"/>
                            <path d="M 80 60 L 68 60 L 68 70 L 60 70 M 80 80 L 68 80 L 68 70" stroke="rgba(255,255,255,0.25)" stroke-width="0.9" fill="none"/>

                            <path d="M 60 25 L 57 25 L 57 45 L 55 45 M 60 70 L 57 70 L 57 45" stroke="rgba(255,255,255,0.25)" stroke-width="1.2" fill="none" opacity="0.8"/>

                            <line x1="55" y1="45" x2="55" y2="92" stroke="var(--accent-green)" stroke-width="1.5"/>

                            <?php for($y = 10; $y <= 40; $y+=10): ?><circle cx="5" cy="<?php echo $y; ?>" r="1.5" fill="var(--accent-green)"/><?php endfor; ?>
                            <?php for($y = 55; $y <= 85; $y+=10): ?><circle cx="5" cy="<?php echo $y; ?>" r="1.5" fill="var(--accent-green)"/><?php endfor; ?>
                            <?php for($y = 10; $y <= 40; $y+=10): ?><circle cx="105" cy="<?php echo $y; ?>" r="1.5" fill="var(--accent-green)"/><?php endfor; ?>
                            <?php for($y = 55; $y <= 85; $y+=10): ?><circle cx="105" cy="<?php echo $y; ?>" r="1.5" fill="var(--accent-green)"/><?php endfor; ?>

                            <polygon points="55,87 61,97 49,97" fill="#42f485"/>
                            <circle cx="55" cy="102" r="4" fill="#42f485" class="winner-pulse-glow"/>
                        </svg>
                    </div>
                </label>

            </div>

            <!-- CENTRAL INTERACTIVE VIEWPORT PANEL -->
            <main class="content-panel">
                
                <!-- Tab Section 1 -->
                <div id="content-1" class="tab-content">
                    <h2>[00 // GROUP DRAWS]</h2>
                    <div class="scaffolding-blueprint">
                        [SYSTEM SYSTEMATICS READY]: 32 Conway Glyphs are randomly selected and assigned to 4 groups
                    </div>
                    <div class="groups-container">
                        <?php foreach (['A', 'B', 'C', 'D'] as $groupLetter): ?>
                            <div class="group-card" id="group-<?php echo $groupLetter; ?>">
                                <h3 class="group-header">
                                    <span>GROUP <?php echo $groupLetter; ?></span>
                                    <span style="font-size: 0.65rem; color: #888;">#</span>
                                </h3>

                                <?php foreach ($firstDraw as $glyph): ?>
                                    <?php 
                                    // Only render this glyph card if its group matches the current loop iteration
                                    // Using strtoupper() ensures case insensitivity (e.g., 'a' matches 'A')
                                    if (strtoupper($glyph['group_label']) !== $groupLetter) {
                                        continue; 
                                    }
                                    ?>
                                    
                                    <div class="glyph-matrix-card" 
                                        data-gens="<?php echo $glyph['ITERATIONS']; ?>" 
                                        data-peak="<?php echo $glyph['PEAK']; ?>"
                                        data-originBin="<?php echo $glyph['BIN']; ?>"
                                        data-originHash="<?php echo $glyph['HASH']; ?>" 
                                        data-name="<?php echo htmlspecialchars($glyph['glyph_name']); ?>">

                                        <div style="display: flex; gap: 10px;">
                                            <div style="flex: 1;">
                                                <div class="glyph-identity">
                                                    <h3><?php echo htmlspecialchars($glyph['glyph_name']); ?></h3>
                                                </div>
                                                <div class="glyph-info-compact">
                                                    <div>Owner: <?php echo htmlspecialchars($glyph['OWNER'] ?? 'SYSTEM'); ?></div>
                                                    <div style="color: var(--accent-green);">
                                                        GENS: <?php echo $glyph['ITERATIONS'] ?? '0'; ?> | PEAK: <?php echo $glyph['PEAK'] ?? '0'; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="glyph-preview" data-pattern='<?php echo $glyph['BIN'] ?? '0'; ?>'>
                                            </div>
                                        </div>

                                    </div>
                                <?php endforeach; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab Section 2 -->
                <div id="content-2" class="tab-content">
                    <h2>[01 // GROUP MATCHES]</h2>
                    <div class="scaffolding-blueprint">
                        [NETWORK MATRIX READY]: Round-Robin Tournaments where every Glyph plays every other Glyphs in the same group twice (56 matches total)
                    </div>
                    <div class="groups-container">
                        <?php foreach (['A', 'B', 'C', 'D'] as $groupLetter): ?>
                            <div class="group-card" id="group-<?php echo $groupLetter; ?>">
                                <h3 class="group-header">
                                    <span>GROUP <?php echo $groupLetter; ?></span>
                                    <span style="font-size: 0.65rem; color: #888;">ROUND-ROBIN</span>
                                </h3>
                                <table class="group-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>GLYPH NAME</th>
                                            <th style="text-align: right;">PTS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                            <tr class="row-advance">
                                                <td>0<?php echo $i; ?></td>
                                                <td>[Awaiting Data]</td>
                                                <td style="text-align: right;" class="stat-val">--</td>
                                            </tr>
                                        <?php endfor; ?>
                                        
                                        <?php for ($i = 4; $i <= 7; $i++): ?>
                                            <tr class="row-redemption">
                                                <td>0<?php echo $i; ?></td>
                                                <td>[Awaiting Data]</td>
                                                <td style="text-align: right;" class="stat-val">--</td>
                                            </tr>
                                        <?php endfor; ?>

                                        <tr class="row-pruned">
                                            <td>08</td>
                                            <td>[Awaiting Data]</td>
                                            <td style="text-align: right;" class="stat-val">--</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab Section 3 -->
                <div id="content-3" class="tab-content">
                    <h2>[02 // REDEMPTION DAY]</h2>
                    <div class="scaffolding-blueprint">
                        [BRACKET ARRAY READY]: 4 Knock-Out Tournaments between Glyphs placed #4 through #7 in each group - winner qualifies for the final tournament
                    </div>
                    <div class="groups-container">
                        <?php foreach (['A', 'B', 'C', 'D'] as $groupLetter): ?>
                            <div class="group-card" style="border-color: rgba(244, 208, 66, 0.2);">
                                <h3 class="group-header" style="color: #f4d042; border-bottom-color: rgba(244, 208, 66, 0.3);">
                                    <span>REDEMPTION BRACKET <?php echo $groupLetter; ?></span>
                                </h3>
                                
                                <div class="bracket-round-header">Semi-Finals</div>
                                <div class="matchup-card" style="margin-bottom: 10px;">
                                    <div class="matchup-slot"><span>4th: [Awaiting Data]</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>7th: [Awaiting Data]</span><span class="score">--</span></div>
                                </div>

                                <div class="matchup-card" style="margin-bottom: 15px;">
                                    <div class="matchup-slot"><span>5th: [Awaiting Data]</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>6th: [Awaiting Data]</span><span class="score">--</span></div>
                                </div>

                                <div class="bracket-round-header">Bracket Final (Winner Advances)</div>
                                <div class="matchup-card">
                                    <div class="matchup-slot"><span>M1 Winner</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>M2 Winner</span><span class="score">--</span></div>
                                    <div class="match-meta">SLOT 1<?php echo (ord($groupLetter) - 65); ?> // FINALS PROJECTION</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab Section 4 -->
                <div id="content-4" class="tab-content">
                    <h2>[03a // FINAL DRAWS]</h2>
                    <div class="scaffolding-blueprint">
                        [SYSTEM SYSTEMATICS READY]: Random draw for the 16 Glyphs that qualified for the Championship
                    </div>
                    <div class="groups-container bracket-draw-view">
                        <?php foreach (['LEFT', 'RIGHT'] as $groupLetter): ?>
                            <div class="group-card" id="group-<?php echo $groupLetter; ?>">
                                <h3 class="group-header">
                                    <span><?php echo $groupLetter; ?> BRACKET</span>
                                    <span style="font-size: 0.65rem; color: #888;">#</span>
                                </h3>

                                <!-- 1. Added $index to track the sequential position (0 to 15) -->
                                <?php foreach ($finalDraw as $index => $glyph): ?>
                                    <?php 
                                    // 2. Logic to separate the sequential items:
                                    // Indices 0-7 belong to LEFT, indices 8-15 belong to RIGHT
                                    if ($groupLetter === 'LEFT' && $index >= 8) {
                                        continue;
                                    }
                                    if ($groupLetter === 'RIGHT' && $index < 8) {
                                        continue;
                                    }
                                    ?>
                                    
                                    <div class="glyph-matrix-card" 
                                        data-gens="<?php echo $glyph['ITERATIONS']; ?>" 
                                        data-peak="<?php echo $glyph['PEAK']; ?>"
                                        data-originBin="<?php echo $glyph['BIN']; ?>"
                                        data-originHash="<?php echo $glyph['HASH']; ?>" 
                                        data-name="<?php echo htmlspecialchars($glyph['glyph_name']); ?>">

                                        <div style="display: flex; gap: 10px;">
                                            <div style="flex: 1;">
                                                <div class="glyph-identity">
                                                    <h3><?php echo htmlspecialchars($glyph['glyph_name']); ?></h3>
                                                </div>
                                                <div class="glyph-info-compact">
                                                    <div>Owner: <?php echo htmlspecialchars($glyph['OWNER'] ?? 'SYSTEM'); ?></div>
                                                    <div style="color: var(--accent-green);">
                                                        GENS: <?php echo $glyph['ITERATIONS'] ?? '0'; ?> | PEAK: <?php echo $glyph['PEAK'] ?? '0'; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="glyph-preview" data-pattern='<?php echo $glyph['BIN'] ?? '0'; ?>'>
                                            </div>
                                        </div>

                                    </div>
                                <?php endforeach; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab Section 5 -->
                <div id="content-5" class="tab-content">
                    <h2>[03b // WEEKLY GRAND CHAMPION FINALS]</h2>
                    <div class="scaffolding-blueprint">
                        [BRACKET ARRAY READY]: The final weekly Knock-Out tournament where the 16 qualifying Glyphs compete
                    </div>
                    <div class="brackets-wrapper" style="display: grid; grid-template-columns: repeat(3, minmax(140px, 190px)) 1fr repeat(3, minmax(140px, 190px)); gap: 10px; align-items: center; width: 100%;">
                
                        <div class="bracket-column">
                            <div class="bracket-round-header">Round of 16 (Left)</div>
                            <?php for ($m = 1; $m <= 4; $m++): ?>
                                <div class="matchup-card" data-index="<?php echo $m-1; ?>">
                                    <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                                    <div class="match-meta">FINALS // MATCH 0<?php echo $m; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: space-around; height: 100%; min-height: 440px;">
                            <div class="bracket-round-header">Quarter-Finals</div>
                            <?php for ($m = 1; $m <= 2; $m++): ?>
                                <div class="matchup-card" data-index="<?php echo $m-1+8; ?>">
                                    <div class="matchup-slot"><span>M<?php echo ($m * 2) - 1; ?> Winner</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>M<?php echo ($m * 2); ?> Winner</span><span class="score">--</span></div>
                                    <div class="match-meta">FINALS // MATCH 0<?php echo 8 + $m; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: center; height: 100%; min-height: 440px;">
                            <div class="bracket-round-header">Semi-Final 1</div>
                            <div class="matchup-card" data-index="<?php echo 12; ?>">
                                <div class="matchup-slot"><span>M09 Winner</span><span class="score">--</span></div>
                                <div class="matchup-slot"><span>M10 Winner</span><span class="score">--</span></div>
                                <div class="match-meta">SEMI 1 // MATCH 13</div>
                            </div>
                        </div>


                        <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: center; gap: 20px; align-items: center; border-left: 1px dashed rgba(255,255,255,0.08); border-right: 1px dashed rgba(255,255,255,0.08); padding: 0 10px; height: 100%; min-height: 460px;">
                            
                            <div style="width: 100%; text-align: center;">
                                <div class="bracket-round-header" style="text-align: center; color: var(--accent-green); letter-spacing: 2px;">Grand Championship</div>
                                <div class="matchup-card" style="border: 1px solid var(--accent-green); box-shadow: 0 0 15px rgba(66, 244, 133, 0.15);" data-index="<?php echo 14; ?>">
                                    <div class="matchup-slot"><span>M13 Champion</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>M14 Champion</span><span class="score">--</span></div>
                                    <div class="match-meta" style="color: var(--accent-green);">FINALS // MATCH 15</div>
                                </div>
                            </div>

                            <div style="width: 100%; text-align: center; opacity: 0.85;">
                                <div class="bracket-round-header" style="text-align: center; color: #f4d042; letter-spacing: 1px;">3rd Place Playoff</div>
                                <div class="matchup-card" style="border: 1px solid rgba(244, 208, 66, 0.4);" data-index="<?php echo 15; ?>">
                                    <div class="matchup-slot"><span>M13 Loser</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>M14 Loser</span><span class="score">--</span></div>
                                    <div class="match-meta" style="color: #f4d042;">BRONZE // MATCH 16</div>
                                </div>
                            </div>
                            
                            <div style="text-align: center; width: 100%;">
                                <span class="profile-label" style="font-size: 0.65rem; letter-spacing: 2px;">PROCLAIMED WEEKLY CHAMPION</span>
                                <div class="seed-display-box winner-pulse-glow" id="weekly-champion-podium" style="color: var(--accent-green); font-size: 1rem; border-color: var(--accent-green); text-transform: uppercase; font-weight: bold; padding: 12px; margin: 5px 0 0 0;">
                                    AWAITING RESOLUTION
                                </div>
                            </div>
                        </div>


                        <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: center; height: 100%; min-height: 440px;">
                            <div class="bracket-round-header" style="text-align: right;">Semi-Final 2</div>
                            <div class="matchup-card" data-index="<?php echo 13; ?>">
                                <div class="matchup-slot"><span>M11 Winner</span><span class="score">--</span></div>
                                <div class="matchup-slot"><span>M12 Winner</span><span class="score">--</span></div>
                                <div class="match-meta">SEMI 2 // MATCH 14</div>
                            </div>
                        </div>

                        <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: space-around; height: 100%; min-height: 440px;">
                            <div class="bracket-round-header" style="text-align: right;">Quarter-Finals</div>
                            <?php for ($m = 3; $m <= 4; $m++): ?>
                                <div class="matchup-card" data-index="<?php echo $m-1+8; ?>">
                                    <div class="matchup-slot"><span>M<?php echo ($m * 2) - 1; ?> Winner</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>M<?php echo ($m * 2); ?> Winner</span><span class="score">--</span></div>
                                    <div class="match-meta">FINALS // MATCH <?php echo 8 + $m; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="bracket-column">
                            <div class="bracket-round-header" style="text-align: right;">Round of 16 (Right)</div>
                            <?php for ($m = 5; $m <= 8; $m++): ?>
                                <div class="matchup-card" data-index="<?php echo $m-1; ?>">
                                    <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                                    <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                                    <div class="match-meta">FINALS // MATCH 0<?php echo $m; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                    </div>
                </div>

            </main>
        </div>

        <!-- LOWER TERMINAL STATUS READOUT FOOTER -->
        <footer>
            <div>LOCATION: [48.7186° N, 8.5097° E]</div>
            <div>SECURITY STATE: OVERWATCH LAYER SECURED</div>
            <div>© 2026 HASHWAR PROTOCOLS // DATA CONSOLE v4.82</div>
        </footer>

    </div>

    <script>

        let firstDraw = null;
        let phase1 = null;
        let phase2 = null;
        let finalDraw = null;
        let phase3 = null;

        document.addEventListener('DOMContentLoaded', () => {

            let s = getURLParameter('series_id');
            if (s){

                firstDraw = <?php echo json_encode($firstDraw); ?>;
                phase1 = <?php echo json_encode($phase1); ?>;
                phase2 = <?php echo json_encode($phase2); ?>;
                finalDraw = <?php echo json_encode($finalDraw); ?>;
                phase3 = <?php echo json_encode($phase3); ?>;

                console.table(firstDraw);
                console.table(phase1);
                console.table(phase2);
                console.table(finalDraw);
                console.table(phase3);

                // indicate weekly series id in the page title
                document.getElementById("weekNumber").innerText = s;

                if (firstDraw.length > 0){ renderGlyphPreviews(); }
                if (phase1.length > 0){ populatePhase(1); }
                if (phase2.length > 0){ populatePhase(2); }
                if (phase3.length > 0){ populatePhase(3); }

                let w = getWeekRange(s);
                document.getElementById("week-start").innerText = w.start;
                document.getElementById("week-end").innerText = w.end;

            }

        });

        function getURLParameter(myParameter){

            const urlParams = new URLSearchParams(window.location.search);
            const paramValue = urlParams.get(myParameter);

            let output = `${myParameter}=${paramValue}`;
            if (paramValue == null){ output = `Parameter ${myParameter} not set.` }
            console.log("[weekly-summary.php] getURLParameter(): " + output);

            return paramValue;

        }

        function renderGlyphPreviews() {
            document.querySelectorAll('.glyph-preview').forEach(container => {
                // Find the parent card to get the hash
                const card = container.closest('.glyph-matrix-card');
                const hash = card.getAttribute('data-originHash');
                
                // Extract hex color (chars 4-9 are indices 3-8 in 0-based indexing)
                // If the hash is not long enough, fallback to green
                const hexColor = (hash && hash.length >= 9) ? '#' + hash.substring(3, 9) : 'var(--accent-green)';
                
                const pattern = container.getAttribute('data-pattern');
                const size = 16;
                let svg = `<svg width="40" height="40" viewBox="0 0 ${size} ${size}">`;
                
                for (let i = 0; i < pattern.length; i++) {
                    if (pattern[i] === '1') {
                        const x = i % size;
                        const y = Math.floor(i / size);
                        // Apply the extracted hex color here
                        svg += `<rect x="${x}" y="${y}" width="0.8" height="0.8" fill="${hexColor}" />`;
                    }
                }
                svg += `</svg>`;
                container.innerHTML = svg;
            });
        }

        function populatePhase(myPhase){

            if (myPhase == '1'){

                // 1. Clear previous data in tables and reset slot text
                document.querySelectorAll('.group-table tbody').forEach(el => el.innerHTML = '');
                
                // Clear only the content inside .matchup-slot, keeping the spans
                document.querySelectorAll('.matchup-card .matchup-slot').forEach(el => {
                    if (!el.parentElement.querySelector('.match-meta')) {
                        el.innerHTML = '<span>--</span><span class="score">--</span>';
                    }
                });

                // 2. Process data by group
                const groups = { 'A': [], 'B': [], 'C': [], 'D': [] };
                phase1.forEach(p => groups[p.group_label]?.push(p));

                Object.keys(groups).forEach(groupLetter => {
                    const contestants = groups[groupLetter];
                    const tbody = document.querySelector(`#group-${groupLetter} .group-table tbody`);

                    contestants.forEach((p, idx) => {
                        const rank = idx + 1;
                        
                        // Populate Group Table
                        if (tbody) {
                            const rowClass = rank <= 3 ? 'row-advance' : (rank <= 7 ? 'row-redemption' : 'row-pruned');
                            tbody.insertAdjacentHTML('beforeend', `
                                <tr class="${rowClass}">
                                    <td>0${rank}</td><td>${p.glyph_name}</td>
                                    <td style="text-align: right;">${p.total_score}</td>
                                </tr>
                            `);
                        }

                        // Populate Redemption Brackets (Ranks 4-7)
                        if (rank >= 4 && rank <= 7) {
                            // Find all slots in the specific group's redemption bracket
                            // Rank 4 is index 0, 7 is index 1, 5 is index 2, 6 is index 3
                            const slotMap = { 4: 0, 7: 1, 5: 2, 6: 3 };
                            const slots = document.querySelectorAll(`div[id="group-${groupLetter}"] ~ div .matchup-slot`);
                            
                            // We target the redemption bracket by searching for the group container 
                            // in the second section (Phase II)
                            const targetSlot = document.querySelectorAll(`div[style*="border-color: rgba(244, 208, 66, 0.2)"]`)[
                                groupLetter.charCodeAt(0) - 65
                            ]?.querySelectorAll('.matchup-slot')[slotMap[rank]];

                            if (targetSlot) {
                                targetSlot.innerHTML = `<span>${rank}${rank === 4 ? 'th' : rank === 5 ? 'th' : rank === 6 ? 'th' : 'th'}: ${p.glyph_name}</span><span class="score"></span>`;
                            }
                        }
                    });
                });

            }

            if (myPhase == '2'){

                const groups = { 'A': [], 'B': [], 'C': [], 'D': [] };
                phase2.forEach(p => groups[p.group_label]?.push(p));

                Object.keys(groups).forEach(groupLetter => {

                    let c = groupLetter.charCodeAt(0) - 65; // this maps the group letters A through D to integers 0 to 3
                    for (let i = 0; i < 3; i++){

                        let g1 = groups[groupLetter][i].p1_glyph_name;
                        let g2 = groups[groupLetter][i].p2_glyph_name;
                        let p1 = groups[groupLetter][i].total_p1;
                        let p2 = groups[groupLetter][i].total_p2;

                        const targetSlot1 = document.querySelectorAll('.matchup-slot')[2*i + c*6];
                        const targetSlot2 = document.querySelectorAll('.matchup-slot')[2*i + 1 + c*6];

                        if (targetSlot1 && targetSlot2) {
                            targetSlot1.innerHTML = `<span>${g1}</span><span class="score">${p1}</span>`;
                            targetSlot2.innerHTML = `<span>${g2}</span><span class="score">${p2}</span>`;
                            if (p1 > p2){ 
                                targetSlot1.style.borderLeft = "2px solid var(--accent-green)"; 
                                targetSlot2.style.borderLeft = "2px solid #f44242";
                            }
                            else{ 
                                targetSlot1.style.borderLeft = "2px solid #f44242";
                                targetSlot2.style.borderLeft = "2px solid var(--accent-green)"; 
                            }
                        }

                    }

                });                

            }

            if (myPhase == '3'){

                // .forEach passes the item and its exact array index (0, 1, 2...)
                phase3.forEach((match, index) => {
                    
                    // 1. Find the card matching this array index
                    const matchCard = document.querySelector(`.matchup-card[data-index="${index}"]`);
                    if (!matchCard) return;

                    const slots = matchCard.querySelectorAll('.matchup-slot');
                    if (slots.length < 2) return;

                    // 2. Populate Player 1
                    slots[0].innerHTML = `<span>${match.p1_glyph_name}</span><span class="score">${match.total_p1}</span>`;
                    if (parseInt(match.p1_win) === 1) {
                        slots[0].classList.add('winner-highlight');
                    } else {
                        slots[0].classList.remove('winner-highlight'); // Reset if re-rendering
                    }

                    // 3. Populate Player 2
                    slots[1].innerHTML = `<span>${match.p2_glyph_name}</span><span class="score">${match.total_p2}</span>`;
                    if (parseInt(match.p2_win) === 1) {
                        slots[1].classList.add('winner-highlight');
                    } else {
                        slots[1].classList.remove('winner-highlight');
                    }
                });

                // 4. Update the Grand Champion podium directly using Index 14 (Match 15)
                const grandFinal = phase3[14];
                if (grandFinal) {
                    const podium = document.getElementById('weekly-champion-podium');
                    if (podium) {
                        podium.textContent = parseInt(grandFinal.p1_win) === 1 
                            ? grandFinal.p1_glyph_name 
                            : grandFinal.p2_glyph_name;
                    }
                }
            }

        }

        function commitToPhase2(){
            
            saveToPhase2(phase1);

        }

        async function saveToPhase2(data){       

            let truncData = [];

            for (let j = 0; j < 4; j++){

                // 'special' mapping for input to the KO tourney... duh
                truncData.push(data[3 + j*8]);
                truncData.push(data[6 + j*8]);
                truncData.push(data[4 + j*8]);
                truncData.push(data[5 + j*8]);

            }

            //console.log("[weekly-summary.php] saveNextStage(): truncData = ");
            //console.table(truncData);

            let myPayload = [];

            truncData.forEach((row) => {
                myPayload.push({
                    name: row.glyph_name,
                    group: row.group_label,
                    phase: 2
                });
            });

            //console.log("[weekly-summary.php] saveNextStage(): myPayload = ");
            //console.table(myPayload);
            
            const queryParameter = window.location.search;
            const urlParameters = new URLSearchParams(queryParameter);
            const seriesId = urlParameters.get('series_id');
            console.log("[weekly-summary.php] saveNextStage(): seriesId = " + seriesId);

            const response = await fetch('php/save-next-stage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ participants: myPayload, series: seriesId })
            });

            console.log("[weekly-summary.php] saveNextStage(): Saving to database... Done.");

        }

        function getWeekRange(weekNumber) {
            // note that the week range starts Sunday and ends Saturday
            const currentYear = 2026; 
            
            // 1. Start with January 1st of the target year
            const janFirst = new Date(currentYear, 0, 1);
            
            // 2. Find the first standard ISO Monday of the year
            const janFirstDayOfWeek = janFirst.getDay(); 
            const daysToFirstMonday = (janFirstDayOfWeek <= 1 ? 1 : 8) - janFirstDayOfWeek;
            const firstMonday = new Date(currentYear, 0, 1 + daysToFirstMonday);
            
            // 3. Jump ahead to the target week's Monday
            const targetMonday = new Date(firstMonday.getTime());
            targetMonday.setDate(firstMonday.getDate() + (weekNumber - 1) * 7);
            
            // 4. SHIFT: Subtract 1 day from Monday to get the preceding Sunday
            const startOfWeek = new Date(targetMonday.getTime());
            startOfWeek.setDate(targetMonday.getDate() - 1);
            
            // 5. Calculate Saturday by adding 6 days to Sunday
            const endOfWeek = new Date(startOfWeek.getTime());
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            
            // Helper function to format date as DD.MM.YYYY
            const formatDate = (date) => {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}.${month}.${year}`;
            };
            
            return {
                start: formatDate(startOfWeek),
                end: formatDate(endOfWeek)
            };
        }

    </script>

</body>
</html>