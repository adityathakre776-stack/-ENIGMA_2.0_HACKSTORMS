#!/usr/bin/env php
<?php
/**
 * SmartEdge ML Sandbox — MQTT Subscriber Service
 * 
 * PURPOSE: Subscribes to ESP32 sensor topics on MQTT broker,
 *          processes data, stores to MySQL, and triggers ML + hardware actions.
 * 
 * USAGE: php mqtt/subscriber.php
 * 
 * REQUIRES: composer require bluerhinos/phpmqtt
 *   OR use the included pure-PHP simple MQTT below (no dependencies)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// ── MQTT Simple Client (pure PHP, no Composer needed) ─────────
class SimpleMQTT {
    private $socket;
    private $msgid = 1;
    private $broker;
    private $port;
    private $clientId;
    private $username;
    private $password;
    public  $debug = true;

    public function __construct(string $broker, int $port, string $clientId) {
        $this->broker   = $broker;
        $this->port     = $port;
        $this->clientId = $clientId;
    }

    public function setCredentials(string $user, string $pass): void {
        $this->username = $user;
        $this->password = $pass;
    }

    public function connect(bool $clean = true): bool {
        $this->socket = @fsockopen($this->broker, $this->port, $errno, $errstr, 10);
        if (!$this->socket) {
            $this->log("❌ Cannot connect to {$this->broker}:{$this->port} — $errstr ($errno)");
            return false;
        }

        socket_set_timeout($this->socket, 1);
        stream_set_blocking($this->socket, false);

        // Build CONNECT packet
        $clientIdLength = strlen($this->clientId);
        $payload  = chr(0x00) . chr(0x04) . 'MQTT'; // Protocol name
        $payload .= chr(0x04);                         // Protocol level 3.1.1
        $connectFlags = 0x02;                          // Clean session
        if ($this->username) $connectFlags |= 0x80;
        if ($this->password) $connectFlags |= 0x40;
        $payload .= chr($connectFlags);
        $payload .= chr(0x00) . chr(0x3C);             // Keep alive 60s
        $payload .= chr(0x00) . chr($clientIdLength) . $this->clientId;
        if ($this->username) $payload .= chr(0x00) . chr(strlen($this->username)) . $this->username;
        if ($this->password) $payload .= chr(0x00) . chr(strlen($this->password)) . $this->password;

        $packet = chr(0x10) . chr(strlen($payload)) . $payload;
        fwrite($this->socket, $packet);
        usleep(200000);

        $response = fread($this->socket, 4);
        if (strlen($response) >= 4 && ord($response[3]) === 0) {
            $this->log("✅ Connected to {$this->broker}:{$this->port}");
            return true;
        }
        $this->log("❌ CONNACK error code: " . (strlen($response) >= 4 ? ord($response[3]) : 'no response'));
        return false;
    }

    public function subscribe(string $topic, int $qos = 0): void {
        $topicLen = strlen($topic);
        $payload  = chr(0x00) . chr($this->msgid++) . chr(0x00) . chr($topicLen) . $topic . chr($qos);
        $packet   = chr(0x82) . chr(strlen($payload)) . $payload;
        fwrite($this->socket, $packet);
        $this->log("📡 Subscribed to: $topic");
    }

    public function publish(string $topic, string $message, int $qos = 0): void {
        $payload  = chr(0x00) . chr(strlen($topic)) . $topic . $message;
        $fixedHdr = 0x30 | ($qos << 1);
        $packet   = chr($fixedHdr) . chr(strlen($payload)) . $payload;
        fwrite($this->socket, $packet);
        $this->log("📤 Published to $topic: $message");
    }

    public function listen(callable $callback): void {
        $this->log("🔄 Listening for messages…");
        while (true) {
            $rawByte = @fread($this->socket, 1);
            if ($rawByte === false || $rawByte === '') {
                usleep(100000);
                $this->ping();
                continue;
            }
            $cmd   = ord($rawByte);
            $msgType = ($cmd >> 4) & 0x0F;

            switch ($msgType) {
                case 3: // PUBLISH
                    $lenMult = 1; $remaining = 0; $pos = 0;
                    do {
                        $byte      = ord(fread($this->socket, 1));
                        $remaining += ($byte & 0x7F) * $lenMult;
                        $lenMult  *= 128; $pos++;
                    } while ($byte & 0x80);

                    $data    = fread($this->socket, $remaining);
                    $topicLen= (ord($data[0]) << 8) | ord($data[1]);
                    $topic   = substr($data, 2, $topicLen);
                    $payload = substr($data, 2 + $topicLen);
                    $this->log("📩 MSG [$topic]: $payload");
                    $callback($topic, $payload);
                    break;

                case 13: // PINGRESP
                    $this->log("🏓 PINGRESP received");
                    break;
            }
        }
    }

    private function ping(): void {
        static $lastPing = 0;
        if (time() - $lastPing > 30) {
            fwrite($this->socket, chr(0xC0) . chr(0x00));
            $lastPing = time();
            $this->log("🏓 PINGREQ sent");
        }
    }

    private function log(string $msg): void {
        if ($this->debug) {
            echo "[" . date('Y-m-d H:i:s') . "] $msg\n";
        }
    }

    public function disconnect(): void {
        fwrite($this->socket, chr(0xE0) . chr(0x00));
        fclose($this->socket);
    }
}

// ── ML Prediction Engine ──────────────────────────────────────
class MLPredictor {
    private array $weights;
    private float $threshold;

    public function __construct(float $threshold = 0.5) {
        $this->weights   = [0.04, -0.01, -2.0]; // [w_water, w_mic, bias]
        $this->threshold = $threshold;
    }

    public function predict(float $water, float $mic): array {
        $z    = $this->weights[0] * $water + $this->weights[1] * $mic + $this->weights[2];
        $prob = 1.0 / (1.0 + exp(-$z));

        return [
            'probability' => round($prob, 4),
            'class'       => $prob >= $this->threshold ? 1 : 0,
            'action'      => $prob >= $this->threshold ? 'PUMP_ON' : 'PUMP_OFF',
            'servo_angle' => round($prob * 180),
            'confidence'  => round($prob * 100, 1),
        ];
    }
}

// ── Message Handler ───────────────────────────────────────────
function handleMessage(SimpleMQTT $mqtt, MLPredictor $ml, string $topic, string $payload): void {
    $db = db();
    echo "\n── Received ──────────────────────────────────\n";
    echo "Topic:   $topic\n";
    echo "Payload: $payload\n";

    // Parse JSON payload
    $data = json_decode($payload, true);
    if (!$data && is_numeric($payload)) {
        $data = ['value' => (float)$payload];
    }
    if (!$data) {
        echo "[WARN] Invalid payload: $payload\n";
        return;
    }

    $deviceId   = $data['device_id'] ?? 'ESP32_001';
    $value      = (float)($data['value'] ?? 0);
    $unit       = $data['unit'] ?? '';
    $sessionId  = $data['session_id'] ?? null;

    // Determine sensor type from topic
    $sensorType = 'generic';
    if (str_contains($topic, 'water'))  $sensorType = 'water_level';
    if (str_contains($topic, 'mic') || str_contains($topic, 'sound')) $sensorType = 'mic';
    if (str_contains($topic, 'temp'))   $sensorType = 'temperature';

    // ── Store sensor data ───────────────────────────────────
    try {
        $db->insert(
            "INSERT INTO sensor_data (device_id, topic, sensor_type, value, unit, raw_payload, session_id, recorded_at) VALUES (?,?,?,?,?,?,?,NOW())",
            [$deviceId, $topic, $sensorType, $value, $unit, $payload, $sessionId]
        );
        $db->execute("UPDATE devices SET last_seen=NOW(), is_online=1 WHERE device_id=?", [$deviceId]);
        echo "[DB] Stored {$sensorType}={$value} for {$deviceId}\n";
    } catch (Exception $e) {
        echo "[DB ERROR] " . $e->getMessage() . "\n";
        return;
    }

    // ── Fetch all latest sensor values for ML prediction ───
    $waterRow = $db->fetchOne("SELECT value FROM sensor_data WHERE device_id=? AND sensor_type='water_level' ORDER BY id DESC LIMIT 1", [$deviceId]);
    $micRow   = $db->fetchOne("SELECT value FROM sensor_data WHERE device_id=? AND sensor_type='mic'         ORDER BY id DESC LIMIT 1", [$deviceId]);

    $waterVal = $waterRow ? (float)$waterRow['value'] : 50;
    $micVal   = $micRow   ? (float)$micRow['value']   : 40;

    // ── ML Prediction ───────────────────────────────────────
    $prediction = $ml->predict($waterVal, $micVal);
    echo "[ML]  Water={$waterVal} Mic={$micVal} → P={$prediction['probability']} → {$prediction['action']} (servo:{$prediction['servo_angle']}°)\n";

    // ── MQTT Command (Publish back to hardware) ─────────────
    $cmdTopic   = "smartedge/cmd/{$deviceId}/pump";
    $servoTopic = "smartedge/cmd/{$deviceId}/servo";
    $mqtt->publish($cmdTopic,   $prediction['action']);
    $mqtt->publish($servoTopic, "angle:" . $prediction['servo_angle']);

    // ── Log command ─────────────────────────────────────────
    try {
        $db->insert(
            "INSERT INTO mqtt_commands (device_id, topic, command, payload, source, status) VALUES (?,?,?,?,?,?)",
            [$deviceId, $cmdTopic, $prediction['action'], json_encode($prediction), 'ml_model', 'sent']
        );
        $db->insert(
            "INSERT INTO hardware_actions (device_id, actuator, action, triggered_by, confidence) VALUES (?,?,?,?,?)",
            [$deviceId, 'pump', $prediction['action'], 'ml_prediction', $prediction['probability']]
        );
    } catch (Exception $e) {
        echo "[DB ERROR logging cmd] " . $e->getMessage() . "\n";
    }

    // ── Gamification: Award XP for sensor data contribution ─
    if ($sessionId) {
        $sessionCount = $db->fetchOne("SELECT COUNT(*) c FROM sensor_data WHERE session_id=?", [$sessionId]);
        if (($sessionCount['c'] ?? 0) % 50 === 0) {
            $db->execute("UPDATE users u JOIN devices d ON d.owner_id = u.id SET u.xp_points = u.xp_points + 5 WHERE d.device_id = ?", [$deviceId]);
            echo "[XP] +5 XP awarded for 50 data points collected!\n";
        }
    }

    echo "──────────────────────────────────────────────\n";
}

// ── Main Subscriber Loop ─────────────────────────────────────
echo "╔══════════════════════════════════════════════╗\n";
echo "║  SmartEdge ML — MQTT Subscriber Service      ║\n";
echo "║  Broker: " . MQTT_BROKER . "                 ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

$retryDelay = 5;
$ml = new MLPredictor(0.5);

while (true) {
    $client = new SimpleMQTT(MQTT_BROKER, MQTT_PORT, MQTT_CLIENT_ID);

    if ($client->connect()) {
        // Subscribe to all sensor topics
        $client->subscribe('smartedge/sensor/#');
        $client->subscribe('smartedge/device/status');

        $client->listen(function(string $topic, string $payload) use ($client, $ml) {
            handleMessage($client, $ml, $topic, $payload);
        });
    } else {
        echo "[ERROR] Retrying in {$retryDelay} seconds…\n";
        sleep($retryDelay);
        $retryDelay = min($retryDelay * 2, 60);
    }
}
