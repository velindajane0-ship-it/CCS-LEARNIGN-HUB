<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$sid = $_SESSION['student_id'];
$s_name = $_SESSION['student_name'];
$active_tab = 'dashboard'; // Default tab

// --- 1. HANDLE ACTIONS (Backend Untouched) ---

// Handle Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload_photo') {
    $active_tab = 'profile';
    $target_dir = "uploads/profiles/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_name = $sid . "_" . basename($_FILES["profile_pic"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
        $conn->query("UPDATE students SET profile_pic = '$target_file' WHERE student_id = '$sid'");
        $msg = "Profile photo updated!";
        $msg_type = "success";
    }
}

// Handle Quiz Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submit_quiz') {
    $active_tab = 'modules';
    $mod_id = $_POST['module_id'];
    $score = 0;
    $total = 0;

    $questions = $conn->query("SELECT * FROM quiz_questions WHERE module_id = $mod_id");
    while($q = $questions->fetch_assoc()) {
        $total++;
        if (isset($_POST['q_' . $q['id']]) && $_POST['q_' . $q['id']] == $q['correct_answer']) {
            $score++;
        }
    }

    $stmt = $conn->prepare("INSERT INTO quiz_results (student_id, module_id, score, total_items) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siii", $sid, $mod_id, $score, $total);
    $stmt->execute();

    $msg = "Quiz Submitted! You scored $score / $total";
    $msg_type = ($score >= ($total / 2)) ? "success" : "warning";
}

// --- 2. FETCH STUDENT DATA ---
$student_info = $conn->query("SELECT * FROM students WHERE student_id = '$sid'")->fetch_assoc();
$profile_img = $student_info['profile_pic'] ? $student_info['profile_pic'] : "https://ui-avatars.com/api/?name=".urlencode($s_name)."&background=002855&color=EAA221&bold=true";
$modules_result = $conn->query("SELECT * FROM modules");

// Fetch Announcements
// Note: Assumes an 'announcements' table exists with 'title', 'content', and 'created_at' columns.
$announcements_result = $conn->query("SELECT * FROM announcements WHERE posted_by IN ('admin', 'instructor') ORDER BY created_at DESC");
$announcement_count = $announcements_result ? $announcements_result->num_rows : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | JRMSU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="tailwind-offline.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

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
        /* Tab Transitions */
        .tab-content { display: none; opacity: 0; }
        .tab-content.active { display: block; opacity: 1; animation: slideUpFade 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes slideUpFade { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Quiz Wizard Animations & Cards */
        .quiz-step { display: none; animation: slideUpFade 0.4s ease-out; }
        .quiz-step.active { display: block; }
        
        .option-card { border: 2px solid #e2e8f0; transition: all 0.2s ease; cursor: pointer; }
        .option-card:hover { border-color: #cbd5e1; background-color: #f8fafc; }
        .option-input:checked + .option-card {
            border-color: #EAA221;
            background-color: #fffaf0;
            box-shadow: 0 4px 12px rgba(234, 162, 33, 0.15);
        }
        .option-input:checked + .option-card .circle-marker {
            background-color: #EAA221;
            border-color: #EAA221;
        }
        .option-input:checked + .option-card .circle-marker::after { 
            content: "\f00c"; 
            font-family: "Font Awesome 6 Free"; 
            font-weight: 900; 
            color: white; 
            font-size: 10px; 
        }

        /* Certificate Styling */
        .certificate-frame {
            border: 8px double #EAA221;
            background: #fff;
            position: relative;
        }
        .cert-seal { position: absolute; bottom: 20px; right: 20px; color: #EAA221; opacity: 0.15; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-gray-800 font-sans antialiased">

<?php if(isset($msg)): ?>
<div id="alertBox" class="fixed top-6 right-6 z-50 flex items-center w-full max-w-sm p-4 text-gray-800 bg-white rounded-xl shadow-2xl border-l-4 <?php echo $msg_type == 'success' ? 'border-green-500' : 'border-amber-500'; ?> animate-[slideUpFade_0.3s_ease-out]" role="alert">
    <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 <?php echo $msg_type == 'success' ? 'text-green-600 bg-green-100' : 'text-amber-600 bg-amber-100'; ?> rounded-full">
        <i class="fas <?php echo $msg_type == 'success' ? 'fa-check' : 'fa-info-circle'; ?> text-lg"></i>
    </div>
    <div class="ms-3 text-sm font-medium flex-1"><?php echo $msg; ?></div>
    <button type="button" onclick="document.getElementById('alertBox').style.display='none'" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 transition-colors">
        <span class="sr-only">Close</span>
        <i class="fas fa-times"></i>
    </button>
</div>
<?php endif; ?>

<div id="sidebar" class="bg-jrmsuNavy w-72 flex-shrink-0 h-full flex flex-col transition-transform duration-300 ease-in-out z-40 fixed md:relative -translate-x-full md:translate-x-0 shadow-2xl border-r border-jrmsuNavyDark">
    
    <div class="p-6 flex items-center justify-between border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-jrmsuGold rounded-lg flex items-center justify-center shadow-lg">
                <i class="fas fa-graduation-cap text-jrmsuNavy text-xl"></i>
            </div>
            <div>
                <h2 class="text-white font-bold text-lg leading-tight tracking-wide">JRMSU</h2>
                <p class="text-jrmsuGold text-xs font-medium uppercase tracking-widest">Student Portal</p>
            </div>
        </div>
        <button id="closeSidebar" class="md:hidden text-white/70 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-6">
        <nav class="space-y-2 px-4">
            <button data-target="dashboard" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-th-large w-5 text-center text-lg"></i> 
                <span>Dashboard</span>
            </button>
            <button data-target="modules" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-book-open w-5 text-center text-lg"></i> 
                <span>Learning Modules</span>
            </button>
            <button data-target="certificates" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-award w-5 text-center text-lg"></i> 
                <span>My Certificates</span>
            </button>
            
            <button data-target="announcements" class="nav-btn w-full flex items-center justify-between px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <div class="flex items-center gap-4">
                    <i class="fas fa-bullhorn w-5 text-center text-lg"></i> 
                    <span>Announcements</span>
                </div>
                <?php if ($announcement_count > 0): ?>
                <span id="announcement-badge" class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                    <?php echo $announcement_count; ?>
                </span>
                <?php endif; ?>
            </button>

            <button data-target="profile" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-user-circle w-5 text-center text-lg"></i> 
                <span>Student Profile</span>
            </button>
        </nav>
    </div>

    <div class="p-4 border-t border-white/10">
        <a href="login.php" class="flex items-center justify-center gap-3 w-full px-4 py-3 text-sm font-medium rounded-xl text-red-300 bg-red-500/10 hover:bg-red-500/20 hover:text-red-200 transition-colors border border-red-500/20">
            <i class="fas fa-sign-out-alt"></i> Secure Logout
        </a>
    </div>
</div>

<div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative bg-slate-50">
    
    <div id="mobileOverlay" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm z-30 hidden md:hidden transition-opacity"></div>

    <header class="bg-white shadow-sm border-b border-slate-200 h-20 flex items-center justify-between px-6 lg:px-10 z-10 shrink-0">
        <div class="flex items-center gap-4">
            <button id="openSidebar" class="md:hidden text-jrmsuNavy hover:text-jrmsuGold transition-colors focus:outline-none bg-slate-100 p-2 rounded-lg">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 id="pageTitle" class="text-2xl font-bold text-jrmsuNavy hidden sm:block tracking-tight">Student Dashboard</h1>
        </div>
        <div class="flex items-center gap-5">
            <div class="text-right hidden sm:block">
                <div class="text-sm font-bold text-jrmsuNavy"><?php echo $s_name; ?></div>
                <div class="text-xs text-slate-500 font-medium uppercase tracking-wider">ID: <?php echo $sid; ?></div>
            </div>
            <div class="relative">
                <img src="<?php echo $profile_img; ?>" alt="Profile" class="w-11 h-11 rounded-full shadow-md ring-2 ring-jrmsuGold ring-offset-2 object-cover">
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
        
        <div id="dashboard" class="tab-content max-w-7xl mx-auto space-y-8">
            <div class="bg-jrmsuNavy rounded-3xl p-8 sm:p-10 shadow-xl relative overflow-hidden border-b-4 border-jrmsuGold">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-20 w-40 h-40 bg-jrmsuGold opacity-10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-jrmsuGold text-xs font-semibold tracking-wider mb-3 backdrop-blur-sm border border-white/10">STUDENT PORTAL</span>
                        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Welcome, <span class="text-jrmsuGold"><?php echo explode(" ", $s_name)[0]; ?></span>!</h2>
                        <p class="text-white/80 text-sm sm:text-base max-w-xl leading-relaxed">Your learning journey at JRMSU continues here. Access your modules, take quizzes, and earn your certifications.</p>
                    </div>
                    <div class="hidden md:flex items-center justify-center">
                        <i class="fas fa-laptop-code text-8xl text-white/10 transform rotate-12"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php 
                $total_mods = $modules_result->num_rows;
                $comp_mods = $conn->query("SELECT id FROM quiz_results WHERE student_id='$sid'")->num_rows;
                
                // ADDED FUNCTIONALITY TO FORCE COUNT TO 12 IF 0
                if ($comp_mods == 0) {
                    $comp_mods = 0;
                }
                ?>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-6 hover:shadow-md transition-shadow group">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-jrmsuNavy flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform duration-300 group-hover:bg-jrmsuNavy group-hover:text-jrmsuGold">
                        <i class="fas fa-book"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Available Modules</p>
                        <h3 class="text-4xl font-bold text-jrmsuNavy mt-1"><?php echo $total_mods; ?></h3>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-6 hover:shadow-md transition-shadow group">
                    <div class="w-16 h-16 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform duration-300 group-hover:bg-green-600 group-hover:text-white">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Modules Completed</p>
                        <h3 class="text-4xl font-bold text-jrmsuNavy mt-1"><?php echo $comp_mods; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div id="modules" class="tab-content max-w-7xl mx-auto">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-jrmsuNavy">Course Syllabus</h2>
                <p class="text-sm text-slate-500 mt-1">Master each topic to earn your digital certificate.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php 
                $modules_result->data_seek(0);
                while($mod = $modules_result->fetch_assoc()): 
                    $check_taken = $conn->query("SELECT * FROM quiz_results WHERE student_id='$sid' AND module_id=".$mod['id']);
                $is_taken = $check_taken->num_rows > 0;
                $score_data = $is_taken ? $check_taken->fetch_assoc() : null;
            ?>
            <div class="h-full w-full"> 
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col h-full group">
                    <div class="h-2 w-full <?php echo $is_taken ? 'bg-green-500' : 'bg-jrmsuGold'; ?>"></div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-jrmsuNavy group-hover:bg-jrmsuNavy group-hover:text-jrmsuGold transition-colors">
                                <i class="fas fa-folder-open text-xl"></i>
                            </div>
                            <?php if($is_taken): ?>
                                <span class="bg-green-50 text-green-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-green-200 flex items-center gap-1 uppercase tracking-wider">
                                    <i class="fas fa-check"></i> Completed
                                </span>
                            <?php else: ?>
                                <span class="bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1 uppercase tracking-wider">
                                    <i class="fas fa-clock"></i> Active
                                </span>
                            <?php endif; ?>
                        </div>
                        <h5 class="text-lg font-bold text-slate-800 mb-2 line-clamp-1 group-hover:text-jrmsuNavy transition-colors"><?php echo htmlspecialchars($mod['title']); ?></h5>
                        <p class="text-sm text-slate-500 flex-1 line-clamp-2 leading-relaxed mb-6"><?php echo htmlspecialchars($mod['description']); ?></p>
                        
                        <?php if($mod['file_path']): ?>
                            <a href="<?php echo htmlspecialchars($mod['file_path']); ?>" target="_blank" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 hover:border-slate-300 transition-all no-underline">
                                <i class="fas fa-file-pdf text-red-500"></i> Read Material
                            </a>
                            <a href="<?php echo htmlspecialchars($mod['file_path']); ?>" download class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 hover:border-slate-300 transition-all no-underline">
                                <i class="fas fa-download text-jrmsuGold"></i> Download Module
                            </a>
                        <?php endif; ?>

                            <?php if($is_taken): ?>
                                <div class="bg-green-50 rounded-xl p-3 text-center border border-green-100">
                                    <p class="text-[10px] uppercase font-bold text-green-600 mb-0.5 tracking-widest">Final Score</p>
                                    <p class="text-lg font-black text-green-700"><?php echo $score_data['score']."/".$score_data['total_items']; ?></p>
                                </div>
                            <?php else: ?>
                                <button type="button" onclick="openAndInitQuiz('quizModal_<?php echo $mod['id']; ?>')" class="w-full py-3 rounded-xl bg-jrmsuNavy text-white font-bold text-sm hover:bg-jrmsuNavyDark transition-all shadow-md flex justify-center items-center gap-2 transform hover:-translate-y-0.5">
                                    Start Assessment <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="quizModal_<?php echo $mod['id']; ?>" class="fixed inset-0 z-[60] hidden">
                    <div class="fixed inset-0 bg-jrmsuNavyDark/80 backdrop-blur-sm transition-opacity"></div>
                    
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
                        <div class="relative bg-slate-50 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-4xl w-full pointer-events-auto flex flex-col max-h-[90vh]">
                            
                            <div class="bg-jrmsuNavy px-8 py-6 flex justify-between items-center shrink-0">
                                <div>
                                    <h5 class="text-jrmsuGold font-bold uppercase tracking-widest text-xs mb-1">Learning Module Assessment</h5>
                                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($mod['title']); ?></p>
                                </div>
                                <button onclick="closeModal('quizModal_<?php echo $mod['id']; ?>')" class="text-white/50 hover:text-white bg-white/5 hover:bg-white/10 rounded-full w-10 h-10 flex items-center justify-center transition-colors focus:outline-none">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="p-6 sm:p-8 overflow-y-auto flex-1">
                                <form method="POST" action="student.php" id="form_<?php echo $mod['id']; ?>">
                                    <input type="hidden" name="action" value="submit_quiz">
                                    <input type="hidden" name="module_id" value="<?php echo $mod['id']; ?>">
                                    
                                    <?php
                                    $q_sql = $conn->query("SELECT * FROM quiz_questions WHERE module_id = ".$mod['id']);
                                    $total_qs = $q_sql->num_rows;
                                    ?>

                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Progress</span>
                                        <span class="text-xs font-bold text-jrmsuNavy uppercase step-count">1 of <?php echo $total_qs; ?></span>
                                    </div>
                                    <div class="h-2 w-full bg-slate-200 rounded-full mb-8 overflow-hidden shadow-inner">
                                        <div class="h-full bg-jrmsuGold transition-all duration-500 progress-bar-custom" style="width: 0%"></div>
                                    </div>

                                    <?php $count = 1; if($total_qs > 0): while($ques = $q_sql->fetch_assoc()): ?>
                                    <div class="quiz-step" data-step="<?php echo $count; ?>">
                                        
                                        <?php 
                                        $has_text = !empty($ques['content_text']);
                                        $has_image = !empty($ques['content_image']);
                                        if($has_text || $has_image): 
                                            $layout = $ques['content_layout'] ?? 'standard';
                                        ?>
                                        <div class="mb-8 relative bg-white rounded-2xl border border-slate-200 shadow-sm transition-all overflow-hidden group">
                                            <div class="absolute top-0 left-0 w-1.5 h-full bg-jrmsuGold z-10"></div>
                                            
                                            <?php if($has_image && $layout == 'hero'): ?>
                                                <div class="w-full h-48 sm:h-64 lg:h-72 overflow-hidden relative bg-slate-100 border-b border-slate-100">
                                                    <img src="<?php echo htmlspecialchars($ques['content_image']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Lesson Hero Image">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-jrmsuNavyDark/40 via-transparent to-transparent"></div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="p-6 sm:p-8 relative">
                                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                                    <div class="w-10 h-10 rounded-full bg-amber-50 text-jrmsuGold flex items-center justify-center shadow-inner">
                                                        <i class="fas fa-book-reader text-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Reading Material</h4>
                                                        <p class="text-sm font-bold text-jrmsuNavy">Lesson Context</p>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col <?php 
                                                    if($layout == 'image_left') echo 'md:flex-row';
                                                    elseif($layout == 'image_right') echo 'md:flex-row-reverse';
                                                ?> gap-6 md:gap-8 items-start">
                                                    
                                                    <?php if($has_image && $layout != 'hero'): ?>
                                                        <div class="w-full <?php echo ($layout == 'standard') ? 'mb-2' : 'md:w-5/12 lg:w-1/2'; ?> rounded-xl overflow-hidden shadow-sm border border-slate-200 bg-slate-50 relative">
                                                            <img src="<?php echo htmlspecialchars($ques['content_image']); ?>" class="w-full h-auto object-cover max-h-96 mx-auto hover:opacity-95 transition-opacity" alt="Lesson Content Image">
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if($has_text): ?>
                                                        <div class="w-full flex-1">
                                                            <div class="text-slate-700 leading-relaxed text-[15px] sm:text-base whitespace-pre-wrap font-medium font-sans"><?php echo htmlspecialchars($ques['content_text']); ?></div>
                                                        </div>
                                                    <?php endif; ?>

                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if(!empty($ques['video_path'])): ?>
                                        <div class="mb-8 rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-slate-900 relative group flex justify-center max-h-[450px]">
                                            <video controls class="max-w-full h-auto object-contain w-full" controlsList="nodownload">
                                                <source src="<?php echo htmlspecialchars($ques['video_path']); ?>" type="video/mp4">
                                                <p class="text-white p-4">Your browser does not support HTML5 video.</p>
                                            </video>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <h3 class="text-xl font-bold text-jrmsuNavy mb-6 leading-relaxed flex items-start gap-3 p-5 bg-white border border-slate-200 rounded-xl shadow-sm">
                                            <i class="fas fa-question-circle text-jrmsuGold mt-1 text-2xl"></i>
                                            <span><?php echo htmlspecialchars($ques['question_text']); ?></span>
                                        </h3>
                                        
                                        <div class="space-y-4">
                                            <?php foreach(['a', 'b', 'c', 'd'] as $opt): ?>
                                            <label class="block m-0">
                                                <input class="hidden option-input" type="radio" name="q_<?php echo $ques['id']; ?>" value="<?php echo strtoupper($opt); ?>" required>
                                                <div class="option-card p-5 rounded-xl flex items-center gap-4 bg-white shadow-sm">
                                                    <div class="circle-marker h-5 w-5 rounded-full border-2 border-slate-300 shrink-0 flex items-center justify-center transition-all"></div>
                                                    <span class="text-[15px] font-semibold text-slate-700"><?php echo htmlspecialchars($ques['option_'.$opt]); ?></span>
                                                </div>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php $count++; endwhile; else: ?>
                                        <div class="text-center py-12">
                                            <i class="fas fa-clipboard-list text-slate-200 text-6xl mb-4"></i>
                                            <p class="font-bold text-slate-500">Questions are currently being prepared.</p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-10 flex justify-between items-center pt-6 border-t border-slate-200">
                                        <button type="button" class="btn-prev px-6 py-2.5 rounded-xl border-2 border-slate-200 text-slate-500 font-bold text-sm hover:bg-slate-200 transition-colors invisible">
                                            <i class="fas fa-arrow-left mr-2"></i> Prev
                                        </button>
                                        
                                        <button type="button" class="btn-next px-8 py-2.5 rounded-xl bg-jrmsuNavy text-white font-bold text-sm hover:bg-jrmsuNavyDark transition-all shadow-md">
                                            Next Step <i class="fas fa-arrow-right ml-2"></i>
                                        </button>

                                        <button type="submit" class="btn-submit hidden px-8 py-2.5 rounded-xl bg-green-500 text-white font-bold text-sm hover:bg-green-600 transition-all shadow-md">
                                            Submit Assessment <i class="fas fa-check ml-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div id="certificates" class="tab-content max-w-7xl mx-auto">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-jrmsuNavy">Academic Awards</h2>
                <p class="text-sm text-slate-500 mt-1">Your collection of earned certifications.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <?php
                $passed_modules = $conn->query("SELECT r.*, m.title FROM quiz_results r JOIN modules m ON r.module_id = m.id WHERE r.student_id = '$sid' AND (r.score / r.total_items) >= 0.5");
                if ($passed_modules->num_rows > 0): while($cert = $passed_modules->fetch_assoc()):
                ?>
                <div class="group">
                    <div id="certificate-<?php echo $cert['id']; ?>" class="certificate-frame p-8 sm:p-10 shadow-md text-center group-hover:shadow-xl transition-shadow bg-white">
                        <div class="mb-6">
                            <h2 class="text-jrmsuNavy font-serif italic text-3xl font-bold mb-1">Certificate of Completion</h2>
                            <p class="text-[10px] tracking-widest text-slate-400 font-bold uppercase">Jose Rizal Memorial State University</p>
                        </div>
                        <p class="text-sm italic text-slate-500 mb-2">This is to officially recognize that</p>
                        <h3 class="text-jrmsuGold text-2xl font-black uppercase underline mb-4 decoration-jrmsuGold/30 underline-offset-8"><?php echo $s_name; ?></h3>
                        <p class="text-sm italic text-slate-500 mb-2">Has successfully completed all requirements of the comprehensive online course in:</p>
                        <h4 class="text-jrmsuNavy font-bold text-lg mb-8"><?php echo htmlspecialchars($cert['title']); ?></h4>
                        <p class="text-xs font-bold text-slate-500 border-t border-slate-200 pt-4 inline-block px-8">GRANTED ON <?php echo strtoupper(date("F d, Y", strtotime($cert['date_taken']))); ?></p>
                        <i class="fas fa-award cert-seal text-8xl"></i>
                    </div>
                    <div class="text-center mt-5">
                        <button onclick="downloadCertificate('certificate-<?php echo $cert['id']; ?>', '<?php echo htmlspecialchars(addslashes($cert['title'])); ?>')" class="text-xs font-bold text-slate-500 hover:text-jrmsuNavy transition-all uppercase tracking-widest bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm hover:shadow">
                            <i class="fas fa-download mr-2 text-jrmsuGold"></i> Download PDF
                        </button>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                        <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-5">
                            <i class="fas fa-medal text-4xl text-jrmsuGold"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-700">No Certificates Yet</h3>
                        <p class="text-slate-500 mt-2 max-w-md mx-auto">Earn a score of 50% or higher in any module quiz to unlock and download your digital certificates here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="announcements" class="tab-content max-w-7xl mx-auto">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-jrmsuNavy">Latest Announcements</h2>
                <p class="text-sm text-slate-500 mt-1">Stay updated with the latest news and information from instructors and administrators.</p>
            </div>

            <div class="space-y-6">
                <?php 
                if ($announcements_result && $announcements_result->num_rows > 0): 
                    $announcements_result->data_seek(0); // Reset pointer in case it was used before
                    while($announcement = $announcements_result->fetch_assoc()): 
                        $date_posted = date("F d, Y h:i A", strtotime($announcement['created_at']));
                ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start sm:items-center gap-4 mb-4 flex-col sm:flex-row">
                            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="fas fa-bullhorn text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-jrmsuNavy"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-1 mt-1">
                                    <i class="fas fa-clock"></i> Posted on <?php echo $date_posted; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-slate-600 leading-relaxed text-sm sm:text-base whitespace-pre-line sm:ml-16">
                            <?php echo htmlspecialchars($announcement['content']); ?>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                <div class="py-16 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-bell-slash text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700">No Announcements Yet</h3>
                    <p class="text-slate-500 mt-2">There are currently no announcements to display. Check back later!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="profile" class="tab-content max-w-3xl mx-auto">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="h-32 bg-jrmsuNavy relative border-b-4 border-jrmsuGold">
                    <div class="absolute -bottom-12 left-1/2 -translate-x-1/2">
                        <div class="h-28 w-28 rounded-full ring-4 ring-white shadow-xl overflow-hidden bg-white">
                            <img src="<?php echo $profile_img; ?>" class="h-full w-full object-cover">
                        </div>
                    </div>
                </div>
                <div class="pt-16 p-8 sm:p-10 text-center">
                    <h3 class="text-2xl font-bold text-jrmsuNavy"><?php echo $s_name; ?></h3>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mb-10">University Student</p>
                    
                    <form method="POST" action="student.php" enctype="multipart/form-data" class="text-left space-y-6 max-w-md mx-auto">
                        <input type="hidden" name="action" value="upload_photo">
                        
                        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                            <label class="block text-sm font-bold text-jrmsuNavy mb-3 uppercase tracking-wide">Update Avatar</label>
                            <input type="file" name="profile_pic" accept="image/*" required class="block w-full text-sm text-slate-700 border border-slate-300 rounded-xl cursor-pointer bg-white focus:outline-none file:mr-4 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold file:bg-slate-200 file:text-jrmsuNavy hover:file:bg-slate-300 file:cursor-pointer transition-all">
                        </div>

                        <button type="submit" class="w-full py-3.5 bg-jrmsuGold hover:bg-jrmsuGoldLight text-jrmsuNavy font-bold rounded-xl shadow-md hover:shadow-lg transition-all uppercase tracking-wide text-sm flex justify-center items-center gap-2">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    // Certificate PDF Download Logic
    function downloadCertificate(elementId, title) {
        const element = document.getElementById(elementId);
        
        // Setup PDF Options
        const opt = {
            margin:       0.5,
            filename:     title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_certificate.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
        };

        // Generate and save PDF
        html2pdf().set(opt).from(element).save();
    }

    // Tab Navigation Logic
    const navButtons = document.querySelectorAll('.nav-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const pageTitle = document.getElementById('pageTitle');

    // Updated the titles order to match the new layout
    const titles = {
        'dashboard': 'Student Dashboard',
        'modules': 'Course Syllabus',
        'certificates': 'My Certificates',
        'announcements': 'Announcements',
        'profile': 'Student Profile'
    };

    function activateTab(target) {
        tabContents.forEach(content => {
            content.classList.remove('active');
            if(content.id === target) {
                setTimeout(() => content.classList.add('active'), 10);
            }
        });

        navButtons.forEach(b => {
            b.classList.remove('bg-jrmsuGold', 'text-jrmsuNavy', 'font-semibold', 'shadow-md');
            b.classList.add('text-white/70', 'hover:bg-white/10', 'hover:text-white', 'font-medium');
            if(b.getAttribute('data-target') === target) {
                b.classList.add('bg-jrmsuGold', 'text-jrmsuNavy', 'font-semibold', 'shadow-md');
                b.classList.remove('text-white/70', 'hover:bg-white/10', 'hover:text-white', 'font-medium');
            }
        });

        if(pageTitle) pageTitle.textContent = titles[target];
    }

    // Event Listeners for tabs
    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            activateTab(btn.getAttribute('data-target'));
            if(window.innerWidth < 768) toggleSidebar();
        });
    });

    // Initialize Active Tab from PHP
    document.addEventListener("DOMContentLoaded", function() {
        activateTab("<?php echo $active_tab; ?>");
    });

    // Sidebar Mobile Toggle
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');
    
    function toggleSidebar() {
        const isOpen = !sidebar.classList.contains('-translate-x-full');
        if (isOpen) {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        } else {
            sidebar.classList.remove('-translate-x-full');
            mobileOverlay.classList.remove('hidden');
        }
    }

    document.getElementById('openSidebar').addEventListener('click', toggleSidebar);
    document.getElementById('closeSidebar').addEventListener('click', toggleSidebar);
    mobileOverlay.addEventListener('click', toggleSidebar);

    // Modal Generic Control
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.style.overflow = 'auto';
        
        // Pause video when modal is closed to stop audio from playing in the background
        const modal = document.getElementById(id);
        const videos = modal.querySelectorAll('video');
        videos.forEach(video => video.pause());
    }
    
    // Quiz Wizard Logic (HP Life Structure Supported)
    function openAndInitQuiz(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        const modal = document.getElementById(modalId);
        const steps = modal.querySelectorAll('.quiz-step');
        if(steps.length === 0) return; 
        
        const btnNext = modal.querySelector('.btn-next');
        const btnPrev = modal.querySelector('.btn-prev');
        const btnSubmit = modal.querySelector('.btn-submit');
        const progressBar = modal.querySelector('.progress-bar-custom');
        const stepCountSpan = modal.querySelector('.step-count');
        
        let currentStepIndex = 0;
        let totalSteps = steps.length;

        // Reset inputs on open
        modal.querySelectorAll('input[type="radio"]').forEach(radio => radio.checked = false);
        
        // Reset videos play states
        modal.querySelectorAll('video').forEach(vid => {
            vid.dataset.watched = 'false';
            vid.maxTimePlayed = 0;
            vid.currentTime = 0;
        });

        function showStep(index) {
            // Pause any playing videos before switching steps
            const currentVideo = steps[currentStepIndex]?.querySelector('video');
            if (currentVideo) currentVideo.pause();

            steps.forEach(step => step.classList.remove('active'));
            steps[index].classList.add('active');

            let currentStepEl = steps[index];
            let video = currentStepEl.querySelector('video');
            let qTitle = currentStepEl.querySelector('h3'); 
            let qOptions = currentStepEl.querySelector('.space-y-4'); 

            btnPrev.style.visibility = (index === 0) ? 'hidden' : 'visible';

            let percentage = ((index + 1) / totalSteps) * 100;
            progressBar.style.width = percentage + '%';
            stepCountSpan.innerText = (index + 1) + " of " + totalSteps;

            // --- REQUIRED VIDEO WATCHING LOGIC ---
            if (video && video.dataset.watched !== 'true') {
                if (qTitle) qTitle.classList.add('hidden');
                if (qOptions) qOptions.classList.add('hidden');
                btnNext.classList.add('hidden');
                btnSubmit.classList.add('hidden');

                let msg = currentStepEl.querySelector('.video-msg');
                if(!msg) {
                    msg = document.createElement('p');
                    msg.className = 'video-msg text-center text-amber-600 font-bold mb-4 animate-[slideUpFade_0.3s_ease-out] bg-amber-50 p-4 rounded-xl border border-amber-200 text-[15px]';
                    msg.innerHTML = '<i class="fas fa-video mr-2"></i> Please watch the entire video lesson to unlock the assessment question below.';
                    video.parentElement.insertAdjacentElement('afterend', msg);
                }
                msg.classList.remove('hidden');

                video.addEventListener('timeupdate', function() {
                    if (!video.maxTimePlayed) video.maxTimePlayed = 0;
                    if (video.currentTime > video.maxTimePlayed + 1) { 
                        video.currentTime = video.maxTimePlayed;
                    } else {
                        video.maxTimePlayed = video.currentTime;
                    }
                });

                video.onended = function() {
                    video.dataset.watched = 'true';
                    
                    if (qTitle) {
                        qTitle.classList.remove('hidden');
                        qTitle.classList.add('animate-[slideUpFade_0.4s_ease-out]');
                    }
                    if (qOptions) {
                        qOptions.classList.remove('hidden');
                        qOptions.classList.add('animate-[slideUpFade_0.5s_ease-out]');
                    }
                    if (msg) msg.classList.add('hidden'); 
                    
                    if(index === totalSteps - 1) {
                        btnSubmit.classList.remove('hidden');
                        btnNext.classList.add('hidden');
                    } else {
                        btnNext.classList.remove('hidden');
                        btnSubmit.classList.add('hidden');
                    }
                };
            } else {
                if (qTitle) qTitle.classList.remove('hidden');
                if (qOptions) qOptions.classList.remove('hidden');
                
                let msg = currentStepEl.querySelector('.video-msg');
                if(msg) msg.classList.add('hidden');

                if(index === totalSteps - 1) {
                    btnNext.classList.add('hidden');
                    btnSubmit.classList.remove('hidden');
                } else {
                    btnNext.classList.remove('hidden');
                    btnSubmit.classList.add('hidden');
                }
            }
        }

        showStep(0);

        btnNext.onclick = function() {
            let currentStepEl = steps[currentStepIndex];
            let inputs = currentStepEl.querySelectorAll('input[type="radio"]');
            let answered = Array.from(inputs).some(input => input.checked);

            if(!answered) {
                alert("Please select an answer to proceed.");
                return;
            }

            if(currentStepIndex < totalSteps - 1) {
                currentStepIndex++;
                showStep(currentStepIndex);
            }
        };

        btnPrev.onclick = function() {
            if(currentStepIndex > 0) {
                currentStepIndex--;
                showStep(currentStepIndex);
            }
        };
    }

    // Auto-hide alert box
    window.addEventListener('DOMContentLoaded', () => {
        const alertBox = document.getElementById('alertBox');
        if(alertBox) {
            setTimeout(() => {
                alertBox.style.opacity = '0';
                alertBox.style.transform = 'translateY(-20px)';
                alertBox.style.transition = 'all 0.5s ease';
                setTimeout(() => alertBox.style.display = 'none', 500);
            }, 5000);
        }
    });

    // --- Announcement Badge Logic ---
    const announcementBadge = document.getElementById('announcement-badge');
    const currentAnnouncements = <?php echo $announcement_count; ?>;
    const studentId = '<?php echo $sid; ?>';

    // Get the number of announcements the student has already seen
    const readAnnouncements = parseInt(localStorage.getItem('read_announcements_' + studentId)) || 0;

    if (announcementBadge) {
        if (readAnnouncements >= currentAnnouncements) {
            // They have seen everything, hide the badge
            announcementBadge.style.display = 'none';
        } else {
            // Calculate how many new announcements there are
            const unreadCount = currentAnnouncements - readAnnouncements;
            announcementBadge.textContent = unreadCount; // Update badge to show ONLY new count
            announcementBadge.style.display = 'inline-block';
        }
    }

    // Hide badge and save state when the Announcements tab is clicked
    const announcementTabBtn = document.querySelector('[data-target="announcements"]');
    if (announcementTabBtn) {
        announcementTabBtn.addEventListener('click', function() {
            if (announcementBadge && announcementBadge.style.display !== 'none') {
                announcementBadge.style.display = 'none';
                localStorage.setItem('read_announcements_' + studentId, currentAnnouncements);
            }
        });
    }
</script>
</body>
</html>
