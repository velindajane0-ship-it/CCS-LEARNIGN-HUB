<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Prepare statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT id, name, password FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 2. Verify the hashed password from DB against the plain text input
        if (password_verify($password, $user['password'])) {
            // Password is correct!
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            header("Location: admin_dashboard.php"); 
            exit();
        } else {
            $msg = "Invalid password.";
            $msg_type = "danger";
        }
    } else {
        $msg = "No account found with that email.";
        $msg_type = "danger";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS LEARN HUB | Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="tailwind-offline.js"></script>
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
        
        /* Custom Checkbox Color */
        .custom-checkbox:checked {
            background-color: var(--jrmsu-gold);
            border-color: var(--jrmsu-gold);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-6 antialiased font-sans">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-jrmsuGold/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-jrmsuGold rounded-3xl shadow-2xl mb-4 transform -rotate-6 transition-transform hover:rotate-0 duration-300">
                <i class="fas fa-layer-group text-jrmsuNavy text-4xl"></i>
            </div>
            <h1 class="text-white text-2xl font-bold tracking-tight">CCS <span class="text-jrmsuGold">LEARN HUB</span></h1>
            <p class="text-white/60 text-sm mt-1">Content Management System</p>
        </div>

        <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-white/10">
            <div class="bg-jrmsuNavy p-6 text-center border-b-4 border-jrmsuGold">
                <h2 class="text-white font-bold text-lg uppercase tracking-widest">Administrator Access</h2>
            </div>

            <div class="p-8 sm:p-10">
                <?php if(isset($msg)): ?>
                <div class="mb-6 flex items-center p-4 rounded-xl text-sm <?php echo $msg_type == 'danger' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100'; ?>">
                    <i class="fas <?php echo $msg_type == 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?> mr-3 text-lg"></i>
                    <span class="font-medium"><?php echo $msg; ?></span>
                </div>
                <?php endif; ?>

                <form id="loginForm" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="login">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Email Address</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                            </div>
                            <input type="email" name="email" required 
                                class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm"
                                placeholder="name@university.edu">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2 px-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Password</label>
                            <a href="#" class="text-[10px] font-bold text-jrmsuNavy hover:text-jrmsuGold transition-colors uppercase tracking-tighter">Forgot?</a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                            </div>
                            <input type="password" name="password" required 
                                class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center px-1">
                        <input id="rememberMe" name="remember" type="checkbox" class="h-4 w-4 text-jrmsuNavy focus:ring-jrmsuGold border-slate-300 rounded custom-checkbox transition-colors cursor-pointer">
                        <label for="rememberMe" class="ml-2 block text-xs font-bold text-slate-500 cursor-pointer">
                            Keep me logged in
                        </label>
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="submitBtn" 
                            class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-sm font-black rounded-2xl text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-jrmsuGold shadow-lg transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0">
                            <span id="btnText" class="flex items-center gap-2">
                                SIGN IN TO CMS <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                            </span>
                            <span id="btnLoader" class="hidden flex items-center gap-2">
                                <i class="fas fa-circle-notch loading-spinner text-lg"></i> AUTHENTICATING...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <p class="text-center mt-8 text-white/40 text-[10px] font-medium uppercase tracking-[0.2em]">
            &copy; 2026 College of Computer Studies. All rights reserved.
        </p>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoader = document.getElementById('btnLoader');

        loginForm.addEventListener('submit', function() {
            // Disable button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
            
            // Toggle text and loader
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');
            
            // The form will then submit naturally to the PHP backend
        });
    </script>
</body>
</html>
