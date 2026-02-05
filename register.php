<?php
require_once __DIR__ . '/includes/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name    = trim($_POST['team-name'] ?? '');
    $leader_name  = trim($_POST['leader-name'] ?? '');
    $roll_number  = trim($_POST['roll-number'] ?? '');
    $gender       = $_POST['gender'] ?? '';
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone-number'] ?? '');
    $password     = $_POST['password'] ?? '';
    $residence    = $_POST['residence'] ?? '';
    $address      = trim($_POST['address'] ?? '');

    if ($team_name === '') {
        $errors[] = 'Team name is required.';
    }
    if ($leader_name === '') {
        $errors[] = 'Team leader name is required.';
    }
    if ($roll_number === '') {
        $errors[] = 'Roll number is required.';
    }
    if (!in_array($gender, ['male', 'female'], true)) {
        $errors[] = 'Please select a valid gender.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($phone_number === '') {
        $errors[] = 'Phone number is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if (!in_array($residence, ['day-scholar', 'hostel'], true)) {
        $errors[] = 'Please select your residence type.';
    }
    if ($address === '') {
        $errors[] = 'Address is required.';
    }

    if (empty($errors)) {
        $checkSql = 'SELECT id FROM teams WHERE email = ? OR roll_number = ? LIMIT 1';
        if ($stmt = $mysqli->prepare($checkSql)) {
            $stmt->bind_param('ss', $email, $roll_number);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = 'A team with this email or roll number is already registered.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error while checking existing registrations.';
        }
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $insertSql = 'INSERT INTO teams (team_name, leader_name, roll_number, gender, email, phone_number, password_hash, residence, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        if ($stmt = $mysqli->prepare($insertSql)) {
            $stmt->bind_param('sssssssss', $team_name, $leader_name, $roll_number, $gender, $email, $phone_number, $password_hash, $residence, $address);
            if ($stmt->execute()) {
                $success = 'Registration successful! Your team has been registered for the Webathon.';
                $team_name = $leader_name = $roll_number = $gender = $email = $phone_number = $password = $residence = $address = '';
            } else {
                $errors[] = 'Failed to save registration. Please try again later.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error while saving registration.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Team Registration - 24 Hour Webathon Using AI</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;700&amp;family=Roboto:wght@300;400;500;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
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
                    "accent-light": "#635985",
                    "accent-dark": "#443c68",
                },
                fontFamily: {
                    display: ["Oswald", "sans-serif"],
                    body: ["Roboto", "sans-serif"],
                },
                borderRadius: {
                    DEFAULT: "0.5rem",
                }
            },
        },
    };
    </script>
    <style>
    body {
        background-color: #18122B;
    }

    .hover-primary-dark:hover {
        background-color: #443c68 !important;
    }

    .register-btn {
        background-color: #18122B !important;
    }
    </style>
</head>

<body class="bg-background-dark font-body antialiased min-h-screen flex flex-col transition-colors duration-300">
    <nav class="bg-surface-dark shadow-md sticky top-0 z-50 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <img src="assets/image/logo.avif" alt="AITAM Logo" class="bg-white rounded-lg p-1 h-14">

                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-8">
                    <a class="text-white hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        href="index.php">Home</a>
                    <a class="text-white hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        href="#">Event Details</a>
                    <a class="text-white hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        href="#">Schedule</a>
                    <a class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-secondary transition-colors shadow-sm"
                        href="login.php">Login</a>
                </div>
                <div class="-mr-2 flex items-center sm:hidden">
                    <button id="mobile-menu-btn" aria-controls="mobile-menu" aria-expanded="false"
                        class="inline-flex items-center justify-center p-2 rounded-md text-text-dark hover:text-primary focus:outline-none"
                        type="button">
                        <span class="sr-only">Open main menu</span>
                        <span class="material-icons">menu</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="hidden sm:hidden bg-surface-dark border-t border-gray-700" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php"
                    class="text-white hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Home</a>
                <a href="#" class="text-white hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Event
                    Details</a>
                <a href="#"
                    class="text-white hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Schedule</a>
                <a href="login.php"
                    class="bg-primary text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-secondary">Login</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow flex items-center justify-center p-4 sm:p-8">
        <div
            class="max-w-6xl w-full bg-surface-dark rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px] transition-colors duration-300">
            <div
                class="w-full md:w-1/2 bg-gradient-to-br from-primary to-secondary p-8 md:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="absolute inset-0 opacity-10"
                    style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                </div>
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-6">
                        <span
                            class="bg-black/50 text-primary px-2 py-1 text-xs font-bold rounded uppercase tracking-wide">ISTE
                            Student Chapter</span>
                        <span
                            class="bg-black/20 text-white px-2 py-1 text-xs font-bold rounded uppercase tracking-wide">Feb
                            7-8, 2026</span>
                    </div>
                    <h1 class="font-display text-4xl md:text-5xl font-bold uppercase leading-tight mb-4">
                        24 Hour<br />Webathon<br />Using AI
                    </h1>
                    <p class="text-red-100 text-lg mb-8 font-light">
                        Problem Statement: <span class="font-medium text-white">Any Web Application with Back End</span>
                    </p>
                    <div class="space-y-4">
                        <div
                            class="flex items-center gap-4 bg-white/10 p-4 rounded-lg backdrop-blur-sm border border-white/20">
                            <span class="material-icons text-3xl">emoji_events</span>
                            <div>
                                <p class="text-xs text-red-100 uppercase font-bold">Total Cash Prize</p>
                                <p class="text-xl font-bold">₹ 15,000/-</p>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-4 bg-white/10 p-4 rounded-lg backdrop-blur-sm border border-white/20">
                            <span class="material-icons text-3xl">groups</span>
                            <div>
                                <p class="text-xs text-red-100 uppercase font-bold">Team Size</p>
                                <p class="text-xl font-bold">1 to 5 Members</p>
                            </div>
                        </div>
                    </div>
                </div>
               
            </div>
            <div
                class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white/5 backdrop-blur-xl border border-white/10 shadow-xl transition-all duration-300">
                <div class="max-w-md mx-auto w-full">
                    <h2 class="font-display text-3xl text-white font-bold mb-2 uppercase">Register Your Team</h2>
                    <p class="text-gray-400 mb-4 text-sm">Fill in the details below to participate. Registration Fee:
                        <span class="font-bold text-primary">300/-</span></p>

                    <?php if (!empty($errors)): ?>
                    <div class="mb-4 rounded-md bg-red-900/40 border border-red-500 text-red-100 px-4 py-3 text-sm">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div
                        class="mb-4 rounded-md bg-emerald-900/40 border border-emerald-500 text-emerald-100 px-4 py-3 text-sm">
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <form action="register.php" class="space-y-5" method="POST">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="team-name">Team
                                Name</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">badge</span>
                                </div>
                                <input
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="team-name" name="team-name" placeholder="Enter your team name" required
                                    type="text"
                                    value="<?php echo htmlspecialchars($team_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="leader-name">Team Leader
                                Name</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">person</span>
                                </div>
                                <input
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="leader-name" name="leader-name" placeholder="Full name of leader" required
                                    type="text"
                                    value="<?php echo htmlspecialchars($leader_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="roll-number">Roll
                                Number</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">badge</span>
                                </div>
                                <input
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="roll-number" name="roll-number" placeholder="Enter your roll number" required
                                    type="text"
                                    value="<?php echo htmlspecialchars($roll_number ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="gender">Gender</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">wc</span>
                                </div>
                                <select
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white transition-colors"
                                    id="gender" name="gender" required>
                                    <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Select
                                        gender</option>
                                    <option value="male"
                                        <?php echo (isset($gender) && $gender === 'male') ? 'selected' : ''; ?>>Male
                                    </option>
                                    <option value="female"
                                        <?php echo (isset($gender) && $gender === 'female') ? 'selected' : ''; ?>>Female
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="email">Email</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">email</span>
                                </div>
                                <input
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="email" name="email" placeholder="your@email.com" required type="email"
                                    value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="phone-number">Phone Number</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">phone</span>
                                </div>
                                <input
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="phone-number" name="phone-number" placeholder="Enter phone number" required type="tel"
                                    value="<?php echo htmlspecialchars($phone_number ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="password">Password</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">lock</span>
                                </div>
                                <input
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="password" name="password" placeholder="Enter password" required
                                    type="password" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1"
                                for="residence">Residence</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">home</span>
                                </div>
                                <select
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white transition-colors"
                                    id="residence" name="residence" required>
                                    <option value="" disabled <?php echo empty($residence) ? 'selected' : ''; ?>>Select
                                        residence type</option>
                                    <option value="day-scholar"
                                        <?php echo (isset($residence) && $residence === 'day-scholar') ? 'selected' : ''; ?>>
                                        Day Scholar</option>
                                    <option value="hostel"
                                        <?php echo (isset($residence) && $residence === 'hostel') ? 'selected' : ''; ?>>
                                        Hostel</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1" for="address">Address</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute top-3 left-0 pl-3 flex items-start pointer-events-none">
                                    <span class="material-icons text-gray-400 text-sm">home</span>
                                </div>
                                <textarea
                                    class="block w-full pl-10 sm:text-sm border-gray-600 rounded-md focus:ring-primary focus:border-primary bg-gray-800 text-white placeholder-gray-500 transition-colors"
                                    id="address" name="address" placeholder="Enter full address" required rows="3"><?php echo htmlspecialchars($address ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <div class="pt-4">
                            <button
                                class="register-btn hover-primary-dark w-full flex justify-center py-3 px-4 border border-transparent hover:border-white rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform hover:-translate-y-0.5 font-display uppercase tracking-wider"
                                type="submit">
                                Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-surface-dark mt-auto border-t border-gray-800 transition-colors duration-300">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="">
                <?php include __DIR__ . '/includes/footer_design.php'; ?>
            </div>
           
        </div>
    </footer>
    <script>
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
    </script>
</body>

</html>