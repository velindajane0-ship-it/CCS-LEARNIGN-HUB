<?php
session_start();
include 'db_connect.php';

// --- 1. HANDLE FORM SUBMISSIONS ---
 
// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_student') {
    $name = $_POST['student_name'];
    $sid = $_POST['student_id'];
    $course = $_POST['course_year'];
    $email = $_POST['student_email'];

    $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, course_year, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $sid, $name, $course, $email);
    
    try {
        if ($stmt->execute()) {
            $msg = "Student added successfully!";
            $msg_type = "success";
        }
    } catch (mysqli_sql_exception $e) {
        // MySQL Error 1062 is "Duplicate entry"
        if ($e->getCode() == 1062) {
            $msg = "Error: A student with ID '$sid' is already registered.";
        } else {
            // Catch any other database errors
            $msg = "Database Error: " . $e->getMessage();
        }
        $msg_type = "danger";
    }
    $stmt->close();
}

// Handle Update Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_student') {
    $id = intval($_POST['id']);
    $name = $_POST['student_name'];
    $sid = $_POST['student_id'];
    $course = $_POST['course_year'];
    $email = $_POST['student_email'];
    $password = $_POST['student_password'];

    try {
        if (!empty($password)) {
            // If password field is not empty, update password as well
            $pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET student_id=?, full_name=?, course_year=?, email=?, password=? WHERE id=?");
            $stmt->bind_param("sssssi", $sid, $name, $course, $email, $pass, $id);
        } else {
            // Update without changing the password
            $stmt = $conn->prepare("UPDATE students SET student_id=?, full_name=?, course_year=?, email=? WHERE id=?");
            $stmt->bind_param("ssssi", $sid, $name, $course, $email, $id);
        }

        if ($stmt->execute()) {
            $msg = "Student updated successfully!";
            $msg_type = "success";
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $msg = "Error: A student with ID '$sid' is already registered.";
        } else {
            $msg = "Database Error: " . $e->getMessage();
        }
        $msg_type = "danger";
    }
}

// Handle Delete Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_student') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Student deleted successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error deleting student: " . $conn->error;
            $msg_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Add Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_teacher') {
    $name = $_POST['teacher_name'];
    $tid = $_POST['teacher_id'];
    $dept = $_POST['department'];
    $email = $_POST['teacher_email'];
    // Hash the password before storing it for security
    $pass = password_hash($_POST['teacher_password'], PASSWORD_DEFAULT); 

    $stmt = $conn->prepare("INSERT INTO instructor (instructor_id, full_name, department, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $tid, $name, $dept, $email, $pass);
    
    if ($stmt->execute()) {
        $msg = "Instructor registered successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error: " . $conn->error;
        $msg_type = "danger";
    }
    $stmt->close();
}

// Handle Update Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_teacher') {
    $id = intval($_POST['id']);
    $name = $_POST['teacher_name'];
    $tid = $_POST['teacher_id'];
    $dept = $_POST['department'];
    $email = $_POST['teacher_email'];
    $password = $_POST['teacher_password'];

    try {
        if (!empty($password)) {
            // Update including new password
            $pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE instructor SET instructor_id=?, full_name=?, department=?, email=?, password=? WHERE id=?");
            $stmt->bind_param("sssssi", $tid, $name, $dept, $email, $pass, $id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE instructor SET instructor_id=?, full_name=?, department=?, email=? WHERE id=?");
            $stmt->bind_param("ssssi", $tid, $name, $dept, $email, $id);
        }

        if ($stmt->execute()) {
            $msg = "Instructor updated successfully!";
            $msg_type = "success";
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $msg = "Error: An instructor with ID '$tid' is already registered.";
        } else {
            $msg = "Database Error: " . $e->getMessage();
        }
        $msg_type = "danger";
    }
}

// Handle Delete Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_teacher') {
    $id = intval($_POST['id']);
    
    // --- ADDED CODE: Delete all modules created by this teacher ---
    $stmt_modules = $conn->prepare("DELETE FROM modules WHERE instructor_id = ?");
    if ($stmt_modules) {
        $stmt_modules->bind_param("i", $id);
        $stmt_modules->execute();
        $stmt_modules->close();
    }
    // --------------------------------------------------------------

    $stmt = $conn->prepare("DELETE FROM instructor WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Instructor deleted successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error deleting instructor: " . $conn->error;
            $msg_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Add Announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_announcement') {
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];
    $date_posted = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_at) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $title, $content, $date_posted);
        if ($stmt->execute()) {
            $msg = "Announcement posted successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error: " . $conn->error;
            $msg_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Delete Announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_announcement') {
    $announcement_id = intval($_POST['announcement_id']);

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id);
        if ($stmt->execute()) {
            $msg = "Announcement deleted successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error deleting announcement: " . $conn->error;
            $msg_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        // Assuming user ID 1 for this demo
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = 1");
        $stmt->bind_param("s", $hashed_password);
        if($stmt->execute()){
            $msg = "Password updated.";
            $msg_type = "success";
        }
    } else {
        $msg = "Passwords do not match.";
        $msg_type = "danger";
    }
}

// --- 2. FETCH DATA FOR VIEW ---

// Dashboard Stats
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;
$total_instructor = $conn->query("SELECT COUNT(*) as count FROM instructor")->fetch_assoc()['count'] ?? 0;
// Use @ to suppress errors if table doesn't exist yet during development
$total_announcements = @$conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'] ?? 0; 

// Fetch Students
$students_result = $conn->query("SELECT * FROM students ORDER BY id DESC");

// Fetch Teachers
$instructor_result = $conn->query("SELECT * FROM instructor ORDER BY id DESC");

// Fetch Announcements
$announcements_result = @$conn->query("SELECT * FROM announcements ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CCS HUB</title>
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
                <i class="fas fa-layer-group text-jrmsuNavy text-xl"></i>
            </div>
            <div>
                <h2 class="text-white font-bold text-lg leading-tight tracking-wide">CCS LEARN HUB</h2>
                <p class="text-jrmsuGold text-xs font-medium uppercase tracking-widest">Admin Portal</p>
            </div>
        </div>
        <button id="closeSidebar" class="md:hidden text-white/70 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-6">
        <nav class="space-y-2 px-4">
            <button data-target="dashboard" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 bg-jrmsuGold text-jrmsuNavy font-semibold shadow-md">
                <i class="fas fa-tachometer-alt w-5 text-center text-lg"></i> 
                <span>Dashboard</span>
            </button>
            <button data-target="teachers" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-chalkboard-teacher w-5 text-center text-lg"></i> 
                <span>Instructor</span>
            </button>
            <button data-target="students" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-users w-5 text-center text-lg"></i> 
                <span>Students</span>
            </button>
            <button data-target="announcements" class="nav-btn w-full flex items-center gap-4 px-4 py-3.5 text-sm rounded-xl transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white font-medium">
                <i class="fas fa-bullhorn w-5 text-center text-lg"></i> 
                <span>Announcements</span>
            </button>
        </nav>
    </div>

    <div class="p-4 border-t border-white/10">
        <a href="logout.php" class="flex items-center justify-center gap-3 w-full px-4 py-3 text-sm font-medium rounded-xl text-red-300 bg-red-500/10 hover:bg-red-500/20 hover:text-red-200 transition-colors border border-red-500/20">
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
                <div class="text-sm font-bold text-jrmsuNavy">Admin User</div>
                <div class="text-xs text-slate-500 font-medium">System Administrator</div>
            </div>
            <div class="relative cursor-pointer hover:opacity-80 transition-opacity" onclick="openModal('profileModal')">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=002855&color=EAA221&bold=true" alt="Profile" class="w-11 h-11 rounded-full shadow-md ring-2 ring-jrmsuGold ring-offset-2">
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
                        <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-jrmsuGold text-xs font-semibold tracking-wider mb-3 backdrop-blur-sm border border-white/10">SYSTEM OVERVIEW</span>
                        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Welcome to <span class="text-jrmsuGold">CCS LEARN HUB</span></h2>
                        <p class="text-white/80 text-sm sm:text-base max-w-xl leading-relaxed">Your Administrative Manager. Oversee the learning ecosystem, track users, and manage student and faculty accounts.</p>
                    </div>
                    <div class="hidden md:flex items-center justify-center">
                        <i class="fas fa-server text-8xl text-white/10 transform rotate-12"></i>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-5 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Students</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_students); ?></h3>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-5 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Instructors</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_instructor); ?></h3>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-5 hover:shadow-md transition-shadow group">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Announcements</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($total_announcements); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div id="teachers" class="tab-content max-w-7xl mx-auto">
            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden flex flex-col h-full max-h-[calc(100vh-8rem)]">
                <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 shrink-0">
                    <div>
                        <h2 class="text-2xl font-bold text-jrmsuNavy">Instructor Directory</h2>
                        <p class="text-sm text-slate-500 mt-1">Manage faculty accounts and access.</p>
                    </div>
                    <button onclick="openModal('addTeacherModal')" class="bg-jrmsuGold hover:bg-jrmsuGoldLight text-jrmsuNavy font-bold py-2.5 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                        <i class="fas fa-user-plus text-lg"></i> Add Instructor
                    </button>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-100 border-b border-slate-200 sticky top-0 z-10 font-bold tracking-wider">
                            <tr>
                                <th scope="col" class="px-8 py-5">Instructor ID</th>
                                <th scope="col" class="px-8 py-5">Name</th>
                                <th scope="col" class="px-8 py-5">Department</th>
                                <th scope="col" class="px-8 py-5">Email</th>
                                <th scope="col" class="px-8 py-5">Status</th>
                                <th scope="col" class="px-8 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php if ($instructor_result && $instructor_result->num_rows > 0): while($row = $instructor_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors duration-150 group">
                                <td class="px-8 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($row['instructor_id']); ?></td>
                                <td class="px-8 py-4 font-bold text-jrmsuNavy whitespace-nowrap group-hover:text-jrmsuGold transition-colors">
                                    <div class="flex items-center gap-3">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=002855&color=fff" class="w-8 h-8 rounded-full"> 
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </div>
                                </td>
                                <td class="px-8 py-4 font-medium text-slate-700"><?php echo htmlspecialchars($row['department']); ?></td>
                                <td class="px-8 py-4 text-slate-500"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="px-8 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full border bg-green-50 text-green-700 border-green-200">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" onclick='openEditTeacherModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)' class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 flex items-center justify-center transition-colors" title="Edit Instructor">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this instructor?');" class="m-0">
                                            <input type="hidden" name="action" value="delete_teacher">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 flex items-center justify-center transition-colors" title="Delete Instructor">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="6" class="px-8 py-8 text-center text-slate-500">No instructor found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="students" class="tab-content max-w-7xl mx-auto">
            <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden flex flex-col h-full max-h-[calc(100vh-8rem)]">
                <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 shrink-0">
                    <div>
                        <h2 class="text-2xl font-bold text-jrmsuNavy">Student Directory</h2>
                        <p class="text-sm text-slate-500 mt-1">Manage enrolled student profiles.</p>
                    </div>
                    <button onclick="openModal('addStudentModal')" class="bg-jrmsuGold hover:bg-jrmsuGoldLight text-jrmsuNavy font-bold py-2.5 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                        <i class="fas fa-user-plus text-lg"></i> Add Student
                    </button>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-100 border-b border-slate-200 sticky top-0 z-10 font-bold tracking-wider">
                            <tr>
                                <th scope="col" class="px-8 py-5">Student ID</th>
                                <th scope="col" class="px-8 py-5">Name</th>
                                <th scope="col" class="px-8 py-5">Course/Year</th>
                                <th scope="col" class="px-8 py-5">Email</th>
                                <th scope="col" class="px-8 py-5">Status</th>
                                <th scope="col" class="px-8 py-5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php if ($students_result && $students_result->num_rows > 0): while($row = $students_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors duration-150 group">
                                <td class="px-8 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td class="px-8 py-4 font-bold text-jrmsuNavy whitespace-nowrap group-hover:text-jrmsuGold transition-colors">
                                    <div class="flex items-center gap-3">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=e2e8f0&color=475569" class="w-8 h-8 rounded-full"> 
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </div>
                                </td>
                                <td class="px-8 py-4 font-medium text-slate-700"><?php echo htmlspecialchars($row['course_year']); ?></td>
                                <td class="px-8 py-4 text-slate-500"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="px-8 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full border bg-green-50 text-green-700 border-green-200">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" onclick='openEditStudentModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)' class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 flex items-center justify-center transition-colors" title="Edit Student">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this student?');" class="m-0">
                                            <input type="hidden" name="action" value="delete_student">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 flex items-center justify-center transition-colors" title="Delete Student">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="6" class="px-8 py-8 text-center text-slate-500">No students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="announcements" class="tab-content max-w-7xl mx-auto space-y-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-jrmsuNavy">Announcements</h2>
                    <p class="text-sm text-slate-500 mt-1">Broadcast messages to all users.</p>
                </div>
                <button onclick="openModal('addAnnouncementModal')" class="bg-jrmsuGold hover:bg-jrmsuGoldLight text-jrmsuNavy font-bold py-2.5 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                    <i class="fas fa-plus-circle text-lg"></i> New Post
                </button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <?php if ($announcements_result && $announcements_result->num_rows > 0): while($row = $announcements_result->fetch_assoc()): ?>
                <div class="bg-white p-6 rounded-3xl shadow-md border border-slate-200 hover:shadow-lg transition-shadow relative group">
                    <div class="absolute top-6 right-6 opacity-0 group-hover:opacity-100 transition-opacity">
                         <form method="POST" action="" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="action" value="delete_announcement">
                            <input type="hidden" name="announcement_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                                <i class="fas fa-trash-alt text-sm"></i>
                            </button>
                        </form>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-500 flex items-center justify-center text-lg shrink-0">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg leading-tight line-clamp-1 pr-8"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="text-xs text-slate-400 font-medium"><?php echo date('M d, Y - h:i A', strtotime($row['created_at'])); ?></p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed line-clamp-3"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                </div>
                <?php endwhile; else: ?>
                <div class="col-span-1 lg:col-span-3 bg-white p-10 rounded-3xl shadow-sm border border-slate-200 text-center flex flex-col items-center justify-center min-h-[300px]">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-inbox text-3xl text-slate-300"></i>
                    </div>
                    <p class="text-slate-500 font-medium">No announcements yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<div id="addStudentModal" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8 relative">
        <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-jrmsuNavy flex items-center gap-2">
                <i class="fas fa-user-plus text-jrmsuGold"></i> Add New Student
            </h3>
            <button type="button" onclick="closeModal('addStudentModal')" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add_student">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Student ID <span class="text-red-500">*</span></label>
                    <input type="text" name="student_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="e.g. 2023-0001">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="student_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Juan Dela Cruz">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Course & Year <span class="text-red-500">*</span></label>
                        <select name="course_year" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all appearance-none cursor-pointer">
                            <option value="">Select Course/Year</option>
                            <option value="BSIS - 2nd Year">BSIS - 2nd Year</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="student_email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="student@example.com">
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-5 border-t border-slate-100">
                <button type="button" onclick="closeModal('addStudentModal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight rounded-xl shadow-md hover:shadow-lg transition-all">Save Student</button>
            </div>
        </form>
    </div>
</div>

<div id="editStudentModal" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8 relative">
        <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-jrmsuNavy flex items-center gap-2">
                <i class="fas fa-user-edit text-blue-500"></i> Edit Student
            </h3>
            <button type="button" onclick="closeModal('editStudentModal')" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-5">
            <input type="hidden" name="action" value="update_student">
            <input type="hidden" name="id" id="edit_student_id_hidden">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Student ID <span class="text-red-500">*</span></label>
                    <input type="text" name="student_id" id="edit_student_id_input" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="student_name" id="edit_student_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Course & Year <span class="text-red-500">*</span></label>
                        <select name="course_year" id="edit_course_year" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all appearance-none cursor-pointer">
                            <option value="">Select Course/Year</option>
                            <option value="BSIS - 2nd Year">BSIS - 2nd Year</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="student_email" id="edit_student_email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Update Password</label>
                    <input type="password" name="student_password" id="edit_student_password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Enter new password">
                    <p class="text-xs text-slate-500 mt-1"><i class="fas fa-info-circle"></i> Leave blank to keep the current password.</p>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-5 border-t border-slate-100">
                <button type="button" onclick="closeModal('editStudentModal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md hover:shadow-lg transition-all">Update Student</button>
            </div>
        </form>
    </div>
</div>

<div id="addTeacherModal" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8 relative">
        <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-jrmsuNavy flex items-center gap-2">
                <i class="fas fa-chalkboard-teacher text-jrmsuGold"></i> Add New Instructor
            </h3>
            <button type="button" onclick="closeModal('addTeacherModal')" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add_teacher">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Instructor ID <span class="text-red-500">*</span></label>
                    <input type="text" name="teacher_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="e.g. FAC-2023-01">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="teacher_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Dr. Maria Clara">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Department <span class="text-red-500">*</span></label>
                    <select name="department" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all appearance-none cursor-pointer">
                        <option value="">Select Department</option>
                        <option value="CCS">College Of Computing Studies</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="teacher_email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="faculty@example.com">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Initial Password <span class="text-red-500">*</span></label>
                    <input type="password" name="teacher_password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Enter temporary password">
                    <p class="text-xs text-slate-500 mt-1"><i class="fas fa-info-circle"></i> They can change this upon first login.</p>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-5 border-t border-slate-100">
                <button type="button" onclick="closeModal('addTeacherModal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight rounded-xl shadow-md hover:shadow-lg transition-all">Save Instructor</button>
            </div>
        </form>
    </div>
</div>

<div id="editTeacherModal" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8 relative">
        <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-jrmsuNavy flex items-center gap-2">
                <i class="fas fa-user-edit text-blue-500"></i> Edit Instructor
            </h3>
            <button type="button" onclick="closeModal('editTeacherModal')" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-5">
            <input type="hidden" name="action" value="update_teacher">
            <input type="hidden" name="id" id="edit_teacher_id_hidden">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Instructor ID <span class="text-red-500">*</span></label>
                    <input type="text" name="teacher_id" id="edit_teacher_id_input" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="teacher_name" id="edit_teacher_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Department <span class="text-red-500">*</span></label>
                    <select name="department" id="edit_department" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all appearance-none cursor-pointer">
                        <option value="">Select Department</option>
                        <option value="CCS">College Of Computing Studies</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="teacher_email" id="edit_teacher_email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Update Password</label>
                    <input type="password" name="teacher_password" id="edit_teacher_password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Enter new password">
                    <p class="text-xs text-slate-500 mt-1"><i class="fas fa-info-circle"></i> Leave blank to keep the current password.</p>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-5 border-t border-slate-100">
                <button type="button" onclick="closeModal('editTeacherModal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md hover:shadow-lg transition-all">Update Instructor</button>
            </div>
        </form>
    </div>
</div>

<div id="addAnnouncementModal" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8 relative">
        <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-jrmsuNavy flex items-center gap-2">
                <i class="fas fa-bullhorn text-jrmsuGold"></i> Create Announcement
            </h3>
            <button type="button" onclick="closeModal('addAnnouncementModal')" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add_announcement">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Announcement Title <span class="text-red-500">*</span></label>
                    <input type="text" name="announcement_title" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="e.g. Midterm Exam Schedule">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Message Content <span class="text-red-500">*</span></label>
                    <textarea name="announcement_content" rows="5" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all resize-none" placeholder="Write your announcement here..."></textarea>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-5 border-t border-slate-100">
                <button type="button" onclick="closeModal('addAnnouncementModal')" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2"><i class="fas fa-paper-plane"></i> Post</button>
            </div>
        </form>
    </div>
</div>

<div id="profileModal" class="fixed inset-0 bg-jrmsuNavyDark/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all my-8 relative">
        <div class="bg-jrmsuNavy p-6 flex flex-col items-center justify-center relative">
            <button type="button" onclick="closeModal('profileModal')" class="absolute top-4 right-4 text-white/50 hover:text-white bg-white/10 hover:bg-white/20 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
            <img src="https://ui-avatars.com/api/?name=Admin+User&background=EAA221&color=002855&bold=true" alt="Profile" class="w-20 h-20 rounded-full shadow-lg border-4 border-white/20 mb-3">
            <h3 class="text-xl font-bold text-white">Admin User</h3>
            <p class="text-jrmsuGold text-sm font-medium">System Administrator</p>
        </div>
        
        <form method="POST" action="" class="p-6 space-y-5">
            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-2 border-b border-slate-100 pb-2">Change Password</h4>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">New Password</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Enter new password">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-jrmsuGold/50 focus:border-jrmsuGold transition-all" placeholder="Confirm new password">
                </div>
            </div>
            
            <div class="mt-8 pt-5 border-t border-slate-100">
                <button type="submit" class="w-full px-5 py-3 text-sm font-bold text-jrmsuNavy bg-jrmsuGold hover:bg-jrmsuGoldLight rounded-xl shadow-md hover:shadow-lg transition-all">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab Navigation Logic
    const navBtns = document.querySelectorAll('.nav-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const pageTitle = document.getElementById('pageTitle');
    
    // Map button targets to clear titles
    const titleMap = {
        'dashboard': 'Dashboard Overview',
        'teachers': 'Teacher Management',
        'students': 'Student Management',
        'announcements': 'System Announcements'
    };

    navBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            
            // Update Navigation Styles
            navBtns.forEach(b => {
                b.classList.remove('bg-jrmsuGold', 'text-jrmsuNavy', 'font-semibold', 'shadow-md');
                b.classList.add('text-white/70', 'hover:bg-white/10', 'hover:text-white', 'font-medium');
            });
            btn.classList.add('bg-jrmsuGold', 'text-jrmsuNavy', 'font-semibold', 'shadow-md');
            btn.classList.remove('text-white/70', 'hover:bg-white/10', 'hover:text-white', 'font-medium');

            // Switch Content
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(target).classList.add('active');
            
            // Update Header Title
            if(pageTitle && titleMap[target]) {
                pageTitle.textContent = titleMap[target];
            }

            // Close sidebar on mobile after clicking
            if (window.innerWidth < 768) {
                toggleSidebar();
            }
        });
    });

    // Mobile Sidebar Logic
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

    // Modal Control Logic
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.body.style.overflow = 'hidden'; 
    }
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.style.overflow = 'auto'; 
    }

    // Edit Modal Data Population Functions
    function openEditTeacherModal(data) {
        document.getElementById('edit_teacher_id_hidden').value = data.id;
        document.getElementById('edit_teacher_id_input').value = data.instructor_id;
        document.getElementById('edit_teacher_name').value = data.full_name;
        document.getElementById('edit_department').value = data.department;
        document.getElementById('edit_teacher_email').value = data.email;
        document.getElementById('edit_teacher_password').value = ''; // Reset password field
        openModal('editTeacherModal');
    }

    function openEditStudentModal(data) {
        document.getElementById('edit_student_id_hidden').value = data.id;
        document.getElementById('edit_student_id_input').value = data.student_id;
        document.getElementById('edit_student_name').value = data.full_name;
        document.getElementById('edit_course_year').value = data.course_year;
        document.getElementById('edit_student_email').value = data.email;
        document.getElementById('edit_student_password').value = ''; // Reset password field
        openModal('editStudentModal');
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
            }, 5000); // Hide after 5 seconds
        }
    });
</script>

</body>
</html>
