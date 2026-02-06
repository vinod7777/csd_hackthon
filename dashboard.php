<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


$user_id = $_SESSION['user_id'];
$sql = 'SELECT id, team_name, leader_name, roll_number, email, phone_number, address, created_at FROM teams WHERE id = ? LIMIT 1';
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

$current_time = time();
$hackathon_start_time = null;
$ps_released_timer = false;

$settings_sql = "SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('release_ps', 'hackathon_start_time')";
$result = $mysqli->query($settings_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] === 'release_ps') {
            $ps_released_timer = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
        }
        if ($row['setting_key'] === 'hackathon_start_time') {
            $hackathon_start_time = intval($row['setting_value']);
        }
    }
}

$event_start_timestamp = strtotime('2026-02-07 10:00:00');
$time_remaining = 0;
$timer_label = "Waiting for Start";
$progress_percentage = 0;
$timer_running = false;

if ($hackathon_start_time) {
    $hackathon_end = $hackathon_start_time + (24 * 60 * 60);
    if ($current_time < $hackathon_end) {
        $time_remaining = $hackathon_end - $current_time;
        $timer_label = "Time Remaining";
        $elapsed = $current_time - $hackathon_start_time;
        $progress_percentage = ($elapsed / (24 * 60 * 60)) * 100;
        $timer_running = true;
    } else {
        $time_remaining = 0;
        $timer_label = "Hackathon Ended";
        $progress_percentage = 100;
    }
} else {
    if ($current_time < $event_start_timestamp) {
        $time_remaining = $event_start_timestamp - $current_time;
        $timer_label = "Starts In";
        $timer_running = true;
    } else {
        $timer_label = "Starts Soon";
        $time_remaining = 0;
    }
}

$hours = floor($time_remaining / 3600);
$minutes = floor(($time_remaining % 3600) / 60);
$seconds = $time_remaining % 60;
$time_display = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

$current_stage = 'registration';
if ($progress_percentage >= 5) {
    $current_stage = 'ps_selection';
}
if ($progress_percentage >= 30) {
    $current_stage = 'development';
}
if ($progress_percentage >= 90) {
    $current_stage = 'submission';
}

$team_id = 'AI-WEB-' . str_pad($team['id'], 3, '0', STR_PAD_LEFT);

$selected_ps = null;
$ps_selected_sql = 'SELECT ps.id, ps.sno, ps.stmt_name, ps.description FROM team_ps_selection tps JOIN problem_statements ps ON tps.ps_id = ps.id WHERE tps.team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($ps_selected_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $selected_ps = $row;
    }
    $stmt->close();
}

$initials = strtoupper(substr($team['leader_name'], 0, 1) . substr(explode(' ', $team['leader_name'])[count(explode(' ', $team['leader_name'])) - 1] ?? '', 0, 1));

$ps_released = false;
$submissions_open = false;
$settings_sql = "SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('release_ps', 'allow_submissions')";
$result = $mysqli->query($settings_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] === 'release_ps') {
            $ps_released = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
        }
        if ($row['setting_key'] === 'allow_submissions') {
            $submissions_open = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
        }
    }
}

$has_submitted = false;
$sub_check_sql = "SELECT id FROM submissions WHERE team_id = {$user_id} LIMIT 1";
if ($res = $mysqli->query($sub_check_sql)) {
    $has_submitted = $res->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Participant Dashboard - Hackathon Journey</title>
    <link href="assets/image/favicon.ico" rel="icon" type="image/x-icon" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@400;500;700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <style type="text/tailwindcss">
        @layer base {
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
        }
        body {
            background-color: #18122B;
        }
    </style>
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "#443c68",
                    secondary: "#635985",
                    "background-light": "#F5F5F5",
                    "background-dark": "#18122B",
                    "surface-light": "#FFFFFF",
                    "surface-dark": "#393053",
                    "text-light": "#333333",
                    "text-dark": "#E0E0E0",
                    "muted-light": "#6B7280",
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
</head>

<body class="bg-background-dark font-sans text-text-dark min-h-screen flex transition-colors duration-200"
    data-has-ps="<?php echo $selected_ps ? '1' : '0'; ?>">
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
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-primary/20 text-white font-semibold"
                href="dashboard.php">
                <span class="material-icons-outlined text-xl">dashboard</span>
                <span>Dashboard</span>
            </a>
            
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
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
                    <p class="text-[10px] text-muted-dark uppercase tracking-tighter">ID:
                        <?php echo htmlspecialchars($team_id, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>
    </aside>
    <main class="flex-1 md:ml-64 p-6 md:p-12 transition-all duration-200">
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-12">
            <div>
                <h1 class="text-4xl font-display font-bold text-white tracking-tight">Team Workspace</h1>
                <p class="text-muted-dark mt-2 text-lg">Manage your squad and track your journey.</p>
            </div>
            <div class="mt-6 md:mt-0">
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-[10px] uppercase font-bold text-muted-dark tracking-widest"><?php echo $timer_label; ?></p>
                        <p class="text-xl font-mono font-bold text-primary" id="countdown">
                            <?php echo htmlspecialchars($time_display, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        </header>
        <div class="max-w-6xl mx-auto space-y-12">
            <section>
                <div class="flex items-center space-x-3 mb-8">
                    <span class="material-symbols-outlined text-primary">route</span>
                    <h2 class="text-xl font-bold text-white">Hackathon Journey</h2>
                </div>
                <div class="relative px-4">
                    <div class="absolute top-1/2 left-8 right-8 h-1 bg-primary/20  hidden md:block">
                    </div>
                    <div class="absolute top-1/2 left-8   bg-primary  hidden md:block"
                        style="width: <?php echo $progress_percentage; ?>%;"></div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative z-10">
                        <div class="flex md:flex-col items-center md:text-center space-x-4 md:space-x-0 group">
                            <div
                                class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/20 transition-transform group-hover:scale-110">
                                <span class="material-symbols-outlined">check_circle</span>
                            </div>
                            <div class="md:mt-4">
                                <h4 class="font-bold text-white">Registration</h4>
                                <p class="text-xs text-primary font-medium">Completed</p>
                            </div>
                        </div>

                        <div class="flex md:flex-col items-center md:text-center space-x-4 md:space-x-0 group">
                            <div
                                class="w-16 h-16 -mt-2 rounded-full border-4 <?php echo $selected_ps ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400 ring-emerald-500/10' : ($ps_released ? 'border-orange-500 bg-surface-dark text-orange-500 ring-orange-500/10' : 'border-gray-600 bg-surface-dark text-gray-500 ring-gray-600/10'); ?> flex items-center justify-center shadow-xl ring-8 transition-all group-hover:scale-110">
                                <span
                                    class="material-symbols-outlined text-3xl"><?php echo $selected_ps ? 'check_circle' : 'psychology'; ?></span>
                            </div>
                            <div class="md:mt-4">
                                <h4
                                    class="font-bold <?php echo $selected_ps ? 'text-emerald-400 text-lg' : ($ps_released ? 'text-orange-500 text-lg' : 'text-muted-dark'); ?>">
                                    PS Selection</h4>
                                <?php if ($selected_ps): ?>
                                <p class="text-xs font-bold text-emerald-300 uppercase tracking-wider mt-1">Selected
                                </p>
                                <p class="text-xs text-emerald-200/80 mt-1">
                                    <?php echo htmlspecialchars($selected_ps['stmt_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php else: ?>
                                <p
                                    class="text-xs font-bold <?php echo $ps_released ? 'text-orange-400 uppercase tracking-wider' : 'text-muted-dark'; ?>">
                                    <?php echo $ps_released ? 'Pending' : 'Upcoming'; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div
                            class="flex md:flex-col items-center md:text-center space-x-4 md:space-x-0 group <?php echo ($selected_ps) ? '' : 'opacity-40'; ?>">
                            <div
                                class="w-12 h-12 rounded-full <?php echo ($has_submitted || $submissions_open) ? 'bg-emerald-500 text-white' : ($selected_ps ? 'bg-orange-500 text-white' : 'bg-gray-700 text-gray-400'); ?> flex items-center justify-center transition-transform group-hover:scale-110">
                                <span
                                    class="material-symbols-outlined"><?php echo ($has_submitted || $submissions_open) ? 'check_circle' : 'code'; ?></span>
                            </div>
                            <div class="md:mt-4">
                                <h4
                                    class="font-semibold <?php echo ($has_submitted || $submissions_open) ? 'text-emerald-400' : ($selected_ps ? 'text-orange-500' : 'text-muted-dark'); ?>">
                                    Development</h4>
                                <?php if ($has_submitted || $submissions_open): ?>
                                <p class="text-xs text-emerald-300 font-bold uppercase tracking-wider">Completed</p>
                                <?php elseif ($selected_ps): ?>
                                <p class="text-xs text-orange-400 font-bold uppercase tracking-wider">In Progress</p>
                                <?php else: ?>
                                <p class="text-xs text-muted-dark">Upcoming</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div
                            class="flex md:flex-col items-center md:text-center space-x-4 md:space-x-0 group <?php echo ($submissions_open) ? '' : 'opacity-40'; ?>">
                            <div
                                class="w-12 h-12 rounded-full <?php echo $has_submitted ? 'bg-emerald-500 text-white' : ($submissions_open ? 'bg-orange-500 text-white' : 'bg-gray-700 text-gray-400'); ?> flex items-center justify-center transition-transform group-hover:scale-110">
                                <span
                                    class="material-symbols-outlined"><?php echo $has_submitted ? 'check_circle' : 'rocket_launch'; ?></span>
                            </div>
                            <div class="md:mt-4">
                                <h4
                                    class="font-semibold <?php echo $has_submitted ? 'text-emerald-400' : ($submissions_open ? 'text-orange-500' : 'text-muted-dark'); ?>">
                                    Submission</h4>
                                <?php if ($has_submitted): ?>
                                <p class="text-xs text-emerald-300 font-bold uppercase tracking-wider">Completed</p>
                                <?php elseif ($submissions_open): ?>
                                <p class="text-xs text-orange-400 font-bold uppercase tracking-wider">Pending</p>
                                <?php else: ?>
                                <p class="text-xs text-muted-dark">Upcoming</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
                <?php if ($selected_ps): ?>
            <section>
                <div class="flex items-center space-x-3 mb-6">
                    <span class="material-symbols-outlined text-emerald-400">check_circle</span>
                    <h2 class="text-xl font-bold text-white">Your Selected Problem Statement</h2>
                </div>
                <div
                    class="bg-gradient-to-br from-emerald-900/20 to-emerald-900/5 border border-emerald-500/30 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div
                            class="h-12 w-12 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400 flex-shrink-0">
                            <span class="material-symbols-outlined">assignment_turned_in</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-white mb-2">Problem
                                #<?php echo htmlspecialchars($selected_ps['sno'], ENT_QUOTES, 'UTF-8'); ?> -
                                <?php echo htmlspecialchars($selected_ps['stmt_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <button onclick="openPSModal()"
                                class="inline-flex items-center gap-2 text-emerald-400 hover:text-emerald-300 font-medium text-sm transition-colors cursor-pointer">
                                <span>View Full Details</span>
                                <span class="material-symbols-outlined text-lg">arrow_forward</span>
                            </button>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 flex-shrink-0">Selected</span>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            <section>
                <div class="flex items-center space-x-3 mb-6">
                    <span class="material-symbols-outlined text-primary">groups</span>
                    <h2 class="text-xl font-bold text-white">Your Team</h2>
                </div>

                <div
                    class="bg-surface-dark border border-primary/30 rounded-2xl p-6 shadow-sm mb-6 flex flex-col md:flex-row items-center gap-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-3">
                        <span
                            class="bg-primary/20 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Team
                            Lead</span>
                    </div>
                    <div
                        class="h-20 w-20 rounded-2xl bg-primary flex items-center justify-center text-white font-bold text-2xl ring-4 ring-primary/20">
                        <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="text-center md:text-left">
                        <h3 class="text-2xl font-bold text-white">
                            <?php echo htmlspecialchars($team['leader_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="text-white font-medium tracking-wide">Team Leader</p>
                        <p class="text-sm text-muted-dark mt-1">Roll No:
                            <?php echo htmlspecialchars($team['roll_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-sm text-muted-dark">
                            <?php echo htmlspecialchars($team['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-sm text-muted-dark">
                            <?php echo htmlspecialchars($team['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-sm text-muted-dark mt-1 max-w-md">
                            <?php echo htmlspecialchars($team['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="md:ml-auto flex gap-2">
                        <button
                            class="p-2 rounded-lg border border-primary/30 text-muted-dark hover:text-primary transition-colors">
                            <span class="material-icons-outlined">mail</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (empty($team_members)): ?>
                    <div class="col-span-full bg-surface-dark border border-primary/30 rounded-xl p-6 text-center">
                        <span class="material-icons-outlined text-4xl text-muted-dark mb-2 block">group</span>
                        <p class="text-muted-dark">No team members added yet.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($team_members as $member): ?>
                    <div
                        class="bg-surface-dark border border-primary/30 rounded-xl p-4 flex items-center space-x-4 hover:border-primary/50 transition-colors">
                        <div
                            class="h-14 w-14 rounded-lg bg-secondary/20 flex items-center justify-center text-secondary font-bold text-lg">
                            <?php echo htmlspecialchars(strtoupper(substr($member['member_name'], 0, 2)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div>
                            <h4 class="font-bold text-white">
                                <?php echo htmlspecialchars($member['member_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="text-xs text-white font-semibold uppercase tracking-tight">Team Member</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </section>

            

        </div>
        <footer class="mt-24 border-t border-primary/30 pt-8">
            <?php include __DIR__ . '/includes/footer_design.php'; ?>
        </footer>
    </main>
    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn"
            class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
            <span class="material-icons-outlined">menu</span>
        </button>
    </div>

    <div id="psModal"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4"
        onclick="closePSModal(event)">
        <div class="bg-surface-dark border border-primary/30 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
            onclick="event.stopPropagation()">
            <div
                class="sticky top-0 bg-gradient-to-r from-primary to-secondary/50 p-6 border-b border-primary/30 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white">Problem
                        #<?php echo htmlspecialchars($selected_ps['sno'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="text-emerald-200/80 text-sm font-medium mt-1">
                        <?php echo htmlspecialchars($selected_ps['stmt_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <button onclick="closePSModal()" class="p-2 hover:bg-primary/30 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-white">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 w-full">
                <p class="text-muted-dark leading-relaxed text-sm break-words overflow-wrap-break-word"
                    style="word-break: break-word; overflow-wrap: break-word; hyphens: auto; white-space: normal;">
                    <?php echo htmlspecialchars($selected_ps['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="sticky bottom-0 bg-surface-dark border-t border-primary/30 p-6 flex justify-end">
                <button onclick="closePSModal()"
                    class="px-6 py-2.5 bg-primary hover:bg-primary/80 text-white font-medium rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
    const isRunning = <?php echo $timer_running ? 'true' : 'false'; ?>;
    const initialRemaining = <?php echo $time_remaining; ?>;
    const endTime = Date.now() + (initialRemaining * 1000);

    function updateCountdown() {
        if (!isRunning) {
            const countdownEl = document.getElementById('countdown');
            if (countdownEl) countdownEl.textContent = '--:--:--';
            return;
        }

        const now = Date.now();
        const remaining = Math.max(0, endTime - now);

        const hours = Math.floor(remaining / (1000 * 60 * 60));
        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

        const display = String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');

        const countdownEl = document.getElementById('countdown');
        if (countdownEl) {
            countdownEl.textContent = display;

            if (remaining === 0) {
                countdownEl.textContent = '00:00:00';
                countdownEl.classList.add('text-red-500');
                alert('Time is up! Please submit your project.');
            }
        }
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    function openPSModal() {
        const modal = document.getElementById('psModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closePSModal(event) {
        if (event && event.target.id !== 'psModal') return;

        const modal = document.getElementById('psModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closePSModal();
        }
    });

    setInterval(function() {
        fetch(window.location.pathname + '?check_ps=1')
            .then(response => response.text())
            .then(data => {
                const currentHasPS = document.querySelector('[data-has-ps]')?.getAttribute(
                    'data-has-ps') === '1';
                const responseHasPS = data.includes('data-has-ps="1"');

                if (currentHasPS !== responseHasPS) {
                    location.reload();
                }
            })
            .catch(err => console.log('Auto-refresh check skipped'));
    }, 10000);

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