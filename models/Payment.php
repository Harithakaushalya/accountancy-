<?php
/**
 * Payment & Receipt Management Model
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/Loan.php';

class Payment {

    /**
     * Process & Record an Installment Payment
     */
    public static function record(int $loanId, ?int $installmentId, float $amount, string $method, string $reference, int $recordedBy, string $notes = ''): string {
        $db = DatabaseConnection::getInstance();
        $db->beginTransaction();

        try {
            $loan = Loan::getById($loanId);
            if (!$loan) {
                throw new Exception("Loan record not found.");
            }

            if ($amount <= 0) {
                throw new Exception("Payment amount must be greater than zero.");
            }

            $receiptNo = generateReceiptNo();

            // Insert Payment Record
            $sql = "INSERT INTO payments (receipt_number, loan_id, installment_id, user_id, amount, payment_method, transaction_reference, recorded_by, notes) 
                    VALUES (:receipt_number, :loan_id, :installment_id, :user_id, :amount, :payment_method, :transaction_reference, :recorded_by, :notes)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':receipt_number'        => $receiptNo,
                ':loan_id'               => $loanId,
                ':installment_id'        => $installmentId,
                ':user_id'               => $loan['user_id'],
                ':amount'                => $amount,
                ':payment_method'        => $method,
                ':transaction_reference' => trim($reference),
                ':recorded_by'           => $recordedBy,
                ':notes'                 => trim($notes)
            ]);

            // Update Installment status if specified
            if ($installmentId) {
                $instStmt = $db->prepare("SELECT * FROM installments WHERE id = :id LIMIT 1");
                $instStmt->execute([':id' => $installmentId]);
                $inst = $instStmt->fetch();

                if ($inst) {
                    $newPaidAmount = (float)$inst['paid_amount'] + $amount;
                    $instStatus = ($newPaidAmount >= (float)$inst['amount_due']) ? 'paid' : 'partial';

                    $uInst = $db->prepare("UPDATE installments SET paid_amount = :paid_amt, status = :status, paid_at = NOW() WHERE id = :id");
                    $uInst->execute([
                        ':paid_amt' => $newPaidAmount,
                        ':status'   => $instStatus,
                        ':id'       => $installmentId
                    ]);
                }
            }

            // Deduct outstanding balance on loan
            $newBalance = max(0.00, (float)$loan['outstanding_balance'] - $amount);
            $loanStatus = ($newBalance <= 0) ? 'completed' : $loan['status'];

            $uLoan = $db->prepare("UPDATE loans SET outstanding_balance = :balance, status = :status WHERE id = :id");
            $uLoan->execute([
                ':balance' => $newBalance,
                ':status'  => $loanStatus,
                ':id'      => $loanId
            ]);

            $db->commit();
            auditLog('PAYMENT_RECORDED', "Recorded payment of " . formatMoney($amount) . " for loan {$loan['loan_number']} (Receipt: {$receiptNo})");
            return $receiptNo;

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Get Complete Details for Receipt Generation
     */
    public static function getByReceipt(string $receiptNo): ?array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT p.*, l.loan_number, l.outstanding_balance, l.interest_rate,
                       u.name AS customer_name, u.account_number, u.email, u.phone,
                       pkg.name AS package_name,
                       rec.name AS recorded_by_name
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN users u ON p.user_id = u.id
                JOIN loan_packages pkg ON l.package_id = pkg.id
                JOIN users rec ON p.recorded_by = rec.id
                WHERE p.receipt_number = :receipt LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':receipt' => trim($receiptNo)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get payments for a specific user
     */
    public static function getByUser(int $userId): array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT p.*, l.loan_number 
                FROM payments p 
                JOIN loans l ON p.loan_id = l.id 
                WHERE p.user_id = :user_id 
                ORDER BY p.payment_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all payments with search filter
     */
    public static function getAll(string $search = ''): array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT p.*, l.loan_number, u.name AS customer_name, u.account_number, rec.name AS recorded_by_name
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN users u ON p.user_id = u.id
                JOIN users rec ON p.recorded_by = rec.id
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (p.receipt_number LIKE :s1 OR l.loan_number LIKE :s2 OR u.name LIKE :s3)";
            $searchTerm = '%' . trim($search) . '%';
            $params[':s1'] = $searchTerm;
            $params[':s2'] = $searchTerm;
            $params[':s3'] = $searchTerm;
        }

        $sql .= " ORDER BY p.payment_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
