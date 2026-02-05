<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $admin_email = 'admin@aitam.com';
    $admin_password = 'admin123';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        if ($email === $admin_email && $password === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Login - Webathon Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined&display=block" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
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
                        "border-dark": "#5a4d74",
                        "text-muted": "#94A3B8",
                    },
                    fontFamily: {
                        display: ["Space Grotesk", "sans-serif"],
                        body: ["Noto Sans", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        body {
            background-color: #18122B;
        }
        .bg-gradient-admin {
            background: linear-gradient(135deg, #443c68 0%, #635985 100%);
        }
        .hover-primary-dark:hover {
            background-color: #443c68 !important;
        }
    </style>
</head>
<body class="bg-background-dark font-body antialiased min-h-screen flex flex-col transition-colors duration-300">
    <nav class="bg-surface-dark shadow-md sticky top-0 z-50 transition-colors duration-300 border-b border-border-dark">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <img src="../assets/image/logo.avif" alt="AITAM Logo" class="bg-white rounded-lg p-1 h-12">
                        <div>
                            <h1 class="font-display text-lg font-bold text-white">Admin Panel</h1>
                            <p class="text-xs text-text-muted">Webathon Management</p>
                        </div>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-8">
                    <a class="text-gray-300 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors" href="../index.php">Back to Home</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center p-4 sm:p-8">
        <div class="max-w-md w-full bg-surface-dark rounded-2xl shadow-2xl overflow-hidden transition-colors duration-300 border border-border-dark">
            <div class="bg-gradient-admin p-8 text-white">
                <h2 class="font-display text-3xl font-bold text-center uppercase tracking-wide">Admin Login</h2>
                <p class="text-purple-200 text-center text-sm mt-2">Webathon Management System</p>
            </div>

            <div class="p-8">
                <?php if (!empty($errors)): ?>
                    <div class="mb-4 rounded-md bg-red-900/40 border border-red-500/50 text-red-100 px-4 py-3 text-sm">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 rounded-md bg-emerald-900/40 border border-emerald-500/50 text-emerald-100 px-4 py-3 text-sm">
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form action="admin_login.php" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2" for="email">Email Address</label>
                        <input 
                            class="block w-full px-4 py-3 sm:text-sm border border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-background-dark text-white placeholder-gray-500 transition-colors" 
                            id="email" 
                            name="email" 
                            placeholder="admin@aitam.com" 
                            required 
                            type="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2" for="password">Password</label>
                        <input 
                            class="block w-full px-4 py-3 sm:text-sm border border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-background-dark text-white placeholder-gray-500 transition-colors" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password" 
                            required 
                            type="password"
                        />
                    </div>

                    <div class="pt-4">
                        <button 
                            class="hover-primary-dark w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-semibold text-white bg-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface-dark focus:ring-primary transition-all transform hover:-translate-y-0.5 font-display uppercase tracking-wider" 
                            type="submit"
                        >
                            Sign In
                        </button>
                    </div>
                </form>

            
            </div>
        </div>
    </main>

    <footer class="bg-surface-dark mt-auto border-t border-border-dark transition-colors duration-300">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p class="text-center md:text-left text-sm text-gray-400">
                        © 2026 Aditya Institute of Technology and Management. All rights reserved.
                    </p>
                </div>
                <div class="flex space-x-6">
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
