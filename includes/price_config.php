<?php
require_once '../config/db.php';

function getCurrentPricePerKg() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT price_per_kg FROM price_settings ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? $result->price_per_kg : 780; // Default fallback
    } catch (Exception $e) {
        return 780; // Default fallback on error
    }
}

function updatePricePerKg($price, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO price_settings (price_per_kg, updated_by) VALUES (?, ?) ON DUPLICATE KEY UPDATE price_per_kg = ?, updated_by = ?");
        return $stmt->execute([$price, $user_id, $price, $user_id]);
    } catch (Exception $e) {
        return false;
    }
}
?>
