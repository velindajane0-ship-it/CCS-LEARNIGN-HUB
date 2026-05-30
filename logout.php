<?php
session_start(); // 1. Start/Access the current session

// 2. Clear all session variables
$_SESSION = array();

// 3. If you want to kill the session cookie (optional but recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session
session_destroy();

// 5. Redirect the user back to the login page
header("Location: admin_login.php");
exit();
?>
