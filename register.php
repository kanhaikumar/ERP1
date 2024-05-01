<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $enrollmentNumber = isset($_POST['enrollmentNumber']) ? $_POST['enrollmentNumber'] : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    $semester = isset($_POST['semester']) ? $_POST['semester'] : '';

    // Check if profile photo is uploaded
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";

        // Check if the directory exists, create it if not
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $profilePhoto = $targetDir . basename($_FILES["profilePhoto"]["name"]);

        // Check if the file was successfully moved
        if (move_uploaded_file($_FILES["profilePhoto"]["tmp_name"], $profilePhoto)) {
            echo "File uploaded successfully.";
        } else {
            echo "Error uploading file.";
        }
    } else {
        // Handle the case where no profile photo is uploaded
        $profilePhoto = '';
    }

    // Check if all required fields are set
    if (!empty($username) && !empty($password) && !empty($role) && !empty($enrollmentNumber) && !empty($department) && !empty($semester)) {
        // Insert data into the database
        $stmt = $dbh->prepare("INSERT INTO users (username, password, role, enrollment_number, department, semester, profile_photo)
                              VALUES (:username, :password, :role, :enrollment_number, :department, :semester, :profile_photo)");

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':enrollment_number', $enrollmentNumber, PDO::PARAM_STR);
        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $stmt->bindParam(':semester', $semester, PDO::PARAM_INT);
        $stmt->bindParam(':profile_photo', $profilePhoto, PDO::PARAM_STR);

        try {
            $stmt->execute();
            echo "User registered successfully";
        } catch (PDOException $e) {
            // Check for duplicate entry error (1062)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "Error: Username already exists.";
            } else {
                echo "Error: " . $e->getMessage();
            }
        }
    } else {
        echo "Error: Missing required fields";
    }
}
?>
