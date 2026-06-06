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
    <script src="js/hashing.js"></script>  
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
        <span style="font-weight: bold; letter-spacing: 2px;">BUREAU OF ENTROPY // OVERSEER NODE</span>
        <span id="clock-readout">SYSTEM EPOCH: LOADING...</span>
    </div>

    <div class="overseer-grid">
        
        <div>
            <div class="panel-header" style="background: var(--panel-bg); padding: 10px 15px; border: 1px solid rgba(255,255,255,0.05); border-bottom: none; margin-bottom: 0;">
                <span>Registered Conway Glyphs (<?php echo count($glyphs); ?> total)</span>
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
                All hail to the <text style="color: var(--accent-green);">National Institute for Standards and Technology</text>!
            </p>

            <h3 style="font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; color: var(--frame-grey);">NIST Pulse Output:</h3>
            <div class="seed-display-box" id="raw-pulse-box">AWAITING QUANTUM HARVEST...</div>

            <h3 style="font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; color: var(--frame-grey);">Compressed Engine Seed (FNV-1a 32-bit):</h3>
            <div class="seed-display-box" id="coerced-seed-box" style="font-size: 1.2rem; text-align: center; color: var(--accent-green); font-weight: bold;">0000000000</div>

            <button class="action-button" id="prune-timeline-btn">Retrieve NIST Pulse</button>

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
        const currentTimestamp = Date.now();
        
        fetch(`https://beacon.nist.gov/beacon/2.0/pulse/time/previous/${currentTimestamp}`)
            .then(response => {
                if(!response.ok) throw new Error("Network latency in the Sacred Timeline.");
                return response.json();
            })
            .then(data => {
                // Grab the 512-bit (128 hex character) output value string
                const rawHex = data.pulse.outputValue;
                pulseBox.innerText = rawHex;

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
                
                statusBadge.innerText = "TIMELINE LOCKED";
                statusBadge.style.background = "rgba(66, 244, 133, 0.2)";
                statusBadge.style.color = "var(--accent-green)";
                
                btn.disabled = false;
                btn.innerText = "Harvest NIST Pulse";

                console.log(`[THE OVERSEER] Input timestamp ${currentTimestamp} -> NIST Full Hex for Pulse ID ${pulseIndex}: ${rawHex} -> Compression Stage: FNV-1a Hash -> INT(11) Signed Seed: ${engineSeed}`);
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

</script>
</body>
</html>