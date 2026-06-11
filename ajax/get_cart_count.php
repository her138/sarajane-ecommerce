<?php
// ajax/get_cart_count.php

require_once "../config/session.php";
require_once "../config/database.php";

header("Content-Type: application/json");

if (empty($_SESSION['user_id'])) {
    echo json_encode(["count" => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);

echo json_encode([
    "count" => (int)$stmt->fetchColumn()
]);