<?php
// config/config.php
if (!defined('SITE_URL')) {
    define('SITE_URL', getenv('SITE_URL') ?: '/');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'SaraJane');
}
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'webklinic2024@gmail.com');
}