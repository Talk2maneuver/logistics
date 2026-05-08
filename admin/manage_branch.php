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

// Handle status change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: branches.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting branch: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'assign_staff') {
        $staff_id = $_POST['staff_id'] ?? '';
        if (!empty($staff_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET branch_id = ? WHERE id = ?");
                $stmt->execute([$id, $staff_id]);
                $success = "Staff member assigned to branch successfully!";
            } catch (PDOException $e) {
                $error = "Error assigning staff: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'assign_vehicle') {
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        if (!empty($vehicle_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicles SET branch_id = ? WHERE id = ?");
                $stmt->execute([$id, $vehicle_id]);
                $success = "Vehicle assigned to branch successfully!";
            } catch (PDOException $e) {
                $error = "Error assigning vehicle: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'remove_staff') {
        $staff_id = $_POST['staff_id'] ?? '';
        if (!empty($staff_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET branch_id = NULL WHERE id = ?");
                $stmt->execute([$staff_id]);
                $success = "Staff member removed from branch successfully!";
            } catch (PDOException $e) {
                $error = "Error removing staff: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'remove_vehicle') {
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        if (!empty($vehicle_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicles SET branch_id = NULL WHERE id = ?");
                $stmt->execute([$vehicle_id]);
                $success = "Vehicle removed from branch successfully!";
            } catch (PDOException $e) {
                $error = "Error removing vehicle: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'assign_vehicle_to_driver') {
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        $driver_id = $_POST['driver_id'] ?? '';
        if (!empty($vehicle_id) && !empty($driver_id)) {
            try {
                // First, unassign this vehicle from any other driver
                $stmt = $pdo->prepare("UPDATE vehicles SET driver_id = NULL WHERE id = ?");
                $stmt->execute([$vehicle_id]);
                
                // Then assign to the selected driver
                $stmt = $pdo->prepare("UPDATE vehicles SET driver_id = ? WHERE id = ?");
                $stmt->execute([$driver_id, $vehicle_id]);
                $success = "Vehicle assigned to driver successfully!";
            } catch (PDOException $e) {
                $error = "Error assigning vehicle to driver: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'remove_vehicle_from_driver') {
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        if (!empty($vehicle_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE vehicles SET driver_id = NULL WHERE id = ?");
                $stmt->execute([$vehicle_id]);
                $success = "Vehicle removed from driver successfully!";
            } catch (PDOException $e) {
                $error = "Error removing vehicle from driver: " . $e->getMessage();
            }
        }
    }
}

// Fetch staff assigned to this branch
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE branch_id = ? ORDER BY name ASC");
    $stmt->execute([$id]);
    $staff = $stmt->fetchAll();
} catch (Exception $e) {
    $staff = [];
}

// Fetch shipments from this branch
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shipments WHERE pickup_branch_id = ? OR delivery_branch_id = ?");
    $stmt->execute([$id, $id]);
    $shipment_stats = $stmt->fetch();
} catch (Exception $e) {
    $shipment_stats = (object) ['total' => 0];
}

// Fetch all available staff for assignment
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role IN ('driver', 'manager') ORDER BY name ASC");
    $all_staff = $stmt->fetchAll();
} catch (Exception $e) {
    $all_staff = [];
}

// Fetch all available vehicles for assignment
try {
    $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY license_plate ASC");
    $all_vehicles = $stmt->fetchAll();
} catch (Exception $e) {
    $all_vehicles = [];
}

// Fetch vehicles assigned to this branch
try {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE branch_id = ? ORDER BY license_plate ASC");
    $stmt->execute([$id]);
    $vehicles = $stmt->fetchAll();
} catch (Exception $e) {
    $vehicles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branch - MurgLogistics</title>
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
                <h1>Manage Branch</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Branch management and settings</p>
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

        <!-- Branch Overview -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Branch Overview</h2>
                <div>
                    <a href="edit_branch.php?id=<?= $branch->id ?>" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 0.5rem;">
                        <i class="fa-solid fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Basic Information</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>Name:</strong> <?= htmlspecialchars($branch->name) ?></div>
                        <div><strong>City:</strong> <?= htmlspecialchars($branch->city) ?></div>
                        <div><strong>Address:</strong> <?= htmlspecialchars($branch->address) ?></div>
                        <div><strong>Phone:</strong> <?= htmlspecialchars($branch->phone ?? 'N/A') ?></div>
                        <div><strong>Contact:</strong> <?= htmlspecialchars($branch->contact_number ?? 'N/A') ?></div>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Statistics</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="background: rgba(99, 102, 241, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent);"><?= count($staff) ?></div>
                            <div style="color: var(--text-muted);">Staff Members</div>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--success);"><?= $shipment_stats->total ?></div>
                            <div style="color: var(--text-muted);">Total Shipments</div>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; color: var(--text-muted);">
                        <small>Created: <?= date('M d, Y', strtotime($branch->created_at)) ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Assignment -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Assign Staff to Branch</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1rem;">
                <input type="hidden" name="action" value="assign_staff">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Staff Member</label>
                        <select name="staff_id" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="">Choose a staff member...</option>
                            <?php foreach ($all_staff as $member): ?>
                                <?php if ($member->branch_id != $id): ?>
                                    <option value="<?= $member->id ?>">
                                        <?= htmlspecialchars($member->name) ?> - <?= htmlspecialchars($member->email) ?> (<?= ucfirst($member->role) ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-user-plus"></i> Assign Staff
                    </button>
                </div>
            </form>
        </div>

        <!-- Staff Members -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Current Staff Members</h2>
                <a href="drivers.php" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--success); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-plus"></i> Add Staff
                </a>
            </div>
            
            <?php if (count($staff) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Name</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Email</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Role</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $member): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($member->name) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($member->email) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="background-color: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?= ucfirst(htmlspecialchars($member->role)) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <a href="edit_staff.php?id=<?= $member->id ?>" style="color: var(--accent); text-decoration: none; cursor: pointer; margin-right: 1rem;">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this staff member from the branch?');">
                                        <input type="hidden" name="action" value="remove_staff">
                                        <input type="hidden" name="staff_id" value="<?= $member->id ?>">
                                        <button type="submit" style="color: var(--danger); background: none; border: none; cursor: pointer; padding: 0; text-decoration: underline;">
                                            <i class="fa-solid fa-user-minus"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No staff assigned to this branch yet.
                </p>
            <?php endif; ?>
        </div>

        <!-- Vehicle Assignment -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Assign Vehicle to Branch</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1rem;">
                <input type="hidden" name="action" value="assign_vehicle">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Vehicle</label>
                        <select name="vehicle_id" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="">Choose a vehicle...</option>
                            <?php foreach ($all_vehicles as $vehicle): ?>
                                <?php if ($vehicle->branch_id != $id): ?>
                                    <option value="<?= $vehicle->id ?>">
                                        <?= htmlspecialchars($vehicle->license_plate) ?> - <?= htmlspecialchars($vehicle->type) ?> (<?= $vehicle->capacity ?> tons)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-truck-plus"></i> Assign Vehicle
                    </button>
                </div>
            </form>
        </div>

        <!-- Vehicle to Driver Assignment -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Assign Vehicle to Driver</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1rem;">
                <input type="hidden" name="action" value="assign_vehicle_to_driver">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Driver</label>
                        <select name="driver_id" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="">Choose a driver...</option>
                            <?php foreach ($staff as $member): ?>
                                <?php if ($member->role == 'driver' || $member->role == 'manager'): ?>
                                    <option value="<?= $member->id ?>">
                                        <?= htmlspecialchars($member->name) ?> - <?= htmlspecialchars($member->email) ?> (<?= ucfirst($member->role) ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Vehicle</label>
                        <select name="vehicle_id" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="">Choose a vehicle...</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?= $vehicle->id ?>">
                                    <?= htmlspecialchars($vehicle->license_plate) ?> - <?= htmlspecialchars($vehicle->type) ?> (<?= $vehicle->capacity ?> tons)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-truck-arrow-right"></i> Assign to Driver
                    </button>
                </div>
            </form>
        </div>

        <!-- Vehicles -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Current Vehicles</h2>
                <a href="vehicles.php" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--success); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-plus"></i> Add Vehicle
                </a>
            </div>
            
            <?php if (count($vehicles) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">License Plate</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Type</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Capacity</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Status</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Driver</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($vehicle->license_plate) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($vehicle->type) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= $vehicle->capacity ?> tons</td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="background-color: 
                                        <?php 
                                            if ($vehicle->status == 'available') echo 'var(--success)';
                                            elseif ($vehicle->status == 'en_route') echo 'var(--warning)';
                                            else echo 'var(--danger)';
                                        ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?= ucfirst(htmlspecialchars($vehicle->status)) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <?php 
                                    $driver_name = 'Not Assigned';
                                    if ($vehicle->driver_id) {
                                        try {
                                            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                                            $stmt->execute([$vehicle->driver_id]);
                                            $driver = $stmt->fetch();
                                            if ($driver) {
                                                $driver_name = $driver->name;
                                            }
                                        } catch (Exception $e) {
                                            // Keep default if error
                                        }
                                    }
                                    echo htmlspecialchars($driver_name);
                                    ?>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <?php if ($vehicle->driver_id): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this vehicle from the driver?');" style="margin-right: 1rem;">
                                            <input type="hidden" name="action" value="remove_vehicle_from_driver">
                                            <input type="hidden" name="vehicle_id" value="<?= $vehicle->id ?>">
                                            <button type="submit" style="color: var(--warning); background: none; border: none; cursor: pointer; padding: 0; text-decoration: underline;">
                                                <i class="fa-solid fa-user-minus"></i> Remove from Driver
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this vehicle from the branch?');">
                                        <input type="hidden" name="action" value="remove_vehicle">
                                        <input type="hidden" name="vehicle_id" value="<?= $vehicle->id ?>">
                                        <button type="submit" style="color: var(--danger); background: none; border: none; cursor: pointer; padding: 0; text-decoration: underline;">
                                            <i class="fa-solid fa-truck-minus"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No vehicles assigned to this branch yet.
                </p>
            <?php endif; ?>
        </div>

        <!-- Danger Zone -->
        <div class="table-container">
            <div class="table-header">
                <h2 style="color: var(--danger);">Danger Zone</h2>
            </div>
            
            <div style="padding: 1.5rem;">
                <div style="background: rgba(239, 68, 68, 0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem;">Delete Branch</h4>
                    <p style="color: var(--text-muted); margin-bottom: 1rem;">Once you delete a branch, there is no going back. Please be certain.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this branch? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" style="padding: 0.5rem 1rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-trash"></i> Delete Branch
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
