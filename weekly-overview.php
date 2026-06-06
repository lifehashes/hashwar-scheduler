<?php
// Global Error Debugging Switch
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Future Database Hook Placement
// include_once __DIR__ . '/../../priv/db_conf_laniakea.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR // WEEKLY SERIES PROTOCOL</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .timeline-container-horizontal {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            width: 100%;
            margin-top: 20px;
            box-sizing: border-box;
        }

        .day-protocol-block-hz {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
            font-family: 'Courier New', monospace;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 520px;
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

        /* Symbolic Matrix Wrapper */
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

        /* Micro Scoreboard Styles */
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
        .micro-scoreboard td {
            padding: 3px 0;
        }
        .row-advance { color: var(--accent-green); font-weight: bold; }
        .row-redemption { color: #f4d042; }
        .row-pruned { color: #ef4444; opacity: 0.6; }

        .status-badge-hz {
            font-size: 0.65rem;
            text-align: center;
            padding: 4px 0;
            border: 1px solid #444;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            box-sizing: border-box;
            margin-top: auto;
        }
        .status-badge-hz.pending { color: #f4d042; border-color: rgba(244, 208, 66, 0.4); }
    </style>
</head>
<body>

<div class="outer-frame">
    <div class="stat-line" style="border-bottom: 1px solid var(--frame-grey); padding-bottom: 5px; margin-bottom: 25px;">
        <span style="font-weight: bold; letter-spacing: 2px;">BUREAU OF ENTROPY // SYSTEM INFRASTRUCTURE GRAPHICS</span>
        <span id="clock-readout">SYSTEM EPOCH: SYNCHRONIZING...</span>
    </div>

    <div style="margin-bottom: 20px;">
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
            <div class="status-badge-hz pending">INITIALIZING</div>
        </div>

        <?php 
        $rrDays = [
            'MONDAY'    => ['CYCLE_01', 'RR: Openings', 'Initial matrix loops activate to compile base states.'],
            'TUESDAY'   => ['CYCLE_02', 'RR: Acceleration', 'Standings solidify over heavy computing blocks.'],
            'WEDNESDAY' => ['CYCLE_03', 'RR: High Entropy', 'Critical point-margin deltas build across columns.'],
            'THURSDAY'  => ['RESOLVE',  'RR: Final Standings', 'Group finalization. Top 3 advance, 4-7 drop to Friday.']
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
                        <tr class="row-pruned"><td>08. OMEGA</td><td style="text-align:right;">--</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="status-badge-hz">STANDBY</div>
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
            <div class="status-badge-hz">STANDBY</div>
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
            <div class="status-badge-hz">STANDBY</div>
        </div>

    </div>
</div>

<script>
    // System Clock Synchronizer
    function updateClock() {
        const now = new Date();
        document.getElementById('clock-readout').innerText = "SYSTEM EPOCH: " + now.toISOString().replace('T', ' ').substring(0, 19) + " UTC";
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
</body>
</html>