<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];
$teacher_name = $_SESSION['teacher_name'];

// --- 1. HANDLE FORM SUBMISSIONS ---

// Handle Delete Announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_announcement') {
    $ann_id = $_POST['announcement_id'];
    
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i", $ann_id);
    if ($stmt->execute()) { 
        $msg = "Announcement deleted successfully!"; 
        $msg_type = "success"; 
    } else { 
        $msg = "Error deleting announcement."; 
        $msg_type = "danger"; 
    }
    $stmt->close();
}

// Handle Add Announcement (New)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_announcement') {
    $title = $_POST['ann_title'];
    $content = $_POST['ann_content'];
    $date_posted = date('Y-m-d H:i:s'); // Explicitly generate the current date/time
    $posted_by = 'instructor'; // Specify the source

    // 4 columns being updated, so we need exactly 4 question marks
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_at, posted_by) VALUES (?, ?, ?, ?)");
    
    // "ssss" means 4 strings are being passed in exact order
    $stmt->bind_param("ssss", $title, $content, $date_posted, $posted_by);
    
    if ($stmt->execute()) { 
        $msg = "Announcement posted successfully!"; 
        $msg_type = "success"; 
    } else { 
        $msg = "Error posting announcement."; 
        $msg_type = "danger"; 
    }
    $stmt->close();
}

// Handle Add Module
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_module') {
    $title = $_POST['module_title'];
    $desc = $_POST['module_desc'];
    
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_path = "";
    if(isset($_FILES['module_file']) && $_FILES['module_file']['name'] != "") {
        $target_file = $target_dir . basename($_FILES["module_file"]["name"]);
        if (move_uploaded_file($_FILES["module_file"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO modules (title, description, file_path, instructor_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $desc, $file_path, $instructor_id);
    if ($stmt->execute()) { $msg = "Module published successfully!"; $msg_type = "success"; } 
    else { $msg = "Error publishing module."; $msg_type = "danger"; }
    $stmt->close();
}

// Handle Update Module
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_module') {
    $mod_id = $_POST['module_id'];
    $title = $_POST['module_title'];
    $desc = $_POST['module_desc'];
    
    // Security check to ensure the teacher owns this module
    $check = $conn->query("SELECT file_path FROM modules WHERE id = $mod_id AND instructor_id = $instructor_id");
    if($check->num_rows > 0) {
        $existing = $check->fetch_assoc();
        $file_path = $existing['file_path'];

        $target_dir = "uploads/";
        if(isset($_FILES['module_file']) && $_FILES['module_file']['name'] != "") {
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $target_file = $target_dir . basename($_FILES["module_file"]["name"]);
            if (move_uploaded_file($_FILES["module_file"]["tmp_name"], $target_file)) {
                $file_path = $target_file;
            }
        }

        $stmt = $conn->prepare("UPDATE modules SET title=?, description=?, file_path=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $desc, $file_path, $mod_id);
        if ($stmt->execute()) { $msg = "Module updated successfully!"; $msg_type = "success"; } 
        else { $msg = "Error updating module."; $msg_type = "danger"; }
        $stmt->close();
    } else {
        $msg = "Unauthorized action."; $msg_type = "danger";
    }
}

// Handle Delete Module
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_module') {
    $mod_id = $_POST['module_id'];
    
    // Security check to ensure the teacher owns this module
    $check = $conn->query("SELECT id FROM modules WHERE id = $mod_id AND instructor_id = $instructor_id");
    if($check->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM modules WHERE id=?");
        $stmt->bind_param("i", $mod_id);
        if ($stmt->execute()) { 
            $msg = "Module deleted successfully!"; 
            $msg_type = "success"; 
        } else { 
            $msg = "Error deleting module."; 
            $msg_type = "danger"; 
        }
        $stmt->close();
    } else {
        $msg = "Unauthorized action."; $msg_type = "danger";
    }
}

// Handle Add Multiple Quiz Questions (New feature)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_multiple_questions') {
    // Since module_id is moved into an array by JS, grab the first one
    $mod_id = is_array($_POST['module_id']) ? $_POST['module_id'][0] : $_POST['module_id'];
    
    // Security check
    $check = $conn->query("SELECT id FROM modules WHERE id = $mod_id AND instructor_id = $instructor_id");
    if($check->num_rows > 0) {
        $questions_added = 0;
        
        if(isset($_POST['question_text']) && is_array($_POST['question_text'])) {
            for($i = 0; $i < count($_POST['question_text']); $i++) {
                $q_text = $_POST['question_text'][$i];
                if(empty(trim($q_text))) continue; // Skip empty questions
                
                $content_text = $_POST['content_text'][$i] ?? '';
                $content_layout = $_POST['content_layout'][$i] ?? 'standard';
                $opt_a = $_POST['option_a'][$i] ?? '';
                $opt_b = $_POST['option_b'][$i] ?? '';
                $opt_c = $_POST['option_c'][$i] ?? '';
                $opt_d = $_POST['option_d'][$i] ?? '';
                $correct = $_POST['correctAnswer'][$i] ?? '';
                
                // Handle Image for this specific question
                $image_path = "";
                $target_dir_images = "uploads/images/";
                if (!file_exists($target_dir_images)) { mkdir($target_dir_images, 0777, true); }
                if(isset($_FILES['content_image']['name'][$i]) && $_FILES['content_image']['name'][$i] != "") {
                    $image_name = time() . "_" . basename($_FILES['content_image']['name'][$i]);
                    $target_file_image = $target_dir_images . $image_name;
                    if (move_uploaded_file($_FILES['content_image']['tmp_name'][$i], $target_file_image)) {
                        $image_path = $target_file_image;
                    }
                }
                
                // Handle Video for this specific question
                $video_path = "";
                $target_dir_videos = "uploads/videos/";
                if (!file_exists($target_dir_videos)) { mkdir($target_dir_videos, 0777, true); }
                if(isset($_FILES['question_video']['name'][$i]) && $_FILES['question_video']['name'][$i] != "") {
                    $video_name = time() . "_" . basename($_FILES['question_video']['name'][$i]);
                    $target_file_video = $target_dir_videos . $video_name;
                    if (move_uploaded_file($_FILES['question_video']['tmp_name'][$i], $target_file_video)) {
                        $video_path = $target_file_video;
                    }
                }

                $stmt = $conn->prepare("INSERT INTO quiz_questions (module_id, content_text, content_layout, content_image, question_text, option_a, option_b, option_c, option_d, correct_answer, video_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssss", $mod_id, $content_text, $content_layout, $image_path, $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $video_path);

                if ($stmt->execute()) {
                    $questions_added++;
                }
                $stmt->close();
            }
        }

        if ($questions_added > 0) {
            $conn->query("UPDATE modules SET item_count = item_count + $questions_added WHERE id = $mod_id");
            $msg = "$questions_added questions successfully added in bulk!"; $msg_type = "success";
        } else {
            $msg = "No valid questions were submitted."; $msg_type = "danger";
        }
    } else {
        $msg = "Unauthorized action."; $msg_type = "danger";
    }
}

// Handle Add Quiz Question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_question') {
    $mod_id = $_POST['module_id'];
    $q_text = $_POST['question_text'];
    
    $check = $conn->query("SELECT id FROM modules WHERE id = $mod_id AND instructor_id = $instructor_id");
    if($check->num_rows > 0) {
        $content_text = $_POST['content_text'] ?? '';
        $content_layout = $_POST['content_layout'] ?? 'standard';
        
        $image_path = "";
        $target_dir_images = "uploads/images/";
        if (!file_exists($target_dir_images)) { mkdir($target_dir_images, 0777, true); }
        
        if(isset($_FILES['content_image']) && $_FILES['content_image']['name'] != "") {
            $image_name = time() . "_" . basename($_FILES["content_image"]["name"]);
            $target_file_image = $target_dir_images . $image_name;
            if (move_uploaded_file($_FILES["content_image"]["tmp_name"], $target_file_image)) {
                $image_path = $target_file_image;
            }
        }
        
        $opt_a = $_POST['option_a']; $opt_b = $_POST['option_b'];
        $opt_c = $_POST['option_c']; $opt_d = $_POST['option_d'];
        $correct = $_POST['correctAnswer'];

        $video_path = "";
        $target_dir_videos = "uploads/videos/";
        if (!file_exists($target_dir_videos)) { mkdir($target_dir_videos, 0777, true); }
        
        if(isset($_FILES['question_video']) && $_FILES['question_video']['name'] != "") {
            $video_name = time() . "_" . basename($_FILES["question_video"]["name"]);
            $target_file_video = $target_dir_videos . $video_name;
            if (move_uploaded_file($_FILES["question_video"]["tmp_name"], $target_file_video)) {
                $video_path = $target_file_video;
            }
        }

        $stmt = $conn->prepare("INSERT INTO quiz_questions (module_id, content_text, content_layout, content_image, question_text, option_a, option_b, option_c, option_d, correct_answer, video_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $mod_id, $content_text, $content_layout, $image_path, $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $video_path);

        if ($stmt->execute()) {
            $conn->query("UPDATE modules SET item_count = item_count + 1 WHERE id = $mod_id");
            $msg = "Question and content added to your module!"; $msg_type = "success";
        }
        $stmt->close();
    } else {
        $msg = "Unauthorized action."; $msg_type = "danger";
    }
}

// Handle Update Quiz Question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_question') {
    $q_id = $_POST['question_id'];
    $mod_id = $_POST['module_id'];
    $q_text = $_POST['question_text'];
    
    // Security check to ensure the teacher owns the target module
    $check = $conn->query("SELECT id FROM modules WHERE id = $mod_id AND instructor_id = $instructor_id");
    if($check->num_rows > 0) {
        $content_text = $_POST['content_text'] ?? '';
        $content_layout = $_POST['content_layout'] ?? 'standard';
        
        // 1. Run the query (it's best practice to wrap the ID in quotes, even if it's an integer)
$query = "SELECT content_image, video_path FROM quiz_questions WHERE id = '$q_id'";
$result = $conn->query($query);

// 2. Safely fetch the data ONLY if the query was successful
$existing_data = $result ? $result->fetch_assoc() : null;

// 3. Extract the paths safely using the Null Coalescing Operator (??)
// If the data doesn't exist, it defaults to an empty string instead of crashing.
$image_path = $existing_data['content_image'] ?? '';
$video_path = $existing_data['video_path'] ?? '';
        $target_dir_images = "uploads/images/";
        if(isset($_FILES['content_image']) && $_FILES['content_image']['name'] != "") {
            if (!file_exists($target_dir_images)) { mkdir($target_dir_images, 0777, true); }
            $image_name = time() . "_" . basename($_FILES["content_image"]["name"]);
            $target_file_image = $target_dir_images . $image_name;
            if (move_uploaded_file($_FILES["content_image"]["tmp_name"], $target_file_image)) {
                $image_path = $target_file_image;
            }
        }

        $target_dir_videos = "uploads/videos/";
        if(isset($_FILES['question_video']) && $_FILES['question_video']['name'] != "") {
            if (!file_exists($target_dir_videos)) { mkdir($target_dir_videos, 0777, true); }
            $video_name = time() . "_" . basename($_FILES["question_video"]["name"]);
            $target_file_video = $target_dir_videos . $video_name;
            if (move_uploaded_file($_FILES["question_video"]["tmp_name"], $target_file_video)) {
                $video_path = $target_file_video;
            }
        }

        $opt_a = $_POST['option_a']; $opt_b = $_POST['option_b'];
        $opt_c = $_POST['option_c']; $opt_d = $_POST['option_d'];
        $correct = $_POST['correctAnswer'];

        $stmt = $conn->prepare("UPDATE quiz_questions SET module_id=?, content_text=?, content_layout=?, content_image=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, video_path=? WHERE id=?");
        $stmt->bind_param("issssssssssi", $mod_id, $content_text, $content_layout, $image_path, $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $video_path, $q_id);
        
        if ($stmt->execute()) {
            $msg = "Question updated successfully!"; $msg_type = "success";
        } else {
            $msg = "Error updating question."; $msg_type = "danger";
        }
        $stmt->close();
    } else {
        $msg = "Unauthorized action."; $msg_type = "danger";
    }
}

// --- 2. FETCH DATA ---
$my_modules = $conn->query("SELECT * FROM modules WHERE instructor_id = $instructor_id ORDER BY id DESC");
$total_my_modules = $my_modules->num_rows;

$student_results = $conn->query("
    SELECT r.score, r.total_items, r.date_taken, s.full_name as student_name, m.title as module_title 
    FROM quiz_results r 
    JOIN students s ON r.student_id = s.student_id 
    JOIN modules m ON r.module_id = m.id 
    WHERE m.instructor_id = $instructor_id 
    ORDER BY r.date_taken DESC
");

// Fetch existing questions for editing
$all_questions = $conn->query("
    SELECT q.*, m.title as module_title 
    FROM quiz_questions q 
    JOIN modules m ON q.module_id = m.id 
    WHERE m.instructor_id = $instructor_id 
    ORDER BY q.id DESC
");

// Fetch Announcements
$announcements_result = $conn->query("SELECT * FROM announcements WHERE posted_by IN ('admin', 'instructor') ORDER BY created_at DESC");
$total_announcements = $announcements_result ? $announcements_result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | JRMSU Portal</title>
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
        .tab-content { display: none; opacity: 0; }
        .tab-content.active { display: block; opacity: 1; animation: slideUpFade 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes slideUpFade { 
            from { opacity: 0; transform: translateY(20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        input, textarea, select { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-gray-800 font-sans antialiased">

<?php if(isset($msg)): ?>
<div id="alertBox" class="fixed top-6 right-6 z-50 flex items-center w-full max-w-sm p-4 text-gray-800 bg-white rounded-xl shadow-2xl border-l-4 <?php echo $msg_type == 'success' ? 'border-green-500' : 'border-red-500'; ?> animate-[slideUpFade_0.3s_ease-out]" role="alert">
    <div class="inline-flex items-center justify-center flex-shrink-0 w-10 h-10 <?php echo $msg_type == 'success' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'; ?> rounded-full">
        <i class="fas <?php echo $msg_type == 'success' ? 'fa-check' : 'fa-exclamation-triangle'; ?> text-lg"></i>
    </div>
    <div class="ms-3 text-sm font-medium flex-1"><?php echo $msg; ?></div>
    <button type="button" onclick="document.getElementById('alertBox').style.display='none'" class="ms-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 transition-colors">
        <span class="sr-only">Close</span>
        <i class="fas fa-times"></i>
    </button>
</div>
<?php endif; ?>

<div id="sidebar" class="bg-jrmsuNavy w-72 flex-shrink-0 h-full flex flex-col transition-transform duration-300 ease-in-out z-40 fixed md:relative -translate-x-full md:translate-x-0 shadow-2xl border-r border-jrmsuNavyDark">
    <div class="p-6 flex items-center justify-between border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-jrmsuGold rounded-lg flex items-center justify-center shadow-lg">
                <i class="fas fa-university text-jrmsuNavy text-xl"></i>
            </div>
            <div>
                <h2 class="text-white font-bold text-lg leading-tight tracking-wide">JRMSU</h2>
                <p class="text-jrmsuGold text-xs font-medium uppercase tracking-widest">Faculty Portal</p>
            </div>
        </div>
        <button id="closeSidebar" class="md:hidden text-white/70 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-6">
        <nav class="space-y-2 px-4">
            <button data-target="dashboard" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 bg-jrmsuGold text-jrmsuNavy font-semibold shadow-md">
                <i class="fas fa-th-large w-5 text-center text-lg"></i> <span>Dashboard</span>
            </button>
            <button data-target="modules" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-book-open w-5 text-center text-lg"></i> <span>My Modules</span>
            </button>
            <button data-target="quizzes" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-clipboard-question w-5 text-center text-lg"></i> <span>Content Management</span>
            </button>
            <button data-target="announcements" id="announcementBtn" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <div class="relative">
                    <i class="fas fa-bullhorn w-5 text-center text-lg"></i>
                    <span id="announcementBadge" class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full hidden shadow-sm border border-jrmsuNavy">New</span>
                </div>
                <span>Announcements</span>
            </button>
            <button data-target="reports" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-chart-pie w-5 text-center text-lg"></i> <span>Student Results</span>
            </button>
        </nav>
    </div>
    <div class="p-4 border-t border-white/10">
        <a href="instructor_login.php" class="flex items-center justify-center gap-3 w-full px-4 py-3 text-sm font-medium rounded-xl text-red-300 bg-red-500/10 hover:bg-red-500/20 hover:text-red-200 transition-colors border border-red-500/20">
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
            <h1 id="pageTitle" class="text-2xl font-bold text-jrmsuNavy hidden sm:block tracking-tight">Dashboard</h1>
        </div>
        <div class="flex items-center gap-5">
            <div class="text-right hidden sm:block">
                <div class="text-sm font-bold text-jrmsuNavy">Prof. <?php echo explode(" ", $teacher_name)[0]; ?></div>
                <div class="text-xs text-slate-500 font-medium">JRMSU Faculty</div>
            </div>
            <div class="relative">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($teacher_name); ?>&background=002855&color=EAA221&bold=true" alt="Profile" class="w-11 h-11 rounded-full shadow-md ring-2 ring-jrmsuGold ring-offset-2">
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 lg:p-10">
        <div id="dashboard" class="tab-content active max-w-7xl mx-auto space-y-8">
            <div class="bg-jrmsuNavy rounded-3xl p-8 sm:p-10 shadow-xl relative overflow-hidden border-b-4 border-jrmsuGold">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-20 w-40 h-40 bg-jrmsuGold opacity-10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-jrmsuGold text-xs font-semibold tracking-wider mb-3 backdrop-blur-sm border border-white/10">ACADEMIC YEAR 2025-2026</span>
                        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Welcome back, <span class="text-jrmsuGold"><?php echo explode(" ", $teacher_name)[0]; ?></span>!</h2>
                        <p class="text-white/80 text-sm sm:text-base max-w-xl leading-relaxed">Your faculty control center. Manage your learning materials, deploy quizzes, and monitor student excellence seamlessly.</p>
                    </div>
                    <div class="hidden md:flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-8xl text-white/10 transform rotate-12"></i>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-6 hover:shadow-md transition-shadow group">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-jrmsuNavy flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform duration-300 group-hover:bg-jrmsuNavy group-hover:text-jrmsuGold">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Published Modules</p>
                        <h3 class="text-4xl font-bold text-jrmsuNavy mt-1"><?php echo $total_my_modules; ?></h3>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-6 hover:shadow-md transition-shadow group">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 text-jrmsuGold flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform duration-300 group-hover:bg-jrmsuGold group-hover:text-white">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Total Submissions</p>
                        <h3 class="text-4xl font-bold text-jrmsuNavy mt-1"><?php echo $student_results->num_rows; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div id="modules" class="tab-content max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-jrmsuNavy">Course Modules</h2>
                    <p class="text-sm text-slate-500 mt-1">Create and manage your educational content.</p>
                </div>
                <button onclick="openModal('moduleModal')" class="bg-jrmsuGold hover:bg-jrmsuGoldLight text-jrmsuNavy font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                    <i class="fas fa-plus-circle text-lg"></i> Create New Module
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if ($total_my_modules > 0): $my_modules->data_seek(0); while($mod = $my_modules->fetch_assoc()): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col h-full group">
                    <div class="h-2 w-full bg-jrmsuGold"></div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-jrmsuNavy group-hover:bg-jrmsuNavy group-hover:text-jrmsuGold transition-colors">
                                <i class="fas fa-folder-open text-xl"></i>
                            </div>
                            <div class="flex gap-2 items-center">
                                <button onclick="
                                    document.getElementById('edit_mod_id').value = this.dataset.id;
                                    document.getElementById('edit_mod_title').value = this.dataset.title;
                                    document.getElementById('edit_mod_desc').value = this.dataset.desc;
                                    openModal('editModuleModal');" 
                                    data-id="<?php echo $mod['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($mod['title'] ?? ''); ?>"
                                    data-desc="<?php echo htmlspecialchars($mod['description'] ?? ''); ?>"
                                    class="bg-white border border-slate-200 text-slate-500 hover:text-jrmsuNavy hover:bg-slate-50 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-colors" title="Edit Module">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <form method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('Are you sure you want to delete this module? Any associated quizzes will also be impacted.');">
                                    <input type="hidden" name="action" value="delete_module">
                                    <input type="hidden" name="module_id" value="<?php echo $mod['id']; ?>">
                                    <button type="submit" class="bg-white border border-red-200 text-red-500 hover:text-white hover:bg-red-500 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-colors" title="Delete Module">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                                <span class="bg-blue-50 text-jrmsuNavy text-xs font-bold px-3 py-1.5 rounded-lg border border-blue-100 flex items-center gap-1">
                                    <i class="fas fa-list-ol"></i> <?php echo $mod['item_count']; ?> Items
                                </span>
                            </div>
                        </div>
                        <h5 class="text-lg font-bold text-slate-800 mb-2 line-clamp-1 group-hover:text-jrmsuNavy transition-colors"><?php echo htmlspecialchars($mod['title']); ?></h5>
                        <p class="text-sm text-slate-500 flex-1 line-clamp-3 leading-relaxed"><?php echo htmlspecialchars($mod['description']); ?></p>
                        
                        <?php if($mod['file_path']): ?>
                        <div class="mt-6 pt-4 border-t border-slate-100">
                            <a href="<?php echo htmlspecialchars($mod['file_path']); ?>" target="_blank" class="inline-flex items-center gap-2 text-sm font-semibold text-jrmsuNavy hover:text-jrmsuGold transition-colors w-full justify-center bg-slate-50 py-2 rounded-lg hover:bg-slate-100">
                                <i class="fas fa-file-pdf text-red-500"></i> View PDF Handout
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="col-span-full py-16 flex flex-col items-center justify-center bg-white rounded-3xl border-2 border-dashed border-slate-300">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5">
                        <i class="fas fa-box-open text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700">No Modules Published</h3>
                    <p class="text-slate-500 mt-2 text-center max-w-sm">You haven't uploaded any learning materials yet. Click the "Create New Module" button to get started.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="quizzes" class="tab-content max-w-4xl mx-auto space-y-10">
            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden">
                <div class="bg-jrmsuNavy px-8 py-6 text-white">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <i class="fas fa-vial text-jrmsuGold"></i> Quiz Question Builder
                    </h2>
                    <p class="text-white/80 text-sm mt-2 font-medium">Systematically add multiple-choice questions to your modules.</p>
                </div>
                
                <div class="p-8">
                    <?php if($total_my_modules == 0): ?>
                        <div class="bg-amber-50 border-l-4 border-jrmsuGold text-amber-900 px-6 py-4 rounded-r-xl flex items-start gap-4 shadow-sm">
                            <i class="fas fa-exclamation-circle text-xl mt-0.5 text-jrmsuGold"></i>
                            <div>
                                <h4 class="font-bold text-sm">Action Required</h4>
                                <p class="text-sm mt-1">You need to create a module first before you can construct quiz questions.</p>
                            </div>
                        </div>
                    <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="add_question">
                        
                        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                            <label class="block text-sm font-bold text-jrmsuNavy mb-3 uppercase tracking-wide">Target Module</label>
                            <select name="module_id" required class="w-full bg-white border border-slate-300 text-slate-800 text-base rounded-xl focus:ring-2 focus:ring-jrmsuGold focus:border-jrmsuGold block p-3.5 outline-none shadow-sm cursor-pointer appearance-none">
                                <option value="" disabled selected>Select a module to append questions to...</option>
                                <?php $my_modules->data_seek(0); while($m = $my_modules->fetch_assoc()): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-jrmsuNavy mb-3 uppercase tracking-wide">Detailed Content / Lesson Info <span class="text-slate-400 font-medium text-xs normal-case">(Optional)</span></label>
                            <textarea name="content_text" rows="5" placeholder="Provide background information, reading material, or context for this question..." class="w-full bg-white border border-slate-300 text-slate-800 text-base rounded-xl focus:ring-2 focus:ring-jrmsuGold focus:border-jrmsuGold block p-4 outline-none shadow-sm resize-none"></textarea>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 p-5 bg-slate-50 rounded-xl border border-slate-200">
                                <div>
                                    <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase tracking-wide"><i class="fas fa-layer-group mr-1"></i> Content Layout</label>
                                    <select name="content_layout" class="w-full bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-jrmsuGold focus:border-jrmsuGold block p-2.5 outline-none shadow-sm cursor-pointer">
                                        <option value="standard">Standard (Text Only)</option>
                                        <option value="hero">Hero (Image Top, Text Below)</option>
                                        <option value="image_left">Split View (Image Left, Text Right)</option>
                                        <option value="image_right">Split View (Text Left, Image Right)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase tracking-wide"><i class="fas fa-image mr-1"></i> Content Image <span class="text-slate-400 font-medium text-[10px] normal-case">(Optional)</span></label>
                                    <input type="file" name="content_image" accept="image/*" class="block w-full text-xs text-slate-700 border border-slate-300 rounded-lg cursor-pointer bg-white focus:outline-none file:mr-3 file:py-2 file:px-3 file:rounded-l-lg file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-jrmsuNavy hover:file:bg-slate-300 file:cursor-pointer transition-all">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-jrmsuNavy mb-3 uppercase tracking-wide">Question Prompt</label>
                            <textarea name="question_text" rows="3" required placeholder="Enter your question here..." class="w-full bg-white border border-slate-300 text-slate-800 text-base rounded-xl focus:ring-2 focus:ring-jrmsuGold focus:border-jrmsuGold block p-4 outline-none shadow-sm resize-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-jrmsuNavy mb-3 uppercase tracking-wide">Attach Video <span class="text-slate-400 font-medium text-xs normal-case">(Optional)</span></label>
                            <input type="file" name="question_video" accept="video/*" class="block w-full text-sm text-slate-700 border border-slate-300 rounded-xl cursor-pointer bg-slate-50 focus:outline-none file:mr-4 file:py-3 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold file:bg-slate-200 file:text-jrmsuNavy hover:file:bg-slate-300 file:cursor-pointer transition-all">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php 
                                $opts = ['A', 'B', 'C', 'D'];
                                foreach($opts as $opt): 
                                    $field_name = "option_" . strtolower($opt);
                            ?>
                            <div class="relative group">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">Option <?php echo $opt; ?></label>
                                <div class="flex shadow-sm rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-jrmsuGold focus-within:border-jrmsuGold border border-slate-300 transition-all">
                                    <div class="flex items-center justify-center px-4 bg-slate-100 border-r border-slate-300">
                                        <input type="radio" name="correctAnswer" value="<?php echo $opt; ?>" required class="w-4 h-4 text-jrmsuNavy focus:ring-jrmsuNavy cursor-pointer">
                                    </div>
                                    <input type="text" name="<?php echo $field_name; ?>" required placeholder="Answer text..." class="bg-white text-slate-800 block flex-1 min-w-0 w-full text-sm p-3.5 outline-none border-none">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="pt-6 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-xs text-slate-500 font-medium"><i class="fas fa-info-circle text-jrmsuNavy"></i> Select the radio button next to the correct answer.</span>
                            <button type="submit" class="bg-jrmsuNavy hover:bg-jrmsuNavyDark text-white font-bold py-3.5 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 text-sm inline-flex items-center gap-2 transform hover:-translate-y-0.5">
                                Save to Database <i class="fas fa-save text-jrmsuGold"></i>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden">
                <div class="bg-slate-100 px-8 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-bold text-jrmsuNavy flex items-center gap-2">
                        <i class="fas fa-list-ul"></i> Manage Existing Questions
                    </h2>
                </div>
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200 font-bold">
                            <tr>
                                <th class="px-6 py-4">Module</th>
                                <th class="px-6 py-4">Question</th>
                                <th class="px-6 py-4">Media attached</th>
                                <th class="px-6 py-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($all_questions->num_rows > 0): while($q = $all_questions->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-medium text-jrmsuNavy whitespace-nowrap"><?php echo htmlspecialchars($q['module_title']); ?></td>
                                <td class="px-6 py-4 max-w-xs truncate" title="<?php echo htmlspecialchars($q['question_text']); ?>">
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <?php if($q['content_image']): ?><i class="fas fa-image text-slate-400" title="Has Image"></i><?php endif; ?>
                                        <?php if($q['video_path']): ?><i class="fas fa-video text-slate-400" title="Has Video"></i><?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="
                                        document.getElementById('edit_q_id').value = this.dataset.id;
                                        document.getElementById('edit_q_module_id').value = this.dataset.mod;
                                        document.getElementById('edit_q_text').value = this.dataset.qtext;
                                        document.getElementById('edit_q_content_text').value = this.dataset.ctext;
                                        document.getElementById('edit_q_content_layout').value = this.dataset.clayout;
                                        document.getElementById('edit_q_opt_a').value = this.dataset.opta;
                                        document.getElementById('edit_q_opt_b').value = this.dataset.optb;
                                        document.getElementById('edit_q_opt_c').value = this.dataset.optc;
                                        document.getElementById('edit_q_opt_d').value = this.dataset.optd;
                                        document.getElementById('edit_correct_' + this.dataset.correct).checked = true;
                                        openModal('editQuestionModal');" 
                                        data-id="<?php echo $q['id']; ?>"
                                        data-mod="<?php echo $q['module_id']; ?>"
                                        data-qtext="<?php echo htmlspecialchars($q['question_text'] ?? ''); ?>"
                                        data-ctext="<?php echo htmlspecialchars($q['content_text'] ?? ''); ?>"
                                        data-clayout="<?php echo htmlspecialchars($q['content_layout'] ?? 'standard'); ?>"
                                        data-opta="<?php echo htmlspecialchars($q['option_a'] ?? ''); ?>"
                                        data-optb="<?php echo htmlspecialchars($q['option_b'] ?? ''); ?>"
                                        data-optc="<?php echo htmlspecialchars($q['option_c'] ?? ''); ?>"
                                        data-optd="<?php echo htmlspecialchars($q['option_d'] ?? ''); ?>"
                                        data-correct="<?php echo htmlspecialchars($q['correct_answer'] ?? ''); ?>"
                                        class="text-xs bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold py-1.5 px-3 rounded-lg shadow-sm transition-colors">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">No questions added yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="announcements" class="tab-content max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-jrmsuNavy">Announcement for Students</h2>
                    <p class="text-sm text-slate-500 mt-1">Post and manage news for all students.</p>
                </div>
                <button onclick="openModal('announcementModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all duration-300 flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                    <i class="fas fa-plus-circle text-lg"></i> Post New Announcement
                </button>
            </div>

            <div class="space-y-6">
                <?php if ($announcements_result && $announcements_result->num_rows > 0): $announcements_result->data_seek(0); while($ann = $announcements_result->fetch_assoc()): ?>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this announcement?');" class="mt-3 text-right">
                        <input type="hidden" name="action" value="delete_announcement">
                        <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                        <button type="submit" class="inline-flex items-center text-red-500 hover:text-red-700 text-sm font-semibold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    </form>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start sm:items-center gap-4 mb-4 flex-col sm:flex-row">
                            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="fas fa-bullhorn text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-jrmsuNavy"><?php echo htmlspecialchars($ann['title']); ?></h3>
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-1 mt-1">
                                    <i class="fas fa-clock"></i> Posted on <?php echo date("F d, Y h:i A", strtotime($ann['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-slate-600 leading-relaxed text-sm sm:text-base whitespace-pre-line sm:ml-16">
                            <?php echo htmlspecialchars($ann['content']); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="py-16 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-bell-slash text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700">No Announcements Yet</h3>
                    <p class="text-slate-500 mt-2">There are currently no announcements to display.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="reports" class="tab-content max-w-7xl mx-auto">
            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden flex flex-col h-full max-h-[calc(100vh-8rem)]">
                <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 shrink-0">
                    <div>
                        <h2 class="text-2xl font-bold text-jrmsuNavy">Student Performance Log</h2>
                        <p class="text-sm text-slate-500 mt-1">Review analytical data for submitted quizzes.</p>
                    </div>
                    <button onclick="window.print()" class="text-sm bg-white border-2 border-slate-200 text-slate-700 hover:border-jrmsuNavy hover:text-jrmsuNavy font-bold py-2.5 px-6 rounded-xl shadow-sm transition-all duration-300 flex items-center gap-2">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-100 border-b border-slate-200 sticky top-0 z-10 font-bold tracking-wider">
                            <tr>
                                <th class="px-8 py-5">Student Name</th>
                                <th class="px-8 py-5">Module Subject</th>
                                <th class="px-8 py-5">Final Score</th>
                                <th class="px-8 py-5">Date & Time Taken</th>
                                <th class="px-8 py-5">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php if ($student_results->num_rows > 0): while($res = $student_results->fetch_assoc()): 
                                $percentage = ($res['score'] / $res['total_items']) * 100;
                                $passed = $percentage >= 50;
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-8 py-4 font-bold text-jrmsuNavy"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold"><?php echo substr($res['student_name'], 0, 1); ?></div><?php echo htmlspecialchars($res['student_name']); ?></div></td>
                                <td class="px-8 py-4 font-medium"><?php echo htmlspecialchars($res['module_title']); ?></td>
                                <td class="px-8 py-4"><span class="text-lg font-black <?php echo $passed ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $res['score']; ?></span> / <?php echo $res['total_items']; ?></td>
                                <td class="px-8 py-4"><?php echo date("M d, Y h:i A", strtotime($res['date_taken'])); ?></td>
                                <td class="px-8 py-4"><span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full border <?php echo $passed ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?>"><i class="fas <?php echo $passed ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> <?php echo $passed ? 'PASSED' : 'FAILED'; ?></span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="px-8 py-16 text-center text-slate-500">No Assessment Data Yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<div id="moduleModal" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-jrmsuNavyDark/70 backdrop-blur-sm" onclick="closeModal('moduleModal')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
        <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:max-w-lg w-full pointer-events-auto border border-slate-100">
            <div class="bg-jrmsuNavy px-6 py-5 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-file-signature text-jrmsuGold"></i> Publish Module</h3>
                <button onclick="closeModal('moduleModal')" class="text-white/60 hover:text-white hover:bg-white/10 rounded-full w-8 h-8 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-8">
                <input type="hidden" name="action" value="add_module">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Module Title</label>
                    <input type="text" name="module_title" required class="w-full bg-slate-50 border border-slate-300 text-sm rounded-xl p-3.5 outline-none">
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Description</label>
                    <textarea name="module_desc" rows="3" required class="w-full bg-slate-50 border border-slate-300 text-sm rounded-xl p-3.5 outline-none resize-none"></textarea>
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Attachment (PDF)</label>
                    <input type="file" name="module_file" accept=".pdf" class="block w-full text-sm border border-slate-300 rounded-xl bg-slate-50 p-2">
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="closeModal('moduleModal')" class="w-1/3 rounded-xl border-2 border-slate-200 px-4 py-3 bg-white text-sm font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="w-2/3 rounded-xl border border-transparent shadow-md px-4 py-3 bg-jrmsuGold text-sm font-bold text-jrmsuNavy">Publish Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="announcementModal" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-jrmsuNavyDark/70 backdrop-blur-sm" onclick="closeModal('announcementModal')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
        <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:max-w-lg w-full pointer-events-auto border border-slate-100">
            <div class="bg-blue-600 px-6 py-5 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-bullhorn text-white"></i> Create Announcement</h3>
                <button onclick="closeModal('announcementModal')" class="text-white/60 hover:text-white hover:bg-white/10 rounded-full w-8 h-8 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="p-8">
                <input type="hidden" name="action" value="add_announcement">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Announcement Title</label>
                    <input type="text" name="ann_title" required placeholder="e.g. Schedule Change, Final Exam Info" class="w-full bg-slate-50 border border-slate-300 text-sm rounded-xl p-3.5 outline-none">
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Content</label>
                    <textarea name="ann_content" rows="5" required placeholder="Type your message here..." class="w-full bg-slate-50 border border-slate-300 text-sm rounded-xl p-3.5 outline-none resize-none"></textarea>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="closeModal('announcementModal')" class="w-1/3 rounded-xl border-2 border-slate-200 px-4 py-3 bg-white text-sm font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="w-2/3 rounded-xl border border-transparent shadow-md px-4 py-3 bg-blue-600 text-white text-sm font-bold">Post to Students</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModuleModal" class="fixed inset-0 z-[65] hidden">
    <div class="fixed inset-0 bg-jrmsuNavyDark/70 backdrop-blur-sm" onclick="closeModal('editModuleModal')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
        <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:max-w-lg w-full pointer-events-auto border border-slate-100">
            <div class="bg-jrmsuNavy px-6 py-5 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-edit text-jrmsuGold"></i> Edit Module</h3>
                <button onclick="closeModal('editModuleModal')" class="text-white/60 hover:text-white hover:bg-white/10 rounded-full w-8 h-8 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-8">
                <input type="hidden" name="action" value="update_module">
                <input type="hidden" name="module_id" id="edit_mod_id">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Module Title</label>
                    <input type="text" name="module_title" id="edit_mod_title" required class="w-full bg-slate-50 border border-slate-300 text-sm rounded-xl p-3.5 outline-none">
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Description</label>
                    <textarea name="module_desc" id="edit_mod_desc" rows="3" required class="w-full bg-slate-50 border border-slate-300 text-sm rounded-xl p-3.5 outline-none resize-none"></textarea>
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-jrmsuNavy mb-2 uppercase">Replace Attachment (PDF) <span class="text-xs font-normal normal-case text-slate-500">- Leave blank to keep current</span></label>
                    <input type="file" name="module_file" accept=".pdf" class="block w-full text-sm border border-slate-300 rounded-xl bg-slate-50 p-2">
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="closeModal('editModuleModal')" class="w-1/3 rounded-xl border-2 border-slate-200 px-4 py-3 bg-white text-sm font-bold text-slate-700">Cancel</button>
                    <button type="submit" class="w-2/3 rounded-xl border border-transparent shadow-md px-4 py-3 bg-jrmsuGold text-sm font-bold text-jrmsuNavy">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editQuestionModal" class="fixed inset-0 z-[70] hidden overflow-y-auto">
    <div class="fixed inset-0 bg-jrmsuNavyDark/70 backdrop-blur-sm" onclick="closeModal('editQuestionModal')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
        <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:max-w-2xl w-full pointer-events-auto border border-slate-100 mt-10 mb-10">
            <div class="bg-jrmsuNavy px-6 py-5 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-edit text-jrmsuGold"></i> Edit Question</h3>
                <button onclick="closeModal('editQuestionModal')" class="text-white/60 hover:text-white hover:bg-white/10 rounded-full w-8 h-8 flex items-center justify-center"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" value="update_question">
                <input type="hidden" name="question_id" id="edit_q_id">
                
                <div class="space-y-4">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase">Module</label>
                        <select name="module_id" id="edit_q_module_id" required class="w-full bg-white border border-slate-300 text-sm rounded-lg p-2.5 outline-none">
                            <?php $my_modules->data_seek(0); while($m = $my_modules->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase">Detailed Content (Optional)</label>
                        <textarea name="content_text" id="edit_q_content_text" rows="3" class="w-full bg-white border border-slate-300 text-sm rounded-lg p-3 outline-none"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase">Content Layout</label>
                            <select name="content_layout" id="edit_q_content_layout" class="w-full bg-white border border-slate-300 text-sm rounded-lg p-2 outline-none">
                                <option value="standard">Standard (Text Only)</option>
                                <option value="hero">Hero (Image Top, Text Below)</option>
                                <option value="image_left">Split View (Image Left, Text Right)</option>
                                <option value="image_right">Split View (Text Left, Image Right)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase">Replace Image (Optional)</label>
                            <input type="file" name="content_image" accept="image/*" class="block w-full text-xs border border-slate-300 rounded-lg p-1.5 bg-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase">Question Prompt</label>
                        <textarea name="question_text" id="edit_q_text" rows="2" required class="w-full bg-white border border-slate-300 text-sm rounded-lg p-3 outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-jrmsuNavy mb-2 uppercase">Replace Video (Optional)</label>
                        <input type="file" name="question_video" accept="video/*" class="block w-full text-xs border border-slate-300 rounded-lg p-1.5 bg-white">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php $opts=['A','B','C','D']; foreach($opts as $opt): $field = "option_" . strtolower($opt); ?>
                        <div class="flex shadow-sm rounded-lg overflow-hidden border border-slate-300">
                            <div class="flex items-center px-3 bg-slate-100 border-r border-slate-300">
                                <input type="radio" name="correctAnswer" id="edit_correct_<?php echo $opt; ?>" value="<?php echo $opt; ?>" required class="w-4 h-4 text-jrmsuNavy">
                            </div>
                            <input type="text" name="<?php echo $field; ?>" id="edit_q_opt_<?php echo strtolower($opt); ?>" required class="bg-white block w-full text-sm p-2 outline-none">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="closeModal('editQuestionModal')" class="w-1/3 rounded-xl border-2 border-slate-200 px-4 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="w-2/3 rounded-xl border border-transparent shadow-md px-4 py-2.5 bg-jrmsuGold text-sm font-bold text-jrmsuNavy hover:bg-jrmsuGoldLight">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab Navigation
    const navButtons = document.querySelectorAll('.nav-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const pageTitle = document.getElementById('pageTitle');
    const titles = { 'dashboard': 'Faculty Dashboard', 'modules': 'Course Modules', 'quizzes': 'Content Management', 'announcements': 'Announcement', 'reports': 'Student Reports' };

    // Notification Badge Logic
    const totalAnnouncements = <?php echo $total_announcements; ?>;
    const viewedAnnouncements = localStorage.getItem('viewedAnnouncements_<?php echo $instructor_id; ?>') || 0;
    const badge = document.getElementById('announcementBadge');

    // Show the badge if there are more announcements in the DB than they've seen
    if (totalAnnouncements > viewedAnnouncements) {
        badge.classList.remove('hidden');
    }

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            
            // Clear badge if they click announcements
            if(target === 'announcements') {
                localStorage.setItem('viewedAnnouncements_<?php echo $instructor_id; ?>', totalAnnouncements);
                if(badge) badge.classList.add('hidden');
            }

            tabContents.forEach(content => { content.classList.remove('active'); if(content.id === target) setTimeout(() => content.classList.add('active'), 10); });
            navButtons.forEach(b => { b.classList.remove('bg-jrmsuGold', 'text-jrmsuNavy', 'font-semibold', 'shadow-md'); b.classList.add('text-white/70', 'hover:bg-white/10', 'hover:text-white', 'font-medium'); });
            btn.classList.add('bg-jrmsuGold', 'text-jrmsuNavy', 'font-semibold', 'shadow-md');
            btn.classList.remove('text-white/70', 'hover:bg-white/10', 'hover:text-white', 'font-medium');
            pageTitle.textContent = titles[target];
            if(window.innerWidth < 768) toggleSidebar();
        });
    });

    // Mobile Sidebar
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');
    function toggleSidebar() {
        const isOpen = !sidebar.classList.contains('-translate-x-full');
        if (isOpen) { sidebar.classList.add('-translate-x-full'); mobileOverlay.classList.add('hidden'); } 
        else { sidebar.classList.remove('-translate-x-full'); mobileOverlay.classList.remove('hidden'); }
    }
    document.getElementById('openSidebar').addEventListener('click', toggleSidebar);
    document.getElementById('closeSidebar').addEventListener('click', toggleSidebar);
    mobileOverlay.addEventListener('click', toggleSidebar);

    // Modal Control
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = 'auto'; }
    
    // Alerts
    window.addEventListener('DOMContentLoaded', () => {
        const alertBox = document.getElementById('alertBox');
        if(alertBox) { setTimeout(() => { alertBox.style.opacity = '0'; alertBox.style.transform = 'translateY(-20px)'; alertBox.style.transition = 'all 0.5s ease'; setTimeout(() => alertBox.style.display = 'none', 500); }, 5000); }
    });

    document.addEventListener('DOMContentLoaded', () => {
    // Find the Add Question Modal's Form
    const actionInputTarget = document.querySelector('form input[name="action"][value="add_question"]');
    if (!actionInputTarget) return; 
    
    const addQuestionForm = actionInputTarget.closest('form');
    
    // Add "Next Question" button dynamically
    const submitBtn = addQuestionForm.querySelector('button[type="submit"]');
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = "bg-indigo-600 text-white px-4 py-2 rounded-lg ml-2 hover:bg-indigo-700 font-medium text-sm transition-colors";
    nextBtn.textContent = 'Next Question (Save locally)';
    submitBtn.parentNode.insertBefore(nextBtn, submitBtn);
    
    // Hidden container to hold our local array of questions
    const hiddenContainer = document.createElement('div');
    hiddenContainer.id = 'hiddenQuestionsContainer';
    hiddenContainer.style.display = 'none';
    addQuestionForm.appendChild(hiddenContainer);

    let questionCount = 1;
    const actionInput = addQuestionForm.querySelector('input[name="action"]');

    // Function to move the current form values into the hidden array container
    function storeCurrentQuestionLocally() {
        const fieldsToMove = ['module_id', 'question_text', 'content_text', 'content_layout', 'option_a', 'option_b', 'option_c', 'option_d', 'correctAnswer'];
        
        fieldsToMove.forEach(field => {
            const input = addQuestionForm.querySelector(`[name="${field}"]`);
            if (input) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `${field}[]`;
                hiddenInput.value = input.value;
                hiddenContainer.appendChild(hiddenInput);
            }
        });

        // Handle File inputs (moves the actual file to hidden div and replaces UI with a fresh input)
        const fileFields = ['content_image', 'question_video'];
        fileFields.forEach(field => {
            const fileInput = addQuestionForm.querySelector(`input[name="${field}"]`);
            if (fileInput) {
                const newFileInput = fileInput.cloneNode(true);
                newFileInput.value = ''; // clear for next use
                fileInput.parentNode.insertBefore(newFileInput, fileInput);
                
                fileInput.name = `${field}[]`;
                fileInput.style.display = 'none';
                hiddenContainer.appendChild(fileInput);
            }
        });
    }

    // Action when they click "Next Question"
    nextBtn.addEventListener('click', () => {
        const qText = addQuestionForm.querySelector('[name="question_text"]').value;
        if (!qText.trim()) {
            alert('Please fill out the question text before adding the next one.');
            return;
        }

        // Change action so PHP triggers the Bulk loop code instead of the single loop
        actionInput.value = 'add_multiple_questions';
        
        storeCurrentQuestionLocally();
        
        // Clear text inputs manually so we don't accidentally clear hidden module_id data
        const textFieldsToClear = ['question_text', 'content_text', 'option_a', 'option_b', 'option_c', 'option_d'];
        textFieldsToClear.forEach(field => {
            const input = addQuestionForm.querySelector(`[name="${field}"]`);
            if(input) input.value = '';
        });
        
        // Reset select dropdowns
        const selectFields = ['content_layout', 'correctAnswer'];
        selectFields.forEach(field => {
            const select = addQuestionForm.querySelector(`[name="${field}"]`);
            if(select) select.selectedIndex = 0;
        });
        
        questionCount++;
        alert(`Question ${questionCount - 1} stored. You can now create Question ${questionCount}.`);
    });

    // Capture final submit (When they finally hit the real Save button)
    addQuestionForm.addEventListener('submit', (e) => {
        if (actionInput.value === 'add_multiple_questions') {
            // Include the very last question that is currently typed in the visible form
            const qText = addQuestionForm.querySelector('[name="question_text"]').value;
            if (qText.trim()) {
                storeCurrentQuestionLocally();
                
                // Disable visible fields so they don't override the array we just created
                const fieldsToDisable = ['question_text', 'content_text', 'content_layout', 'option_a', 'option_b', 'option_c', 'option_d', 'correctAnswer', 'content_image', 'question_video'];
                fieldsToDisable.forEach(field => {
                    const input = addQuestionForm.querySelector(`[name="${field}"]`);
                    if(input) input.disabled = true;
                });
            }
        }
    });
});
</script>

</body>
</html>