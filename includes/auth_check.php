<?php
// includes/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: /logistics/login.php");
    exit;
}

// Optional: function to check role access
function enforceRole($requiredRole) {
    if ($_SESSION['user_role'] !== $requiredRole) {
        die("Unauthorized access. You do not have permission to view this page.");
    }
}
?>
