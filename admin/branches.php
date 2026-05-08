<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow admin
if ($_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle branch addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch_name = $_POST['branch_name'] ?? '';
    $branch_city = $_POST['branch_city'] ?? '';
    $branch_address = $_POST['branch_address'] ?? '';
    $branch_phone = $_POST['branch_phone'] ?? '';

    if (!empty($branch_name) && !empty($branch_city)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO branches (name, city, address, phone)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$branch_name, $branch_city, $branch_address, $branch_phone]);
            $success = "Branch added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding branch: " . $e->getMessage();
        }
    } else {
        $error = "Branch name and city are required.";
    }
}

// Handle filtering
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for branches
$query = "SELECT * FROM branches WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR city LIKE ? OR address LIKE ?)";
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

$query .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$branches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branches - MurgLogistics</title>
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
                <h1>Branches</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage your company branches and offices</p>
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

        <!-- Add Branch Form -->
        <div class="table-container" style="margin-bottom: 2rem;">
            <div class="table-header">
                <h2>Add New Branch</h2>
            </div>
            
            <form method="POST" style="display: grid; gap: 1rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Branch Name</label>
                        <input type="text" name="branch_name" placeholder="e.g., Downtown Hub" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">City</label>
                        <input type="text" name="branch_city" placeholder="e.g., New York" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Address</label>
                        <input type="text" name="branch_address" placeholder="Full Address" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Phone</label>
                        <input type="tel" name="branch_phone" placeholder="+1-234-567-8900" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                    </div>
                </div>
                <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; align-self: flex-end;">
                    <i class="fa-solid fa-plus"></i> Add Branch
                </button>
            </form>
        </div>

        <!-- Branches List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Branches List</h2>
            </div>
            
            <!-- Search and Filter Controls -->
            <div style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 1rem; padding: 1rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <input type="text" placeholder="Search by name, city, or address..." 
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
                <button onclick="filterBranches()" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>

            <?php if (count($branches) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Branch Name</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">City</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Address</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Phone</th>
                            <th style="padding: 1rem; text-align: left; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color);">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($branch->name) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($branch->city) ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($branch->address ?? 'N/A') ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);"><?= htmlspecialchars($branch->phone ?? 'N/A') ?></td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <a href="view_branch.php?id=<?= $branch->id ?>" style="color: var(--info); text-decoration: none; margin-right: 1rem; cursor: pointer;">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <a href="edit_branch.php?id=<?= $branch->id ?>" style="color: var(--accent); text-decoration: none; margin-right: 1rem; cursor: pointer;">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_branch.php?id=<?= $branch->id ?>" style="color: var(--warning); text-decoration: none; cursor: pointer;">
                                        <i class="fa-solid fa-sliders"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                    No branches yet. Add one to get started!
                </p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function filterBranches() {
            const search = document.querySelector('input[type="text"]').value;
            const dateFrom = document.querySelectorAll('input[type="date"]')[0].value;
            const dateTo = document.querySelectorAll('input[type="date"]')[1].value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            window.location.href = `branches.php?${params.toString()}`;
        }
        
        // Allow Enter key to trigger filter
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        filterBranches();
                    }
                });
            });
        });
    </script>

</body>
</html>
