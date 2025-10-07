<?php
require_once '../config/config.php';
header('Content-Type: application/json');

echo json_encode([
    'site_name' => SITE_NAME,
    'site_location' => SITE_LOCATION,
    'site_number' => SITE_NUMBER,
    'site_url' => SITE_URL,
    'site_mail' => SITE_MAIL
]);
