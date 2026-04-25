<?php
session_start();
session_destroy();
header('Location: ../auth/auth.php');
exit;
?>