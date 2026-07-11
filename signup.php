<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - Madrasa System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f3f8ff; }
        .signup-wrapper { max-width: 760px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,0.08); }
        h1 { margin-bottom: 16px; color: #0f172a; }
        p { margin-bottom: 28px; color: #475569; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .card { background: #f8fafc; padding: 28px 24px; border-radius: 18px; border: 1px solid #e2e8f0; text-align: center; }
        .card h2 { margin-bottom: 12px; font-size: 20px; color: #0f172a; }
        .card p { color: #475569; margin-bottom: 22px; }
        .card a { display: inline-block; padding: 12px 18px; background: #2563eb; color: #fff; border-radius: 12px; text-decoration: none; font-weight: 700; }
        .card a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="signup-wrapper">
        <h1>Create an Account</h1>
        <p>Choose your role and complete the registration. If you are a student or academic staff, you can sign up here.</p>
        <div class="card-grid">
            <div class="card">
                <h2>Student Signup</h2>
                <p>Register as a student and start tracking your progress.</p>
                <a href="student/signup.php">Student Sign Up</a>
            </div>
            <div class="card">
                <h2>Academic Staff Signup</h2>
                <p>Register as a teacher or academic staff member.</p>
                <a href="teacher/signup.php">Staff Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>
