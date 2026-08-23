<?php
    // 1. Configure Secure Session Settings before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }

    session_start();

    // 2. Database Connection
    include_once __DIR__ . '/../../priv/db_conf_laniakea.php';

    // 3. AUTHENTICATION GUARD: Verify user session exists
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }

    // 4. AUTHORIZATION GUARD: Allowed operators or DB role check
    $allowed_operators = ['hash0'];

    // Check username whitelist OR database role
    $is_authorized = in_array($_SESSION['username'] ?? '', $allowed_operators, true);

    // Optional DB query check if role is stored in DB:
    // $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    // $stmt->execute([$_SESSION['user_id']]);
    // $user = $stmt->fetch();
    // if ($user && $user['role'] === 'admin') { $is_authorized = true; }

    if (!$is_authorized) {
        http_response_code(403);
        exit('ACCESS_DENIED // UNAUTHORIZED_OPERATOR');
    }

    // 5. CSRF TOKEN GENERATION: Embed in HTML for JavaScript API calls
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Safely escape operator details for UI rendering
    $current_operator = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
    $operator_id      = htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8');
    $csrf_token       = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Lifehashes - Entry Manager</title>
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

        /* --- MANAGEMENT CONSOLE LAYOUT --- */
        .manager-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 20px;
            align-items: start;
        }

        /* --- FORM STYLES --- */
        .editor-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .editor-title {
            font-family: 'Courier New', monospace;
            color: var(--accent-green);
            font-size: 0.85rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(66, 244, 133, 0.3);
            padding-bottom: 6px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .form-group label {
            font-family: 'Courier New', monospace;
            font-size: 0.65rem;
            color: var(--frame-grey);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .form-control {
            background: var(--dark-base);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--accent-green);
        }

        .color-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-picker-wrapper input[type="color"] {
            -webkit-appearance: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 38px;
            height: 32px;
            background: none;
            cursor: pointer;
        }

        .btn-group {
            display: flex;
            gap: 8px;
            margin-top: 5px;
        }

        .btn-terminal {
            flex: 1;
            background: var(--dark-base);
            color: var(--accent-green);
            border: 1px solid var(--accent-green);
            padding: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.7rem;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }

        .btn-terminal:hover {
            background: var(--accent-green);
            color: var(--dark-base);
        }

        .btn-terminal.danger {
            color: #f44242;
            border-color: #f44242;
        }

        .btn-terminal.danger:hover {
            background: #f44242;
            color: #fff;
        }

        /* --- ENTRIES TABLE --- */
        .entries-container {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow-x: auto;
        }

        .entries-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
        }

        .entries-table th {
            text-align: left;
            color: var(--frame-grey);
            padding: 10px;
            font-weight: normal;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-transform: uppercase;
        }

        .entries-table td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-main);
        }

        .entries-table tr:hover {
            background: rgba(66, 244, 133, 0.04);
            cursor: pointer;
        }

        .color-badge {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 2px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            vertical-align: middle;
        }

        .action-link {
            color: var(--accent-green);
            cursor: pointer;
            text-decoration: underline;
            margin-right: 8px;
        }

        .action-link.delete {
            color: #f44242;
        }

        /* Responsive Breakpoint */
        @media (max-width: 900px) {
            .manager-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="outer-frame">
        <header>
            <div class="title">LIFEHASHES</div>
            <div class="subtitle">Chronological Matrix // Entry Management Console</div>
        </header>

        <div class="console-container">
            <div class="content-panel">
                <div class="manager-layout">
                    
                    <!-- LEFT: ENTRY EDITOR FORM -->
                    <div class="editor-card">
                        <div class="editor-title" id="formTitle">ADD NEW ENTRY</div>
                        <input type="hidden" id="entryId">

                        <div class="form-group">
                            <label for="designation">Designation</label>
                            <input type="text" id="designation" class="form-control" placeholder="e.g. 33, PLAY-OFFS">
                        </div>

                        <div class="form-group">
                            <label for="weightClass">Weight-Class</label>
                            <input type="text" id="weightClass" class="form-control" placeholder="e.g. 500, 1000+">
                        </div>

                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="glowColor">Glow Color</label>
                            <div class="color-picker-wrapper">
                                <input type="color" id="glowColor" value="#42f485">
                                <input type="text" id="glowColorHex" class="form-control" value="#42f485" style="flex: 1;">
                            </div>
                        </div>

                        <div class="btn-group">
                            <button class="btn-terminal" id="saveBtn" onclick="saveEntry()">SAVE ENTRY</button>
                            <button class="btn-terminal danger" id="cancelBtn" onclick="resetForm()" style="display: none;">CANCEL</button>
                        </div>
                    </div>

                    <!-- RIGHT: EXISTING ENTRIES TABLE -->
                    <div class="entries-container">
                        <table class="entries-table">
                            <thead>
                                <tr>
                                    <th>Glow</th>
                                    <th>Rendered Text</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="entriesTableBody">
                                <!-- Dynamic Entries -->
                            </tbody>
                        </table>
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let entries = [];

        const colorPicker = document.getElementById('glowColor');
        const colorHexInput = document.getElementById('glowColorHex');

        colorPicker.addEventListener('input', (e) => colorHexInput.value = e.target.value);
        colorHexInput.addEventListener('input', (e) => colorPicker.value = e.target.value);

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        async function loadEntries() {
            try {
                const response = await fetch('php/api_calendar.php');
                if (!response.ok) throw new Error('Failed to load entries');
                entries = await response.json();
                renderEntriesTable();
            } catch (err) {
                alert('Error fetching records: ' + err.message);
            }
        }

        function renderEntriesTable() {
            const tbody = document.getElementById('entriesTableBody');
            tbody.innerHTML = '';

            entries.forEach(entry => {
                const tr = document.createElement('tr');
                const safeDesignation = escapeHtml(entry.designation);
                const safeWeight = escapeHtml(entry.weightClass);
                const safeColor = escapeHtml(entry.color);

                tr.innerHTML = `
                    <td>
                        <span class="color-badge" style="background-color: ${safeColor}; box-shadow: 0 0 8px ${safeColor};"></span>
                    </td>
                    <td><strong>${safeDesignation} (${safeWeight})</strong></td>
                    <td>${escapeHtml(entry.startDate)}</td>
                    <td>${escapeHtml(entry.endDate)}</td>
                    <td>
                        <span class="action-link" onclick="editEntry('${entry.id}')">EDIT</span>
                        <span class="action-link delete" onclick="deleteEntry('${entry.id}')">DELETE</span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function saveEntry() {
            const id = document.getElementById('entryId').value;
            const designation = document.getElementById('designation').value.trim();
            const weightClass = document.getElementById('weightClass').value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const color = document.getElementById('glowColorHex').value;

            if (!designation || !weightClass || !startDate || !endDate) {
                alert('Please fill out all fields.');
                return;
            }

            const payload = { id, designation, weightClass, startDate, endDate, color };

            try {
                const response = await fetch('php/api_calendar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'Save failed');

                resetForm();
                await loadEntries();
            } catch (err) {
                alert('Error saving entry: ' + err.message);
            }
        }

        function editEntry(id) {
            const entry = entries.find(e => String(e.id) === String(id));
            if (!entry) return;

            document.getElementById('entryId').value = entry.id;
            document.getElementById('designation').value = entry.designation;
            document.getElementById('weightClass').value = entry.weightClass;
            document.getElementById('startDate').value = entry.startDate;
            document.getElementById('endDate').value = entry.endDate;
            document.getElementById('glowColor').value = entry.color;
            document.getElementById('glowColorHex').value = entry.color;

            document.getElementById('formTitle').innerText = 'EDIT ENTRY';
            document.getElementById('saveBtn').innerText = 'UPDATE ENTRY';
            document.getElementById('cancelBtn').style.display = 'block';
        }

        async function deleteEntry(id) {
            if (!confirm('Are you sure you want to delete this entry?')) return;

            try {
                const response = await fetch(`php/api_calendar.php?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-Token': csrfToken }
                });

                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'Delete failed');

                await loadEntries();
            } catch (err) {
                alert('Error deleting entry: ' + err.message);
            }
        }

        function resetForm() {
            document.getElementById('entryId').value = '';
            document.getElementById('designation').value = '';
            document.getElementById('weightClass').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('glowColor').value = '#42f485';
            document.getElementById('glowColorHex').value = '#42f485';

            document.getElementById('formTitle').innerText = 'ADD NEW ENTRY';
            document.getElementById('saveBtn').innerText = 'SAVE ENTRY';
            document.getElementById('cancelBtn').style.display = 'none';
        }

        window.onload = () => loadEntries();
    </script>
</body>
</html>