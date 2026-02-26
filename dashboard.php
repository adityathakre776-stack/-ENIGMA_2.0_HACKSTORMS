<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
$auth->requireLogin();
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
$initial  = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SmartEdge ML Sandbox</title>
<link rel="stylesheet" href="assets/css/main.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.dashboard-header{margin-bottom:32px}
.dashboard-header h1{font-size:1.75rem}
.widget-row{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px}
.sensor-live{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
.sensor-card{padding:24px;position:relative;overflow:hidden}
.sensor-reading{font-size:3rem;font-weight:900;line-height:1;margin:12px 0}
.sensor-trend{font-size:.8rem;font-weight:600}
.sensor-trend.up{color:var(--primary)}
.sensor-trend.down{color:var(--danger)}
.chart-card{margin-bottom:28px}
.chart-card canvas{max-height:220px}
.experiment-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="brand-logo">🧠</div>
    <span>SmartEdge <span class="gradient-text">ML</span></span>
  </a>
  <div class="navbar-nav">
    <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
    <a href="ml/playground.php" class="nav-link">🎛️ ML Playground</a>
    <a href="pipeline/index.php" class="nav-link">🔗 Pipeline</a>
    <a href="datasets/index.php" class="nav-link">📁 Datasets</a>
    <?php if($userRole==='admin'): ?>
    <a href="admin/index.php" class="nav-link" style="color:var(--warning)">🛡️ Admin</a>
    <?php endif; ?>
  </div>
  <div class="navbar-right">
    <div style="position:relative">
      <button id="notifBtn" style="background:none;border:none;cursor:pointer;color:var(--text-secondary);font-size:20px;position:relative">
        🔔 <span id="notifCount" class="badge badge-danger" style="position:absolute;top:-4px;right:-4px;font-size:.6rem;padding:2px 5px;display:none">0</span>
      </button>
    </div>
    <div class="avatar" title="<?= htmlspecialchars($userName) ?>"><?= $initial ?></div>
    <div style="display:flex;flex-direction:column;gap:2px">
      <span style="font-size:.875rem;font-weight:600"><?= htmlspecialchars($userName) ?></span>
      <span class="badge badge-<?= $userRole==='admin'?'warning':'info' ?>"><?= ucfirst($userRole) ?></span>
    </div>
    <a href="auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
  </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Navigation</div>
    <a href="dashboard.php" class="sidebar-link active"><span class="icon">📊</span> Dashboard</a>
    <a href="ml/playground.php" class="sidebar-link"><span class="icon">🎛️</span> ML Playground</a>
    <a href="ml/experiments.php" class="sidebar-link"><span class="icon">🔬</span> Experiments</a>
    <a href="pipeline/index.php" class="sidebar-link"><span class="icon">🔗</span> Pipeline Visualizer</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">IoT & Data</div>
    <a href="mqtt/devices.php" class="sidebar-link"><span class="icon">📡</span> My Devices</a>
    <a href="datasets/index.php" class="sidebar-link"><span class="icon">📁</span> Datasets</a>
    <a href="datasets/replay.php" class="sidebar-link"><span class="icon">🔁</span> Replay Mode</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Learning</div>
    <a href="gamification/levels.php" class="sidebar-link"><span class="icon">🎮</span> Game Levels</a>
    <a href="ml/explainable.php" class="sidebar-link"><span class="icon">🔬</span> Explainable AI</a>
    <a href="ml/edge-cloud.php" class="sidebar-link"><span class="icon">☁️</span> Edge vs Cloud</a>
  </div>
  <?php if($userRole==='admin'): ?>
  <div class="sidebar-section">
    <div class="sidebar-label">Admin</div>
    <a href="admin/index.php" class="sidebar-link" style="color:var(--warning)"><span class="icon">🛡️</span> Admin Panel</a>
    <a href="admin/devices.php" class="sidebar-link"><span class="icon">📱</span> Manage Devices</a>
    <a href="admin/users.php" class="sidebar-link"><span class="icon">👥</span> Manage Users</a>
  </div>
  <?php endif; ?>
  <!-- XP Widget -->
  <div class="card" style="margin-top:auto;padding:16px">
    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px">Your Progress</div>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
      <span style="font-weight:700" id="sideXP">0 XP</span>
      <span class="badge badge-success" id="sideLevel">Lv.1</span>
    </div>
    <div class="progress"><div class="progress-bar" id="sideXPBar" style="width:0%"></div></div>
  </div>
</aside>

<!-- Main Content -->
<main class="main-content">
  <div class="dashboard-header flex between">
    <div>
      <h1>Welcome back, <span class="gradient-text"><?= htmlspecialchars($userName) ?>!</span> 👋</h1>
      <p style="color:var(--text-secondary)">Here's what's happening in your ML workspace today.</p>
    </div>
    <div class="flex gap-4">
      <span class="flex gap-4" id="deviceStatus">
        <span class="offline-dot"></span>
        <span style="font-size:.875rem;color:var(--text-muted)">No devices online</span>
      </span>
      <a href="ml/playground.php" class="btn btn-primary">🚀 New Experiment</a>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="widget-row stagger">
    <div class="card card-stat">
      <div class="stat-icon green">🔬</div>
      <div>
        <div class="stat-value" id="statExp">—</div>
        <div class="stat-label">Experiments</div>
      </div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon blue">📁</div>
      <div>
        <div class="stat-value" id="statDS">—</div>
        <div class="stat-label">Datasets</div>
      </div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon purple">⭐</div>
      <div>
        <div class="stat-value" id="statXP">—</div>
        <div class="stat-label">XP Points</div>
      </div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon pink">🏆</div>
      <div>
        <div class="stat-value" id="statLevel">—</div>
        <div class="stat-label">Level</div>
      </div>
    </div>
  </div>

  <!-- Live Sensor Readings -->
  <h3 style="margin-bottom:16px">Live Sensor Readings <span class="online-dot" style="margin-left:8px"></span></h3>
  <div class="sensor-live stagger">
    <div class="card sensor-card">
      <div class="flex between">
        <span style="font-size:.8rem;color:var(--text-muted);font-weight:600">💧 WATER LEVEL</span>
        <span class="badge badge-success">Live</span>
      </div>
      <div class="sensor-reading gradient-text" id="waterVal">—</div>
      <div class="sensor-trend up" id="waterTrend">Loading…</div>
      <div style="margin-top:12px">
        <div class="progress"><div class="progress-bar" id="waterBar" style="width:0%"></div></div>
      </div>
    </div>
    <div class="card sensor-card">
      <div class="flex between">
        <span style="font-size:.8rem;color:var(--text-muted);font-weight:600">🔊 MIC INPUT</span>
        <span class="badge badge-info">Live</span>
      </div>
      <div class="sensor-reading" style="color:var(--secondary)" id="micVal">—</div>
      <div class="sensor-trend" id="micTrend">Loading…</div>
      <canvas id="micMini" height="40"></canvas>
    </div>
    <div class="card sensor-card">
      <div class="flex between">
        <span style="font-size:.8rem;color:var(--text-muted);font-weight:600">📡 DEVICE STATUS</span>
        <span class="badge badge-warning" id="deviceBadge">Offline</span>
      </div>
      <div class="sensor-reading" style="font-size:1.8rem;color:var(--warning)" id="deviceName">ESP32_001</div>
      <div style="display:flex;flex-direction:column;gap:6px;margin-top:12px">
        <div class="flex between"><span style="font-size:.8rem;color:var(--text-muted)">Last Seen</span><span style="font-size:.8rem" id="lastSeen">—</span></div>
        <div class="flex between"><span style="font-size:.8rem;color:var(--text-muted)">Firmware</span><span style="font-size:.8rem">v1.0.0</span></div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="experiment-row">
    <div class="card chart-card">
      <div class="flex between" style="margin-bottom:16px">
        <h3 style="font-size:1rem">ML Training History</h3>
        <select class="form-control" style="width:auto;padding:6px 12px;font-size:.8rem" onchange="loadTrainingChart(this.value)">
          <option value="">Select experiment</option>
        </select>
      </div>
      <canvas id="trainingChart"></canvas>
      <div id="trainingEmpty" style="text-align:center;padding:40px;color:var(--text-muted)">
        <div style="font-size:40px">📊</div>
        <p>Run an experiment to see training curves</p>
      </div>
    </div>
    <div class="card chart-card">
      <div style="margin-bottom:16px"><h3 style="font-size:1rem">Recent Experiments</h3></div>
      <div id="recentExps">
        <div style="text-align:center;padding:40px;color:var(--text-muted)">
          <div style="font-size:40px">🔬</div>
          <p>No experiments yet. <a href="ml/playground.php" style="color:var(--primary)">Start one!</a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Gamification Progress -->
  <div class="card" style="margin-bottom:28px">
    <div class="flex between" style="margin-bottom:24px">
      <h3 style="font-size:1rem">🎮 Learning Progress</h3>
      <a href="gamification/levels.php" class="btn btn-outline btn-sm">View All Levels</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px" id="progressLevels">
      <div class="card" style="padding:20px;text-align:center">
        <div style="font-size:32px;margin-bottom:8px">🔊</div>
        <h4 style="font-size:.9rem">Level 1: Sound → Fan</h4>
        <div style="margin:12px 0"><div class="progress"><div class="progress-bar level1Bar" style="width:0%"></div></div></div>
        <span class="badge badge-info level1Status">Not Started</span>
      </div>
      <div class="card" style="padding:20px;text-align:center">
        <div style="font-size:32px;margin-bottom:8px">💧</div>
        <h4 style="font-size:.9rem">Level 2: Water Automation</h4>
        <div style="margin:12px 0"><div class="progress"><div class="progress-bar level2Bar" style="width:0%"></div></div></div>
        <span class="badge badge-info level2Status">Not Started</span>
      </div>
      <div class="card" style="padding:20px;text-align:center">
        <div style="font-size:32px;margin-bottom:8px">🤖</div>
        <h4 style="font-size:.9rem">Level 3: Predictive ML</h4>
        <div style="margin:12px 0"><div class="progress"><div class="progress-bar level3Bar" style="width:0%"></div></div></div>
        <span class="badge badge-info level3Status">Not Started</span>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="card">
    <h3 style="font-size:1rem;margin-bottom:20px">⚡ Quick Hardware Control</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <button class="btn btn-primary btn-sm" onclick="sendHWCmd('fan','ON')">💨 Fan ON</button>
      <button class="btn btn-danger btn-sm" onclick="sendHWCmd('fan','OFF')">💨 Fan OFF</button>
      <button class="btn btn-primary btn-sm" onclick="sendHWCmd('pump','ON')">🚿 Pump ON</button>
      <button class="btn btn-danger btn-sm" onclick="sendHWCmd('pump','OFF')">🚿 Pump OFF</button>
      <button class="btn btn-secondary btn-sm" onclick="sendServo(90)">🔧 Servo 90°</button>
      <button class="btn btn-secondary btn-sm" onclick="sendServo(180)">🔧 Servo 180°</button>
      <button class="btn btn-outline btn-sm" onclick="sendHWCmd('relay','TOGGLE')">🔌 Toggle Relay</button>
    </div>
    <div id="hwCmdResult" style="margin-top:12px"></div>
  </div>
</main>

<!-- Chatbot FAB -->
<button class="chatbot-fab" id="chatFab" onclick="toggleChatbot()">🤖</button>

<!-- Chatbot Panel -->
<div class="chatbot-panel" id="chatPanel">
  <div class="chat-header">
    <span style="font-size:24px">🤖</span>
    <div>
      <div style="font-weight:700">NeuroBot</div>
      <div style="font-size:.75rem;font-weight:500">ML Tutor Assistant</div>
    </div>
    <button onclick="toggleChatbot()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#000;font-size:18px">✕</button>
  </div>
  <div class="chat-messages" id="chatMessages">
    <div class="chat-msg assistant">
      👋 Hi! I'm <strong>NeuroBot</strong>, your ML tutor!<br><br>
      Ask me about learning rate, overfitting, gradient descent, MQTT, or any ML concept you're curious about!
    </div>
  </div>
  <div class="chat-input-wrap">
    <input type="text" class="form-control" id="chatInput" placeholder="Ask me about ML…" style="flex:1" onkeydown="if(event.key==='Enter')sendChat()">
    <button class="btn btn-primary btn-sm btn-icon" onclick="sendChat()">➤</button>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
const API = 'api/index.php';

// ── Dashboard Stats ─────────────────────────────────────────
async function loadDashboard() {
  try {
    const r = await fetch(`${API}?action=dashboard_stats`);
    const d = await r.json();
    document.getElementById('statExp').textContent   = d.experiments ?? 0;
    document.getElementById('statDS').textContent    = d.datasets ?? 0;
    document.getElementById('statXP').textContent    = (d.xp ?? 0).toLocaleString();
    document.getElementById('statLevel').textContent = `Lv.${d.level ?? 1}`;
    document.getElementById('sideXP').textContent    = `${(d.xp ?? 0).toLocaleString()} XP`;
    document.getElementById('sideLevel').textContent = `Lv.${d.level ?? 1}`;
    document.getElementById('sideXPBar').style.width = `${Math.min(100, (d.xp ?? 0) % 100)}%`;

    if (d.latest_sensor) {
      updateSensorUI(d.latest_sensor);
    }

    if (d.recent_experiments && d.recent_experiments.length > 0) {
      const html = d.recent_experiments.map(e => `
        <div class="flex between" style="padding:10px 0;border-bottom:1px solid var(--border)">
          <div>
            <div style="font-weight:600;font-size:.875rem">${e.title}</div>
            <div style="font-size:.75rem;color:var(--text-muted)">${e.status}</div>
          </div>
          ${e.accuracy ? `<span class="badge badge-success">${(e.accuracy*100).toFixed(1)}% acc</span>` : `<span class="badge badge-warning">${e.status}</span>`}
        </div>
      `).join('');
      document.getElementById('recentExps').innerHTML = html;
    }

    if (d.progress) {
      const p = d.progress;
      document.querySelector('.level1Bar').style.width = p.level_1_done ? '100%' : '0%';
      document.querySelector('.level2Bar').style.width = p.level_2_done ? '100%' : p.level_1_done ? '20%' : '0%';
      document.querySelector('.level3Bar').style.width = p.level_3_done ? '100%' : p.level_2_done ? '20%' : '0%';
      document.querySelector('.level1Status').textContent = p.level_1_done ? '✅ Completed' : 'Not Started';
      document.querySelector('.level2Status').textContent = p.level_2_done ? '✅ Completed' : p.level_1_done ? '🔓 Unlocked' : '🔒 Locked';
      document.querySelector('.level3Status').textContent = p.level_3_done ? '✅ Completed' : p.level_2_done ? '🔓 Unlocked' : '🔒 Locked';
    }
  } catch(e) { console.warn('Dashboard load error:', e); }
}

// ── Sensor Simulation (when no real device) ──────────────────
let waterHist = [], micHist = [];
let micChart = null;

function simulateSensorData() {
  const water = Math.round(50 + 30 * Math.sin(Date.now()/5000) + Math.random()*10);
  const mic   = Math.round(40 + 20 * Math.random());
  document.getElementById('waterVal').textContent = `${water}%`;
  document.getElementById('waterBar').style.width = `${water}%`;
  document.getElementById('waterTrend').textContent = water > 60 ? '⬆ Rising' : '⬇ Falling';
  document.getElementById('micVal').textContent = `${mic} dB`;
  micHist.push(mic); if (micHist.length > 20) micHist.shift();
  if (!micChart) {
    const ctx = document.getElementById('micMini').getContext('2d');
    micChart = new Chart(ctx, {
      type:'line', data:{labels:Array(20).fill(''),datasets:[{data:micHist,borderColor:'#a78bfa',fill:true,backgroundColor:'rgba(167,139,250,.1)',tension:.4,pointRadius:0,borderWidth:2}]},
      options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{display:false},y:{display:false}},animation:{duration:0}}
    });
  } else { micChart.data.datasets[0].data=[...micHist]; micChart.update('none'); }
}

function updateSensorUI(s) {
  if (s.sensor_type==='water_level') {
    document.getElementById('waterVal').textContent=`${s.value}%`;
    document.getElementById('waterBar').style.width=`${s.value}%`;
  }
}

// ── Hardware Commands ─────────────────────────────────────────
async function sendHWCmd(actuator, command) {
  const r = await fetch(`${API}?action=send_command`, {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({device_id:'ESP32_001', actuator, command})
  });
  const d = await r.json();
  showToast(d.success ? `✅ ${d.message}` : `❌ ${d.error}`, d.success ? 'success' : 'error');
}
async function sendServo(angle) { sendHWCmd('servo', `angle:${angle}`); }

// ── Chatbot ───────────────────────────────────────────────────
function toggleChatbot() {
  const panel = document.getElementById('chatPanel');
  const fab   = document.getElementById('chatFab');
  const open  = panel.classList.contains('open');
  panel.classList.toggle('open', !open);
  fab.classList.toggle('hidden', !open);
  if (!open) document.getElementById('chatInput').focus();
}

async function sendChat() {
  const input = document.getElementById('chatInput');
  const msg   = input.value.trim();
  if (!msg) return;
  addChatMsg(msg, 'user');
  input.value = '';
  addChatMsg('<span class="spinner" style="width:20px;height:20px;border-width:2px"></span>', 'assistant', 'typing-msg');
  try {
    const r = await fetch(`${API}?action=chatbot`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({message: msg, context: {learning_rate:0.01, epochs:100, threshold:0.5}})
    });
    const d = await r.json();
    document.querySelector('.typing-msg')?.remove();
    addChatMsg(d.response || 'Sorry, I had trouble responding.', 'assistant');
  } catch { document.querySelector('.typing-msg')?.remove(); addChatMsg('Network error. Try again.','assistant'); }
}

function addChatMsg(text, role, cls='') {
  const el = document.createElement('div');
  el.className = `chat-msg ${role} ${cls}`;
  el.innerHTML = text.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
  const msgs = document.getElementById('chatMessages');
  msgs.appendChild(el);
  msgs.scrollTop = msgs.scrollHeight;
}

// ── Training Chart ────────────────────────────────────────────
let trainingChart = null;
async function loadTrainingChart(expId) {
  if (!expId) return;
  const r = await fetch(`${API}?action=training_steps&experiment_id=${expId}`);
  const d = await r.json();
  if (!d.data || !d.data.length) return;
  document.getElementById('trainingEmpty').style.display='none';

  const ctx = document.getElementById('trainingChart').getContext('2d');
  if (trainingChart) trainingChart.destroy();
  trainingChart = new Chart(ctx, {
    type:'line',
    data:{
      labels: d.data.map(s=>s.epoch),
      datasets:[
        {label:'Train Loss',data:d.data.map(s=>s.loss),borderColor:'#ef4444',tension:.4,pointRadius:0},
        {label:'Val Loss',data:d.data.map(s=>s.val_loss),borderColor:'#fbbf24',borderDash:[5,5],tension:.4,pointRadius:0},
        {label:'Accuracy',data:d.data.map(s=>s.accuracy),borderColor:'#06d6a0',tension:.4,pointRadius:0,yAxisID:'y2'},
      ]
    },
    options:{
      responsive:true,
      plugins:{legend:{labels:{color:'#9ca3af',font:{size:11}}}},
      scales:{
        x:{ticks:{color:'#6b7280'},grid:{color:'rgba(255,255,255,.04)'}},
        y:{ticks:{color:'#6b7280'},grid:{color:'rgba(255,255,255,.04)'}},
        y2:{position:'right',ticks:{color:'#06d6a0'},grid:{display:false}}
      }
    }
  });
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg, type='info') {
  const icons = {success:'✅',error:'❌',info:'ℹ️',warning:'⚠️'};
  const colors = {success:'var(--primary)',error:'var(--danger)',info:'var(--info)',warning:'var(--warning)'};
  const t = document.createElement('div');
  t.className='toast';
  t.innerHTML=`<span style="color:${colors[type]}">${icons[type]}</span>${msg}`;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(()=>t.remove(),4000);
}

// ── Notifications ──────────────────────────────────────────────
async function loadNotifs() {
  try {
    const r = await fetch(`${API}?action=notifications`);
    const d = await r.json();
    const count = document.getElementById('notifCount');
    if (d.data?.length) { count.style.display='flex'; count.textContent=d.data.length; }
  } catch {}
}

// ── Init ─────────────────────────────────────────────────────
loadDashboard();
loadNotifs();
setInterval(simulateSensorData, 2000);
setInterval(loadDashboard, 30000);
simulateSensorData();
</script>
</body>
</html>
