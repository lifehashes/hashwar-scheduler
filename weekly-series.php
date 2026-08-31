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

$current_operator = htmlspecialchars($_SESSION['username'] ?? 'UNKNOWN');

// ---------------------------------------------------------
// Handle Weight Class Filtering (GET param)
// ---------------------------------------------------------
$allowedWeightClasses = ['ALL', '500', '600', '700', '800'];
$selectedWeightClass = $_GET['weight_class'] ?? 'ALL';

if (!in_array($selectedWeightClass, $allowedWeightClasses)) {
    $selectedWeightClass = 'ALL';
}

// Holding structures
$seriesCounts    = [];
$medals          = []; // Global medals tally for Hall of Fame
$seriesPodiums   = []; // Per-series medals: [ designation => ['gold' => X, 'silver' => Y, 'bronze' => [Z1, Z2]] ]
$seriesList      = [];
$decoratedGlyphs = [];

try {
    // ---------------------------------------------------------
    // Fetch Series with correct `weight_class` mapped by `designation`
    // ---------------------------------------------------------
    $seriesStmt = $pdo->query("SELECT id, designation, weight_class, start_date FROM weekly_entries ORDER BY designation DESC");
    $rawSeriesList = $seriesStmt->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------------
    // Limit Future Series: Retain all past/current series + at most 1 future series
    // ---------------------------------------------------------
    $today = date('Y-m-d H:i:s');
    $pastOrCurrent = [];
    $futureSeries  = [];

    foreach ($rawSeriesList as $s) {
        if (isset($s['start_date']) && $s['start_date'] > $today) {
            $futureSeries[] = $s;
        } else {
            $pastOrCurrent[] = $s;
        }
    }

    // Sort future series chronologically to pick the immediate next one
    usort($futureSeries, function($a, $b) {
        return strtotime($a['start_date']) <=> strtotime($b['start_date']);
    });

    // Keep only 1 upcoming future series (if any exist)
    $nextFutureSeries = array_slice($futureSeries, 0, 1);

    // Merge: single future series at top, followed by past/current series (descending)
    $seriesList = array_merge($nextFutureSeries, $pastOrCurrent);

    // Map series weight classes by `designation` instead of `id`
    $seriesWeightClasses = [];
    foreach ($seriesList as $s) {
        $seriesWeightClasses[$s['designation']] = (int)($s['weight_class'] ?? 500);
    }

    // 1. Fetch participation counts per glyph matching via `designation`
    $participationsSql = "
        SELECT sp.glyph_name, COUNT(DISTINCT sp.series_id) AS series_count 
        FROM series_participants sp
        INNER JOIN weekly_entries we ON sp.series_id = we.designation
        WHERE sp.phase_id = '1'
    ";
    $participationsParams = [];
    if ($selectedWeightClass !== 'ALL') {
        $participationsSql .= " AND we.weight_class = :wc";
        $participationsParams[':wc'] = $selectedWeightClass;
    }
    $participationsSql .= " GROUP BY sp.glyph_name";

    $participationsStmt = $pdo->prepare($participationsSql);
    $participationsStmt->execute($participationsParams);
    $participationsRaw = $participationsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($participationsRaw as $row) {
        $seriesCounts[strtoupper($row['glyph_name'])] = (int)$row['series_count'];
    }

    // Helper function to initialize medals structure for a glyph
    $initGlyphMedal = function($name) use (&$medals) {
        if (!isset($medals[$name])) {
            $medals[$name] = ['gold' => 0, 'silver' => 0, 'bronze' => 0];
        }
    };

    // 3. Process Podium Winners for each Series
    foreach ($seriesList as $s) {
        $designation  = $s['designation'];
        $seriesWeight = $seriesWeightClasses[$designation] ?? 500;
        $seriesPodiums[$designation] = ['gold' => null, 'silver' => null, 'bronze' => []];

        // Locate the unique finale tournament_id using `designation`
        $finaleStmt = $pdo->prepare("
            SELECT DISTINCT tournament_id 
            FROM series_participants 
            WHERE series_id = :series_desig 
              AND (phase_id = '3' OR LOWER(group_label) = 'f')
            LIMIT 1
        ");
        $finaleStmt->execute([':series_desig' => $designation]);
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

            // Always track podium for left-side series cards by designation
            if (strpos(strtoupper($m['match_designation']), '1/1') !== false) {
                $seriesPodiums[$designation]['gold']   = $winner;
                $seriesPodiums[$designation]['silver'] = $loser;
            } elseif (strpos(strtoupper($m['match_designation']), '1/2') !== false || strpos(strtoupper($m['match_designation']), '2/2') !== false) {
                $seriesPodiums[$designation]['bronze'][] = $loser;
            }

            // Only aggregate to global medals list if series matches selected weight class filter
            if ($selectedWeightClass === 'ALL' || (string)$seriesWeight === (string)$selectedWeightClass) {
                $initGlyphMedal($winner);
                $initGlyphMedal($loser);

                $matchDesig = strtoupper($m['match_designation']);
                if (strpos($matchDesig, '1/1') !== false) {
                    $medals[$winner]['gold']++;
                    $medals[$loser]['silver']++;
                } elseif (strpos($matchDesig, '1/2') !== false || strpos($matchDesig, '2/2') !== false) {
                    $medals[$loser]['bronze']++;
                }
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

    // 6. Fetch User Scores from `users` table
    $usersStmt = $pdo->query("SELECT username, score FROM users");
    $userScores = [];
    while ($row = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
        $userScores[$row['username']] = (int)$row['score'];
    }

    // 7. Aggregate Medals, Series Participations, and Scores per Owner
    $ownerStats = [];

    foreach ($allGlyphs as $glyph) {
        $owner     = $glyph['OWNER'] ?: 'SYSTEM';
        $upperName = strtoupper($glyph['BATTLE_NAME']);
        
        $gold   = $medals[$upperName]['gold']   ?? 0;
        $silver = $medals[$upperName]['silver'] ?? 0;
        $bronze = $medals[$upperName]['bronze'] ?? 0;
        $series = $seriesCounts[$upperName]   ?? 0;

        // Skip owners with zero relevant activity for the filtered view
        if ($selectedWeightClass !== 'ALL' && ($gold + $silver + $bronze + $series === 0)) {
            continue;
        }

        if (!isset($ownerStats[$owner])) {
            $ownerStats[$owner] = [
                'username' => $owner,
                'score'    => $userScores[$owner] ?? 0,
                'gold'     => 0,
                'silver'   => 0,
                'bronze'   => 0,
                'series'   => 0,
                'glyphs'   => 0
            ];
        }

        $ownerStats[$owner]['gold']   += $gold;
        $ownerStats[$owner]['silver'] += $silver;
        $ownerStats[$owner]['bronze'] += $bronze;
        $ownerStats[$owner]['series'] += $series;
        $ownerStats[$owner]['glyphs'] += 1;
    }

    // 8. Sort Owners: Gold > Silver > Bronze > User Score > Total Series
    usort($ownerStats, function($a, $b) {
        if ($a['gold'] !== $b['gold'])     return $b['gold'] <=> $a['gold'];
        if ($a['silver'] !== $b['silver']) return $b['silver'] <=> $a['silver'];
        if ($a['bronze'] !== $b['bronze']) return $b['bronze'] <=> $a['bronze'];
        if ($a['score'] !== $b['score'])   return $b['score'] <=> $a['score'];
        return $b['series'] <=> $a['series'];
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

        /* Schedule Action Link Banner */
        .schedule-link-banner {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(0, 255, 65, 0.05); border: 1px solid rgba(0, 255, 65, 0.3);
            padding: 8px 12px; margin-bottom: 15px; border-radius: 4px;
            font-family: monospace; font-size: 0.75rem; color: var(--accent-green);
            text-decoration: none; transition: all 0.2s ease;
        }
        .schedule-link-banner:hover {
            background: rgba(0, 255, 65, 0.15); border-color: var(--accent-green);
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.25); transform: translateX(2px);
        }

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

        .status-badge-future {
            font-size: 0.55rem;
            font-family: monospace;
            color: #ffaa00;
            border: 1px solid rgba(255, 170, 0, 0.4);
            background: rgba(255, 170, 0, 0.1);
            padding: 0px 4px;
            border-radius: 2px;
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

        /* Vertical flex container for the right column */
        .hub-right-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Weight Class Selector Toolbar Dropdown */
        .weight-class-selector-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(10, 15, 12, 0.7);
            border: 1px solid rgba(0, 255, 65, 0.25);
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .weight-class-select {
            background: #000;
            color: var(--accent-green);
            border: 1px solid rgba(0, 255, 65, 0.4);
            font-family: monospace;
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            outline: none;
        }
        .weight-class-select:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 6px rgba(0, 255, 65, 0.4);
        }

        /* Individual split card container */
        .right-split-box {
            display: flex;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 12px;
            border-radius: 4px;
        }

        .split-scroll-area {
            max-height: 380px;
            overflow-y: auto;
            padding-right: 4px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Owner Aggregate Row Styling */
        .owner-matrix-card {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 8px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .owner-matrix-card:hover {
            border-color: var(--accent-green);
            background: rgba(66, 244, 133, 0.05);
        }

        .owner-score-badge {
            text-align: right;
            font-family: monospace;
            line-height: 1.1;
        }

        .owner-score-val {
            font-size: 0.95rem;
            font-weight: bold;
            color: var(--accent-green);
        }

        /* --- Global Entropy-Style Scrollbars --- */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
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
                <div class="panel-title" style="margin-bottom:8px;">WEEKLY SERIES // SELECT TOURNAMENT</div>
                
                <!-- Yearly Series Schedule Link -->
                <a href="https://lifehashes.net/hashwar-scheduler/yearly-overview.php" class="schedule-link-banner">
                    <span>VIEW YEARLY SERIES SCHEDULE</span>
                    <span>OPEN &gt;</span>
                </a>

                <div class="large-content-box" style="min-height: 300px;">
                    <?php if (empty($seriesList)): ?>
                        <p style="color: #eee; font-family: monospace;">No active or past weekly series found.</p>
                    <?php else: ?>
                        <?php foreach ($seriesList as $index => $s): 
                            $designation = $s['designation'];
                            $podium = $seriesPodiums[$designation] ?? ['gold' => null, 'silver' => null, 'bronze' => []];
                            $weightClass = $s['weight_class'] ?? 500;
                            $bronzeDisplay = !empty($podium['bronze']) ? implode(', ', $podium['bronze']) : 'TBD';

                            $isFuture  = isset($s['start_date']) && $s['start_date'] > date('Y-m-d H:i:s');
                            $isCurrent = (!$isFuture && $index === 0) || (!$isFuture && isset($seriesList[0]['start_date']) && $seriesList[0]['start_date'] > date('Y-m-d H:i:s') && $index === 1);
                            
                            $cardClass = $isCurrent ? 'series-control-surface active-series' : 'series-control-surface';
                        ?>
                            <a href="weekly-overview.php?series_id=<?php echo urlencode($designation); ?>" class="<?php echo $cardClass; ?>">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="series-id-badge">[ <?php echo sprintf('%02d', $designation); ?> ]</div>
                                    
                                    <div class="series-meta">
                                        <div class="weight-class-tag" style="display: flex; align-items: center; gap: 8px;">
                                            <span>WEIGHT CLASS: <?php echo htmlspecialchars($weightClass); ?></span>
                                            <?php if ($isFuture): ?>
                                                <span class="status-badge-future">UPCOMING</span>
                                            <?php elseif ($isCurrent): ?>
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

            <!-- RIGHT HALF: VERTICAL SPLIT (GLYPH HOF TOP // OWNER LEADERBOARD BOTTOM) -->
            <div class="hub-right-column">

                <!-- WEIGHT CLASS SELECTOR TOOLBAR -->
                <div class="weight-class-selector-box">
                    <span style="font-family: monospace; font-size: 0.75rem; color: var(--accent-green); font-weight: bold; letter-spacing: 1px;">
                        FILTER WEIGHT CLASS:
                    </span>
                    <form method="GET" action="" style="margin:0;">
                        <select name="weight_class" class="weight-class-select" onchange="this.form.submit()">
                            <option value="ALL" <?php echo $selectedWeightClass === 'ALL' ? 'selected' : ''; ?>>ALL CLASSES</option>
                            <option value="500" <?php echo $selectedWeightClass === '500' ? 'selected' : ''; ?>>500</option>
                            <option value="600" <?php echo $selectedWeightClass === '600' ? 'selected' : ''; ?>>600</option>
                            <option value="700" <?php echo $selectedWeightClass === '700' ? 'selected' : ''; ?>>700</option>
                            <option value="800" <?php echo $selectedWeightClass === '800' ? 'selected' : ''; ?>>800</option>
                        </select>
                    </form>
                </div>

                <!-- TOP SECTION: INDIVIDUAL GLYPH HALL OF FAME -->
                <div class="right-split-box">
                    <div class="panel-title" style="margin-bottom:10px;">HALL_OF_FAME // GLYPH STANDINGS (<?php echo count($decoratedGlyphs); ?>)</div>

                    <div class="split-scroll-area">
                        <?php if (empty($decoratedGlyphs)): ?>
                            <p style="color: #eee; font-family: monospace;">No decorated Glyphs found for this weight class.</p>
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
                                    
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <div style="flex: 1;">
                                            <div class="glyph-identity" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="rank-badge <?php echo $rankClass; ?>">#<?php echo sprintf('%02d', $rank); ?></span>
                                                <h3 style="margin: 0; display: inline-block;"><?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?></h3>
                                                
                                                <div class="glyph-badges" style="display: inline-flex; align-items: center; gap: 4px;">
                                                    <?php if ($glyph['gold'] > 0): ?><span class="medal-badge medal-gold" title="<?php echo $glyph['gold']; ?> Gold">★ <?php echo $glyph['gold']; ?></span><?php endif; ?>
                                                    <?php if ($glyph['silver'] > 0): ?><span class="medal-badge medal-silver" title="<?php echo $glyph['silver']; ?> Silver">★ <?php echo $glyph['silver']; ?></span><?php endif; ?>
                                                    <?php if ($glyph['bronze'] > 0): ?><span class="medal-badge medal-bronze" title="<?php echo $glyph['bronze']; ?> Bronze">★ <?php echo $glyph['bronze']; ?></span><?php endif; ?>

                                                    <div class="chevrons-wrapper">
                                                        <?php 
                                                        if ($pCount > 0) {
                                                            $golds = floor($pCount / 5);
                                                            $greens = $pCount % 5;
                                                            for ($i = 0; $i < $golds; $i++) {
                                                                echo '<svg width="7" height="10" viewBox="0 0 7 10" style="filter: drop-shadow(0 0 3px #f4d042); margin-right: 1px;"><polyline points="1,1 6,5 1,9" fill="none" stroke="#f4d042" stroke-width="2.2" stroke-linecap="round"/></svg>';
                                                            }
                                                            for ($i = 0; $i < $greens; $i++) {
                                                                echo '<svg width="6" height="10" viewBox="0 0 6 10" style="filter: drop-shadow(0 0 2px var(--accent-green));"><polyline points="1,1 5,5 1,9" fill="none" stroke="var(--accent-green)" stroke-width="1.6" stroke-linecap="round"/></svg>';
                                                            }
                                                        } else {
                                                            echo '<span class="pip-rookie-label">NEW</span>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="glyph-info-compact" style="margin-top: 4px;">
                                                Owner: <?php echo htmlspecialchars($glyph['OWNER'] ?: 'SYSTEM'); ?> | EFF: <?php echo number_format($glyph['efficiency'] * 100, 1); ?>%
                                            </div>
                                        </div>

                                        <div class="glyph-preview" data-pattern="<?php echo htmlspecialchars($glyph['BIN']); ?>"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BOTTOM SECTION: OPERATOR / OWNER AGGREGATE LEADERBOARD -->
                <div class="right-split-box">
                    <div class="panel-title" style="margin-bottom:10px;">OPERATOR_RANKINGS // OWNER AGGREGATE</div>

                    <div class="split-scroll-area">
                        <?php if (empty($ownerStats)): ?>
                            <p style="color: #eee; font-family: monospace;">No active owners found for this weight class.</p>
                        <?php else: ?>
                            <?php foreach ($ownerStats as $oIndex => $owner): 
                                $oRank = $oIndex + 1;
                                $oRankClass = $oRank === 1 ? 'top-1' : ($oRank === 2 ? 'top-2' : ($oRank === 3 ? 'top-3' : ''));
                                $oSeries = $owner['series'];
                            ?>
                                <div class="owner-matrix-card">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="rank-badge <?php echo $oRankClass; ?>">#<?php echo sprintf('%02d', $oRank); ?></span>
                                            <strong style="color: #fff; font-family: monospace; font-size: 0.85rem; text-transform: uppercase;">
                                                <?php echo htmlspecialchars($owner['username']); ?>
                                            </strong>
                                            
                                            <div class="glyph-badges" style="display: inline-flex; align-items: center; gap: 4px;">
                                                <?php if ($owner['gold'] > 0): ?><span class="medal-badge medal-gold">★ <?php echo $owner['gold']; ?></span><?php endif; ?>
                                                <?php if ($owner['silver'] > 0): ?><span class="medal-badge medal-silver">★ <?php echo $owner['silver']; ?></span><?php endif; ?>
                                                <?php if ($owner['bronze'] > 0): ?><span class="medal-badge medal-bronze">★ <?php echo $owner['bronze']; ?></span><?php endif; ?>

                                                <!-- Aggregated Owner Chevrons -->
                                                <div class="chevrons-wrapper">
                                                    <?php 
                                                    if ($oSeries > 0) {
                                                        $oGolds = floor($oSeries / 5);
                                                        $oGreens = $oSeries % 5;
                                                        for ($i = 0; $i < $oGolds; $i++) {
                                                            echo '<svg width="7" height="10" viewBox="0 0 7 10" style="filter: drop-shadow(0 0 3px #f4d042); margin-right: 1px;"><polyline points="1,1 6,5 1,9" fill="none" stroke="#f4d042" stroke-width="2.2" stroke-linecap="round"/></svg>';
                                                        }
                                                        for ($i = 0; $i < $oGreens; $i++) {
                                                            echo '<svg width="6" height="10" viewBox="0 0 6 10" style="filter: drop-shadow(0 0 2px var(--accent-green));"><polyline points="1,1 5,5 1,9" fill="none" stroke="var(--accent-green)" stroke-width="1.6" stroke-linecap="round"/></svg>';
                                                        }
                                                    } else {
                                                        echo '<span class="pip-rookie-label">NEW</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="glyph-info-compact" style="margin-top: 4px;">
                                            GLYPHS: <?php echo $owner['glyphs']; ?> | TOTAL SERIES PARTICIPATIONS: <?php echo $owner['series']; ?>
                                        </div>
                                    </div>

                                    <!-- User Score Display -->
                                    <div class="owner-score-badge">
                                        <div style="font-size: 0.55rem; color: #888;">SCORE</div>
                                        <div class="owner-score-val"><?php echo number_format($owner['score']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 6px; font-size: 0.55rem; color: var(--frame-grey); text-align: center;">
                    RANKING HIERARCHY: GOLD > SILVER > BRONZE > USER SCORE > PARTICIPATIONS
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