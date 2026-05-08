<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow admin
if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: branches.php");
    exit;
}

// Fetch branch details
try {
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$id]);
    $branch = $stmt->fetch();
    
    if (!$branch) {
        header("Location: branches.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: branches.php");
    exit;
}

$success = '';
$error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch_name = $_POST['branch_name'] ?? '';
    $branch_city = $_POST['branch_city'] ?? '';
    $branch_address = $_POST['branch_address'] ?? '';
    $branch_phone = $_POST['branch_phone'] ?? '';

    if (!empty($branch_name) && !empty($branch_city)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE branches SET name = ?, city = ?, address = ?, phone = ? WHERE id = ?
            ");
            $stmt->execute([$branch_name, $branch_city, $branch_address, $branch_phone, $id]);
            $success = "Branch updated successfully!";
            
            // Refresh branch data
            $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
            $stmt->execute([$id]);
            $branch = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating branch: " . $e->getMessage();
        }
    } else {
        $error = "Branch name and city are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Branch - MurgLogistics</title>
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
            <li><a href="drivers.php"><i class="fa-solid fa-users"></i> Staff & Drivers</a></li>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <li><a href="branches.php" class="active"><i class="fa-solid fa-building"></i> Branches</a></li>
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
                <h1>Edit Branch</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Update branch details</p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="branches.php" style="color: var(--accent); text-decoration: none; cursor: pointer;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Branches
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
                <h2>Update Branch Information</h2>
            </div>

            <form method="POST" style="display: grid; gap: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Branch Name</label>
                        <input type="text" name="branch_name" value="<?= htmlspecialchars($branch->name) ?>" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">City</label>
                        <input type="text" name="branch_city" value="<?= htmlspecialchars($branch->city) ?>" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Address</label>
                        <input type="text" name="branch_address" value="<?= htmlspecialchars($branch->address) ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Phone</label>
                        <input type="tel" name="branch_phone" value="<?= htmlspecialchars($branch->phone) ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Contact Number</label>
                        <input type="tel" value="<?= htmlspecialchars($branch->contact_number) ?>" disabled style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-muted);">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500;">Created Date</label>
                        <input type="text" value="<?= date('M d, Y', strtotime($branch->created_at)) ?>" disabled style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-muted);">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="padding: 0.75rem 2rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                    <a href="branches.php" style="padding: 0.75rem 2rem; background: var(--border-color); color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
