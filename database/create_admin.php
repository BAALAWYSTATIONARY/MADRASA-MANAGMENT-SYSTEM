<?php
require_once __DIR__ . '/../auth/db.php';

// Usage: php database/create_admin.php username password "Full Name" [madrasa_id]
if ($argc < 4) {
    echo "Usage: php database/create_admin.php username password \"Full Name\" [madrasa_id]\n";
    exit(1);
}

$username = $argv[1];
$password = $argv[2];
$full_name = $argv[3];
$madrasa_id = isset($argv[4]) ? intval($argv[4]) : 0;

try {
    // Ensure madrasas table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS madrasas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        address VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    // If madrasa_id not provided, pick first or create default
    if ($madrasa_id <= 0) {
        $stmt = $conn->query("SELECT id FROM madrasas ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $madrasa_id = (int)$row['id'];
        } else {
            $ins = $conn->prepare("INSERT INTO madrasas (name) VALUES (:name)");
            $ins->execute([':name' => 'Default Madrasa']);
            $madrasa_id = (int)$conn->lastInsertId();
            echo "Created default madrasa with id={$madrasa_id}\n";
        }
    } else {
        // verify madrasa exists
        $stmt = $conn->prepare("SELECT id FROM madrasas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $madrasa_id]);
        if ($stmt->rowCount() === 0) {
            echo "Madrasa id={$madrasa_id} not found.\n";
            exit(1);
        }
    }

    // Check if username exists for that madrasa
    $check = $conn->prepare("SELECT id FROM users WHERE username = :username AND madrasa_id = :madrasa_id LIMIT 1");
    $check->execute([':username' => $username, ':madrasa_id' => $madrasa_id]);
    if ($check->rowCount() > 0) {
        echo "User '$username' already exists for madrasa_id={$madrasa_id}\n";
        exit(1);
    }

    // Insert admin user
    $pwdHash = md5($password);
    $ins = $conn->prepare("INSERT INTO users (username, password, full_name, role, madrasa_id) VALUES (:username, :password, :full_name, 'Admin', :madrasa_id)");
    $ins->execute([
        ':username' => $username,
        ':password' => $pwdHash,
        ':full_name' => $full_name,
        ':madrasa_id' => $madrasa_id
    ]);

    $id = $conn->lastInsertId();
    echo "Admin user created: id={$id}, username={$username}, madrasa_id={$madrasa_id}\n";
    echo "You can now log in at /index.php with that username and password.\n";
    exit(0);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
