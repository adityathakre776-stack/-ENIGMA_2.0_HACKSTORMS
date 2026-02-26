<?php
// Helper functions
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data);
    exit;
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function getInput(): array {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    return array_merge($_POST, $input);
}

function formatBytes(int $bytes): string {
    $units = ['B','KB','MB','GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hrs ago';
    return floor($time/86400) . ' days ago';
}

function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function dd(mixed $data): void {
    echo '<pre>' . print_r($data, true) . '</pre>';
    exit;
}
