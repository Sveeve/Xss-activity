<?php
require_once __DIR__ . '/auth.php';

// -- Logout --
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit();
}
?>
