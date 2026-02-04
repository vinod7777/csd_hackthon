<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    // Redirect to admin login page
    header('Location: admin_login.php');
    exit;
}

// Handle form submissions
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

    // Handle CSV import
    if ($_POST['action'] === 'import_csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if (file_exists($file)) {
            $imported = 0;
            $failed = 0;
            if (($handle = fopen($file, 'r')) !== FALSE) {
                fgetcsv($handle); // Skip header row
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

// Fetch current settings
$ps_enabled = false;
$check_sql = "SELECT setting_value FROM admin_settings WHERE setting_key = 'ps_enabled' LIMIT 1";
$result = $mysqli->query($check_sql);
if ($result && $row = $result->fetch_assoc()) {
    $ps_enabled = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
}

// Fetch all problem statements
$problem_statements = [];
$fetch_ps = 'SELECT id, sno, stmt_name, description, slot, is_active FROM problem_statements ORDER BY sno ASC';
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
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin - Problem Statement Upload</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
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
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="admin_dashboard.php">
<span class="material-symbols-outlined text-text-muted group-hover:text-white">dashboard</span>
<span class="text-sm font-medium">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/20 shadow-[0_0_15px_rgba(68,60,104,0.3)]" href="manage_ps.php">
<span class="material-symbols-outlined fill-current text-primary">upload_file</span>
<span class="text-sm font-medium">PS Upload</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="submissions.php">
<span class="material-symbols-outlined text-text-muted group-hover:text-white">assignment_turned_in</span>
<span class="text-sm font-medium">Submissions</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="user_management.php">
<span class="material-symbols-outlined text-text-muted group-hover:text-white">people</span>
<span class="text-sm font-medium">User Management</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-muted hover:bg-white/5 hover:text-white transition-colors group" href="?logout=1">
<span class="material-symbols-outlined text-text-muted group-hover:text-white">logout</span>
<span class="text-sm font-medium">Logout</span>
</a>
</nav>
</aside>
<main class="flex-1 flex flex-col relative overflow-hidden">
<header class="md:hidden h-16 border-b border-border-dark/30 flex items-center justify-between px-4 bg-background-dark/80 backdrop-blur-md sticky top-0 z-20">
<div class="flex items-center gap-2">
<div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">AI</div>
<span class="font-bold">HackAdmin</span>
</div>
<button class="text-white p-2">
<span class="material-symbols-outlined">menu</span>
</button>
</header>
<div class="flex-1 flex overflow-hidden">
<div class="flex-1 overflow-y-auto p-4 md:p-8 lg:p-12 scroll-smooth">
<div class="max-w-3xl mx-auto space-y-8 pb-20">
<?php if (isset($_GET['logout'])) { session_destroy(); header('Location: manage_ps.php'); exit; } ?>

<?php if ($message): ?>
<div class="mb-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-100' : 'bg-red-900/40 border border-red-500 text-red-100'; ?> px-4 py-3 text-sm">
<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<!-- Release Toggle Section -->
<div class="bg-gradient-to-br from-primary/10 to-secondary/10 border border-primary/20 rounded-xl p-6 mb-8">
<div class="flex items-center justify-between">
<div>
<h3 class="text-lg font-bold text-white mb-1">Release Problem Statements</h3>
<p class="text-text-muted text-sm">Toggle to make problem statements visible to participants</p>
</div>
<form method="POST" class="inline">
<input type="hidden" name="action" value="toggle_release"/>
<?php 
$ps_released = false;
$check_sql = "SELECT setting_value FROM admin_settings WHERE setting_key = 'release_ps' LIMIT 1";
$result = $mysqli->query($check_sql);
if ($result && $row = $result->fetch_assoc()) {
    $ps_released = ($row['setting_value'] === '1' || $row['setting_value'] === 'true');
}
?>
<input type="hidden" name="release_status" value="<?php echo $ps_released ? '0' : '1'; ?>"/>
<button type="submit" class="px-6 py-2.5 rounded-lg <?php echo $ps_released ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-400 hover:bg-emerald-900/60' : 'bg-red-900/40 border border-red-500 text-red-400 hover:bg-red-900/60'; ?> font-medium transition-colors flex items-center gap-2">
<span class="material-symbols-outlined" style="font-size:20px;"><?php echo $ps_released ? 'check_circle' : 'cancel'; ?></span>
<?php echo $ps_released ? 'Released' : 'Not Released'; ?>
</button>
</form>
</div>
</div>

<div class="flex flex-col gap-2 mb-8">
<h2 class="text-3xl md:text-4xl font-bold tracking-tight text-white">Problem Statements</h2>
<p class="text-text-muted text-base md:text-lg">Upload and manage challenge descriptions for participants.</p>
</div>

<div class="border-t border-border-dark/30 pt-8">
<form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
<input type="hidden" name="action" value="import_csv"/>
<div class="relative w-full border-2 border-dashed border-border-dark hover:border-primary/50 bg-surface-dark/50 rounded-xl p-8 flex flex-col items-center justify-center text-center transition-all cursor-pointer group">
<input class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" name="csv_file" type="file" accept=".csv" required/>
<div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-primary text-3xl">cloud_upload</span>
</div>
<p class="text-white font-medium">Click to upload CSV</p>
<p class="text-text-muted text-sm mt-1">Required Format: sno, title, description, slot (slots from 1-20)</p>
<p class="text-emerald-300 text-xs mt-2">✓ Download CSV template: sno, stmt_name, description, slot</p>
</div>
<button type="submit" class="px-6 py-2.5 rounded-lg bg-secondary text-white font-medium hover:bg-primary transition-colors">
Import CSV
</button>
</form>
</div>
</div>
</div>

<aside class="w-80 bg-surface-dark border-l border-border-dark/30 hidden xl:flex flex-col shrink-0">
<div class="p-6 border-b border-border-dark/30 sticky top-0 z-10">
<h3 class="text-white font-bold text-lg">Problem Statements</h3>
<p class="text-text-muted text-xs mt-1">Manage existing challenges</p>
</div>
<div class="flex-1 overflow-y-auto p-4 space-y-4">
<?php if (empty($problem_statements)): ?>
<div class="text-text-muted text-sm p-4">No problem statements added yet.</div>
<?php else: ?>
<?php foreach ($problem_statements as $ps): ?>
<div class="group relative bg-background-dark border border-border-dark rounded-xl p-4 hover:border-primary/40 transition-all">
<div class="flex justify-between items-start mb-2">
<span class="inline-flex items-center rounded-full <?php echo $ps['is_active'] ? 'bg-emerald-900/30' : 'bg-gray-700'; ?> px-2 py-1 text-xs font-medium <?php echo $ps['is_active'] ? 'text-emerald-400' : 'text-gray-300'; ?>">
<?php echo $ps['is_active'] ? 'Active' : 'Inactive'; ?>
</span>
<div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
<form method="POST" class="inline">
<input type="hidden" name="action" value="toggle_active"/>
<input type="hidden" name="ps_id" value="<?php echo (int)$ps['id']; ?>"/>
<input type="hidden" name="is_active" value="<?php echo $ps['is_active'] ? '0' : '1'; ?>"/>
<button type="submit" class="p-1 hover:bg-white/10 rounded text-text-muted hover:text-white" title="Toggle">
<span class="material-symbols-outlined text-[18px]"><?php echo $ps['is_active'] ? 'visibility_off' : 'visibility'; ?></span>
</button>
</form>
<form method="POST" class="inline" onclick="return confirm('Delete this problem statement?');">
<input type="hidden" name="action" value="delete_ps"/>
<input type="hidden" name="ps_id" value="<?php echo (int)$ps['id']; ?>"/>
<button type="submit" class="p-1 hover:bg-white/10 rounded text-text-muted hover:text-red-400" title="Delete">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</form>
</div>
</div>
<h4 class="text-white font-semibold text-sm leading-snug mb-1"><?php echo htmlspecialchars($ps['stmt_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
<p class="text-text-muted text-xs mb-3">Slot: <?php echo htmlspecialchars($ps['slot'], ENT_QUOTES, 'UTF-8'); ?></p>
<div class="text-text-muted text-xs line-clamp-2"><?php echo htmlspecialchars(substr($ps['description'], 0, 80), ENT_QUOTES, 'UTF-8'); ?>...</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</aside>
</div>
</main>
</body>
</html>
