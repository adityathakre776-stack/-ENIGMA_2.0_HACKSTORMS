<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect logged in users
if ($auth->isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartEdge ML Learning Sandbox — IoT-Powered ML Platform</title>
<meta name="description" content="Learn Machine Learning visually through real IoT hardware. ESP32-powered interactive ML simulator with real-time sensor data, gradient descent visualization, and hands-on experiments.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<!-- Animated Background -->
<div class="bg-canvas">
  <div class="bg-blob b1"></div>
  <div class="bg-blob b2"></div>
  <div class="bg-blob b3"></div>
  <canvas id="particleCanvas"></canvas>
</div>

<!-- Navigation -->
<nav class="navbar navbar-landing">
  <a class="navbar-brand" href="#">
    <div class="brand-logo">🧠</div>
    <span>SmartEdge <span class="gradient-text">ML</span> Sandbox</span>
  </a>
  <div class="navbar-nav" style="flex:1;justify-content:center;gap:4px">
    <a href="#features" class="nav-link">Features</a>
    <a href="#pipeline" class="nav-link">Pipeline</a>
    <a href="#hardware" class="nav-link">Hardware</a>
    <a href="#gamification" class="nav-link">Gamification</a>
  </div>
  <div class="navbar-right">
    <a href="auth/login.php" class="btn btn-outline btn-sm">Login</a>
    <a href="auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="hero-badge fade-in">
      <span class="online-dot"></span>
      <span>Live IoT + ML Platform • ESP32 Integrated</span>
    </div>
    <h1 class="hero-title fade-in" style="animation-delay:.1s">
      Learn Machine Learning<br>
      <span class="gradient-text">Through Real Hardware</span>
    </h1>
    <p class="hero-subtitle fade-in" style="animation-delay:.2s">
      Where your ESP32 sensors become training data. See gradient descent happen in real-time,<br>
      control physical hardware with ML predictions, and master AI without writing complex code.
    </p>
    <div class="hero-cta fade-in" style="animation-delay:.3s">
      <a href="auth/register.php" class="btn btn-primary btn-lg">
        🚀 Start Learning Free
      </a>
      <a href="#pipeline" class="btn btn-outline btn-lg">
        📡 See How It Works
      </a>
    </div>
    <!-- Hero Stats -->
    <div class="hero-stats fade-in" style="animation-delay:.4s">
      <div class="hero-stat">
        <div class="stat-num gradient-text">12</div>
        <div class="stat-desc">ML Experiments</div>
      </div>
      <div class="hero-stat">
        <div class="stat-num gradient-text">6+</div>
        <div class="stat-desc">Hardware Sensors</div>
      </div>
      <div class="hero-stat">
        <div class="stat-num gradient-text">MQTT</div>
        <div class="stat-desc">Real-time Protocol</div>
      </div>
      <div class="hero-stat">
        <div class="stat-num gradient-text">100%</div>
        <div class="stat-desc">Visual Learning</div>
      </div>
    </div>

    <!-- Live Demo Preview -->
    <div class="hero-preview fade-in" style="animation-delay:.5s">
      <div class="preview-window">
        <div class="preview-bar">
          <span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
          <span class="preview-url">localhost/NeuroSandbox/dashboard.php</span>
        </div>
        <div class="preview-content">
          <div class="mini-pipeline">
            <div class="mp-node mp-active">📡<span>ESP32</span></div>
            <div class="mp-arrow active"></div>
            <div class="mp-node mp-active">🔗<span>MQTT</span></div>
            <div class="mp-arrow active"></div>
            <div class="mp-node running">⚙️<span>Backend</span></div>
            <div class="mp-arrow"></div>
            <div class="mp-node">🤖<span>ML Model</span></div>
            <div class="mp-arrow"></div>
            <div class="mp-node">🎯<span>Prediction</span></div>
            <div class="mp-arrow"></div>
            <div class="mp-node">🔌<span>Hardware</span></div>
          </div>
          <div class="mini-charts">
            <canvas id="miniLossChart" width="200" height="80"></canvas>
            <canvas id="miniAccChart" width="200" height="80"></canvas>
          </div>
          <div class="mini-sensors">
            <div class="ms-item"><span>💧 Water Level</span><span class="ms-val" id="demoWater">72%</span></div>
            <div class="ms-item"><span>🔊 Mic Input</span><span class="ms-val" id="demoMic">45dB</span></div>
            <div class="ms-item"><span>⚙️ Servo</span><span class="ms-val" id="demoServo">127°</span></div>
            <div class="ms-item"><span>💨 Fan</span><span class="ms-val success" id="demoFan">ON</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pipeline Section -->
<section class="section" id="pipeline">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">System Architecture</div>
      <h2>How SmartEdge <span class="gradient-text">Works</span></h2>
      <p>From real sensors to ML predictions to physical hardware — all in milliseconds</p>
    </div>
    <div class="pipeline-flow">
      <div class="pf-step" data-delay="0">
        <div class="pf-icon">📡</div>
        <h3>ESP32 Senses</h3>
        <p>Water level, microphone, temperature sensors collect real-world data continuously</p>
        <div class="pf-tech">ESP32 + Sensors</div>
      </div>
      <div class="pf-arrow">→</div>
      <div class="pf-step" data-delay="1">
        <div class="pf-icon">🔗</div>
        <h3>MQTT Publish</h3>
        <p>ESP32 publishes sensor readings to HiveMQ broker on <code>smartedge/sensor/#</code></p>
        <div class="pf-tech">MQTT Protocol</div>
      </div>
      <div class="pf-arrow">→</div>
      <div class="pf-step" data-delay="2">
        <div class="pf-icon">⚙️</div>
        <h3>PHP Backend</h3>
        <p>MQTT subscriber processes data, stores in MySQL, triggers ML simulation</p>
        <div class="pf-tech">PHP + MySQL</div>
      </div>
      <div class="pf-arrow">→</div>
      <div class="pf-step" data-delay="3">
        <div class="pf-icon">🤖</div>
        <h3>ML Prediction</h3>
        <p>Trained model predicts outcomes: pump ON/OFF, fan speed, servo angle</p>
        <div class="pf-tech">ML Engine</div>
      </div>
      <div class="pf-arrow">→</div>
      <div class="pf-step" data-delay="4">
        <div class="pf-icon">🔌</div>
        <h3>Hardware Action</h3>
        <p>Backend publishes MQTT commands → ESP32 actuates relay, fan, pump, servo</p>
        <div class="pf-tech">MQTT + GPIO</div>
      </div>
    </div>
  </div>
</section>

<!-- Features Grid -->
<section class="section" id="features">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Platform Features</div>
      <h2>Everything You Need to <span class="gradient-text">Master ML</span></h2>
    </div>
    <div class="features-grid stagger">
      <div class="feature-card card">
        <div class="feature-icon">🎛️</div>
        <h3>Interactive ML Playground</h3>
        <p>Tune learning rate, epochs, and threshold with sliders. Watch gradient descent animate in real-time on a decision boundary canvas.</p>
        <div class="feature-tags"><span class="badge badge-success">Live Charts</span><span class="badge badge-info">Animations</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">📡</div>
        <h3>MQTT IoT Integration</h3>
        <p>Real ESP32 sensor data flows through MQTT into ML models. Publish commands back to control physical hardware.</p>
        <div class="feature-tags"><span class="badge badge-success">Real-time</span><span class="badge badge-purple">Hardware</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">🔐</div>
        <h3>Secure Authentication</h3>
        <p>OTP email verification, role-based access for Admin/Student, and device authentication with Device ID approval system.</p>
        <div class="feature-tags"><span class="badge badge-warning">OTP Verified</span><span class="badge badge-info">RBAC</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">🤖</div>
        <h3>AI Chatbot Tutor</h3>
        <p>NeuroBot explains ML concepts, suggests hyperparameter changes, and guides you through experiments interactively.</p>
        <div class="feature-tags"><span class="badge badge-success">Context-Aware</span><span class="badge badge-purple">ML Expert</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">🎮</div>
        <h3>Gamified Learning</h3>
        <p>3 levels: Sound→Fan control, Water automation, Predictive ML. Earn XP, unlock badges, and track your progress.</p>
        <div class="feature-tags"><span class="badge badge-warning">XP System</span><span class="badge badge-success">Badges</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">📊</div>
        <h3>Live Dataset Generator</h3>
        <p>MQTT sensor data auto-saved to MySQL. Export as CSV, retrain experiments, and build your own ML datasets.</p>
        <div class="feature-tags"><span class="badge badge-info">CSV Export</span><span class="badge badge-success">Auto-collect</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">🔬</div>
        <h3>Explainable AI Mode</h3>
        <p>See which sensor features influence predictions most. Feature importance visualization with dynamic highlighting.</p>
        <div class="feature-tags"><span class="badge badge-purple">XAI</span><span class="badge badge-success">Transparent</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">🔁</div>
        <h3>Learning Replay Mode</h3>
        <p>Record MQTT sessions and replay experiment training step-by-step. Review your learning journey visually.</p>
        <div class="feature-tags"><span class="badge badge-info">Session Record</span><span class="badge badge-warning">Replay</span></div>
      </div>
      <div class="feature-card card">
        <div class="feature-icon">☁️</div>
        <h3>Edge vs Cloud Toggle</h3>
        <p>Switch between local ESP32 automation and cloud ML processing. Compare response times and accuracy.</p>
        <div class="feature-tags"><span class="badge badge-success">Edge</span><span class="badge badge-info">Cloud</span></div>
      </div>
    </div>
  </div>
</section>

<!-- Hardware Section -->
<section class="section hardware-section" id="hardware">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Physical Hardware</div>
      <h2>Real Sensors. <span class="gradient-text">Real Learning.</span></h2>
      <p>Unlike simulations — SmartEdge connects to actual ESP32 hardware for authentic IoT + ML experience</p>
    </div>
    <div class="hardware-grid">
      <div class="hw-card card">
        <div class="hw-icon">📡</div>
        <h4>ESP32 Microcontroller</h4>
        <p>Main control unit with WiFi & MQTT client firmware</p>
        <span class="badge badge-success">Primary Hub</span>
      </div>
      <div class="hw-card card">
        <div class="hw-icon">💧</div>
        <h4>Water Level Sensor</h4>
        <p>Analog sensor measuring liquid levels 0-100%</p>
        <span class="badge badge-info">Level 2</span>
      </div>
      <div class="hw-card card">
        <div class="hw-icon">🔊</div>
        <h4>Microphone Sensor</h4>
        <p>Sound detection for Level 1 fan control experiment</p>
        <span class="badge badge-info">Level 1</span>
      </div>
      <div class="hw-card card">
        <div class="hw-icon">🔧</div>
        <h4>Servo Motor</h4>
        <p>Angle represents ML prediction confidence (0–180°)</p>
        <span class="badge badge-purple">ML Feedback</span>
      </div>
      <div class="hw-card card">
        <div class="hw-icon">💨</div>
        <h4>Relay Fan</h4>
        <p>Controlled ON/OFF by ML sound classification</p>
        <span class="badge badge-warning">Actuator</span>
      </div>
      <div class="hw-card card">
        <div class="hw-icon">🚿</div>
        <h4>Water Pump</h4>
        <p>Auto-activated by predictive ML water level model</p>
        <span class="badge badge-warning">Actuator</span>
      </div>
    </div>
  </div>
</section>

<!-- Gamification Section -->
<section class="section" id="gamification">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Gamified Learning Path</div>
      <h2>3 Levels. Real <span class="gradient-text">Hardware. Real ML.</span></h2>
    </div>
    <div class="levels-grid">
      <div class="level-card card">
        <div class="level-badge">Level 1</div>
        <div class="level-icon">🔊</div>
        <h3>Sound Detection → Fan Control</h3>
        <p>Train a binary classifier on microphone data. Predict loud/quiet. Control relay fan with predictions.</p>
        <div class="level-reward"><span>🏆 +100 XP</span><span>🎖️ Sound Ranger Badge</span></div>
        <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
      </div>
      <div class="level-card card">
        <div class="level-badge">Level 2</div>
        <div class="level-icon">💧</div>
        <h3>Water Level Automation</h3>
        <p>Use threshold-based ML to predict water shortage. Auto-activate water pump when model predicts low level.</p>
        <div class="level-reward"><span>🏆 +150 XP</span><span>🎖️ Hydro Engineer Badge</span></div>
        <div class="progress"><div class="progress-bar" style="width:60%"></div></div>
      </div>
      <div class="level-card card">
        <div class="level-badge">Level 3</div>
        <div class="level-icon">🤖</div>
        <h3>Predictive ML Automation</h3>
        <p>Multi-sensor fusion ML. ESP32 data trains a regression model. Servo angle = prediction confidence. Full pipeline!</p>
        <div class="level-reward"><span>🏆 +250 XP</span><span>🎖️ ML Master Badge</span></div>
        <div class="progress"><div class="progress-bar" style="width:20%"></div></div>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
  <div class="container">
    <div class="cta-card card-glass">
      <div class="bg-blob b4"></div>
      <h2>Ready to Learn ML with <span class="gradient-text">Real Hardware?</span></h2>
      <p>Connect your ESP32, start your first experiment, and watch Machine Learning come to life.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:32px">
        <a href="auth/register.php" class="btn btn-primary btn-lg">🚀 Create Free Account</a>
        <a href="auth/login.php" class="btn btn-outline btn-lg">🔐 Login</a>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="navbar-brand" style="margin-bottom:12px">
          <div class="brand-logo">🧠</div>
          <span>SmartEdge ML Sandbox</span>
        </div>
        <p style="color:var(--text-muted);font-size:.875rem">IoT-powered Machine Learning platform for visual, interactive, hardware-driven education.</p>
      </div>
      <div>
        <h4 style="margin-bottom:16px;font-size:.875rem;color:var(--text-secondary)">Platform</h4>
        <div style="display:flex;flex-direction:column;gap:8px">
          <a href="dashboard.php" class="footer-link">Dashboard</a>
          <a href="ml/playground.php" class="footer-link">ML Playground</a>
          <a href="pipeline/index.php" class="footer-link">Pipeline Visualizer</a>
          <a href="datasets/index.php" class="footer-link">Datasets</a>
        </div>
      </div>
      <div>
        <h4 style="margin-bottom:16px;font-size:.875rem;color:var(--text-secondary)">Tech Stack</h4>
        <div style="display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:.875rem">
          <span>📡 MQTT (HiveMQ)</span>
          <span>🔧 PHP + MySQL</span>
          <span>🤖 ESP32 (Arduino)</span>
          <span>📊 Chart.js</span>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2026 SmartEdge ML Learning Sandbox. Built for IoT + ML Education.</p>
      <p>Default Admin: <code>admin@smartedge.local</code> / <code>password</code></p>
    </div>
  </div>
</footer>

<script src="assets/js/landing.js"></script>
</body>
</html>
