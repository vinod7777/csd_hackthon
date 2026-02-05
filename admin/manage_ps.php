<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_release') {
        $status = isset($_POST['release_status']) ? intval($_POST['release_status']) : 0;
        $check_sql = "SELECT id FROM admin_settings WHERE setting_key = 'release_ps'";
        $result = $mysqli->query($check_sql);
        
        if ($result && $result->num_rows > 0) {
            $update_sql = 'UPDATE admin_settings SET setting_value = ? WHERE setting_key = "release_ps"';
        } else {
            $update_sql = 'INSERT INTO admin_settings (setting_key, setting_value) VALUES ("release_ps", ?)';
        }
        
        if ($stmt = $mysqli->prepare($update_sql)) {
            $status_str = $status ? '1' : '0';
            $stmt->bind_param('s', $status_str);
            if ($stmt->execute()) {
                $message = $status ? 'Problem Statements are now RELEASED!' : 'Problem Statements are now HIDDEN!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update release status.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
    
    if ($_POST['action'] === 'delete_ps') {
        $ps_id = intval($_POST['ps_id'] ?? 0);
        if ($ps_id > 0) {
            $delete_sql = 'DELETE FROM problem_statements WHERE id = ?';
            if ($stmt = $mysqli->prepare($delete_sql)) {
                $stmt->bind_param('i', $ps_id);
                if ($stmt->execute()) {
                    $message = 'Problem statement deleted successfully!';
                    $message_type = 'success';
                }
                $stmt->close();
            }
        }
    }
    
    if ($_POST['action'] === 'toggle_active') {
        $ps_id = intval($_POST['ps_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        if ($ps_id > 0) {
            $update_sql = 'UPDATE problem_statements SET is_active = ? WHERE id = ?';
            if ($stmt = $mysqli->prepare($update_sql)) {
                $stmt->bind_param('ii', $is_active, $ps_id);
                if ($stmt->execute()) {
                    $message = 'Problem statement status updated!';
                    $message_type = 'success';
                }
                $stmt->close();
            }
        }
    }

    if ($_POST['action'] === 'import_csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if (file_exists($file)) {
            $imported = 0;
            $failed = 0;
            if (($handle = fopen($file, 'r')) !== FALSE) {
                fgetcsv($handle); 
                while (($row = fgetcsv($handle)) !== FALSE) {
                    if (count($row) >= 4) {
                        $sno = intval($row[0]);
                        $stmt_name = trim($row[1]);
                        $description = trim($row[2]);
                        $slot = intval($row[3]);
                        
                        if ($sno > 0 && !empty($stmt_name) && !empty($description) && $slot > 0 && $slot <= 20) {
                            $insert_sql = 'INSERT INTO problem_statements (sno, stmt_name, description, slot) VALUES (?, ?, ?, ?)';
                            if ($stmt = $mysqli->prepare($insert_sql)) {
                                $stmt->bind_param('issi', $sno, $stmt_name, $description, $slot);
                                if ($stmt->execute()) {
                                    $imported++;
                                } else {
                                    $failed++;
                                }
                                $stmt->close();
                            }
                        } else {
                            $failed++;
                        }
                    }
                }
                fclose($handle);
                $message = "CSV imported: $imported successful, $failed failed.";
                $message_type = $imported > 0 ? 'success' : 'error';
            }
        }
    }
}

$ps_enabled = false;
$check_sql = "SELECT setting_value FROM admin_settings WHERE setting_key = 'ps_enabled' LIMIT 1";
$result = $mysqli->query($check_sql);
if ($result && $row = $result->fetch_assoc()) {
    $ps_enabled = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
}

$problem_statements = [];
$fetch_ps = 'SELECT ps.id, ps.sno, ps.stmt_name, ps.description, ps.slot, ps.is_active, (SELECT COUNT(*) FROM team_ps_selection WHERE ps_id = ps.id) as selected_count FROM problem_statements ps ORDER BY ps.sno ASC';
$result = $mysqli->query($fetch_ps);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $problem_statements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admin - Problem Statement Upload</title>
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
    <aside id="sidebar"
        class="w-64 bg-background-dark flex flex-col border-r border-border-dark/30 hidden md:flex shrink-0 fixed md:relative z-50 h-full">
        <div class="p-6 flex items-center gap-3">
            <div
                class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg">
                A</div>
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
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/20 shadow-[0_0_15px_rgba(68,60,104,0.3)]"
                href="manage_ps.php">
                <span class="material-symbols-outlined fill-current text-primary">upload_file</span>
                <span class="text-sm font-medium">PS Upload</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group"
                href="submissions.php">
                <span
                    class="material-symbols-outlined text-text-muted group-hover:text-white">assignment_turned_in</span>
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
    <main class="flex-1 flex flex-col relative overflow-hidden">
        <header
            class="md:hidden h-16 border-b border-border-dark/30 flex items-center justify-between px-4 bg-background-dark/80 backdrop-blur-md sticky top-0 z-20">
            <div class="flex items-center gap-2">
                <div
                    class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">
                    A</div>
                <span class="font-bold">HackAdmin</span>
            </div>
        </header>
        <div class="flex-1 flex overflow-hidden">
            <div class="flex-1 overflow-y-auto p-4 md:p-8 lg:p-12 scroll-smooth">
                <div class="max-w-6xl mx-auto space-y-8 pb-20">
                    <?php if (isset($_GET['logout'])) { session_destroy(); header('Location: manage_ps.php'); exit; } ?>

                    <?php if ($message): ?>
                    <div
                        class="mb-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-100' : 'bg-red-900/40 border border-red-500 text-red-100'; ?> px-4 py-3 text-sm">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <div
                        class="bg-gradient-to-br from-primary/10 to-secondary/10 border border-primary/20 rounded-xl p-6 mb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white mb-1">Release Problem Statements</h3>
                                <p class="text-text-muted text-sm">Toggle to make problem statements visible to
                                    participants</p>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_release" />
                                <?php 
$ps_released = false;
$check_sql = "SELECT setting_value FROM admin_settings WHERE setting_key = 'release_ps' LIMIT 1";
$result = $mysqli->query($check_sql);
if ($result && $row = $result->fetch_assoc()) {
    $ps_released = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
}
?>
                                <input type="hidden" name="release_status"
                                    value="<?php echo $ps_released ? '0' : '1'; ?>" />
                                <button type="submit"
                                    class="px-6 py-2.5 rounded-lg <?php echo $ps_released ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-400 hover:bg-emerald-900/60' : 'bg-red-900/40 border border-red-500 text-red-400 hover:bg-red-900/60'; ?> font-medium transition-colors flex items-center gap-2">
                                    <span class="material-symbols-outlined"
                                        style="font-size:20px;"><?php echo $ps_released ? 'check_circle' : 'cancel'; ?></span>
                                    <?php echo $ps_released ? 'Released' : 'Not Released'; ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 mb-8">
                        <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-white">Problem Statements</h2>
                        <p class="text-text-muted text-base md:text-lg">Upload and manage challenge descriptions for
                            participants.</p>
                    </div>

                    <div class="border-t border-border-dark/30 pt-8">
                        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                            <input type="hidden" name="action" value="import_csv" />
                            <div
                                class="relative w-full border-2 border-dashed border-border-dark hover:border-primary/50 bg-surface-dark/50 rounded-xl p-8 flex flex-col items-center justify-center text-center transition-all cursor-pointer group">
                                <input class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    name="csv_file" type="file" accept=".csv" required />
                                <div
                                    class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
                                </div>
                                <p class="text-white font-medium">Click to upload CSV</p>
                                <p class="text-text-muted text-sm mt-1">Required Format: sno, title, description, slot
                                    (slots from 1-20)</p>
                                <p class="text-emerald-300 text-xs mt-2">✓ Download CSV template: sno, stmt_name,
                                    description, slot</p>
                            </div>
                            <button type="submit"
                                class="px-6 py-2.5 rounded-lg bg-secondary text-white font-medium hover:bg-primary transition-colors">
                                Import CSV
                            </button>
                        </form>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-xl font-bold text-white mb-4">Uploaded Problem Statements</h3>
                        <div
                            class="bg-surface-dark/50 rounded-xl border border-border-dark/30 overflow-hidden shadow-xl">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-surface-dark border-b border-border-dark/30">
                                            <th
                                                class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">
                                                S.No</th>
                                            <th
                                                class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">
                                                Title</th>
                                            <th
                                                class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">
                                                Description</th>
                                            <th
                                                class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">
                                                Remaining</th>
                                            <th
                                                class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider">
                                                Status</th>
                                            <th
                                                class="px-6 py-4 text-xs font-bold text-text-muted uppercase tracking-wider text-right">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border-dark/30">
                                        <?php if (empty($problem_statements)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-text-muted">No problem
                                                statements uploaded yet.</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($problem_statements as $ps): ?>
                                        <tr class="hover:bg-surface-dark transition-colors">
                                            <td class="px-6 py-4 text-sm text-white">
                                                <?php echo htmlspecialchars($ps['sno']); ?></td>
                                            <td class="px-6 py-4 text-sm text-white font-medium">
                                                <?php echo htmlspecialchars($ps['stmt_name']); ?></td>
                                            <td class="px-6 py-4 text-sm text-text-muted max-w-xs truncate"
                                                title="<?php echo htmlspecialchars($ps['description']); ?>">
                                                <?php echo htmlspecialchars($ps['description']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-white">
                                                <?php $remaining = max(0, 20 - $ps['selected_count']); ?>
                                                <span
                                                    class="<?php echo $remaining === 0 ? 'text-red-400 font-bold' : 'text-emerald-400'; ?>"><?php echo $remaining; ?></span>
                                                / 20
                                            </td>
                                            <td class="px-6 py-4">
                                                <span
                                                    class="inline-flex items-center rounded-full <?php echo $ps['is_active'] ? 'bg-emerald-900/30 text-emerald-400' : 'bg-gray-700 text-gray-300'; ?> px-2 py-1 text-xs font-medium">
                                                    <?php echo $ps['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="action" value="toggle_active" />
                                                    <input type="hidden" name="ps_id"
                                                        value="<?php echo (int)$ps['id']; ?>" />
                                                    <input type="hidden" name="is_active"
                                                        value="<?php echo $ps['is_active'] ? '0' : '1'; ?>" />
                                                    <button type="submit"
                                                        class="p-2 hover:bg-white/10 rounded-lg text-text-muted hover:text-white transition-colors"
                                                        title="Toggle Status">
                                                        <span
                                                            class="material-symbols-outlined text-[20px]"><?php echo $ps['is_active'] ? 'visibility_off' : 'visibility'; ?></span>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline-block ml-2"
                                                    onsubmit="return confirm('Delete this problem statement?');">
                                                    <input type="hidden" name="action" value="delete_ps" />
                                                    <input type="hidden" name="ps_id"
                                                        value="<?php echo (int)$ps['id']; ?>" />
                                                    <button type="submit"
                                                        class="p-2 hover:bg-red-900/30 rounded-lg text-text-muted hover:text-red-400 transition-colors"
                                                        title="Delete">
                                                        <span
                                                            class="material-symbols-outlined text-[20px]">delete</span>
                                                    </button>
                                                </form>
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
            </div>

        </div>
    </main>
    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn"
            class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
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