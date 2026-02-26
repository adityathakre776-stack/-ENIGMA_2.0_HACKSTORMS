# 🧠 SmartEdge ML Learning Sandbox
### IoT-Powered Interactive Machine Learning Simulator

> Learn Machine Learning **visually** through **real ESP32 hardware** — no complex coding required.

---

## 🚀 Quick Start

### 1. Prerequisites
- **XAMPP** (PHP 8.0+, MySQL 5.7+, Apache)
- **phpMyAdmin** for database setup
- (Optional) **ESP32** board with sensors

### 2. Database Setup
```sql
-- Open phpMyAdmin → SQL tab → paste and run:
-- File: smartedge_schema.sql
```

**Or import via phpMyAdmin:**
1. Open `http://localhost/phpmyadmin`
2. Click **Import** → Choose `smartedge_schema.sql`
3. Click Go

### 3. Configure Settings
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartedge_ml');
define('DB_USER', 'root');
define('DB_PASS', '');          // Your MySQL password

define('MQTT_BROKER', 'broker.hivemq.com');  // Or your broker
define('SMTP_USER', 'your_email@gmail.com'); // For OTP emails
define('SMTP_PASS', 'your_app_password');
```

### 4. Access Platform
```
http://localhost/NeuroSandbox/
```

**Default Admin Login:**
- Email: `admin@smartedge.local`
- Password: `password`
> ⚠️ Change password immediately after first login!

---

## 📁 Project Structure

```
NeuroSandbox/
├── index.php                    # Landing page
├── dashboard.php                # Main dashboard
├── smartedge_schema.sql         # Complete database schema
│
├── includes/
│   ├── config.php               # Configuration constants
│   ├── db.php                   # PDO database singleton
│   ├── auth.php                 # Authentication & OTP
│   └── helpers.php              # Utility functions
│
├── api/
│   └── index.php                # REST API (all endpoints)
│
├── auth/
│   ├── login.php                # Login + forgot password
│   ├── register.php             # Register + OTP verify
│   └── logout.php               # Session destroy
│
├── ml/
│   ├── playground.php           # 🎛️ ML Playground (MAIN)
│   ├── experiments.php          # Experiment history
│   ├── explainable.php          # 🔬 Explainable AI Mode
│   └── edge-cloud.php           # ☁️ Edge vs Cloud Toggle
│
├── pipeline/
│   └── index.php                # 🔗 Pipeline Visualizer
│
├── mqtt/
│   ├── subscriber.php           # MQTT subscriber service (run via CLI)
│   └── esp32_firmware.ino       # Arduino ESP32 code
│
├── admin/
│   ├── index.php                # Admin dashboard
│   ├── users.php                # User management
│   └── devices.php              # Device management
│
├── gamification/
│   └── levels.php               # 🎮 3 Learning Levels
│
├── datasets/
│   └── index.php                # Dataset generator + download
│
├── assets/
│   ├── css/
│   │   ├── main.css             # Design system
│   │   └── landing.css          # Landing page styles
│   └── js/
│       └── landing.js           # Landing page animations
│
└── uploads/                     # User uploads (auto-created)
```

---

## 🔗 MQTT Integration

### ESP32 Setup
1. Open `mqtt/esp32_firmware.ino` in Arduino IDE
2. Install libraries: `PubSubClient`, `ArduinoJson`, `ESP32Servo`
3. Update WiFi credentials and MQTT broker
4. Flash to ESP32

### MQTT Subscriber (Backend service)
```bash
# Run in a separate terminal/cmd window:
php mqtt/subscriber.php
```
This subscribes to `smartedge/sensor/#` and processes all incoming sensor data.

### MQTT Topics
| Topic | Direction | Description |
|-------|-----------|-------------|
| `smartedge/sensor/water` | ESP32 → Broker | Water level % |
| `smartedge/sensor/mic` | ESP32 → Broker | Mic level dB |
| `smartedge/device/status` | ESP32 → Broker | Device online/offline |
| `smartedge/cmd/ESP32_001/fan` | Broker → ESP32 | Fan ON/OFF |
| `smartedge/cmd/ESP32_001/pump` | Broker → ESP32 | Pump ON/OFF |
| `smartedge/cmd/ESP32_001/servo` | Broker → ESP32 | `angle:90` |

---

## 🎮 Learning Levels

| Level | Challenge | Sensor | Actuator | XP |
|-------|-----------|--------|----------|----|
| 1 | Sound Detection | Microphone | Relay Fan | +100 |
| 2 | Water Automation | Water Level | Water Pump | +150 |
| 3 | Predictive ML | All Sensors | Servo + Relays | +250 |

---

## 🌐 REST API Endpoints

| Method | Action | Description |
|--------|--------|-------------|
| POST | `register` | Create account |
| POST | `verify_otp` | Verify email OTP |
| POST | `login` | Login (creates session) |
| POST | `logout` | Destroy session |
| GET | `sensor_data` | Get sensor readings |
| POST | `sensor_data` | Store sensor reading |
| GET | `devices` | List devices |
| POST | `register_device` | Register new ESP32 |
| POST | `approve_device` | Admin: approve device |
| GET | `experiments` | List experiments |
| POST | `create_experiment` | Create new experiment |
| POST | `run_experiment` | Run ML training |
| GET | `training_steps` | Get training history |
| GET | `datasets` | List datasets |
| POST | `generate_dataset` | Generate CSV from MQTT data |
| GET | `download_dataset` | Download CSV file |
| POST | `send_command` | Send MQTT command to hardware |
| GET | `dashboard_stats` | User dashboard stats |
| GET | `admin_stats` | Admin analytics |
| POST | `chatbot` | NeuroBot AI response |

---

## 🔒 Security Notes
- OTP expires in 5 minutes
- Passwords hashed with bcrypt (cost 12)
- Device approval required before data accepted
- Role-based access control (Admin/Student)
- Session-based authentication

---

## ⚙️ Hardware Wiring

```
ESP32          →    Sensor/Actuator
────────────────────────────────────
GPIO 34 (ADC)  →    Water Level Sensor (AO)
GPIO 35 (ADC)  →    Microphone (AO)
GPIO 32        →    Microphone (DO/digital)
GPIO 18 (PWM)  →    Servo Signal
GPIO 26        →    Relay Module 1 (Fan)
GPIO 27        →    Relay Module 2 (Pump)
3V3            →    Sensors VCC
GND            →    Common Ground
```

---

## 📧 Email OTP Setup
For OTP emails to work:
1. Enable Gmail "App Passwords" (2FA required)
2. Update in `includes/config.php`:
   ```php
   define('SMTP_USER', 'your@gmail.com');
   define('SMTP_PASS', 'xxxx xxxx xxxx xxxx'); // 16-char app password
   ```
> Or use any SMTP relay service (SendGrid, Mailgun, etc.)

---

**SmartEdge ML Sandbox** — Where IoT meets Machine Learning Education 🚀
