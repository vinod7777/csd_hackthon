<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$ps_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ps_id <= 0) {
    header('Location: manage_ps.php');
    exit;
}

// Fetch PS Details
$ps = null;
if ($stmt = $mysqli->prepare("SELECT * FROM problem_statements WHERE id = ?")) {
    $stmt->bind_param("i", $ps_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $ps = $res->fetch_assoc();
    $stmt->close();
}

if (!$ps) {
    die("Problem Statement not found.");
}

// Fetch Teams who selected this PS
$teams = [];
$sql = "SELECT t.id, t.team_name, t.leader_name, t.email, t.roll_number, tps.selected_at 
        FROM teams t 
        JOIN team_ps_selection tps ON t.id = tps.team_id 
        WHERE tps.ps_id = ? 
        ORDER BY tps.selected_at ASC";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $ps_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $teams[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>PS Details - Admin</title>
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
        .no-print { display: none !important; }
        body { background-color: white !important; color: black !important; }
        .bg-surface-dark { background-color: white !important; border: 1px solid #ccc !important; color: black !important; box-shadow: none !important; }
        .bg-background-dark { background-color: white !important; }
        .text-white { color: black !important; }
        .text-text-muted { color: #555 !important; }
        header { position: static !important; background: none !important; border-bottom: 1px solid #ccc !important; }
    }
    </style>
</head>
<body class="bg-background-dark font-display text-white min-h-screen flex flex-col">
    <header class="h-16 border-b border-border-dark/30 bg-background-dark/50 backdrop-blur-md flex items-center justify-between px-6 shrink-0 sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <a href="manage_ps.php" class="p-2 rounded-lg hover:bg-surface-dark text-text-muted hover:text-white transition-colors no-print">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h2 class="text-white text-xl font-bold tracking-tight">Problem Statement Details</h2>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full bg-primary/20 text-white border border-primary/20 text-sm font-medium">
                PS-<?php echo htmlspecialchars($ps['sno']); ?>
            </span>
            <button onclick="window.print()" class="no-print p-2 rounded-lg hover:bg-white/10 text-white transition-colors" title="Print Details">
                <span class="material-symbols-outlined">print</span>
            </button>
        </div>
    </header>

    <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full space-y-8">
        <!-- PS Header -->
        <div class="bg-surface-dark border border-border-dark/30 rounded-2xl p-8 shadow-lg">
            <div class="flex flex-col md:flex-row gap-6 justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h1 class="text-3xl font-bold text-white mb-4"><?php echo htmlspecialchars($ps['stmt_name']); ?></h1>
                    <p class="text-text-muted leading-relaxed break-words whitespace-pre-wrap"><?php echo htmlspecialchars($ps['description']); ?></p>
                </div>
                <div class="flex flex-col gap-2 min-w-[200px]">
                    <div class="bg-black/20 rounded-lg p-4 border border-border-dark/30">
                        <p class="text-xs text-text-muted uppercase font-bold mb-1">Total Slots</p>
                        <p class="text-2xl font-bold text-white"><?php echo $ps['slot']; ?></p>
                    </div>
                    <div class="bg-black/20 rounded-lg p-4 border border-border-dark/30">
                        <p class="text-xs text-text-muted uppercase font-bold mb-1">Selected By</p>
                        <p class="text-2xl font-bold text-emerald-400"><?php echo count($teams); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teams List -->
        <div class="bg-surface-dark border border-border-dark/30 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-border-dark/30 bg-black/20 flex justify-between items-center">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">groups</span> Teams Selected
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs text-text-muted uppercase border-b border-border-dark/30 bg-primary/5">
                            <th class="px-6 py-3 font-bold">Team ID</th>
                            <th class="px-6 py-3 font-bold">Team Name</th>
                            <th class="px-6 py-3 font-bold">Leader</th>
                            <th class="px-6 py-3 font-bold">Roll No</th>
                            <th class="px-6 py-3 font-bold">Selected At</th>
                            <th class="px-6 py-3 font-bold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-dark/30">
                        <?php if (empty($teams)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-text-muted italic">No teams have selected this problem statement yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teams as $team): ?>
                            <tr class="hover:bg-primary/5 transition-colors group">
                                <td class="px-6 py-4 text-text-muted font-mono">
                                    <?php echo str_pad($team['id'], 3, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td class="px-6 py-4 text-white font-medium">
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-text-muted">
                                    <?php echo htmlspecialchars($team['leader_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-text-muted font-mono">
                                    <?php echo htmlspecialchars($team['roll_number']); ?>
                                </td>
                                <td class="px-6 py-4 text-text-muted text-sm">
                                    <?php echo date('M j, Y g:i A', strtotime($team['selected_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="team_details.php?id=<?php echo $team['id']; ?>" class="inline-flex items-center  justify-center px-3 py-1.5 rounded-lg bg-white/20 hover:bg-white/40 text-white text-xs font-bold transition-colors">
                                        View Team
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>