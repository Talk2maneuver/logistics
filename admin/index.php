<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow admin and manager
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit;
}

// Fetch some basic stats
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cx FROM shipments");
    $totalShipments = $stmt->fetch()->cx;

    $stmt = $pdo->query("SELECT COUNT(*) as cx FROM shipments WHERE current_status != 'Delivered'");
    $activeShipments = $stmt->fetch()->cx;

    $stmt = $pdo->query("SELECT COUNT(*) as cx FROM vehicles WHERE status = 'available'");
    $availableVehicles = $stmt->fetch()->cx;

    $stmt = $pdo->query("SELECT COUNT(*) as cx FROM users WHERE role = 'driver'");
    $totalDrivers = $stmt->fetch()->cx;

    // Handle filtering
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for shipments
$query = "SELECT * FROM shipments WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (tracking_number LIKE ? OR sender_name LIKE ? OR receiver_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($date_from)) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY created_at DESC LIMIT 10";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$recentShipments = $stmt->fetchAll();
    
} catch (Exception $e) {
    // defaults
    $totalShipments = $activeShipments = $availableVehicles = $totalDrivers = 0;
    $recentShipments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MurgLogistics</title>
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
            <li><a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="create_shipment.php"><i class="fa-solid fa-box"></i> Shipments</a></li>
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
                <h1>Overview</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
            
            <div class="user-profile">
                <!-- Simple avatar with initials -->
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span>Total Shipments</span>
                    <div class="stat-icon" style="color: var(--accent);"><i class="fa-solid fa-box"></i></div>
                </div>
                <div class="stat-value"><?= number_format($totalShipments) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span>Active Deliveries</span>
                    <div class="stat-icon" style="color: var(--warning);"><i class="fa-solid fa-route"></i></div>
                </div>
                <div class="stat-value"><?= number_format($activeShipments) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span>Available Vehicles</span>
                    <div class="stat-icon" style="color: var(--success);"><i class="fa-solid fa-truck"></i></div>
                </div>
                <div class="stat-value"><?= number_format($availableVehicles) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span>Available Drivers</span>
                    <div class="stat-icon" style="color: var(--danger);"><i class="fa-solid fa-id-card"></i></div>
                </div>
                <div class="stat-value"><?= number_format($totalDrivers) ?></div>
            </div>
        </div>

        <!-- Recent Activity Placeholder -->
        <div class="table-container">
            <div class="table-header">
                <h2>Recent Shipments</h2>
                <a href="create_shipment.php" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-plus"></i> New Shipment
                </a>
            </div>
            
            <!-- Search and Filter Controls -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; padding: 1rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <input type="text" placeholder="Search by tracking ID, sender, or receiver..." 
                           value="<?= htmlspecialchars($search) ?>" 
                           style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <div>
                    <input type="date" placeholder="From date" 
                           value="<?= htmlspecialchars($date_from) ?>" 
                           style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <div>
                    <input type="date" placeholder="To date" 
                           value="<?= htmlspecialchars($date_to) ?>" 
                           style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <button onclick="filterShipments()" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>
            
            <?php if (count($recentShipments) > 0): ?>
                <table class="shipments-table" style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                        <tr style="background: rgba(15, 23, 42, 0.4);">
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Tracking ID</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Sender</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Receiver</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Status</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Created Date</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentShipments as $shipment): ?>
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;">
                                <td style="padding: 1.25rem 1rem; font-weight: 600; color: var(--text-primary);"><strong><?= htmlspecialchars($shipment->tracking_number) ?></strong></td>
                                <td style="padding: 1.25rem 1rem; color: var(--text-primary);"><?= htmlspecialchars($shipment->sender_name) ?></td>
                                <td style="padding: 1.25rem 1rem; color: var(--text-primary);"><?= htmlspecialchars($shipment->receiver_name) ?></td>
                                <td style="padding: 1.25rem 1rem;">
                                    <span class="status-badge" style="background-color: 
                                        <?php 
                                            if ($shipment->current_status == 'Delivered') echo 'var(--success)';
                                            elseif ($shipment->current_status == 'In Transit') echo 'var(--warning)';
                                            elseif ($shipment->current_status == 'Pending') echo 'var(--info)';
                                            else echo 'var(--muted)';
                                        ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                                        <?= htmlspecialchars($shipment->current_status) ?>
                                    </span>
                                </td>
                                <td style="padding: 1.25rem 1rem; color: var(--text-muted);"><?= date('M d, Y', strtotime($shipment->created_at)) ?></td>
                                <td style="padding: 1.25rem 1rem;">
                                    <a href="view_shipment.php?id=<?= $shipment->id ?>" style="color: var(--accent); text-decoration: none; cursor: pointer; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid var(--accent); border-radius: 6px; display: inline-block; transition: all 0.2s; margin-right: 0.5rem;">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <a href="invoice.php?id=<?= $shipment->id ?>" style="color: var(--info); text-decoration: none; cursor: pointer; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid var(--info); border-radius: 6px; display: inline-block; transition: all 0.2s; margin-right: 0.5rem;">
                                        <i class="fa-solid fa-file-invoice"></i> Invoice
                                    </a>
                                    <a href="edit_shipment.php?id=<?= $shipment->id ?>" style="color: var(--warning); text-decoration: none; cursor: pointer; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid var(--warning); border-radius: 6px; display: inline-block; transition: all 0.2s; margin-right: 0.5rem;">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_shipment.php?id=<?= $shipment->id ?>" style="color: var(--success); text-decoration: none; cursor: pointer; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid var(--success); border-radius: 6px; display: inline-block; transition: all 0.2s;">
                                        <i class="fa-solid fa-sliders"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No shipments created yet.<br>
                    <small>Once you create a shipment, it will appear here.</small>
                </p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function filterShipments() {
            const search = document.querySelector('input[type="text"]').value;
            const dateFrom = document.querySelectorAll('input[type="date"]')[0].value;
            const dateTo = document.querySelectorAll('input[type="date"]')[1].value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            window.location.href = `index.php?${params.toString()}`;
        }
        
        // Allow Enter key to trigger filter
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        filterShipments();
                    }
                });
            });
        });
    </script>

</body>
</html>
