<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

$submissions_open = true;
$check_sql = "SELECT setting_value FROM admin_settings WHERE setting_key = 'allow_submissions' LIMIT 1";
$result = $mysqli->query($check_sql);
if ($result && $row = $result->fetch_assoc()) {
    $submissions_open = intval($row['setting_value']) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$submissions_open) {
        $message = 'Submissions are currently closed by the admin.';
        $message_type = 'error';
    } else {
    $submission_type = $_POST['submission_type'] ?? '';
    $submission_link = trim($_POST['submission_link'] ?? '');
    
    if (!in_array($submission_type, ['github', 'drive'])) {
        $message = 'Invalid submission type.';
        $message_type = 'error';
    } elseif (empty($submission_link)) {
        $message = 'Please enter a submission link.';
        $message_type = 'error';
    } else {
        $is_valid = false;
        if ($submission_type === 'github') {
            if (preg_match('/^https?:\/\/(www\.)?(github\.com|github\.io)\/.+/i', $submission_link)) {
                $is_valid = true;
            } else {
                $message = 'Please enter a valid GitHub link (e.g., https://github.com/username/repo)';
                $message_type = 'error';
            }
        } elseif ($submission_type === 'drive') {
            if (preg_match('/^https?:\/\/(drive\.google\.com|docs\.google\.com)\/.+/i', $submission_link)) {
                $is_valid = true;
            } else {
                $message = 'Please enter a valid Google Drive link (e.g., https://drive.google.com/...)';
                $message_type = 'error';
            }
        }
        
        if ($is_valid) {
            $user_id = $_SESSION['user_id'];
            
            $check_sql = 'SELECT id FROM submissions WHERE team_id = ? LIMIT 1';
            $existing_id = null;
            if ($stmt = $mysqli->prepare($check_sql)) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $existing_id = $row['id'];
                }
                $stmt->close();
            }
            
            if ($existing_id) {
                $update_sql = 'UPDATE submissions SET submission_type = ?, submission_link = ? WHERE id = ? AND team_id = ?';
                if ($stmt = $mysqli->prepare($update_sql)) {
                    $stmt->bind_param('ssii', $submission_type, $submission_link, $existing_id, $user_id);
                    if ($stmt->execute()) {
                        header('Location: submit.php?msg=updated');
                        exit;
                    } else {
                        $message = 'Failed to update submission.';
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            } else {
                $insert_sql = 'INSERT INTO submissions (team_id, submission_type, submission_link) VALUES (?, ?, ?)';
                if ($stmt = $mysqli->prepare($insert_sql)) {
                    $stmt->bind_param('iss', $user_id, $submission_type, $submission_link);
                    if ($stmt->execute()) {
                        header('Location: submit.php?msg=submitted');
                        exit;
                    } else {
                        $message = 'Failed to submit. Please try again.';
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            }
        }
    }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'submitted') {
        $message = 'Submission successful! Your project has been submitted.';
        $message_type = 'success';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Submission updated successfully!';
        $message_type = 'success';
    }
}

$user_id = $_SESSION['user_id'];
$existing_submission = null;
$fetch_sql = 'SELECT submission_type, submission_link, submitted_at FROM submissions WHERE team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($fetch_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_submission = $result->fetch_assoc();
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
    <title>Submit Project - Hackathon</title>
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
        <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
        href="problem_statement.php">
        <span class="material-icons-outlined text-xl">assignment</span>
        <span>Problem Statement</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-primary/20 text-white font-semibold"
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
    <main class="flex-1 md:ml-64 p-6 md:p-12 transition-all duration-200">
        <header class="mb-8">
            <h1 class="text-4xl font-display font-bold text-white tracking-tight mb-2">Submit Your Project</h1>
            <p class="text-muted-dark text-lg">Submit your project link (GitHub or Google Drive).</p>
        </header>

        <div class="max-w-3xl mx-auto">
            <?php if ($message): ?>
            <div
                class="mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-100' : 'bg-red-900/40 border border-red-500 text-red-100'; ?> px-4 py-3 text-sm">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <?php if ($existing_submission): ?>
            <div class="bg-emerald-900/20 border border-emerald-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
                <span class="material-icons-outlined text-emerald-400">check_circle</span>
                <div>
                    <h3 class="font-bold text-emerald-200 mb-1">Submission Received</h3>
                    <p class="text-emerald-200/80 text-sm">Your project has been submitted. You can update it below.</p>
                    <p class="text-emerald-200/60 text-xs mt-1">Submitted:
                        <?php echo date('F j, Y g:i A', strtotime($existing_submission['submitted_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($submissions_open): ?>
            <div class="bg-surface-dark border border-primary/30 rounded-xl p-8 shadow-lg">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-white mb-3">Submission Type</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label
                                class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all <?php echo (isset($_POST['submission_type']) && $_POST['submission_type'] === 'github') || ($existing_submission && $existing_submission['submission_type'] === 'github') ? 'border-emerald-500 bg-emerald-500/10' : 'border-primary/30 hover:border-primary/50'; ?>">
                                <input type="radio" name="submission_type" value="github" class="sr-only" required
                                    <?php echo ($existing_submission && $existing_submission['submission_type'] === 'github') ? 'checked' : ''; ?> />
                                <div class="flex items-center gap-3">
                                    <span class="material-icons-outlined text-white text-2xl">code</span>
                                    <div>
                                        <p class="font-semibold text-white">GitHub Repository</p>
                                        <p class="text-xs text-muted-dark">Share your code repository</p>
                                    </div>
                                </div>
                            </label>
                            <label
                                class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all <?php echo (isset($_POST['submission_type']) && $_POST['submission_type'] === 'drive') || ($existing_submission && $existing_submission['submission_type'] === 'drive') ? 'border-emerald-500 bg-emerald-500/10' : 'border-primary/30 hover:border-primary/50'; ?>">
                                <input type="radio" name="submission_type" value="drive" class="sr-only" required
                                    <?php echo ($existing_submission && $existing_submission['submission_type'] === 'drive') ? 'checked' : ''; ?> />
                                <div class="flex items-center gap-3">
                                    <span class="material-icons-outlined text-white text-2xl">folder</span>
                                    <div>
                                        <p class="font-semibold text-white">Google Drive</p>
                                        <p class="text-xs text-muted-dark">Share your project files</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white mb-2" for="submission_link">Submission
                            Link</label>
                        <input type="url" id="submission_link" name="submission_link"
                            placeholder="<?php echo ($existing_submission && $existing_submission['submission_type'] === 'github') ? 'https://github.com/username/repository' : 'https://drive.google.com/...'; ?>"
                            required
                            class="w-full px-4 py-3 bg-gray-800 border border-primary/30 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            value="<?php echo htmlspecialchars($existing_submission['submission_link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                        <p id="submission_hint" class="text-xs text-muted-dark mt-2">
                            <span class="material-icons-outlined text-xs align-middle">info</span>
                            Enter a valid
                            <?php echo ($existing_submission && $existing_submission['submission_type'] === 'github') ? 'GitHub' : 'Google Drive'; ?>
                            link
                        </p>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary hover:bg-secondary text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <span class="material-icons-outlined">cloud_upload</span>
                        <?php echo $existing_submission ? 'Update Submission' : 'Submit Project'; ?>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="bg-red-900/20 border border-red-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
                <span class="material-icons-outlined text-red-400">block</span>
                <div>
                    <h3 class="font-bold text-red-200 mb-1">Submissions Closed</h3>
                    <p class="text-red-200/80 text-sm">The admin has closed submissions. You cannot submit or update
                        projects at this time.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-6 bg-surface-dark/50 border border-primary/20 rounded-lg p-4">
                <h3 class="font-bold text-white mb-2 flex items-center gap-2">
                    <span class="material-icons-outlined text-primary text-sm">info</span>
                    Submission Guidelines
                </h3>
                <ul class="text-sm text-muted-dark space-y-1 list-disc list-inside">
                    <li>GitHub: Share your repository link (e.g., https://github.com/username/repo)</li>
                    <li>Google Drive: Share a folder or file link with view access</li>
                    <li>Ensure your submission is accessible and not private</li>
                    <li>You can update your submission before the deadline</li>
                </ul>
            </div>
        </div>
    </main>
    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn"
            class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
            <span class="material-icons-outlined">menu</span>
        </button>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="submission_type"]');
        const linkInput = document.getElementById('submission_link');
        const hint = document.getElementById('submission_hint');

        function updatePlaceholder(type) {
            if (!linkInput || !hint) return;
            if (type === 'github') {
                linkInput.placeholder = 'https://github.com/username/repository';
                hint.innerHTML =
                    '<span class="material-icons-outlined text-xs align-middle">info</span> Enter a valid GitHub link (e.g., https://github.com/username/repo)';
            } else if (type === 'drive') {
                linkInput.placeholder = 'https://drive.google.com/...';
                hint.innerHTML =
                    '<span class="material-icons-outlined text-xs align-middle">info</span> Enter a valid Google Drive link (e.g., https://drive.google.com/...)';
            }
        }

        function updateVisualSelection() {
            radios.forEach(function(r) {
                const label = r.closest('label');
                if (r.checked) {
                    label.classList.remove('border-primary/30', 'hover:border-primary/50');
                    label.classList.add('border-emerald-500', 'bg-emerald-500/10');
                } else {
                    label.classList.add('border-primary/30', 'hover:border-primary/50');
                    label.classList.remove('border-emerald-500', 'bg-emerald-500/10');
                }
            });
        }

        radios.forEach(function(r) {
            r.addEventListener('change', function(e) {
                updatePlaceholder(e.target.value);
                updateVisualSelection();
            });
        });

        const checked = document.querySelector('input[name="submission_type"]:checked');
        if (checked) {
            updatePlaceholder(checked.value);
            updateVisualSelection();
        } else {
            updatePlaceholder(
                '<?php echo ($existing_submission && $existing_submission['submission_type'] === 'drive') ? 'drive' : 'github'; ?>'
                );
        }

        const mobileBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });
        }
    });
    </script>
</body>

</html>