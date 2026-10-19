
<?php
require_once __DIR__ . "/../includes/db.php";

$username = "admin";
$password = password_hash("Admin@123", PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO admins (username, password)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE password = VALUES(password)"
);

$stmt->bind_param("ss", $username, $password);

if ($stmt->execute()) {
    echo "Admin account created successfully.";
} else {
    echo "Error creating admin account.";
}

$stmt->close();
$conn->close();
?>
