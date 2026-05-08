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

$success = '';
$error = '';

// Handle status change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $new_status = $_POST['status'] ?? '';
        if (!empty($new_status)) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                $success = "Vehicle status updated successfully!";
                
                // Refresh vehicle data
                $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
                $stmt->execute([$id]);
                $vehicle = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error updating vehicle status: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'assign_driver') {
        $driver_id = $_POST['driver_id'] ?? '';
        if (!empty($driver_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicles SET driver_id = ? WHERE id = ?");
                $stmt->execute([$driver_id, $id]);
                $success = "Driver assigned to vehicle successfully!";
                
                // Refresh vehicle data
                $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
                $stmt->execute([$id]);
                $vehicle = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error assigning driver: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'assign_branch') {
        $branch_id = $_POST['branch_id'] ?? '';
        if (!empty($branch_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicles SET branch_id = ? WHERE id = ?");
                $stmt->execute([$branch_id, $id]);
                $success = "Vehicle assigned to branch successfully!";
                
                // Refresh vehicle data
                $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
                $stmt->execute([$id]);
                $vehicle = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error assigning branch: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: vehicles.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting vehicle: " . $e->getMessage();
        }
    }
}

// Fetch all drivers for assignment
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role IN ('driver', 'manager') AND status = 'active' ORDER BY name ASC");
    $drivers = $stmt->fetchAll();
} catch (Exception $e) {
    $drivers = [];
}

// Fetch all branches for assignment
try {
    $stmt = $pdo->query("SELECT * FROM branches ORDER BY name ASC");
    $branches = $stmt->fetchAll();
} catch (Exception $e) {
    $branches = [];
}

// Fetch current assignments
$current_driver = 'Not Assigned';
if ($vehicle->driver_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$vehicle->driver_id]);
        $driver = $stmt->fetch();
        if ($driver) {
            $current_driver = $driver->name;
        }
    } catch (Exception $e) {
        // Keep default if error
    }
}

$current_branch = 'Not Assigned';
if ($vehicle->branch_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $stmt->execute([$vehicle->branch_id]);
        $branch = $stmt->fetch();
        if ($branch) {
            $current_branch = $branch->name;
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
    <title>Manage Vehicle - MurgLogistics</title>
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
                <h1>Manage Vehicle</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Vehicle management and settings</p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="vehicles.php" style="color: var(--accent); text-decoration: none; cursor: pointer;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Vehicles
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

        <!-- Vehicle Overview -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Vehicle Overview</h2>
                <div>
                    <a href="edit_vehicle.php?id=<?= $vehicle->id ?>" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 0.5rem;">
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
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Current Assignments</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>Assigned Driver:</strong> <?= htmlspecialchars($current_driver) ?></div>
                        <div><strong>Assigned Branch:</strong> <?= htmlspecialchars($current_branch) ?></div>
                    </div>
                    <div style="margin-top: 1rem; color: var(--text-muted);">
                        <small>Added to fleet: <?= date('M d, Y', strtotime($vehicle->created_at)) ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Management -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Status Management</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1rem;">
                <input type="hidden" name="action" value="update_status">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Update Vehicle Status</label>
                        <select name="status" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="available" <?= $vehicle->status == 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="en_route" <?= $vehicle->status == 'en_route' ? 'selected' : '' ?>>En Route</option>
                            <option value="maintenance" <?= $vehicle->status == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-sync"></i> Update Status
                    </button>
                </div>
            </form>
        </div>

        <!-- Driver Assignment -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Driver Assignment</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1rem;">
                <input type="hidden" name="action" value="assign_driver">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Assign Driver</label>
                        <select name="driver_id" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= $driver->id ?>" <?= $vehicle->driver_id == $driver->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($driver->name) ?> - <?= htmlspecialchars($driver->email) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-user-plus"></i> Assign Driver
                    </button>
                </div>
            </form>
        </div>

        <!-- Branch Assignment -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Branch Assignment</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1rem;">
                <input type="hidden" name="action" value="assign_branch">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Assign Branch</label>
                        <select name="branch_id" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="">Unassigned</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= $branch->id ?>" <?= $vehicle->branch_id == $branch->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($branch->name) ?> - <?= htmlspecialchars($branch->city) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-building"></i> Assign Branch
                    </button>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="table-container">
            <div class="table-header">
                <h2 style="color: var(--danger);">Danger Zone</h2>
            </div>
            
            <div style="padding: 1.5rem;">
                <div style="background: rgba(239, 68, 68, 0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem;">Delete Vehicle</h4>
                    <p style="color: var(--text-muted); margin-bottom: 1rem;">Once you delete a vehicle, there is no going back. Please be certain.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" style="padding: 0.5rem 1rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-trash"></i> Delete Vehicle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
