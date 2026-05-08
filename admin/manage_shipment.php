<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/price_config.php';

// Only allow admin and manager
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit;
}

$shipment_id = $_GET['id'] ?? null;
$success = '';
$error = '';
$shipment = null;

// Get current price per kg
$current_price_per_kg = getCurrentPricePerKg();

if ($shipment_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
        $stmt->execute([$shipment_id]);
        $shipment = $stmt->fetch();
        
        if (!$shipment) {
            $error = "Shipment not found.";
        }
    } catch (Exception $e) {
        $error = "Error fetching shipment: " . $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_status') {
        $new_status = $_POST['new_status'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        
        if (!empty($new_status)) {
            try {
                $stmt = $pdo->prepare("UPDATE shipments SET current_status = ? WHERE id = ?");
                $stmt->execute([$new_status, $shipment_id]);
                
                // Add to tracking history
                $hist_stmt = $pdo->prepare("INSERT INTO tracking_history (shipment_id, status, location, remarks, updated_by) VALUES (?, ?, ?, ?, ?)");
                $hist_stmt->execute([$shipment_id, $new_status, 'Admin Update', $remarks, $_SESSION['user_id']]);
                
                $success = "Shipment status updated to: <strong>$new_status</strong>";
                
                // Refresh shipment data
                $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
                $stmt->execute([$shipment_id]);
                $shipment = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error updating status: " . $e->getMessage();
            }
        }
    }
    
    if ($action == 'assign_driver') {
        $driver_id = $_POST['driver_id'] ?? '';
        
        if (!empty($driver_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE shipments SET driver_id = ? WHERE id = ?");
                $stmt->execute([$driver_id, $shipment_id]);
                
                // Add to tracking history
                $hist_stmt = $pdo->prepare("INSERT INTO tracking_history (shipment_id, status, location, remarks, updated_by) VALUES (?, 'In Transit', 'Driver Assigned', ?, ?)");
                $hist_stmt->execute([$shipment_id, $driver_id, $_SESSION['user_id']]);
                
                $success = "Driver assigned successfully!";
                
                // Refresh shipment data
                $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
                $stmt->execute([$shipment_id]);
                $shipment = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error assigning driver: " . $e->getMessage();
            }
        }
    }
    
    if ($action == 'delete') {
        try {
            // Delete tracking history first
            $hist_stmt = $pdo->prepare("DELETE FROM tracking_history WHERE shipment_id = ?");
            $hist_stmt->execute([$shipment_id]);
            
            // Delete shipment
            $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = ?");
            $stmt->execute([$shipment_id]);
            
            $success = "Shipment deleted successfully!";
            $shipment = null;
        } catch (PDOException $e) {
            $error = "Error deleting shipment: " . $e->getMessage();
        }
    }
}

// Fetch available drivers
$drivers = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ('driver', 'manager') ORDER BY name ASC");
    $stmt->execute();
    $drivers = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error fetching drivers: " . $e->getMessage();
}

// Fetch tracking history
$tracking_history = [];
if ($shipment) {
    try {
        $stmt = $pdo->prepare("SELECT th.*, u.name as updated_by_name FROM tracking_history th LEFT JOIN users u ON th.updated_by = u.id WHERE th.shipment_id = ? ORDER BY th.updated_at DESC");
        $stmt->execute([$shipment_id]);
        $tracking_history = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = "Error fetching tracking history: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shipment - MurgLogistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <i class="fa-solid fa-truck-fast"></i> MurgLogistics
        </a>
        
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="create_shipment.php" class="active"><i class="fa-solid fa-box"></i> Shipments</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck"></i> Fleet & Vehicles</a></li>
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
                <h1>Manage Shipment</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Shipment ID: <strong><?= htmlspecialchars($shipment->tracking_number ?? 'N/A') ?></strong></p>
            </div>
        </header>

        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.2); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background-color: rgba(16, 185, 129, 0.2); color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($shipment): ?>
            <div class="table-container" style="margin-bottom: 2rem;">
                <div class="table-header">
                    <h2>Shipment Details</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Sender Information</h3>
                        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                            <div style="margin-bottom: 0.5rem;"><strong>Name:</strong> <?= htmlspecialchars($shipment->sender_name) ?></div>
                            <div style="margin-bottom: 0.5rem;"><strong>Phone:</strong> <?= htmlspecialchars($shipment->sender_phone) ?></div>
                            <div><strong>Address:</strong><br><?= nl2br(htmlspecialchars($shipment->sender_address)) ?></div>
                        </div>
                    </div>
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Receiver Information</h3>
                        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                            <div style="margin-bottom: 0.5rem;"><strong>Name:</strong> <?= htmlspecialchars($shipment->receiver_name) ?></div>
                            <div style="margin-bottom: 0.5rem;"><strong>Phone:</strong> <?= htmlspecialchars($shipment->receiver_phone) ?></div>
                            <div><strong>Address:</strong><br><?= nl2br(htmlspecialchars($shipment->receiver_address)) ?></div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Shipment Status</h3>
                        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                            <div style="margin-bottom: 0.5rem;"><strong>Weight:</strong> <?= number_format($shipment->weight, 2) ?> kg</div>
                            <div style="margin-bottom: 0.5rem;"><strong>Cost:</strong> ₦<?= number_format($shipment->cost, 2) ?></div>
                            <div style="margin-bottom: 0.5rem;"><strong>Current Status:</strong> 
                                <span style="background-color: 
                                    <?php 
                                        if ($shipment->current_status == 'Delivered') echo 'var(--success)';
                                        elseif ($shipment->current_status == 'In Transit') echo 'var(--warning)';
                                        elseif ($shipment->current_status == 'Pending') echo 'var(--info)';
                                        else echo 'var(--muted)';
                                    ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                                    <?= htmlspecialchars($shipment->current_status) ?>
                                </span>
                            </div>
                            <div><strong>Created:</strong> <?= date('M d, Y', strtotime($shipment->created_at)) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Actions -->
            <div class="table-container" style="margin-bottom: 2rem;">
                <div class="table-header">
                    <h2>Management Actions</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                    <!-- Update Status -->
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Update Status</h3>
                        <form method="POST" style="display: grid; gap: 1rem;">
                            <input type="hidden" name="action" value="update_status">
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">New Status</label>
                                <select name="new_status" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                                    <option value="">Select Status</option>
                                    <option value="Pending" <?= $shipment->current_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Transit" <?= $shipment->current_status == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                                    <option value="Delivered" <?= $shipment->current_status == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Remarks</label>
                                <textarea name="remarks" placeholder="Add remarks about this status update..." style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; min-height: 80px;"></textarea>
                            </div>
                            <div>
                                <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                                    <i class="fa-solid fa-check"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Assign Driver -->
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Assign Driver</h3>
                        <form method="POST" style="display: grid; gap: 1rem;">
                            <input type="hidden" name="action" value="assign_driver">
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Driver</label>
                                <select name="driver_id" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                                    <option value="">Select Driver</option>
                                    <?php foreach ($drivers as $driver): ?>
                                        <option value="<?= $driver->id ?>" <?= $shipment->driver_id == $driver->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($driver->name) ?> (<?= ucfirst($driver->role) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--success); color: white; border: none; border-radius: 8px; cursor: pointer;">
                                    <i class="fa-solid fa-user-plus"></i> Assign Driver
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tracking History -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Tracking History</h2>
                </div>
                
                <?php if (count($tracking_history) > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Status</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Location</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Remarks</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Updated By</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tracking_history as $history): ?>
                                <tr>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                        <span style="background-color: 
                                            <?php 
                                                if ($history->status == 'Delivered') echo 'var(--success)';
                                                elseif ($history->status == 'In Transit') echo 'var(--warning)';
                                                elseif ($history->status == 'Pending') echo 'var(--info)';
                                                else echo 'var(--muted)';
                                            ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                                            <?= htmlspecialchars($history->status) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($history->location) ?></td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($history->remarks) ?></td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($history->updated_by_name) ?></td>
                                    <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= date('M d, Y H:i', strtotime($history->updated_at)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No tracking history available.</p>
                <?php endif; ?>
            </div>

            <!-- Delete Shipment -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Danger Zone</h2>
                </div>
                
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger); border-radius: 8px; padding: 1.5rem; text-align: center;">
                    <h3 style="color: var(--danger); margin-bottom: 1rem;">Delete Shipment</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1rem;">This action cannot be undone. This will permanently delete the shipment and all its tracking history.</p>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this shipment? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" style="padding: 1rem 2rem; background: var(--danger); color: white; border: none; border-radius: 8px; cursor: pointer;">
                            <i class="fa-solid fa-trash"></i> Delete Shipment
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="fa-solid fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                <h3>Shipment Not Found</h3>
                <p>The shipment you're looking for doesn't exist or has been removed.</p>
                <a href="index.php" style="text-decoration: none; padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; display: inline-block; margin-top: 1rem;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>
