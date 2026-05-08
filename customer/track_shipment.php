<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

$shipment = null;
$error = '';

// Handle search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tracking_number'])) {
    $tracking_number = trim($_POST['tracking_number']);
    
    if (!empty($tracking_number)) {
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, u.name as driver_name 
                FROM shipments s 
                LEFT JOIN users u ON s.driver_id = u.id 
                WHERE s.tracking_number = ?
            ");
            $stmt->execute([$tracking_number]);
            $shipment = $stmt->fetch();
            
            if (!$shipment) {
                $error = "Shipment not found or you don't have access to this shipment.";
            }
        } catch (Exception $e) {
            $error = "Error searching for shipment: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a tracking number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Shipment - MurgLogistics</title>
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
            <li><a href="track_shipment.php" class="active"><i class="fa-solid fa-search"></i> Track Shipment</a></li>
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
                <h1>Track Shipment</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Enter your tracking number to check shipment status</p>
            </div>
        </header>

        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.2); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="table-container">
            <div class="table-header">
                <h2>Track Your Shipment</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem; padding: 1.5rem;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Tracking Number</label>
                        <input type="text" name="tracking_number" value="<?= htmlspecialchars($_POST['tracking_number'] ?? '') ?>" placeholder="e.g., TRK2024050112345" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                        <i class="fa-solid fa-search"></i> Track
                    </button>
                </div>
            </form>
        </div>

        <?php if ($shipment): ?>
            <!-- Shipment Details -->
            <div class="table-container" style="margin-top: 2rem;">
                <div class="table-header">
                    <h2>Shipment Details</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                    <div>
                        <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Shipment Information</h3>
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
                        </div>
                    </div>
                    
                    <div>
                        <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Address Information</h3>
                        <div style="display: grid; gap: 0.75rem;">
                            <div><strong>Sender Address:</strong><br><?= nl2br(htmlspecialchars($shipment->sender_address)) ?></div>
                            <div><strong>Receiver Address:</strong><br><?= nl2br(htmlspecialchars($shipment->receiver_address)) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>
