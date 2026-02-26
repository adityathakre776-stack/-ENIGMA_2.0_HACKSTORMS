<?php
// ============================================================
// SmartEdge ML Learning Sandbox - Core Configuration
// ============================================================

define('APP_NAME', 'SmartEdge ML Learning Sandbox');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/NeuroSandbox');
define('APP_ROOT', dirname(__DIR__));

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartedge_ml');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// MQTT Broker Configuration
define('MQTT_BROKER', 'broker.hivemq.com');
define('MQTT_PORT', 1883);
define('MQTT_CLIENT_ID', 'smartedge_backend_' . uniqid());
define('MQTT_USERNAME', '');
define('MQTT_PASSWORD', '');
define('MQTT_TOPIC_SENSOR', 'smartedge/sensor/#');
define('MQTT_TOPIC_WATER', 'smartedge/sensor/water');
define('MQTT_TOPIC_MIC', 'smartedge/sensor/mic');
define('MQTT_TOPIC_STATUS', 'smartedge/device/status');
define('MQTT_TOPIC_CMD', 'smartedge/cmd/');

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_FROM', 'smartedge@yourdomain.com');
define('SMTP_FROM_NAME', 'SmartEdge ML Sandbox');

// Session & Security
define('SESSION_NAME', 'smartedge_session');
define('SESSION_LIFETIME', 3600);
define('OTP_EXPIRY', 300); // 5 minutes in seconds
define('JWT_SECRET', 'smartedge_jwt_secret_2024_ultra_secure');
define('BCRYPT_COST', 12);

// ML Simulation
define('ML_DEFAULT_LR', 0.01);
define('ML_DEFAULT_EPOCHS', 100);
define('ML_DEFAULT_THRESHOLD', 0.5);
define('ML_MAX_DATASET_ROWS', 10000);

// Upload Paths
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('DATASET_DIR', APP_ROOT . '/datasets/');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
