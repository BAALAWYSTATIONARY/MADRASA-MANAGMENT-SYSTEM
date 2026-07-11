<?php
require_once __DIR__ . '/../auth/db.php';

$fields = [
    "dob" => "ALTER TABLE users ADD COLUMN dob DATE DEFAULT NULL AFTER full_name",
    "phone" => "ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER dob",
    "photo" => "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER phone",
];

try {
    foreach ($fields as $col => $sql) {
        $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE :col");
        $stmt->execute([':col' => $col]);
        if ($stmt->rowCount() > 0) {
            echo "Column $col already exists.\n";
            continue;
        }
        echo "Adding column $col...\n";
        $conn->exec($sql);
        echo "Added $col.\n";
    }
    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>