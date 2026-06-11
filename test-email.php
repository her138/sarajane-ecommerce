<?php
require_once __DIR__ . '/config/email.php';

$result = sendEmail(
    'webklinic2024@gmail.com',
    'SaraJane SMTP Test',
    '<h2>SMTP is working</h2><p>This is a test email from SaraJane.</p>'
);

echo '<pre>';
var_dump($result);
echo '</pre>';