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

// Handle driver addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $driver_name = $_POST['driver_name'] ?? '';
    $driver_email = $_POST['driver_email'] ?? '';
    $user_role = $_POST['user_role'] ?? 'driver';

    if (!empty($driver_name) && !empty($driver_email)) {
        try {
            // Check if email already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$driver_email]);
            
            if ($check->rowCount() > 0) {
                $error = "Email already exists!";
            } else {
                // Generate a default password
                $default_password = password_hash('staff123', PASSWORD_BCRYPT);
                
                // Ensure role is either 'driver' or 'manager' (valid enum values)
                $valid_role = ($user_role === 'manager') ? 'manager' : 'driver';
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$driver_name, $driver_email, $default_password, $valid_role]);
                $success = "Staff member added successfully! Default password: staff123";
            }
        } catch (PDOException $e) {
            $error = "Error adding staff: " . $e->getMessage();
        }
    } else {
        $error = "Name and email are required.";
    }
}

// Handle filtering
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for staff and drivers
$query = "SELECT * FROM users WHERE role IN ('driver', 'manager')";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
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
$drivers = $stmt->fetchAll();

// Fetch vehicles for assignment
try {
    $stmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'available'");
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
    <title>Staff & Drivers - MurgLogistics</title>
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
            <li><a href="vehicles.php"><i class="fa-solid fa-truck"></i> Fleet & Vehicles</a></li>
            <li><a href="drivers.php" class="active"><i class="fa-solid fa-users"></i> Staff & Drivers</a></li>
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
                <h1>Staff & Drivers</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage your delivery drivers and staff</p>
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

        <!-- Add Staff/Driver Form -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Add New Staff Member</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Full Name</label>
                        <input type="text" name="driver_name" placeholder="Full Name" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Email</label>
                        <input type="email" name="driver_email" placeholder="user@example.com" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Role</label>
                        <select name="user_role" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                            <option value="driver">Driver</option>
                            <option value="manager">Staff</option>
                        </select>
                    </div>
                </div>
                <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; align-self: flex-end;">
                    <i class="fa-solid fa-plus"></i> Add Staff Member
                </button>
            </form>
        </div>

        <!-- Staff & Drivers List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Staff & Drivers List</h2>
            </div>
            
            <!-- Search and Filter Controls -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 1rem; padding: 1rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <input type="text" placeholder="Search by name or email..." 
                           value="<?= htmlspecialchars($search) ?>" 
                           style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <div>
                    <select name="role_filter" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        <option value="">All Roles</option>
                        <option value="driver" <?= $role_filter == 'driver' ? 'selected' : '' ?>>Driver</option>
                        <option value="manager" <?= $role_filter == 'manager' ? 'selected' : '' ?>>Staff</option>
                    </select>
                </div>
                <div>
                    <select name="status_filter" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
                <button onclick="filterDrivers()" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>

            <?php if (count($drivers) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Name</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Email</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Role</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Status</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Created Date</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($driver->name) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($driver->email) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="background-color: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?= ($driver->role == 'manager' ? 'Staff' : ucfirst(htmlspecialchars($driver->role))) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span style="background-color: <?= ($driver->status ?? 'active') == 'active' ? 'var(--success)' : 'var(--danger)' ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?= ($driver->status ?? 'active') == 'active' ? 'Available' : 'Unavailable' ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= date('M d, Y', strtotime($driver->created_at)) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <a href="view_staff.php?id=<?= $driver->id ?>" style="color: var(--info); text-decoration: none; margin-right: 1rem; cursor: pointer;">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <a href="edit_staff.php?id=<?= $driver->id ?>" style="color: var(--accent); text-decoration: none; margin-right: 1rem; cursor: pointer;">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_staff.php?id=<?= $driver->id ?>" style="color: var(--warning); text-decoration: none; cursor: pointer;">
                                        <i class="fa-solid fa-sliders"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No staff or drivers yet. Add one to get started!
                </p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function filterDrivers() {
            const search = document.querySelector('input[type="text"]').value;
            const roleFilter = document.querySelector('select[name="role_filter"]').value;
            const statusFilter = document.querySelector('select[name="status_filter"]').value;
            const dateFrom = document.querySelectorAll('input[type="date"]')[0].value;
            const dateTo = document.querySelectorAll('input[type="date"]')[1].value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (roleFilter) params.append('role', roleFilter);
            if (statusFilter) params.append('status', statusFilter);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            window.location.href = `drivers.php?${params.toString()}`;
        }
        
        // Allow Enter key to trigger filter
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        filterDrivers();
                    }
                });
            });
        });
    </script>

</body>
</html>
