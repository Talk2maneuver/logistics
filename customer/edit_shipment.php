<?php
session_start();
require_once '../config/db.php';
require_once '../includes/price_config.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

$shipment_id = $_GET['id'] ?? null;
$success = '';
$error = '';
$shipment = null;
$shipment_items = [];

// Get current price per kg
$current_price_per_kg = getCurrentPricePerKg();

if ($shipment_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
        $stmt->execute([$shipment_id]);
        $shipment = $stmt->fetch();
        
        if (!$shipment) {
            $error = "Shipment not found.";
        } else {
            // Fetch shipment items
            $items_stmt = $pdo->prepare("SELECT * FROM shipment_items WHERE shipment_id = ?");
            $items_stmt->execute([$shipment_id]);
            $shipment_items = $items_stmt->fetchAll();
        }
    } catch (Exception $e) {
        $error = "Error fetching shipment: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $shipment) {
    $sender_name = $_POST['sender_name'] ?? '';
    $sender_phone = $_POST['sender_phone'] ?? '';
    $receiver_name = $_POST['receiver_name'] ?? '';
    $receiver_phone = $_POST['receiver_phone'] ?? '';
    $sender_address = $_POST['sender_address'] ?? '';
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

    if (!empty($sender_name) && !empty($receiver_name) && !empty($sender_address) && !empty($receiver_address) && $total_weight > 0) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update shipments table
            $stmt = $pdo->prepare("
                UPDATE shipments 
                SET sender_name = ?, sender_phone = ?, receiver_name = ?, receiver_phone = ?, 
                    sender_address = ?, receiver_address = ?, weight = ?, cost = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $sender_name, $sender_phone, $receiver_name, $receiver_phone,
                $sender_address, $receiver_address, $total_weight, $total_cost, $shipment_id
            ]);
            
            // Delete existing items
            $delete_stmt = $pdo->prepare("DELETE FROM shipment_items WHERE shipment_id = ?");
            $delete_stmt->execute([$shipment_id]);
            
            // Insert new items
            foreach ($items as $item) {
                $description = $item['description'] ?? '';
                $weight = floatval($item['weight'] ?? 0);
                
                if ($weight > 0) {
                    $insert_stmt = $pdo->prepare("INSERT INTO shipment_items (shipment_id, description, weight) VALUES (?, ?, ?)");
                    $insert_stmt->execute([$shipment_id, $description, $weight]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            $success = "Shipment updated successfully!";
            
            // Refresh shipment data
            $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
            $stmt->execute([$shipment_id]);
            $shipment = $stmt->fetch();
            
            // Refresh items data
            $items_stmt = $pdo->prepare("SELECT * FROM shipment_items WHERE shipment_id = ?");
            $items_stmt->execute([$shipment_id]);
            $shipment_items = $items_stmt->fetchAll();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating shipment: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Shipment - MurgLogistics</title>
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
                <h1>Edit Shipment</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Update shipment details below</p>
            </div>
        </header>

        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.2); color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($shipment): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2>Shipment Information</h2>
                </div>
                
                <form method="POST" style="display: grid; gap: 1.5rem; padding: 1.5rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Tracking Number</label>
                            <input type="text" value="<?= htmlspecialchars($shipment->tracking_number) ?>" readonly style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border-color); border-radius: 8px; color: white; cursor: not-allowed;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Current Status</label>
                            <input type="text" value="<?= htmlspecialchars($shipment->current_status) ?>" readonly style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--border-color); border-radius: 8px; color: white; cursor: not-allowed;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Sender Name *</label>
                            <input type="text" name="sender_name" value="<?= htmlspecialchars($shipment->sender_name) ?>" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Sender Phone</label>
                            <input type="tel" name="sender_phone" value="<?= htmlspecialchars($shipment->sender_phone) ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Receiver Name *</label>
                            <input type="text" name="receiver_name" value="<?= htmlspecialchars($shipment->receiver_name) ?>" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Receiver Phone</label>
                            <input type="tel" name="receiver_phone" value="<?= htmlspecialchars($shipment->receiver_phone) ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Sender Address *</label>
                            <textarea name="sender_address" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; min-height: 80px;"><?= htmlspecialchars($shipment->sender_address) ?></textarea>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Receiver Address *</label>
                            <textarea name="receiver_address" required style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white; min-height: 80px;"><?= htmlspecialchars($shipment->receiver_address) ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Multiple Items Section -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3 style="margin: 0; color: var(--text-primary);">Items in Shipment</h3>
                            <button type="button" onclick="addItem()" style="padding: 0.5rem 1rem; background: var(--success); color: white; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fa-solid fa-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <div id="items-container" style="display: grid; gap: 1rem;">
                            <?php if (count($shipment_items) > 0): ?>
                                <?php foreach ($shipment_items as $index => $item): ?>
                                    <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: start;">
                                        <div>
                                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Description</label>
                                            <input type="text" name="items[<?= $index ?>][description]" placeholder="Item description" value="<?= htmlspecialchars($item->description ?? '') ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                                        </div>
                                        <div>
                                            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Weight (kg) *</label>
                                            <input type="number" name="items[<?= $index ?>][weight]" placeholder="0.0" step="0.01" required oninput="calculateTotals()" value="<?= htmlspecialchars($item->weight ?? '') ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                                        </div>
                                        <div style="padding-top: 1.5rem;">
                                            <button type="button" onclick="removeItem(this)" style="padding: 0.5rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php $itemCounter = count($shipment_items); ?>
                            <?php else: ?>
                                <!-- Default item if no existing items -->
                                <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: start;">
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Description</label>
                                        <input type="text" name="items[0][description]" placeholder="Item description" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Weight (kg) *</label>
                                        <input type="number" name="items[0][weight]" placeholder="0.0" step="0.01" required oninput="calculateTotals()" value="<?= htmlspecialchars($shipment->weight ?? '') ?>" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                                    </div>
                                    <div style="padding-top: 1.5rem;">
                                        <button type="button" onclick="removeItem(this)" style="padding: 0.5rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php $itemCounter = 1; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Price Display -->
                    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center;">
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Weight</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-primary);" id="total-weight"><?= number_format($shipment->weight ?? 0, 2) ?> kg</div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">Price per kg</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-primary);">₦<?= number_format($current_price_per_kg, 2) ?></div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">Total Cost</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: var(--accent);" id="total-cost">₦<?= number_format($shipment->cost ?? 0, 2) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="submit" style="padding: 0.75rem 2rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                            <i class="fa-solid fa-save"></i> Save Changes
                        </button>
                        <a href="index.php" style="padding: 0.75rem 2rem; background: var(--border-color); color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; font-weight: 500;">
                            <i class="fa-solid fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
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

<script>
        let itemCounter = <?= $itemCounter ?? 1 ?>;
        const pricePerKg = <?= $current_price_per_kg ?>;

        function addItem() {
            const container = document.getElementById('items-container');
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: start;';
            
            itemRow.innerHTML = `
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Description</label>
                    <input type="text" name="items[${itemCounter}][description]" placeholder="Item description" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted);">Weight (kg) *</label>
                    <input type="number" name="items[${itemCounter}][weight]" placeholder="0.0" step="0.01" required oninput="calculateTotals()" style="width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                </div>
                <div style="padding-top: 1.5rem;">
                    <button type="button" onclick="removeItem(this)" style="padding: 0.5rem; background: var(--danger); color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(itemRow);
            itemCounter++;
        }

        function removeItem(button) {
            const itemRows = document.querySelectorAll('.item-row');
            if (itemRows.length > 1) {
                button.closest('.item-row').remove();
                calculateTotals();
            }
        }

        function calculateTotals() {
            const weightInputs = document.querySelectorAll('input[name*="[weight]"]');
            let totalWeight = 0;
            
            weightInputs.forEach(input => {
                const weight = parseFloat(input.value) || 0;
                totalWeight += weight;
            });
            
            const totalCost = totalWeight * pricePerKg;
            
            document.getElementById('total-weight').textContent = totalWeight.toFixed(2) + ' kg';
            document.getElementById('total-cost').textContent = '₦' + totalCost.toFixed(2);
        }

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', calculateTotals);
    </script>
</body>
</html>
