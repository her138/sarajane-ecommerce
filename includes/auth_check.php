<?php
if (!isset($_SESSION)) {
    require_once __DIR__ . '/../config/session.php';
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: /ecommerce-website/login.php");
    exit;
}
