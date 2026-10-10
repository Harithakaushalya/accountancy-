<?php
/**
 * Financial Reports & Analytics Model
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/database.php';

class Report {

    /**
     * Executive Overview Summary Metrics
     */
    public static function getOverview(): array {
        $db = DatabaseConnection::getInstance();

        $totalCustomers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
        $totalLoans = (int)$db->query("SELECT COUNT(*) FROM loans")->fetchColumn();
        $pendingLoans = (int)$db->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'")->fetchColumn();
        $activeLoans = (int)$db->query("SELECT COUNT(*) FROM loans WHERE status IN ('approved', 'disbursed')")->fetchColumn();

        $totalDisbursed = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loans WHERE status IN ('approved', 'disbursed', 'completed')")->fetchColumn();
        $totalCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
        $totalOutstanding = (float)$db->query("SELECT COALESCE(SUM(outstanding_balance), 0) FROM loans WHERE status IN ('approved', 'disbursed')")->fetchColumn();

        return [
            'total_customers'   => $totalCustomers,
            'total_loans'       => $totalLoans,
            'pending_loans'     => $pendingLoans,
            'active_loans'      => $activeLoans,
            'total_disbursed'   => $totalDisbursed,
            'total_collected'   => $totalCollected,
            'total_outstanding' => $totalOutstanding
        ];
    }

    /**
     * Get Monthly Financial Data for HTML & PDF Reports
     */
    public static function getMonthlyReport(int $year, int $month): array {
        $db = DatabaseConnection::getInstance();

        $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $endDate = date('Y-m-t 23:59:59', strtotime($startDate));

        $sql = "SELECT l.*, u.name AS customer_name, u.account_number
                FROM loans l
                JOIN users u ON l.user_id = u.id
                WHERE l.applied_at BETWEEN :start AND :end
                ORDER BY l.applied_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $loans = $stmt->fetchAll();

        // Calculate Totals
        $count = count($loans);
        $disbursed = 0;
        $outstanding = 0;

        foreach ($loans as $loan) {
            $disbursed += (float)$loan['amount'];
            $outstanding += (float)$loan['outstanding_balance'];
        }

        $pSql = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN :start AND :end";
        $pStmt = $db->prepare($pSql);
        $pStmt->execute([':start' => $startDate, ':end' => $endDate]);
        $collected = (float)$pStmt->fetchColumn();

        return [
            'month_name' => date('F Y', strtotime($startDate)),
            'loans'      => $loans,
            'totals'     => [
                'count'       => $count,
                'disbursed'   => $disbursed,
                'collected'   => $collected,
                'outstanding' => $outstanding
            ]
        ];
    }
}
