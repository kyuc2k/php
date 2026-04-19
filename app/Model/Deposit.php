<?php

class Deposit {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance();
    }

    public function create($userId, $amount, $vnpTxnRef) {
        $stmt = $this->db->prepare("INSERT INTO deposits (user_id, amount, vnp_txn_ref, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("ids", $userId, $amount, $vnpTxnRef);
        return $stmt->execute();
    }

    public function getByTxnRef($vnpTxnRef) {
        $stmt = $this->db->prepare("SELECT * FROM deposits WHERE vnp_txn_ref = ?");
        $stmt->bind_param("s", $vnpTxnRef);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function updateStatus($id, $status, $vnpResponseCode, $vnpTransactionNo, $vnpBankCode, $vnpPayDate, $vnpCardType) {
        $stmt = $this->db->prepare("UPDATE deposits SET status = ?, vnp_response_code = ?, vnp_transaction_no = ?, vnp_bank_code = ?, vnp_pay_date = ?, vnp_card_type = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $status, $vnpResponseCode, $vnpTransactionNo, $vnpBankCode, $vnpPayDate, $vnpCardType, $id);
        return $stmt->execute();
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $deposits = [];
        while ($row = $result->fetch_assoc()) {
            $deposits[] = $row;
        }
        return $deposits;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM deposits WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
