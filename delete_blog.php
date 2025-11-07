<?php
session_start();
include 'includes/db.php';

// Only allow logged-in users
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$blogid = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch cover image path for safe file deletion
$cover = null;
$stmt = $conn->prepare("SELECT coverPage FROM blogs WHERE blogid = ? AND userid = ?");
if ($stmt) {
    $stmt->bind_param("ii", $blogid, $userid);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cover = $row['coverPage'] ?? null;
        }
    }
    $stmt->close();
}

// Delete blog if it belongs to the logged-in user
$stmt = $conn->prepare("DELETE FROM blogs WHERE blogid = ? AND userid = ?");
if ($stmt) {
    $stmt->bind_param("ii", $blogid, $userid);
    $success = false;
    if ($stmt->execute()) {
        $success = true;
        // Safe file deletion if cover exists
        if ($cover) {
            $coverFile = __DIR__ . '/' . $cover;
            if (is_file($coverFile)) @unlink($coverFile);
        }
    }
    $stmt->close();
    if ($success) {
        header("Location: my_blogs.php?deleted=1");
        exit();
    } else {
        header("Location: my_blogs.php?error=deletefailed");
        exit();
    }
} else {
    header("Location: my_blogs.php?error=prepfail");
    exit();
}
?>
