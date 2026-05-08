<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

$shipment_id = $_GET['id'] ?? null;
$error = '';

if ($shipment_id) {
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE id = ?");
    $stmt->execute([$shipment_id]);
    $shipment = $stmt->fetch();

    if (!$shipment) {
        $error = "Shipment not found.";
    }
} else {
    $error = "No tracking ID provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= htmlspecialchars($shipment->tracking_number ?? 'Unknown') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;566
            padding: 2rem;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 40px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #6366f1;
            margin: 0;
            font-size: 2rem;
        }
        .header p {
            margin: 5px 0 0;
            color: #64748b;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            margin: 0;
            font-size: 2.5rem;
            color: #0f172a;
            text-transform: uppercase;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .details-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .details-box h3 {
            margin-top: 0;
            color: #334155;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 10px;
        }
        .table-invoice {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table-invoice th, .table-invoice td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-invoice th {
            background: #f8fafc;
            color: #475569;
        }
        .total-row td {
            font-weight: 700;
            font-size: 1.25rem;
            background: #f1f5f9;
        }
        .btn-print {
            display: block;
            width: 200px;
            margin: 30px auto 0;
            padding: 12px;
            text-align: center;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .btn-print:hover { background: #4f46e5; }
        @media print {
            body { padding: 0; background: white; }
            .invoice-box { box-shadow: none; padding: 0; }
            .btn-print { display: none; }
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .invoice-box {
                padding: 20px;
                margin: 0;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .invoice-title h2 {
                font-size: 1.75rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .table-invoice {
                font-size: 0.875rem;
            }
            
            .table-invoice th,
            .table-invoice td {
                padding: 10px 5px;
            }
            
            .btn-print {
                width: 100%;
                margin: 20px 0 0 0;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }
            
            .invoice-box {
                padding: 15px;
                border-radius: 8px;
            }
            
            .header h1 {
                font-size: 1.25rem;
            }
            
            .invoice-title h2 {
                font-size: 1.5rem;
            }
            
            .header p,
            .invoice-title p {
                font-size: 0.8rem;
            }
            
            .details-box {
                padding: 15px;
            }
            
            .details-box h3 {
                font-size: 0.9rem;
            }
            
            .table-invoice {
                font-size: 0.8rem;
            }
            
            .table-invoice th,
            .table-invoice td {
                padding: 8px 3px;
                font-size: 0.75rem;
            }
            
            .table-invoice th {
                font-size: 0.7rem;
            }
            
            .total-row td {
                font-size: 0.9rem;
                padding: 8px 3px;
            }
            
            .btn-print {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 360px) {
            .invoice-box {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 1.125rem;
            }
            
            .invoice-title h2 {
                font-size: 1.25rem;
            }
            
            .table-invoice {
                font-size: 0.75rem;
            }
            
            .table-invoice th,
            .table-invoice td {
                padding: 6px 2px;
                font-size: 0.7rem;
            }
            
            .table-invoice th {
                font-size: 0.65rem;
            }
            
            .btn-print {
                padding: 8px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

<?php if ($error): ?>
    <div class="invoice-box" style="text-align: center; color: red;">
        <h2><?= $error ?></h2>
    </div>
<?php else: ?>
    <div class="invoice-box">
        <div class="header">
            <div>
                <h1>MurgLogistics</h1>
                <p>Shop No. 1 & 2 Gidan Murtala Jega<br>Layin Kwarin Me Shayi<br>IBB way Kwari Market Kano<br>Kano, Nigeria<br>billing@murglogistics.com</p>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p><strong>Tracking No:</strong> <?= htmlspecialchars($shipment->tracking_number) ?></p>
                <p><strong>Date:</strong> <?= date('F d, Y', strtotime($shipment->created_at)) ?></p>
            </div>
        </div>

        <div class="details-grid">
            <div class="details-box">
                <h3>Shipper / Sender</h3>
                <strong><?= htmlspecialchars($shipment->sender_name) ?></strong><br>
                <?= nl2br(htmlspecialchars($shipment->sender_address)) ?><br>
                Phone: <?= htmlspecialchars($shipment->sender_phone) ?>
            </div>
            <div class="details-box">
                <h3>Consignee / Receiver</h3>
                <strong><?= htmlspecialchars($shipment->receiver_name) ?></strong><br>
                <?= nl2br(htmlspecialchars($shipment->receiver_address)) ?><br>
                Phone: <?= htmlspecialchars($shipment->receiver_phone) ?>
            </div>
        </div>

        <table class="table-invoice">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Weight</th>
                    <th>Unit Cost</th>
                    <th style="text-align: right;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Logistics Freight Services for Parcel <?= htmlspecialchars($shipment->tracking_number) ?></td>
                    <td><?= htmlspecialchars($shipment->weight) ?> kg</td>
                    <td>₦<?= number_format($shipment->cost / max($shipment->weight, 1), 2) ?>/kg</td>
                    <td style="text-align: right;">₦<?= number_format($shipment->cost, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; color: #64748b;">Subtotal:</td>
                    <td style="text-align: right;">₦<?= number_format($shipment->cost, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; color: #64748b;">Tax (0%):</td>
                    <td style="text-align: right;">₦0.00</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; border-bottom: none;">GRAND TOTAL:</td>
                    <td style="text-align: right; border-bottom: none; color: #6366f1;">₦<?= number_format($shipment->cost, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <button class="btn-print" onclick="window.print()">Print Invoice PDF</button>
    </div>
<?php endif; ?>

</body>
</html>
