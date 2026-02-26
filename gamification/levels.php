<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Game Levels — SmartEdge</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.level-card-big{
  background:var(--bg-card);border:1px solid var(--border);
  border-radius:var(--radius-lg);padding:36px;margin-bottom:24px;
  position:relative;overflow:hidden;transition:var(--transition);
}
.level-card-big:hover{border-color:var(--border-glow);box-shadow:var(--shadow-glow)}
.level-num{
  position:absolute;top:24px;right:28px;
  font-size:5rem;font-weight:900;opacity:.06;
  font-family:'JetBrains Mono',monospace;color:var(--primary);
  line-height:1;
}
.level-badge-big{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--grad-primary);color:#000;
  font-size:.75rem;font-weight:700;padding:4px 14px;border-radius:20px;
  margin-bottom:16px;
}
.objective-list{display:flex;flex-direction:column;gap:8px;margin:16px 0}
.obj-item{
  display:flex;align-items:center;gap:10px;
  font-size:.875rem;color:var(--text-secondary);
}
.obj-check{width:20px;height:20px;border-radius:50%;background:rgba(6,214,160,.15);border:1px solid rgba(6,214,160,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.65rem}
.obj-check.done{background:var(--primary);border-color:var(--primary);color:#000}
.badge-earned{
  background:linear-gradient(135deg,rgba(251,191,36,.15),rgba(251,191,36,.05));
  border:1px solid rgba(251,191,36,.4);border-radius:var(--radius);
  padding:16px;text-align:center;
}
.xp-rewards{display:flex;gap:16px;flex-wrap:wrap;margin-top:16px}
</style>
</head>
<body>
<nav class="navbar">
  <a href="../index.php" class="navbar-brand"><div class="brand-logo">🧠</div><span>SmartEdge <span class="gradient-text">ML</span></span></a>
  <div class="navbar-nav">
    <a href="../dashboard.php" class="nav-link">📊 Dashboard</a>
    <a href="../ml/playground.php" class="nav-link">🎛️ ML Playground</a>
    <a href="levels.php" class="nav-link active">🎮 Levels</a>
  </div>
</nav>

<div style="margin-top:72px;padding:32px;max-width:1000px;margin-left:auto;margin-right:auto">
  <div class="flex between" style="margin-bottom:32px">
    <div>
      <h1 style="font-size:1.75rem">🎮 Gamified Learning Levels</h1>
      <p style="color:var(--text-secondary)">Complete IoT + ML challenges to earn XP, unlock badges, and master real-world automation</p>
    </div>
    <div class="card" style="padding:16px 24px;text-align:center">
      <div id="totalXP" style="font-size:2rem;font-weight:900;color:var(--primary)">0</div>
      <div style="font-size:.75rem;color:var(--text-muted)">TOTAL XP</div>
    </div>
  </div>

  <!-- Level 1 -->
  <div class="level-card-big fade-in" id="level1">
    <div class="level-num">1</div>
    <div class="level-badge-big">🔊 Level 1</div>
    <h2 style="font-size:1.4rem;margin-bottom:8px">Sound Detection → Fan Control</h2>
    <p style="color:var(--text-secondary);margin-bottom:16px">Train a binary classifier on microphone data to distinguish loud vs quiet environments. Use ML predictions to automatically control a relay fan.</p>

    <div class="objective-list" id="obj1">
      <div class="obj-item"><div class="obj-check done">✓</div><span>Connect ESP32 mic sensor to MQTT broker</span></div>
      <div class="obj-item"><div class="obj-check" id="o1b">○</div><span>Collect 50+ microphone data points (MQTT Stream)</span></div>
      <div class="obj-item"><div class="obj-check" id="o1c">○</div><span>Train ML model with collected data (LR ≤ 0.05, Epochs ≥ 50)</span></div>
      <div class="obj-item"><div class="obj-check" id="o1d">○</div><span>Achieve ≥ 80% classification accuracy</span></div>
      <div class="obj-item"><div class="obj-check" id="o1e">○</div><span>Successfully control fan via ML prediction</span></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin:20px 0">
      <div class="card" style="padding:16px;text-align:center">
        <div style="font-size:28px;margin-bottom:6px">🔊</div>
        <div style="font-size:.75rem;color:var(--text-muted)">Sensor</div>
        <div style="font-weight:700;font-size:.875rem">Microphone</div>
        <div id="micLive" style="color:var(--primary);font-family:'JetBrains Mono',monospace;font-size:1.2rem;margin-top:6px">—</div>
      </div>
      <div class="card" style="padding:16px;text-align:center">
        <div style="font-size:28px;margin-bottom:6px">🤖</div>
        <div style="font-size:.75rem;color:var(--text-muted)">ML Output</div>
        <div style="font-weight:700;font-size:.875rem">Binary Class</div>
        <div id="mlPredL1" style="font-family:'JetBrains Mono',monospace;font-size:1.2rem;margin-top:6px">—</div>
      </div>
      <div class="card" style="padding:16px;text-align:center">
        <div style="font-size:28px;margin-bottom:6px">💨</div>
        <div style="font-size:.75rem;color:var(--text-muted)">Actuator</div>
        <div style="font-weight:700;font-size:.875rem">Relay Fan</div>
        <div id="fanState" class="badge badge-warning" style="margin-top:6px">OFF</div>
      </div>
    </div>

    <div class="xp-rewards">
      <div class="badge-earned"><div style="font-size:28px">🏆</div><div style="font-weight:700;margin-top:4px">+100 XP</div></div>
      <div class="badge-earned"><div style="font-size:28px">🎖️</div><div style="font-weight:700;margin-top:4px">Sound Ranger</div></div>
    </div>
    <div style="margin-top:16px;display:flex;gap:12px">
      <a href="../ml/playground.php?dataset=iot_sound" class="btn btn-primary">🚀 Start Level 1 Experiment</a>
      <button class="btn btn-outline" onclick="simulateLevel1()">⚡ Demo Simulation</button>
    </div>
  </div>

  <!-- Level 2 -->
  <div class="level-card-big fade-in" id="level2" style="animation-delay:.1s">
    <div class="level-num">2</div>
    <div class="level-badge-big" style="background:linear-gradient(135deg,#38bdf8,#a78bfa)">💧 Level 2</div>
    <h2 style="font-size:1.4rem;margin-bottom:8px">Water Level Automation</h2>
    <p style="color:var(--text-secondary);margin-bottom:16px">Use threshold-based ML on water level sensor data to predict water shortage events. Automatically activate water pump before reservoir runs dry.</p>

    <div class="objective-list">
      <div class="obj-item"><div class="obj-check done">✓</div><span>Complete Level 1 ✅</span></div>
      <div class="obj-item"><div class="obj-check" id="o2b">○</div><span>Stream water level data from ESP32 sensor</span></div>
      <div class="obj-item"><div class="obj-check" id="o2c">○</div><span>Build dataset with 100+ labeled water level readings</span></div>
      <div class="obj-item"><div class="obj-check" id="o2d">○</div><span>Train regression/classification model</span></div>
      <div class="obj-item"><div class="obj-check" id="o2e">○</div><span>Automate pump based on ML prediction (≥ 85% accuracy)</span></div>
    </div>

    <!-- Water Level Display -->
    <div class="card" style="padding:16px;margin:16px 0">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <span style="font-weight:600">💧 Water Level Gauge</span>
        <span id="waterPct" style="color:var(--primary);font-weight:700;font-family:'JetBrains Mono',monospace">72%</span>
      </div>
      <div class="progress" style="height:16px"><div class="progress-bar" id="waterGauge" style="width:72%"></div></div>
      <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:.75rem;color:var(--text-muted)">
        <span>Critical (0%)</span><span>Threshold (40%)</span><span>Safe (100%)</span>
      </div>
      <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
        <span id="pumpDecision" class="badge badge-warning">🚿 Pump: OFF</span>
        <span id="mlConfWater" class="badge badge-info">ML Confidence: —</span>
      </div>
    </div>

    <div class="xp-rewards">
      <div class="badge-earned"><div style="font-size:28px">🏆</div><div style="font-weight:700;margin-top:4px">+150 XP</div></div>
      <div class="badge-earned"><div style="font-size:28px">💧</div><div style="font-weight:700;margin-top:4px">Hydro Engineer</div></div>
    </div>
    <a href="../ml/playground.php?dataset=iot_water" class="btn btn-primary" style="margin-top:16px">🚀 Start Level 2</a>
  </div>

  <!-- Level 3 -->
  <div class="level-card-big fade-in" id="level3" style="animation-delay:.2s;border-color:rgba(167,139,250,.3)">
    <div class="level-num">3</div>
    <div class="level-badge-big" style="background:linear-gradient(135deg,#a78bfa,#f72585)">🤖 Level 3</div>
    <h2 style="font-size:1.4rem;margin-bottom:8px">Predictive ML Automation</h2>
    <p style="color:var(--text-secondary);margin-bottom:16px">The ultimate challenge! Combine ALL sensors (water + mic + temperature) into a multi-feature ML model. Servo angle represents confidence. Full pipeline execution!</p>

    <div class="objective-list">
      <div class="obj-item"><div class="obj-check done">✓</div><span>Complete Levels 1 & 2 ✅</span></div>
      <div class="obj-item"><div class="obj-check" id="o3b">○</div><span>Run full pipeline: ESP32 → MQTT → ML → Hardware</span></div>
      <div class="obj-item"><div class="obj-check" id="o3c">○</div><span>Train multi-feature ML model with all 3 sensor types</span></div>
      <div class="obj-item"><div class="obj-check" id="o3d">○</div><span>Servo angle correctly reflects prediction confidence</span></div>
      <div class="obj-item"><div class="obj-check" id="o3e">○</div><span>Achieve ≥ 90% accuracy on test set</span></div>
      <div class="obj-item"><div class="obj-check" id="o3f">○</div><span>Export dataset and complete replay session</span></div>
    </div>

    <!-- Servo Visualization -->
    <div class="card" style="padding:20px;margin:16px 0;text-align:center">
      <h4 style="margin-bottom:12px">🔧 Servo Confidence Indicator</h4>
      <canvas id="servoCanvas" width="200" height="120"></canvas>
      <div style="margin-top:8px;font-size:.875rem;color:var(--text-muted)">Servo angle = ML prediction confidence × 180°</div>
    </div>

    <div class="xp-rewards">
      <div class="badge-earned"><div style="font-size:28px">🏆</div><div style="font-weight:700;margin-top:4px">+250 XP</div></div>
      <div class="badge-earned"><div style="font-size:28px">🤖</div><div style="font-weight:700;margin-top:4px">ML Master</div></div>
      <div class="badge-earned"><div style="font-size:28px">⚡</div><div style="font-weight:700;margin-top:4px">Pipeline Pro</div></div>
    </div>
    <a href="../pipeline/index.php" class="btn btn-primary" style="margin-top:16px;background:linear-gradient(135deg,#a78bfa,#f72585)">🚀 Start Level 3</a>
  </div>
</div>

<script>
const API='../api/index.php';

// Servo Canvas
function drawServo(angle) {
  const cv=document.getElementById('servoCanvas');
  if(!cv)return;
  const ctx=cv.getContext('2d');
  const cx=cv.width/2,cy=cv.height-10,r=80;
  ctx.clearRect(0,0,cv.width,cv.height);

  // Arc background
  ctx.beginPath();ctx.arc(cx,cy,r,Math.PI,0);
  ctx.strokeStyle='rgba(255,255,255,.08)';ctx.lineWidth=12;ctx.stroke();

  // Colored arc
  const grad=ctx.createLinearGradient(cx-r,cy,cx+r,cy);
  grad.addColorStop(0,'#ef4444');grad.addColorStop(0.5,'#fbbf24');grad.addColorStop(1,'#06d6a0');
  ctx.beginPath();
  const startRad=Math.PI;
  const endRad=Math.PI+(angle/180)*Math.PI;
  ctx.arc(cx,cy,r,startRad,endRad);
  ctx.strokeStyle=grad;ctx.lineWidth=12;ctx.stroke();

  // Needle
  const rad=Math.PI+(angle/180)*Math.PI;
  const nx=cx+Math.cos(rad)*(r-10);
  const ny=cy+Math.sin(rad)*(r-10);
  ctx.beginPath();ctx.moveTo(cx,cy);ctx.lineTo(nx,ny);
  ctx.strokeStyle='#06d6a0';ctx.lineWidth=3;ctx.stroke();
  ctx.beginPath();ctx.arc(cx,cy,6,0,Math.PI*2);ctx.fillStyle='#06d6a0';ctx.fill();

  // Angle text
  ctx.fillStyle='var(--text-primary,#f0f0ff)';ctx.font='bold 16px JetBrains Mono';
  ctx.textAlign='center';ctx.fillText(angle+'°',cx,cy-20);
}

// Dashboard data
async function loadProgress() {
  try {
    const r=await fetch(`${API}?action=dashboard_stats`);
    const d=await r.json();
    document.getElementById('totalXP').textContent=(d.xp??0).toLocaleString();
  } catch {}
}

// Level 1 simulation
function simulateLevel1() {
  let i=0;
  const interval=setInterval(()=>{
    const mic=Math.round(30+40*Math.random());
    const isLoud=mic>55;
    document.getElementById('micLive').textContent=mic+'dB';
    document.getElementById('mlPredL1').textContent=isLoud?'Loud (1)':'Quiet (0)';
    document.getElementById('mlPredL1').style.color=isLoud?'var(--primary)':'var(--danger)';
    const fanEl=document.getElementById('fanState');
    fanEl.textContent=isLoud?'ON':'OFF';
    fanEl.className='badge '+(isLoud?'badge-success':'badge-warning');
    drawServo(Math.round((mic/100)*180));
    i++;if(i>20)clearInterval(interval);
  },500);
}

// Water level simulation
setInterval(()=>{
  const water=Math.round(35+50*Math.sin(Date.now()/6000)+Math.random()*10);
  const pumpOn=water<40;
  const conf=(pumpOn?(100-water)/100:water/100);
  document.getElementById('waterPct').textContent=water+'%';
  document.getElementById('waterGauge').style.width=water+'%';
  document.getElementById('pumpDecision').textContent='🚿 Pump: '+(pumpOn?'ON':'OFF');
  document.getElementById('pumpDecision').className='badge '+(pumpOn?'badge-success':'badge-warning');
  document.getElementById('mlConfWater').textContent='ML Confidence: '+(conf*100).toFixed(0)+'%';
  drawServo(Math.round(conf*180));
},1200);

loadProgress();
drawServo(90);
</script>
</body>
</html>
