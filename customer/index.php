<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

// Get customer information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
} catch (Exception $e) {
    $customer = null;
}

// Handle filtering
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for shipments - only show shipments belonging to the logged-in customer
$query = "SELECT * FROM shipments WHERE user_id = ?";
$params = [$_SESSION['user_id']];

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
$shipments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - MurgLogistics</title>
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
        <a href="../index.html" class="brand">
            <i class="fa-solid fa-truck-fast"></i> MurgLogistics
        </a>
        
        <ul class="nav-links">
            <li><a href="index.php" class="active"><i class="fa-solid fa-home"></i> Dashboard</a></li>
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
                <h1>Welcome, <?= htmlspecialchars($customer->name) ?></h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Track your shipments and manage your deliveries</p>
            </div>
        </header>

        <!-- Quick Stats -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number"><?= count($shipments) ?></div>
                    <div class="stat-label">Total Shipments</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <div class="stat-info">
                    <?php 
                    $pending_count = 0;
                    foreach ($shipments as $shipment) {
                        if ($shipment->current_status == 'Pending') {
                            $pending_count++;
                        }
                    }
                    ?>
                    <div class="stat-number"><?= $pending_count ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <?php 
                    $delivered_count = 0;
                    foreach ($shipments as $shipment) {
                        if ($shipment->current_status == 'Delivered') {
                            $delivered_count++;
                        }
                    }
                    ?>
                    <div class="stat-number"><?= $delivered_count ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
            </div>
        </div>

        <!-- Recent Shipments -->
        <div class="table-container">
            <div class="table-header">
                <h2>Recent Shipments</h2>
                <a href="create_shipment.php" style="text-decoration: none; padding: 0.5rem 1rem; background: var(--success); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-plus"></i> Create Shipment
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
            
            <?php if (count($shipments) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Tracking ID</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Status</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Created Date</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 2px solid var(--border-color);">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shipments as $shipment): ?>
                            <tr>
                                <td style="padding: 1.25rem 1rem; font-weight: 600; color: var(--text-primary);">
                                    <strong><?= htmlspecialchars($shipment->tracking_number) ?></strong>
                                </td>
                                <td style="padding: 1.25rem 1rem;">
                                    <span style="background-color: 
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
                                    <a href="invoice.php?id=<?= $shipment->id ?>" style="color: var(--success); text-decoration: none; cursor: pointer; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid var(--success); border-radius: 6px; display: inline-block; transition: all 0.2s; margin-right: 0.5rem;">
                                        <i class="fa-solid fa-file-invoice"></i> Invoice
                                    </a>
                                    <?php if ($shipment->current_status == 'Pending'): ?>
                                        <a href="edit_shipment.php?id=<?= $shipment->id ?>" style="color: var(--warning); text-decoration: none; cursor: pointer; font-weight: 500; padding: 0.5rem 1rem; border: 1px solid var(--warning); border-radius: 6px; display: inline-block; transition: all 0.2s;">
                                            <i class="fa-solid fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fa-solid fa-box" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    <h3>No Shipments Yet</h3>
                    <p>Create your first shipment to get started with tracking and delivery management.</p>
                    <a href="create_shipment.php" style="text-decoration: none; padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; display: inline-block; margin-top: 1rem;">
                        <i class="fa-solid fa-plus"></i> Create Your First Shipment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <style>
        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
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
        
        // Mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        
        function closeMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
        
        // Close mobile menu when clicking on a link
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    closeMobileMenu();
                });
            });
        });
    </script>
</body>
</html>
