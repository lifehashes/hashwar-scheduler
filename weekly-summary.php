<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HASHWAR // NETWORK CONSOLE</title>
    <style>
        /* --- CORE STYLES SEEDED FROM STYLES.CSS --- */
        :root {
            --bg-color: #203030;
            --panel-bg: rgba(48, 64, 64, 0.8);
            --accent-green: #42f485;
            --frame-grey: #d1d1d1;
            --text-main: #f0f0f0;
            --dark-base: #111a1a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Calibri', 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 20px;
        }

        /* --- THE CORE COMPREHENSIVE WINDOW FRAME --- */
        .outer-frame {
            border: 1px solid var(--frame-grey);
            padding: 20px;
            width: 98vw;
            max-width: 1600px;
            background: rgba(0, 0, 0, 0.2);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.5);
        }

        header {
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(209, 209, 209, 0.3);
            padding-bottom: 15px;
        }

        .title {
            font-family: 'Courier New', monospace;
            font-size: 2.2rem;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #fff;
        }

        .subtitle {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--accent-green);
            margin-top: 5px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* --- CONSOLE CONTAINER SYSTEM --- */
        .console-container {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* Radio State Tokens */
        .tab-state {
            display: none;
        }

        /* --- STYLED ADVANCED LABELS (From image_8b3fe7.png) --- */
        .tab-headers {
            display: flex;
            gap: 10px;
            list-style: none;
            position: relative;
            z-index: 1;
            width: 100%;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .tab-card {
            flex: 1;
            min-width: 180px;
            background: var(--panel-bg);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 15px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 5px;
            transition: all 0.2s ease-in-out;
            border-top: 3px solid rgba(255, 255, 255, 0.2);
        }

        .tab-card:hover {
            background: rgba(66, 244, 133, 0.08);
            border-color: rgba(66, 244, 133, 0.4);
        }

        /* Card Text Top-Row Styling */
        .tab-card-title {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1rem;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Card Secondary Meta Subtitle */
        .tab-card-meta {
            font-family: 'Courier New', monospace;
            font-size: 0.65rem;
            color: var(--frame-grey);
            letter-spacing: 1px;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
            padding-bottom: 6px;
            margin-bottom: 4px;
        }

        /* Custom Graphic Enclosure Inside the Tab Label */
        .tab-card-graphic {
            width: 100%;
            height: 120px;
            background: var(--dark-base);
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 5px;
        }

        /* --- MAIN CENTRAL WORKSPACE --- */
        .content-panel {
            background-color: var(--panel-bg);
            border: 1px solid var(--frame-grey);
            flex-grow: 1;
            padding: 30px;
            position: relative;
            margin-top: -1px; /* Ties directly flush against the cards */
            min-height: 400px;
        }

        .tab-content {
            display: none;
            animation: terminalFade 0.25s ease-out forwards;
        }

        .tab-content h2 {
            font-family: 'Courier New', monospace;
            color: var(--accent-green);
            font-size: 1.3rem;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        /* --- SWITCHING CONTROL SYSTEM ARCHITECTURE --- */
        #tab-1:checked ~ .tab-headers label[for="tab-1"],
        #tab-2:checked ~ .tab-headers label[for="tab-2"],
        #tab-3:checked ~ .tab-headers label[for="tab-3"],
        #tab-4:checked ~ .tab-headers label[for="tab-4"],
        #tab-5:checked ~ .tab-headers label[for="tab-5"],
        #tab-6:checked ~ .tab-headers label[for="tab-6"],
        #tab-7:checked ~ .tab-headers label[for="tab-7"] {
            border-color: var(--accent-green);
            border-top: 3px solid var(--accent-green);
            background: rgba(48, 64, 64, 1);
            box-shadow: 0 -4px 15px rgba(66, 244, 133, 0.15);
        }

        #tab-1:checked ~ .tab-headers label[for="tab-1"] .tab-card-title,
        #tab-2:checked ~ .tab-headers label[for="tab-2"] .tab-card-title,
        #tab-3:checked ~ .tab-headers label[for="tab-3"] .tab-card-title,
        #tab-4:checked ~ .tab-headers label[for="tab-4"] .tab-card-title,
        #tab-5:checked ~ .tab-headers label[for="tab-5"] .tab-card-title,
        #tab-6:checked ~ .tab-headers label[for="tab-6"] .tab-card-title,
        #tab-7:checked ~ .tab-headers label[for="tab-7"] .tab-card-title {
            color: var(--accent-green);
        }

        #tab-1:checked ~ .content-panel #content-1,
        #tab-2:checked ~ .content-panel #content-2,
        #tab-3:checked ~ .content-panel #content-3,
        #tab-4:checked ~ .content-panel #content-4,
        #tab-5:checked ~ .content-panel #content-5,
        #tab-6:checked ~ .content-panel #content-6,
        #tab-7:checked ~ .content-panel #content-7 {
            display: block;
        }

        /* --- PLACEHOLDER WIREFRAMES --- */
        .scaffolding-blueprint {
            border: 1px dashed rgba(66, 244, 133, 0.2);
            padding: 20px;
            text-align: center;
            font-family: 'Courier New', monospace;
            color: var(--frame-grey);
            font-size: 0.85rem;
            background: rgba(0,0,0,0.15);
        }

        /* --- SYSTEM FOOTER --- */
        footer {
            margin-top: 25px;
            border-top: 1px dashed rgba(209, 209, 209, 0.2);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: var(--frame-grey);
            letter-spacing: 1px;
        }

        @keyframes terminalFade {
            from { opacity: 0; }
            to { opacity: 1; }
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

    <!-- PRIMARY ENCOMPASSING OUTER FRAME -->
    <div class="outer-frame">
        
        <!-- HEADER READOUTS -->
        <header>
            <div class="title">Hashwar // Console Core</div>
            <div id="gamestatus">Active Tactical Pipeline Overload // Stream Online</div>
        </header>

        <!-- CONTROL PANEL WORKSPACE -->
        <div class="console-container">
            
            <!-- Hidden State Flags -->
            <input type="radio" name="console-tabs" id="tab-1" class="tab-state" checked>
            <input type="radio" name="console-tabs" id="tab-2" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-3" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-4" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-5" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-6" class="tab-state">
            <input type="radio" name="console-tabs" id="tab-7" class="tab-state">

            <!-- GRAPHIC RICH FILING CONTAINER LABELS -->
            <div class="tab-headers">
                
                <!-- CARD TAB 1 -->
                <label for="tab-1" class="tab-card">
                    <span class="tab-card-title">GROUP DRAWS</span>
                    <span class="tab-card-meta">Sunday</span>
                    <div class="tab-card-graphic">
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
                </label>

                <!-- CARD TAB 2 -->
                <label for="tab-2" class="tab-card">
                    <span class="tab-card-title">GROUP A</span>
                    <span class="tab-card-meta">Monday</span>
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
                    <span class="tab-card-title">GROUP B</span>
                    <span class="tab-card-meta">Tuesday</span>
                    <div class="tab-card-graphic">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <polygon points="55,10 87,23 100,55 87,87 55,100 23,87 10,55 23,23" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                            <path d="M 55 10 L 100 55 M 55 10 L 55 100 M 55 10 L 10 55 M 87 23 L 87 87 M 87 23 L 23 87 M 100 55 L 10 55 M 23 23 L 87 87" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                            <circle cx="55" cy="10" r="4.5" fill="#42f485"/> <circle cx="87" cy="23" r="4.5" fill="#42f485"/> <circle cx="100" cy="55" r="4.5" fill="#42f485"/> <circle cx="87" cy="87" r="4.5" fill="#f4d042"/> <circle cx="55" cy="100" r="4.5" fill="#f4d042"/> <circle cx="23" cy="87" r="4.5" fill="#f4d042"/> <circle cx="10" cy="55" r="4.5" fill="#f4d042"/> <circle cx="23" cy="23" r="4.5" fill="#ef4444"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 4 -->
                <label for="tab-4" class="tab-card">
                    <span class="tab-card-title">GROUP C</span>
                    <span class="tab-card-meta">Wednesday</span>
                    <div class="tab-card-graphic">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <polygon points="55,10 87,23 100,55 87,87 55,100 23,87 10,55 23,23" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                            <path d="M 55 10 L 100 55 M 55 10 L 55 100 M 55 10 L 10 55 M 87 23 L 87 87 M 87 23 L 23 87 M 100 55 L 10 55 M 23 23 L 87 87" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                            <circle cx="55" cy="10" r="4.5" fill="#42f485"/> <circle cx="87" cy="23" r="4.5" fill="#42f485"/> <circle cx="100" cy="55" r="4.5" fill="#42f485"/> <circle cx="87" cy="87" r="4.5" fill="#f4d042"/> <circle cx="55" cy="100" r="4.5" fill="#f4d042"/> <circle cx="23" cy="87" r="4.5" fill="#f4d042"/> <circle cx="10" cy="55" r="4.5" fill="#f4d042"/> <circle cx="23" cy="23" r="4.5" fill="#ef4444"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 5 -->
                <label for="tab-5" class="tab-card">
                    <span class="tab-card-title">GROUP D</span>
                    <span class="tab-card-meta">Thursday</span>
                    <div class="tab-card-graphic">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <polygon points="55,10 87,23 100,55 87,87 55,100 23,87 10,55 23,23" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
                            <path d="M 55 10 L 100 55 M 55 10 L 55 100 M 55 10 L 10 55 M 87 23 L 87 87 M 87 23 L 23 87 M 100 55 L 10 55 M 23 23 L 87 87" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                            <circle cx="55" cy="10" r="4.5" fill="#42f485"/> <circle cx="87" cy="23" r="4.5" fill="#42f485"/> <circle cx="100" cy="55" r="4.5" fill="#42f485"/> <circle cx="87" cy="87" r="4.5" fill="#f4d042"/> <circle cx="55" cy="100" r="4.5" fill="#f4d042"/> <circle cx="23" cy="87" r="4.5" fill="#f4d042"/> <circle cx="10" cy="55" r="4.5" fill="#f4d042"/> <circle cx="23" cy="23" r="4.5" fill="#ef4444"/>
                        </svg>
                    </div>
                </label>

                <!-- CARD TAB 6 -->
                <label for="tab-6" class="tab-card">
                    <span class="tab-card-title">REDEMPTION</span>
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

                <!-- CARD TAB 7 -->
                <label for="tab-7" class="tab-card">
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

                <!-- Tab Section 2 -->
                <div id="content-2" class="tab-content">
                    <h2>[01a // GROUP A MATCHES]</h2>
                    <div class="scaffolding-blueprint">
                        [NETWORK MATRIX READY]: A Round-Robin Tournament where every Glyph in Group A plays every other Glyphs twice (56 matches total)
                    </div>
                    <div class="groups-container">
                        <?php foreach (['A'] as $groupLetter): ?>
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
                    <h2>[01b // GROUP B MATCHES]</h2>
                    <div class="scaffolding-blueprint">
                        [VALENCE VECTOR READY]: A Round-Robin Tournament where every Glyph in Group B plays every other Glyphs twice (56 matches total)
                    </div>
                    <div class="groups-container">
                        <?php foreach (['B'] as $groupLetter): ?>
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

                <!-- Tab Section 4 -->
                <div id="content-4" class="tab-content">
                    <h2>[01c // GROUP C MATCHES]</h2>
                    <div class="scaffolding-blueprint">
                        [BRACKET ARRAY READY]: A Round-Robin Tournament where every Glyph in Group C plays every other Glyphs twice (56 matches total)
                    </div>
                    <div class="groups-container">
                        <?php foreach (['C'] as $groupLetter): ?>
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

                <!-- Tab Section 5 -->
                <div id="content-5" class="tab-content">
                    <h2>[01d // GROUP D MATCHES]</h2>
                    <div class="scaffolding-blueprint">
                        [BRACKET ARRAY READY]: A Round-Robin Tournament where every Glyph in Group D plays every other Glyphs twice (56 matches total)
                    </div>
                    <div class="groups-container">
                        <?php foreach (['D'] as $groupLetter): ?>
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

                <!-- Tab Section 6 -->
                <div id="content-6" class="tab-content">
                    <h2>[02 // REDEMPTION DAY // FINAL DRAW]</h2>
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

                <!-- Tab Section 7 -->
                <div id="content-7" class="tab-content">
                    <h2>[03 // WEEKLY GRAND CHAMPION FINALS]</h2>
                    <div class="scaffolding-blueprint">
                        [BRACKET ARRAY READY]: The final weekly Knock-Out tournament where the 16 qualifying Glyphs compete
                    </div>
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

            </main>
        </div>

        <!-- LOWER TERMINAL STATUS READOUT FOOTER -->
        <footer>
            <div>LOCATION: [48.7186° N, 8.5097° E]</div>
            <div>SECURITY STATE: OVERWATCH LAYER SECURED</div>
            <div>© 2026 HASHWAR PROTOCOLS // DATA CONSOLE v4.82</div>
        </footer>

    </div>

</body>
</html>