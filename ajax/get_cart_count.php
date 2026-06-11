<?php
session_start();
require_once "../config/database.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["count" => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

echo json_encode([
    "count" => (int)$stmt->fetchColumn()
]);
