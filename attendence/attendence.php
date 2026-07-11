<?php
session_start();
include '../auth/db.php';

// Only teachers can access
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Teacher') {
    header("Location: index.html");
    exit();
}

// Fetch all students for this madrasa
$studentsStmt = $conn->prepare("SELECT * FROM students WHERE madrasa_id = :madrasa_id ORDER BY full_name ASC");
$studentsStmt->execute([':madrasa_id' => $_SESSION['madrasa_id']]);
$students = $studentsStmt;

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $attendance_date = $_POST['attendance_date'];

    // Loop through attendance (use prepared statement)
    if (!empty($_POST['attendance'])) {
        $attStmt = $conn->prepare("INSERT INTO attendance (student_id, attendance_date, status, madrasa_id) VALUES (:student_id, :attendance_date, :status, :madrasa_id)");
        foreach($_POST['attendance'] as $student_id => $status) {
            $attStmt->execute([
                ':student_id' => $student_id,
                ':attendance_date' => $attendance_date,
                ':status' => $status,
                ':madrasa_id' => $_SESSION['madrasa_id']
            ]);
        }
    }

    // Loop through memorization
    if (!empty($_POST['memorization'])) {
        $memStmt = $conn->prepare("INSERT INTO memorization_records 
                (student_id, surah_name, ayah_start, ayah_end, memorization_date, teacher_notes, madrasa_id)
                VALUES (:student_id, :surah, :ayah_start, :ayah_end, :date, :notes, :madrasa_id)");

        foreach($_POST['memorization'] as $student_id => $mem_data) {
            $surah = trim($mem_data['surah'] ?? '');
            $ayah_start = intval($mem_data['ayah_start'] ?? 0);
            $ayah_end = intval($mem_data['ayah_end'] ?? 0);
            $notes = trim($mem_data['notes'] ?? '');

            if(!empty($surah)) {
                $memStmt->execute([
                    ':student_id' => $student_id,
                    ':surah' => $surah,
                    ':ayah_start' => $ayah_start,
                    ':ayah_end' => $ayah_end,
                    ':date' => $attendance_date,
                    ':notes' => $notes,
                    ':madrasa_id' => $_SESSION['madrasa_id']
                ]);
            }
        }
    }

    $message = "Attendance and memorization records saved successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance & Memorization - Madrasa Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<header>
    <h1>Madrasa Management System</h1>
    <nav>
        <a href="../teacher/dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2>Attendance & Memorization Tracking</h2>
    <?php if($message != ""): ?>
        <p style="color: green; text-align: center;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="attendance_date">Date:</label>
        <input type="date" name="attendance_date" required value="<?php echo date('Y-m-d'); ?>">

        <table border="1" cellpadding="10" style="margin-top:20px; width:100%; border-collapse: collapse;">
            <tr>
                <th>Student Name</th>
                <th>Attendance</th>
                <th>Surah Name</th>
                <th>Ayah Start</th>
                <th>Ayah End</th>
                <th>Notes</th>
            </tr>
            <?php while($student = $students->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo $student['full_name']; ?></td>
                <td>
                    <select name="attendance[<?php echo $student['id']; ?>]">
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                    </select>
                </td>
                <td><input type="text" name="memorization[<?php echo $student['id']; ?>][surah]"></td>
                <td><input type="number" name="memorization[<?php echo $student['id']; ?>][ayah_start]" min="1"></td>
                <td><input type="number" name="memorization[<?php echo $student['id']; ?>][ayah_end]" min="1"></td>
                <td><input type="text" name="memorization[<?php echo $student['id']; ?>][notes]"></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <button type="submit" style="margin-top: 15px;">Save Records</button>
    </form>
</main>


</body>
</html>
