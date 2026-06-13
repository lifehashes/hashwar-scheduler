<?php
// Include your database configuration when you are ready to pull live states
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

/**
 * HASHWAR WEEKLY SERIES OVERVIEW TEMPLATE
 * Structure:
 * - Sunday: 32 Glyph Roster Draw -> 4 Groups of 8
 * - Monday - Thursday: Round-Robin Phase (Top 3 Advance, 4-7 to Redemption)
 * - Friday: Redemption Day (4 Parallel Knock-out Brackets -> 4 Final Spots)
 * - Saturday: Weekly Finale (16-Contestant Knock-out Bracket)
 */

// Fetch the series_participants list (debugging)
/*
$series_id = isset($_GET['series_id']) && is_numeric($_GET['series_id']) ? (int)$_GET['series_id'] : null;
if ($series_id){
    $query = $pdo->prepare("SELECT * FROM series_participants WHERE series_id = ?");
    $query->execute([$series_id]);
    $seriesData = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
    // no parameter provided
}
*/

// Fetch the current series stats
$series_id = isset($_GET['series_id']) && is_numeric($_GET['series_id']) ? (int)$_GET['series_id'] : null;
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
WHERE sp.series_id = ?
GROUP BY sp.glyph_name
ORDER BY sp.group_label, total_score DESC;");
    $query->execute([$series_id]);
    $seriesData = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
    // no parameter provided
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR // WEEKLY SERIES OVERVIEW</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .series-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            width: 100%;
            margin-top: 20px;
        }
        
        .stage-banner {
            background: rgba(0, 0, 0, 0.4);
            border-left: 4px solid var(--accent-green);
            padding: 10px 15px;
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stage-title {
            margin: 0;
            font-size: 1.2rem;
            letter-spacing: 2px;
            color: #fff;
            text-transform: uppercase;
        }

        .stage-day {
            font-size: 0.8rem;
            color: var(--accent-green);
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Group Phase Layout (4 columns) */
        .groups-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .group-card {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px;
        }

        .group-header {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--accent-green);
            border-bottom: 1px solid rgba(66, 244, 133, 0.3);
            padding-bottom: 5px;
            margin-top: 0;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        /* Leaderboard table simulation */
        .group-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
        }

        .group-table th {
            text-align: left;
            color: #888;
            padding: 4px;
            font-weight: normal;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .group-table td {
            padding: 6px 4px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }

        /* Row threshold colors mapping your rules */
        .row-advance { border-left: 2px solid var(--accent-green); background: rgba(66, 244, 133, 0.02); }
        .row-redemption { border-left: 2px solid #f4d042; background: rgba(244, 208, 66, 0.01); }
        .row-pruned { border-left: 2px solid #f44242; color: #666; }

        /* Bracket Systems (Friday & Saturday) */
        .brackets-wrapper {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .bracket-column {
            flex: 1;
            min-width: 160px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .bracket-round-header {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: var(--frame-grey);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .matchup-card {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            position: relative;
        }

        .matchup-slot {
            display: flex;
            justify-content: space-between;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            padding: 4px 6px;
            background: rgba(0,0,0,0.2);
        }

        .matchup-slot.winner {
            color: var(--accent-green);
            font-weight: bold;
            background: rgba(66, 244, 133, 0.05);
        }

        .matchup-slot span.score {
            font-weight: bold;
            color: var(--frame-grey);
        }
        
        .matchup-slot.winner span.score {
            color: var(--accent-green);
        }

        .match-meta {
            font-size: 0.6rem;
            color: #666;
            text-align: right;
            font-family: 'Courier New', monospace;
            margin-top: 2px;
        }
    </style>
</head>
<body>

<div class="outer-frame">

    <div class="stat-line" style="border-bottom: 1px solid var(--frame-grey); padding-bottom: 5px; margin-bottom: 20px;">
        <span style="font-weight: bold; letter-spacing: 2px;">BUREAU OF ENTROPY // WEEKLY SERIES MONITOR</span>
        <span id="clock-readout">SYSTEM EPOCH: LOADING...</span>
    </div>

    <div class="series-grid">

        <div>
            <div class="stage-banner">
                <h2 class="stage-title">Phase I: Group Qualifications</h2>
                <span class="stage-day">MON // TUE // WED // THU</span>
            </div>
            <p style="font-size: 0.75rem; color: #888; font-family: 'Courier New', monospace; margin: 10px 0;">
                32 Contestants drawn Sunday night. 8-variant round-robin matrix execution. Top 3 advance to Finals; Rank 4–7 proceed to Redemption Day.
            </p>
            
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

        <div>
            <div class="stage-banner" style="border-left-color: #f4d042;">
                <h2 class="stage-title">Phase II: Redemption Day</h2>
                <span class="stage-day" style="color: #f4d042;">FRIDAY NIGHT // MATCHES 01 - 12</span>
            </div>
            <p style="font-size: 0.75rem; color: #888; font-family: 'Courier New', monospace; margin: 10px 0;">
                Four in-group knock-out tourneys of group Rank 4 vs 7 and 5 vs 6. Winners of each tourney claim the final 4 slots for Saturday's Grand Finale.
            </p>

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

        <div>
            <div class="stage-banner" style="border-left-color: var(--accent-green); background: rgba(66, 244, 133, 0.05);">
                <h2 class="stage-title winner-pulse-glow">Phase III: The Weekly Finale</h2>
                <span class="stage-day">SATURDAY NIGHT</span>
            </div>
            <p style="font-size: 0.75rem; color: #888; font-family: 'Courier New', monospace; margin: 10px 0;">
                16-variant high-entropy bracket compilation.
            </p>

            <div class="brackets-wrapper" style="display: grid; grid-template-columns: repeat(3, minmax(140px, 190px)) 1fr repeat(3, minmax(140px, 190px)); gap: 10px; align-items: center; width: 100%;">
                
                <div class="bracket-column">
                    <div class="bracket-round-header">Round of 16 (Left)</div>
                    <?php for ($m = 1; $m <= 4; $m++): ?>
                        <div class="matchup-card">
                            <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                            <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                            <div class="match-meta">FINALS // MATCH 0<?php echo $m; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: space-around; height: 100%; min-height: 440px;">
                    <div class="bracket-round-header">Quarter-Finals</div>
                    <?php for ($m = 1; $m <= 2; $m++): ?>
                        <div class="matchup-card">
                            <div class="matchup-slot"><span>M<?php echo ($m * 2) - 1; ?> Winner</span><span class="score">--</span></div>
                            <div class="matchup-slot"><span>M<?php echo ($m * 2); ?> Winner</span><span class="score">--</span></div>
                            <div class="match-meta">FINALS // MATCH 0<?php echo 8 + $m; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: center; height: 100%; min-height: 440px;">
                    <div class="bracket-round-header">Semi-Final 1</div>
                    <div class="matchup-card">
                        <div class="matchup-slot"><span>M09 Winner</span><span class="score">--</span></div>
                        <div class="matchup-slot"><span>M10 Winner</span><span class="score">--</span></div>
                        <div class="match-meta">SEMI 1 // MATCH 13</div>
                    </div>
                </div>


                <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: center; gap: 20px; align-items: center; border-left: 1px dashed rgba(255,255,255,0.08); border-right: 1px dashed rgba(255,255,255,0.08); padding: 0 10px; height: 100%; min-height: 460px;">
                    
                    <div style="width: 100%; text-align: center;">
                        <div class="bracket-round-header" style="text-align: center; color: var(--accent-green); letter-spacing: 2px;">Grand Championship</div>
                        <div class="matchup-card" style="border: 1px solid var(--accent-green); box-shadow: 0 0 15px rgba(66, 244, 133, 0.15);">
                            <div class="matchup-slot"><span>M13 Champion</span><span class="score">--</span></div>
                            <div class="matchup-slot"><span>M14 Champion</span><span class="score">--</span></div>
                            <div class="match-meta" style="color: var(--accent-green);">FINALS // MATCH 15</div>
                        </div>
                    </div>

                    <div style="width: 100%; text-align: center; opacity: 0.85;">
                        <div class="bracket-round-header" style="text-align: center; color: #f4d042; letter-spacing: 1px;">3rd Place Playoff</div>
                        <div class="matchup-card" style="border: 1px solid rgba(244, 208, 66, 0.4);">
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
                    <div class="matchup-card">
                        <div class="matchup-slot"><span>M11 Winner</span><span class="score">--</span></div>
                        <div class="matchup-slot"><span>M12 Winner</span><span class="score">--</span></div>
                        <div class="match-meta">SEMI 2 // MATCH 14</div>
                    </div>
                </div>

                <div class="bracket-column" style="display: flex; flex-direction: column; justify-content: space-around; height: 100%; min-height: 440px;">
                    <div class="bracket-round-header" style="text-align: right;">Quarter-Finals</div>
                    <?php for ($m = 3; $m <= 4; $m++): ?>
                        <div class="matchup-card">
                            <div class="matchup-slot"><span>M<?php echo ($m * 2) - 1; ?> Winner</span><span class="score">--</span></div>
                            <div class="matchup-slot"><span>M<?php echo ($m * 2); ?> Winner</span><span class="score">--</span></div>
                            <div class="match-meta">FINALS // MATCH <?php echo 8 + $m; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="bracket-column">
                    <div class="bracket-round-header" style="text-align: right;">Round of 16 (Right)</div>
                    <?php for ($m = 5; $m <= 8; $m++): ?>
                        <div class="matchup-card">
                            <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                            <div class="matchup-slot"><span>[Seed / Draw Slot]</span><span class="score">--</span></div>
                            <div class="match-meta">FINALS // MATCH 0<?php echo $m; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script>

    document.addEventListener('DOMContentLoaded', getSeriesData);

    // System Clock Synchronizer
    function updateClock() {
        const now = new Date();
        document.getElementById('clock-readout').innerText = "SYSTEM EPOCH: " + now.toISOString().replace('T', ' ').substring(0, 19) + " UTC";
    }
    setInterval(updateClock, 1000);
    updateClock();

    /*
    function populateLeaderboards(data) {
        // 1. Clear existing placeholders (optional, keeps it clean)
        document.querySelectorAll('.group-table tbody').forEach(tbody => {
            tbody.innerHTML = '';
        });

        // 2. Iterate through data and append rows
        data.forEach((contestant, index) => {
            const groupLetter = contestant.group_label;
            const tbody = document.querySelector(`#group-${groupLetter} .group-table tbody`);
            
            if (tbody) {
                // Determine row style based on rank (1-3: advance, 4-7: redemption, 8: pruned)
                // Note: index calculation might need adjustment depending on how you sort your groups
                let rowClass = 'row-pruned';
                const rank = tbody.children.length + 1;
                
                if (rank <= 3) rowClass = 'row-advance';
                else if (rank <= 7) rowClass = 'row-redemption';

                // Create row HTML
                const row = document.createElement('tr');
                row.className = rowClass;
                row.innerHTML = `
                    <td>0${rank}</td>
                    <td>${contestant.glyph_name}</td>
                    <td style="text-align: right;" class="stat-val">${contestant.total_score}</td>
                `;
                tbody.appendChild(row);
            }
        });
    }
    */

    function populateTournament(data) {
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
        data.forEach(p => groups[p.group_label]?.push(p));

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
                        targetSlot.innerHTML = `<span>${rank}${rank === 4 ? 'th' : rank === 5 ? 'th' : rank === 6 ? 'th' : 'th'}: ${p.glyph_name}</span><span class="score">${p.total_score}</span>`;
                    }
                }
            });
        });
    }

    function getSeriesData(){

        const currentSeriesData = <?php echo json_encode($seriesData); ?>;
        console.log("[weekly-overview.php] getSeriesData(): data = ");
        console.table(currentSeriesData);

        // populateLeaderboards(currentSeriesData);
        populateTournament(currentSeriesData);

    }

</script>
</body>
</html>