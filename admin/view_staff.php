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

// Fetch branch name if assigned
$branch_name = 'Not Assigned';
if ($staff->branch_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $stmt->execute([$staff->branch_id]);
        $branch = $stmt->fetch();
        if ($branch) {
            $branch_name = $branch->name;
        }
    } catch (Exception $e) {
        // Keep default if error
    }
}

// Fetch shipments count for this staff
$shipment_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shipments WHERE driver_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    $shipment_count = $result->total;
} catch (Exception $e) {
    // Keep 0 if error
}

// Fetch assigned vehicle if any
$vehicle_info = 'No vehicle assigned';
if ($staff->id) {
    try {
        $stmt = $pdo->prepare("SELECT license_plate, type, capacity FROM vehicles WHERE driver_id = ?");
        $stmt->execute([$staff->id]);
        $vehicle = $stmt->fetch();
        if ($vehicle) {
            $vehicle_info = "{$vehicle->license_plate} - {$vehicle->type} ({$vehicle->capacity} tons)";
        }
    } catch (Exception $e) {
        // Keep default if error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Staff - MurgLogistics</title>
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
                <h1>Staff Details</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Complete staff information and statistics</p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="drivers.php" style="color: var(--accent); text-decoration: none; cursor: pointer;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Staff List
                </a>
            </div>
        </header>

        <!-- Staff Overview -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Staff Information</h2>
                <div>
                    <a href="edit_staff.php?id=<?= $staff->id ?>" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Basic Information</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>Name:</strong> <?= htmlspecialchars($staff->name) ?></div>
                        <div><strong>Email:</strong> <?= htmlspecialchars($staff->email) ?></div>
                        <div><strong>Role:</strong> 
                            <span style="background-color: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                <?= ($staff->role == 'manager' ? 'Staff' : ucfirst(htmlspecialchars($staff->role))) ?>
                            </span>
                        </div>
                        <div><strong>Status:</strong> 
                            <span style="background-color: <?= ($staff->status ?? 'active') == 'active' ? 'var(--success)' : 'var(--danger)' ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                <?= ($staff->status ?? 'active') == 'active' ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div><strong>Branch:</strong> <?= htmlspecialchars($branch_name) ?></div>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Work Statistics</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="background: rgba(99, 102, 241, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent);"><?= $shipment_count ?></div>
                            <div style="color: var(--text-muted);">Total Shipments</div>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--success);"><?= $vehicle_info ?></div>
                            <div style="color: var(--text-muted);">Assigned Vehicle</div>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; color: var(--text-muted);">
                        <small>Member since: <?= date('M d, Y', strtotime($staff->created_at)) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
