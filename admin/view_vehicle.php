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
    header("Location: vehicles.php");
    exit;
}

// Fetch vehicle details
try {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        header("Location: vehicles.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: vehicles.php");
    exit;
}

// Fetch assigned branch name
$branch_name = 'Not Assigned';
if ($vehicle->branch_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $stmt->execute([$vehicle->branch_id]);
        $branch = $stmt->fetch();
        if ($branch) {
            $branch_name = $branch->name;
        }
    } catch (Exception $e) {
        // Keep default if error
    }
}

// Fetch assigned driver
$driver_name = 'Not Assigned';
if ($vehicle->id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = (SELECT driver_id FROM vehicles WHERE id = ?)");
        $stmt->execute([$id]);
        $driver = $stmt->fetch();
        if ($driver) {
            $driver_name = $driver->name;
        }
    } catch (Exception $e) {
        // Keep default if error
    }
}

// Fetch shipment statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shipments WHERE vehicle_id = ?");
    $stmt->execute([$id]);
    $shipment_stats = $stmt->fetch();
} catch (Exception $e) {
    $shipment_stats = (object) ['total' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Vehicle - MurgLogistics</title>
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
            <li><a href="vehicles.php" class="active"><i class="fa-solid fa-truck"></i> Fleet & Vehicles</a></li>
            <li><a href="drivers.php"><i class="fa-solid fa-users"></i> Staff & Drivers</a></li>
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
                <h1>Vehicle Details</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Complete vehicle information and assignment details</p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="vehicles.php" style="color: var(--accent); text-decoration: none; cursor: pointer;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Vehicles
                </a>
            </div>
        </header>

        <!-- Vehicle Overview -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Vehicle Information</h2>
                <div>
                    <a href="edit_vehicle.php?id=<?= $vehicle->id ?>" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Basic Information</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>License Plate:</strong> <?= htmlspecialchars($vehicle->license_plate) ?></div>
                        <div><strong>Type:</strong> <?= htmlspecialchars($vehicle->type) ?></div>
                        <div><strong>Capacity:</strong> <?= number_format($vehicle->capacity, 2) ?> kg</div>
                        <div><strong>Status:</strong> 
                            <span style="background-color: 
                                <?php 
                                    if ($vehicle->status == 'available') echo 'var(--success)';
                                    elseif ($vehicle->status == 'en_route') echo 'var(--warning)';
                                    else echo 'var(--danger)';
                                ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($vehicle->status))) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Assignment Details</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>Assigned Branch:</strong> <?= htmlspecialchars($branch_name) ?></div>
                        <div><strong>Assigned Driver:</strong> <?= htmlspecialchars($driver_name) ?></div>
                        <div><strong>Total Shipments:</strong> <?= $shipment_stats->total ?></div>
                    </div>
                    <div style="margin-top: 1rem; color: var(--text-muted);">
                        <small>Added to fleet: <?= date('M d, Y', strtotime($vehicle->created_at)) ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Shipments -->
        <div class="table-container">
            <div class="table-header">
                <h2>Recent Shipments</h2>
            </div>
            
            <?php
            try {
                $stmt = $pdo->prepare("SELECT s.*, u.name as driver_name FROM shipments s LEFT JOIN users u ON s.driver_id = u.id WHERE s.vehicle_id = ? ORDER BY s.created_at DESC LIMIT 5");
                $stmt->execute([$id]);
                $recent_shipments = $stmt->fetchAll();
            } catch (Exception $e) {
                $recent_shipments = [];
            }
            ?>
            
            <?php if (count($recent_shipments) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Tracking ID</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Driver</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Status</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_shipments as $shipment): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($shipment->tracking_number) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($shipment->driver_name ?? 'Not Assigned') ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="background-color: 
                                        <?php 
                                            if ($shipment->current_status == 'Delivered') echo 'var(--success)';
                                            elseif ($shipment->current_status == 'In Transit') echo 'var(--warning)';
                                            elseif ($shipment->current_status == 'Pending') echo 'var(--info)';
                                            else echo 'var(--muted)';
                                        ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?= htmlspecialchars($shipment->current_status) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= date('M d, Y', strtotime($shipment->created_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No shipments found for this vehicle.
                </p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
