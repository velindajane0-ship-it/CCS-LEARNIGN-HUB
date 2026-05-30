<?php
/**
 * Project: CCS HUB - Student Access
 * Developed by: MANILYN A. JAMALUL
 */
session_start();
include 'db_connect.php';

$msg = "";
$msg_type = "";
$active_tab = "login"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $sid = $_POST['student_id'];
        $pass = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $sid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            if ($student['password'] == NULL) {
                $msg = "Account not activated. Use the 'Activate' tab.";
                $msg_type = "warning";
            } elseif (password_verify($pass, $student['password'])) {
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['full_name'];
                header("Location: student.php");
                exit();
            } else {
                $msg = "Incorrect password.";
                $msg_type = "danger";
            }
        } else {
            $msg = "Student ID not found.";
            $msg_type = "danger";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $active_tab = "register"; 
        $sid = $_POST['reg_student_id'];
        $pass = $_POST['reg_password'];
        $confirm = $_POST['reg_confirm'];

        if ($pass !== $confirm) {
            $msg = "Passwords do not match.";
            $msg_type = "danger";
        } else {
            $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->bind_param("s", $sid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $student = $result->fetch_assoc();
                if ($student['password'] != NULL) {
                    $msg = "Already activated. Please login.";
                    $msg_type = "info";
                    $active_tab = "login";
                } else {
                    $hashed = password_hash($pass, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                    $update->bind_param("ss", $hashed, $sid);
                    if ($update->execute()) {
                        $msg = "Activated! You may now login.";
                        $msg_type = "success";
                        $active_tab = "login";
                    }
                }
            } else {
                $msg = "Student ID not found.";
                $msg_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Access | JRMSU Portal</title>
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
        
        /* Tab transitions */
        .tab-content { display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .tab-content.active { display: block; opacity: 1; }
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
                <i class="fas fa-user-graduate text-jrmsuNavy text-4xl"></i>
            </div>
            <h1 class="text-white text-2xl font-bold tracking-tight">JRMSU <span class="text-jrmsuGold">Student</span></h1>
            <p class="text-white/60 text-sm mt-1">Quality Education for a Better World</p>
        </div>

        <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-white/10">
            <div class="bg-jrmsuNavy p-0 text-center border-b-4 border-jrmsuGold flex">
                <button onclick="switchTab('login')" id="tab-login" class="flex-1 py-4 text-sm font-bold tracking-widest uppercase transition-colors <?php echo ($active_tab == 'login') ? 'bg-white/10 text-jrmsuGold' : 'text-white/60 hover:text-white hover:bg-white/5'; ?>">
                    <i class="fas fa-lock mr-2"></i>Sign In
                </button>
                <button onclick="switchTab('register')" id="tab-register" class="flex-1 py-4 text-sm font-bold tracking-widest uppercase transition-colors <?php echo ($active_tab == 'register') ? 'bg-white/10 text-jrmsuGold' : 'text-white/60 hover:text-white hover:bg-white/5'; ?>">
                    <i class="fas fa-key mr-2"></i>Activate
                </button>
            </div>

            <div class="p-8 sm:p-10">
                <?php if($msg != ""): 
                    // Determine Tailwind classes based on Bootstrap msg_type
                    $alert_bg = 'bg-blue-50'; $alert_text = 'text-blue-700'; $alert_border = 'border-blue-100'; $alert_icon = 'fa-info-circle';
                    if($msg_type == 'danger') { $alert_bg = 'bg-red-50'; $alert_text = 'text-red-700'; $alert_border = 'border-red-100'; $alert_icon = 'fa-circle-exclamation'; }
                    elseif($msg_type == 'success') { $alert_bg = 'bg-green-50'; $alert_text = 'text-green-700'; $alert_border = 'border-green-100'; $alert_icon = 'fa-circle-check'; }
                    elseif($msg_type == 'warning') { $alert_bg = 'bg-yellow-50'; $alert_text = 'text-yellow-700'; $alert_border = 'border-yellow-100'; $alert_icon = 'fa-triangle-exclamation'; }
                ?>
                <div class="mb-6 flex items-center p-4 rounded-xl text-sm <?php echo "$alert_bg $alert_text $alert_border"; ?> border">
                    <i class="fas <?php echo $alert_icon; ?> mr-3 text-lg"></i>
                    <span class="font-medium"><?php echo $msg; ?></span>
                </div>
                <?php endif; ?>

                <div id="content-login" class="tab-content <?php echo ($active_tab == 'login') ? 'active' : ''; ?>">
                    <form id="loginForm" method="POST" class="space-y-6 auth-form">
                        <input type="hidden" name="action" value="login">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Student ID</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-id-card text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                                </div>
                                <input type="text" name="student_id" required 
                                    class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm"
                                    placeholder="2024-XXXX">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Password</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                                </div>
                                <input type="password" name="password" required 
                                    class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm"
                                    placeholder="••••••••">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" 
                                class="submitBtn group relative w-full flex justify-center py-4 px-4 border border-transparent text-sm font-black rounded-2xl text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-jrmsuGold shadow-lg transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0">
                                <span class="btnText flex items-center gap-2">
                                    SIGN IN TO DASHBOARD <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                                </span>
                                <span class="btnLoader hidden flex items-center gap-2">
                                    <i class="fas fa-circle-notch loading-spinner text-lg"></i> AUTHENTICATING...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <div id="content-register" class="tab-content <?php echo ($active_tab == 'register') ? 'active' : ''; ?>">
                    <form id="regForm" method="POST" class="space-y-5 auth-form">
                        <input type="hidden" name="action" value="register">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Student ID</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-id-card text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                                </div>
                                <input type="text" name="reg_student_id" required 
                                    class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm"
                                    placeholder="Enter given ID">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Create Password</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                                </div>
                                <input type="password" name="reg_password" required 
                                    class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Confirm Password</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-check-double text-slate-400 group-focus-within:text-jrmsuGold transition-colors"></i>
                                </div>
                                <input type="password" name="reg_confirm" required 
                                    class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-jrmsuGold focus:border-transparent focus:bg-white transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" 
                                class="submitBtn group relative w-full flex justify-center py-4 px-4 border border-transparent text-sm font-black rounded-2xl text-white bg-jrmsuNavy hover:bg-jrmsuNavyDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-jrmsuNavy shadow-lg transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0">
                                <span class="btnText flex items-center gap-2">
                                    ACTIVATE ACCOUNT <i class="fas fa-user-check text-xs group-hover:scale-110 transition-transform"></i>
                                </span>
                                <span class="btnLoader hidden flex items-center gap-2">
                                    <i class="fas fa-circle-notch loading-spinner text-lg"></i> PROCESSING...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="text-center mt-6">
                    <a href="index.php" class="text-slate-400 hover:text-jrmsuNavy text-xs font-bold uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-chevron-left"></i> Return to Campus Gateway
                    </a>
                </div>

            </div>
        </div>

        <p class="text-center mt-8 text-white/40 text-[10px] font-medium uppercase tracking-[0.2em]">
            &copy; 2026 JRMSU Student Access Portal
        </p>
    </div>

    <script>
        // Custom Tab Switcher
        function switchTab(tab) {
            // Update buttons
            document.getElementById('tab-login').className = tab === 'login' 
                ? 'flex-1 py-4 text-sm font-bold tracking-widest uppercase transition-colors bg-white/10 text-jrmsuGold' 
                : 'flex-1 py-4 text-sm font-bold tracking-widest uppercase transition-colors text-white/60 hover:text-white hover:bg-white/5';
                
            document.getElementById('tab-register').className = tab === 'register' 
                ? 'flex-1 py-4 text-sm font-bold tracking-widest uppercase transition-colors bg-white/10 text-jrmsuGold' 
                : 'flex-1 py-4 text-sm font-bold tracking-widest uppercase transition-colors text-white/60 hover:text-white hover:bg-white/5';

            // Update content visibility
            document.getElementById('content-login').classList.remove('active');
            document.getElementById('content-register').classList.remove('active');
            
            // Add slight delay for fade effect
            setTimeout(() => {
                document.getElementById('content-' + tab).classList.add('active');
            }, 50);
        }

        // Button Loading Animation Logic for both forms
        document.querySelectorAll('.auth-form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('.submitBtn');
                const btnText = this.querySelector('.btnText');
                const btnLoader = this.querySelector('.btnLoader');

                // Disable button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
                
                // Toggle text and loader
                btnText.classList.add('hidden');
                btnLoader.classList.remove('hidden');
            });
        });
    </script>
</body>
</html>
