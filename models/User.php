<?php
/**
 * User Model
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Helpers.php';

class User {

    /**
     * Create new user record (Customer or Staff)
     */
    public static function create(array $data): int {
        $db = DatabaseConnection::getInstance();
        $accountNo = generateAccountNo($data['role'] ?? 'customer');
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 11]);

        $sql = "INSERT INTO users (account_number, name, email, password_hash, role, phone, national_id, address, occupation, monthly_income, status) 
                VALUES (:account_number, :name, :email, :password_hash, :role, :phone, :national_id, :address, :occupation, :monthly_income, :status)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':account_number' => $accountNo,
            ':name'           => trim($data['name']),
            ':email'          => strtolower(trim($data['email'])),
            ':password_hash'  => $passwordHash,
            ':role'           => $data['role'] ?? 'customer',
            ':phone'          => trim($data['phone'] ?? ''),
            ':national_id'    => trim($data['national_id'] ?? ''),
            ':address'        => trim($data['address'] ?? ''),
            ':occupation'     => trim($data['occupation'] ?? ''),
            ':monthly_income' => (float)($data['monthly_income'] ?? 0),
            ':status'         => $data['status'] ?? 'active'
        ]);

        $newId = (int)$db->lastInsertId();
        auditLog('USER_CREATED', "New user account created: {$data['email']} with role {$data['role']}.");
        return $newId;
    }

    public static function findByEmail(string $email): ?array {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->prepare("SELECT id, account_number, name, email, role, phone, national_id, address, occupation, monthly_income, status, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Search and Filter Customers (with PDO parameter binding)
     */
    public static function getAllCustomers(string $search = ''): array {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT id, account_number, name, email, phone, national_id, status, created_at FROM users WHERE role = 'customer'";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (name LIKE :s1 OR email LIKE :s2 OR account_number LIKE :s3 OR national_id LIKE :s4)";
            $searchTerm = '%' . trim($search) . '%';
            $params = [
                ':s1' => $searchTerm,
                ':s2' => $searchTerm,
                ':s3' => $searchTerm,
                ':s4' => $searchTerm
            ];
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateProfile(int $id, array $data): bool {
        $db = DatabaseConnection::getInstance();
        $sql = "UPDATE users SET name = :name, phone = :phone, address = :address, occupation = :occupation, monthly_income = :monthly_income WHERE id = :id";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':name'           => trim($data['name']),
            ':phone'          => trim($data['phone']),
            ':address'        => trim($data['address']),
            ':occupation'     => trim($data['occupation']),
            ':monthly_income' => (float)$data['monthly_income'],
            ':id'             => $id
        ]);
        auditLog('PROFILE_UPDATED', "User ID {$id} updated profile details.");
        return $result;
    }

    public static function updatePassword(int $id, string $newPassword): bool {
        $db = DatabaseConnection::getInstance();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 11]);
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $res = $stmt->execute([':hash' => $hash, ':id' => $id]);
        auditLog('PASSWORD_CHANGED', "User ID {$id} updated password.");
        return $res;
    }
}
