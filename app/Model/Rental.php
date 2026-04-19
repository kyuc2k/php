<?php

class Rental {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance();
    }

    public function getAllPackages() {
        $result = $this->db->query("SELECT * FROM rental_packages ORDER BY duration_months ASC");
        $packages = [];
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
        return $packages;
    }

    public function getPackageById($id) {
        $stmt = $this->db->prepare("SELECT * FROM rental_packages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createRental($userId, $packageId) {
        $package = $this->getPackageById($packageId);
        if (!$package) {
            return false;
        }

        // Calculate end date
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime('+' . $package['duration_months'] . ' months'));

        $stmt = $this->db->prepare("INSERT INTO rentals (user_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("iiss", $userId, $packageId, $startDate, $endDate);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function updateVpsInfo($rentalId, $vpsUrl, $vpsPassword) {
        $stmt = $this->db->prepare("UPDATE rentals SET vps_url = ?, vps_password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $vpsUrl, $vpsPassword, $rentalId);
        return $stmt->execute();
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT r.*, rp.name as package_name, rp.duration_months, rp.price FROM rentals r JOIN rental_packages rp ON r.package_id = rp.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rentals = [];
        while ($row = $result->fetch_assoc()) {
            $rentals[] = $row;
        }
        return $rentals;
    }

    public function getActiveRental($userId) {
        $stmt = $this->db->prepare("SELECT r.*, rp.name as package_name, rp.duration_months, rp.price FROM rentals r JOIN rental_packages rp ON r.package_id = rp.id WHERE r.user_id = ? AND r.status = 'active' AND r.end_date > NOW() ORDER BY r.created_at DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
