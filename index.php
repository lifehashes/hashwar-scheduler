<?php
include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

// Fetch all available Conway Glyphs from the database for the roster
$stmt = $pdo->query("SELECT BATTLE_NAME, BIN, ITERATIONS as GENERATIONS, PEAK, MAX, MIN, HASH, TERMINAL, OWNER FROM GLYPHREG ORDER BY BATTLE_NAME ASC");
$glyphs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR-SCHEDULER :: v100</title>
    <link rel="stylesheet" href="styles.css">
    <script src="js/sha256.js"></script>  
    <style>
        /* Incremental layout adjustments extending your engine styles */
        .overseer-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            width: 100%;
        }
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
        .roster-container {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            height: 82vh;
            overflow-y: auto;
        }
        .glyph-matrix-card {
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 8px;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }
        .glyph-matrix-card:hover {
            border-color: var(--accent-green);
            background: rgba(66, 244, 133, 0.05);
        }
        .glyph-identity h3 {
            margin: 0;
            color: #fff;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .glyph-identity span {
            font-size: 0.7rem;
            color: #888;
            font-family: 'Courier New', monospace;
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
        .stat-badge-group {
            display: flex;
            gap: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
        }
        .stat-badge-unit text { color: #888; }
        .stat-badge-unit val { color: var(--accent-green); font-weight: bold; }
    </style>
</head>
<body>

<div class="outer-frame">
    <div class="stat-line" style="border-bottom: 1px solid var(--frame-grey); padding-bottom: 5px; margin-bottom: 20px;">
        <span style="font-weight: bold; letter-spacing: 2px;">BUREAU OF ENTROPY // THE OVERSEER</span>
        <span id="clock-readout">SYSTEM EPOCH: LOADING...</span>
    </div>

    <div class="overseer-grid">
        
        <div>
            <div class="panel-header" style="background: var(--panel-bg); padding: 10px 15px; border: 1px solid rgba(255,255,255,0.05); border-bottom: none; margin-bottom: 0;">
                <span>Registered Gladiators (<?php echo count($glyphs); ?> total)</span>
                <span style="font-size: 0.7rem; color: #888;">ORDER: BATTLE_NAME ASC</span>
            </div>
            <div class="roster-container">
                <?php foreach ($glyphs as $glyph): ?>
                    <div class="glyph-matrix-card" data-hash="<?php echo htmlspecialchars($glyph['HASH']); ?>">
                        <div class="glyph-identity">
                            <h3><?php echo htmlspecialchars($glyph['BATTLE_NAME']); ?></h3>
                            <span>Owner: <text style="color: var(--accent-green);"><?php echo htmlspecialchars($glyph['OWNER'] ?? 'SYSTEM'); ?></text></span>
                        </div>
                        <div class="stat-badge-group">
                            <div class="stat-badge-unit"><text>GENS:</text> <val><?php echo $glyph['GENERATIONS']; ?></val></div>
                            <div class="stat-badge-unit"><text>PEAK:</text> <val><?php echo $glyph['PEAK']; ?></val></div>
                            <div class="stat-badge-unit"><text>MAX:</text> <val><?php echo $glyph['MAX']; ?></val></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="timeline-control-panel">
            <h2 class="panel-header">
                Sacred Timeline
                <span class="status-badge" id="tva-status">MONITORING</span>
            </h2>
            
            <p style="font-size: 0.75rem; color: #aaa; line-height: 1.4;">
                Poll the external cryptographic anchor. Capturing a 512-bit raw hex variant pulse from the NIST Randomness Beacon and coercing it to an immutable, auditable <text style="color: var(--accent-green);">INT(11)</text> engine seed.
            </p>

            <h3 style="font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; color: var(--frame-grey);">NIST Pulse Output:</h3>
            <div class="seed-display-box" id="raw-pulse-box">AWAITING QUANTUM HARVEST...</div>

            <h3 style="font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; color: var(--frame-grey);">Coerced Engine Seed:</h3>
            <div class="seed-display-box" id="coerced-seed-box" style="font-size: 1.2rem; text-align: center; color: var(--accent-green); font-weight: bold;">0000000000</div>

            <button class="action-button" id="prune-timeline-btn">Harvest NIST Pulse</button>

            <div style="margin-top: 30px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 20px;">
                <h3 style="font-size: 0.8rem; margin-bottom: 10px; text-transform: uppercase; color: var(--accent-green);">Broadcast Overlays</h3>
                <p style="font-size: 0.7rem; color: #888; margin-bottom: 15px;">Capture these paths as OBS Studio Browser Sources for flawless match analytics.</p>
                <button class="action-button" style="font-size: 0.7rem; border-color: #aaa; color: #aaa;" onclick="alert('Path mapped to overlay: stats-view.php')">Launch Tale-of-the-Tape Panel</button>
            </div>
        </div>

    </div>
</div>

<script>
    // System Clock matching the universe epoch
    function updateClock() {
        const now = new Date();
        document.getElementById('clock-readout').innerText = "SYSTEM EPOCH: " + now.toISOString().replace('T', ' ').substring(0, 19) + " UTC";
    }
    setInterval(updateClock, 1000);
    updateClock();

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
        const currentTimestamp = Math.floor(Date.now() / 1000);
        
        // Using a public proxy fallback if standard CORS restrictions intercept native browser requests
        fetch(`https://beacon.nist.gov/beacon/2.0/pulse/time/${currentTimestamp}`)
            .then(response => {
                if(!response.ok) throw new Error("Network latency in the Sacred Timeline.");
                return response.json();
            })
            .then(data => {
                // Grab the 512-bit (128 hex character) output value string
                const rawHex = data.pulse.outputValue;
                pulseBox.innerText = rawHex;

                // CRITICAL CORNERSTONE: 
                // Slice exactly the first 7 hexadecimal characters. 
                // 7 hex chars max out at 0xFFFFFFF (268,435,455 decimal), 
                // matching seamlessly and cleanly inside the INT(11) signed limit of 2,147,483,647.
                const hexSlice = rawHex.substring(0, 7);
                const engineSeed = parseInt(hexSlice, 16);

                // Update UI display
                seedBox.innerText = engineSeed;
                
                statusBadge.innerText = "TIMELINE LOCKED";
                statusBadge.style.background = "rgba(66, 244, 133, 0.2)";
                statusBadge.style.color = "var(--accent-green)";
                
                btn.disabled = false;
                btn.innerText = "Harvest NIST Pulse";

                console.log(`[THE OVERSEER] NIST Hex: ${rawHex} -> Slice: ${hexSlice} -> INT(11) Seed: ${engineSeed}`);
            })
            .catch(error => {
                console.warn(error);
                // Fallback deterministic simulation mode if NIST API hits network throttling
                const localFallbackHex = crypto.subtle ? 'A576F821B000CD91BFA3C672B10906BC' : 'DEADBEEF101010101010101010101010';
                pulseBox.innerText = localFallbackHex + " [LOCAL TIMELINE FALLBACK]";
                
                const hexSlice = localFallbackHex.substring(0, 7);
                const engineSeed = parseInt(hexSlice, 16);
                
                seedBox.innerText = engineSeed;
                statusBadge.innerText = "PRUNED FALLBACK";
                statusBadge.style.background = "rgba(244, 66, 66, 0.2)";
                statusBadge.style.color = "#f44242";
                
                btn.disabled = false;
                btn.innerText = "Harvest NIST Pulse";
            });
    });
</script>
</body>
</html>