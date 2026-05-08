<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

$shipment_id = $_GET['id'] ?? null;
if (!$shipment_id) {
    header("Location: index.php");
    exit;
}

// Fetch shipment details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as driver_name 
        FROM shipments s 
        LEFT JOIN users u ON s.driver_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$shipment_id]);
    $shipment = $stmt->fetch();
    
    if (!$shipment) {
        header("Location: index.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Details - MurgLogistics</title>
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
            <li><a href="index.php"><i class="fa-solid fa-home"></i> Dashboard</a></li>
            <li><a href="create_shipment.php"><i class="fa-solid fa-box"></i> New Shipment</a></li>
            <li><a href="track_shipment.php"><i class="fa-solid fa-search"></i> Track Shipment</a></li>
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
                <h1>Shipment Details</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Complete information about your shipment</p>
            </div>
            <div style="margin-top: 1rem;">
                <a href="index.php" style="color: var(--accent); text-decoration: none; cursor: pointer;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Shipment Overview -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Shipment Information</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Basic Information</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>Tracking Number:</strong> <?= htmlspecialchars($shipment->tracking_number) ?></div>
                        <div><strong>Status:</strong> 
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
                        <div><strong>Created Date:</strong> <?= date('M d, Y', strtotime($shipment->created_at)) ?></div>
                        <div><strong>Weight:</strong> <?= $shipment->weight ?> kg</div>
                        <div><strong>Cost:</strong> $<?= number_format($shipment->cost, 2) ?></div>
                        <div><strong>Assigned Driver:</strong> <?= htmlspecialchars($shipment->driver_name ?? 'Not Assigned') ?></div>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Contact Information</h3>
                    <div style="display: grid; gap: 0.75rem;">
                        <div><strong>Sender Name:</strong> <?= htmlspecialchars($shipment->sender_name) ?></div>
                        <div><strong>Sender Phone:</strong> <?= htmlspecialchars($shipment->sender_phone) ?></div>
                        <div><strong>Receiver Name:</strong> <?= htmlspecialchars($shipment->receiver_name) ?></div>
                        <div><strong>Receiver Phone:</strong> <?= htmlspecialchars($shipment->receiver_phone) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Address Information</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Sender Address</h3>
                    <div style="background: rgba(15, 23, 42, 0.6); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <?= nl2br(htmlspecialchars($shipment->sender_address)) ?>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Receiver Address</h3>
                    <div style="background: rgba(15, 23, 42, 0.6); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <?= nl2br(htmlspecialchars($shipment->receiver_address)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="table-container">
            <div class="table-header">
                <h2>Shipment Status</h2>
            </div>
            
            <div style="padding: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: center; padding: 2rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">
                            <?php
                            if ($shipment->current_status == 'Delivered') {
                                echo '<i class="fa-solid fa-check-circle" style="color: var(--success);"></i>';
                            } elseif ($shipment->current_status == 'In Transit') {
                                echo '<i class="fa-solid fa-truck" style="color: var(--warning);"></i>';
                            } elseif ($shipment->current_status == 'Pending') {
                                echo '<i class="fa-solid fa-clock" style="color: var(--info);"></i>';
                            } else {
                                echo '<i class="fa-solid fa-question-circle" style="color: var(--muted);"></i>';
                            }
                            ?>
                        </div>
                        <h3 style="margin-bottom: 0.5rem;">
                            <?= htmlspecialchars($shipment->current_status) ?>
                        </h3>
                        <p style="color: var(--text-muted);">
                            <?php
                            if ($shipment->current_status == 'Delivered') {
                                echo 'Your shipment has been successfully delivered';
                            } elseif ($shipment->current_status == 'In Transit') {
                                echo 'Your shipment is currently in transit';
                            } elseif ($shipment->current_status == 'Pending') {
                                echo 'Your shipment is pending pickup';
                            } else {
                                echo 'Status unknown';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
            <a href="track_shipment.php" style="text-decoration: none; padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                <i class="fa-solid fa-search"></i> Track Another Shipment
            </a>
            <a href="create_shipment.php" style="text-decoration: none; padding: 0.75rem 1.5rem; background: var(--success); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                <i class="fa-solid fa-plus"></i> Create New Shipment
            </a>
        </div>
    </main>

</body>
</html>
