<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lifehashes - Year View</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* --- TERMINAL CUSTOM SCROLLBARS --- */
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
            background: var(--dark-base);
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(66, 244, 133, 0.2);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(66, 244, 133, 0.4);
            border: 1px solid var(--accent-green);
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-green);
        }

        /* --- CONTROLS --- */
        .calendar-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
        }

        .year-nav-btn {
            background: var(--dark-base);
            color: var(--accent-green);
            border: 1px solid var(--accent-green);
            padding: 6px 16px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            letter-spacing: 1px;
            transition: all 0.2s ease;
        }

        .year-nav-btn:hover {
            background: var(--accent-green);
            color: var(--dark-base);
        }

        .year-display {
            font-size: 1.2rem;
            color: #fff;
            letter-spacing: 2px;
            font-weight: bold;
        }

        /* --- YEAR GRID ARCHITECTURE --- */
        .year-container {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 15px;
        }

        .year-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 1400px;
        }

        .quarter-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            position: relative;
        }

        .month-block {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-direction: column;
        }

        .month-header {
            font-family: 'Courier New', monospace;
            text-align: center;
            font-size: 0.85rem;
            letter-spacing: 2px;
            color: var(--text-main);
            padding: 6px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-transform: uppercase;
        }

        .month-grid {
            display: grid;
            grid-template-rows: auto auto auto;
            width: 100%;
        }

        .days-row, .names-row {
            display: grid;
            width: 100%;
        }

        .cell {
            font-family: 'Courier New', monospace;
            text-align: center;
            border-right: 1px solid rgba(255, 255, 255, 0.04);
            box-sizing: border-box;
        }

        .cell:last-child {
            border-right: none;
        }

        .day-num {
            font-size: 0.65rem;
            color: #aaa;
            padding: 3px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .day-name {
            font-size: 0.55rem;
            color: #666;
            padding: 2px 0;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .weeks-row {
            position: relative;
            height: 28px;
            background: rgba(0, 0, 0, 0.15);
            width: 100%;
        }

        /* --- WEEKLY SLOTS & BOUNDARY CONTINUITY --- */
        .week-slot {
            position: absolute;
            top: 2px;
            height: 24px;
            background: var(--panel-bg);
            /* Default muted placeholder styling */
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #555555;
            font-family: 'Courier New', monospace;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-sizing: border-box;
            border-radius: 2px;
            z-index: 2;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.2s ease;
        }

        .week-slot:hover {
            filter: brightness(1.2);
            background: rgba(255, 255, 255, 0.05);
            color: #888888;
        }

        /* Active Database Slot Override Class */
        .week-slot.has-entry:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        /* Quarter Boundary Visual Connection Styles */
        .week-slot.open-right {
            border-right: none !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }

        .week-slot.open-left {
            border-left: none !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }
    </style>
</head>
<body>

    <div class="outer-frame">
        <header>
            <div class="title">LIFEHASHES</div>
            <div class="subtitle">Chronological Matrix // Year Grid Engine</div>
        </header>

        <div class="console-container">
            <div class="content-panel">
                <div class="calendar-controls">
                    <button class="year-nav-btn" onclick="changeYear(-1)">&lt; PREV YEAR</button>
                    <div class="year-display" id="yearDisplay">2026</div>
                    <button class="year-nav-btn" onclick="changeYear(1)">NEXT YEAR &gt;</button>
                </div>

                <div class="year-container">
                    <div class="year-grid" id="yearGrid">
                        <!-- Rendered dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <span>STATUS: ACTIVE</span>
            <span>SYSTEM: READY</span>
        </footer>
    </div>

<script>
        let currentYear = 2026;
        let weeklyEntries = [];

        const dayNames = ['SU', 'MO', 'TU', 'WD', 'TH', 'FR', 'SA'];
        const monthNames = [
            'January', 'February', 'March', 'April', 
            'May', 'June', 'July', 'August', 
            'September', 'October', 'November', 'December'
        ];

        async function fetchWeeklyEntries() {
            try {
                const res = await fetch('php/api_calendar.php');
                if (res.ok) {
                    weeklyEntries = await res.json();
                }
            } catch (err) {
                console.error('Failed to load database entries:', err);
            }
        }

        function changeYear(delta) {
            currentYear += delta;
            renderCalendar(currentYear);
        }

        // Helper to format JavaScript Date objects to YYYY-MM-DD
        function formatDateToISO(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        // Search database entries for matching start & end dates
        function findMatchingEntry(startDateStr, endDateStr) {
            return weeklyEntries.find(entry => {
                return entry.startDate === startDateStr && entry.endDate === endDateStr;
            });
        }

        function renderCalendar(year) {
            document.getElementById('yearDisplay').innerText = year;
            const gridContainer = document.getElementById('yearGrid');
            gridContainer.innerHTML = '';

            for (let q = 0; q < 4; q++) {
                const quarterRow = document.createElement('div');
                quarterRow.className = 'quarter-row';
                quarterRow.id = `quarter-${q}`;

                for (let m = 0; m < 3; m++) {
                    const monthIdx = q * 3 + m;
                    const daysInMonth = new Date(year, monthIdx + 1, 0).getDate();

                    const monthBlock = document.createElement('div');
                    monthBlock.className = 'month-block';
                    monthBlock.id = `month-block-${monthIdx}`;

                    const monthHeader = document.createElement('div');
                    monthHeader.className = 'month-header';
                    monthHeader.innerText = monthNames[monthIdx];
                    monthBlock.appendChild(monthHeader);

                    const monthGrid = document.createElement('div');
                    monthGrid.className = 'month-grid';

                    const daysRow = document.createElement('div');
                    daysRow.className = 'days-row';
                    daysRow.style.gridTemplateColumns = `repeat(${daysInMonth}, 1fr)`;

                    const namesRow = document.createElement('div');
                    namesRow.className = 'names-row';
                    namesRow.style.gridTemplateColumns = `repeat(${daysInMonth}, 1fr)`;

                    for (let d = 1; d <= daysInMonth; d++) {
                        const dateObj = new Date(year, monthIdx, d);
                        const dayOfWeek = dateObj.getDay();

                        const dayNumCell = document.createElement('div');
                        dayNumCell.className = 'cell day-num';
                        dayNumCell.innerText = d;
                        dayNumCell.id = `cell-num-${monthIdx}-${d}`;
                        daysRow.appendChild(dayNumCell);

                        const dayNameCell = document.createElement('div');
                        dayNameCell.className = 'cell day-name';
                        dayNameCell.innerText = dayNames[dayOfWeek];
                        namesRow.appendChild(dayNameCell);
                    }

                    const weeksRow = document.createElement('div');
                    weeksRow.className = 'weeks-row';
                    weeksRow.id = `weeks-row-${monthIdx}`;

                    monthGrid.appendChild(daysRow);
                    monthGrid.appendChild(namesRow);
                    monthGrid.appendChild(weeksRow);
                    monthBlock.appendChild(monthGrid);

                    quarterRow.appendChild(monthBlock);
                }

                gridContainer.appendChild(quarterRow);
            }

            renderContinuousWeeks(year);
        }

        function renderContinuousWeeks(year) {
            let currentDate = new Date(year, 0, 1);
            const yearEnd = new Date(year, 11, 31);
            
            // Advance to the first Sunday of the year
            while (currentDate.getDay() !== 0 && currentDate <= yearEnd) {
                currentDate.setDate(currentDate.getDate() + 1);
            }

            let weekNumber = 1;

            while (currentDate <= yearEnd) {
                const weekStart = new Date(currentDate);
                const weekEnd = new Date(currentDate);
                weekEnd.setDate(weekEnd.getDate() + 6);

                const startMonth = weekStart.getMonth();
                const endMonth = weekEnd.getMonth();
                const startQuarter = Math.floor(startMonth / 3);
                const endQuarter = Math.floor(endMonth / 3);

                const startDateStr = formatDateToISO(weekStart);
                const endDateStr = formatDateToISO(weekEnd);

                // Fetch entry from DB matched against week's exact ISO dates
                const dbEntry = findMatchingEntry(startDateStr, endDateStr);
                const formattedWeek = String(weekNumber).padStart(2, '0');
                
                // Fallback to week number if no database entry exists
                let labelText = dbEntry ? `${dbEntry.designation} (${dbEntry.weightClass})` : `WK ${formattedWeek}`;
                let customColor = dbEntry ? dbEntry.color : null;

                // Helper to apply dynamic styles
                const applySlotStyles = (slotElement) => {
                    slotElement.innerText = labelText;
                    if (dbEntry) {
                        slotElement.classList.add('has-entry');
                        // Use DB color if present, or fallback to terminal green for active entries
                        const activeColor = customColor || 'var(--accent-green)';
                        slotElement.style.borderColor = activeColor;
                        slotElement.style.color = activeColor;
                        slotElement.style.boxShadow = `0 0 6px ${activeColor}44`;
                    }
                };

                // CASE 1: Intra-quarter continuous spanning (Same quarter row)
                if (startQuarter === endQuarter) {
                    const startCell = document.getElementById(`cell-num-${startMonth}-${weekStart.getDate()}`);
                    const endCell = document.getElementById(`cell-num-${endMonth}-${weekEnd.getDate()}`);

                    if (startCell && endCell) {
                        const quarterRow = document.getElementById(`quarter-${startQuarter}`);
                        
                        const qRect = quarterRow.getBoundingClientRect();
                        const sRect = startCell.getBoundingClientRect();
                        const eRect = endCell.getBoundingClientRect();

                        const leftOffset = sRect.left - qRect.left;
                        const width = eRect.right - sRect.left;

                        const weekSlot = document.createElement('div');
                        weekSlot.className = 'week-slot';
                        weekSlot.style.left = `${leftOffset}px`;
                        weekSlot.style.width = `${width}px`;
                        
                        applySlotStyles(weekSlot);

                        const targetTrack = document.getElementById(`weeks-row-${startMonth}`);
                        const trackRect = targetTrack.getBoundingClientRect();
                        weekSlot.style.top = `${trackRect.top - qRect.top + 2}px`;

                        quarterRow.appendChild(weekSlot);
                    }
                } 
                // CASE 2: Crosses Quarter boundary (e.g. March to April)
                else {
                    // Segment 1 (Ends quarter)
                    const month1Track = document.getElementById(`weeks-row-${startMonth}`);
                    const startCell = document.getElementById(`cell-num-${startMonth}-${weekStart.getDate()}`);
                    const month1EndDay = new Date(year, startMonth + 1, 0).getDate();
                    const lastCell1 = document.getElementById(`cell-num-${startMonth}-${month1EndDay}`);

                    if (startCell && lastCell1) {
                        const tRect = month1Track.getBoundingClientRect();
                        const sRect = startCell.getBoundingClientRect();
                        const eRect = lastCell1.getBoundingClientRect();

                        const slot1 = document.createElement('div');
                        slot1.className = 'week-slot open-right';
                        slot1.style.left = `${sRect.left - tRect.left}px`;
                        slot1.style.width = `${eRect.right - sRect.left}px`;
                        
                        applySlotStyles(slot1);

                        month1Track.appendChild(slot1);
                    }

                    // Segment 2 (Starts next quarter)
                    const month2Track = document.getElementById(`weeks-row-${endMonth}`);
                    const firstCell2 = document.getElementById(`cell-num-${endMonth}-1`);
                    const endCell = document.getElementById(`cell-num-${endMonth}-${weekEnd.getDate()}`);

                    if (firstCell2 && endCell) {
                        const tRect = month2Track.getBoundingClientRect();
                        const sRect = firstCell2.getBoundingClientRect();
                        const eRect = endCell.getBoundingClientRect();

                        const slot2 = document.createElement('div');
                        slot2.className = 'week-slot open-left';
                        slot2.style.left = `${sRect.left - tRect.left}px`;
                        slot2.style.width = `${eRect.right - sRect.left}px`;
                        
                        applySlotStyles(slot2);

                        month2Track.appendChild(slot2);
                    }
                }

                weekNumber++;
                currentDate.setDate(currentDate.getDate() + 7);
            }
        }

        // Unified Initialization Flow
        window.addEventListener('DOMContentLoaded', async () => {
            await fetchWeeklyEntries();
            renderCalendar(currentYear);
        });

        window.addEventListener('resize', () => renderCalendar(currentYear));
    </script>
</body>
</html>