<?php
require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $dbh->prepare("SELECT id, password, role FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);

    try {
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'teacher') {
                header('Location: teacher_profile.php');
            } elseif ($user['role'] === 'student') {
                header('Location: student_profile.php');
            } else {
                // Handle other roles if needed
                echo "Invalid role";
            }
            exit();
        } else {
            echo "Invalid username or password";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
