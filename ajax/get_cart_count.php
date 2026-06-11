<?php
// ajax/get_cart_count.php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0) 
        FROM cart 
        WHERE user_id = ?
    ");
    $stmt->execute([(int)$_SESSION['user_id']]);

    echo json_encode([
        'count' => (int)$stmt->fetchColumn()
    ]);
    exit;

} catch (Throwable $e) {
    error_log('Cart count error: ' . $e->getMessage());

    echo json_encode(['count' => 0]);
    exit;
}