<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Require admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// Handle toggle submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_submissions') {
    $status = isset($_POST['submission_status']) ? intval($_POST['submission_status']) : 0;
    $check_sql = 'SELECT id FROM admin_settings WHERE setting_key = "allow_submissions"';
    $check_result = $mysqli->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        $update_sql = 'UPDATE admin_settings SET setting_value = ? WHERE setting_key = "allow_submissions"';
        $stmt = $mysqli->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param('i', $status);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $insert_sql = 'INSERT INTO admin_settings (setting_key, setting_value) VALUES ("allow_submissions", ?)';
        $stmt = $mysqli->prepare($insert_sql);
        if ($stmt) {
            $stmt->bind_param('i', $status);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_submission') {
    $id = intval($_POST['submission_id'] ?? 0);
    if ($id > 0) {
        $del = $mysqli->prepare('DELETE FROM submissions WHERE id = ?');
        if ($del) {
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();
        }
    }
}

// Fetch submissions with pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total submissions
$count_sql = 'SELECT COUNT(*) as total FROM submissions';
$count_res = $mysqli->query($count_sql);
$total = $count_res ? $count_res->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $limit);

// Check if submissions are allowed
$submissions_allowed = false;
$allow_check = $mysqli->query('SELECT setting_value FROM admin_settings WHERE setting_key = "allow_submissions" LIMIT 1');
if ($allow_check && $row = $allow_check->fetch_assoc()) {
    $submissions_allowed = intval($row['setting_value']) === 1;
}

// Fetch submissions
$sql = 'SELECT s.id, s.team_id, s.submission_type, s.submission_link, s.submitted_at, t.team_name FROM submissions s LEFT JOIN teams t ON t.id = s.team_id ORDER BY s.submitted_at DESC LIMIT ? OFFSET ?';
$stmt = $mysqli->prepare($sql);
$submissions = [];
if ($stmt) {
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $submissions[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error preparing submissions query: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admin Submission Management</title>
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
    </style>
</head>

<body class="bg-background-dark font-display text-white h-screen flex overflow-hidden">
    <aside class="w-64 bg-background-dark flex flex-col border-r border-border-dark/30 hidden md:flex shrink-0">
        <div class="p-6 flex items-center gap-3">
            <div
                class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                A</div>
            <div>
                <h1 class="text-white text-base font-bold leading-tight">HackAdmin</h1>
                <p class="text-text-muted text-xs font-normal">Management Console</p>
            </div>
        </div>
        <nav class="flex-1 px-4 flex flex-col gap-2 overflow-y-auto py-4">
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="admin_dashboard.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">dashboard</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="manage_ps.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">upload_file</span>
                <span class="text-sm font-medium">PS Upload</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="submissions.php">
                <span class="material-symbols-outlined fill-current text-primary">assignment_turned_in</span>
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
    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Header -->
        <header
            class="h-16 border-b border-border-dark/30 bg-background-dark/50 backdrop-blur-md flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-white material-symbols-outlined">menu</button>
                <h2 class="text-white text-xl font-bold tracking-tight">Submission Management</h2>
            </div>
           
        </header>
        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-surface-dark scrollbar-track-transparent">
            <div class="max-w-[1400px] mx-auto flex flex-col gap-6">
                <!-- Toggle Submissions Section -->
                <div class="bg-gradient-to-r from-primary/20 to-secondary/20 border border-primary/30 rounded-xl p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white mb-1">Submission Control</h3>
                        <p class="text-text-muted text-sm">Toggle to allow or restrict team submissions</p>
                    </div>
                    <form method="POST" class="flex items-center gap-3">
                        <input type="hidden" name="action" value="toggle_submissions" />
                        <input type="hidden" name="submission_status" value="<?php echo $submissions_allowed ? '0' : '1'; ?>" />
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium <?php echo $submissions_allowed ? 'text-emerald-400' : 'text-yellow-400'; ?>">
                                <?php echo $submissions_allowed ? ' Submissions Allowed' : ' Submissions Restricted'; ?>
                            </span>
                            <button type="submit" class="px-6 py-2.5 rounded-lg font-bold text-white transition-all shadow-lg <?php echo $submissions_allowed ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20' : 'bg-yellow-600 hover:bg-yellow-700 shadow-yellow-600/20'; ?>">
                                <?php echo $submissions_allowed ? 'Restrict' : 'Allow'; ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Filters Toolbar -->
                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end justify-between bg-surface-dark/30 p-4 rounded-xl border border-border-dark/30">
                    <!-- Search -->
                    <div class="w-full lg:w-96">
                        <label class="block text-xs font-medium text-text-muted mb-1.5 uppercase tracking-wider">Find Team</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-text-muted group-focus-within:text-white transition-colors">search</span>
                            </div>
                            <input class="block w-full pl-10 pr-3 py-2.5 bg-surface-dark border border-border-dark/30 rounded-lg text-white placeholder-text-muted/50 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="Search by Team Name..." type="text" id="searchInput" onkeyup="filterTable()" />
                        </div>
                    </div>
                </div>
                <!-- Data Table Card -->
                <div class="bg-surface-dark/50 rounded-xl border border-border-dark/30 overflow-hidden shadow-xl shadow-black/20">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse" id="submissionsTable">
                                            <thead>
                                                <tr class="bg-surface-dark border-b border-border-dark/30">
                                                    <th
                                                        class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-16 text-center">
                                                        S.No</th>
                                                    <th
                                                        class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-1/5">
                                                        Team Name</th>
                                                    <th
                                                        class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-1/4">
                                                        Submission Type</th>
                                                    <th
                                                        class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-32">
                                                        Submitted At</th>
                                                    <th
                                                        class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-24 text-center">
                                                        Links</th>
                                                    <th
                                                        class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider text-right">
                                                        Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border-dark/30">
                                                <?php if (empty($submissions)): ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-8 text-center text-text-muted">No
                                                        submissions yet.</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($submissions as $idx => $s): ?>
                                                <tr class="hover:bg-surface-dark transition-colors group"
                                                    data-team="<?php echo htmlspecialchars(strtolower($s['team_name'] ?? ''), ENT_QUOTES); ?>">
                                                    <td class="px-6 py-4 text-sm text-text-muted text-center font-mono">
                                                        <?php echo str_pad($offset + $idx + 1, 2, '0', STR_PAD_LEFT); ?>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div
                                                                class="h-8 w-8 rounded-full bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-white text-xs font-bold shadow-lg shadow-primary/20">
                                                                <?php $initials = ''; $parts = explode(' ', $s['team_name'] ?? ''); foreach ($parts as $p) { $initials .= substr($p, 0, 1); } echo htmlspecialchars(substr(strtoupper($initials), 0, 2), ENT_QUOTES); ?>
                                                            </div>
                                                            <div>
                                                                <p class="text-white text-sm font-medium">
                                                                    <?php echo htmlspecialchars($s['team_name'] ?? ('Team #' . $s['team_id']), ENT_QUOTES); ?>
                                                                </p>
                                                                <p class="text-text-muted text-xs">ID:
                                                                    #TIM-<?php echo str_pad($s['team_id'], 4, '0', STR_PAD_LEFT); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <p class="text-white text-sm">
                                                            <?php echo htmlspecialchars(ucfirst($s['submission_type'] ?? 'unknown'), ENT_QUOTES); ?>
                                                        </p>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 text-sm text-text-muted font-mono whitespace-nowrap">
                                                        <?php echo date('Y-m-d H:i', strtotime($s['submitted_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <a class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-surface-dark hover:bg-primary/20 text-white transition-all hover:scale-110"
                                                            href="<?php echo htmlspecialchars($s['submission_link'], ENT_QUOTES); ?>"
                                                            target="_blank" title="Open Project Link">
                                                            <span
                                                                class="material-symbols-outlined text-lg">open_in_new</span>
                                                        </a>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <form method="POST"
                                                            onsubmit="return confirm('Delete this submission?');"
                                                            class="inline">
                                                            <input type="hidden" name="action"
                                                                value="delete_submission" />
                                                            <input type="hidden" name="submission_id"
                                                                value="<?php echo (int)$s['id']; ?>" />
                                                            <button type="submit"
                                                                class="bg-primary hover:bg-secondary text-white text-xs font-bold py-2 px-4 rounded-lg transition-colors shadow-lg shadow-primary/20">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-border-dark/30 bg-surface-dark flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-text-muted">
                        Showing <span class="text-white font-medium"><?php echo $total > 0 ? $offset + 1 : 0; ?></span>
                        to <span class="text-white font-medium"><?php echo min($offset + $limit, $total); ?></span>
                        of <span class="text-white font-medium"><?php echo $total; ?></span> results
                    </p>
                    <div class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="p-2 rounded-lg bg-surface-dark/50 border border-border-dark/30 text-text-muted hover:text-white hover:bg-primary/20">
                            <span class="material-symbols-outlined text-lg">chevron_left</span>
                        </a>
                        <?php else: ?>
                        <button class="p-2 rounded-lg bg-surface-dark/50 border border-border-dark/30 text-text-muted disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-lg">chevron_left</span>
                        </button>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                        <button class="h-8 w-8 flex items-center justify-center rounded-lg bg-primary text-white text-sm font-bold"><?php echo $i; ?></button>
                        <?php elseif ($i <= 3 || $i > $total_pages - 2): ?>
                        <a href="?page=<?php echo $i; ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-transparent text-text-muted hover:bg-surface-dark/50 hover:text-white text-sm transition-colors border border-border-dark/30"><?php echo $i; ?></a>
                        <?php elseif ($i == 4): ?>
                        <span class="text-text-muted">...</span>
                        <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="p-2 rounded-lg bg-surface-dark/50 border border-border-dark/30 text-text-muted hover:text-white hover:bg-primary/20">
                            <span class="material-symbols-outlined text-lg">chevron_right</span>
                        </a>
                        <?php else: ?>
                        <button class="p-2 rounded-lg bg-surface-dark/50 border border-border-dark/30 text-text-muted disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <span class="material-symbols-outlined text-lg">chevron_right</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
                function filterTable() {
                    const input = document.getElementById('searchInput').value.toLowerCase();
                    const rows = document.getElementById('submissionsTable').getElementsByTagName('tbody')[0]
                        .getElementsByTagName('tr');

                    for (let row of rows) {
                        const teamCell = row.getAttribute('data-team');
                        if (teamCell && teamCell.includes(input)) {
                            row.style.display = '';
                        } else if (input === '') {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                }

                function exportCSV() {
                    const table = document.getElementById('submissionsTable');
                    const rows = table.querySelectorAll('tr');
                    let csv = 'Team Name,Submission Type,Link,Submitted At\n';

                    rows.forEach((row, idx) => {
                        if (idx === 0) return; // Skip header
                        if (row.style.display === 'none') return; // Skip hidden rows

                        const cells = row.querySelectorAll('td');
                        const teamName = cells[1].textContent.split('\n')[0].trim();
                        const type = cells[2].textContent.trim();
                        const link = cells[4].querySelector('a')?.href || '';
                        const date = cells[3].textContent.trim();

                        csv += `"${teamName}","${type}","${link}","${date}"\n`;
                    });

                    const blob = new Blob([csv], {
                        type: 'text/csv'
                    });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'submissions.csv';
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
                </script>
</body>

</html>