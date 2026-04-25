<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';

$id      = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Pastikan task milik user yang login baru dihapus
$query = "DELETE FROM tasks WHERE id='$id' AND user_id='$user_id'";
if (mysqli_query($conn, $query)) {
    header('Location: ../index.php?success=Task berhasil dihapus!');
} else {
    header('Location: ../index.php?error=Gagal menghapus task!');
}
exit;
?>