<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch team details
$sql = 'SELECT * FROM teams WHERE id = ? LIMIT 1';
$team = null;
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = $result->fetch_assoc();
    $stmt->close();
}

if (!$team) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch team members
$team_members = [];
$members_sql = 'SELECT * FROM team_members WHERE team_id = ? ORDER BY created_at ASC';
if ($stmt = $mysqli->prepare($members_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $team_members[] = $row;
    }
    $stmt->close();
}

// Fetch selected PS
$selected_ps = null;
$ps_sql = 'SELECT ps.sno, ps.stmt_name, ps.description FROM team_ps_selection tps JOIN problem_statements ps ON tps.ps_id = ps.id WHERE tps.team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($ps_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_ps = $result->fetch_assoc();
    $stmt->close();
}

// Fetch submission
$submission = null;
$sub_sql = 'SELECT submission_type, submission_link, submitted_at FROM submissions WHERE team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($sub_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
    $stmt->close();
}

$initials = strtoupper(substr($team['leader_name'], 0, 1) . substr(explode(' ', $team['leader_name'])[count(explode(' ', $team['leader_name'])) - 1] ?? '', 0, 1));
$team_id_display = 'AI-WEB-' . str_pad($team['id'], 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Team Report - Hackathon</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@400;500;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "#443c68",
                    secondary: "#635985",
                    "background-dark": "#18122B",
                    "surface-dark": "#393053",
                    "text-dark": "#E0E0E0",
                    "muted-dark": "#94A3B8",
                },
                fontFamily: {
                    sans: ["Inter", "sans-serif"],
                    display: ["Oswald", "sans-serif"],
                },
            },
        },
    };
    </script>
    <style>
    body { background-color: #18122B; }
    @media print {
        body { background-color: white; color: black; }
        .no-print { display: none !important; }
        .print-bg-white { background-color: white !important; color: black !important; border: 1px solid #ddd !important; box-shadow: none !important; }
        .print-text-black { color: black !important; }
        main { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    }
    </style>
</head>
<body class="bg-background-dark font-sans text-white min-h-screen flex transition-colors duration-200">
    <aside id="sidebar" class="w-64 bg-surface-dark border-r border-primary/30 flex flex-col fixed h-full z-50 transition-colors duration-200 hidden md:flex no-print">
        <div class="p-6 border-b border-primary/30 flex items-center justify-start gap-4">
            <img src="assets/image/logo.avif" alt="AITAM Logo" class="bg-white rounded-lg p-1 h-12">
            <div>
                <h1 class="font-display font-bold text-white">Webathon</h1>
                <p class="text-xs text-muted-dark">Hackathon 2026</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="dashboard.php">
                <span class="material-icons-outlined text-xl">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="team.php">
                <span class="material-icons-outlined text-xl">group</span>
                <span>Team</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="problem_statement.php">
                <span class="material-icons-outlined text-xl">assignment</span>
                <span>Problem Statement</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="submit.php">
                <span class="material-icons-outlined text-xl">cloud_upload</span>
                <span>Submission</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="profile.php">
                <span class="material-icons-outlined text-xl">person</span>
                <span>Profile</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-primary/20 text-white font-semibold" href="report.php">
                <span class="material-icons-outlined text-xl">assessment</span>
                <span>Report</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="logout.php">
                <span class="material-icons-outlined text-xl">logout</span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 md:ml-64 p-6 md:p-12 transition-all duration-200 min-w-0">
        <div class="flex justify-between items-center mb-8 no-print">
             <h1 class="text-4xl font-display font-bold text-white tracking-tight">Project Report</h1>
             <button onclick="window.print()" class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                <span class="material-icons-outlined">print</span> Print Report
             </button>
        </div>
        
        <div class="bg-surface-dark border border-primary/30 rounded-2xl p-8 shadow-xl print-bg-white">
            <div class="border-b border-primary/30 pb-6 mb-6 flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-white print-text-black"><?php echo htmlspecialchars($team['team_name']); ?></h2>
                    <p class="text-gray-200 print-text-black">Team ID: <?php echo $team_id_display; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-200 print-text-black">Generated on</p>
                    <p class="font-mono text-white print-text-black"><?php echo date('F j, Y, g:i a'); ?></p>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-lg font-bold text-primary mb-4 uppercase tracking-wider">Team Composition</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-300 uppercase font-bold mb-1">Team Leader</p>
                        <p class="text-white print-text-black font-medium text-lg"><?php echo htmlspecialchars($team['leader_name']); ?></p>
                        <p class="text-sm text-gray-200 print-text-black"><?php echo htmlspecialchars($team['email']); ?></p>
                        <p class="text-sm text-gray-200 print-text-black"><?php echo htmlspecialchars($team['roll_number']); ?></p>
                        <p class="text-sm text-gray-200 print-text-black"><?php echo htmlspecialchars($team['phone_number'] ?? ''); ?></p>
                        <p class="text-sm text-gray-200 print-text-black break-words"><?php echo nl2br(htmlspecialchars($team['address'] ?? '')); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-300 uppercase font-bold mb-1">Team Members</p>
                        <?php if (!empty($team_members)): ?>
                            <div class="space-y-3">
                                <?php foreach ($team_members as $member): ?>
                                    <div class="bg-white/5 p-3 rounded-lg border border-white/10 print:border-gray-300 print:bg-white break-inside-avoid">
                                        <p class="text-white print-text-black font-bold text-sm"><?php echo htmlspecialchars($member['member_name']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400 italic">No additional members</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-lg font-bold text-primary mb-4 uppercase tracking-wider">Problem Statement</h3>
                <?php if ($selected_ps): ?>
                    <div class="bg-black/20 rounded-xl p-6 border border-primary/10 print-bg-white">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="bg-emerald-500/20 text-emerald-400 px-2 py-1 rounded text-xs font-bold print-text-black">PS-<?php echo $selected_ps['sno']; ?></span>
                            <h4 class="text-xl font-bold text-white print-text-black"><?php echo htmlspecialchars($selected_ps['stmt_name']); ?></h4>
                        </div>
                        <p class="text-gray-200 leading-relaxed mt-2 print-text-black break-words whitespace-pre-wrap"><?php echo htmlspecialchars($selected_ps['description']); ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-yellow-400 italic">No problem statement selected yet.</p>
                <?php endif; ?>
            </div>

            <div>
                <h3 class="text-lg font-bold text-primary mb-4 uppercase tracking-wider">Project Submission</h3>
                <?php if ($submission): ?>
                    <div class="bg-black/20 rounded-xl p-6 border border-primary/10 flex items-center justify-between print-bg-white">
                        <div>
                            <p class="text-xs text-gray-300 uppercase font-bold mb-1">Submission Type</p>
                            <div class="flex items-center gap-2">
                                <span class="material-icons-outlined text-white print-text-black"><?php echo $submission['submission_type'] === 'github' ? 'code' : 'folder'; ?></span>
                                <span class="text-white font-medium capitalize print-text-black"><?php echo $submission['submission_type'] === 'github' ? 'GitHub Repository' : 'Google Drive'; ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-300 uppercase font-bold mb-1">Submitted At</p>
                            <p class="text-white font-mono print-text-black"><?php echo date('M j, Y H:i', strtotime($submission['submitted_at'])); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs text-gray-300 uppercase font-bold mb-1">Project Link</p>
                        <a href="<?php echo htmlspecialchars($submission['submission_link']); ?>" target="_blank" class="text-blue-400 hover:underline break-all print-text-black"><?php echo htmlspecialchars($submission['submission_link']); ?></a>
                    </div>
                <?php else: ?>
                    <p class="text-red-400 italic">Project not submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <footer class="mt-12 border-t border-primary/30 pt-8 no-print">
            <?php include __DIR__ . '/includes/footer_design.php'; ?>
        </footer>
    </main>
    <script>
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>