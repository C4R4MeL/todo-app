<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Tambahkan style.css -->
    <?php
    // Deteksi otomatis path style.css
    $depth = substr_count($_SERVER['PHP_SELF'], '/');
    $prefix = $depth > 2 ? '../' : '';
    echo "<link rel='stylesheet' href='{$prefix}style.css'>";
    ?>
</head>

<body class="bg-light">