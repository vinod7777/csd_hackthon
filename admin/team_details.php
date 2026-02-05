<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$team_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($team_id <= 0) {
    header('Location: user_management.php');
    exit;
}

// Fetch Team Details
$team = null;
if ($stmt = $mysqli->prepare("SELECT * FROM teams WHERE id = ?")) {
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $team = $res->fetch_assoc();
    $stmt->close();
}

if (!$team) {
    die("Team not found.");
}

// Fetch Members
$members = [];
if ($stmt = $mysqli->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY created_at ASC")) {
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
}

// Fetch PS
$ps = null;
if ($stmt = $mysqli->prepare("SELECT ps.*, tps.selected_at FROM team_ps_selection tps JOIN problem_statements ps ON tps.ps_id = ps.id WHERE tps.team_id = ?")) {
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $ps = $res->fetch_assoc();
    $stmt->close();
}

// Fetch Submission
$submission = null;
if ($stmt = $mysqli->prepare("SELECT * FROM submissions WHERE team_id = ?")) {
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $submission = $res->fetch_assoc();
    $stmt->close();
}

// Helper for initials
$initials = '';
$parts = explode(' ', $team['team_name']);
foreach ($parts as $p) {
    $initials .= substr($p, 0, 1);
}
$initials = substr(strtoupper($initials), 0, 2);
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Team Details - Admin</title>
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
    </style>
</head>
<body class="bg-background-dark font-display text-white min-h-screen flex flex-col">
    <header class="h-16 border-b border-border-dark/30 bg-background-dark/50 backdrop-blur-md flex items-center justify-between px-6 shrink-0 sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <a href="user_management.php" class="p-2 rounded-lg hover:bg-surface-dark text-text-muted hover:text-white transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h2 class="text-white text-xl font-bold tracking-tight">Team Details</h2>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full bg-primary/20 text-white border border-primary/20 text-sm font-medium">
                ID: <?php echo str_pad($team['id'], 3, '0', STR_PAD_LEFT); ?>
            </span>
        </div>
    </header>

    <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full space-y-8">
        <!-- Team Header -->
        <div class="bg-surface-dark border border-border-dark/30 rounded-2xl p-8 flex flex-col md:flex-row items-center gap-8 shadow-lg">
            <div class="h-24 w-24 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-3xl shadow-xl ring-4 ring-surface-dark">
                <?php echo $initials; ?>
            </div>
            <div class="text-center md:text-left flex-1">
                <h1 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($team['team_name']); ?></h1>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-text-muted">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-lg">calendar_today</span>
                        Registered: <?php echo date('M j, Y g:i A', strtotime($team['created_at'])); ?>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-lg">group</span>
                        Members: <?php echo count($members) + 1; ?>
                    </span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <?php if ($submission): ?>
                    <span class="px-4 py-2 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-center font-bold">Submitted</span>
                <?php else: ?>
                    <span class="px-4 py-2 rounded-lg bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-center font-bold">Pending Submission</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Leader Details -->
                <div class="bg-surface-dark border border-border-dark/30 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dark/30 bg-black/20">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">person</span> Team Leader
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Full Name</p>
                            <p class="text-white font-medium text-lg"><?php echo htmlspecialchars($team['leader_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Roll Number</p>
                            <p class="text-white font-mono"><?php echo htmlspecialchars($team['roll_number']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Email</p>
                            <p class="text-white"><?php echo htmlspecialchars($team['email']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Phone</p>
                            <p class="text-white"><?php echo htmlspecialchars($team['phone_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Gender</p>
                            <p class="text-white capitalize"><?php echo htmlspecialchars($team['gender']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Residence</p>
                            <p class="text-white capitalize"><?php echo htmlspecialchars($team['residence']); ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-text-muted uppercase tracking-wider font-bold mb-1">Address</p>
                            <p class="text-white"><?php echo nl2br(htmlspecialchars($team['address'] ?? 'N/A')); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Team Members -->
                <div class="bg-surface-dark border border-border-dark/30 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dark/30 bg-black/20">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">groups</span> Team Members
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($members)): ?>
                            <p class="text-text-muted italic text-center py-4">No additional team members.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-xs text-text-muted uppercase border-b border-border-dark/30">
                                            <th class="pb-3 font-bold">Name</th>
                                            <th class="pb-3 font-bold">Roll No</th>
                                            <th class="pb-3 font-bold">Email</th>
                                            <th class="pb-3 font-bold">Phone</th>
                                            <th class="pb-3 font-bold">Residence</th>
                                            <th class="pb-3 font-bold">Address</th>
                                            <th class="pb-3 font-bold">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border-dark/30">
                                        <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td class="py-3 text-white font-medium"><?php echo htmlspecialchars($member['member_name']); ?></td>
                                            <td class="py-3 text-text-muted font-mono"><?php echo htmlspecialchars($member['roll_number'] ?? '-'); ?></td>
                                            <td class="py-3 text-text-muted"><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                                            <td class="py-3 text-text-muted"><?php echo htmlspecialchars($member['phone_number'] ?? '-'); ?></td>
                                            <td class="py-3 text-text-muted capitalize"><?php echo htmlspecialchars($member['residence'] ?? '-'); ?></td>
                                            <td class="py-3 text-text-muted max-w-xs truncate" title="<?php echo htmlspecialchars($member['address'] ?? ''); ?>"><?php echo htmlspecialchars($member['address'] ?? '-'); ?></td>
                                            <td class="py-3 text-text-muted"><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Problem Statement -->
                <div class="bg-surface-dark border border-border-dark/30 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dark/30 bg-black/20">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">assignment</span> Problem Statement
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if ($ps): ?>
                            <div class="mb-4">
                                <span class="inline-block px-2 py-1 rounded bg-emerald-500/20 text-emerald-400 text-xs font-bold mb-2">
                                    PS-<?php echo $ps['sno']; ?>
                                </span>
                                <h4 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($ps['stmt_name']); ?></h4>
                                <p class="text-text-muted text-sm leading-relaxed mb-4">
                                    <?php echo nl2br(htmlspecialchars($ps['description'])); ?>
                                </p>
                                <p class="text-xs text-text-muted">
                                    Selected on: <?php echo date('M j, Y g:i A', strtotime($ps['selected_at'])); ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6">
                                <span class="material-symbols-outlined text-4xl text-text-muted mb-2">assignment_late</span>
                                <p class="text-text-muted">No problem statement selected yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submission -->
                <div class="bg-surface-dark border border-border-dark/30 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dark/30 bg-black/20">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">cloud_upload</span> Submission
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if ($submission): ?>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="h-10 w-10 rounded-lg bg-surface-dark border border-border-dark/50 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white">
                                        <?php echo $submission['submission_type'] === 'github' ? 'code' : 'folder'; ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white capitalize"><?php echo $submission['submission_type'] === 'github' ? 'GitHub Repository' : 'Google Drive'; ?></p>
                                    <p class="text-xs text-text-muted"><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></p>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($submission['submission_link']); ?>" target="_blank" class="block w-full py-2 px-4 bg-primary hover:bg-secondary text-white text-center rounded-lg transition-colors text-sm font-medium">
                                View Project
                            </a>
                        <?php else: ?>
                            <div class="text-center py-6">
                                <span class="material-symbols-outlined text-4xl text-text-muted mb-2">cloud_off</span>
                                <p class="text-text-muted">No project submitted yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>