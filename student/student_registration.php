<?php
session_start();
include '../auth/db.php';


// Only teachers or admins can access
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Teacher','Admin'])) {
    header("Location: ../index.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $full_name = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);
    $age = intval($_POST['age']);
    $student_class = trim($_POST['student_class']);
    $parent_contact = trim($_POST['parent_contact']);

    $stmt = $conn->prepare("INSERT INTO students (full_name, age, student_class, parent_contact, madrasa_id) 
              VALUES (:full_name, :age, :student_class, :parent_contact, :madrasa_id)");

    $ok = $stmt->execute([
        ':full_name' => $full_name,
        ':age' => $age,
        ':student_class' => $student_class,
        ':parent_contact' => $parent_contact,
        ':madrasa_id' => $_SESSION['madrasa_id']
    ]);

    if ($ok) {
        $message = "Student registered successfully!";
    } else {
        $error = $stmt->errorInfo();
        $message = "Error: " . ($error[2] ?? 'Unknown error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Student - Madrasa Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #eef2ff 0%, #d4e4ff 100%); color: #1e293b; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 24px 36px; background: transparent; }
        header h1 { font-size: 20px; color: #0f172a; letter-spacing: 0.5px; }
        header nav a { color: #0f172a; margin-left: 18px; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        header nav a:hover { color: #2563eb; }
        main { max-width: 920px; margin: 30px auto 40px; padding: 0 20px; }
        .card { background: #ffffff; border-radius: 24px; padding: 32px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08); border: 1px solid rgba(148, 163, 184, 0.18); }
        .card h2 { font-size: 28px; margin-bottom: 14px; color: #0f172a; }
        .card p.subtitle { margin-bottom: 24px; color: #475569; font-size: 15px; }
        .status-message { padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; font-weight: 600; display: inline-block; }
        .status-success { background: #ecfdf5; color: #166534; border: 1px solid #d1fae5; }
        .status-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; margin-bottom: 8px; color: #334155; }
        .form-group input { padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; color: #0f172a; background: #f8fafc; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-group input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08); }
        .full-width { grid-column: span 2; }
        button.submit-btn { margin-top: 8px; background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%); color: #fff; padding: 15px 22px; border: none; border-radius: 14px; cursor: pointer; font-size: 15px; font-weight: 700; transition: transform 0.2s, box-shadow 0.2s; }
        button.submit-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 36px rgba(37, 99, 235, 0.24); }
        @media (max-width: 760px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } header { flex-direction: column; align-items: flex-start; gap: 12px; } }
    </style>
</head>
<body>
<header>
    <h1>Madrasa Management</h1>
    <nav>
        <a href="../teacher/dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <div class="card">
        <h2>Register New Student</h2>
        <p class="subtitle">Fill out the details below to add a student to your class. This form saves the student profile immediately.</p>

        <?php if($message != ""): ?>
            <div class="status-message <?php echo strpos($message, 'Error') === 0 ? 'status-error' : 'status-success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" name="middle_name" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" name="age" required min="5">
                </div>
                <div class="form-group full-width">
                    <label for="student_class">Class</label>
                    <input type="text" name="student_class" required>
                </div>
                <div class="form-group full-width">
                    <label for="parent_contact">Parent/Guardian Contact</label>
                    <input type="text" name="parent_contact" placeholder="Phone or WhatsApp number">
                </div>
            </div>
            <button type="submit" class="submit-btn">Register Student</button>
        </form>
    </div>
</main>

</body>
</html>
