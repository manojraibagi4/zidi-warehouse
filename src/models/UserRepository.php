<?php
require_once __DIR__ . '/../config/database.php';

require_once 'User.php';

class UserRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getUserByCredentials($username, $role_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? AND role_id = ?");
        $stmt->bind_param("si", $username, $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createUser($username, $email, $password, $role_id, $email_noti = 0) {
        $stmt = $this->conn->prepare(
            "INSERT INTO users (username, email, password, role_id, email_noti) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssii", $username, $email, $password, $role_id, $email_noti);
        return $stmt->execute();
    }
    
    
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    

    public function getRoles() {
        $result = $this->conn->query("SELECT id, role FROM roles");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoleIdByUsername($username) {
        $stmt = $this->conn->prepare("SELECT role_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['role_id'];
        }
        return null;
    }

    public function getRoleById(int $role_id): ?string
    {
        $stmt = $this->conn->prepare("SELECT role FROM roles WHERE id = ?");
        $stmt->bind_param("i", $role_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row ? $row['role'] : null;
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getAllUsers(): array
    {
        $sql = "SELECT u.id, u.username, u.email, u.email_noti, r.role 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id";
        $result = $this->conn->query($sql);

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getUserById(int $id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateUser($id, $username, $email, $password, $role_id, $email_noti)
    {
        if ($password) {
            $stmt = $this->conn->prepare(
                "UPDATE users SET username = ?, email = ?, password = ?, role_id = ?, email_noti = ? WHERE id = ?"
            );
            $stmt->bind_param("sssiii", $username, $email, $password, $role_id, $email_noti, $id);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users SET username = ?, email = ?, role_id = ?, email_noti = ? WHERE id = ?"
            );
            $stmt->bind_param("ssiii", $username, $email, $role_id, $email_noti, $id);
        }
        return $stmt->execute();
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getNotificationUsers(): array {
        $sql = "SELECT username, email FROM users WHERE email_noti = 1 AND email IS NOT NULL AND email <> ''";
        $result = $this->conn->query($sql);

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }
}