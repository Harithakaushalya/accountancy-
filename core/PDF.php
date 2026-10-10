<?php
/**
 * PDF Template Service (Red, Black, White theme)
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/FPDF.php';
require_once __DIR__ . '/Helpers.php';

class PDFService {

    /**
     * Generate & Download Official Payment Receipt PDF
     */
    public static function generateReceipt(array $payment): void {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->addPage();

        // Colors
        $pdf->setFillColor(220, 38, 38); // Crimson Red #DC2626
        $pdf->setTextColor(255, 255, 255);
        
        // Header Banner
        $pdf->rect(10, 10, 190, 25, 'F');
        $pdf->setFont('Helvetica', 'B', 16);
        $pdf->cell(0, 10, 'APEXFINANCE - PAYMENT RECEIPT', 0, 1, 'C');
        $pdf->setFont('Helvetica', '', 10);
        $pdf->cell(0, 5, 'Official Financial Transaction Statement', 0, 1, 'C');

        $pdf->ln(15);
        $pdf->setTextColor(17, 24, 39); // Charcoal Black #111827

        // Receipt Information Box
        $pdf->setFont('Helvetica', 'B', 12);
        $pdf->cell(100, 8, 'Receipt No: ' . $payment['receipt_number'], 0, 0, 'L');
        $pdf->cell(90, 8, 'Date: ' . formatDate($payment['payment_date'], 'Y-m-d H:i'), 0, 1, 'R');

        $pdf->line(10, 52, 200, 52);
        $pdf->ln(8);

        // Customer & Loan Details Table
        $pdf->setFont('Helvetica', 'B', 10);
        $pdf->setFillColor(243, 244, 246);
        $pdf->cell(95, 8, 'Customer Details', 1, 0, 'L', true);
        $pdf->cell(95, 8, 'Loan Information', 1, 1, 'L', true);

        $pdf->setFont('Helvetica', '', 10);
        $pdf->cell(95, 8, 'Name: ' . $payment['customer_name'], 1, 0, 'L');
        $pdf->cell(95, 8, 'Loan No: ' . $payment['loan_number'], 1, 1, 'L');

        $pdf->cell(95, 8, 'Account No: ' . $payment['account_number'], 1, 0, 'L');
        $pdf->cell(95, 8, 'Package: ' . $payment['package_name'], 1, 1, 'L');

        $pdf->cell(95, 8, 'Phone: ' . ($payment['phone'] ?? 'N/A'), 1, 0, 'L');
        $pdf->cell(95, 8, 'Interest Rate: ' . $payment['interest_rate'] . '% p.a.', 1, 1, 'L');

        $pdf->ln(10);

        // Payment Summary Highlight Box
        $pdf->setFillColor(17, 24, 39); // Black
        $pdf->setTextColor(255, 255, 255);
        $pdf->setFont('Helvetica', 'B', 11);
        $pdf->cell(190, 9, 'PAYMENT SUMMARY', 1, 1, 'C', true);

        $pdf->setTextColor(17, 24, 39);
        $pdf->setFont('Helvetica', '', 10);
        $pdf->cell(95, 8, 'Amount Paid:', 1, 0, 'L');
        $pdf->setFont('Helvetica', 'B', 11);
        $pdf->cell(95, 8, formatMoney($payment['amount']), 1, 1, 'R');

        $pdf->setFont('Helvetica', '', 10);
        $pdf->cell(95, 8, 'Payment Method:', 1, 0, 'L');
        $pdf->cell(95, 8, strtoupper($payment['payment_method']), 1, 1, 'R');

        $pdf->cell(95, 8, 'Transaction Reference:', 1, 0, 'L');
        $pdf->cell(95, 8, $payment['transaction_reference'] ?: 'N/A', 1, 1, 'R');

        $pdf->cell(95, 8, 'Remaining Outstanding Balance:', 1, 0, 'L');
        $pdf->setFont('Helvetica', 'B', 11);
        $pdf->cell(95, 8, formatMoney($payment['outstanding_balance']), 1, 1, 'R');

        $pdf->ln(20);

        // Footer / Signature Section
        $pdf->setFont('Helvetica', '', 9);
        $pdf->cell(95, 6, 'Processed By: ' . $payment['recorded_by_name'], 0, 0, 'L');
        $pdf->cell(95, 6, 'Authorized Signature: __________________', 0, 1, 'R');

        $pdf->ln(15);
        $pdf->setFont('Helvetica', '', 8);
        $pdf->setTextColor(107, 114, 128);
        $pdf->cell(0, 5, 'This is a computer-generated receipt. Verified securely by ApexFinance Management System.', 0, 1, 'C');

        $pdf->output('D', 'Receipt_' . $payment['receipt_number'] . '.pdf');
    }

    /**
     * Generate & Download Monthly Loan Report PDF
     */
    public static function generateMonthlyReport(string $monthName, array $loans, array $totals): void {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->addPage();

        // Banner Header
        $pdf->setFillColor(220, 38, 38);
        $pdf->rect(10, 10, 190, 22, 'F');
        $pdf->setTextColor(255, 255, 255);
        $pdf->setFont('Helvetica', 'B', 15);
        $pdf->cell(0, 10, 'APEXFINANCE - MONTHLY FINANCIAL REPORT', 0, 1, 'C');
        $pdf->setFont('Helvetica', '', 10);
        $pdf->cell(0, 5, 'Period: ' . $monthName, 0, 1, 'C');

        $pdf->ln(12);
        $pdf->setTextColor(17, 24, 39);

        // Executive Summary Bar
        $pdf->setFont('Helvetica', 'B', 10);
        $pdf->setFillColor(243, 244, 246);
        $pdf->cell(47, 8, 'Total Loans: ' . $totals['count'], 1, 0, 'C', true);
        $pdf->cell(47, 8, 'Disbursed: ' . formatMoney($totals['disbursed']), 1, 0, 'C', true);
        $pdf->cell(48, 8, 'Collected: ' . formatMoney($totals['collected']), 1, 0, 'C', true);
        $pdf->cell(48, 8, 'Outstanding: ' . formatMoney($totals['outstanding']), 1, 1, 'C', true);

        $pdf->ln(8);

        // Table Header
        $pdf->setFillColor(17, 24, 39);
        $pdf->setTextColor(255, 255, 255);
        $pdf->setFont('Helvetica', 'B', 9);
        $pdf->cell(30, 8, 'Loan No', 1, 0, 'L', true);
        $pdf->cell(45, 8, 'Customer', 1, 0, 'L', true);
        $pdf->cell(30, 8, 'Amount', 1, 0, 'R', true);
        $pdf->cell(30, 8, 'Installment', 1, 0, 'R', true);
        $pdf->cell(30, 8, 'Balance', 1, 0, 'R', true);
        $pdf->cell(25, 8, 'Status', 1, 1, 'C', true);

        $pdf->setTextColor(17, 24, 39);
        $pdf->setFont('Helvetica', '', 8);

        foreach ($loans as $loan) {
            $pdf->cell(30, 7, $loan['loan_number'], 1, 0, 'L');
            $pdf->cell(45, 7, substr($loan['customer_name'], 0, 22), 1, 0, 'L');
            $pdf->cell(30, 7, formatMoney($loan['amount']), 1, 0, 'R');
            $pdf->cell(30, 7, formatMoney($loan['monthly_installment']), 1, 0, 'R');
            $pdf->cell(30, 7, formatMoney($loan['outstanding_balance']), 1, 0, 'R');
            $pdf->cell(25, 7, strtoupper($loan['status']), 1, 1, 'C');
        }

        $pdf->output('D', 'Monthly_Loan_Report_' . str_replace(' ', '_', $monthName) . '.pdf');
    }
}
