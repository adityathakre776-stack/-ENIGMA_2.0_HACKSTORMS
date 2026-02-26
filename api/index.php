<?php
/**
 * SmartEdge ML Sandbox — REST API
 * Endpoint: /api/index.php?action=ACTION_NAME
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$input  = array_merge($_POST, $input);

// ── Route Dispatcher ─────────────────────────────────────
switch ("$method:$action") {

    // ── AUTH ─────────────────────────────────────────────
    case 'POST:register':
        $result = $auth->register($input);
        jsonResponse($result, $result['success'] ? 201 : 400);

    case 'POST:verify_otp':
        $result = $auth->verifyOTP($input['email'] ?? '', $input['otp'] ?? '');
        jsonResponse($result, $result['success'] ? 200 : 400);

    case 'POST:login':
        $result = $auth->login($input['email'] ?? '', $input['password'] ?? '');
        jsonResponse($result, $result['success'] ? 200 : 401);

    case 'POST:logout':
        $auth->logout();
        jsonResponse(['success' => true, 'message' => 'Logged out.']);

    case 'POST:request_reset':
        $result = $auth->requestPasswordReset($input['email'] ?? '');
        jsonResponse($result, $result['success'] ? 200 : 400);

    case 'POST:reset_password':
        $result = $auth->resetPassword($input['email'] ?? '', $input['otp'] ?? '', $input['password'] ?? '');
        jsonResponse($result, $result['success'] ? 200 : 400);

    // ── SENSOR DATA ───────────────────────────────────────
    case 'GET:sensor_data':
        $auth->requireLogin();
        $device    = $_GET['device_id'] ?? '';
        $type      = $_GET['sensor_type'] ?? '';
        $limit     = min((int)($_GET['limit'] ?? 100), 1000);
        $sessionId = $_GET['session_id'] ?? '';

        $sql = "SELECT * FROM sensor_data WHERE 1=1";
        $params = [];
        if ($device) { $sql .= " AND device_id = ?"; $params[] = $device; }
        if ($type)   { $sql .= " AND sensor_type = ?"; $params[] = $type; }
        if ($sessionId) { $sql .= " AND session_id = ?"; $params[] = $sessionId; }
        $sql .= " ORDER BY recorded_at DESC LIMIT $limit";

        jsonResponse(['data' => db()->fetchAll($sql, $params)]);

    case 'POST:sensor_data':
        // For ESP32 or MQTT bridge to push data
        $required = ['device_id','sensor_type','value'];
        foreach ($required as $f) {
            if (empty($input[$f])) {
                jsonResponse(['error' => "Missing field: $f"], 400);
            }
        }

        // Verify device is approved
        $device = db()->fetchOne("SELECT id FROM devices WHERE device_id = ? AND is_approved = 1", [$input['device_id']]);
        if (!$device) {
            jsonResponse(['error' => 'Device not approved or not found.'], 403);
        }

        $id = db()->insert(
            "INSERT INTO sensor_data (device_id, topic, sensor_type, value, unit, raw_payload, session_id, recorded_at) VALUES (?,?,?,?,?,?,?,NOW())",
            [
                $input['device_id'],
                $input['topic'] ?? 'smartedge/sensor/generic',
                $input['sensor_type'],
                (float)$input['value'],
                $input['unit'] ?? null,
                $input['raw_payload'] ?? null,
                $input['session_id'] ?? null,
            ]
        );

        // Update device last_seen
        db()->execute("UPDATE devices SET last_seen = NOW(), is_online = 1 WHERE device_id = ?", [$input['device_id']]);

        jsonResponse(['success' => true, 'id' => $id], 201);

    // ── DEVICES ───────────────────────────────────────────
    case 'GET:devices':
        $auth->requireLogin();
        $where = $auth->isAdmin() ? '' : "AND d.owner_id = {$_SESSION['user_id']}";
        $devs = db()->fetchAll("SELECT d.*, u.name AS owner_name FROM devices d LEFT JOIN users u ON d.owner_id = u.id WHERE 1=1 $where ORDER BY d.created_at DESC");
        jsonResponse(['data' => $devs]);

    case 'POST:register_device':
        $auth->requireLogin();
        $deviceId = sanitize($input['device_id'] ?? '');
        $name     = sanitize($input['device_name'] ?? '');
        if (!$deviceId || !$name) {
            jsonResponse(['error' => 'Device ID and name are required.'], 400);
        }
        $existing = db()->fetchOne("SELECT id FROM devices WHERE device_id = ?", [$deviceId]);
        if ($existing) { jsonResponse(['error' => 'Device already registered.'], 409); }
        $id = db()->insert(
            "INSERT INTO devices (device_id, device_name, description, owner_id, is_approved) VALUES (?,?,?,?,?)",
            [$deviceId, $name, $input['description'] ?? '', $_SESSION['user_id'], 0]
        );
        jsonResponse(['success' => true, 'message' => 'Device registered, pending admin approval.', 'id' => $id], 201);

    case 'POST:approve_device':
        $auth->requireAdmin();
        db()->execute("UPDATE devices SET is_approved = 1 WHERE id = ?", [(int)$input['device_id']]);
        jsonResponse(['success' => true, 'message' => 'Device approved.']);

    case 'DELETE:delete_device':
        $auth->requireAdmin();
        db()->execute("DELETE FROM devices WHERE id = ?", [(int)$input['id']]);
        jsonResponse(['success' => true]);

    // ── ML EXPERIMENTS ────────────────────────────────────
    case 'GET:experiments':
        $auth->requireLogin();
        $userId = $auth->isAdmin() ? null : $_SESSION['user_id'];
        $sql = "SELECT e.*, u.name AS user_name FROM experiments e LEFT JOIN users u ON e.user_id = u.id WHERE 1=1";
        $params = [];
        if ($userId) { $sql .= " AND e.user_id = ?"; $params[] = $userId; }
        $sql .= " ORDER BY e.created_at DESC";
        jsonResponse(['data' => db()->fetchAll($sql, $params)]);

    case 'POST:create_experiment':
        $auth->requireLogin();
        $config = json_encode([
            'learning_rate' => (float)($input['learning_rate'] ?? 0.01),
            'epochs'        => (int)($input['epochs'] ?? 100),
            'threshold'     => (float)($input['threshold'] ?? 0.5),
            'algorithm'     => $input['algorithm'] ?? 'gradient_descent',
            'dataset_id'    => $input['dataset_id'] ?? null,
        ]);
        $id = db()->insert(
            "INSERT INTO experiments (user_id, title, description, experiment_type, level, status, config, created_at) VALUES (?,?,?,?,?,?,?,NOW())",
            [
                $_SESSION['user_id'],
                sanitize($input['title'] ?? 'Untitled Experiment'),
                sanitize($input['description'] ?? ''),
                $input['experiment_type'] ?? 'custom',
                (int)($input['level'] ?? 1),
                'draft',
                $config,
            ]
        );
        // Add XP
        db()->execute("UPDATE users SET xp_points = xp_points + 10 WHERE id = ?", [$_SESSION['user_id']]);
        jsonResponse(['success' => true, 'id' => $id, 'message' => 'Experiment created. +10 XP!'], 201);

    case 'POST:run_experiment':
        $auth->requireLogin();
        $expId = (int)$input['experiment_id'];
        $exp   = db()->fetchOne("SELECT * FROM experiments WHERE id = ? AND user_id = ?", [$expId, $_SESSION['user_id']]);
        if (!$exp) { jsonResponse(['error' => 'Experiment not found.'], 404); }

        $config = json_decode($exp['config'], true);
        $result = simulateMLTraining($expId, $config);

        db()->execute(
            "UPDATE experiments SET status = 'completed', result_data = ?, accuracy = ?, started_at = ?, completed_at = NOW() WHERE id = ?",
            [json_encode($result), $result['final_accuracy'], date('Y-m-d H:i:s', strtotime('-' . $config['epochs'] . ' seconds')), $expId]
        );

        // Gamification
        db()->execute("UPDATE users SET xp_points = xp_points + 50 WHERE id = ?", [$_SESSION['user_id']]);
        jsonResponse(['success' => true, 'result' => $result, 'message' => 'Experiment completed! +50 XP']);

    // ── TRAINING STEPS ────────────────────────────────────
    case 'GET:training_steps':
        $auth->requireLogin();
        $expId = (int)($_GET['experiment_id'] ?? 0);
        $steps = db()->fetchAll(
            "SELECT epoch, loss, accuracy, val_loss, val_accuracy FROM training_steps WHERE experiment_id = ? ORDER BY epoch",
            [$expId]
        );
        jsonResponse(['data' => $steps]);

    // ── DATASETS ──────────────────────────────────────────
    case 'GET:datasets':
        $auth->requireLogin();
        $sql = "SELECT d.*, u.name AS user_name FROM datasets d LEFT JOIN users u ON d.user_id = u.id";
        if (!$auth->isAdmin()) {
            $sql .= " WHERE d.user_id = " . (int)$_SESSION['user_id'];
        }
        $sql .= " ORDER BY d.created_at DESC";
        jsonResponse(['data' => db()->fetchAll($sql)]);

    case 'POST:generate_dataset':
        $auth->requireLogin();
        $deviceId  = $input['device_id'] ?? 'ESP32_001';
        $sensorType= $input['sensor_type'] ?? 'water_level';
        $sessionId = $input['session_id'] ?? uniqid('sess_');
        $rows = db()->fetchAll(
            "SELECT value, unit, recorded_at FROM sensor_data WHERE device_id = ? AND sensor_type = ? ORDER BY recorded_at DESC LIMIT 1000",
            [$deviceId, $sensorType]
        );
        if (empty($rows)) {
            jsonResponse(['error' => 'No data found for this device/sensor combination.'], 404);
        }

        // Write CSV
        $filename = "dataset_{$sessionId}.csv";
        $filepath = DATASET_DIR . $filename;
        $fp = fopen($filepath, 'w');
        fputcsv($fp, ['timestamp', 'value', 'unit', 'label']);
        foreach ($rows as $r) {
            fputcsv($fp, [$r['recorded_at'], $r['value'], $r['unit'], $r['value'] > 50 ? 1 : 0]);
        }
        fclose($fp);

        $id = db()->insert(
            "INSERT INTO datasets (user_id, device_id, name, source, file_path, row_count, session_id) VALUES (?,?,?,?,?,?,?)",
            [$_SESSION['user_id'], $deviceId, "MQTT Dataset ({$sensorType})", 'mqtt', $filename, count($rows), $sessionId]
        );
        jsonResponse(['success' => true, 'id' => $id, 'rows' => count($rows), 'file' => $filename]);

    case 'GET:download_dataset':
        $auth->requireLogin();
        $id = (int)$_GET['id'];
        $ds = db()->fetchOne("SELECT * FROM datasets WHERE id = ?", [$id]);
        if (!$ds) { jsonResponse(['error' => 'Not found.'], 404); }
        $path = DATASET_DIR . $ds['file_path'];
        if (!file_exists($path)) { jsonResponse(['error' => 'File missing.'], 404); }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
        exit;

    // ── MQTT COMMANDS (Publish to hardware) ───────────────
    case 'POST:send_command':
        $auth->requireLogin();
        $deviceId = sanitize($input['device_id'] ?? '');
        $command  = sanitize($input['command'] ?? '');
        $payload  = $input['payload'] ?? '';
        $actuator = $input['actuator'] ?? 'relay';

        if (!$deviceId || !$command) {
            jsonResponse(['error' => 'device_id and command required.'], 400);
        }

        $topic = MQTT_TOPIC_CMD . $deviceId . '/' . $actuator;

        // Log the command
        $cmdId = db()->insert(
            "INSERT INTO mqtt_commands (device_id, topic, command, payload, issued_by, source, status) VALUES (?,?,?,?,?,?,?)",
            [$deviceId, $topic, $command, json_encode($payload), $_SESSION['user_id'], 'user', 'sent']
        );

        // Log hardware action
        db()->insert(
            "INSERT INTO hardware_actions (device_id, actuator, action, triggered_by) VALUES (?,?,?,?)",
            [$deviceId, $actuator, $command, 'manual']
        );

        jsonResponse([
            'success' => true,
            'message' => "Command '{$command}' sent to {$actuator}",
            'topic'   => $topic,
            'command_id' => $cmdId
        ]);

    // ── DASHBOARD STATS ───────────────────────────────────
    case 'GET:dashboard_stats':
        $auth->requireLogin();
        $userId = $_SESSION['user_id'];
        $user   = db()->fetchOne("SELECT xp_points, level FROM users WHERE id = ?", [$userId]);
        $expCount = db()->fetchOne("SELECT COUNT(*) c FROM experiments WHERE user_id = ?", [$userId])['c'];
        $dsCount  = db()->fetchOne("SELECT COUNT(*) c FROM datasets WHERE user_id = ?", [$userId])['c'];
        $latestSensor = db()->fetchOne("SELECT value, sensor_type, recorded_at FROM sensor_data ORDER BY id DESC LIMIT 1");
        $recentExp    = db()->fetchAll("SELECT title, status, accuracy FROM experiments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);
        $progress     = db()->fetchOne("SELECT * FROM user_progress WHERE user_id = ?", [$userId]);
        jsonResponse([
            'xp'            => $user['xp_points'],
            'level'         => $user['level'],
            'experiments'   => $expCount,
            'datasets'      => $dsCount,
            'latest_sensor' => $latestSensor,
            'recent_experiments' => $recentExp,
            'progress'      => $progress,
        ]);

    // ── ADMIN STATS ───────────────────────────────────────
    case 'GET:admin_stats':
        $auth->requireAdmin();
        jsonResponse([
            'total_users'       => db()->fetchOne("SELECT COUNT(*) c FROM users")['c'],
            'total_devices'     => db()->fetchOne("SELECT COUNT(*) c FROM devices")['c'],
            'online_devices'    => db()->fetchOne("SELECT COUNT(*) c FROM devices WHERE is_online = 1")['c'],
            'total_experiments' => db()->fetchOne("SELECT COUNT(*) c FROM experiments")['c'],
            'total_sensor_rows' => db()->fetchOne("SELECT COUNT(*) c FROM sensor_data")['c'],
            'pending_devices'   => db()->fetchOne("SELECT COUNT(*) c FROM devices WHERE is_approved = 0")['c'],
            'recent_logs'       => db()->fetchAll("SELECT al.*, u.name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 20"),
            'top_learners'      => db()->fetchAll("SELECT name, email, xp_points, level FROM users WHERE role='student' ORDER BY xp_points DESC LIMIT 10"),
        ]);

    // ── ADMIN CRUD: USERS ──────────────────────────────────
    case 'GET:admin_users':
        $auth->requireAdmin();
        jsonResponse(['data' => db()->fetchAll("SELECT id, name, email, role, is_verified, is_active, xp_points, level, last_login, created_at FROM users ORDER BY created_at DESC")]);

    case 'PUT:update_user':
        $auth->requireAdmin();
        $id = (int)$input['id'];
        db()->execute(
            "UPDATE users SET name=?, role=?, is_active=? WHERE id=?",
            [sanitize($input['name'] ?? ''), $input['role'] ?? 'student', (int)($input['is_active'] ?? 1), $id]
        );
        jsonResponse(['success' => true, 'message' => 'User updated.']);

    case 'DELETE:delete_user':
        $auth->requireAdmin();
        if ((int)$input['id'] === $_SESSION['user_id']) {
            jsonResponse(['error' => 'Cannot delete yourself.'], 400);
        }
        db()->execute("DELETE FROM users WHERE id = ?", [(int)$input['id']]);
        jsonResponse(['success' => true]);

    // ── CHATBOT ───────────────────────────────────────────
    case 'POST:chatbot':
        $auth->requireLogin();
        $message   = trim($input['message'] ?? '');
        $sessionId = $input['session_id'] ?? session_id();
        $context   = $input['context'] ?? [];  // ML params context

        if (!$message) { jsonResponse(['error' => 'Message required.'], 400); }

        // Save user message
        db()->insert(
            "INSERT INTO chatbot_history (user_id, session_id, role, message, context) VALUES (?,?,?,?,?)",
            [$_SESSION['user_id'], $sessionId, 'user', $message, json_encode($context)]
        );

        // AI Response (rule-based ML tutor)
        $response = mlTutorResponse($message, $context);

        // Save assistant response
        db()->insert(
            "INSERT INTO chatbot_history (user_id, session_id, role, message) VALUES (?,?,?,?)",
            [$_SESSION['user_id'], $sessionId, 'assistant', $response]
        );

        jsonResponse(['success' => true, 'response' => $response, 'session_id' => $sessionId]);

    // ── PIPELINE STATUS ───────────────────────────────────
    case 'GET:pipeline_status':
        $auth->requireLogin();
        $expId = (int)($_GET['experiment_id'] ?? 0);
        $logs  = db()->fetchAll(
            "SELECT stage, status, latency_ms, created_at FROM pipeline_executions WHERE experiment_id = ? ORDER BY id DESC LIMIT 10",
            [$expId]
        );
        jsonResponse(['data' => $logs]);

    // ── REPLAY SESSIONS ────────────────────────────────────
    case 'GET:replay_sessions':
        $auth->requireLogin();
        $sessions = db()->fetchAll(
            "SELECT rs.*, u.name AS user_name FROM replay_sessions rs LEFT JOIN users u ON rs.user_id = u.id ORDER BY rs.created_at DESC LIMIT 50"
        );
        jsonResponse(['data' => $sessions]);

    case 'POST:save_replay':
        $auth->requireLogin();
        $sid = uniqid('replay_');
        $id  = db()->insert(
            "INSERT INTO replay_sessions (user_id, experiment_id, session_id, title, description, data_points, duration_ms, created_at) VALUES (?,?,?,?,?,?,?,NOW())",
            [$_SESSION['user_id'], $input['experiment_id'] ?? null, $sid, sanitize($input['title'] ?? 'Replay'), sanitize($input['description'] ?? ''), (int)($input['data_points'] ?? 0), (int)($input['duration_ms'] ?? 0)]
        );
        jsonResponse(['success' => true, 'id' => $id, 'session_id' => $sid]);

    // ── NOTIFICATIONS ─────────────────────────────────────
    case 'GET:notifications':
        $auth->requireLogin();
        $notifs = db()->fetchAll(
            "SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0 ORDER BY created_at DESC LIMIT 20",
            [$_SESSION['user_id']]
        );
        jsonResponse(['data' => $notifs]);

    case 'POST:mark_read':
        $auth->requireLogin();
        db()->execute("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [(int)$input['id'], $_SESSION['user_id']]);
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['error' => "Unknown action: $action", 'method' => $method], 404);
}

// ============================================================
//  HELPER FUNCTIONS
// ============================================================

/**
 * Simulate ML training and store steps in DB
 */
function simulateMLTraining(int $expId, array $config): array {
    $lr      = $config['learning_rate'] ?? 0.01;
    $epochs  = min((int)($config['epochs'] ?? 100), 500);
    $thresh  = $config['threshold'] ?? 0.5;

    $loss      = 1.0;
    $accuracy  = 0.0;
    $steps     = [];
    $lossHistory = [];
    $accHistory  = [];

    for ($e = 1; $e <= $epochs; $e++) {
        // Simulate gradient descent
        $noise   = (mt_rand(-50, 50) / 1000);
        $loss    = max(0.01, $loss - ($lr * 0.9) + $noise);
        $accuracy= min(0.99, $accuracy + ($lr * 0.85) + abs($noise) * 0.5);

        $valLoss = $loss + (mt_rand(10, 50) / 1000);
        $valAcc  = $accuracy - (mt_rand(0, 30) / 1000);

        // Store every 5th epoch to avoid DB bloat
        if ($e % max(1, intval($epochs / 50)) === 0) {
            db()->execute(
                "INSERT INTO training_steps (experiment_id, epoch, loss, accuracy, val_loss, val_accuracy) VALUES (?,?,?,?,?,?)",
                [$expId, $e, round($loss, 4), round($accuracy, 4), round($valLoss, 4), round($valAcc, 4)]
            );
        }

        $lossHistory[] = round($loss, 4);
        $accHistory[]  = round($accuracy, 4);
    }

    return [
        'final_accuracy' => round($accuracy, 4),
        'final_loss'     => round($loss, 4),
        'epochs_run'     => $epochs,
        'loss_history'   => $lossHistory,
        'acc_history'    => $accHistory,
        'converged'      => $loss < 0.1,
    ];
}

/**
 * Rule-based ML Tutor Chatbot
 */
function mlTutorResponse(string $msg, array $ctx): string {
    $msg = strtolower($msg);
    $lr  = $ctx['learning_rate'] ?? 0.01;
    $ep  = $ctx['epochs'] ?? 100;
    $th  = $ctx['threshold'] ?? 0.5;

    if (str_contains($msg, 'learning rate') || str_contains($msg, 'lr')) {
        return "📐 **Learning Rate** controls how big the steps are during gradient descent. Your current LR is **{$lr}**.\n\n" .
               "• Too high (>0.1) → Model diverges, loss explodes 💥\n" .
               "• Too low (<0.001) → Very slow training ⏳\n" .
               "• Sweet spot for most tasks: **0.001 – 0.01** ✅\n\n" .
               ($lr > 0.1 ? "⚠️ Your LR seems high. Try reducing to 0.01!" : "Your LR looks reasonable! 🎯");
    }

    if (str_contains($msg, 'overfitting') || str_contains($msg, 'overfit')) {
        return "📊 **Overfitting** occurs when your model memorizes training data instead of generalizing.\n\n" .
               "Signs: Training accuracy high, Validation accuracy low.\n\n" .
               "🔧 Fixes:\n• Reduce epochs (currently {$ep})\n• Add more training data\n• Use regularization (L1/L2)\n• Use dropout layers\n• Early stopping";
    }

    if (str_contains($msg, 'epoch')) {
        return "🔄 **Epochs** = How many times the model sees the entire dataset.\n\n" .
               "You have {$ep} epochs set.\n\n" .
               "• Too few → Underfitting (model hasn't learned enough)\n" .
               "• Too many → Overfitting (model memorizes training data)\n\n" .
               "Tip: Watch the validation loss curve — stop when it starts rising! 📈";
    }

    if (str_contains($msg, 'gradient') || str_contains($msg, 'descent')) {
        return "📉 **Gradient Descent** is the core optimization algorithm in ML.\n\n" .
               "It minimizes the loss function by:\n1. Computing gradient (direction of steepest increase)\n2. Moving opposite to gradient\n3. Repeat for each epoch\n\n" .
               "Think of it as rolling a ball downhill to find the lowest point! ⛰️";
    }

    if (str_contains($msg, 'threshold') || str_contains($msg, 'predict')) {
        return "🎯 **Classification Threshold** = The decision boundary.\n\n" .
               "Your threshold is **{$th}**.\n\n" .
               "• Output probability > {$th} → Class 1 (e.g., pump ON)\n" .
               "• Output probability ≤ {$th} → Class 0 (pump OFF)\n\n" .
               "Adjust based on your false positive/negative tolerance!";
    }

    if (str_contains($msg, 'mqtt') || str_contains($msg, 'esp32') || str_contains($msg, 'iot')) {
        return "📡 **MQTT + ESP32 Integration**:\n\n" .
               "Your ESP32 publishes sensor data to the MQTT broker → Our backend subscribes → Processes via ML model → Sends commands back!\n\n" .
               "Topics:\n• `smartedge/sensor/water` – Water level data\n• `smartedge/sensor/mic` – Microphone input\n• `smartedge/cmd/{device_id}/fan` – Fan control\n• `smartedge/cmd/{device_id}/pump` – Pump control\n\n" .
               "This is called **Edge-to-Cloud ML**! 🌐";
    }

    if (str_contains($msg, 'accuracy') || str_contains($msg, 'loss')) {
        return "📊 **Accuracy vs Loss**:\n\n" .
               "• **Loss** = How wrong the model is (lower is better)\n" .
               "• **Accuracy** = % of correct predictions (higher is better)\n\n" .
               "Ideal training: Loss ↓↓, Accuracy ↑↑\n\n" .
               "Watch for: if val_loss goes up while train_loss goes down → **Overfitting!**";
    }

    if (str_contains($msg, 'hello') || str_contains($msg, 'hi') || str_contains($msg, 'hey')) {
        return "👋 Hello! I'm **NeuroBot**, your ML tutor assistant!\n\nI can help you with:\n• 📐 Learning Rate & Hyperparameters\n• 📉 Gradient Descent\n• 📊 Overfitting & Generalization\n• 🎯 Thresholds & Predictions\n• 📡 MQTT & IoT integration\n\nWhat would you like to learn today?";
    }

    // Default response
    return "🤖 Great question! I'm here to help you understand Machine Learning concepts.\n\nTry asking me about:\n• **Learning rate** – How to choose the right value\n• **Overfitting** – How to detect and prevent it\n• **Gradient descent** – The optimization engine\n• **Epochs** – How much training is enough\n• **MQTT** – How your ESP32 feeds data to the ML model\n\nWhat would you like to explore? 🧠";
}
