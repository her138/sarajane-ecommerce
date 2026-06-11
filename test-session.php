<?php
require_once __DIR__ . '/config/session.php';

$_SESSION['session_test'] = 'working';

echo '<pre>';
echo "Session ID: " . session_id() . "\n\n";
print_r($_SESSION);
echo '</pre>';