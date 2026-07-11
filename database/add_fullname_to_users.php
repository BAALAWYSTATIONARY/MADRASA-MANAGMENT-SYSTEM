<?php
require_once __DIR__ . '/../auth/db.php';

try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'full_name'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "Column full_name already exists on users.\n";
        exit(0);
    }

    echo "Adding column full_name to users...\n";
    $conn->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(150) DEFAULT NULL AFTER username");
    echo "Column added. Backfilling existing rows (setting full_name = username where full_name IS NULL)...\n";
    $conn->exec("UPDATE users SET full_name = username WHERE full_name IS NULL OR full_name = ''");
    echo "Backfill complete.\n";

    // show columns
    $cols = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Current users columns:\n";
    foreach ($cols as $c) {
        echo $c['Field'] . ': ' . $c['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>