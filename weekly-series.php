<?php
session_start();
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Protection Guard: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$current_operator = htmlspecialchars($_SESSION['username'] ?? 'UNKNOWN');

// Holding structures
$seriesCounts    = [];
$medals          = []; // Global medals tally for Hall of Fame
$seriesPodiums   = []; // Per-series medals: [ series_id => ['gold' => X, 'silver' => Y, 'bronze' => [Z1, Z2]] ]
$seriesList      = [];
$decoratedGlyphs = [];

try {
    // 1. Fetch participation counts per glyph across all weekly series
    $participationsStmt = $pdo->query("
        SELECT glyph_name, COUNT(DISTINCT series_id) AS series_count 
        FROM series_participants 
        WHERE phase_id = '1'
        GROUP BY glyph_name
    ");
    $participationsRaw = $participationsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($participationsRaw as $row) {
        $seriesCounts[strtoupper($row['glyph_name'])] = (int)$row['series_count'];
    }

    // 2. Fetch all series records
    $seriesStmt = $pdo->query("SELECT id, designation, type, start_date FROM series ORDER BY id DESC");
    $seriesList = $seriesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function to initialize medals structure for a glyph
    $initGlyphMedal = function($name) use (&$medals) {
        if (!isset($medals[$name])) {
            $medals[$name] = ['gold' => 0, 'silver' => 0, 'bronze' => 0];
        }
    };

    // 3. Process Podium Winners for each Series
    foreach ($seriesList as $s) {
        $sId = $s['id'];
        $seriesPodiums[$sId] = ['gold' => null, 'silver' => null, 'bronze' => []];

        // Locate the unique finale tournament_id for this series (Phase 3 / Group F)
        $finaleStmt = $pdo->prepare("
            SELECT DISTINCT tournament_id 
            FROM series_participants 
            WHERE series_id = :series_id 
              AND (phase_id = '3' OR LOWER(group_label) = 'f')
            LIMIT 1
        ");
        $finaleStmt->execute([':series_id' => $sId]);
        $tournamentId = $finaleStmt->fetchColumn();

        if (!$tournamentId) {
            continue; // Series not completed or no finale configured yet
        }

        // Fetch matches for this finale tournament with aggregate scores from match_rounds
        $matchesStmt = $pdo->prepare("
            SELECT 
                m.id AS match_id,
                m.match_designation,
                UPPER(m.p1_glyph_name) AS p1,
                UPPER(m.p2_glyph_name) AS p2,
                COALESCE(SUM(mr.p1_final_score), 0) AS total_p1,
                COALESCE(SUM(mr.p2_final_score), 0) AS total_p2
            FROM matches m
            LEFT JOIN match_rounds mr ON m.id = mr.match_id
            WHERE m.tournament_id = :tournament_id
            GROUP BY m.id, m.match_designation, m.p1_glyph_name, m.p2_glyph_name
        ");
        $matchesStmt->execute([':tournament_id' => $tournamentId]);
        $fMatches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fMatches as $m) {
            $p1      = $m['p1'];
            $p2      = $m['p2'];
            $p1Score = (int)$m['total_p1'];
            $p2Score = (int)$m['total_p2'];

            if ($p1Score > $p2Score) {
                $winner = $p1; $loser = $p2;
            } elseif ($p2Score > $p1Score) {
                $winner = $p2; $loser = $p1;
            } else {
                continue; // Skip ties
            }

            $initGlyphMedal($winner);
            $initGlyphMedal($loser);

            $designation = strtoupper($m['match_designation']);

            // FINALE: Match 1/1 -> Gold & Silver
            if (strpos($designation, '1/1') !== false) {
                $medals[$winner]['gold']++;
                $medals[$loser]['silver']++;

                $seriesPodiums[$sId]['gold']   = $winner;
                $seriesPodiums[$sId]['silver'] = $loser;
            } 
            // SEMI-FINALS: Match 1/2 or Match 2/2 -> Loser gets Bronze
            elseif (strpos($designation, '1/2') !== false || strpos($designation, '2/2') !== false) {
                $medals[$loser]['bronze']++;
                $seriesPodiums[$sId]['bronze'][] = $loser;
            }
        }
    }

    // 4. Fetch registered Glyphs for the Hall of Fame
    $glyphStmt = $pdo->query("
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
    $allGlyphs = $glyphStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allGlyphs as $glyph) {
        $upperName = strtoupper($glyph['BATTLE_NAME']);
        
        $gold   = $medals[$upperName]['gold']   ?? 0;
        $silver = $medals[$upperName]['silver'] ?? 0;
        $bronze = $medals[$upperName]['bronze'] ?? 0;
        $series = $seriesCounts[$upperName] ?? 0;

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

    // 5. Sort Olympic Model Priority Hierarchy
    usort($decoratedGlyphs, function($a, $b) {
        if ($a['gold'] !== $b['gold'])          return $b['gold'] <=> $a['gold'];
        if ($a['silver'] !== $b['silver'])      return $b['silver'] <=> $a['silver'];
        if ($a['bronze'] !== $b['bronze'])      return $b['bronze'] <=> $a['bronze'];
        if ($a['efficiency'] !== $b['efficiency']) return $b['efficiency'] <=> $a['efficiency'];
        if ($a['series'] !== $b['series'])      return $b['series'] <=> $a['series'];
        return $b['PEAK'] <=> $a['PEAK'];
    });

} catch (PDOException $e) {
    echo "<div style='color: #ff5555; background: #111; padding: 20px; font-family: monospace; border: 1px solid #ff5555;'>";
    echo "<h3>DATABASE ERROR ENCOUNTERED:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEEKLY SERIES // LANDING HUB</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        html { scrollbar-gutter: stable; }
        .hub-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        @media (max-width: 900px) { .hub-grid { grid-template-columns: 1fr; } }

        /* Control Surfaces (Left Column) */
        .series-control-surface {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(10, 15, 12, 0.7); border: 1px solid rgba(0, 255, 65, 0.25);
            border-left: 4px solid var(--accent-green); padding: 12px 16px; margin-bottom: 10px;
            border-radius: 4px; cursor: pointer; transition: all 0.2s ease-in-out; text-decoration: none; color: inherit;
        }
        .series-control-surface:hover {
            background: rgba(0, 255, 65, 0.08); border-color: var(--accent-green);
            transform: translateX(3px); box-shadow: 0 0 10px rgba(0, 255, 65, 0.2);
        }
        .series-id-badge { font-family: monospace; font-size: 1.4rem; font-weight: bold; color: var(--accent-green); letter-spacing: 1px; line-height: 1; }
        .series-meta { display: flex; flex-direction: column; gap: 4px; }
        .weight-class-tag { font-family: monospace; font-size: 0.65rem; color: var(--frame-grey); text-transform: uppercase; letter-spacing: 1px; }
        .podium-mini-list { font-family: monospace; font-size: 0.7rem; display: flex; gap: 10px; flex-wrap: wrap; }
        .podium-item { display: inline-flex; align-items: center; gap: 3px; }
        .podium-item.gold { color: #ffd700; } 
        .podium-item.silver { color: #c0c0c0; } 
        .podium-item.bronze { color: #cd7f32; }

        /* Hall of Fame Badges (Right Column) */
        .rank-badge { font-family: monospace; font-size: 0.85rem; font-weight: bold; color: var(--accent-green); background: rgba(0, 255, 65, 0.1); border: 1px solid rgba(0, 255, 65, 0.3); padding: 2px 6px; border-radius: 3px; margin-right: 6px; }
        .top-1 { color: #ffd700; border-color: #ffd700; background: rgba(255, 215, 0, 0.15); }
        .top-2 { color: #c0c0c0; border-color: #c0c0c0; background: rgba(192, 192, 192, 0.15); }
        .top-3 { color: #cd7f32; border-color: #cd7f32; background: rgba(205, 127, 50, 0.15); }
        .efficiency-tag { font-size: 0.65rem; color: var(--frame-grey); font-family: monospace; }
        .medal-badge { display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: bold; padding: 1px 5px; border-radius: 3px; margin-right: 4px; font-family: monospace; line-height: 1; }
        .medal-gold { color: #ffd700; background: rgba(255, 215, 0, 0.15); border: 1px solid rgba(255, 215, 0, 0.5); box-shadow: 0 0 5px rgba(255, 215, 0, 0.3); }
        .medal-silver { color: #c0c0c0; background: rgba(192, 192, 192, 0.15); border: 1px solid rgba(192, 192, 192, 0.5); box-shadow: 0 0 5px rgba(192, 192, 192, 0.2); }
        .medal-bronze { color: #cd7f32; background: rgba(205, 127, 50, 0.15); border: 1px solid rgba(205, 127, 50, 0.5); box-shadow: 0 0 5px rgba(205, 127, 50, 0.2); }
        .pip-rookie-label { font-size: 0.6rem; font-family: monospace; color: #888; border: 1px dashed #555; padding: 0 3px; border-radius: 2px; }
        .chevrons-wrapper { display: inline-flex; align-items: center; vertical-align: middle; margin-left: 4px; gap: 1px; }

        /* Active / Current Series Surface Styling */
        .series-control-surface.active-series {
            background: rgba(0, 255, 65, 0.08);
            border-color: var(--accent-green);
            border-left: 6px solid var(--accent-green);
            box-shadow: 0 0 12px rgba(0, 255, 65, 0.25);
            animation: activeGlowPulse 2.5s infinite ease-in-out;
        }

        /* Subtle Breathing Animation for Current Series */
        @keyframes activeGlowPulse {
            0% {
                box-shadow: 0 0 8px rgba(0, 255, 65, 0.2);
                border-color: rgba(0, 255, 65, 0.6);
            }
            50% {
                box-shadow: 0 0 18px rgba(0, 255, 65, 0.45);
                border-color: rgba(0, 255, 65, 1);
            }
            100% {
                box-shadow: 0 0 8px rgba(0, 255, 65, 0.2);
                border-color: rgba(0, 255, 65, 0.6);
            }
        }

        /* Status Badges */
        .status-badge-live {
            font-size: 0.55rem;
            font-family: monospace;
            color: #000;
            background: var(--accent-green);
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 0 6px var(--accent-green);
        }

        .status-badge-closed {
            font-size: 0.55rem;
            font-family: monospace;
            color: var(--frame-grey);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0px 4px;
            border-radius: 2px;
        }

        /* Subtle dimming for archived past series */
        .series-control-surface:not(.active-series) {
            opacity: 0.85;
        }

        .series-control-surface:not(.active-series):hover {
            opacity: 1;
        }

        .series-open-arrow {
            color: var(--accent-green);
            font-family: monospace;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .tab-button {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--frame-grey, #888);
            padding: 8px 16px;
            font-family: monospace;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab-button.exit-button {
            color: #ff4d4d;
            border-color: rgba(255, 77, 77, 0.4);
            background: rgba(255, 0, 0, 0.05);
        }
        .tab-button.exit-button:hover {
            color: #ff1a1a;
            border-color: #ff4d4d;
            background: rgba(255, 0, 0, 0.15);
            box-shadow: 0 0 12px rgba(255, 77, 77, 0.3);
        }

    </style>
</head>
<body>

    <div class="outer-frame">
        <div style="font-size: 0.65rem; color: var(--accent-green); margin-bottom: 15px; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 20px; align-items: center;">
                <span>SYS_STATUS: AUTHORIZED // ACCESS_LEVEL: OPERATOR</span>
                <span style="color: var(--frame-grey);">|</span>
                <span>OPERATOR: <?php echo $current_operator; ?></span>
            </div>
            <button class="tab-button exit-button" onclick="window.location.href='https://lifehashes.net/';">[ BACK TO MAIN_HUB ]</button>
        </div>

        <div class="hub-grid">
            <!-- LEFT HALF: DYNAMIC SERIES CONTROL SURFACES -->
            <div class="hub-left-column">
                <div class="panel-title" style="margin-bottom:15px;">WEEKLY SERIES // SELECT TOURNAMENT</div>
                <div class="large-content-box" style="min-height: 300px;">
                    <?php if (empty($seriesList)): ?>
                        <p style="color: #eee; font-family: monospace;">No active or past weekly series found.</p>
                    <?php else: ?>
                        <?php foreach ($seriesList as $index => $s): 
                            $sId = $s['id'];
                            $podium = $seriesPodiums[$sId] ?? ['gold' => null, 'silver' => null, 'bronze' => []];
                            $weightClass = 500;
                            $bronzeDisplay = !empty($podium['bronze']) ? implode(', ', $podium['bronze']) : 'TBD';

                            // Highlight the latest series as ACTIVE (index 0 assuming DESC sort)
                            $isCurrent = ($index === 0); 
                            $cardClass = $isCurrent ? 'series-control-surface active-series' : 'series-control-surface';
                        ?>
                            <a href="weekly-overview.php?series_id=<?php echo urlencode($sId); ?>" class="<?php echo $cardClass; ?>">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="series-id-badge">[ <?php echo sprintf('%02d', $sId); ?> ]</div>
                                    
                                    <div class="series-meta">
                                        <div class="weight-class-tag" style="display: flex; align-items: center; gap: 8px;">
                                            <span>WEIGHT CLASS: <?php echo $weightClass; ?></span>
                                            <?php if ($isCurrent): ?>
                                                <span class="status-badge-live">ONGOING</span>
                                            <?php else: ?>
                                                <span class="status-badge-closed">ARCHIVE</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="podium-mini-list">
                                            <span class="podium-item gold" title="Gold Winner">
                                                ★ <?php echo htmlspecialchars($podium['gold'] ?? 'TBD'); ?>
                                            </span>
                                            <span class="podium-item silver" title="Silver Winner">
                                                ★ <?php echo htmlspecialchars($podium['silver'] ?? 'TBD'); ?>
                                            </span>
                                            <span class="podium-item bronze" title="Bronze Winners">
                                                ★ <?php echo htmlspecialchars($bronzeDisplay); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="series-open-arrow">
                                    <?php echo $isCurrent ? 'ENTER &gt;' : 'OPEN &gt;'; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT HALF: HALL OF FAME -->
            <div class="hub-right-column">
                <div class="panel-title" style="margin-bottom:15px;">HALL_OF_FAME // TOP <?php echo count($decoratedGlyphs); ?> DECORATED GLYPHS</div>

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

                                                    <div class="chevrons-wrapper">
                                                        <?php 
                                                        if ($pCount > 0) {
                                                            $golds = floor($pCount / 5);
                                                            $greens = $pCount % 5;

                                                            for ($i = 0; $i < $golds; $i++) {
                                                                echo '<svg width="7" height="10" viewBox="0 0 7 10" style="filter: drop-shadow(0 0 3px #f4d042); margin-right: 1px;" title="5 Series Completed">
                                                                        <polyline points="1,1 6,5 1,9" fill="none" stroke="#f4d042" stroke-width="2.2" stroke-linecap="round"/>
                                                                      </svg>';
                                                            }

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
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            renderGlyphPreviews();
        });

        function selectSeries(seriesId) {
            window.location.href = `weekly-overview.php?series_id=${encodeURIComponent(seriesId)}`;
        }

        function renderGlyphPreviews() {
            document.querySelectorAll('.glyph-preview').forEach(container => {
                const card = container.closest('.glyph-matrix-card');
                const hash = card.getAttribute('data-originHash');
                
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