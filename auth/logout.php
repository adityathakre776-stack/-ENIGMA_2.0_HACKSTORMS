<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$auth->logout();
header('Location: ' . APP_URL . '/auth/login.php');
exit;
