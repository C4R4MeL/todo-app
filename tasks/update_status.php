<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/auth.php');
    exit;
}

require_once '../config/db.php';

$id      = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Ambil status saat ini
$query = mysqli_query($conn, "SELECT status FROM tasks WHERE id='$id' AND user_id='$user_id'");
$task  = mysqli_fetch_assoc($query);

if ($task) {
    // Toggle status: kalau pending → completed, kalau completed → pending
    $new_status = $task['status'] === 'pending' ? 'completed' : 'pending';
    mysqli_query($conn, "UPDATE tasks SET status='$new_status' WHERE id='$id'");
}

header('Location: ../index.php');
exit;
?>