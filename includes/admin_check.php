<?php
// includes/admin_check.php

require_once __DIR__ . '/auth_check.php';

if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../index.php');
    exit;
}