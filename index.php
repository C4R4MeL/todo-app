<?php
session_start();

// Kalau belum login, redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <h3>Halo, <?= $_SESSION['username'] ?>! 👋</h3>
    <p>Selamat datang di Todo App kamu.</p>
    <a href="auth/logout.php" class="btn btn-danger">Logout</a>
</div>

<?php require_once 'includes/footer.php'; ?>