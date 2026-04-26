<?php
session_start();
session_destroy();

function getMyUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

$server = getMyUrl();

// Detect if running locally or on the server
$is_local = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if ($is_local) {
    $base_path = "/General"; // Local path
} else {
    $base_path = ""; // Server path
}

//echo $server . $base_path;

header("Location: $server$base_path/login");
exit();
// session_destroy();
// header("location:https://$_SERVER[HTTP_HOST]/General/login");
