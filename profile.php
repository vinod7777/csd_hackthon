<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $leader_name = trim($_POST['leader_name'] ?? '');
    $roll_number = trim($_POST['roll_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $residence = $_POST['residence'] ?? '';
    $address = trim($_POST['address'] ?? '');
    
    if (empty($leader_name) || empty($roll_number) || empty($email) || empty($phone_number) || empty($gender) || empty($residence) || empty($address)) {
        $message = 'All fields are required.';
        $message_type = 'error';
    } else {
        $update_sql = 'UPDATE teams SET leader_name = ?, roll_number = ?, email = ?, phone_number = ?, gender = ?, residence = ?, address = ? WHERE id = ?';
        if ($stmt = $mysqli->prepare($update_sql)) {
            $stmt->bind_param('sssssssi', $leader_name, $roll_number, $email, $phone_number, $gender, $residence, $address, $user_id);
            if ($stmt->execute()) {
                $message = 'Profile updated successfully!';
                $message_type = 'success';
                // Update session variables if needed
                $_SESSION['leader_name'] = $leader_name;
            } else {
                $message = 'Failed to update profile. Roll number might already be in use.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
             $message = 'Database error.';
             $message_type = 'error';
        }
    }
}

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
$members_sql = 'SELECT member_name FROM team_members WHERE team_id = ? ORDER BY created_at ASC';
if ($stmt = $mysqli->prepare($members_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $team_members[] = $row['member_name'];
    }
    $stmt->close();
}

// Fetch selected PS
$selected_ps = null;
$ps_sql = 'SELECT ps.sno, ps.stmt_name FROM team_ps_selection tps JOIN problem_statements ps ON tps.ps_id = ps.id WHERE tps.team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($ps_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_ps = $result->fetch_assoc();
    $stmt->close();
}

// Fetch submission
$submission = null;
$sub_sql = 'SELECT submission_type, submitted_at FROM submissions WHERE team_id = ? LIMIT 1';
if ($stmt = $mysqli->prepare($sub_sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
    $stmt->close();
}

$initials = strtoupper(substr($team['leader_name'], 0, 1) . substr(explode(' ', $team['leader_name'])[count(explode(' ', $team['leader_name'])) - 1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Team Profile - Hackathon</title>
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
    </style>
</head>
<body class="bg-background-dark font-sans text-text-dark min-h-screen flex transition-colors duration-200">
    <aside id="sidebar" class="w-64 bg-surface-dark border-r border-primary/30 flex flex-col fixed h-full z-50 transition-colors duration-200 hidden md:flex">
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
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all"
                href="profile.php">
                <span class="material-icons-outlined text-xl">person</span>
                <span>Profile</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="report.php">
                <span class="material-icons-outlined text-xl">assessment</span>
                <span>Report</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 rounded-xl text-muted-dark hover:bg-primary/10 transition-all" href="logout.php">
                <span class="material-icons-outlined text-xl">logout</span>
                <span>Logout</span>
            </a>
        </nav>
        <div class="p-4 border-t border-primary/30">
            <div class="bg-primary/10 p-3 rounded-xl flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-primary flex items-center justify-center text-white font-bold shadow-lg shadow-primary/20">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($team['team_name']); ?></p>
                    <p class="text-[10px] text-muted-dark uppercase tracking-tighter">Team Leader</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 md:ml-64 p-6 md:p-12 transition-all duration-200">
        <header class="mb-8">
            <h1 class="text-4xl font-display font-bold text-white tracking-tight mb-2">Team Profile</h1>
            <p class="text-muted-dark text-lg">View complete details of your team registration.</p>
        </header>

        <?php if ($message): ?>
        <div class="mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-emerald-900/40 border border-emerald-500 text-emerald-100' : 'bg-red-900/40 border border-red-500 text-red-100'; ?> px-4 py-3 text-sm">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-surface-dark border border-primary/30 rounded-2xl overflow-hidden shadow-lg">
                <div class="bg-gradient-to-r from-primary to-secondary p-6 md:p-8 flex flex-col md:flex-row items-center gap-6">
                    <div class="h-24 w-24 rounded-full bg-white flex items-center justify-center text-primary font-bold text-3xl shadow-xl ring-4 ring-white/20">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <div class="text-center md:text-left">
                        <h2 class="text-3xl font-bold text-white mb-1"><?php echo htmlspecialchars($team['team_name']); ?></h2>
                        <p class="text-white/80 text-lg">Leader: <?php echo htmlspecialchars($team['leader_name']); ?></p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-3">
                            <span class="px-3 py-1 rounded-full bg-black/20 text-white text-xs font-bold uppercase tracking-wider">
                                ID: AI-WEB-<?php echo str_pad($team['id'], 3, '0', STR_PAD_LEFT); ?>
                            </span>
                            <span class="px-3 py-1 rounded-full bg-black/20 text-white text-xs font-bold uppercase tracking-wider">
                                <?php echo htmlspecialchars(ucfirst($team['residence'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="md:ml-auto">
                        <button onclick="openEditModal()" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                            <span class="material-icons-outlined text-sm">edit</span> Edit Details
                        </button>
                    </div>
                </div>
                
                <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-bold text-white mb-4 border-b border-primary/30 pb-2">Leader Details</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Full Name</p>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($team['leader_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Roll Number</p>
                                <p class="text-white font-medium font-mono"><?php echo htmlspecialchars($team['roll_number']); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Email (Gmail)</p>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($team['email']); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Phone Number</p>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($team['phone_number'] ?? 'Not set'); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Gender</p>
                                <p class="text-white font-medium"><?php echo htmlspecialchars(ucfirst($team['gender'])); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Address</p>
                                <p class="text-white font-medium"><?php echo nl2br(htmlspecialchars($team['address'] ?? 'Not set')); ?></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-white mb-4 border-b border-primary/30 pb-2">Participation Details</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Team Members</p>
                                <?php if (empty($team_members)): ?>
                                    <p class="text-muted-dark italic">No additional members</p>
                                <?php else: ?>
                                    <ul class="list-disc list-inside text-white font-medium">
                                        <?php foreach ($team_members as $member): ?>
                                            <li><?php echo htmlspecialchars($member); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Problem Statement</p>
                                <?php if ($selected_ps): ?>
                                    <p class="text-emerald-400 font-medium">
                                        PS-<?php echo $selected_ps['sno']; ?>: <?php echo htmlspecialchars($selected_ps['stmt_name']); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-yellow-400 font-medium">Not Selected Yet</p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Submission Status</p>
                                <?php if ($submission): ?>
                                    <p class="text-emerald-400 font-medium">
                                        Submitted via <?php echo ucfirst($submission['submission_type']); ?>
                                        <span class="text-xs text-muted-dark block font-normal">on <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-muted-dark italic">Pending</p>
                                <?php endif; ?>
                            </div>
                             <div>
                                <p class="text-xs text-muted-dark uppercase tracking-wider font-bold">Registration Date</p>
                                <p class="text-white font-medium"><?php echo date('F j, Y, g:i a', strtotime($team['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="mt-12 border-t border-primary/30 pt-8">
            <?php include __DIR__ . '/includes/footer_design.php'; ?>
        </footer>
    </main>
    <div class="md:hidden fixed bottom-6 right-6 z-50">
        <button id="mobile-menu-btn" class="h-14 w-14 rounded-full bg-primary text-white shadow-2xl flex items-center justify-center">
            <span class="material-icons-outlined">menu</span>
        </button>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4" onclick="closeEditModal(event)">
        <div class="bg-surface-dark border border-primary/30 rounded-2xl shadow-2xl w-full max-w-md flex flex-col" onclick="event.stopPropagation()">
            <div class="bg-gradient-to-r from-primary to-secondary/50 p-6 border-b border-primary/30 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Edit Profile</h3>
                <button onclick="closeEditModal()" class="p-2 hover:bg-primary/30 rounded-lg transition-colors">
                    <span class="material-icons-outlined text-white">close</span>
                </button>
            </div>
            <form method="POST" class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" value="update_profile" />
                
                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Leader Name</label>
                    <input type="text" name="leader_name" value="<?php echo htmlspecialchars($team['leader_name']); ?>" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Roll Number</label>
                    <input type="text" name="roll_number" value="<?php echo htmlspecialchars($team['roll_number']); ?>" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Email (Gmail)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($team['email']); ?>" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Phone Number</label>
                    <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($team['phone_number'] ?? ''); ?>" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Gender</label>
                    <select name="gender" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="male" <?php echo $team['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $team['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Residence</label>
                    <select name="residence" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="day-scholar" <?php echo $team['residence'] === 'day-scholar' ? 'selected' : ''; ?>>Day Scholar</option>
                        <option value="hostel" <?php echo $team['residence'] === 'hostel' ? 'selected' : ''; ?>>Hostel</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-dark mb-2">Address</label>
                    <textarea name="address" rows="3" class="w-full px-4 py-2 bg-surface-dark border border-primary/30 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" required><?php echo htmlspecialchars($team['address'] ?? ''); ?></textarea>
                </div>

                <div class="flex gap-3 mt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2.5 border border-primary/30 hover:bg-surface-dark text-muted-dark font-medium rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-primary hover:bg-primary/80 text-white font-medium rounded-lg transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });
    }

    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal(event) {
        if (event && event.target.id !== 'editModal') return;
        document.getElementById('editModal').classList.add('hidden');
    }

    // Auto refresh
    setTimeout(function() {
        location.reload();
    }, 30000);
    </script>
</body>
</html>