<?php
require_once 'config/db.php';

$tracking_param = $_GET['id'] ?? '';
$shipment = null;
$history = [];
$error = '';

if (!empty($tracking_param)) {
    // Lookup shipment
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_number = ? LIMIT 1");
    $stmt->execute([$tracking_param]);
    $shipment = $stmt->fetch();

    if ($shipment) {
        // Get history events
        $hist_stmt = $pdo->prepare("SELECT * FROM tracking_history WHERE shipment_id = ? ORDER BY id DESC");
        $hist_stmt->execute([$shipment->id]);
        $history = $hist_stmt->fetchAll();
    } else {
        $error = "Tracking number not found. Please verify and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Parcel - MurgLogistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/track.css">
</head>
<body>

    <header class="tracking-header">
        <h1><i class="fa-solid fa-earth-americas"></i> MurgLogistics Tracking</h1>
        <p>Enter your Tracking ID to view the latest real-time status of your parcel.</p>
        
        <form action="track.php" method="GET" class="search-box">
            <input type="text" name="id" placeholder="e.g. TRK-ABC12345" value="<?= htmlspecialchars($tracking_param) ?>" required>
            <button type="submit">Track Parcel <i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </header>

    <?php if (!empty($tracking_param)): ?>
        <div class="tracking-container">
            <?php if ($error): ?>
                <div class="not-found">
                    <i class="fa-solid fa-box-open"></i>
                    <h2>Oops!</h2>
                    <p><?= $error ?></p>
                </div>
            <?php elseif ($shipment): ?>
                <!-- Parcel Summary Info -->
                <div class="parcel-info">
                    <div class="info-block">
                        <span>Receiver</span>
                        <h3><?= htmlspecialchars($shipment->receiver_name) ?></h3>
                        <p style="margin: 5px 0 0; color: #475569;"><?= htmlspecialchars($shipment->receiver_address) ?></p>
                    </div>
                    <div class="info-block">
                        <span>Current Status</span>
                        <h3>
                            <?php if ($shipment->current_status == 'Delivered'): ?>
                                <span style="color: #10b981;"><i class="fa-solid fa-circle-check"></i> Delivered</span>
                            <?php else: ?>
                                <span style="color: #f59e0b;"><i class="fa-solid fa-truck-fast"></i> <?= htmlspecialchars($shipment->current_status) ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>
                </div>

                <!-- Vertical Timeline -->
                <h3 style="margin-bottom: 2rem; color: #334155;">Tracking History</h3>
                <div class="timeline">
                    <?php foreach ($history as $index => $event): ?>
                    <div class="timeline-item <?= ($index === 0) ? 'active' : '' ?>">
                        <div class="timeline-icon"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">
                                <i class="fa-regular fa-clock"></i> <?= date('M d, Y - H:i A', strtotime($event->updated_at)) ?>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event->location) ?>
                            </div>
                            <h4 class="timeline-title"><?= htmlspecialchars($event->status) ?></h4>
                            <?php if ($event->remarks): ?>
                                <p class="timeline-desc"><?= htmlspecialchars($event->remarks) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html>
