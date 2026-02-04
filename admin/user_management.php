<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Require admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: manage_ps.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: manage_ps.php');
    exit;
}

// Handle update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $team_id = intval($_POST['team_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($team_id > 0) {
        if (!empty($password)) {
            // Update both email and password
            $update_sql = 'UPDATE teams SET email = ?, roll_number = ? WHERE id = ?';
            if ($stmt = $mysqli->prepare($update_sql)) {
                $stmt->bind_param('ssi', $email, $password, $team_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'User updated successfully!';
                    $_SESSION['message_type'] = 'success';
                }
                $stmt->close();
            }
        } else {
            // Update only email
            $update_sql = 'UPDATE teams SET email = ? WHERE id = ?';
            if ($stmt = $mysqli->prepare($update_sql)) {
                $stmt->bind_param('si', $email, $team_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'User updated successfully!';
                    $_SESSION['message_type'] = 'success';
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all teams
$teams = [];
$sql = 'SELECT id, team_name, leader_name, email, roll_number FROM teams ORDER BY id ASC';
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
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
    </style>
</head>
<body class="bg-background-dark font-display text-white h-screen flex overflow-hidden">
    <aside class="w-64 bg-background-dark flex flex-col border-r border-border-dark/30 hidden md:flex shrink-0">
        <div class="p-6 flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">A</div>
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
                <span class="material-symbols-outlined text-text-muted group-hover:text-white">assignment_turned_in</span>
                <span class="text-sm font-medium">Submissions</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/20 shadow-[0_0_15px_rgba(68,60,104,0.3)]"
                href="user_management.php">
                <span class="material-symbols-outlined fill-current text-primary">people</span>
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
                <h2 class="text-white text-xl font-bold tracking-tight">User Management</h2>
            </div>
        </header>
        
        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-surface-dark scrollbar-track-transparent">
            <div class="max-w-[1400px] mx-auto flex flex-col gap-6">
                <!-- Message Alert -->
                <?php if (!empty($message)): ?>
                <div class="<?php echo $message_type === 'success' ? 'bg-emerald-900/40 border-emerald-500 text-emerald-100' : 'bg-red-900/40 border-red-500 text-red-100'; ?> border rounded-lg px-4 py-3 text-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Teams Table Card -->
                <div class="bg-surface-dark/50 rounded-xl border border-border-dark/30 overflow-hidden shadow-xl shadow-black/20">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="teamsTable">
                            <thead>
                                <tr class="bg-surface-dark border-b border-border-dark/30">
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider w-16 text-center">ID</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Team Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Leader Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">Password</th>
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
                                <tr class="hover:bg-surface-dark transition-colors group">
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
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($team)); ?>)" class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-primary/20 hover:bg-primary/40 text-primary transition-all hover:scale-110" title="Edit User">
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

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4" onclick="closeEditModal(event)">
        <div class="bg-surface-dark border border-primary/30 rounded-2xl shadow-2xl w-full max-w-md flex flex-col" onclick="event.stopPropagation()">
            <!-- Modal Header -->
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
    function openEditModal(team) {
        document.getElementById('edit_team_id').value = team.id;
        document.getElementById('edit_team_name').value = team.team_name;
        document.getElementById('edit_leader_name').value = team.leader_name;
        document.getElementById('edit_email').value = team.email;
        document.getElementById('edit_password').value = '';
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
</body>
</html>
