<?php
// src/models/SettingsRepository.php

require_once __DIR__ . '/Settings.php';

class SettingsRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    // Helper method to check if 'settings' table exists
    private function tableExists(): bool {
        $checkTableSql = "SELECT 1 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'settings' 
            LIMIT 1";
        $result = $this->conn->query($checkTableSql);
        return $result && $result->num_rows > 0;
    }

    public function getSettings(): ?Settings {
        $data = [
            'lowstock_threshold' => '10',
            'header' => '',
            'footer' => '',
            'default_lang' => 'en',
            'from_email' => '',
            'app_password' => '',
            'date_format' => 'Y-m-d',   // default
            'time_zone' => 'UTC'        // default
        ];

        if ($this->tableExists()) {
            $sql = "SELECT setting_key, setting_value FROM settings";
            $result = $this->conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if (array_key_exists($row['setting_key'], $data)) {
                        $data[$row['setting_key']] = $row['setting_value'];
                    }
                }
            }
        }

        return new Settings(
            $data['lowstock_threshold'],
            $data['header'],
            $data['footer'],
            $data['default_lang'],
            $data['from_email'],
            $data['app_password'],
            $data['date_format'],
            $data['time_zone']
        );
    }

    public function getSettingsArray(): array {
        $settings = [];
        if ($this->tableExists()) {
            $sql = "SELECT setting_key, setting_value FROM settings";
            $result = $this->conn->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
        return $settings;
    }

    public function updateSetting(string $key, string $value): void {
        if (!$this->tableExists()) {
            return; // Cannot update if table doesn't exist
        }

        $stmt = $this->conn->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
        $stmt->close();
    }

    public function getEmailSettings(): array {
        $settings = [];
        if ($this->tableExists()) {
            $sql = "SELECT setting_key, setting_value 
                    FROM settings 
                    WHERE setting_key IN ('from_email', 'app_password')";
            $result = $this->conn->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
        return $settings;
    }

    // === SIZE METHODS ===
    public function getSizes(): array {
        $sizes = [];
        $sql = "SELECT id, name FROM sizes ORDER BY name";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sizes[] = $row;
            }
        }
        return $sizes;
    }

    public function addSize(string $name): bool {
        $stmt = $this->conn->prepare("INSERT INTO sizes (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function updateSize(int $id, string $name): bool {
        $stmt = $this->conn->prepare("UPDATE sizes SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }

    public function deleteSize(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM sizes WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getSizeById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, name FROM sizes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // === CLUB METHODS ===
    public function getClubs(): array {
        $clubs = [];
        $sql = "SELECT id, name FROM clubs ORDER BY name";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clubs[] = $row;
            }
        }
        return $clubs;
    }

    public function addClub(string $name): bool {
        $stmt = $this->conn->prepare("INSERT INTO clubs (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function updateClub(int $id, string $name): bool {
        $stmt = $this->conn->prepare("UPDATE clubs SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }

    public function deleteClub(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM clubs WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getClubById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, name FROM clubs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // === MANUFACTURER METHODS ===
    public function getManufacturers(): array {
        $manufacturers = [];
        $sql = "SELECT id, name FROM manufacturers ORDER BY name";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $manufacturers[] = $row;
            }
        }
        return $manufacturers;
    }

    public function addManufacturer(string $name): bool {
        $stmt = $this->conn->prepare("INSERT INTO manufacturers (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function updateManufacturer(int $id, string $name): bool {
        $stmt = $this->conn->prepare("UPDATE manufacturers SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }

    public function deleteManufacturer(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM manufacturers WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getManufacturerById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, name FROM manufacturers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // === CATEGORY METHODS ===
    public function getCategories(): array {
        $categories = [];
        $sql = "SELECT id, name FROM categories ORDER BY name";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    public function addCategory(string $name): bool {
        $stmt = $this->conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function updateCategory(int $id, string $name): bool {
        $stmt = $this->conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }

    public function deleteCategory(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getCategoryById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, name FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // === SUPPLIER METHODS ===
    public function getSuppliers(): array {
        $suppliers = [];
        $sql = "SELECT id, name FROM suppliers ORDER BY name";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
        }
        return $suppliers;
    }

    public function addSupplier(string $name): bool {
        $stmt = $this->conn->prepare("INSERT INTO suppliers (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function updateSupplier(int $id, string $name): bool {
        $stmt = $this->conn->prepare("UPDATE suppliers SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }

    public function deleteSupplier(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getSupplierById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, name FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // === PRODUCT SETTINGS METHODS ===
    public function getAllProductSettings(): array {
        return [
            'sizes' => $this->getSizes(),
            'clubs' => $this->getClubs(),
            'manufacturers' => $this->getManufacturers(),
            'categories' => $this->getCategories(),
            'suppliers' => $this->getSuppliers()
        ];
    }

    // Add these methods after the existing methods in SettingsRepository.php

    // === DUPLICATE CHECK METHODS ===

    public function sizeExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM sizes WHERE LOWER(name) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM sizes WHERE LOWER(name) = LOWER(?)");
            $stmt->bind_param("s", $name);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function clubExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM clubs WHERE LOWER(name) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM clubs WHERE LOWER(name) = LOWER(?)");
            $stmt->bind_param("s", $name);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function manufacturerExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM manufacturers WHERE LOWER(name) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM manufacturers WHERE LOWER(name) = LOWER(?)");
            $stmt->bind_param("s", $name);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function categoryExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM categories WHERE LOWER(name) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM categories WHERE LOWER(name) = LOWER(?)");
            $stmt->bind_param("s", $name);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function supplierExists(string $name, ?int $excludeId = null): bool {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE LOWER(name) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE LOWER(name) = LOWER(?)");
            $stmt->bind_param("s", $name);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
}
