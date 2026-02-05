<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: manage_ps.php');
    exit;
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: manage_ps.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $team_id = intval($_POST['team_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ps_id = isset($_POST['ps_id']) ? intval($_POST['ps_id']) : -1;
    
    if ($team_id > 0) {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = 'UPDATE teams SET email = ?, password_hash = ? WHERE id = ?';
            if ($stmt = $mysqli->prepare($update_sql)) {
                $stmt->bind_param('ssi', $email, $password_hash, $team_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'User updated successfully!';
                    $_SESSION['message_type'] = 'success';
                }
                $stmt->close();
            }
        } else {
            $update_sql = 'UPDATE teams SET email = ? WHERE id = ?';
            if ($stmt = $mysqli->prepare($update_sql)) {
                $stmt->bind_param('si', $email, $team_id);
                if ($stmt->execute()) {
                    $_SESSION['message_type'] = 'success';
                }
                $stmt->close();
            }
        }

        // Update Problem Statement Selection
        if ($ps_id >= 0) {
            if ($ps_id === 0) {
                // Remove selection
                $del_sql = 'DELETE FROM team_ps_selection WHERE team_id = ?';
                if ($stmt = $mysqli->prepare($del_sql)) {
                    $stmt->bind_param('i', $team_id);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                // Update or Insert selection
                // First check if exists to decide between UPDATE or INSERT (or use ON DUPLICATE KEY UPDATE if supported, but logic is safer here)
                $check_sql = "SELECT id FROM team_ps_selection WHERE team_id = $team_id";
                $check_res = $mysqli->query($check_sql);
                
                if ($check_res && $check_res->num_rows > 0) {
                    $ps_sql = 'UPDATE team_ps_selection SET ps_id = ? WHERE team_id = ?';
                } else {
                    $ps_sql = 'INSERT INTO team_ps_selection (ps_id, team_id) VALUES (?, ?)';
                }
                
                if ($stmt = $mysqli->prepare($ps_sql)) {
                    $stmt->bind_param('ii', $ps_id, $team_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

$teams = [];
$sql = 'SELECT t.id, t.team_name, t.leader_name, t.email, t.roll_number, ps.stmt_name, ps.sno, ps.id as selected_ps_id 
        FROM teams t 
        LEFT JOIN team_ps_selection tps ON t.id = tps.team_id 
        LEFT JOIN problem_statements ps ON tps.ps_id = ps.id 
        ORDER BY t.id ASC';
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
}

// Fetch all active problem statements for the dropdown
$all_ps = [];
$ps_res = $mysqli->query("SELECT id, sno, stmt_name FROM problem_statements WHERE is_active = 1 ORDER BY sno ASC");
if ($ps_res) {
    while ($row = $ps_res->fetch_assoc()) {
        $all_ps[] = $row;
    }
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admin User Management</title>
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
    <aside id="sidebar" class="w-64 bg-background-dark flex flex-col border-r border-border-dark/30 hidden md:flex shrink-0 fixed md:relative z-50 h-full">
        <div class="p-6 flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">A</div>
            <div>
                <h1 class="text-white text-base font-bold leading-tight">Admin</h1>
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
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">assignment_turned_in</span>
                <span class="text-sm font-medium">Submissions</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/20 shadow-[0_0_15px_rgba(68,60,104,0.3)]"
                href="user_management.php">
                <span class="material-symbols-outlined fill-current text-primary">people</span>
                <span class="text-sm font-medium">User Management</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="reports.php">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">assessment</span>
                <span class="text-sm font-medium">Reports</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="?logout=1">
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">logout</span>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </nav>
    </aside>
    
    <main class="flex-1 flex flex-col h-full overflow-hidden relative w-full min-w-0">
        <header
            class="h-16 border-b border-border-dark/30 bg-background-dark/50 backdrop-blur-md flex items-center justify-between px-6 shrink-0 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-white text-xl font-bold tracking-tight">User Management</h2>
            </div>
            <div class="flex items-center gap-3 no-print">
                <button onclick="window.print()" class="p-2 rounded-lg hover:bg-white/10 text-white transition-colors" title="Print Users">
                    <span class="material-symbols-outlined">print</span>
                </button>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-surface-dark scrollbar-track-transparent">
            <div class="max-w-[1400px] mx-auto flex flex-col gap-6">
                <?php if (!empty($message)): ?>
                <div class="<?php echo $message_type === 'success' ? 'bg-emerald-900/40 border-emerald-500 text-emerald-100' : 'bg-red-900/40 border-red-500 text-red-100'; ?> border rounded-lg px-4 py-3 text-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end justify-between bg-surface-dark/30 p-4 rounded-xl border border-border-dark/30">
                    <div class="w-full lg:w-96">
                        <label class="block text-xs font-medium text-text-muted mb-1.5 uppercase tracking-wider">Search Users</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-text-muted group-focus-within:text-white transition-colors">search</span>
                            </div>
                            <input class="block w-full pl-10 pr-3 py-2.5 bg-surface-dark border border-border-dark/30 rounded-lg text-white placeholder-text-muted/50 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm transition-all" placeholder="Search by Name, Email, Roll No..." type="text" id="searchInput" onkeyup="filterTable()" />
                        </div>
                    </div>
                </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="teamsTable">
                            <thead>
                                <tr class="bg-surface-dark border-b border-border-dark/30">
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-16 text-center">ID</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Team Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Leader Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Roll Number</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Problem Statement</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dark/30">
                                <?php if (empty($teams)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-text-muted">No teams registered yet.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($teams as $idx => $team): ?>
                                <tr class="hover:bg-surface-dark transition-colors group cursor-pointer" onclick="window.location.href='team_details.php?id=<?php echo $team['id']; ?>'" data-search="<?php echo htmlspecialchars(strtolower($team['team_name'] . ' ' . $team['leader_name'] . ' ' . $team['email'] . ' ' . $team['roll_number'])); ?>">
                                    <td class="px-6 py-4 text-sm text-text-muted text-center font-mono">
                                        <?php echo str_pad($team['id'], 2, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-white text-xs font-bold shadow-lg shadow-primary/20">
                                                <?php $initials = ''; $parts = explode(' ', $team['team_name']); foreach ($parts as $p) { $initials .= substr($p, 0, 1); } echo htmlspecialchars(substr(strtoupper($initials), 0, 2)); ?>
                                            </div>
                                            <p class="text-white text-sm font-medium">
                                                <?php echo htmlspecialchars($team['team_name']); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-white">
                                        <?php echo htmlspecialchars($team['leader_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-text-muted">
                                        <?php echo htmlspecialchars($team['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-text-muted font-mono">
                                        <?php echo htmlspecialchars($team['roll_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-text-muted">
                                        <?php if (!empty($team['stmt_name'])): ?>
                                            <span class="text-emerald-400 font-medium">PS-<?php echo htmlspecialchars($team['sno']); ?>:</span> <?php echo htmlspecialchars($team['stmt_name']); ?>
                                        <?php else: ?>
                                            <span class="text-gray-500 italic">Not Selected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center" onclick="event.stopPropagation()">
                                        <a href="team_details.php?id=<?php echo $team['id']; ?>" class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 transition-all hover:scale-110 mr-2" title="View Details">
                                            <span class="material-symbols-outlined text-lg">visibility</span>
                                        </a>
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($team)); ?>)" class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-white hover:bg-gray-100/40 text-primary transition-all hover:scale-110" title="Edit User">
                                            <span class="material-symbols-outlined text-lg">edit</span>
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

    <div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4" onclick="closeEditModal(event)">
        <div class="bg-surface-dark border border-primary/30 rounded-2xl shadow-2xl w-full max-w-md flex flex-col" onclick="event.stopPropagation()">
            <div class="bg-gradient-to-r from-primary to-secondary/50 p-6 border-b border-primary/30 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Edit User</h3>
                <button onclick="closeEditModal()" class="p-2 hover:bg-primary/30 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-white">close</span>
                </button>
            </div>

            <!-- Modal Content -->
            <form method="POST" class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" value="update_user" />
                <input type="hidden" name="team_id" id="edit_team_id" />
                
                <div>
                    <label class="block text-sm font-medium text-text-muted mb-2">Team Name</label>
                    <input type="text" id="edit_team_name" class="w-full px-4 py-2 bg-surface-dark border border-border-dark/30 rounded-lg text-white" disabled />
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-muted mb-2">Leader Name</label>
                    <input type="text" id="edit_leader_name" class="w-full px-4 py-2 bg-surface-dark border border-border-dark/30 rounded-lg text-white" disabled />
                </div>
   <div>
                    <label class="block text-sm font-medium text-text-muted mb-2">Email</label>
                    <input type="email" name="email" id="edit_email" class="w-full px-4 py-2 bg-surface-dark border border-border-dark/30 rounded-lg text-white placeholder-text-muted/50 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-muted mb-2">Password (leave empty to keep current)</label>
                    <input type="text" name="password" id="edit_password" class="w-full px-4 py-2 bg-surface-dark border border-border-dark/30 rounded-lg text-white placeholder-text-muted/50 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" placeholder="New password (optional)" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-muted mb-2">Problem Statement</label>
                    <select name="ps_id" id="edit_ps_id" class="w-full px-4 py-2 bg-surface-dark border border-border-dark/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="0">None (Not Selected)</option>
                        <?php foreach ($all_ps as $ps): ?>
                            <option value="<?php echo $ps['id']; ?>">PS-<?php echo $ps['sno']; ?>: <?php echo htmlspecialchars($ps['stmt_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-3 mt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2.5 border border-border-dark/30 hover:bg-surface-dark text-text-muted font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-primary hover:bg-primary/80 text-white font-medium rounded-lg transition-colors">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function filterTable() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.getElementById('teamsTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let row of rows) {
            const searchData = row.getAttribute('data-search');
            if (searchData && searchData.includes(input)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    function openEditModal(team) {
        document.getElementById('edit_team_id').value = team.id;
        document.getElementById('edit_team_name').value = team.team_name;
        document.getElementById('edit_leader_name').value = team.leader_name;
        document.getElementById('edit_email').value = team.email;
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_ps_id').value = team.selected_ps_id || 0;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal(event) {
        if (event && event.target.id !== 'editModal') return;
        document.getElementById('editModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
        }
    });
    </script>
<div class="md:hidden fixed bottom-6 right-6 z-50">
<button id="mobile-menu-btn" class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
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
