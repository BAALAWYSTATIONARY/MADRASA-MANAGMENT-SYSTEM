<?php
require_once __DIR__ . '/../auth/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        madrasa_id INT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_password_resets_user (user_id),
        INDEX idx_password_resets_madrasa (madrasa_id),
        UNIQUE KEY uniq_token_hash (token_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $conn->exec($sql);
    echo "password_resets table created or already exists.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
