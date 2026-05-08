<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $shipment_id = $_POST['shipment_id'];
    $new_status = $_POST['status'];
    $location = $_POST['location'];
    $remarks = $_POST['remarks'];

    try {
        // Update main shipment status
        $stmt = $pdo->prepare("UPDATE shipments SET current_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $shipment_id]);

        // Insert history record
        $hist = $pdo->prepare("INSERT INTO tracking_history (shipment_id, status, location, remarks, updated_by) VALUES (?, ?, ?, ?, ?)");
        $hist->execute([$shipment_id, $new_status, $location, $remarks, $_SESSION['user_id']]);

        $success = "Tracking updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating status.";
    }
}

// Fetch shipments
$shipments_stmt = $pdo->query("SELECT * FROM shipments ORDER BY created_at DESC");
$shipments = $shipments_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shipments - MurgLogistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .shipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .shipment-table th, .shipment-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .shipment-table th {
            color: var(--text-muted);
            font-weight: 500;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-Pending { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .status-In-Transit { background: rgba(99, 102, 241, 0.2); color: var(--accent); }
        .status-Delivered { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        
        .update-form {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .update-form input, .update-form select {
            padding: 0.5rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: white;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="index.php" class="brand">
            <i class="fa-solid fa-truck-fast"></i> MurgLogistics
        </a>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="create_shipment.php"><i class="fa-solid fa-plus"></i> New Shipment</a></li>
            <li><a href="shipments.php" class="active"><i class="fa-solid fa-box"></i> Manage Shipments</a></li>
        </ul>
        <div class="logout-container">
            <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Manage Shipments</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Update parcel locations and statuses in real-time.</p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="index.php" style="color: var(--accent); text-decoration: none; cursor: pointer; padding: 0.5rem 1rem; border: 1px solid var(--accent); border-radius: 6px; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <div class="table-container">
            <?php if(isset($success)) echo "<div style='color: #10b981; margin-bottom:1rem;'>$success</div>"; ?>
            <?php if(isset($error)) echo "<div style='color: #ef4444; margin-bottom:1rem;'>$error</div>"; ?>

            <table class="shipment-table">
                <thead>
                    <tr>
                        <th>Tracking ID</th>
                        <th>Receiver</th>
                        <th>Current Status</th>
                        <th>Update Location / Timeline Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($shipments as $s): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($s->tracking_number) ?></strong><br>
                            <a href="invoice.php?id=<?= $s->id ?>" target="_blank" style="font-size: 0.8rem; color: var(--accent); text-decoration: none;"><i class="fa-solid fa-file-invoice"></i> View Invoice</a>
                        </td>
                        <td><?= htmlspecialchars($s->receiver_name) ?></td>
                        <td>
                            <span class="status-badge status-<?= str_replace(' ', '-', $s->current_status) ?>">
                                <?= htmlspecialchars($s->current_status) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($s->current_status != 'Delivered'): ?>
                            <form method="POST" class="update-form">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="shipment_id" value="<?= $s->id ?>">
                                
                                <select name="status" required>
                                    <option value="Processed">Processed</option>
                                    <option value="In Transit">In Transit</option>
                                    <option value="Out for Delivery">Out for Delivery</option>
                                    <option value="Delivered">Delivered</option>
                                </select>
                                
                                <input type="text" name="location" placeholder="Current City/Hub" required style="width: 130px;">
                                <input type="text" name="remarks" placeholder="Remarks" style="width: 150px;">
                                
                                <button type="submit" class="btn-sm">Push Update</button>
                            </form>
                            <?php else: ?>
                                <span style="color: var(--success);"><i class="fa-solid fa-check-circle"></i> Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
