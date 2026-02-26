/*
 * ============================================================
 *  SmartEdge ML Sandbox — ESP32 Firmware
 *  File: esp32_firmware.ino
 * ============================================================
 *  HARDWARE CONNECTIONS:
 *  - Water Level Sensor: Analog pin 34
 *  - Microphone (KY-038): Analog pin 35, Digital pin 32
 *  - Servo Motor: GPIO 18 (PWM)
 *  - Relay Fan: GPIO 26 (HIGH = ON)
 *  - Water Pump Relay: GPIO 27 (HIGH = ON)
 *  - Status LED: GPIO 2 (Built-in)
 *
 *  DEPENDENCIES (Arduino Library Manager):
 *  - PubSubClient by Nick O'Leary
 *  - ArduinoJson by Benoit Blanchon
 *  - ESP32Servo by Kevin Harrington
 * ============================================================
 */

#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <ESP32Servo.h>

// ── WiFi Configuration ───────────────────────────────────────
const char* WIFI_SSID     = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// ── MQTT Configuration ───────────────────────────────────────
const char* MQTT_SERVER   = "broker.hivemq.com";
const int   MQTT_PORT     = 1883;
const char* MQTT_CLIENT_ID= "SmartEdge_ESP32_001";
const char* DEVICE_ID     = "ESP32_001";

// ── MQTT Topics ───────────────────────────────────────────────
#define TOPIC_WATER_PUB   "smartedge/sensor/water"
#define TOPIC_MIC_PUB     "smartedge/sensor/mic"
#define TOPIC_STATUS_PUB  "smartedge/device/status"
#define TOPIC_CMD_FAN     "smartedge/cmd/ESP32_001/fan"
#define TOPIC_CMD_PUMP    "smartedge/cmd/ESP32_001/pump"
#define TOPIC_CMD_SERVO   "smartedge/cmd/ESP32_001/servo"

// ── Pin Definitions ──────────────────────────────────────────
#define PIN_WATER_SENSOR  34   // Analog input
#define PIN_MIC_ANALOG    35   // Microphone analog
#define PIN_MIC_DIGITAL   32   // Microphone digital (threshold)
#define PIN_SERVO         18   // Servo PWM
#define PIN_RELAY_FAN     26   // Fan relay
#define PIN_RELAY_PUMP    27   // Pump relay
#define PIN_STATUS_LED    2    // Built-in LED

// ── Global Objects ────────────────────────────────────────────
WiFiClient   espClient;
PubSubClient mqttClient(espClient);
Servo        servoMotor;

// ── State Variables ───────────────────────────────────────────
bool  fanState   = false;
bool  pumpState  = false;
int   servoAngle = 90;
float waterLevel = 0;
float micLevel   = 0;

unsigned long lastPublish     = 0;
unsigned long lastStatusPub   = 0;
String        sessionId       = "";

// ── Function Prototypes ───────────────────────────────────────
void connectWiFi();
void connectMQTT();
void mqttCallback(char* topic, byte* payload, unsigned int length);
float readWaterLevel();
float readMicLevel();
void publishSensorData();
void publishStatus();
void setFan(bool on);
void setPump(bool on);
void setServo(int angle);
void blinkLED(int times, int ms = 100);
String generateSessionId();

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  Serial.println("\n╔══════════════════════════════════════╗");
  Serial.println("║  SmartEdge ML Sandbox — ESP32 Node   ║");
  Serial.println("╚══════════════════════════════════════╝");

  // Pin modes
  pinMode(PIN_RELAY_FAN,  OUTPUT);
  pinMode(PIN_RELAY_PUMP, OUTPUT);
  pinMode(PIN_STATUS_LED, OUTPUT);
  pinMode(PIN_MIC_DIGITAL, INPUT);

  // Init off
  digitalWrite(PIN_RELAY_FAN,  LOW);
  digitalWrite(PIN_RELAY_PUMP, LOW);
  digitalWrite(PIN_STATUS_LED, LOW);

  // Servo
  servoMotor.attach(PIN_SERVO, 500, 2400);
  servoMotor.write(90);

  // Generate session ID based on boot time
  sessionId = generateSessionId();
  Serial.printf("Session ID: %s\n", sessionId.c_str());

  // Connect WiFi
  connectWiFi();

  // MQTT setup
  mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
  mqttClient.setBufferSize(512);
  mqttClient.setKeepAlive(60);

  connectMQTT();

  blinkLED(3);
  Serial.println("[READY] SmartEdge ESP32 online!");
}

// ============================================================
// MAIN LOOP
// ============================================================
void loop() {
  // Maintain MQTT connection
  if (!mqttClient.connected()) {
    connectMQTT();
  }
  mqttClient.loop();

  unsigned long now = millis();

  // Publish sensor data every 2 seconds
  if (now - lastPublish >= 2000) {
    lastPublish = now;
    publishSensorData();
  }

  // Publish device status every 30 seconds
  if (now - lastStatusPub >= 30000) {
    lastStatusPub = now;
    publishStatus();
  }

  delay(10);
}

// ============================================================
// WiFi Connection
// ============================================================
void connectWiFi() {
  Serial.printf("[WiFi] Connecting to %s", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500); Serial.print(".");
    digitalWrite(PIN_STATUS_LED, !digitalRead(PIN_STATUS_LED));
  }
  Serial.printf("\n[WiFi] ✅ Connected! IP: %s\n", WiFi.localIP().toString().c_str());
  digitalWrite(PIN_STATUS_LED, HIGH);
}

// ============================================================
// MQTT Connection
// ============================================================
void connectMQTT() {
  while (!mqttClient.connected()) {
    Serial.printf("[MQTT] Connecting to %s:%d…", MQTT_SERVER, MQTT_PORT);

    String willTopic   = String("smartedge/device/") + DEVICE_ID + "/lwt";
    String willMessage = "{\"status\":\"offline\",\"device_id\":\"" + String(DEVICE_ID) + "\"}";

    if (mqttClient.connect(MQTT_CLIENT_ID, nullptr, nullptr, willTopic.c_str(), 0, true, willMessage.c_str())) {
      Serial.println(" ✅ Connected!");

      // Subscribe to command topics
      mqttClient.subscribe(TOPIC_CMD_FAN);
      mqttClient.subscribe(TOPIC_CMD_PUMP);
      mqttClient.subscribe(TOPIC_CMD_SERVO);

      // Announce online
      publishStatus();
      blinkLED(2);
    } else {
      Serial.printf(" ❌ Failed (rc=%d), retry in 5s\n", mqttClient.state());
      delay(5000);
    }
  }
}

// ============================================================
// MQTT Callback (Receive Commands from Backend/ML)
// ============================================================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String topicStr(topic);
  String payloadStr;
  for (uint i = 0; i < length; i++) payloadStr += (char)payload[i];

  Serial.printf("[MQTT RX] %s → %s\n", topic, payloadStr.c_str());

  // ── Fan command ─────────────────────────────────────────
  if (topicStr == TOPIC_CMD_FAN) {
    if (payloadStr == "ON" || payloadStr == "1") {
      setFan(true);
    } else if (payloadStr == "OFF" || payloadStr == "0") {
      setFan(false);
    } else if (payloadStr == "TOGGLE") {
      setFan(!fanState);
    }
  }

  // ── Pump command ────────────────────────────────────────
  else if (topicStr == TOPIC_CMD_PUMP) {
    if (payloadStr == "PUMP_ON" || payloadStr == "ON" || payloadStr == "1") {
      setPump(true);
    } else if (payloadStr == "PUMP_OFF" || payloadStr == "OFF" || payloadStr == "0") {
      setPump(false);
    }
  }

  // ── Servo command ───────────────────────────────────────
  else if (topicStr == TOPIC_CMD_SERVO) {
    // Format: "angle:90" or just "90"
    if (payloadStr.startsWith("angle:")) {
      int angle = payloadStr.substring(6).toInt();
      setServo(constrain(angle, 0, 180));
    } else {
      int angle = payloadStr.toInt();
      if (angle >= 0 && angle <= 180) setServo(angle);
    }
  }
}

// ============================================================
// Sensor Readings
// ============================================================
float readWaterLevel() {
  // Water level sensor: 0 = dry, 4095 = full (12-bit ADC)
  int raw = analogRead(PIN_WATER_SENSOR);
  return (raw / 4095.0) * 100.0;  // Convert to percentage
}

float readMicLevel() {
  // Read mic level (average 10 samples for stability)
  long sum = 0;
  for (int i = 0; i < 10; i++) {
    sum += analogRead(PIN_MIC_ANALOG);
    delay(1);
  }
  int raw = sum / 10;
  // Convert raw mic value to approximate dB (simplified)
  float voltage = (raw / 4095.0) * 3.3;
  float db = 20.0 * log10(voltage / 0.006);  // Arbitrary ref
  return constrain(db, 20.0, 120.0);
}

// ============================================================
// Publish Sensor Data
// ============================================================
void publishSensorData() {
  waterLevel = readWaterLevel();
  micLevel   = readMicLevel();

  // ── Water Level ─────────────────────────────────────────
  {
    StaticJsonDocument<200> doc;
    doc["device_id"]   = DEVICE_ID;
    doc["value"]       = round(waterLevel * 10) / 10.0;
    doc["unit"]        = "%";
    doc["sensor_type"] = "water_level";
    doc["session_id"]  = sessionId;

    char buf[200];
    serializeJson(doc, buf);
    mqttClient.publish(TOPIC_WATER_PUB, buf, false);
    Serial.printf("[PUB] Water: %.1f%%\n", waterLevel);
  }

  // ── Microphone ──────────────────────────────────────────
  {
    StaticJsonDocument<200> doc;
    doc["device_id"]   = DEVICE_ID;
    doc["value"]       = round(micLevel * 10) / 10.0;
    doc["unit"]        = "dB";
    doc["sensor_type"] = "mic";
    doc["digital"]     = digitalRead(PIN_MIC_DIGITAL);
    doc["session_id"]  = sessionId;

    char buf[200];
    serializeJson(doc, buf);
    mqttClient.publish(TOPIC_MIC_PUB, buf, false);
    Serial.printf("[PUB] Mic: %.1fdB (dig: %d)\n", micLevel, digitalRead(PIN_MIC_DIGITAL));
  }

  // Visual feedback via LED
  digitalWrite(PIN_STATUS_LED, HIGH);
  delay(50);
  digitalWrite(PIN_STATUS_LED, LOW);
}

// ============================================================
// Publish Device Status
// ============================================================
void publishStatus() {
  StaticJsonDocument<300> doc;
  doc["device_id"]    = DEVICE_ID;
  doc["status"]       = "online";
  doc["ip"]           = WiFi.localIP().toString();
  doc["rssi"]         = WiFi.RSSI();
  doc["uptime_ms"]    = millis();
  doc["fan_on"]       = fanState;
  doc["pump_on"]      = pumpState;
  doc["servo_angle"]  = servoAngle;
  doc["firmware"]     = "1.0.0";

  char buf[300];
  serializeJson(doc, buf);
  mqttClient.publish(TOPIC_STATUS_PUB, buf, true);  // Retained
  Serial.printf("[STATUS] RSSI: %d dBm, Fan: %s, Pump: %s, Servo: %d°\n",
                WiFi.RSSI(),
                fanState ? "ON" : "OFF",
                pumpState ? "ON" : "OFF",
                servoAngle);
}

// ============================================================
// Hardware Control
// ============================================================
void setFan(bool on) {
  fanState = on;
  digitalWrite(PIN_RELAY_FAN, on ? HIGH : LOW);
  Serial.printf("[HW] Fan %s\n", on ? "ON" : "OFF");
  blinkLED(on ? 2 : 1, 50);
}

void setPump(bool on) {
  pumpState = on;
  digitalWrite(PIN_RELAY_PUMP, on ? HIGH : LOW);
  Serial.printf("[HW] Pump %s\n", on ? "ON" : "OFF");
}

void setServo(int angle) {
  servoAngle = angle;
  servoMotor.write(angle);
  Serial.printf("[HW] Servo → %d°\n", angle);
}

// ============================================================
// Utilities
// ============================================================
void blinkLED(int times, int ms) {
  for (int i = 0; i < times; i++) {
    digitalWrite(PIN_STATUS_LED, HIGH); delay(ms);
    digitalWrite(PIN_STATUS_LED, LOW);  delay(ms);
  }
}

String generateSessionId() {
  // Use MAC address + boot time for unique session
  uint64_t mac = ESP.getEfuseMac();
  uint32_t t   = (uint32_t)(millis() / 1000);
  char id[20];
  snprintf(id, sizeof(id), "s%08X%04X", (uint32_t)(mac >> 32), t % 0xFFFF);
  return String(id);
}
