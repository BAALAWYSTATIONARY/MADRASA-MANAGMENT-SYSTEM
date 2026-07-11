<?php
session_start();
include '../auth/db.php';

function get_grade_label($percentage) {
    if ($percentage >= 80) {
        return 'A';
    }
    if ($percentage >= 65) {
        return 'B';
    }
    if ($percentage >= 45) {
        return 'C';
    }
    if ($percentage >= 30) {
        return 'D';
    }
    return 'F';
}

// Only teachers can access
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Teacher') {
    header("Location: index.html");
    exit();
}

$message = "";

// Fetch students for the current madrasa
$studentsStmt = $conn->prepare("SELECT * FROM students WHERE madrasa_id = :madrasa_id ORDER BY full_name ASC");
$studentsStmt->execute([':madrasa_id' => $_SESSION['madrasa_id']]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_name = trim($_POST['exam_name']);
    $exam_date = $_POST['exam_date'];
    $class = trim($_POST['class']);

    // Insert exam
    $examStmt = $conn->prepare("INSERT INTO exams (exam_name, exam_date, class, madrasa_id) VALUES (:exam_name, :exam_date, :class, :madrasa_id)");
    $examStmt->execute([
        ':exam_name' => $exam_name,
        ':exam_date' => $exam_date,
        ':class' => $class,
        ':madrasa_id' => $_SESSION['madrasa_id']
    ]);
    $exam_id = $conn->lastInsertId();

    // Insert results
    if (!empty($_POST['marks'])) {
        $resStmt = $conn->prepare("INSERT INTO results (exam_id, student_id, marks_obtained, total_marks, madrasa_id)
            VALUES (:exam_id, :student_id, :marks_obtained, :total_marks, :madrasa_id)");

        foreach ($_POST['marks'] as $student_id => $marks) {
            $total_marks = intval($_POST['total_marks'][$student_id] ?? 0);
            $marks_obtained = intval($marks);
            $resStmt->execute([
                ':exam_id' => $exam_id,
                ':student_id' => $student_id,
                ':marks_obtained' => $marks_obtained,
                ':total_marks' => $total_marks,
                ':madrasa_id' => $_SESSION['madrasa_id']
            ]);
        }
    }

    $message = "Exam and results recorded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Entry - Madrasa Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .grade-pill { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #e2e8f0; color: #0f172a; font-weight: 700; font-size: 12px; }
    </style>
</head>
<body>
<header>
    <h1>Madrasa Management System</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2>Enter Exam & Scores</h2>

    <?php if($message != ""): ?>
        <p style="color: green; text-align: center;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Exam Name:</label>
        <input type="text" name="exam_name" required>

        <label>Exam Date:</label>
        <input type="date" name="exam_date" required value="<?php echo date('Y-m-d'); ?>">

        <label>Class:</label>
        <input type="text" name="class" required>

        <h3>Enter Student Marks</h3>
        <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse;">
            <tr>
                <th>Student Name</th>
                <th>Marks Obtained</th>
                <th>Total Marks</th>
                <th>Grade</th>
            </tr>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                <td><input type="number" name="marks[<?php echo $student['id']; ?>]" id="marks_<?php echo $student['id']; ?>" min="0" value="0" oninput="updateGrade(<?php echo $student['id']; ?>)"></td>
                <td><input type="number" name="total_marks[<?php echo $student['id']; ?>]" id="total_<?php echo $student['id']; ?>" min="1" value="100" oninput="updateGrade(<?php echo $student['id']; ?>)"></td>
                <td><span class="grade-pill" id="grade_<?php echo $student['id']; ?>">--</span></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit" style="margin-top: 15px;">Save Exam & Results</button>
    </form>
</main>

<script>
function getGradeFromPercentage(percentage) {
    if (percentage >= 80) return 'A';
    if (percentage >= 65) return 'B';
    if (percentage >= 45) return 'C';
    if (percentage >= 30) return 'D';
    return 'F';
}
function updateGrade(studentId) {
    var marksInput = document.getElementById('marks_' + studentId);
    var totalInput = document.getElementById('total_' + studentId);
    var gradeBox = document.getElementById('grade_' + studentId);
    if (!marksInput || !totalInput || !gradeBox) return;
    var marks = parseFloat(marksInput.value || 0);
    var total = parseFloat(totalInput.value || 0);
    if (total <= 0) {
        gradeBox.textContent = '—';
        return;
    }
    var percentage = Math.max(0, Math.min(100, (marks / total) * 100));
    var grade = getGradeFromPercentage(percentage);
    gradeBox.textContent = grade + ' (' + Math.round(percentage) + '%)';
}
window.addEventListener('DOMContentLoaded', function(){
    <?php foreach ($students as $student): ?>
    updateGrade(<?php echo $student['id']; ?>);
    <?php endforeach; ?>
});
</script>
</body>
</html>
