<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Gateway | JRMSU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { 
                        jrmsuNavy: '#002855',
                        jrmsuNavyDark: '#001a38',
                        jrmsuGold: '#EAA221',
                        jrmsuGoldLight: '#facc15'
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .login-bg {
            background: radial-gradient(circle at top left, #002855 0%, #001a38 100%);
        }
        @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .loading-spinner { animation: spin-slow 1s linear infinite; }
        
        .portal-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0; /* Started at 0 for animation */
        }
        .portal-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 25px -5px rgba(234, 162, 33, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Animation Delays */
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.3s; }
        .delay-300 { animation-delay: 0.5s; }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-6 antialiased font-sans overflow-x-hidden">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-jrmsuGold/10 rounded-full blur-3xl animate-float"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
    </div>

    <div class="w-full max-w-5xl relative z-10">
        <div class="text-center mb-12 animate-fade-in-up">
            <div class="relative inline-block mb-6">
                <div class="absolute -inset-1 bg-jrmsuGold rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                <div class="relative w-32 h-32 bg-white p-1 rounded-full shadow-2xl flex items-center justify-center overflow-hidden border-2 border-jrmsuGold/20">
                    <img src="OIP.jpg" alt="JRMSU Logo" class="w-full h-full object-contain">
                </div>
            </div>
            <h1 class="text-white text-4xl md:text-5xl font-black tracking-tight mb-2">
                JRMSU <span class="text-jrmsuGold">PORTAL</span>
            </h1>
            <p class="text-white/60 text-lg font-medium tracking-wide">College of Computing Studies Gateway</p>
            <div class="h-1 w-20 bg-jrmsuGold mx-auto mt-4 rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <div class="portal-card animate-fade-in-up delay-100 bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/10 flex flex-col">
                <div class="bg-jrmsuNavy p-8 text-center border-b-4 border-jrmsuGold">
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-graduate text-jrmsuGold text-3xl"></i>
                    </div>
                    <h2 class="text-white font-bold text-sm uppercase tracking-widest">Student Hub</h2>
                </div>
                <div class="p-8 flex-grow flex flex-col justify-between">
                    <p class="text-slate-500 text-sm text-center mb-8 leading-relaxed">
                        Access your grades, enrollment status, and personalized student modules.
                    </p>
                    <button onclick="navigateTo('login.php', this)" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-xs font-black rounded-2xl text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight shadow-lg transition-all duration-300 overflow-hidden">
                        <span class="btnText flex items-center gap-2 relative z-10">
                            ENTER DASHBOARD <i class="fas fa-chevron-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span class="btnLoader hidden flex items-center gap-2">
                            <i class="fas fa-circle-notch loading-spinner text-lg"></i>
                        </span>
                    </button>
                </div>
            </div>

            <div class="portal-card animate-fade-in-up delay-200 bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/10 flex flex-col">
                <div class="bg-jrmsuNavy p-8 text-center border-b-4 border-jrmsuGold">
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chalkboard-teacher text-jrmsuGold text-3xl"></i>
                    </div>
                    <h2 class="text-white font-bold text-sm uppercase tracking-widest">Faculty Access</h2>
                </div>
                <div class="p-8 flex-grow flex flex-col justify-between">
                    <p class="text-slate-500 text-sm text-center mb-8 leading-relaxed">
                        Manage class schedules, student grading, and faculty academic resources.
                    </p>
                    <button onclick="navigateTo('instructor_login.php', this)" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-xs font-black rounded-2xl text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight shadow-lg transition-all duration-300">
                        <span class="btnText flex items-center gap-2">
                            FACULTY LOGIN <i class="fas fa-chevron-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span class="btnLoader hidden flex items-center gap-2">
                            <i class="fas fa-circle-notch loading-spinner text-lg"></i>
                        </span>
                    </button>
                </div>
            </div>

            <div class="portal-card animate-fade-in-up delay-300 bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/10 flex flex-col">
                <div class="bg-jrmsuNavy p-8 text-center border-b-4 border-jrmsuGold">
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-halved text-jrmsuGold text-3xl"></i>
                    </div>
                    <h2 class="text-white font-bold text-sm uppercase tracking-widest">System Admin</h2>
                </div>
                <div class="p-8 flex-grow flex flex-col justify-between">
                    <p class="text-slate-500 text-sm text-center mb-8 leading-relaxed">
                        Full system management, user control, and secure database maintenance.
                    </p>
                    <button onclick="navigateTo('admin_login.php', this)" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-xs font-black rounded-2xl text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight shadow-lg transition-all duration-300">
                        <span class="btnText flex items-center gap-2">
                            ADMIN CONSOLE <i class="fas fa-chevron-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span class="btnLoader hidden flex items-center gap-2">
                            <i class="fas fa-circle-notch loading-spinner text-lg"></i>
                        </span>
                    </button>
                </div>
            </div>

        </div>

        <div class="text-center mt-16 space-y-3 animate-fade-in-up" style="animation-delay: 0.8s;">
            <p class="text-white/40 text-[11px] font-semibold uppercase tracking-[0.3em]">
                &copy; 2026 Jose Rizal Memorial State University
            </p>
            <div class="flex items-center justify-center gap-4">
                <span class="h-px w-8 bg-white/10"></span>
                <p class="text-jrmsuGold/60 text-[10px] uppercase tracking-widest font-bold">
                    Siocon Campus • CCS LMS
                </p>
                <span class="h-px w-8 bg-white/10"></span>
            </div>
        </div>
    </div>

    <script>
        function navigateTo(url, btn) {
            const btnText = btn.querySelector('.btnText');
            const btnLoader = btn.querySelector('.btnLoader');

            document.querySelectorAll('button').forEach(b => {
                b.disabled = true;
                b.classList.add('opacity-80', 'cursor-not-allowed');
            });
            
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');
            
            setTimeout(() => {
                window.location.href = url;
            }, 800);
        }
    </script>
</body>
</html>
