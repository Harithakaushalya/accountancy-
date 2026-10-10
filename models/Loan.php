<?php
/**
 * Loan Engine & Calculation Model
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Helpers.php';

class Loan {

    /**
     * Standard Financial EMI Calculation Formula
     */
    public static function calculateEMI(float $principal, float $annualRate, int $months): array {
        if ($months <= 0 || $principal <= 0) {
            return ['monthly' => 0, 'total_payable' => 0, 'total_interest' => 0];
        }

        $monthlyRate = ($annualRate / 12) / 100;
        
        if ($monthlyRate == 0) {
            $monthly = $principal / $months;
        } else {
            $monthly = ($principal * $monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
        }

        $monthly = round($monthly, 2);
        $totalPayable = round($monthly * $months, 2);
        $totalInterest = round($totalPayable - $principal, 2);

        return [
            'monthly'          => $monthly,
            'total_payable'    => $totalPayable,
            'total_interest'   => $totalInterest
        ];
    }

    /**
     * Get active loan packages
     */
    public static function getPackages(): array {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->query("SELECT * FROM loan_packages WHERE status = 'active' ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public static function getPackageById(int $id): ?array {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->prepare("SELECT * FROM loan_packages WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pkg = $stmt->fetch();
        return $pkg ?: null;
    }

    /**
     * Create new loan application
     */
    public static function apply(int $userId, int $packageId, float $amount, int $termMonths, string $purpose): string {
        $db = DatabaseConnection::getInstance();
        $package = self::getPackageById($packageId);

        if (!$package) {
            throw new Exception("Invalid loan package selected.");
        }

        if ($amount < $package['min_amount'] || $amount > $package['max_amount']) {
            throw new Exception("Amount must be between " . formatMoney($package['min_amount']) . " and " . formatMoney($package['max_amount']));
        }

        if ($termMonths < $package['min_term_months'] || $termMonths > $package['max_term_months']) {
            throw new Exception("Term must be between {$package['min_term_months']} and {$package['max_term_months']} months.");
        }

        $interestRate = (float)$package['interest_rate'];
        $calc = self::calculateEMI($amount, $interestRate, $termMonths);
        $loanNumber = generateLoanNo();

        $sql = "INSERT INTO loans (loan_number, user_id, package_id, amount, interest_rate, term_months, monthly_installment, total_payable, total_interest, outstanding_balance, purpose, status) 
                VALUES (:loan_number, :user_id, :package_id, :amount, :interest_rate, :term_months, :monthly_installment, :total_payable, :total_interest, :outstanding_balance, :purpose, 'pending')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':loan_number'         => $loanNumber,
            ':user_id'             => $userId,
            ':package_id'          => $packageId,
            ':amount'              => $amount,
            ':interest_rate'       => $interestRate,
            ':term_months'         => $termMonths,
            ':monthly_installment' => $calc['monthly'],
            ':total_payable'       => $calc['total_payable'],
            ':total_interest'      => $calc['total_interest'],
            ':outstanding_balance' => $calc['total_payable'],
            ':purpose'             => trim($purpose)
        ]);

        auditLog('LOAN_APPLICATION_SUBMITTED', "User {$userId} submitted application {$loanNumber} for " . formatMoney($amount));
        return $loanNumber;
    }

    /**
     * Review Loan (Approve / Reject) & Generate Installment Schedule if approved
     */
    public static function reviewLoan(int $loanId, string $status, int $officerId, string $notes): bool {
        $db = DatabaseConnection::getInstance();
        $db->beginTransaction();

        try {
            $loan = self::getById($loanId);
            if (!$loan) {
                throw new Exception("Loan application not found.");
            }

            $validStatuses = ['approved', 'rejected', 'disbursed'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid review status.");
            }

            $stmt = $db->prepare("UPDATE loans SET status = :status, reviewed_by = :reviewed_by, decision_notes = :notes, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':status'      => $status,
                ':reviewed_by' => $officerId,
                ':notes'       => trim($notes),
                ':id'          => $loanId
            ]);

            // If approved or disbursed, generate installment schedule if not exists
            if ($status === 'approved' || $status === 'disbursed') {
                self::generateInstallments($loanId);
            }

            $db->commit();
            auditLog('LOAN_REVIEWED', "Loan ID {$loanId} set to {$status} by Officer ID {$officerId}.");
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Generate Monthly Installments Schedule
     */
    private static function generateInstallments(int $loanId): void {
        $db = DatabaseConnection::getInstance();
        
        // Check if schedule already generated
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM installments WHERE loan_id = :loan_id");
        $checkStmt->execute([':loan_id' => $loanId]);
        if ($checkStmt->fetchColumn() > 0) {
            return;
        }

        $loan = self::getById($loanId);
        $termMonths = (int)$loan['term_months'];
        $monthlyAmount = (float)$loan['monthly_installment'];
        $principalPerMonth = round((float)$loan['amount'] / $termMonths, 2);
        $interestPerMonth = round((float)$loan['total_interest'] / $termMonths, 2);

        $startDate = new DateTime();

        for ($i = 1; $i <= $termMonths; $i++) {
            $dueDate = (clone $startDate)->modify("+{$i} month")->format('Y-m-d');
            $stmt = $db->prepare("INSERT INTO installments (loan_id, installment_number, due_date, amount_due, principal_amount, interest_amount, paid_amount, status) 
                                  VALUES (:loan_id, :num, :due, :due_amt, :p_amt, :i_amt, 0.00, 'pending')");
            $stmt->execute([
                ':loan_id' => $loanId,
                ':num'     => $i,
                ':due'     => $dueDate,
                ':due_amt' => $monthlyAmount,
                ':p_amt'   => $principalPerMonth,
                ':i_amt'   => $interestPerMonth
            ]);
        }
    }

    public static function getById(int $id): ?array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT l.*, p.name AS package_name, u.name AS customer_name, u.email AS customer_email, u.account_number, u.phone,
                       off.name AS officer_name
                FROM loans l
                JOIN loan_packages p ON l.package_id = p.id
                JOIN users u ON l.user_id = u.id
                LEFT JOIN users off ON l.reviewed_by = off.id
                WHERE l.id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $loan = $stmt->fetch();
        return $loan ?: null;
    }

    public static function getByUser(int $userId): array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT l.*, p.name AS package_name 
                FROM loans l 
                JOIN loan_packages p ON l.package_id = p.id 
                WHERE l.user_id = :user_id 
                ORDER BY l.applied_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getAll(string $statusFilter = '', string $search = ''): array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT l.*, p.name AS package_name, u.name AS customer_name, u.account_number, u.email 
                FROM loans l 
                JOIN loan_packages p ON l.package_id = p.id 
                JOIN users u ON l.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($statusFilter)) {
            $sql .= " AND l.status = :status";
            $params[':status'] = $statusFilter;
        }

        if (!empty($search)) {
            $sql .= " AND (l.loan_number LIKE :s1 OR u.name LIKE :s2 OR u.account_number LIKE :s3)";
            $searchTerm = '%' . trim($search) . '%';
            $params[':s1'] = $searchTerm;
            $params[':s2'] = $searchTerm;
            $params[':s3'] = $searchTerm;
        }

        $sql .= " ORDER BY l.applied_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getInstallments(int $loanId): array {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->prepare("SELECT * FROM installments WHERE loan_id = :loan_id ORDER BY installment_number ASC");
        $stmt->execute([':loan_id' => $loanId]);
        return $stmt->fetchAll();
    }
}
