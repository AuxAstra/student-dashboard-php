<?php
// Reviewed by Chardin Ardrei M. Alunan
// Final verification by Chadin Ardrei M. Alunan
// index.php - Login Page
// Concepts:
//   - $credentials  : global array holding valid username/password
//   - $_POST        : reading submitted form values
//   - $_SESSION     : storing the logged-in user after login
//   - header()      : redirecting to the dashboard after login
session_start();

if (isset($_SESSION['user'])) {
    header('Location: admin/index.php');
    exit;
}
 
// Load DB config + all classes
require_once 'config.php';
 
// Instantiate the User model, passing the DB connection
$userModel = new User($conn);
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
 
    // Use the User model to find the matching record
    $user = $userModel->findByUsername($input_username);
 
    // Check if user exists and password matches
    if ($user && $user['password'] === $input_password) {
 
        // Store essential user info in session
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'name'     => $user['name'],
            'username' => $user['username'],
            'student_no' => $user['student_no'],
        ];
 
        header('Location: admin/index.php');
        exit;
 
    } else {
        $error = "Invalid username or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="src/style/global.css">
    <link rel="stylesheet" href="src/style/login-styles.css">
</head>
<body class="login-page">
    <div class="login-wrapper">

    <!-- Brand -->
    <div class="login-brand">
        <div class="login-brand-icon"><i class="bi bi-tencent-qq"></i></div>
        <div class="login-brand-name">CIT11333Z</div>
    </div>

    <!-- Card -->
    <div class="login-card">
        <div class="login-card-header">
            <div class="login-card-title">Sign in to your account</div>
            <div class="login-card-sub">Enter your credentials to continue</div>
        </div>
        <div class="login-card-body">

            <!-- Error message -->
            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!--
                METHOD="POST"  — credentials go via $_POST, not the URL
                ACTION=""      — submits back to this same index.php
            -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="Enter your password"
                           required>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
    <div class="login-footer">
        PHP Student Dashboard - Midterm Project &copy; <?= date('Y') ?> CIT11333Z
    </div>
</div>
</body>
</html>