<?php
// Apply multi-tenant schema changes for Madrasa System
// Usage: php database/apply_multitenant.php

require_once __DIR__ . '/../auth/db.php';

function columnExists(PDO $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
    $stmt->execute([':column' => $column]);
    return $stmt->rowCount() > 0;
}

function indexExists(PDO $conn, string $table, string $index): bool {
    $stmt = $conn->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :index");
    $stmt->execute([':index' => $index]);
    return $stmt->rowCount() > 0;
}

$queries = [];

// Create madrasa table
$queries[] = "CREATE TABLE IF NOT EXISTS madrasas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$tables = [
    'users' => [
        'column' => "ALTER TABLE users ADD COLUMN madrasa_id INT NOT NULL AFTER role",
        'index' => 'idx_users_madrasa_id'
    ],
    'students' => [
        'column' => "ALTER TABLE students ADD COLUMN madrasa_id INT NOT NULL AFTER parent_contact",
        'index' => 'idx_students_madrasa_id'
    ],
    'attendance' => [
        'column' => "ALTER TABLE attendance ADD COLUMN madrasa_id INT NOT NULL AFTER attendance_date",
        'index' => 'idx_attendance_madrasa_id'
    ],
    'memorization_records' => [
        'column' => "ALTER TABLE memorization_records ADD COLUMN madrasa_id INT NOT NULL AFTER memorization_date",
        'index' => 'idx_memorization_madrasa_id'
    ],
    'exams' => [
        'column' => "ALTER TABLE exams ADD COLUMN madrasa_id INT NOT NULL AFTER class",
        'index' => 'idx_exams_madrasa_id'
    ],
    'results' => [
        'column' => "ALTER TABLE results ADD COLUMN madrasa_id INT NOT NULL AFTER student_id",
        'index' => 'idx_results_madrasa_id'
    ],
];

try {
    echo "Applying multi-tenant schema changes...\n";

    foreach ($queries as $sql) {
        $conn->exec($sql);
    }

    foreach ($tables as $table => $metadata) {
        if (!columnExists($conn, $table, basename($metadata['column']))) {
            echo "Adding column madrasa_id to $table...\n";
            $conn->exec($metadata['column']);
        } else {
            echo "Column madrasa_id already exists on $table.\n";
        }

        if (!indexExists($conn, $table, $metadata['index'])) {
            echo "Adding index {$metadata['index']} on $table...\n";
            $conn->exec("ALTER TABLE $table ADD INDEX {$metadata['index']} (madrasa_id)");
        } else {
            echo "Index {$metadata['index']} already exists on $table.\n";
        }
    }

    if (!indexExists($conn, 'users', 'unique_username_madrasa')) {
        echo "Adding unique index unique_username_madrasa on users...\n";
        $conn->exec("ALTER TABLE users ADD UNIQUE INDEX unique_username_madrasa (username, madrasa_id)");
    } else {
        echo "Unique index unique_username_madrasa already exists on users.\n";
    }

    echo "\nMulti-tenant schema applied successfully.\n";
    echo "Please review the database tables and add at least one madrasa record in the 'madrasas' table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
