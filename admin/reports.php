<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="hackathon_report_' . date('Y-m-d_H-i') . '.csv"');
    $output = fopen('php://output', 'w');

    // Header row
    $headers = [
        'Team ID', 'Team Name', 'Leader Name', 'Leader Roll No', 'Leader Email', 'Leader Phone', 
        'Leader Residence', 'Leader Address', 'Registration Date', 
        'PS ID', 'PS Title', 'Submission Type', 'Submission Link', 'Submission Date'
    ];
    // Add member headers
    for ($i = 1; $i <= 4; $i++) {
        $headers[] = "Member $i Name";
        $headers[] = "Member $i Roll";
        $headers[] = "Member $i Email";
        $headers[] = "Member $i Phone";
        $headers[] = "Member $i Residence";
        $headers[] = "Member $i Address";
    }
    fputcsv($output, $headers);

    // Fetch data for export
    $teams_export = [];
    $sql = "SELECT t.*, 
               ps.sno as ps_sno, ps.stmt_name as ps_title, 
               s.submission_type, s.submission_link, s.submitted_at
        FROM teams t
        LEFT JOIN team_ps_selection tps ON t.id = tps.team_id
        LEFT JOIN problem_statements ps ON tps.ps_id = ps.id
        LEFT JOIN submissions s ON t.id = s.team_id
        ORDER BY t.id ASC";
    
    if ($result = $mysqli->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $row['members'] = [];
            $teams_export[$row['id']] = $row;
        }
    }

    $members_sql = "SELECT * FROM team_members ORDER BY team_id, id";
    if ($result = $mysqli->query($members_sql)) {
        while ($row = $result->fetch_assoc()) {
            if (isset($teams_export[$row['team_id']])) {
                $teams_export[$row['team_id']]['members'][] = $row;
            }
        }
    }

    foreach ($teams_export as $team) {
        $line = [
            $team['id'], $team['team_name'], $team['leader_name'], $team['roll_number'], $team['email'], $team['phone_number'], $team['residence'], $team['address'], $team['created_at'],
            $team['ps_sno'] ? 'PS-'.$team['ps_sno'] : 'N/A', $team['ps_title'] ?? 'N/A',
            $team['submission_type'] ?? 'N/A', $team['submission_link'] ?? 'N/A', $team['submitted_at'] ?? 'N/A'
        ];

        $members = $team['members'];
        for ($i = 0; $i < 4; $i++) {
            if (isset($members[$i])) {
                $m = $members[$i];
                array_push($line, $m['member_name'], $m['roll_number'], $m['email'], $m['phone_number'], $m['residence'], $m['address']);
            } else {
                array_push($line, '', '', '', '', '', '');
            }
        }
        fputcsv($output, $line);
    }
    fclose($output);
    exit;
}

// Fetch all teams with related data
$teams = [];
$sql = "SELECT t.*, 
               ps.sno as ps_sno, ps.stmt_name as ps_title, ps.description as ps_desc, tps.selected_at as ps_selected_at,
               s.submission_type, s.submission_link, s.submitted_at
        FROM teams t
        LEFT JOIN team_ps_selection tps ON t.id = tps.team_id
        LEFT JOIN problem_statements ps ON tps.ps_id = ps.id
        LEFT JOIN submissions s ON t.id = s.team_id
        ORDER BY t.id ASC";

if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $row['members'] = []; // Initialize members array
        $teams[$row['id']] = $row;
    }
}

// Fetch all members and assign to teams
$members_sql = "SELECT * FROM team_members ORDER BY team_id, id";
if ($result = $mysqli->query($members_sql)) {
    while ($row = $result->fetch_assoc()) {
        if (isset($teams[$row['team_id']])) {
            $teams[$row['team_id']]['members'][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admin Reports</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#443c68",
                    "secondary": "#635985",
                    "background-dark": "#18122B",
                    "surface-dark": "#393053",
                    "border-dark": "#5a4d74",
                    "text-muted": "#94A3B8",
                },
                fontFamily: {
                    "display": ["Space Grotesk", "sans-serif"],
                    "body": ["Noto Sans", "sans-serif"],
                },
            },
        },
    }
    </script>
    <style>
    .material-symbols-outlined { font-variation-settings: 'FILL'0, 'wght'400, 'GRAD'0, 'opsz'24; }
    body { background-color: #18122B; color: white; }
    @media print {
        #sidebar, #mobile-menu-btn, .no-print { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; height: auto !important; overflow: visible !important; }
        body { background-color: white !important; color: black !important; height: auto !important; overflow: visible !important; }
        .bg-surface-dark { background-color: white !important; border: 1px solid #ccc !important; color: black !important; box-shadow: none !important; }
        .bg-background-dark { background-color: white !important; }
        .text-white { color: black !important; }
        .text-text-muted { color: #555 !important; }
        header { position: static !important; background: none !important; border: none !important; }
        /* Modal Print Styles */
        body.modal-open > *:not(#reportModal) { display: none !important; }
        #reportModal { position: absolute !important; top: 0 !important; left: 0 !important; height: auto !important; width: 100% !important; background: white !important; display: block !important; }
        #reportModal .bg-surface-dark { background: white !important; border: none !important; box-shadow: none !important; max-width: 100% !important; max-height: none !important; }
        #reportModal .text-white { color: black !important; }
        #reportModal .text-text-muted { color: #333 !important; }
    }
    </style>
</head>
<body class="bg-background-dark font-display text-white h-screen flex overflow-hidden">
    <aside id="sidebar" class="w-64 bg-background-dark flex flex-col border-r border-border-dark/30 hidden md:flex shrink-0 fixed md:relative z-50 h-full">
        <div class="p-6 flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">A</div>
            <div>
                <h1 class="text-white text-base font-bold leading-tight">Admin</h1>
                <p class="text-text-muted text-xs font-normal">Management Console</p>
            </div>
        </div>
        <nav class="flex-1 px-4 flex flex-col gap-2 overflow-y-auto py-4">
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="admin_dashboard.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">dashboard</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="manage_ps.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">upload_file</span>
                <span class="text-sm font-medium">PS Upload</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="submissions.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">assignment_turned_in</span>
                <span class="text-sm font-medium">Submissions</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="user_management.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">people</span>
                <span class="text-sm font-medium">User Management</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/20 shadow-[0_0_15px_rgba(68,60,104,0.3)]" href="reports.php">
                <span class="material-symbols-outlined fill-current text-primary">assessment</span>
                <span class="text-sm font-medium">Reports</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="?logout=1">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">logout</span>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </nav>
    </aside>
    <main class="flex-1 flex flex-col h-full overflow-hidden relative w-full min-w-0">
        <header class="h-16 border-b border-border-dark/30 bg-background-dark/50 backdrop-blur-md flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-white text-xl font-bold tracking-tight">System Reports</h2>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="?export=csv" class="p-2 rounded-lg hover:bg-white/10 text-white transition-colors" title="Download Excel/CSV">
                    <span class="material-symbols-outlined">download</span>
                </a>
                <button onclick="window.print()" class="p-2 rounded-lg hover:bg-white/10 text-white transition-colors" title="Print Reports">
                    <span class="material-symbols-outlined">print</span>
                </button>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-surface-dark scrollbar-track-transparent">
            <div class="max-w-[1400px] mx-auto flex flex-col gap-6">
                
                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end justify-between bg-surface-dark/30 p-4 rounded-xl border border-border-dark/30">
                    <div class="w-full lg:w-96">
                        <label class="block text-xs font-medium text-text-muted mb-1.5 uppercase tracking-wider">Search Reports</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-text-muted group-focus-within:text-white transition-colors">search</span>
                            </div>
                            <input class="block w-full pl-10 pr-3 py-2.5 bg-surface-dark border border-border-dark/30 rounded-lg text-white placeholder-text-muted/50 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="Search by Team, Leader, ID..." type="text" id="searchInput" onkeyup="filterTable()" />
                        </div>
                    </div>
                </div>

                <!-- Teams Table -->
                <div class="bg-surface-dark border border-border-dark/30 rounded-xl overflow-hidden shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-background-dark/50 border-b border-border-dark/30 text-xs uppercase text-text-muted font-bold tracking-wider">
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Team Name</th>
                                    <th class="px-6 py-4">Leader</th>
                                    <th class="px-6 py-4">Members</th>
                                    <th class="px-6 py-4">PS Status</th>
                                    <th class="px-6 py-4">Submission</th>
                                    <th class="px-6 py-4  text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dark/30">
                                <?php if (empty($teams)): ?>
                                    <tr><td colspan="7" class="px-6 py-8 text-center text-text-muted">No teams found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($teams as $team): ?>
                                        <tr class="hover:bg-white/5 transition-colors cursor-pointer group" onclick="openReportModal(<?php echo htmlspecialchars(json_encode($team), ENT_QUOTES, 'UTF-8'); ?>)" data-search="<?php echo htmlspecialchars(strtolower($team['team_name'] . ' ' . $team['leader_name'] . ' ' . $team['id'] . ' ' . str_pad($team['id'], 3, '0', STR_PAD_LEFT))); ?>">
                                            <td class="px-6 py-4 font-mono text-text-muted text-sm">#<?php echo str_pad($team['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td class="px-6 py-4 font-medium text-white"><?php echo htmlspecialchars($team['team_name']); ?></td>
                                            <td class="px-6 py-4 text-text-muted text-sm"><?php echo htmlspecialchars($team['leader_name']); ?></td>
                                            <td class="px-6 py-4 text-text-muted text-sm"><?php echo count($team['members']) + 1; ?></td>
                                            <td class="px-6 py-4">
                                                <?php if ($team['ps_sno']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                        PS-<?php echo $team['ps_sno']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-text-muted text-xs italic">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($team['submitted_at']): ?>
                                                    <span class="inline-flex items-center gap-1 text-emerald-400 text-xs font-medium">
                                                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Submitted
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-text-muted text-xs italic">Not Submitted</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="p-2 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Full Report Modal -->
    <div id="reportModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4 print:p-0" onclick="closeReportModal(event)">
        <div class="bg-surface-dark border border-border-dark/50 rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl print:shadow-none print:border-none print:max-h-none print:h-auto" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="p-6 border-b border-border-dark/50 flex justify-between items-center bg-background-dark/50 rounded-t-2xl print:bg-white print:border-b-2 print:border-black">
                <div>
                    <h2 class="text-2xl font-bold text-white" id="modalTeamName">Team Name</h2>
                    <p class="text-text-muted text-sm font-mono" id="modalTeamId">ID: #000</p>
                </div>
                <button onclick="closeReportModal()" class="p-2 hover:bg-white/10 rounded-lg transition-colors text-text-muted hover:text-white no-print">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar print:overflow-visible">
                <!-- 1. Registration & Leader -->
                <section>
                    <h3 class="text-white font-bold uppercase text-xs tracking-wider mb-4 flex items-center gap-2 print:text-black">
                        <span class="material-symbols-outlined text-lg">person</span> Team Leader & Registration
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-background-dark/30 p-4 rounded-xl border border-border-dark/30 print:bg-white print:border-gray-300">
                        <div><p class="text-xs text-text-muted uppercase">Full Name</p><p class="text-white font-medium print:text-black" id="modalLeaderName">-</p></div>
                        <div><p class="text-xs text-text-muted uppercase">Roll Number</p><p class="text-white font-mono print:text-black" id="modalLeaderRoll">-</p></div>
                        <div><p class="text-xs text-text-muted uppercase">Email</p><p class="text-white print:text-black break-all" id="modalLeaderEmail">-</p></div>
                        <div><p class="text-xs text-text-muted uppercase">Phone</p><p class="text-white print:text-black" id="modalLeaderPhone">-</p></div>
                        <div><p class="text-xs text-text-muted uppercase">Residence</p><p class="text-white capitalize print:text-black" id="modalLeaderRes">-</p></div>
                        <div><p class="text-xs text-text-muted uppercase">Registered At</p><p class="text-white font-mono text-sm print:text-black" id="modalRegTime">-</p></div>
                        <div class="md:col-span-3"><p class="text-xs text-text-muted uppercase">Address</p><p class="text-white text-sm print:text-black" id="modalLeaderAddr">-</p></div>
                    </div>
                </section>

                <!-- 2. Members -->
                <section>
                    <h3 class="text-white font-bold uppercase text-xs tracking-wider mb-4 flex items-center gap-2 print:text-black">
                        <span class="material-symbols-outlined text-lg">groups</span> Team Members
                    </h3>
                    <div id="modalMembersGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                </section>

                <!-- 3. Problem Statement -->
                <section>
                    <h3 class="text-white font-bold uppercase text-xs tracking-wider mb-4 flex items-center gap-2 print:text-black">
                        <span class="material-symbols-outlined text-lg">assignment</span> Problem Statement
                    </h3>
                    <div id="modalPSContent" class="bg-background-dark/30 p-4 rounded-xl border border-border-dark/30 print:bg-white print:border-gray-300"></div>
                </section>

                <!-- 4. Submission -->
                <section>
                    <h3 class="text-white font-bold uppercase text-xs tracking-wider mb-4 flex items-center gap-2 print:text-black">
                        <span class="material-symbols-outlined text-lg">cloud_upload</span> Project Submission
                    </h3>
                    <div id="modalSubmissionContent" class="bg-background-dark/30 p-4 rounded-xl border border-border-dark/30 print:bg-white print:border-gray-300"></div>
                </section>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-border-dark/50 bg-background-dark/50 rounded-b-2xl flex justify-end no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-surface-dark hover:bg-white/10 border border-border-dark/50 rounded-lg text-white text-sm font-medium transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">print</span> Print Report
                </button>
            </div>
        </div>
    </div>

    <script>
    function filterTable() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            if (searchData && searchData.includes(input)) {
                row.style.display = '';
            } else if (searchData) {
                row.style.display = 'none';
            }
        });
    }

    function openReportModal(team) {
        // 1. Header & Leader
        document.getElementById('modalTeamName').textContent = team.team_name;
        document.getElementById('modalTeamId').textContent = 'ID: #' + String(team.id).padStart(3, '0');
        document.getElementById('modalLeaderName').textContent = team.leader_name;
        document.getElementById('modalLeaderRoll').textContent = team.roll_number;
        document.getElementById('modalLeaderEmail').textContent = team.email;
        document.getElementById('modalLeaderPhone').textContent = team.phone_number || '-';
        document.getElementById('modalLeaderRes').textContent = team.residence || '-';
        document.getElementById('modalLeaderAddr').textContent = team.address || '-';
        document.getElementById('modalRegTime').textContent = new Date(team.created_at).toLocaleString();

        // 2. Members
        const membersGrid = document.getElementById('modalMembersGrid');
        membersGrid.innerHTML = '';
        if (team.members && team.members.length > 0) {
            team.members.forEach(m => {
                membersGrid.innerHTML += `
                    <div class="bg-background-dark/30 border border-border-dark/30 rounded-xl p-4 print:bg-white print:border-gray-300 break-inside-avoid">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="h-10 w-10 rounded-full bg-secondary/20 flex items-center justify-center text-secondary font-bold text-sm print:border print:border-gray-300">
                                ${m.member_name.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <h4 class="font-bold text-white print:text-black">${m.member_name}</h4>
                                <p class="text-xs text-primary font-bold uppercase tracking-wider print:text-gray-600">Team Member</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="grid grid-cols-2 gap-2">
                                <div><p class="text-xs text-text-muted uppercase">Roll No</p><p class="text-white font-mono print:text-black">${m.roll_number || '-'}</p></div>
                                <div><p class="text-xs text-text-muted uppercase">Phone</p><p class="text-white print:text-black">${m.phone_number || '-'}</p></div>
                            </div>
                            <div><p class="text-xs text-text-muted uppercase">Email</p><p class="text-white break-all print:text-black">${m.email || '-'}</p></div>
                            <div><p class="text-xs text-text-muted uppercase">Residence</p><p class="text-white capitalize print:text-black">${m.residence || '-'}</p></div>
                            <div><p class="text-xs text-text-muted uppercase">Address</p><p class="text-white print:text-black">${m.address || '-'}</p></div>
                            <div><p class="text-xs text-text-muted uppercase">Joined</p><p class="text-white print:text-black">${new Date(m.created_at).toLocaleDateString()}</p></div>
                        </div>
                    </div>`;
            });
        } else {
            membersGrid.innerHTML = '<p class="col-span-full text-center text-text-muted italic py-4">No additional members</p>';
        }

        // 3. Problem Statement
        const psContent = document.getElementById('modalPSContent');
        if (team.ps_sno) {
            psContent.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <h4 class="text-lg font-bold text-white print:text-black">PS-${team.ps_sno}: ${team.ps_title}</h4>
                    <span class="text-xs text-text-muted font-mono print:text-black">Selected: ${new Date(team.ps_selected_at).toLocaleString()}</span>
                </div>
                <p class="text-text-muted text-sm leading-relaxed print:text-black break-words whitespace-pre-wrap">${team.ps_desc}</p>
            `;
        } else {
            psContent.innerHTML = '<p class="text-text-muted italic text-center">No problem statement selected yet.</p>';
        }

        // 4. Submission
        const subContent = document.getElementById('modalSubmissionContent');
        if (team.submitted_at) {
            const typeIcon = team.submission_type === 'github' ? 'code' : 'folder';
            subContent.innerHTML = `
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400 print:border print:border-black">
                        <span class="material-symbols-outlined text-2xl">${typeIcon}</span>
                    </div>
                    <div>
                        <p class="text-white font-bold capitalize print:text-black">${team.submission_type} Submission</p>
                        <p class="text-text-muted text-xs font-mono print:text-black">Submitted: ${new Date(team.submitted_at).toLocaleString()}</p>
                        <a href="${team.submission_link}" target="_blank" class="text-white hover:underline text-sm mt-1 inline-block print:text-blue-600">${team.submission_link}</a>
                    </div>
                </div>
            `;
        } else {
            subContent.innerHTML = '<p class="text-text-muted italic text-center">No project submitted yet.</p>';
        }

        document.body.classList.add('modal-open');
        document.getElementById('reportModal').classList.remove('hidden');
    }

    function closeReportModal(event) {
        if (event && event.target.id !== 'reportModal' && !event.target.closest('button')) return;
        document.body.classList.remove('modal-open');
        document.getElementById('reportModal').classList.add('hidden');
    }
    </script>
</body>
</html>