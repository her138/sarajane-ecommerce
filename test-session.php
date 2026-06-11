<?php
require_once __DIR__ . '/config/session.php';

$_SESSION['session_test'] = 'working';

echo '<pre>';
echo "Session name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n\n";

echo "Cookie received by PHP:\n";
print_r($_COOKIE);

echo "\nSession data:\n";
print_r($_SESSION);
echo '</pre>';