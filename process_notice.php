<?php
require_once 'config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author'];
    $usertype = $_POST['usertype'];

    // Insert notice into the database
    $stmt = $dbh->prepare("INSERT INTO notices (title, content, author, usertype, created_at) VALUES (:title, :content, :author, :usertype, NOW())");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':usertype', $usertype);

    try {
        $stmt->execute();
        echo "Notice has been successfully submitted.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>
