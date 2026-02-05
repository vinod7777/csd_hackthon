<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: manage_ps.php');
    exit;
}

$totals = [
    'teams' => 0,
    'submissions' => 0,
    'ps_total' => 0,
    'ps_active' => 0,
    'submissions_open' => true,
    'ps_released' => false,
];

$res = $mysqli->query('SELECT COUNT(*) AS cnt FROM teams');
if ($res && $r = $res->fetch_assoc()) $totals['teams'] = (int)$r['cnt'];

$res = $mysqli->query('SELECT COUNT(*) AS cnt FROM submissions');
if ($res && $r = $res->fetch_assoc()) $totals['submissions'] = (int)$r['cnt'];

$res = $mysqli->query('SELECT COUNT(*) AS cnt FROM problem_statements');
if ($res && $r = $res->fetch_assoc()) $totals['ps_total'] = (int)$r['cnt'];

$res = $mysqli->query('SELECT COUNT(*) AS cnt FROM problem_statements WHERE is_active = 1');
if ($res && $r = $res->fetch_assoc()) $totals['ps_active'] = (int)$r['cnt'];

$res = $mysqli->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'allow_submissions' LIMIT 1");
if ($res && $r = $res->fetch_assoc()) {
    $totals['submissions_open'] = ($r['setting_value'] === '1' || $r['setting_value'] === 'true');
}

$res = $mysqli->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'release_ps' LIMIT 1");
if ($res && $r = $res->fetch_assoc()) {
    $totals['ps_released'] = ($r['setting_value'] === '1' || $r['setting_value'] === 'true');
}

$res = $mysqli->query('SELECT COUNT(*) AS cnt FROM team_members');
if ($res && $r = $res->fetch_assoc()) {
    $members_count = (int)$r['cnt'];
} else {
    $members_count = 0;
}

$res = $mysqli->query('SELECT COUNT(*) AS cnt FROM teams');
if ($res && $r = $res->fetch_assoc()) {
    $leads_count = (int)$r['cnt'];
} else {
    $leads_count = 0;
}

$totals['participants'] = $members_count + $leads_count;

$recent = [];
$sql = 'SELECT s.id, s.team_id, s.submission_type, s.submission_link, s.submitted_at, t.team_name, "submission" AS event_type FROM submissions s LEFT JOIN teams t ON t.id = s.team_id ORDER BY s.submitted_at DESC LIMIT 10';
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $recent[] = $row;
}

$sql = 'SELECT tps.id, tps.team_id, tps.selected_at, t.team_name, ps.stmt_name, "ps_selection" AS event_type FROM team_ps_selection tps LEFT JOIN teams t ON t.id = tps.team_id LEFT JOIN problem_statements ps ON ps.id = tps.ps_id ORDER BY tps.selected_at DESC LIMIT 10';
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $recent[] = $row;
}

$sql = 'SELECT id, team_name, created_at, "registration" AS event_type FROM teams ORDER BY created_at DESC LIMIT 10';
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $recent[] = $row;
}

usort($recent, function($a, $b) {
    $time_a = strtotime($a['submitted_at'] ?? $a['selected_at'] ?? $a['created_at']);
    $time_b = strtotime($b['submitted_at'] ?? $b['selected_at'] ?? $b['created_at']);
    return $time_b - $time_a;
});

$recent = array_slice($recent, 0, 5);

$teams = [];
$sql = 'SELECT t.id, t.team_name, t.leader_name, COUNT(tm.id) AS member_count FROM teams t LEFT JOIN team_members tm ON tm.team_id = t.id GROUP BY t.id ORDER BY t.id DESC LIMIT 10';
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $teams[] = $row;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admin Dashboard - Problem Statements & Analytics</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
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
    .material-symbols-outlined {
        font-variation-settings: 'FILL'0, 'wght'400, 'GRAD'0, 'opsz'24;
    }

    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #18122B;
    }

    ::-webkit-scrollbar-thumb {
        background: #5a4d74;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #6b5f8e;
    }
    @media print {
        #sidebar, #mobile-menu-btn, .no-print { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; height: auto !important; overflow: visible !important; }
        body { background-color: white !important; color: black !important; height: auto !important; overflow: visible !important; }
        .bg-surface-dark { background-color: white !important; border: 1px solid #ccc !important; color: black !important; box-shadow: none !important; }
        .bg-background-dark { background-color: white !important; }
        .text-white { color: black !important; }
        .text-text-muted { color: #555 !important; }
        header { position: static !important; background: none !important; border: none !important; }
    }
    </style>
</head>

<body class="bg-background-dark font-display text-white h-screen flex overflow-hidden">
    <aside id="sidebar"
        class="w-64 bg-background-dark flex flex-col border-r border-border-dark/30 hidden md:flex shrink-0 fixed md:relative z-50 h-full">
        <div class="p-6 flex items-center gap-3">
            <div
                class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                A</div>
            <div>
                <h1 class="text-white text-base font-bold leading-tight">Admin</h1>
                <p class="text-text-muted text-xs font-normal">Management Console</p>
            </div>
        </div>
        <nav class="flex-1 px-4 flex flex-col gap-2 overflow-y-auto py-4">
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/20 shadow-[0_0_15px_rgba(68,60,104,0.3)]"
                href="admin_dashboard.php">
                <span class="material-symbols-outlined fill-current text-primary">dashboard</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="manage_ps.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">upload_file</span>
                <span class="text-sm font-medium">PS Upload</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="submissions.php">
                <span
                    class="material-symbols-outlined text-text-muted group-hover:text-white">assignment_turned_in</span>
                <span class="text-sm font-medium">Submissions</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="user_management.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">people</span>
                <span class="text-sm font-medium">User Management</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="?logout=1">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">logout</span>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </nav>
    </aside>
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header
            class="h-16 border-b border-border-dark/30 bg-background-dark/50 backdrop-blur-md flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-white text-xl font-bold tracking-tight">Dashboard Overview</h2>
            </div>
            <div class="flex items-center gap-3 no-print">
                <button onclick="window.print()" class="p-2 rounded-lg hover:bg-white/10 text-white transition-colors" title="Print Dashboard">
                    <span class="material-symbols-outlined">print</span>
                </button>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 lg:p-12">
            <div class="max-w-full space-y-8">
                <?php if (isset($_GET['logout'])) { session_destroy(); header('Location: manage_ps.php'); exit; } ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        class="bg-surface-dark p-6 rounded-xl border border-border-dark/30 shadow-lg hover:shadow-xl transition-shadow relative overflow-hidden group">
                        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <span class="material-symbols-outlined text-8xl text-white">group</span>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm font-medium text-text-muted    uppercase tracking-wider mb-1">Total Teams
                            </p>
                            <h3 class="text-3xl font-bold text-white"><?php echo $totals['teams']; ?></h3>
                            <div class="mt-4 flex items-center text-sm text-white">
                                <span class="material-symbols-outlined  text-base mr-1">trending_up</span>
                                <span>Active participants</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-surface-dark p-6 rounded-xl border border-border-dark/30 shadow-lg hover:shadow-xl transition-shadow relative overflow-hidden group">
                        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <span class="material-symbols-outlined text-8xl text-white">person_add</span>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm font-medium text-text-muted uppercase tracking-wider mb-1">Active
                                Participants</p>
                            <h3 class="text-3xl font-bold text-white"><?php echo $totals['participants']; ?></h3>
                            <div class="mt-4 flex items-center text-sm text-white">
                                <span class="material-symbols-outlined text-base mr-1">trending_up</span>
                                <span>Total members</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-surface-dark p-6 rounded-xl border border-border-dark/30 shadow-lg hover:shadow-xl transition-shadow relative overflow-hidden group">
                        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <span class="material-symbols-outlined text-8xl text-white">code</span>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm font-medium text-text-muted uppercase tracking-wider mb-1">Submissions</p>
                            <h3 class="text-3xl font-bold text-white"><?php echo $totals['submissions']; ?></h3>
                            <div class="mt-4 flex items-center text-sm text-text-muted">
                                <span class="material-symbols-outlined text-base mr-1">schedule</span>
                                <span><?php echo $totals['submissions_open'] ? 'Open' : 'Closed'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-surface-dark p-6 rounded-xl border border-border-dark/30 shadow-lg hover:shadow-xl transition-shadow relative overflow-hidden group">
                        <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <span class="material-symbols-outlined text-8xl text-white">assignment</span>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm font-medium text-text-muted uppercase tracking-wider mb-1">Problem
                                Statements</p>
                            <h3 class="text-3xl font-bold text-white">
                                <?php echo $totals['ps_active']; ?>/<?php echo $totals['ps_total']; ?></h3>
                            <div class="mt-4 flex items-center text-sm text-white">
                                <span class="material-symbols-outlined text-base mr-1">check_circle</span>
                                <span>Active/Total</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-surface-dark p-6 rounded-xl border border-border-dark/30 shadow-lg">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-lg text-white">Activity Summary</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Teams -->
                            <div class="bg-background-dark/50 p-4 rounded-lg border border-border-dark/30 flex items-center gap-4 hover:border-primary/50 transition-colors">
                                <div class="p-3 rounded-full bg-primary/20 text-primary">
                                    <span class="material-symbols-outlined">groups</span>
                                </div>
                                <div>
                                    <p class="text-xs text-text-muted uppercase font-bold tracking-wider">Teams Registered</p>
                                    <p class="text-2xl font-bold text-white mt-1"><?php echo $totals['teams']; ?></p>
                                    <p class="text-xs text-text-muted">Active teams</p>
                                </div>
                            </div>

                            <!-- Participants -->
                            <div class="bg-background-dark/50 p-4 rounded-lg border border-border-dark/30 flex items-center gap-4 hover:border-blue-500/50 transition-colors">
                                <div class="p-3 rounded-full bg-blue-500/20 text-blue-400">
                                    <span class="material-symbols-outlined">person</span>
                                </div>
                                <div>
                                    <p class="text-xs text-text-muted uppercase font-bold tracking-wider">Participants</p>
                                    <p class="text-2xl font-bold text-white mt-1"><?php echo $totals['participants']; ?></p>
                                    <p class="text-xs text-text-muted">Total members</p>
                                </div>
                            </div>

                            <!-- Submissions -->
                            <div class="bg-background-dark/50 p-4 rounded-lg border border-border-dark/30 flex items-center gap-4 hover:border-emerald-500/50 transition-colors">
                                <div class="p-3 rounded-full <?php echo $totals['submissions_open'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'; ?>">
                                    <span class="material-symbols-outlined">cloud_upload</span>
                                </div>
                                <div>
                                    <p class="text-xs text-text-muted uppercase font-bold tracking-wider">Submissions</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <p class="text-2xl font-bold text-white"><?php echo $totals['submissions']; ?></p>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?php echo $totals['submissions_open'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'; ?>">
                                            <?php echo $totals['submissions_open'] ? 'OPEN' : 'CLOSED'; ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-text-muted">Projects received</p>
                                </div>
                            </div>

                            <!-- Problem Statements -->
                            <div class="bg-background-dark/50 p-4 rounded-lg border border-border-dark/30 flex items-center gap-4 hover:border-orange-500/50 transition-colors">
                                <div class="p-3 rounded-full <?php echo $totals['ps_released'] ? 'bg-orange-500/20 text-orange-400' : 'bg-gray-700/20 text-gray-400'; ?>">
                                    <span class="material-symbols-outlined">description</span>
                                </div>
                                <div>
                                    <p class="text-xs text-text-muted uppercase font-bold tracking-wider">Problem Statements</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <p class="text-2xl font-bold text-white"><?php echo $totals['ps_active']; ?> <span class="text-sm text-text-muted font-normal">/ <?php echo $totals['ps_total']; ?></span></p>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?php echo $totals['ps_released'] ? 'bg-orange-500/20 text-orange-400' : 'bg-gray-700/20 text-gray-400'; ?>">
                                            <?php echo $totals['ps_released'] ? 'RELEASED' : 'HIDDEN'; ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-text-muted">Active / Total</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-dark p-6 rounded-xl border border-border-dark/30 shadow-lg flex flex-col">
                        <h3 class="font-bold text-lg text-white mb-4">Live Updates</h3>
                        <div class="space-y-4 flex-1 overflow-y-auto pr-2">
                            <?php if (!empty($recent)): ?>
                            <?php foreach ($recent as $r): ?>
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 <?php 
    if ($r['event_type'] === 'submission') echo 'bg-secondary/20';
    elseif ($r['event_type'] === 'ps_selection') echo 'bg-emerald-500/20';
    else echo 'bg-primary/20';
?>">
                                    <span class="material-symbols-outlined text-sm <?php 
    if ($r['event_type'] === 'submission') echo 'text-secondary';
    elseif ($r['event_type'] === 'ps_selection') echo 'text-emerald-400';
    else echo 'text-primary';
?>">
                                        <?php 
    if ($r['event_type'] === 'submission') echo 'upload_file';
    elseif ($r['event_type'] === 'ps_selection') echo 'assignment_turned_in';
    else echo 'person_add';
?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-white">
                                        <?php 
if ($r['event_type'] === 'submission') {
    echo '<span class="font-medium">' . htmlspecialchars($r['team_name'] ?: 'Team #' . $r['team_id'], ENT_QUOTES) . '</span> submitted ' . htmlspecialchars(ucfirst($r['submission_type']), ENT_QUOTES);
} elseif ($r['event_type'] === 'ps_selection') {
    echo '<span class="font-medium">' . htmlspecialchars($r['team_name'] ?: 'Team #' . $r['team_id'], ENT_QUOTES) . '</span> selected <span class="text-emerald-400">' . htmlspecialchars($r['stmt_name'], ENT_QUOTES) . '</span>';
} else {
    echo '<span class="font-medium">' . htmlspecialchars($r['team_name'], ENT_QUOTES) . '</span> registered';
}
?>
                                    </p>
                                    <p class="text-xs text-text-muted mt-1">
                                        <?php 
    $timestamp = $r['submitted_at'] ?? $r['selected_at'] ?? $r['created_at'];
    echo date('M j, Y g:i A', strtotime($timestamp));
?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="text-text-muted text-sm">No activity yet.</div>
                            <?php endif; ?>
                        </div>
                        <button
                            class="mt-4 w-full py-2 text-sm text-white hover:text-white font-medium border border-border-dark/30 hover:bg-primary/10 rounded-lg transition-colors">
                            Refresh Activity
                        </button>
                    </div>
                </div>

                <div class="bg-surface-dark rounded-xl border border-border-dark/30 shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-border-dark/30 flex items-center justify-between">
                        <h3 class="font-bold text-lg text-white">Recent Team Registrations</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr
                                    class="bg-primary/5 border-b border-border-dark/30 text-xs uppercase tracking-wider text-text-muted">
                                    <th class="p-4 font-medium w-12">S.No</th>
                                    <th class="p-4 font-medium">Team Name</th>
                                    <th class="p-4 font-medium">Leader</th>
                                    <th class="p-4 font-medium">Members</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-border-dark/30">
                                <?php if (empty($teams)): ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-text-muted">No teams registered yet.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($teams as $index => $team): ?>
                                <tr class="hover:bg-primary/5 transition-colors">
                                    <td class="p-4 font-medium text-white"><?php echo $index + 1; ?></td>
                                    <td class="p-4 font-medium text-white">
                                        <?php echo htmlspecialchars($team['team_name'], ENT_QUOTES); ?></td>
                                    <td class="p-4 text-white">
                                        <?php echo htmlspecialchars($team['leader_name'], ENT_QUOTES); ?></td>
                                    <td class="p-4 text-text-muted"><?php echo $team['member_count'] + 1; ?> (1 lead +
                                        <?php echo $team['member_count']; ?> members)</td>
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
    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn"
            class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>
    <script>
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });
    }
    </script>
</body>

</html>