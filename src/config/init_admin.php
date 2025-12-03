<?php
// init_admin.php
require_once __DIR__ . '/database.php';

function createAdminIfNotExists() {
    $flagFile = __DIR__ . '/admin_created.flag';
    if (file_exists($flagFile)) {
        return;
    }

    $conn = connectDB();

    $username = 'admin1';
    $plainPassword = '1';
    $email = 'manojraibagi4@gmail.com'; // default email for admin
    $roleId = 1;
    $emailNoti = 1; // default = false

    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $conn->close();
        return; // Admin already exists, nothing to do
    }

    // Hash the password securely
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Insert the admin user with email and email_noti
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, email_noti) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssii', $username, $email, $hashedPassword, $roleId, $emailNoti);

    if ($stmt->execute()) {
        // Success log if needed
    } else {
        // Error log if needed
    }

    $stmt->close();
    $conn->close();

    // If admin exists or insert success:
    file_put_contents($flagFile, "Admin created on " . date('Y-m-d H:i:s'));
}
