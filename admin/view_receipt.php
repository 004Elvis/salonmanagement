<?php
// admin/view_receipt.php
require '../config/db.php';

// Safe session start to prevent "Notice" error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_appointments.php");
    exit();
}

$appointment_id = $_GET['id'];

// Fetch detailed info - Added customer_email for automated emailing
$stmt = $pdo->prepare("
    SELECT a.*, s.service_name, s.price_kes, u.full_name AS beautician_name, cust.full_name AS customer_name, cust.email AS customer_email
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.staff_id = u.user_id
    JOIN users cust ON a.customer_id = cust.user_id
    WHERE a.appointment_id = ?
");
$stmt->execute([$appointment_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die("Receipt not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Receipt View #<?php echo $appointment_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f4f6f9;
            --text-main: #333333;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --accent: #4caf50;
        }

        body.dark-mode {
            --bg-color: #121416;
            --text-main: #f8f9fa;
            --card-bg: #212529;
            --border-color: #373b3e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s; }
        
        body { 
            background-color: var(--bg-color); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* The Receipt Card */
        .receipt-card { 
            width: 100%;
            max-width: 500px; 
            background: #fff; 
            color: #333; 
            padding: 40px; 
            border: 1px solid #ccc; 
            position: relative; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            font-family: 'Courier New', Courier, monospace;
        }

        .receipt-header { 
            text-align: center; 
            border-bottom: 2px dashed #333; 
            padding-bottom: 20px; 
            margin-bottom: 25px; 
        }

        .receipt-header h3 { margin-bottom: 5px; letter-spacing: 1px; }
        
        .admin-badge { 
            position: absolute; 
            top: 10px; 
            right: 10px; 
            font-size: 10px; 
            color: #999; 
            text-transform: uppercase; 
            font-family: sans-serif;
        }

        .details p { margin-bottom: 10px; font-size: 15px; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-section h4 { border-top: 1px solid #333; display: inline-block; padding-top: 10px; font-size: 20px; }

        /* Navigation Buttons */
        .btn-container { 
            margin-top: 30px; 
            display: flex; 
            gap: 10px; 
            justify-content: center; 
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-family: sans-serif;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print { background: var(--accent); color: white; }
        .btn-pdf { background: #e67e22; color: white; }
        .btn-back { background: #6c757d; color: white; }

        @media print { 
            .no-print { display: none !important; } 
            body { background: #fff; padding: 0; } 
            .receipt-card { border: none; box-shadow: none; margin: 0; max-width: 100%; } 
        }

        /* Mobile Adjustments */
        @media (max-width: 480px) {
            .receipt-card { padding: 20px; }
            .receipt-header h3 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <span class="admin-badge no-print">Official Admin Copy</span>
    
    <div class="receipt-header">
        <h3>ELVIS MIDEGA SALON</h3>
        <p>Nairobi, Kenya</p>
        <p>Tel: +254 700 000 000</p>
        <h5 style="margin-top: 10px; text-decoration: underline;">OFFICIAL RECEIPT</h5>
    </div>

    <div class="details">
        <p><strong>Receipt No:</strong> #<?php echo str_pad($receipt['appointment_id'], 5, '0', STR_PAD_LEFT); ?></p>
        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($receipt['appointment_date'])); ?></p>
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($receipt['customer_name']); ?></p>
        <p><strong>Service:</strong> <?php echo htmlspecialchars($receipt['service_name']); ?></p>
        <p><strong>Staff:</strong> <?php echo htmlspecialchars($receipt['beautician_name']); ?></p>
    </div>

    <div class="total-section">
        <h4>TOTAL: KES <?php echo number_format($receipt['price_kes'], 2); ?></h4>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 12px; font-style: italic;">
        Thank you for choosing Elvis Midega Salon!
    </div>

    <div class="btn-container no-print">
        <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print Receipt</button>
        
        <a href="../actions/download_receipt.php?id=<?php echo $appointment_id; ?>" class="btn btn-pdf">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>

        <a href="admin_appointments.php" class="btn btn-back">Back to List</a>
    </div>
</div>

<script>
    // Apply Global Theme
    const savedTheme = localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>

</body>
</html>