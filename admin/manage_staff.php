<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow admin and manager
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: drivers.php");
    exit;
}

// Fetch staff/driver details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $staff = $stmt->fetch();
    
    if (!$staff) {
        header("Location: drivers.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: drivers.php");
    exit;
}

$success = '';
$error = '';

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'reset_password') {
        try {
            $new_password = password_hash('staff123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $id]);
            $success = "Password reset successfully! Default password: staff123";
        } catch (PDOException $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
    elseif ($_POST['action'] == 'delete_member') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: drivers.php?msg=deleted");
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting member: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - MurgLogistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <i class="fa-solid fa-truck-fast"></i> MurgLogistics
        </a>
        
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="create_shipment.php"><i class="fa-solid fa-box"></i> Shipments</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck"></i> Fleet & Vehicles</a></li>
            <li><a href="drivers.php" class="active"><i class="fa-solid fa-users"></i> Staff & Drivers</a></li>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <li><a href="branches.php"><i class="fa-solid fa-building"></i> Branches</a></li>
            <?php endif; ?>
        </ul>

        <div class="logout-container">
            <a href="../logout.php" class="logout-btn">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1>Manage Staff Member</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage settings for: <strong><?= htmlspecialchars($staff->name) ?></strong></p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="drivers.php" style="color: var(--accent); text-decoration: none; cursor: pointer;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Staff List
                </a>
            </div>
        </header>

        <?php if ($success): ?>
            <div style="background-color: rgba(16, 185, 129, 0.2); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.2); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Staff Member Info Card -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Member Information</h2>
            </div>

            <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;">Full Name</label>
                    <p style="font-size: 1.125rem; font-weight: 500;"><?= htmlspecialchars($staff->name) ?></p>
                </div>
                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;">Email</label>
                    <p style="font-size: 1.125rem; font-weight: 500;"><?= htmlspecialchars($staff->email) ?></p>
                </div>
                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;">Role</label>
                    <p style="font-size: 1.125rem; font-weight: 500;">
                        <span style="background-color: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; display: inline-block;">
                            <?= ucfirst(htmlspecialchars($staff->role)) ?>
                        </span>
                    </p>
                </div>
                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;">Member Since</label>
                    <p style="font-size: 1.125rem; font-weight: 500;"><?= date('M d, Y', strtotime($staff->created_at)) ?></p>
                </div>
                <div>
                    <label style="display: block; color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;">Status</label>
                    <p style="font-size: 1.125rem; font-weight: 500;">
                        <span style="background-color: var(--success); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; display: inline-block;">
                            Active
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Management Actions -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Actions</h2>
            </div>

            <div style="padding: 1.5rem; display: grid; gap: 1rem;">
                <!-- Reset Password -->
                <div style="padding: 1rem; background: rgba(99, 102, 241, 0.1); border: 1px solid var(--border-color); border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem 0; color: white;">Reset Password</h3>
                    <p style="color: var(--text-muted); margin: 0 0 1rem 0; font-size: 0.875rem;">Reset the member's password to default (staff123)</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <button type="submit" onclick="return confirm('Are you sure you want to reset this member\'s password?')" style="padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-key"></i> Reset Password
                        </button>
                    </form>
                </div>

                <!-- Edit Member -->
                <div style="padding: 1rem; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--border-color); border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem 0; color: white;">Edit Details</h3>
                    <p style="color: var(--text-muted); margin: 0 0 1rem 0; font-size: 0.875rem;">Update member's name, email, or role</p>
                    <a href="edit_staff.php?id=<?= $staff->id ?>" style="padding: 0.5rem 1rem; background: var(--success); color: white; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block;">
                        <i class="fa-solid fa-edit"></i> Edit Details
                    </a>
                </div>

                <!-- Delete Member -->
                <div style="padding: 1rem; background: rgba(239, 68, 68, 0.1); border: 1px solid var(--border-color); border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem 0; color: white;">Delete Member</h3>
                    <p style="color: var(--text-muted); margin: 0 0 1rem 0; font-size: 0.875rem;">Permanently remove this member from the system</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_member">
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this member? This action cannot be undone.')" style="padding: 0.5rem 1rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-trash"></i> Delete Member
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
