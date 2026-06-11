<?php
require_once __DIR__ . '/config/database.php';

echo '<pre>';

echo "PDO loaded: ";
echo extension_loaded('pdo_mysql') ? "YES\n" : "NO\n";

echo "Database connected: ";
echo isset($pdo) && $pdo instanceof PDO ? "YES\n" : "NO\n";

try {
    $stmt = $pdo->query("SELECT DATABASE() AS db_name");
    $db = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current database: " . $db['db_name'] . "\n\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables:\n";
    print_r($tables);

    echo "\nUsers test:\n";
    $stmt = $pdo->query("SELECT id, username, email, role, email_verified FROM users LIMIT 5");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
    echo "DATABASE ERROR:\n";
    echo $e->getMessage();
}

echo '</pre>';