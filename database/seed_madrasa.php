<?php
require_once __DIR__ . '/../auth/db.php';

$name = $argv[1] ?? 'Default Madrasa';

try {
    // Ensure table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS madrasas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        address VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    // Check if already exists
    $stmt = $conn->prepare("SELECT id FROM madrasas WHERE name = :name LIMIT 1");
    $stmt->execute([':name' => $name]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Madrasa already exists: id=" . $row['id'] . " name='" . $name . "'\n";
        exit(0);
    }

    $ins = $conn->prepare("INSERT INTO madrasas (name) VALUES (:name)");
    $ins->execute([':name' => $name]);
    $id = $conn->lastInsertId();
    echo "Inserted madrasa id=$id name='$name'\n";
    exit(0);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
