<?php
session_start();
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Protection Guard: Ensure user is logged in
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
*/

$current_operator = htmlspecialchars($_SESSION['username']);

// 1. Fetch participation counts per glyph across all weekly series
$participationsStmt = $pdo->query("
    SELECT glyph_name, COUNT(DISTINCT series_id) AS series_count 
    FROM series_participants 
    WHERE phase_id = '1'
    GROUP BY glyph_name
");
$participationsRaw = $participationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Map into key-value array [ 'GLYPH_NAME' => count ]
$seriesCounts = [];
foreach ($participationsRaw as $row) {
    $seriesCounts[strtoupper($row['glyph_name'])] = (int)$row['series_count'];
}

// 2. Calculate podium medals for Phase 3 / Group F matches
$podiumStmt = $pdo->query("
    SELECT 
        m.id AS match_id,
        m.match_designation,
        UPPER(m.p1_glyph_name) AS p1,
        UPPER(m.p2_glyph_name) AS p2,
        SUM(mr.p1_final_score) AS total_p1,
        SUM(mr.p2_final_score) AS total_p2
    FROM matches m
    JOIN match_rounds mr ON m.id = mr.match_id
    WHERE m.match_designation LIKE '%GROUP F%'
    GROUP BY m.id, m.match_designation, m.p1_glyph_name, m.p2_glyph_name
");
$fMatches = $podiumStmt->fetchAll(PDO::FETCH_ASSOC);

// Map medals per glyph: ['GLYPH_NAME' => ['gold' => x, 'silver' => y, 'bronze' => z]]
$medals = [];
foreach ($fMatches as $m) {
    $p1 = $m['p1'];
    $p2 = $m['p2'];
    $p1Score = (int)$m['total_p1'];
    $p2Score = (int)$m['total_p2'];

    if ($p1Score > $p2Score) {
        $winner = $p1; $loser = $p2;
    } elseif ($p2Score > $p1Score) {
        $winner = $p2; $loser = $p1;
    } else {
        continue; // Skip ties
    }

    $init = function($name) use (&$medals) {
        if (!isset($medals[$name])) {
            $medals[$name] = ['gold' => 0, 'silver' => 0, 'bronze' => 0];
        }
    };

    if (strpos($m['match_designation'], 'MATCH 1/1') !== false) {
        $init($winner); $medals[$winner]['gold']++;
        $init($loser);  $medals[$loser]['silver']++;
    } elseif (strpos($m['match_designation'], 'MATCH 1/2') !== false || strpos($m['match_designation'], 'MATCH 2/2') !== false) {
        $init($loser); $medals[$loser]['bronze']++;
    }
}

// 3. Fetch all registered Glyphs across the entire system
$stmt = $pdo->query("
    SELECT 
        g.BATTLE_NAME, 
        g.ITERATIONS AS GENERATIONS, 
        g.PEAK, 
        g.MAX, 
        g.OWNER, 
        g.BIN, 
        g.HASH
    FROM GLYPHREG g
");
$allGlyphs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$decoratedGlyphs = [];

foreach ($allGlyphs as $glyph) {
    $upperName = strtoupper($glyph['BATTLE_NAME']);
    
    $gold   = $medals[$upperName]['gold']   ?? 0;
    $silver = $medals[$upperName]['silver'] ?? 0;
    $bronze = $medals[$upperName]['bronze'] ?? 0;
    
    // Series participation pulled directly from $seriesCounts lookup
    $series = $seriesCounts[$upperName] ?? 0;

    // Filter: Only include Glyphs that have won at least 1 medal
    if ($gold > 0 || $silver > 0 || $bronze > 0) {
        $totalMedals = $gold + $silver + $bronze;
        $efficiency  = $series > 0 ? ($totalMedals / $series) : 0;

        $glyph['gold']       = $gold;
        $glyph['silver']     = $silver;
        $glyph['bronze']     = $bronze;
        $glyph['series']     = $series;
        $glyph['efficiency'] = $efficiency;

        $decoratedGlyphs[] = $glyph;
    }
}

// 4. Sort using the Olympic Model Priority Hierarchy
usort($decoratedGlyphs, function($a, $b) {
    if ($a['gold'] !== $b['gold'])          return $b['gold'] <=> $a['gold'];
    if ($a['silver'] !== $b['silver'])      return $b['silver'] <=> $a['silver'];
    if ($a['bronze'] !== $b['bronze'])      return $b['bronze'] <=> $a['bronze'];
    if ($a['efficiency'] !== $b['efficiency']) return $b['efficiency'] <=> $a['efficiency'];
    if ($a['series'] !== $b['series'])      return $b['series'] <=> $a['series'];
    return $b['PEAK'] <=> $a['PEAK'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HALL OF FAME // OLYMPIC STANDINGS</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles_user.css">
    <style>
        html { scrollbar-gutter: stable; }
        
        .rank-badge {
            font-family: monospace;
            font-size: 0.85rem;
            font-weight: bold;
            color: var(--accent-green);
            background: rgba(0, 255, 65, 0.1);
            border: 1px solid rgba(0, 255, 65, 0.3);
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 6px;
        }

        .top-1 { color: #ffd700; border-color: #ffd700; background: rgba(255, 215, 0, 0.15); }
        .top-2 { color: #c0c0c0; border-color: #c0c0c0; background: rgba(192, 192, 192, 0.15); }
        .top-3 { color: #cd7f32; border-color: #cd7f32; background: rgba(205, 127, 50, 0.15); }

        .efficiency-tag {
            font-size: 0.65rem;
            color: var(--frame-grey);
            font-family: monospace;
        }

        .medal-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            margin-right: 4px;
            font-family: monospace;
            line-height: 1;
        }

        .medal-gold   { color: #ffd700; background: rgba(255, 215, 0, 0.15); border: 1px solid rgba(255, 215, 0, 0.5); box-shadow: 0 0 5px rgba(255, 215, 0, 0.3); }
        .medal-silver { color: #c0c0c0; background: rgba(192, 192, 192, 0.15); border: 1px solid rgba(192, 192, 192, 0.5); box-shadow: 0 0 5px rgba(192, 192, 192, 0.2); }
        .medal-bronze { color: #cd7f32; background: rgba(205, 127, 50, 0.15); border: 1px solid rgba(205, 127, 50, 0.5); box-shadow: 0 0 5px rgba(205, 127, 50, 0.2); }

        .pip-rookie-label {
            font-size: 0.6rem;
            font-family: monospace;
            color: #888;
            border: 1px dashed #555;
            padding: 0 3px;
            border-radius: 2px;
        }

        .chevrons-wrapper {
            display: inline-flex;
            align-items: center;
            vertical-align: middle;
            margin-left: 4px;
            gap: 1px;
        }
    </style>
</head>
<body>

    <div class="outer-frame">
        <div style="font-size: 0.65rem; color: var(--accent-green); margin-bottom: 15px; letter-spacing: 1px; display: flex; justify-content: space-between;">
            <span>SYS_STATUS: AUTHORIZED // ACCESS_LEVEL: OPERATOR</span>
            <span>OPERATOR: <?php echo $current_operator; ?></span>
        </div>

        <div class="panel-title">HALL_OF_FAME // OLYMPIC MEDAL STANDINGS (<?php echo count($decoratedGlyphs); ?> DECORATED GLYPHS)</div>

        <div class="large-content-box">
            <div class="roster-container">
                <?php if (empty($decoratedGlyphs)): ?>
                    <p style="color: #eee; font-family: monospace;">No decorated Glyphs found in database.</p>
                <?php else: ?>
                    <?php foreach ($decoratedGlyphs as $index => $glyph): 
                        $rank = $index + 1;
                        $rankClass = $rank === 1 ? 'top-1' : ($rank === 2 ? 'top-2' : ($rank === 3 ? 'top-3' : ''));
                        $pCount = $glyph['series'];
                    ?>
                        <div class="glyph-matrix-card" 
                             data-gens="<?php echo $glyph['GENERATIONS']; ?>"
                             data-peak="<?php echo $glyph['PEAK']; ?>"
                             data-originHash="<?php echo htmlspecialchars($glyph['HASH']); ?>"
                             data-pattern="<?php echo htmlspecialchars($glyph['BIN']); ?>">
                            
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <div class="glyph-identity" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="rank-badge <?php echo $rankClass; ?>">#<?php echo sprintf('%02d', $rank); ?></span>
                                        <h3 style="margin: 0; display: inline-block;"><?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?></h3>
                                        
                                        <div class="glyph-badges" style="display: inline-flex; align-items: center; gap: 4px;">
                                            <?php if ($glyph['gold'] > 0): ?>
                                                <span class="medal-badge medal-gold" title="<?php echo $glyph['gold']; ?> Gold">★ <?php echo $glyph['gold']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($glyph['silver'] > 0): ?>
                                                <span class="medal-badge medal-silver" title="<?php echo $glyph['silver']; ?> Silver">★ <?php echo $glyph['silver']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($glyph['bronze'] > 0): ?>
                                                <span class="medal-badge medal-bronze" title="<?php echo $glyph['bronze']; ?> Bronze">★ <?php echo $glyph['bronze']; ?></span>
                                            <?php endif; ?>

                                            <!-- SVG Series Chevrons (Gold = 5 Series, Green = 1 Series) -->
                                            <div class="chevrons-wrapper">
                                                <?php 
                                                if ($pCount > 0) {
                                                    $golds = floor($pCount / 5);
                                                    $greens = $pCount % 5;

                                                    // Gold Chevrons (5 series each)
                                                    for ($i = 0; $i < $golds; $i++) {
                                                        echo '<svg width="7" height="10" viewBox="0 0 7 10" style="filter: drop-shadow(0 0 3px #f4d042); margin-right: 1px;" title="5 Series Completed">
                                                                <polyline points="1,1 6,5 1,9" fill="none" stroke="#f4d042" stroke-width="2.2" stroke-linecap="round"/>
                                                              </svg>';
                                                    }

                                                    // Green Chevrons (1 series each)
                                                    for ($i = 0; $i < $greens; $i++) {
                                                        echo '<svg width="6" height="10" viewBox="0 0 6 10" style="filter: drop-shadow(0 0 2px var(--accent-green));" title="1 Series Completed">
                                                                <polyline points="1,1 5,5 1,9" fill="none" stroke="var(--accent-green)" stroke-width="1.6" stroke-linecap="round"/>
                                                              </svg>';
                                                    }
                                                } else {
                                                    echo '<span class="pip-rookie-label">NEW</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="glyph-info-compact" style="margin-top: 6px;">
                                        <div>Owner: <?php echo htmlspecialchars($glyph['OWNER'] ?: 'SYSTEM'); ?></div>
                                        <div style="color: var(--accent-green);">
                                            GENS: <?php echo $glyph['GENERATIONS']; ?> | PEAK: <?php echo $glyph['PEAK']; ?>
                                        </div>
                                        <div class="efficiency-tag">
                                            EFFICIENCY: <?php echo number_format($glyph['efficiency'] * 100, 1); ?>% (<?php echo ($glyph['gold']+$glyph['silver']+$glyph['bronze']); ?> Medals / <?php echo $glyph['series']; ?> Series)
                                        </div>
                                    </div>
                                </div>

                                <div class="glyph-preview" data-pattern="<?php echo htmlspecialchars($glyph['BIN']); ?>"></div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255,255,255,0.05); margin-top: 20px; padding-top: 10px; font-size: 0.55rem; color: var(--frame-grey); text-align: center;">
            RANKING HIERARCHY: GOLD > SILVER > BRONZE > MEDAL EFFICIENCY > SERIES PARTICIPATION > PEAK SCORE
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            renderGlyphPreviews();
        });

        function renderGlyphPreviews() {
            document.querySelectorAll('.glyph-preview').forEach(container => {
                const card = container.closest('.glyph-matrix-card');
                const hash = card.getAttribute('data-originHash');
                
                // Extract hex color from hash string
                const hexColor = (hash && hash.length >= 9) ? '#' + hash.substring(3, 9) : 'var(--accent-green)';
                
                const pattern = container.getAttribute('data-pattern');
                const size = 16;
                let svg = `<svg width="40" height="40" viewBox="0 0 ${size} ${size}">`;
                
                for (let i = 0; i < pattern.length; i++) {
                    if (pattern[i] === '1') {
                        const x = i % size;
                        const y = Math.floor(i / size);
                        svg += `<rect x="${x}" y="${y}" width="0.8" height="0.8" fill="${hexColor}" />`;
                    }
                }
                svg += `</svg>`;
                container.innerHTML = svg;
            });
        }
    </script>
</body>
</html>