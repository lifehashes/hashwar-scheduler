<?php
// Global Error Debugging Switch
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Connection
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Fetch all available Conway Glyphs
$stmt = $pdo->query("SELECT BATTLE_NAME, ITERATIONS as GENERATIONS, PEAK, MAX, OWNER, BIN, HASH FROM GLYPHREG ORDER BY BATTLE_NAME ASC");
$glyphs = $stmt->fetchAll();

// Top 3 Glyphs from Phase I
$phaseOneAdvances = [];
$series_id = isset($_GET['series_id']) && is_numeric($_GET['series_id']) ? (int)$_GET['series_id'] : null;
if ($series_id){
    $query = $pdo->prepare("WITH RankedGlyphs AS (
    SELECT 
        sp.glyph_name, 
        sp.group_label, 
        SUM(
            CASE 
                WHEN m.p1_glyph_name = sp.glyph_name THEN mr.p1_final_score 
                WHEN m.p2_glyph_name = sp.glyph_name THEN mr.p2_final_score 
                ELSE 0 
            END
        ) AS total_score,
        DENSE_RANK() OVER (
            PARTITION BY sp.group_label 
            ORDER BY SUM(
                CASE 
                    WHEN m.p1_glyph_name = sp.glyph_name THEN mr.p1_final_score 
                    WHEN m.p2_glyph_name = sp.glyph_name THEN mr.p2_final_score 
                    ELSE 0 
                END
            ) DESC
        ) AS score_rank
    FROM series_participants sp
    JOIN matches m ON sp.tournament_id = m.tournament_id 
        AND (sp.glyph_name = m.p1_glyph_name OR sp.glyph_name = m.p2_glyph_name)
    JOIN match_rounds mr ON m.id = mr.match_id
    WHERE sp.series_id = ? AND sp.phase_id = '1'
    GROUP BY sp.glyph_name, sp.group_label
)
SELECT 
    glyph_name, 
    group_label, 
    total_score
FROM RankedGlyphs
WHERE score_rank <= 3
ORDER BY group_label, total_score DESC;");
    $query->execute([$series_id]);
    $phaseOneAdvances = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
    // no parameter provided
}

// Winners of Redemption Knock-Out tourneys
$phaseTwoWinners = [];
$series_id = isset($_GET['series_id']) && is_numeric($_GET['series_id']) ? (int)$_GET['series_id'] : null;
if ($series_id){
    $query = $pdo->prepare("WITH MatchResults AS (
    SELECT 
        m.tournament_id,         
        m.p1_glyph_name, 
        m.p2_glyph_name,
        sp.group_label,
        CASE 
            WHEN mr.total_p1 > mr.total_p2 THEN 1 
            ELSE 0 
        END AS p1_win,
        CASE 
            WHEN mr.total_p2 > mr.total_p1 THEN 1 
            ELSE 0 
        END AS p2_win
    FROM `matches` m
    INNER JOIN (
        SELECT DISTINCT tournament_id, group_label 
        FROM `series_participants` 
        WHERE series_id = ? AND phase_id = '2'
    ) sp ON m.tournament_id = sp.tournament_id
    LEFT JOIN (
        SELECT 
            match_id, 
            SUM(p1_final_score) AS total_p1, 
            SUM(p2_final_score) AS total_p2
        FROM `match_rounds`
        GROUP BY match_id
    ) mr ON m.id = mr.match_id
),
UnpivotedWins AS (
    SELECT group_label, p1_glyph_name AS glyph_name, p1_win AS win_count FROM MatchResults
    UNION ALL
    SELECT group_label, p2_glyph_name AS glyph_name, p2_win AS win_count FROM MatchResults
),
AggregatedWins AS (
    SELECT 
        glyph_name,
        group_label,        
        SUM(win_count) AS total_wins,
        -- Rank the glyphs within each group based on their aggregated wins
        ROW_NUMBER() OVER (
            PARTITION BY group_label 
            ORDER BY SUM(win_count) DESC
        ) AS win_rank
    FROM UnpivotedWins
    GROUP BY group_label, glyph_name
)
SELECT 
    glyph_name,
    group_label,    
    total_wins
FROM AggregatedWins
WHERE win_rank = 1
ORDER BY group_label ASC;");
    $query->execute([$series_id]);
    $phaseTwoWinners = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
    // no parameter provided
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR // WEEKLY SERIES PROTOCOL</title>
    <link rel="stylesheet" href="styles_old.css">
    <script src="js/hashing.js"></script>
    <style>
        /* --- Main Layout Grid --- */
        .master-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            padding: 10px;
        }

        .timeline-container-horizontal {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            width: 100%;
            margin-top: 10px;
            box-sizing: border-box;
        }

        /* --- Protocol Blocks --- */
        .day-protocol-block-hz {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            font-family: 'Courier New', monospace;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 420px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .day-protocol-block-hz:hover {
            border-color: rgba(66, 244, 133, 0.3);
            background: rgba(48, 64, 64, 0.9);
            box-shadow: 0 0 10px rgba(66, 244, 133, 0.05);
        }

        .day-identity-hz {
            border-bottom: 1px dashed rgba(255, 255, 255, 0.15);
            padding-bottom: 6px;
            width: 100%;
        }

        .day-name-hz {
            font-size: 1.1rem;
            font-weight: bold;
            color: #fff;
            letter-spacing: 1px;
        }

        .day-code-hz {
            font-size: 0.6rem;
            color: #666;
            margin-top: 2px;
        }

        .protocol-title-hz {
            font-size: 0.75rem;
            color: var(--accent-green);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }

        /* --- Roster & Registry --- */
        .roster-container {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            height: 82vh;
            overflow-y: auto;
            display: grid;            
            grid-template-columns: repeat(4, 1fr); /* Create equal columns here */
            gap: 10px;
            align-content: start;
        }

        /* Compact card layout */
        .glyph-matrix-card {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 8px 10px;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .glyph-matrix-card.offline {
            opacity: 0.15;
            filter: grayscale(1);
            pointer-events: none; /* Prevents interaction if needed */
        }

        .glyph-matrix-card:hover {
            border-color: var(--accent-green);
            background: rgba(66, 244, 133, 0.05);
        }

        .glyph-identity h3 {
            margin: 0;
            color: #fff;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .glyph-info-compact {
            font-size: 0.65rem;
            color: #888;
            font-family: 'Courier New', monospace;
            margin-top: 4px;
        }

        .glyph-preview {
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- Sidebar & Controls --- */
        .timeline-control-panel {
            background: var(--panel-bg);
            border: 1px solid var(--accent-green);
            padding: 20px;
            font-family: 'Courier New', monospace;
            box-shadow: 0 0 15px rgba(66, 244, 133, 0.1);
        }

        .panel-header {
            color: var(--accent-green);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 1.1rem;
            margin-top: 0;
            border-bottom: 1px solid rgba(66, 244, 133, 0.3);
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            font-size: 0.7rem;
            background: rgba(66, 244, 133, 0.2);
            padding: 3px 8px;
            border-radius: 3px;
            animation: pulse-animation 2s infinite;
        }

        .seed-display-box {
            background: #000;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px;
            font-size: 0.8rem;
            word-break: break-all;
            margin: 15px 0;
            color: #aaa;
        }

        .action-button {
            width: 100%;
            background: transparent;
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-button:hover {
            background: var(--accent-green);
            color: #000;
            box-shadow: 0 0 15px var(--accent-green);
        }

        .status-badge-hz {
            font-size: 0.65rem;
            text-align: center;
            padding: 4px 0;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            box-sizing: border-box;
            margin-top: auto;
            cursor: pointer;
            appearance: none;     
            -webkit-appearance: none;
            background: transparent; 
            border: 1px solid #444;
            transition: all 0.2s ease;
        }

        .status-badge-hz.pending { color: #f4d042; border-color: rgba(244, 208, 66, 0.4); }

        .status-badge-hz.active {
            background: var(--accent-green);
            color: #000;
            border-color: var(--accent-green);
            box-shadow: 0 0 8px var(--accent-green);
        }

        .status-badge-hz:hover {
            background: var(--accent-green);
            color: #000;
            box-shadow: 0 0 10px var(--accent-green);
        }

        /* --- Supplemental Elements --- */
        .symbol-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255,255,255,0.03);
            padding: 8px 0;
            border-radius: 2px;
            margin: 4px 0;
        }

        .micro-scoreboard {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.6rem;
            color: #aaa;
            margin-top: 4px;
        }

        .micro-scoreboard th {
            text-align: left;
            color: #666;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 2px;
        }

        .micro-scoreboard td { padding: 3px 0; }
        .row-advance { color: var(--accent-green); font-weight: bold; }
        .row-redemption { color: #f4d042; }
        .row-pruned { color: #ef4444; opacity: 0.6; }

        .stat-badge-group {
            display: flex;
            gap: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
        }
        .stat-badge-unit text { color: #888; }
        .stat-badge-unit val { color: var(--accent-green); font-weight: bold; }

        /* --- Glyph Filtering --- */

        .filter-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-family: 'Courier New', monospace;
        }

        .filter-stack label {
            font-size: 0.65rem;
            color: #888;
            margin-bottom: -5px;
        }

        .filter-inputs {
            display: flex;
            gap: 10px;
        }

        .filter-inputs input {
            flex: 1;
            min-width: 0; /* Prevents input from forcing its container to expand */
            background: #000;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--accent-green);
            padding: 8px;
            font-family: 'Courier New', monospace;
        }

        .filter-stack input:focus {
            border-color: var(--accent-green);
            outline: none;
        }

        .full-width-input {
            width: 100%;
            box-sizing: border-box;
            background: #000;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--accent-green);
            padding: 8px;
            font-family: 'Courier New', monospace;
        }

        /* --- Global Entropy-Style Scrollbars --- */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px; /* Added for horizontal scrollbars if needed */
        }

        ::-webkit-scrollbar-track {
            background: #000;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: #222;
            border: 1px solid rgba(66, 244, 133, 0.2);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-green);
        }

    </style>
</head>
<body>

<div class="outer-frame">
    <div class="stat-line" style="border-bottom: 1px solid var(--frame-grey); padding-bottom: 5px; margin-bottom: 5px;">
        <span style="font-weight: bold; letter-spacing: 2px;">BUREAU OF ENTROPY // SYSTEM INFRASTRUCTURE</span>
        <span id="clock-readout">SYSTEM EPOCH: SYNCHRONIZING...</span>
    </div>

    <div class="master-layout">
        <div>

            <div style="margin-bottom: 5px;">
                <h1 style="font-size: 1.6rem; color: #fff; margin: 0 0 5px 0; font-family: 'Calibri', sans-serif;">Weekly Series Overview</h1>
                <p style="font-size: 0.8rem; color: #888; font-family: 'Courier New', monospace; margin: 0;">
                    Visual sequence matrix modeling the 32-variant high-entropy competitive flow.
                </p>
            </div>

            <div class="timeline-container-horizontal">
                <div class="day-protocol-block-hz">
                    <div class="day-identity-hz">
                        <div class="day-name-hz">SUNDAY</div>
                        <div class="day-code-hz">PHASE_01 // DRAW</div>
                    </div>
                    <div>
                        <div class="protocol-title-hz">Glyph Selection</div>
                        <div class="symbol-wrapper">
                            <svg width="100" height="80" viewBox="0 0 100 80">
                                <rect x="10" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                                <rect x="39" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                                <rect x="68" y="10" width="22" height="18" fill="none" stroke="#666" stroke-width="1" stroke-dasharray="2,2"/>
                                <rect x="10" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                                <rect x="39" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                                <rect x="68" y="45" width="22" height="18" fill="none" stroke="var(--accent-green)" stroke-width="1"/>
                                <path d="M 21 28 L 21 45 M 50 28 L 50 45 M 79 28 L 79 45" stroke="#444" stroke-width="1" fill="none"/>
                            </svg>
                        </div>
                        <p style="font-size: 0.65rem; color: #888; line-height: 1.3;">32 Glyphs drawn and mapped dynamically into 4 competitive arrays.</p>
                    </div>
                    <button id="rnd-draw-btn" class="status-badge-hz pending" onclick="executeRndDraw()">RND DRAW</button>
                </div>

                <?php 
                $rrDays = [
                    'MONDAY'    => ['GROUP A', '8RR', 'Initial matrix loops activate to compile base states.'],
                    'TUESDAY'   => ['GROUP B', '8RR', 'Standings solidify over heavy computing blocks.'],
                    'WEDNESDAY' => ['GROUP C', '8RR', 'Critical point-margin deltas build across columns.'],
                    'THURSDAY'  => ['GROUP D',  '8RR', 'Group finalization. Top 3 advance, 4-7 drop to Friday.']
                ];
                foreach ($rrDays as $dayName => $info): 
                ?>
                <div class="day-protocol-block-hz">
                    <div class="day-identity-hz">
                        <div class="day-name-hz"><?php echo $dayName; ?></div>
                        <div class="day-code-hz">PHASE_02 // <?php echo $info[0]; ?></div>
                    </div>
                    <div>
                        <div class="protocol-title-hz"><?php echo $info[1]; ?></div>
                        
                        <div class="symbol-wrapper">
                            <svg width="110" height="110" viewBox="0 0 110 110">
                                <polygon points="55,10 87,23 100,55 87,87 55,100 23,87 10,55 23,23" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                                <path d="M 55 10 L 100 55 M 55 10 L 55 100 M 55 10 L 10 55 M 87 23 L 87 87 M 87 23 L 23 87 M 100 55 L 10 55 M 23 23 L 87 87" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                                
                                <circle cx="55" cy="10" r="4.5" fill="#42f485"/> <circle cx="87" cy="23" r="4.5" fill="#42f485"/> <circle cx="100" cy="55" r="4.5" fill="#42f485"/> <circle cx="87" cy="87" r="4.5" fill="#f4d042"/> <circle cx="55" cy="100" r="4.5" fill="#f4d042"/> <circle cx="23" cy="87" r="4.5" fill="#f4d042"/> <circle cx="10" cy="55" r="4.5" fill="#f4d042"/> <circle cx="23" cy="23" r="4.5" fill="#ef4444"/> </svg>
                        </div>

                        <table class="micro-scoreboard">
                            <thead>
                                <tr><th>GLYPH</th><th style="text-align:right;">PTS</th></tr>
                            </thead>
                            <tbody>
                                <tr class="row-advance"><td>01. ALPHA</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-advance"><td>02. BETA</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-advance"><td>03. GAMMA</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-redemption"><td>04. DELTA</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-redemption"><td>05. EPSILON</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-redemption"><td>06. ZETA</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-redemption"><td>75. ETA</td><td style="text-align:right;">--</td></tr>
                                <tr class="row-pruned"><td>08. OMEGA</td><td style="text-align:right;">--</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <button class="status-badge-hz" onclick="">EVALUATE</button>
                </div>
                <?php endforeach; ?>

                <div class="day-protocol-block-hz">
                    <div class="day-identity-hz">
                        <div class="day-name-hz">FRIDAY</div>
                        <div class="day-code-hz">PHASE_03 // REDEMPTION</div>
                    </div>
                    <div>
                        <div class="protocol-title-hz" style="color: #f4d042;">Redemption Day</div>
                        
                        <div class="symbol-wrapper" style="padding: 18px 0;">
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
                        <p style="font-size: 0.65rem; color: #888; line-height: 1.3; margin-top: 20px;">
                            16 variants map into 4 parallel sudden-death trees. 4 winners secure the final Saturday slots.
                        </p>
                    </div>
                    <button class="status-badge-hz" onclick="">EVALUATE</button>
                </div>

                <div class="day-protocol-block-hz" style="border-color: rgba(66, 244, 133, 0.2);">
                    <div class="day-identity-hz">
                        <div class="day-name-hz">SATURDAY</div>
                        <div class="day-code-hz">PHASE_04 // CHAMPION</div>
                    </div>
                    <div>
                        <div class="protocol-title-hz" style="color: #fff;">The Weekly Finale</div>
                        
                        <div class="symbol-wrapper" style="padding: 13px 0;">
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
                        <p style="font-size: 0.65rem; color: #aaa; line-height: 1.3; margin-top: 10px;">
                            16-variant converging knockout matrix executes to calculate the definitive Weekly Series Champion.
                        </p>
                    </div>
                    <button class="status-badge-hz" onclick="">EVALUATE</button>
                </div>
            </div>

            <div class="panel-header" style="padding: 10px 0;">Conway Glyphs (<?php echo count($glyphs); ?>)</div>
            <div class="roster-container">
                <?php foreach ($glyphs as $glyph): ?>
                    <div class="glyph-matrix-card" 
                        data-gens="<?php echo $glyph['GENERATIONS']; ?>" 
                        data-peak="<?php echo $glyph['PEAK']; ?>"
                        data-originBin="<?php echo $glyph['BIN']; ?>"
                        data-originHash="<?php echo $glyph['HASH']; ?>"
                        data-name="<?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?>">

                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <div class="glyph-identity">
                                    <h3><?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?></h3>
                                </div>
                                <div class="glyph-info-compact">
                                    <div>Owner: <?php echo htmlspecialchars($glyph['OWNER'] ?? 'SYSTEM'); ?></div>
                                    <div style="color: var(--accent-green);">
                                        GENS: <?php echo $glyph['GENERATIONS']; ?> | PEAK: <?php echo $glyph['PEAK']; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="glyph-preview" data-pattern='<?php echo $glyph['BIN']; ?>'>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="timeline-control-panel">
            <h2 class="panel-header" style="font-size: 0.9rem;">
                Canonical Timeline
                <span class="status-badge" id="tva-status">MONITORING</span>
            </h2>
            
            <p style="font-size: 0.75rem; color: #aaa; line-height: 1.4;">
                All hail to the <text style="color: var(--accent-green);">National Institute of Standards and Technology</text>!
            </p>

            <h3 style="font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; color: var(--frame-grey);">NIST Pulse <span id="pulseID"></span></h3>
            <div class="seed-display-box" id="raw-pulse-box">..oo.o.o...o.oo.oo.oo..o.o....oo...o.AWAITING QUANTUM HARVEST.o...o...oo.oo...o...o.o.oo.oooo.o.....o.oo.o..o.ooo.o...o.o...oo...</div>

            <h3 style="font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; color: var(--frame-grey);">Compressed Engine Seed (FNV-1a 32-bit):</h3>
            <div class="seed-display-box" id="coerced-seed-box" style="font-size: 1.2rem; text-align: center; color: var(--accent-green); font-weight: bold;">0000000000</div>

            <button class="action-button" id="prune-timeline-btn">Retrieve NIST Pulse</button>

            <div style="margin-top: 30px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 20px;">
                <h3 style="font-size: 0.8rem; margin-bottom: 15px; text-transform: uppercase; color: var(--accent-green);">[ LAYER 01 ] GLYPH ELIGIBILITY FILTERS</h3>
                
                <div class="filter-stack">
                    <label>GENERATION RANGE (MIN - MAX)</label>
                    <div class="filter-inputs">
                        <input type="number" id="gen-min" placeholder="0">
                        <input type="number" id="gen-max" placeholder="9999">
                    </div>

                    <label>PEAK RANGE (MIN - MAX)</label>
                    <div class="filter-inputs">
                        <input type="number" id="peak-min" placeholder="0">
                        <input type="number" id="peak-max" placeholder="9999">
                    </div>

                    <label>HASH REQUIREMENT</label>
                    <input type="text" placeholder="0x..." class="full-width-input">

                    <button class="action-button" id="apply-filter-btn" onClick="applyFilter();">Apply Filter</button>
                </div>                
            </div>
        </div>
    </div>
</div>

<div id="draw-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1000; padding:40px; box-sizing:border-box;">
    <h2 style="color:var(--accent-green); text-align:center;" id="first-draw-title">INITIATING QUANTUM SHUFFLE...</h2>
    <div id="draw-columns" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; height:80vh; overflow:hidden;">
        <div id="col-MONDAY" class="group-col"><h3>GROUP A (MONDAY)</h3></div>
        <div id="col-TUESDAY" class="group-col"><h3>GROUP B (TUESDAY)</h3></div>
        <div id="col-WEDNESDAY" class="group-col"><h3>GROUP C (WEDNESDAY)</h3></div>
        <div id="col-THURSDAY" class="group-col"><h3>GROUP D (THURSDAY)</h3></div>
    </div>
    <div id="first-draw-link"></div>
</div>

<div id="tournament-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(6, 12, 12, 0.95); z-index:1000; padding:30px; box-sizing:border-box; font-family:monospace; color:#a3b8cc;">
    
    <h2 style="color:#00ffcc; text-align:center; font-size: 2rem; letter-spacing: 2px; margin-bottom: 30px; text-shadow: 0 0 10px rgba(0,255,204,0.3);">
        INITIATING QUANTUM SHUFFLE...
    </h2>
    
    <div style="display:grid; grid-template-columns: 1.5fr 1fr 1fr 1.2fr 1fr 1fr 1.5fr; gap:15px; height:calc(100vh - 120px); align-items: center;">
        
        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color:#567;">Round of 16 (Left)</div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-1"></div><div class="group-col" id="tourney-glyph-2"></div></div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-3"></div><div class="group-col" id="tourney-glyph-4"></div></div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-5"></div><div class="group-col" id="tourney-glyph-6"></div></div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-7"></div><div class="group-col" id="tourney-glyph-8"></div></div>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: space-around; height: 100%;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color:#567; align-self: flex-start;">Quarter-Finals</div>
            <div style="border: 1px solid #223333; background: rgba(10,20,20,0.4); min-height: 70px; width: 100%;"></div>
            <div style="border: 1px solid #223333; background: rgba(10,20,20,0.4); min-height: 70px; width: 100%;"></div>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: center; height: 100%;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color:#567; margin-bottom: 10px;">Semi-Final 1</div>
            <div style="border: 1px solid #223333; background: rgba(10,20,20,0.4); min-height: 80px; width: 100%;"></div>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: center; height: 100%; gap: 40px; text-align: center;">
            
            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color:#d4af37; margin-bottom: 8px;">Grand Championship</div>
                <div style="border: 2px solid #d4af37; background: rgba(25,22,15,0.4); min-height: 90px; border-radius: 4px;"></div>
            </div>

            <div>
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color:#a87c43; margin-bottom: 8px;">3rd Place Playoff</div>
                <div style="border: 1px solid #a87c43; background: rgba(20,16,12,0.4); min-height: 70px; font-size: 0.75rem; padding: 10px 5px; color: #765;">
                    <div style="margin-bottom: 5px;">M13 LOSER</div>
                    <div>M14 LOSER</div>
                </div>
            </div>

            <div style="color: #00ffcc; font-size: 0.9rem; font-weight: bold; letter-spacing: 1px; animation: blink 2s infinite; margin-top: 20px;">
                AWAITING RESOLUTION
            </div>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: center; height: 100%;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color:#567; margin-bottom: 10px; text-align: right;">Semi-Final 2</div>
            <div style="border: 1px solid #223333; background: rgba(10,20,20,0.4); min-height: 80px; width: 100%;"></div>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: space-around; height: 100%;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color:#567; align-self: flex-end;">Quarter-Finals</div>
            <div style="border: 1px solid #223333; background: rgba(10,20,20,0.4); min-height: 70px; width: 100%;"></div>
            <div style="border: 1px solid #223333; background: rgba(10,20,20,0.4); min-height: 70px; width: 100%;"></div>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color:#567; text-align: right;">Round of 16 (Right)</div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-9"></div><div class="group-col" id="tourney-glyph-10"></div></div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-11"></div><div class="group-col" id="tourney-glyph-12"></div></div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-13"></div><div class="group-col" id="tourney-glyph-14"></div></div>
            <div style="margin: 10px 0;"><div class="group-col" id="tourney-glyph-15"></div><div class="group-col" id="tourney-glyph-16"></div></div>
        </div>

    </div>
</div>

<style>
    .group-col { border: 1px solid #333; padding: 10px; display:flex; flex-direction:column; gap:5px; }
    .group-col h3 { color:#666; font-size:0.8rem; text-align:center; }
    /* Quick helper for the flashing resolution status */
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
</style>

<script>

    document.addEventListener("DOMContentLoaded", function() {
        renderGlyphPreviews();
        checkButtonReadiness();
    });

    function updateClock() {
        document.getElementById('clock-readout').innerText = "SYSTEM EPOCH: " + new Date().toISOString().replace('T', ' ').substring(0, 19) + " UTC";
    }
    setInterval(updateClock, 1000);
    updateClock();

    function applyFilter() {
        // Target the IDs directly
        const minGen = parseInt(document.getElementById('gen-min').value) || 0;
        const maxGen = parseInt(document.getElementById('gen-max').value) || 9999;
        const minPeak = parseInt(document.getElementById('peak-min').value) || 0;
        const maxPeak = parseInt(document.getElementById('peak-max').value) || 9999;

        const cards = document.querySelectorAll('.glyph-matrix-card');
        let activeCount = 0;

        cards.forEach(card => {
            const gens = parseInt(card.getAttribute('data-gens'));
            const peak = parseInt(card.getAttribute('data-peak'));

            const isMatch = (gens >= minGen && gens <= maxGen) && 
                            (peak >= minPeak && peak <= maxPeak);

            if (isMatch) {
                card.classList.remove('offline');
                activeCount++;
            } else {
                card.classList.add('offline');
            }
        });

        const header = document.querySelector('.panel-header:not(.status-badge)'); // Targets the specific glyph counter header
        // Use the selector that specifically targets your header text:
        const glyphHeader = document.querySelector('.roster-container').previousElementSibling;
        glyphHeader.innerText = `Conway Glyphs (${activeCount})`;

        checkButtonReadiness();

        // console.log(`[FILTER] Applied: Gen(${minGen}-${maxGen}), Peak(${minPeak}-${maxPeak})`);
    }
    
    async function executeRndDraw() {
        const status = document.getElementById('tva-status').innerText;
        const rawSeed = parseInt(document.getElementById('coerced-seed-box').innerText);
        const engineSeed = Math.abs(rawSeed);
        const eligibleGlyphs = Array.from(document.querySelectorAll('.glyph-matrix-card:not(.offline)'));

        if (status !== 'LOCKED' || eligibleGlyphs.length < 32) return;

        // 1. Initialize seeded PRNG and shuffle
        const rng = seededRandom(engineSeed);
        for (let i = eligibleGlyphs.length - 1; i > 0; i--) {
            const j = Math.floor(rng() * (i + 1));
            [eligibleGlyphs[i], eligibleGlyphs[j]] = [eligibleGlyphs[j], eligibleGlyphs[i]];
        }
        // console.log("[weekly-overview.php] executeRndDraw(): Number of elligible Glyphs to draw from: " + eligibleGlyphs.length);

        // 2. Assign shuffled deck to groups
        const finalSet = eligibleGlyphs.slice(0, 32);
        // console.log("[weekly-overview.php] executeRndDraw(): Final set of Glyphs: " + finalSet.length);
        const groups = {
            'MONDAY': finalSet.slice(0, 8),
            'TUESDAY': finalSet.slice(8, 16),
            'WEDNESDAY': finalSet.slice(16, 24),
            'THURSDAY': finalSet.slice(24, 32)
        };
        console.log("[weekly-overview.php] executeRndDraw(): Assignment complete:", groups);

        // 3a. Create a simplified package
        const labels = ['A', 'B', 'C', 'D'];
        const groupSize = 8; // Based on your logic of 32 glyphs / 4 groups
        const simplifiedPackage = [];

        labels.forEach((label, index) => {
            const start = index * groupSize;
            const end = start + groupSize;
            const groupGlyphs = finalSet.slice(start, end);
            
            groupGlyphs.forEach(glyph => {
                simplifiedPackage.push({
                    name: glyph.querySelector('h3').innerText,
                    group: label, // This will now be 'A', 'B', 'C', or 'D'
                    phase: 1
                });
            });
        });

        // 3b. Save package to database
        console.log("Saving to database...");        
        const response = await fetch('php/save-draw.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ participants: simplifiedPackage })
        });

        const result = await response.json();
        if (result.status === 'success') {
            console.log('Draw saved! Series ID: ' + result.series_id);

            // 4. Now animate using the 'groups' object
            const dayKeys = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'];
            const modal = document.getElementById('draw-modal');
            modal.style.display = 'block';

            // Iterate through each day/column
            for (const day of dayKeys) {
                const col = document.getElementById(`col-${day}`); // Use the 'day' directly
                
                // Iterate through the 8 glyphs assigned to that specific day
                for (const glyph of groups[day]) {
                    const clone = glyph.cloneNode(true);
                    clone.style.opacity = '0';
                    col.appendChild(clone);
                    
                    await new Promise(r => setTimeout(r, 50));
                    clone.style.transition = 'opacity 0.3s';
                    clone.style.opacity = '1';
                }
            }

            document.getElementById("first-draw-title").innerText = "SHUFFLE COMPLETE.";
            document.getElementById("first-draw-link").innerHTML = `<a href="https://lifehashes.net/hashwar-scheduler/weekly-overview.php?series_id=${result.series_id}" target="_blank">WEEKLY SERIES OVERVIEW</a>`;

            // console.log("[RND DRAW] Assignment complete:", groups);
            return groups;

        } else {
            console.error('Failed to save draw:', result.message);
        }

    }
    
    /**
     * Simple Linear Congruential Generator
     * @param {number} seed - The NIST-derived engineSeed
     */
    function seededRandom(seed) {
        return function() {
            seed = (seed * 16807) % 2147483647;
            return (seed - 1) / 2147483646;
        };
    }

    function checkButtonReadiness() {
        const btn = document.getElementById('rnd-draw-btn');
        const status = document.getElementById('tva-status').innerText;
        const count = document.querySelectorAll('.glyph-matrix-card:not(.offline)').length;

        if (status === 'LOCKED' && count >= 32) {
            btn.disabled = false;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
        } else {
            btn.disabled = true;
            btn.style.opacity = "0.3";
            btn.style.cursor = "not-allowed";
        }
    }

    // NIST Randomness Beacon Harvester & INT(11) Coercion Layer
    document.getElementById('prune-timeline-btn').addEventListener('click', function() {
        const btn = this;
        const pulseBox = document.getElementById('raw-pulse-box');
        const seedBox = document.getElementById('coerced-seed-box');
        const statusBadge = document.getElementById('tva-status');
        
        btn.disabled = true;
        btn.innerText = "Harvesting Pulse...";
        statusBadge.innerText = "HARVESTING";
        statusBadge.style.background = "rgba(244, 208, 66, 0.2)";
        statusBadge.style.color = "#f4d042";

        // Poll the live NIST Randomness Beacon via its official time API endpoint
        const currentTimestamp = Date.now();
        
        fetch(`https://beacon.nist.gov/beacon/2.0/pulse/time/previous/${currentTimestamp}`)
            .then(response => {
                if(!response.ok) throw new Error("Network latency in the Canonical Timeline.");
                return response.json();
            })
            .then(data => {
                // Grab the 512-bit (128 hex character) output value string
                const rawHex = data.pulse.outputValue;
                //pulseBox.innerText = rawHex;
                updatePulseUI(rawHex, pulseBox);

                const pulseIndex = data.pulse.pulseIndex;

                // Compress the 512-bit hex string by hashing and mapping it deterministically to 32-bit int
                let engineSeed = fnv1a32(rawHex);

                // MySQL signed INT(11) upper limit safeguard: 2,147,483,647.
                // If our unsigned bit shift pushed past this limit, we map it into signed territory,
                // or safely modulo/clamp it so your database never chokes on insertion.
                if (engineSeed > 2147483647) {
                    engineSeed = engineSeed - 4294967296; 
                }

                updateSeedUI(engineSeed, btn, seedBox);
                // seedBox.innerText = engineSeed;
                
                statusBadge.innerText = "LOCKED";
                statusBadge.style.background = "rgba(66, 244, 133, 0.2)";
                statusBadge.style.color = "var(--accent-green)";
                
                btn.disabled = false;
                btn.innerText = "Harvest NIST Pulse";

                checkButtonReadiness();

                console.log(`[THE OVERSEER] Input timestamp ${currentTimestamp} -> NIST Full Hex for Pulse ID ${pulseIndex}: ${rawHex} -> Compression Stage: FNV-1a Hash -> INT(11) Signed Seed: ${engineSeed}`);
                document.getElementById("pulseID").innerText = `(ID ${pulseIndex}):`;

            })
            .catch(error => {
                console.warn(error);
                // Fallback deterministic simulation mode if NIST API hits network throttling
                const localFallbackHex = crypto.subtle ? 'A576F821B000CD91BFA3C672B10906BC' : 'DEADBEEF101010101010101010101010';
                pulseBox.innerText = localFallbackHex + " [LOCAL TIMELINE FALLBACK]";
                
                let engineSeed = fnv1a32(localFallbackHex);
                if (engineSeed > 2147483647) {
                    engineSeed = engineSeed - 4294967296;
                }
                
                updateSeedUI(engineSeed, btn, seedBox);
                //seedBox.innerText = engineSeed;
                statusBadge.innerText = "PRUNED FALLBACK";
                statusBadge.style.background = "rgba(244, 66, 66, 0.2)";
                statusBadge.style.color = "#f44242";
                
                btn.disabled = false;
                btn.innerText = "Harvest NIST Pulse";
            });
    });

    function updatePulseUI(rawHex, pulseBox) {
        const totalDuration = 2000; // Slightly longer for the long hex string
        const frameRate = 30;
        const cyclesPerChar = Math.floor(totalDuration / frameRate);
        let frame = 0;

        const interval = setInterval(() => {
            let currentDisplay = "";
            for (let i = 0; i < rawHex.length; i++) {
                // Reveal characters progressively
                const lockThreshold = (totalDuration / rawHex.length) * i;
                if (frame * frameRate >= lockThreshold) {
                    currentDisplay += rawHex[i];
                } else {
                    // Cycle through random hex characters
                    currentDisplay += "0123456789ABCDEF"[Math.floor(Math.random() * 16)];
                }
            }
            pulseBox.innerText = currentDisplay;
            frame++;

            if (frame >= cyclesPerChar) {
                clearInterval(interval);
                pulseBox.innerText = rawHex;
            }
        }, frameRate);
    }

    function updateSeedUI(engineSeed, btn, seedBox) {
        // --- COOL NUMBER CYCLING ANIMATION LAYER ---
        const targetStr = engineSeed.toString();
        const totalDuration = 1500; // Total animation time in ms
        const frameRate = 40;       // Speed of the cycling shuffle in ms
        const cyclesPerChar = Math.floor(totalDuration / frameRate);
        let frame = 0;

        // Clear any existing intervals if the user clicks rapidly
        if (btn.animationInterval) clearInterval(btn.animationInterval);

        btn.animationInterval = setInterval(() => {
            let currentDisplay = "";

            for (let i = 0; i < targetStr.length; i++) {
                // If the character is a minus sign, keep it stable
                if (targetStr[i] === '-') {
                    currentDisplay += '-';
                    continue;
                }

                // Determine if this specific digit should be locked yet
                const lockThreshold = (totalDuration / targetStr.length) * i;
                const currentProgress = frame * frameRate;

                if (currentProgress >= lockThreshold) {
                    // Reveal the actual true digit
                    currentDisplay += targetStr[i];
                } else {
                    // Cycle through a random digit
                    currentDisplay += Math.floor(Math.random() * 10).toString();
                }
            }

            seedBox.innerText = currentDisplay;
            frame++;

            // When the final frame is reached, force absolute accuracy and clear the loop
            if (frame >= cyclesPerChar) {
                clearInterval(btn.animationInterval);
                seedBox.innerText = targetStr; // Absolute fallback safety match
            }
        }, frameRate);
        // --- END ANIMATION LAYER ---
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

    function fetchTopThreeFromPhaseOne(){

        const phaseOneTopThree = <?php echo json_encode($phaseOneAdvances); ?>;
        console.table(phaseOneTopThree);
        return phaseOneTopThree;

    }

    function fetchPhaseTwoWinners(){

        const phaseTwoWinners = <?php echo json_encode($phaseTwoWinners); ?>;
        console.table(phaseTwoWinners);
        return phaseTwoWinners;

    }

    function compileFinalTourneyParticipants(){

        let participants = [];

        let phaseOne = fetchTopThreeFromPhaseOne();
        let phaseTwo = fetchPhaseTwoWinners();

        phaseOne.forEach((glyph) => { participants.push(glyph.glyph_name.toUpperCase()); });
        phaseTwo.forEach((glyph) => { participants.push(glyph.glyph_name.toUpperCase()); });

        console.log("[weekly-overview.php] compileFinalTourneyParticipants(): Fetching Phase I and Phase II winners.");
        console.table(participants);

        executeFinalDraw(participants);

    }

    async function executeFinalDraw(allowedNames) {

        const rawSeed = parseInt(document.getElementById('coerced-seed-box').innerText);
        const engineSeed = Math.abs(rawSeed);
        const eligibleGlyphs = Array.from(document.querySelectorAll('.glyph-matrix-card'))
                                .filter(card => allowedNames.includes(card.dataset.name.toUpperCase()));

        console.log("[weekly-overview.php] executeFinalDraw(): Number of finalists: " + eligibleGlyphs.length);

        if (eligibleGlyphs.length < 16) return;

        // 1. Initialize seeded PRNG and shuffle
        const rng = seededRandom(engineSeed);
        for (let i = eligibleGlyphs.length - 1; i > 0; i--) {
            const j = Math.floor(rng() * (i + 1));
            [eligibleGlyphs[i], eligibleGlyphs[j]] = [eligibleGlyphs[j], eligibleGlyphs[i]];
        }
        
        // 2. Assign shuffled deck to groups
        const finalSet = eligibleGlyphs.slice(0, 16);

        const finalSetOutput = finalSet.map(element => element.dataset.name);
        console.log("[weekly-overview.php] executeFinalDraw(): Here are the 16 finalists for the Grand Finale in randomized draw order:");
        console.table(finalSetOutput);

        // 4. Now animate using the 'groups' object
        const modal = document.getElementById('tournament-modal');
        modal.style.display = 'block';

        for (let i = 1; i < 17; i++){

            const col = document.getElementById(`tourney-glyph-${i}`);
            const clone = finalSet[i-1].cloneNode(true);
            clone.style.opacity = '0';
            col.appendChild(clone);

            await new Promise(r => setTimeout(r, 50));
            clone.style.transition = 'opacity 0.3s';
            clone.style.opacity = '1';

        }

        let package = [];
        for (let i = 0; i < finalSetOutput.length; i++){

            package.push({
                name: finalSetOutput[i].toUpperCase(),
                group: "final",
                phase: 3
            });

        }        

        // 3b. Save package to database
        console.log("Saving to database...");        
        const queryParameter = window.location.search;
        const urlParameters = new URLSearchParams(queryParameter);
        const seriesId = urlParameters.get('series_id');
        console.log("[weekly-summary.php] saveNextStage(): seriesId = " + seriesId);

        const response = await fetch('php/save-next-stage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ participants: package, series: seriesId })
        });

    }

</script>
</body>
</html>