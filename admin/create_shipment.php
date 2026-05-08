<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/price_config.php';

// Only allow admin and manager
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Get current price per kg
$current_price_per_kg = getCurrentPricePerKg();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_name = $_POST['sender_name'] ?? '';
    $sender_phone = $_POST['sender_phone'] ?? '';
    $sender_address = $_POST['sender_address'] ?? '';
    
    $receiver_name = $_POST['receiver_name'] ?? '';
    $receiver_phone = $_POST['receiver_phone'] ?? '';
    $receiver_address = $_POST['receiver_address'] ?? '';
    
    // Handle multiple items
    $items = $_POST['items'] ?? [];
    $total_weight = 0;
    $total_cost = 0;
    
    if (!empty($items)) {
        foreach ($items as $item) {
            $weight = floatval($item['weight'] ?? 0);
            $total_weight += $weight;
            $total_cost += $weight * $current_price_per_kg;
        }
    }
    
    // Update price per kg if admin changed it
    if ($_SESSION['user_role'] == 'admin') {
        $new_price_per_kg = floatval($_POST['price_per_kg'] ?? $current_price_per_kg);
        if ($new_price_per_kg != $current_price_per_kg) {
            updatePricePerKg($new_price_per_kg, $_SESSION['user_id']);
            $current_price_per_kg = $new_price_per_kg;
            // Recalculate costs with new price
            $total_cost = 0;
            foreach ($items as $item) {
                $weight = floatval($item['weight'] ?? 0);
                $total_cost += $weight * $current_price_per_kg;
            }
        }
    }
    
    // Generate a unique tracking number
    $tracking_number = 'TRK-' . strtoupper(uniqid()) . rand(10, 99);

    if (!empty($sender_name) && !empty($receiver_name) && !empty($sender_address) && !empty($receiver_address) && $total_weight > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO shipments (tracking_number, user_id, sender_name, sender_phone, sender_address, receiver_name, receiver_phone, receiver_address, weight, cost, current_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            
            $stmt->execute([
                $tracking_number, $_SESSION['user_id'], $sender_name, $sender_phone, $sender_address,
                $receiver_name, $receiver_phone, $receiver_address,
                $total_weight, $total_cost
            ]);
            
            $shipment_id = $pdo->lastInsertId();
            
            // Add initial history record
            $hist_stmt = $pdo->prepare("INSERT INTO tracking_history (shipment_id, status, location, remarks, updated_by) VALUES (?, 'Pending', 'Origin Hub', 'Shipment Registered', ?)");
            $hist_stmt->execute([$shipment_id, $_SESSION['user_id']]);
            
            // Redirect to invoice page
            header("Location: invoice.php?id=$shipment_id");
            exit;
        } catch (PDOException $e) {
            $error = "Error adding shipment: " . $e->getMessage();
        }
    } else {
        $error = "Sender and Receiver names are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Shipment - MurgLogistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }
        .form-section {
            background: rgba(30, 41, 59, 0.4);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: white;
        }
        .btn-submit {
            padding: 1rem 2rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 1.5rem;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: var(--accent-hover);
        }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); color: #6ee7b7; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger); color: #fca5a5; }
    </style>
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
            <li><a href="#"><i class="fa-solid fa-truck"></i> Fleet & Vehicles</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Staff & Drivers</a></li>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <li><a href="#"><i class="fa-solid fa-building"></i> Branches</a></li>
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
                <h1>Create New Shipment</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Register a new parcel into the network.</p>
            </div>
            
            <div class="user-profile">
                <!-- Simple avatar with initials -->
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <div class="table-container">
            <?php if($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form action="create_shipment.php" method="POST">
                <div class="form-grid">
                    
                    <!-- Sender Details -->
                    <div class="form-section">
                        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;"><i class="fa-solid fa-user"></i> Sender Details</h3>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="sender_name" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="sender_phone" required>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="sender_address" rows="3" required></textarea>
                        </div>
                    </div>

                    <!-- Receiver Details -->
                    <div class="form-section">
                        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;"><i class="fa-solid fa-location-dot"></i> Receiver Details</h3>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="receiver_name" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="receiver_phone" required>
                        </div>
                        <div class="form-group">
                            <label>Delivery Address</label>
                            <textarea name="receiver_address" rows="3" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Multiple Items Section -->
                <div class="form-section" style="margin-top: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="margin: 0;"><i class="fa-solid fa-weight-scale"></i> Items in Shipment</h3>
                        <button type="button" onclick="addItem()" style="padding: 0.5rem 1rem; background: var(--success); color: white; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <div id="items-container" style="display: grid; gap: 1rem;">
                        <!-- First item (default) -->
                        <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: start;">
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="items[0][description]" placeholder="Item description">
                            </div>
                            <div class="form-group">
                                <label>Weight (kg) *</label>
                                <input type="number" name="items[0][weight]" placeholder="0.0" step="0.01" required oninput="calculateTotals()">
                            </div>
                            <div style="padding-top: 1.5rem;">
                                <button type="button" onclick="removeItem(this)" style="padding: 0.5rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Price Configuration -->
                <div class="form-section" style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-cog"></i> Price Configuration</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div class="form-group">
                            <label>Price per kg (₦)</label>
                            <input type="number" step="0.01" name="price_per_kg" value="<?= $current_price_per_kg ?>" required id="price_per_kg" oninput="calculateTotals()" <?= $_SESSION['user_role'] != 'admin' ? 'readonly' : '' ?>>
                            <?php if ($_SESSION['user_role'] != 'admin'): ?>
                                <small style="color: var(--text-muted);">Only admins can change price per kg</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Total Items</label>
                            <input type="text" value="1" readonly style="background: rgba(15, 23, 42, 0.8); cursor: not-allowed;" id="total-items">
                        </div>
                    </div>
                </div>
                
                <!-- Price Display -->
                <div class="form-section" style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-calculator"></i> Cost Summary</h3>
                    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center;">
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Weight</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-primary);" id="total-weight">0.00 kg</div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">Price per kg</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-primary);" id="display-price">₦<?= number_format($current_price_per_kg, 2) ?></div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Cost</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--accent);" id="total-cost">₦0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Register Shipment</button>
            </form>
        </div>
    </main>

<script>
        let itemCounter = 1;
        let currentPricePerKg = <?= $current_price_per_kg ?>;

        function addItem() {
            const container = document.getElementById('items-container');
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: start;';
            
            itemRow.innerHTML = `
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="items[${itemCounter}][description]" placeholder="Item description">
                </div>
                <div class="form-group">
                    <label>Weight (kg) *</label>
                    <input type="number" name="items[${itemCounter}][weight]" placeholder="0.0" step="0.01" required oninput="calculateTotals()">
                </div>
                <div style="padding-top: 1.5rem;">
                    <button type="button" onclick="removeItem(this)" style="padding: 0.5rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(itemRow);
            itemCounter++;
            updateItemCount();
        }

        function removeItem(button) {
            const itemRows = document.querySelectorAll('.item-row');
            if (itemRows.length > 1) {
                button.closest('.item-row').remove();
                calculateTotals();
                updateItemCount();
            }
        }

        function updateItemCount() {
            const itemRows = document.querySelectorAll('.item-row');
            document.getElementById('total-items').value = itemRows.length;
        }

        function calculateTotals() {
            const weightInputs = document.querySelectorAll('input[name*="[weight]"]');
            let totalWeight = 0;
            
            weightInputs.forEach(input => {
                const weight = parseFloat(input.value) || 0;
                totalWeight += weight;
            });
            
            const pricePerKg = parseFloat(document.getElementById('price_per_kg').value) || currentPricePerKg;
            const totalCost = totalWeight * pricePerKg;
            
            document.getElementById('total-weight').textContent = totalWeight.toFixed(2) + ' kg';
            document.getElementById('display-price').textContent = '₦' + pricePerKg.toFixed(2);
            document.getElementById('total-cost').textContent = '₦' + totalCost.toFixed(2);
        }

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals();
            updateItemCount();
        });
    </script>
</body>
</html>
