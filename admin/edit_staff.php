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

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'driver';
    $status = $_POST['status'] ?? 'active';

    if (!empty($name) && !empty($email)) {
        try {
            // Check if email is already used by another user
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            
            if ($check->rowCount() > 0) {
                $error = "Email already exists!";
            } else {
                // Map 'staff' to 'manager' for database compatibility
                $db_role = ($role === 'staff') ? 'manager' : $role;
                
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $email, $db_role, $status, $id]);
                $success = "Staff member updated successfully!";
                
                // Refresh staff data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $staff = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = "Error updating staff: " . $e->getMessage();
        }
    } else {
        $error = "Name and email are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - MurgLogistics</title>
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
                <h1>Edit Staff Member</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Update staff details</p>
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

        <!-- Edit Form -->
        <div class="table-container">
            <div class="table-header">
                <h2>Update Information</h2>
            </div>

            <form method="POST" style="display: grid; gap: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Full Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($staff->name) ?>" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($staff->email) ?>" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Role</label>
                        <select name="role" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                            <option value="driver" <?= $staff->role == 'driver' ? 'selected' : '' ?>>Driver</option>
                            <option value="staff" <?= $staff->role == 'manager' ? 'selected' : '' ?>>Staff</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Status</label>
                        <select name="status" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                            <option value="active" <?= ($staff->status ?? 'active') == 'active' ? 'selected' : '' ?>>Available</option>
                            <option value="inactive" <?= ($staff->status ?? 'active') == 'inactive' ? 'selected' : '' ?>>Unavailable</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Member Since</label>
                        <input type="text" value="<?= date('M d, Y', strtotime($staff->created_at)) ?>" disabled style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-muted);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="padding: 0.75rem 2rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                    <a href="drivers.php" style="padding: 0.75rem 2rem; background: var(--border-color); color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
