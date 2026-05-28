<?php
// admin/auth.php - Session Guard
// Include at the VERY TOP of every admin page.
// Redirects to login if no active session exists.
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Make logged-in user data available to every page
$logged_in_user = $_SESSION['user'];
