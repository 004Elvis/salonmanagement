<?php
// actions/download_receipt.php
require '../config/db.php'; 
require '../includes/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (isset($_GET['id'])) {
    $appointment_id = $_GET['id'];

    // 1. Fetch data using PDO (Matching your specific table/column names)
    $query = "SELECT a.*, s.service_name, s.price_kes, u.full_name AS beautician_name, cust.full_name AS customer_name
              FROM appointments a
              JOIN services s ON a.service_id = s.service_id
              JOIN users u ON a.staff_id = u.user_id
              JOIN users cust ON a.customer_id = cust.user_id
              WHERE a.appointment_id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$appointment_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // 2. Setup Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        // 3. Create the HTML Receipt Layout
        $html = "
        <style>
            body { font-family: sans-serif; color: #333; }
            .header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; }
            .details { margin-top: 20px; line-height: 1.6; }
            .item-row { display: block; margin: 10px 0; }
            .total { font-weight: bold; font-size: 1.4em; margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; text-align: right; }
            .footer { margin-top: 50px; text-align: center; font-size: 12px; font-style: italic; }
        </style>
        <div class='header'>
            <h2>ELVIS MIDEGA SALON</h2>
            <p>Nairobi, Kenya | Tel: +254 700 000 000</p>
            <h3>OFFICIAL RECEIPT</h3>
        </div>
        <div class='details'>
            <p><strong>Receipt No:</strong> #" . str_pad($data['appointment_id'], 5, '0', STR_PAD_LEFT) . "</p>
            <p><strong>Date:</strong> " . date('d M Y', strtotime($data['appointment_date'])) . "</p>
            <p><strong>Customer:</strong> {$data['customer_name']}</p>
            <hr>
            <div class='item-row'>
                <strong>Service:</strong> {$data['service_name']}<br>
                <strong>Beautician:</strong> {$data['beautician_name']}
            </div>
            <div class='total'>
                TOTAL PAID: KES " . number_format($data['price_kes'], 2) . "
            </div>
        </div>
        <div class='footer'>
            Thank you for visiting us! Stay beautiful.
        </div>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        // 4. Trigger Download
        $dompdf->stream("Receipt_#{$appointment_id}.pdf", array("Attachment" => 1));
    } else {
        echo "Receipt data not found.";
    }
}