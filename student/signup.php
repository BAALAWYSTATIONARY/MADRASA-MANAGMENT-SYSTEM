<?php
session_start();
include '../auth/db.php';

function ensureMadrasaTable(PDO $conn) {
    $conn->exec("CREATE TABLE IF NOT EXISTS madrasas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        address VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

function getMadrasaOptions(PDO $conn) {
    $stmt = $conn->query("SELECT id, name FROM madrasas ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getDefaultMadrasaId(PDO $conn) {
    $options = getMadrasaOptions($conn);
    if (count($options) > 0) {
        return array_key_first($options);
    }

    $stmt = $conn->prepare("INSERT INTO madrasas (name) VALUES ('Default Madrasa')");
    $stmt->execute();
    return (int)$conn->lastInsertId();
}

$message = '';
$error = '';
$madrasaOptions = [];

try {
    ensureMadrasaTable($conn);
    $madrasaOptions = getMadrasaOptions($conn);
    if (empty($madrasaOptions)) {
        $defaultId = getDefaultMadrasaId($conn);
        $madrasaOptions = getMadrasaOptions($conn);
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $student_class = trim($_POST['student_class'] ?? '');
    $parent_contact = trim($_POST['parent_contact'] ?? '');
    $madrasa_id = intval($_POST['madrasa_id'] ?? 0);

    if ($madrasa_id <= 0 || !isset($madrasaOptions[$madrasa_id])) {
        $madrasa_id = array_key_first($madrasaOptions);
    }

    if ($username === '' || $password === '' || $full_name === '' || $age <= 0 || $student_class === '') {
        $error = 'Tafadhali jaza sehemu zote muhimu za fomu.';
    } else {
        try {
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND madrasa_id = :madrasa_id LIMIT 1");
            $checkStmt->execute([':username' => $username, ':madrasa_id' => $madrasa_id]);

            if ($checkStmt->rowCount() > 0) {
                $error = 'Username hiyo tayari imetumiwa kwa madrasa hii. Tumia nyingine.';
            } else {
                $conn->beginTransaction();

                $userStmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, madrasa_id) VALUES (:username, :password, :full_name, 'Student', :madrasa_id)");
                $userStmt->execute([
                    ':username' => $username,
                    ':password' => md5($password),
                    ':full_name' => $full_name,
                    ':madrasa_id' => $madrasa_id
                ]);

                $studentStmt = $conn->prepare("INSERT INTO students (full_name, age, student_class, parent_contact, madrasa_id) VALUES (:full_name, :age, :student_class, :parent_contact, :madrasa_id)");
                $studentStmt->execute([
                    ':full_name' => $full_name,
                    ':age' => $age,
                    ':student_class' => $student_class,
                    ':parent_contact' => $parent_contact,
                    ':madrasa_id' => $madrasa_id
                ]);

                $conn->commit();
                $message = 'Usajili umefanikiwa! Tafadhali ingia kwa kutumia taarifa zako.';
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Kosa la database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Sign Up</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f4f7ff; }
        .signup-page { max-width: 720px; margin: 60px auto; padding: 32px; background: #ffffff; border-radius: 20px; box-shadow: 0 18px 60px rgba(15,23,42,0.08); }
        .signup-page h1 { margin-bottom: 14px; color: #0f172a; }
        .signup-page p { margin-bottom: 22px; color: #475569; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #334155; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #cbd5e1; background: #f8fafc; }
        button { margin-top: 8px; background: #2563eb; color: #fff; border: none; border-radius: 12px; padding: 14px 18px; cursor: pointer; font-size: 15px; font-weight: 700; }
        button:hover { background: #1d4ed8; }
        .message { margin-bottom: 18px; padding: 14px 16px; border-radius: 14px; }
        .message.success { background: #ecfdf5; color: #166534; border: 1px solid #d1fae5; }
        .message.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .back-link { display: inline-block; margin-top: 18px; color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="signup-page">
        <h1>Student Sign Up</h1>
        <p>Jaza taarifa zako na jiunge ili utaweza kufuatilia maendeleo yako kwa mfumo wa Madrasa.</p>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" min="5" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="student_class">Class</label>
                <input type="text" id="student_class" name="student_class" value="<?php echo htmlspecialchars($_POST['student_class'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="parent_contact">Parent / Guardian Contact</label>
                <input type="text" id="parent_contact" name="parent_contact" value="<?php echo htmlspecialchars($_POST['parent_contact'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="madrasa_id">Madrasa</label>
                <select id="madrasa_id" name="madrasa_id">
                    <?php foreach ($madrasaOptions as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo (isset($_POST['madrasa_id']) && intval($_POST['madrasa_id']) === $id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Sign Up</button>
        </form>

        <a class="back-link" href="../index.php">← Back to Login</a>
    </div>
</body>
</html>
