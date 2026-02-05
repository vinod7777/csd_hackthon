<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_member'])) {
        $member_name = trim($_POST['member_name'] ?? '');
        if (!empty($member_name)) {
            $user_id = $_SESSION['user_id'];
            
            $count_sql = 'SELECT COUNT(*) as count FROM team_members WHERE team_id = ?';
            $current_count = 0;
            if ($stmt = $mysqli->prepare($count_sql)) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $current_count = $row['count'] ?? 0;
                $stmt->close();
            }
            
            if ($current_count >= 4) { 
                $message = 'Maximum team size reached (5 members including team leader).';
                $message_type = 'error';
            } else {
                $insert_sql = 'INSERT INTO team_members (team_id, member_name) VALUES (?, ?)';
                if ($stmt = $mysqli->prepare($insert_sql)) {
                    $stmt->bind_param('is', $user_id, $member_name);
                    if ($stmt->execute()) {
                        header('Location: team.php?msg=added');
                        exit;
                    } else {
                        $message = 'Failed to add team member.';
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            }
        } else {
            $message = 'Please enter a team member name.';
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['remove_member'])) {
        $member_id = intval($_POST['member_id'] ?? 0);
        if ($member_id > 0) {
            $user_id = $_SESSION['user_id'];
            $delete_sql = 'DELETE FROM team_members WHERE id = ? AND team_id = ?';
            if ($stmt = $mysqli->prepare($delete_sql)) {
                $stmt->bind_param('ii', $member_id, $user_id);
                if ($stmt->execute()) {
                    header('Location: team.php?msg=removed');
                    exit;
                } else {
                    $message = 'Failed to remove team member.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $message = 'Team member added successfully!';
        $message_type = 'success';
    } elseif ($_GET['msg'] === 'removed') {
        $message = 'Team member removed successfully!';
        $message_type = 'success';
    }
}

$user_id = $_SESSION['user_id'];
$sql = 'SELECT id, team_name, leader_name, roll_number, email, created_at FROM teams WHERE id = ? LIMIT 1';
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


$team_members = [];
$members_sql = 'SELECT id, member_name FROM team_members WHERE team_id = ? ORDER BY created_at ASC';
if ($stmt = $mysqli->prepare($members_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $team_members[] = $row;
    }
    $stmt->close();
}

$initials = strtoupper(substr($team['leader_name'], 0, 1) . substr(explode(' ', $team['leader_name'])[count(explode(' ', $team['leader_name'])) - 1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Team Management - Hackathon</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@400;500;700&display=swap"
        rel="stylesheet" />
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
    body {
        background-color: #18122B;
    }
    </style>
</head>

<body class="bg-background-dark font-sans text-text-dark min-h-screen flex transition-colors duration-200">
    <aside id="sidebar"
        class="w-64 bg-surface-dark border-r border-primary/30 flex flex-col fixed h-full z-50 transition-colors duration-200 hidden md:flex">
        <div class="p-6 border-b border-primary/30 flex items-center justify-start gap-4">
            <img src="assets/image/logo.avif" alt="AITAM Logo" class="bg-white rounded-lg p-1 h-12">
            <div>
                <h1 class="font-display font-bold text-white">Webathon</h1>
                <p class="text-xs text-muted-dark">Hackathon 2026</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="dashboard.php">
                <span class="material-icons-outlined text-xl">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-primary/20 text-white font-semibold"
                href="team.php">
                <span class="material-icons-outlined text-xl">group</span>
                <span>Team</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="problem_statement.php">
                <span class="material-icons-outlined text-xl">assignment</span>
                <span>Problem Statement</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="submit.php">
                <span class="material-icons-outlined text-xl">cloud_upload</span>
                <span>Submission</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="logout.php">
                <span class="material-icons-outlined text-xl">logout</span>
                <span>Logout</span>
            </a>
        </nav>
        <div class="p-4 border-t border-primary/30">
            <div class="bg-primary/10 p-3 rounded-xl flex items-center space-x-3">
                <div
                    class="h-10 w-10 rounded-full bg-primary flex items-center justify-center text-white font-bold shadow-lg shadow-primary/20">
                    <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-white">
                        <?php echo htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-[10px] text-muted-dark uppercase tracking-tighter">Team Leader</p>
                </div>
            </div>
        </div>
    </aside>
    <main class="flex-1 md:ml-64 p-6 md:p-12 transition-all duration-200">
        <header class="mb-8">
            <h1 class="text-4xl font-display font-bold text-white tracking-tight mb-2">Team Management</h1>
            <p class="text-muted-dark text-lg">Manage your team members and build your squad.</p>
        </header>

        <?php if ($message): ?>
        <div
            class="mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-100' : 'bg-red-900/40 border border-red-500 text-red-100'; ?> px-4 py-3 text-sm">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php endif; ?>

        <div class="max-w-6xl mx-auto space-y-8">
            <div
                class="bg-surface-dark border border-primary/30 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row items-center gap-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-3">
                    <span
                        class="bg-primary/20 text-primary text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Team
                        Lead</span>
                </div>
                <div
                    class="h-20 w-20 rounded-2xl bg-primary flex items-center justify-center text-white font-bold text-2xl ring-4 ring-primary/20">
                    <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="text-center md:text-left flex-1">
                    <h3 class="text-2xl font-bold text-white">
                        <?php echo htmlspecialchars($team['leader_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="text-primary font-medium tracking-wide">Team Leader</p>
                    <p class="text-sm text-muted-dark mt-1">Roll No:
                        <?php echo htmlspecialchars($team['roll_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-sm text-muted-dark">
                        <?php echo htmlspecialchars($team['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <div class="bg-surface-dark border border-primary/30 rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="material-icons-outlined text-primary">person_add</span>
                    Add Team Member
                </h3>
                <form method="POST" class="flex gap-3">
                    <input type="text" name="member_name" placeholder="Enter team member name" required
                        class="flex-1 px-4 py-2 bg-gray-800 border border-primary/30 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                        maxlength="100" <?php echo (count($team_members) >= 4) ? 'disabled' : ''; ?> />
                    <button type="submit" name="add_member"
                        class="bg-primary hover:bg-secondary text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2"
                        <?php echo (count($team_members) >= 4) ? 'disabled' : ''; ?>>
                        <span class="material-icons-outlined text-sm">add</span>
                        Add Member
                    </button>
                </form>
                <p class="text-xs text-muted-dark mt-2">Maximum 5 members per team (including team leader). Current:
                    <?php echo count($team_members) + 1; ?>/5</p>
            </div>

            <div class="bg-surface-dark border border-primary/30 rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="material-icons-outlined text-primary">groups</span>
                    Team Members (<?php echo count($team_members); ?>)
                </h3>
                <?php if (empty($team_members)): ?>
                <div class="bg-surface-dark/50 border border-primary/20 rounded-lg p-8 text-center">
                    <span class="material-icons-outlined text-4xl text-muted-dark mb-2 block">group_add</span>
                    <p class="text-muted-dark">No team members added yet. Add your team members above.</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($team_members as $member): ?>
                    <div
                        class="bg-gray-800/50 border border-primary/30 rounded-xl p-4 flex items-center justify-between hover:border-primary/50 transition-colors">
                        <div class="flex items-center space-x-4 flex-1">
                            <div
                                class="h-14 w-14 rounded-lg bg-secondary/20 flex items-center justify-center text-secondary font-bold text-lg">
                                <?php echo htmlspecialchars(strtoupper(substr($member['member_name'], 0, 2)), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-white">
                                    <?php echo htmlspecialchars($member['member_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p class="text-xs text-primary font-semibold uppercase tracking-tight">Team Member</p>
                            </div>
                        </div>
                        <form method="POST" class="ml-2">
                            <input type="hidden" name="member_id"
                                value="<?php echo htmlspecialchars($member['id'], ENT_QUOTES, 'UTF-8'); ?>" />
                            <button type="submit" name="remove_member"
                                class="p-2 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-900/20 transition-colors"
                                onclick="return confirm('Are you sure you want to remove this team member?');">
                                <span class="material-icons-outlined text-sm">delete</span>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php 
$total_members = count($team_members) + 1;
if ($total_members >= 5): 
?>
            <div
                class="bg-yellow-900/20 border border-yellow-500/30 rounded-lg p-4 text-yellow-200 text-sm flex items-center gap-2">
                <span class="material-icons-outlined text-sm">info</span>
                Maximum team size reached (5 members including team leader).
            </div>
            <?php endif; ?>

        </div>
    </main>
    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn"
            class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
            <span class="material-icons-outlined">menu</span>
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