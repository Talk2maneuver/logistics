<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow admin and manager
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle vehicle addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $license_plate = $_POST['license_plate'] ?? '';
    $vehicle_type = $_POST['vehicle_type'] ?? '';
    $capacity = floatval($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'available';

    if (!empty($license_plate) && !empty($vehicle_type) && $capacity > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vehicles (license_plate, type, capacity, status)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$license_plate, $vehicle_type, $capacity, $status]);
            $success = "Vehicle added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding vehicle: " . $e->getMessage();
        }
    } else {
        $error = "License plate, vehicle type, and capacity are required.";
    }
}

// Handle filtering
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for vehicles
$query = "SELECT * FROM vehicles WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (license_plate LIKE ? OR type LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet & Vehicles - MurgLogistics</title>
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
                <h1>Fleet & Vehicles</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage your fleet of delivery vehicles</p>
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

        <!-- Add Vehicle Form -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Add New Vehicle</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">License Plate</label>
                        <input type="text" name="license_plate" placeholder="e.g., ABC-1234" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Vehicle Type</label>
                        <select name="vehicle_type" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="Truck">Truck</option>
                            <option value="Van">Van</option>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Bicycle">Bicycle</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Capacity (kg)</label>
                        <input type="number" name="capacity" placeholder="e.g., 1000" step="0.01" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Status</label>
                        <select name="status" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="available">Available</option>
                            <option value="en_route">En Route</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; align-self: flex-end;">
                    <i class="fa-solid fa-plus"></i> Add Vehicle
                </button>
            </form>
        </div>

        <!-- Vehicles List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Vehicles List</h2>
            </div>
            
            <!-- Search and Filter Controls -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; padding: 1rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <input type="text" placeholder="Search by license plate or type..." 
                           value="<?= htmlspecialchars($search) ?>" 
                           style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <div>
                    <select name="status_filter" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        <option value="">All Status</option>
                        <option value="available" <?= $status_filter == 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="en_route" <?= $status_filter == 'en_route' ? 'selected' : '' ?>>En Route</option>
                        <option value="maintenance" <?= $status_filter == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
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
                <button onclick="filterVehicles()" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>

            <?php if (count($vehicles) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">License Plate</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Type</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Capacity (kg)</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Status</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><strong><?= htmlspecialchars($vehicle->license_plate) ?></strong></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($vehicle->type) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= number_format($vehicle->capacity, 2) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="background-color: 
                                        <?php 
                                            if ($vehicle->status == 'available') echo 'var(--success)';
                                            elseif ($vehicle->status == 'en_route') echo 'var(--warning)';
                                            else echo 'var(--danger)';
                                        ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($vehicle->status))) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <a href="view_vehicle.php?id=<?= $vehicle->id ?>" style="color: var(--info); text-decoration: none; margin-right: 1rem; cursor: pointer;">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <a href="edit_vehicle.php?id=<?= $vehicle->id ?>" style="color: var(--accent); text-decoration: none; margin-right: 1rem; cursor: pointer;">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_vehicle.php?id=<?= $vehicle->id ?>" style="color: var(--warning); text-decoration: none; cursor: pointer;">
                                        <i class="fa-solid fa-sliders"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No vehicles in the fleet yet. Add one to get started!
                </p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function filterVehicles() {
            const search = document.querySelector('input[type="text"]').value;
            const statusFilter = document.querySelector('select[name="status_filter"]').value;
            const dateFrom = document.querySelectorAll('input[type="date"]')[0].value;
            const dateTo = document.querySelectorAll('input[type="date"]')[1].value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (statusFilter) params.append('status', statusFilter);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            window.location.href = `vehicles.php?${params.toString()}`;
        }
        
        // Allow Enter key to trigger filter
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        filterVehicles();
                    }
                });
            });
        });
    </script>

</body>
</html>
