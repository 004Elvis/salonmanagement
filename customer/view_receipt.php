<?php
// customer/view_receipt.php
require '../config/db.php';

// Safe session start to prevent the "Notice" error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch detailed appointment info
$stmt = $pdo->prepare("
    SELECT a.*, s.service_name, s.price_kes, u.full_name AS beautician_name, cust.full_name AS customer_name, cust.email AS customer_email
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.staff_id = u.user_id
    JOIN users cust ON a.customer_id = cust.user_id
    WHERE a.appointment_id = ? AND a.customer_id = ?
");
$stmt->execute([$appointment_id, $user_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die("Receipt not found or unauthorized access.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $appointment_id; ?> - Elvis Salon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --body-bg: #f4f6f9;
            --text-main: #333333;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --accent: #4caf50;
        }

        body.dark-mode {
            --body-bg: #121416;
            --text-main: #f8f9fa;
            --card-bg: #212529;
            --border-color: #373b3e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s; }
        
        body { 
            background-color: var(--body-bg); 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* The Receipt Design */
        .receipt-card { 
            width: 100%;
            max-width: 500px; 
            background: #fff; /* Paper effect */
            color: #222; 
            padding: 35px; 
            border: 1px solid #ddd; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            font-family: 'Courier New', Courier, monospace;
        }

        .receipt-header { 
            text-align: center; 
            border-bottom: 2px dashed #ccc; 
            padding-bottom: 20px; 
            margin-bottom: 20px; 
        }

        .receipt-header h3 { font-family: 'Segoe UI', sans-serif; font-weight: bold; margin-bottom: 5px; }

        .details-row { margin-bottom: 10px; font-size: 15px; }
        
        .line-item { display: flex; justify-content: space-between; margin-bottom: 12px; }
        
        .total-row { 
            border-top: 2px solid #222; 
            padding-top: 15px; 
            margin-top: 20px;
            font-weight: bold; 
            font-size: 1.2rem; 
        }

        /* Buttons */
        .no-print { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 30px; font-family: 'Segoe UI', sans-serif; }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print { background: #333; color: #fff; }
        .btn-pdf { background: #e67e22; color: #fff; }
        .btn-back { background: transparent; border: 1px solid #666; color: #666; }
        body.dark-mode .btn-back { color: #adb5bd; border-color: #adb5bd; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0; }
            .receipt-card { box-shadow: none; border: none; margin: 0; width: 100%; max-width: 100%; }
        }

        /* Mobile Tweak */
        @media (max-width: 480px) {
            .receipt-card { padding: 20px; }
            .total-row { font-size: 1rem; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

<div class="receipt-card">
    <div class="receipt-header">
        <h3>ELVIS MIDEGA SALON</h3>
        <p>Nairobi, Kenya</p>
        <p>Tel: +254 700 000 000</p>
        <h5 style="margin-top: 15px; letter-spacing: 2px;">OFFICIAL RECEIPT</h5>
    </div>

    <div class="details-row">
        <p><strong>Receipt:</strong> #<?php echo str_pad($receipt['appointment_id'], 5, '0', STR_PAD_LEFT); ?></p>
        <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($receipt['appointment_date'])); ?></p>
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($receipt['customer_name']); ?></p>
    </div>

    <div style="margin: 20px 0; border-top: 1px solid #eee; padding-top: 20px;">
        <div class="line-item">
            <span>Service: <?php echo htmlspecialchars($receipt['service_name']); ?></span>
            <span>KES <?php echo number_format($receipt['price_kes'], 2); ?></span>
        </div>
        <div class="line-item">
            <span>Beautician: <?php echo htmlspecialchars($receipt['beautician_name']); ?></span>
            <span>--</span>
        </div>
    </div>

    <div class="line-item total-row">
        <span>TOTAL PAID</span>
        <span>KES <?php echo number_format($receipt['price_kes'], 2); ?></span>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <p>Thank you for visiting us!</p>
        <p style="font-size: 11px; margin-top: 10px;">Status: <?php echo strtoupper($receipt['status']); ?></p>
    </div>

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> Print
        </button>

        <a href="../actions/download_receipt.php?id=<?php echo $appointment_id; ?>" class="btn btn-pdf">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>

        <a href="dashboard.php" class="btn btn-back">Back to Dashboard</a>
    </div>
</div>

<script>
    // Global Theme Check
    const savedTheme = localStorage.getItem('customer-theme') || localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>

</body>
</html>