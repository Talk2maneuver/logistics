<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Query user
        $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user->password)) {
            // Success
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_role'] = $user->role;
            
            // Redirect based on role
            if ($user->role == 'admin' || $user->role == 'manager') {
                header("Location: admin/index.php");
            } else if ($user->role == 'driver') {
                header("Location: driver/index.php");
            } else {
                header("Location: customer/index.php");
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics Platform - Login</title>
    <!-- Use FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="login-container">
        <div class="logo-container">
            <i class="fa-solid fa-truck-fast logo-icon"></i>
            <h1 class="logo-text">MurgLogistics</h1>
            <p class="logo-subtext">Sign in to your dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="admin@logistics.com" required autocomplete="email">
                <i class="fa-regular fa-envelope input-icon"></i>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <button type="submit" class="submit-btn">
                Authenticate <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>
        
        <div class="register-link">
            <p>Don't have an account? <a href="register.php" class="register-btn">Create Account</a></p>
        </div>
    </div>
</body>
</html>
