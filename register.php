<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Email address already exists.';
                } else {
                    // Create new customer account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'customer', 'active', NOW())");
                    $stmt->execute([$name, $email, $hashed_password]);
                    
                    $success = 'Account created successfully! You can now login with your credentials.';
                    
                    // Clear form fields
                    $name = $email = $phone = '';
                }
            } catch (PDOException $e) {
                $error = 'Error creating account: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - MurgLogistics</title>
    <!-- Use FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .register-link {
            margin-top: 2rem;
            text-align: center;
        }
        
        .register-link p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .register-btn {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .register-btn:hover {
            color: var(--accent-hover);
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .password-strength.weak {
            color: var(--danger);
        }
        
        .password-strength.medium {
            color: var(--warning);
        }
        
        .password-strength.strong {
            color: var(--success);
        }
        
        /* Add scrollbar styles */
        body {
            overflow-y: auto;
            min-height: 100vh;
        }
        
        .login-container {
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        
        /* Custom scrollbar styling */
        .login-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .login-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .login-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
        
        .login-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="login-container">
        <div class="logo-container">
            <i class="fa-solid fa-truck-fast logo-icon"></i>
            <h1 class="logo-text">MurgLogistics</h1>
            <p class="logo-subtext">Create your customer account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required value="<?= htmlspecialchars($name ?? '') ?>" autocomplete="name">
                <i class="fa-solid fa-user input-icon"></i>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="customer@example.com" required value="<?= htmlspecialchars($email ?? '') ?>" autocomplete="email">
                <i class="fa-regular fa-envelope input-icon"></i>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+234 800 000 0000" value="<?= htmlspecialchars($phone ?? '') ?>" autocomplete="tel">
                <i class="fa-solid fa-phone input-icon"></i>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password" oninput="checkPasswordStrength(this.value)">
                <i class="fa-solid fa-lock input-icon"></i>
                <div id="password-strength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <button type="submit" class="submit-btn">
                Create Account <i class="fa-solid fa-user-plus" style="margin-left: 8px;"></i>
            </button>
        </form>
        
        <div class="register-link">
            <p>Already have an account? <a href="login.php" class="register-btn">Sign In</a></p>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthDiv.className = 'password-strength';
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
            } else if (strength <= 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.classList.add('weak');
            } else if (strength <= 4) {
                strengthDiv.textContent = 'Medium strength';
                strengthDiv.classList.add('medium');
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.classList.add('strong');
            }
        }
    </script>
</body>
</html>
