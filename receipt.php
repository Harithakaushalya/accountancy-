<?php
/**
 * PDF Export Endpoint for Payment Receipts & Monthly Reports
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PDF.php';
require_once __DIR__ . '/models/Payment.php';
require_once __DIR__ . '/models/Report.php';

Auth::requireLogin();
$user = Auth::user();

// Handle Monthly Report PDF Download
if (isset($_GET['report']) && $_GET['report'] === 'true') {
    Auth::requireRole(['admin', 'officer']);

    $year = (int)($_GET['year'] ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('m'));

    $reportData = Report::getMonthlyReport($year, $month);
    PDFService::generateMonthlyReport($reportData['month_name'], $reportData['loans'], $reportData['totals']);
    exit();
}

// Handle Payment Receipt PDF Download
$receiptNo = $_GET['receipt'] ?? '';

if (empty($receiptNo)) {
    die("Receipt number not specified.");
}

$payment = Payment::getByReceipt($receiptNo);

if (!$payment) {
    die("Payment receipt not found.");
}

// Security Check: Customer can only download their own receipt
if (Auth::role() === 'customer' && (int)$payment['user_id'] !== (int)$user['id']) {
    die("Unauthorized access to receipt.");
}

PDFService::generateReceipt($payment);
