<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth scroll-pt-24">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>24 Hour AI Webathon | AITAM</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;700&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#b91c1c", // Deep Red from poster
                        secondary: "#1f2937", // Dark Gray
                        "background-light": "#f3f4f6", // Light Gray
                        "background-dark": "#111827", // Almost Black
                        "surface-light": "#ffffff",
                        "surface-dark": "#1f2937",
                        "surface-dark-lighter": "#374151",
                    },
                    fontFamily: {
                        display: ["Oswald", "sans-serif"],
                        body: ["Inter", "sans-serif"],
                    },
                    backgroundImage: {
                        'grid-pattern': "linear-gradient(to right, #e5e7eb 1px, transparent 1px), linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)",
                        'grid-pattern-dark': "linear-gradient(to right, #374151 1px, transparent 1px), linear-gradient(to bottom, #374151 1px, transparent 1px)",
                    }
                },
            },
        };
    </script>
    <style>
        .clip-arrow {
            clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%);
        }
        .clip-hexagon {
            clip-path: polygon(10% 0, 90% 0, 100% 50%, 90% 100%, 10% 100%, 0% 50%);
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: #b91c1c; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #991b1b; 
        }
    </style>
</head>
<body class=" bg-background-dark text-gray-100 font-body transition-colors duration-300">

    
    <nav class="sticky top-0 z-50 bg-white/90 dark:bg-background-dark/90 backdrop-blur shadow-sm">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img  src="assets/image/logo.avif" alt="AITAM Logo" class="bg-white rounded-lg p-1 h-14">
                <div class="hidden sm:block leading-tight">
                    <h1 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wide">Department of CSD</h1>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase">Institution's Innovation Council</p>
                </div>
            </div>
            <div class="hidden md:flex items-center space-x-8 font-medium text-sm uppercase tracking-wider">
                <a class="hover:text-primary transition-colors" href="#about">About</a>
                <a class="hover:text-primary transition-colors" href="#prizes">Prizes</a>
                <a class="hover:text-primary transition-colors" href="#schedule">Schedule</a>
                <a class="hover:text-primary transition-colors" href="#coordinators">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                <a class="bg-primary hover:bg-red-800 text-white px-5 py-2 rounded-lg font-bold uppercase text-sm shadow-lg transform hover:-translate-y-0.5 transition-all" href="register.php">
                    Register
                </a>
                <button id="mobile-menu-btn" class="md:hidden text-gray-900 dark:text-white focus:outline-none ml-2">
                    <span class="material-icons text-3xl">menu</span>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-background-dark border-t border-gray-200 dark:border-gray-700 absolute w-full left-0 top-full shadow-lg">
            <div class="flex flex-col px-4 py-4 space-y-4 font-medium text-sm uppercase tracking-wider text-center">
                <a class="hover:text-primary transition-colors block py-2 text-gray-800 dark:text-gray-200" href="#about">About</a>
                <a class="hover:text-primary transition-colors block py-2 text-gray-800 dark:text-gray-200" href="#prizes">Prizes</a>
                <a class="hover:text-primary transition-colors block py-2 text-gray-800 dark:text-gray-200" href="#schedule">Schedule</a>
                <a class="hover:text-primary transition-colors block py-2 text-gray-800 dark:text-gray-200" href="#coordinators">Contact</a>
            </div>
        </div>
    </nav>
    
    <header class="relative overflow-hidden  bg-background-light dark:bg-background-dark">
        <img class="mx-auto h-32" src="assets/image/25.png" alt="">
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-size: 40px 40px; background-image: linear-gradient(to right, #9ca3af 1px, transparent 1px), linear-gradient(to bottom, #9ca3af 1px, transparent 1px);"></div>
        <div class="container mx-auto px-4 relative z-10 text-center">
            
            <span class="inline-block py-1 px-3 rounded-full bg-red-100 dark:bg-red-900/30 text-primary font-bold text-xs uppercase tracking-widest mb-4 border border-red-200 dark:border-red-900">
                ISTE Student Chapter Presents
            </span>
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-bold text-primary mb-2 uppercase tracking-tight drop-shadow-sm">
                24 Hour Webathon
            </h1>
            <h2 class="text-3xl md:text-5xl font-display font-bold text-gray-800 dark:text-white mb-8 uppercase tracking-wide">
                Using Artificial Intelligence
            </h2>
            <div class="max-w-2xl mx-auto bg-white dark:bg-surface-dark rounded-xl shadow-xl overflow-hidden mb-12 border border-gray-200 dark:border-gray-700">
                <img src="assets/image/poster.jpg" alt="Event Poster" class="w-full h-auto object-cover">
                <div class="p-8">
                    <p class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white uppercase">
                        Any Web Application with Back End
                    </p>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Build innovative full-stack solutions powered by AI within 24 hours.</p>
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-6 mb-12" id="schedule">
                <div class="bg-white dark:bg-surface-dark p-6 rounded-lg shadow-md border border-gray-100 dark:border-gray-700 w-full sm:w-auto min-w-[200px]">
                    <span class="material-icons text-primary text-3xl mb-2">calendar_today</span>
                    <p class="text-xs font-bold text-gray-400 uppercase">Starts</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">07th Feb, 2026</p>
                    <p class="text-sm text-gray-500">10:00 AM</p>
                </div>
                <div class="hidden sm:flex items-center text-gray-300 dark:text-gray-600">
                    <span class="material-icons text-4xl">arrow_forward</span>
                </div>
                <div class="bg-white dark:bg-surface-dark p-6 rounded-lg shadow-md border border-gray-100 dark:border-gray-700 w-full sm:w-auto min-w-[200px]">
                    <span class="material-icons text-primary text-3xl mb-2">event</span>
                    <p class="text-xs font-bold text-gray-400 uppercase">Ends</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">08th Feb, 2026</p>
                    <p class="text-sm text-gray-500">10:00 AM</p>
                </div>
            </div>
            
        </div>
    </header>
    
    <section class="bg-primary py-8 text-white relative">
        <div class="absolute top-0 left-0 w-full h-2 bg-black/10"></div>
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-red-800">
                <div class="py-2">
                    <div class="flex items-center justify-center mb-2">
                        <span class="material-icons text-3xl opacity-80 mr-2">group</span>
                        <h3 class="text-xl font-bold uppercase">Team Size</h3>
                    </div>
                    <p class="text-3xl font-display font-bold">1 to 5 Members</p>
                </div>
                <div class="py-2">
                    <div class="flex items-center justify-center mb-2">
                        <span class="material-icons text-3xl opacity-80 mr-2">payments</span>
                        <h3 class="text-xl font-bold uppercase">Registration Fee</h3>
                    </div>
                    <div class="inline-block bg-white text-primary px-6 py-1 rounded-full text-3xl font-display font-bold shadow-lg transform rotate-[-2deg]">
                        ₹ 300/-
                    </div>
                </div>
                <div class="py-2">
                    <div class="flex items-center justify-center mb-2">
                        <span class="material-icons text-3xl opacity-80 mr-2">location_on</span>
                        <h3 class="text-xl font-bold uppercase">Venue</h3>
                    </div>
                    <p class="text-lg font-bold leading-tight">Laptop Lab, B-Block</p>
                    <p class="text-sm opacity-80">AITAM Campus</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-20 bg-white dark:bg-surface-dark" id="about">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2">
                    <div class="relative">
                        <div class="absolute -top-4 -left-4 w-24 h-24 bg-primary/10 rounded-full blur-xl"></div>
                        <img src="assets/image/AMC_2789.JPG" alt="CSD Department Team" class="rounded-xl shadow-2xl border-4 border-white dark:border-gray-700 relative z-10 w-full object-cover h-[400px]">
                        <div class="absolute -bottom-6 -right-6 bg-white dark:bg-surface-dark-lighter p-4 rounded-lg shadow-lg border border-gray-100 dark:border-gray-600 z-20 hidden md:block">
                            <p class="font-bold text-primary text-xl">Excellence</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">In Innovation</p>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2">
                    <span class="text-primary font-bold tracking-wider uppercase text-sm mb-2 block">Organized By</span>
                    <h2 class="text-4xl md:text-5xl font-display font-bold text-gray-900 dark:text-white mb-6">
                        Department of <span class="text-primary">CSD</span>
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        The Department of Computer Science and Design (CSD) at AITAM is dedicated to fostering innovation at the intersection of technology and creativity. This 24-Hour Webathon is our flagship event designed to challenge students to push the boundaries of web development using Artificial Intelligence.
                    </p>
                    <p class="text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                        We aim to provide a platform for aspiring developers to showcase their skills, collaborate with peers, and build real-world solutions. Join us in this journey of coding, designing, and innovating for the future.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="border-l-4 border-primary pl-4">
                            <h4 class="font-bold text-gray-900 dark:text-white text-lg">Innovation</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fostering creative solutions</p>
                        </div>
                        <div class="border-l-4 border-primary pl-4">
                            <h4 class="font-bold text-gray-900 dark:text-white text-lg">Technology</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Cutting-edge AI integration</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-20 bg-gray-50 dark:bg-surface-dark/50" id="prizes">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-display font-bold text-gray-900 dark:text-white mb-4 uppercase">
                Prizes & Rewards
            </h2>
            <div class="w-24 h-1 bg-primary mx-auto mb-12"></div>
            <div class="flex flex-col lg:flex-row justify-center items-center gap-8 max-w-5xl mx-auto">
                <div class="bg-gradient-to-br from-red-600 to-red-800 text-white p-8 rounded-2xl shadow-xl w-full lg:w-1/3 transform hover:scale-105 transition-transform duration-300 relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                    <h3 class="text-xl font-bold uppercase mb-2 opacity-90">Total Cash Prize Reward</h3>
                    <div class="text-6xl font-display font-bold mb-4">₹15k</div>
                    <p class="text-sm opacity-80">Plus certificates for all participants</p>
                </div>
                <div class="flex items-end justify-center gap-4 w-full lg:w-2/3 h-64">
                    <div class="w-1/3 h-2/3 bg-white dark:bg-surface-dark-lighter border-t-4 border-gray-400 rounded-t-lg shadow-lg flex flex-col justify-end p-4 relative group">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-gray-400 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-xl shadow-md">2</div>
                        <div class="text-center">
                            <span class="material-icons text-gray-400 text-4xl mb-2 group-hover:scale-110 transition-transform">emoji_events</span>
                            <p class="font-bold text-gray-600 dark:text-gray-300 uppercase text-sm">Runner Up</p>
                        </div>
                    </div>
                    <div class="w-1/3 h-full bg-white dark:bg-surface-dark-lighter border-t-4 border-yellow-500 rounded-t-lg shadow-xl flex flex-col justify-end p-4 relative z-10 group">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-yellow-500 text-white w-12 h-12 rounded-full flex items-center justify-center font-bold text-2xl shadow-md">1</div>
                        <div class="text-center pb-4">
                            <span class="material-icons text-yellow-500 text-6xl mb-2 group-hover:scale-110 transition-transform">emoji_events</span>
                            <p class="font-bold text-gray-800 dark:text-white uppercase text-lg">Winner</p>
                            <p class="text-primary font-bold">Top Prize</p>
                        </div>
                    </div>
                    <div class="w-1/3 h-1/2 bg-white dark:bg-surface-dark-lighter border-t-4 border-orange-700 rounded-t-lg shadow-lg flex flex-col justify-end p-4 relative group">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-orange-700 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-xl shadow-md">3</div>
                        <div class="text-center">
                            <span class="material-icons text-orange-700 text-4xl mb-2 group-hover:scale-110 transition-transform">emoji_events</span>
                            <p class="font-bold text-gray-600 dark:text-gray-300 uppercase text-sm">2nd Runner</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-20 bg-background-light dark:bg-background-dark relative" id="coordinators">
        <div class="container mx-auto px-4">
            <div class="bg-primary text-white text-center py-3 mb-12 max-w-xs mx-auto rounded font-display font-bold text-xl uppercase shadow-lg">
                Co-Ordinators
            </div>
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-white dark:bg-surface-dark p-6 rounded-xl shadow-md border-l-4 border-primary flex items-center space-x-4 hover:shadow-xl transition-shadow">
                    <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-full">
                        <span class="material-icons text-primary text-3xl">person</span>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-primary uppercase">Mr. M. D. R. Siva Santhosh</h4>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Assistant Professor</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Department of CSD</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-surface-dark p-6 rounded-xl shadow-md border-l-4 border-primary flex items-center space-x-4 hover:shadow-xl transition-shadow">
                    <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-full">
                        <span class="material-icons text-primary text-3xl">person</span>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-primary uppercase">Mr. Kamalkiran Sumala</h4>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Assistant Professor</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Department of CSD</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-16 bg-white dark:bg-surface-dark border-t border-gray-200 dark:border-gray-700" id="register">
        <div class="container mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-12 max-w-6xl">
            <div class="md:w-1/2">
                <h2 class="text-4xl font-display font-bold text-gray-900 dark:text-white mb-4">Ready to Innovate?</h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    Gather your team of 1-5 members and register now for the 24 Hour Webathon. Challenge yourself and win exciting prizes.
                </p>
                <div class="space-y-4">
                    <div class="flex items-center space-x-3 text-gray-700 dark:text-gray-300">
                        <span class="material-icons text-primary">check_circle</span>
                        <span>Official ISTE Certificate</span>
                    </div>
                    <div class="flex items-center space-x-3 text-gray-700 dark:text-gray-300">
                        <span class="material-icons text-primary">check_circle</span>
                        <span>Networking Opportunities</span>
                    </div>
                    <div class="flex items-center space-x-3 text-gray-700 dark:text-gray-300">
                        <span class="material-icons text-primary">check_circle</span>
                        <span>Industry Recognition</span>
                    </div>
                </div>
            </div>
            <div class="md:w-auto flex flex-col items-center">
                <div class="bg-white p-4 rounded-xl shadow-2xl border-2 border-primary mb-4">
                    <img alt="Register QR Code" class="w-48 h-48" src="assets/image/qrcode.png"/>
                </div>
                <a href="register.php" class="bg-primary hover:bg-red-800 text-white font-bold py-3 px-10 rounded-lg shadow-lg uppercase tracking-wider transition-all transform hover:scale-105">
                    Register Now
                </a> 
            </div>
        </div>
    </section>
    
    <footer class="bg-secondary text-white pt-12 pb-6">
        <div class="container  mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-12">
                <div class="text-center mx-auto md:text-left">
                    <h5 class="font-display text-xl font-bold mb-4 border-b border-gray-600 inline-block pb-1">Contact Us</h5>
                    <div class="space-y-2 text-gray-300">
                        <div class="flex items-center justify-center md:justify-start">
                            <span class="material-icons text-sm mr-2">phone</span>
                            <span>92466 57908</span>
                        </div>
                        <div class="flex items-center justify-center md:justify-start">
                            <span class="material-icons text-sm mr-2">phone</span>
                            <span>92466 57913</span>
                        </div>
                        <div class="flex items-center justify-center md:justify-start">
                            <span class="material-icons text-sm mr-2">phone</span>
                            <span>84091 66041 (Nepal)</span>
                        </div>
                    </div>
                </div>
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

            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        }
    </script>
</body>
</html>
