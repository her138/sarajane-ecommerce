<?php
require_once __DIR__ . '/config/session.php';

$_SESSION['session_test'] = 'working';

echo '<pre>';
echo "Session name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n\n";
print_r($_COOKIE);
print_r($_SESSION);
echo '</pre>';