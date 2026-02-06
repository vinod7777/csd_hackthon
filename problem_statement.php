<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_ps'])) {
    $ps_id = intval($_POST['ps_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $check_ps = 'SELECT id, slot FROM problem_statements WHERE id = ? AND is_active = 1 LIMIT 1';
    $ps_exists = false;
    $ps_slot = 0;
    
    if ($stmt = $mysqli->prepare($check_ps)) {
        $stmt->bind_param('i', $ps_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $ps_exists = true;
            $ps_slot = $row['slot'];
        }
        $stmt->close();
    }
    
    if (!$ps_exists) {
        $message = 'Invalid problem statement selected.';
        $message_type = 'error';
    } else {
        $check_selection = 'SELECT ps_id FROM team_ps_selection WHERE team_id = ? LIMIT 1';
        $has_selection = false;
        $existing_ps_id = null;
        
        if ($stmt = $mysqli->prepare($check_selection)) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $has_selection = true;
                $existing_ps_id = $row['ps_id'];
            }
            $stmt->close();
        }
        
        if ($has_selection && $existing_ps_id != $ps_id) {
            $message = 'You have already selected a problem statement. You cannot change it.';
            $message_type = 'error';
        } else if ($has_selection && $existing_ps_id == $ps_id) {
            $message = 'You have already selected this problem statement.';
            $message_type = 'error';
        } else {
            $insert_sql = 'INSERT INTO team_ps_selection (team_id, ps_id) VALUES (?, ?)';
            if ($stmt = $mysqli->prepare($insert_sql)) {
                $stmt->bind_param('ii', $user_id, $ps_id);
                if ($stmt->execute()) {
                    $message = 'Problem statement selected successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to select problem statement.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

$ps_released = false;
$release_check = 'SELECT setting_value FROM admin_settings WHERE setting_key = "release_ps" LIMIT 1';
$result = $mysqli->query($release_check);
if ($result && $row = $result->fetch_assoc()) {
    $ps_released = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
}

$problem_statements = [];
if ($ps_released) {
    $fetch_ps = 'SELECT id, sno, stmt_name, description, slot FROM problem_statements WHERE is_active = 1 ORDER BY sno ASC';
    $result = $mysqli->query($fetch_ps);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $count_sql = 'SELECT COUNT(*) as count FROM team_ps_selection WHERE ps_id = ?';
            $count_stmt = $mysqli->prepare($count_sql);
            $count_stmt->bind_param('i', $row['id']);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            $count_stmt->close();
            
            $row['selected_count'] = $count_row['count'];
            $row['available_slots'] = max(0, $row['slot'] - $count_row['count']);
            $problem_statements[] = $row;
        }
    }
}

$user_id = $_SESSION['user_id'];
$selected_ps_id = null;
$fetch_selection = 'SELECT ps_id FROM team_ps_selection WHERE team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($fetch_selection)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $selected_ps_id = $row['ps_id'];
    }
    $stmt->close();
}

$sql = 'SELECT id, team_name, leader_name FROM teams WHERE id = ? LIMIT 1';
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

$initials = strtoupper(substr($team['leader_name'], 0, 1) . substr(explode(' ', $team['leader_name'])[count(explode(' ', $team['leader_name'])) - 1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Problem Statement - Hackathon</title>
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
            
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="team.php">
                <span class="material-icons-outlined text-xl">group</span>
                <span>Team</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-primary/20 text-white font-semibold"
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
                href="profile.php">
                <span class="material-icons-outlined text-xl">person</span>
                <span>Profile</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="report.php">
                <span class="material-icons-outlined text-xl">assessment</span>
                <span>Report</span>
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
    <main class="flex-1 md:ml-64 p-6 md:p-12 transition-all duration-200 w-full min-w-0">
        <header class="mb-8">
            <h1 class="text-4xl font-display font-bold text-white tracking-tight mb-2">Problem Statements</h1>
            <p class="text-muted-dark text-lg">Select a problem statement for your team.</p>
        </header>

        <?php if ($message): ?>
        <div
            class="mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-100' : 'bg-red-900/40 border border-red-500 text-red-100'; ?> px-4 py-3 text-sm">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php endif; ?>

        <div class="max-w-6xl mx-auto">
            <?php if (!$ps_released): ?>
            <div class="bg-yellow-900/20 border border-yellow-500/30 rounded-xl p-8 text-center">
                <span class="material-icons-outlined text-5xl text-yellow-400 mb-4 block">lock</span>
                <h2 class="text-2xl font-bold text-yellow-200 mb-2">Problem Statements Not Released Yet</h2>
                <p class="text-yellow-200/80">The administrator will release the problem statements soon. Please check
                    back later.</p>
            </div>
            <?php elseif (empty($problem_statements)): ?>
            <div class="bg-surface-dark border border-primary/30 rounded-xl p-8 text-center">
                <span class="material-icons-outlined text-5xl text-muted-dark mb-4 block">assignment</span>
                <h2 class="text-2xl font-bold text-white mb-2">No Problem Statements Available</h2>
                <p class="text-muted-dark">Problem statements will be added by the administrator soon.</p>
            </div>
            <?php else: ?>
            <div class="bg-surface-dark border border-primary/30 rounded-xl overflow-hidden shadow-lg">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-primary/20">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Sno</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Statement Name</th>
                                <th
                                    class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider w-64">
                                    Description</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">
                                    Available / Total</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/20">
                            <?php foreach ($problem_statements as $ps): ?>
                            <tr
                                class="hover:bg-primary/5 transition-colors <?php echo $selected_ps_id == $ps['id'] ? 'bg-primary/10' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="text-white font-bold"><?php echo htmlspecialchars($ps['sno'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-white font-semibold"><?php echo htmlspecialchars($ps['stmt_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="px-4 py-4 w-64">
                                    <button type="button"
                                        onclick="openModal(<?php echo htmlspecialchars($ps['id'], ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars(addslashes($ps['stmt_name']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($ps['description']), ENT_QUOTES, 'UTF-8'); ?>')"
                                        class="text-muted-dark text-xs line-clamp-2 hover:text-primary hover:underline cursor-pointer text-left"
                                        title="Click to view full description">
                                        <?php echo htmlspecialchars(substr($ps['description'], 0, 80), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($ps['description']) > 80 ? '...' : ''; ?>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $ps['available_slots'] > 0 ? 'bg-emerald-900/40 text-emerald-300' : 'bg-red-900/40 text-red-300'; ?>">
                                        <?php echo htmlspecialchars($ps['available_slots'], ENT_QUOTES, 'UTF-8'); ?> /
                                        <?php echo htmlspecialchars($ps['slot'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($selected_ps_id == $ps['id']): ?>
                                    <span
                                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-emerald-900/40 text-emerald-200 border border-emerald-500">
                                        <span class="material-icons-outlined text-sm mr-1">check_circle</span>
                                        Selected
                                    </span>
                                    <?php elseif ($selected_ps_id !== null): ?>
                                    <button type="button" disabled
                                        class="bg-gray-700 text-gray-300 px-4 py-2 rounded-lg text-sm font-semibold cursor-not-allowed opacity-60">
                                        Locked
                                    </button>
                                    <?php elseif ($ps['available_slots'] <= 0): ?>
                                    <button type="button" disabled
                                        class="bg-red-900/40 text-red-300 px-4 py-2 rounded-lg text-sm font-semibold cursor-not-allowed border border-red-500">
                                        Full
                                    </button>
                                    <?php else: ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="ps_id"
                                            value="<?php echo htmlspecialchars($ps['id'], ENT_QUOTES, 'UTF-8'); ?>" />
                                        <button type="submit" name="select_ps"
                                            class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                            Choose
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <?php endif; ?>
        </div>
        <footer class="mt-12 border-t border-primary/30 pt-8">
            <?php include __DIR__ . '/includes/footer_design.php'; ?>
        </footer>
    </main>

    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn"
            class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
            <span class="material-icons-outlined">menu</span>
        </button>
    </div>

    <div id="descriptionModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div
            class="bg-surface-dark border border-primary/30 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-surface-dark border-b border-primary/30 p-6 flex items-center justify-between flex-shrink-0">
                <h2 id="modalTitle" class="text-2xl font-bold text-white break-words"></h2>
                <button type="button" onclick="closeModal()"
                    class="text-muted-dark hover:text-white transition-colors p-2 flex-shrink-0">
                    <span class="material-icons-outlined text-2xl">close</span>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <p id="modalDescription"
                    class="text-muted-dark text-sm leading-relaxed break-words whitespace-pre-wrap"></p>
            </div>
            <div class="bg-surface-dark border-t border-primary/30 p-6 flex justify-end gap-3 flex-shrink-0">
                <button type="button" onclick="closeModal()"
                    class="px-6 py-2.5 rounded-lg bg-primary hover:bg-secondary text-white font-medium transition-colors whitespace-nowrap">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
    function openModal(id, title, description) {
        const modal = document.getElementById('descriptionModal');
        const titleEl = document.getElementById('modalTitle');
        const descEl = document.getElementById('modalDescription');

        titleEl.textContent = title;
        descEl.textContent = description;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('descriptionModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('descriptionModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });
    }

    // Auto refresh
    setTimeout(function() {
        location.reload();
    }, 30000);
    </script>
</body>

</html>