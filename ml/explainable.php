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
<title>Explainable AI — SmartEdge</title>
<link rel="stylesheet" href="../assets/css/main.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.xai-bar{height:28px;border-radius:4px;transition:width .8s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;display:flex;align-items:center;padding:0 10px}
.xai-label{font-size:.75rem;font-weight:700;color:#000;white-space:nowrap}
.feature-row{margin-bottom:12px}
.feature-name{display:flex;justify-content:space-between;margin-bottom:4px;font-size:.85rem;font-weight:500}
.heat-cell{border-radius:4px;padding:6px 10px;font-size:.7rem;font-weight:600;text-align:center;transition:background .5s}
.pred-meter{width:160px;height:160px;position:relative}
</style>
</head>
<body>
<nav class="navbar">
  <a href="../index.php" class="navbar-brand"><div class="brand-logo">🧠</div><span>SmartEdge <span class="gradient-text">ML</span></span></a>
  <div class="navbar-nav">
    <a href="../dashboard.php" class="nav-link">📊 Dashboard</a>
    <a href="../ml/playground.php" class="nav-link">🎛️ ML Playground</a>
    <a href="explainable.php" class="nav-link active">🔬 XAI</a>
  </div>
</nav>

<div style="margin-top:72px;padding:28px">
  <h1 style="font-size:1.5rem;margin-bottom:8px">🔬 Explainable AI Mode</h1>
  <p style="color:var(--text-secondary);margin-bottom:28px">Understand <em>why</em> the ML model made each prediction. Feature importance and SHAP-like explanations.</p>

  <div style="display:grid;grid-template-columns:320px 1fr;gap:24px">
    <!-- Input Panel -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <h3 style="font-size:.875rem;margin-bottom:16px">📡 Live Sensor Input</h3>
        <div class="slider-group">
          <div class="slider-header"><span class="slider-label">💧 Water Level</span><span class="slider-value" id="xaiWater">72%</span></div>
          <input type="range" id="slWater" min="0" max="100" value="72" oninput="updateXAI()">
        </div>
        <div class="slider-group">
          <div class="slider-header"><span class="slider-label">🔊 Mic Input</span><span class="slider-value" id="xaiMic">45 dB</span></div>
          <input type="range" id="slMic" min="20" max="120" value="45" oninput="updateXAI()">
        </div>
        <div class="slider-group">
          <div class="slider-header"><span class="slider-label">🌡️ Temperature</span><span class="slider-value" id="xaiTemp">28°C</span></div>
          <input type="range" id="slTemp" min="0" max="60" value="28" oninput="updateXAI()">
        </div>
        <div class="slider-group">
          <div class="slider-header"><span class="slider-label">⚡ Voltage</span><span class="slider-value" id="xaiVolt">3.3V</span></div>
          <input type="range" id="slVolt" min="1" max="5" step="0.1" value="3.3" oninput="updateXAI()">
        </div>
        <button class="btn btn-primary" style="width:100%" onclick="startLiveXAI()">⚡ Live Mode</button>
      </div>

      <div class="card">
        <h3 style="font-size:.875rem;margin-bottom:16px">🤖 Model Selection</h3>
        <select class="form-control" id="xaiModel" onchange="updateXAI()">
          <option value="water">Water Pump Controller</option>
          <option value="fan">Fan Speed Controller</option>
          <option value="multi">Multi-Sensor Predictor</option>
        </select>
        <div style="margin-top:12px;font-size:.8rem;color:var(--text-muted)">Each model has different feature weights learned from sensor data.</div>
      </div>
    </div>

    <!-- XAI Output -->
    <div style="display:flex;flex-direction:column;gap:20px">
      <!-- Prediction Result -->
      <div class="card" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;align-items:center">
        <div style="text-align:center">
          <div style="font-size:3.5rem;font-weight:900;color:var(--primary)" id="xaiProbPct">—</div>
          <div style="font-size:.8rem;color:var(--text-muted)">Confidence Score</div>
          <div style="margin-top:8px"><div class="progress"><div class="progress-bar" id="xaiConfBar" style="width:0%"></div></div></div>
        </div>
        <div style="text-align:center">
          <div style="font-size:2.5rem;margin-bottom:8px" id="xaiActionIcon">🤔</div>
          <div style="font-size:1.2rem;font-weight:800" id="xaiAction">—</div>
          <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px" id="xaiClass">—</div>
        </div>
        <div style="text-align:center">
          <canvas id="xaiServo" width="120" height="80"></canvas>
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px">Servo Angle = Confidence</div>
        </div>
      </div>

      <!-- Feature Importance (SHAP-like) -->
      <div class="card">
        <div class="flex between" style="margin-bottom:16px">
          <h3 style="font-size:.875rem">📊 Feature Importance (SHAP Values)</h3>
          <span class="badge badge-success">Live</span>
        </div>
        <div id="xaiFeatures">
          <div class="feature-row"><div class="feature-name"><span>💧 Water Level</span><span id="sv0">0.000</span></div><div class="progress"><div class="progress-bar" id="fb0" style="width:0%"></div></div></div>
          <div class="feature-row"><div class="feature-name"><span>🔊 Mic Input</span><span id="sv1">0.000</span></div><div class="progress" style="height:8px"><div class="progress-bar" id="fb1" style="width:0%;background:var(--secondary)"></div></div></div>
          <div class="feature-row"><div class="feature-name"><span>🌡️ Temperature</span><span id="sv2">0.000</span></div><div class="progress" style="height:8px"><div class="progress-bar" id="fb2" style="width:0%;background:var(--info)"></div></div></div>
          <div class="feature-row"><div class="feature-name"><span>⚡ Voltage</span><span id="sv3">0.000</span></div><div class="progress" style="height:8px"><div class="progress-bar" id="fb3" style="width:0%;background:var(--warning)"></div></div></div>
          <div class="feature-row"><div class="feature-name"><span>🔧 Bias</span><span id="sv4">0.000</span></div><div class="progress" style="height:8px"><div class="progress-bar" id="fb4" style="width:0%;background:var(--accent)"></div></div></div>
        </div>
      </div>

      <!-- Heat Map / Sensor Influence -->
      <div class="card">
        <h3 style="font-size:.875rem;margin-bottom:16px">🌡️ Sensor Influence Heatmap</h3>
        <div style="display:grid;grid-template-columns:repeat(10,1fr);gap:4px" id="heatmap"></div>
        <div style="margin-top:8px;display:flex;gap:16px;font-size:.75rem;color:var(--text-muted);align-items:center">
          <div style="width:16px;height:16px;border-radius:3px;background:rgba(239,68,68,.8)"></div><span>Low Influence</span>
          <div style="width:16px;height:16px;border-radius:3px;background:rgba(251,191,36,.8)"></div><span>Medium</span>
          <div style="width:16px;height:16px;border-radius:3px;background:rgba(6,214,160,.8)"></div><span>High Influence</span>
        </div>
      </div>

      <!-- Explanation Text -->
      <div class="card" style="background:rgba(6,214,160,.04);border-color:rgba(6,214,160,.2)">
        <h3 style="font-size:.875rem;margin-bottom:12px">💬 AI Explanation</h3>
        <p id="xaiExplanation" style="color:var(--text-secondary);font-size:.875rem;line-height:1.7">
          Adjust the sensor sliders to see how each feature influences the ML model's decision…
        </p>
      </div>
    </div>
  </div>
</div>

<script>
const modelWeights = {
  water: [0.06, -0.010, -0.005, 0.01, -3.0],   // [water, mic, temp, volt, bias]
  fan:   [-0.005, 0.08, 0.002, -0.01, -3.5],
  multi: [0.03, 0.04, 0.01, 0.005, -2.5],
};

const featureNames = ['Water Level', 'Mic Input', 'Temperature', 'Voltage'];
let liveInterval = null;

function sigmoid(z) { return 1 / (1 + Math.exp(-z)); }

function updateXAI() {
  const w    = parseFloat(document.getElementById('slWater').value);
  const m    = parseFloat(document.getElementById('slMic').value);
  const t    = parseFloat(document.getElementById('slTemp').value);
  const v    = parseFloat(document.getElementById('slVolt').value);
  const model= document.getElementById('xaiModel').value;
  const wts  = modelWeights[model];

  // Labels
  document.getElementById('xaiWater').textContent = w + '%';
  document.getElementById('xaiMic').textContent   = m + ' dB';
  document.getElementById('xaiTemp').textContent  = t + '°C';
  document.getElementById('xaiVolt').textContent  = v + 'V';

  // Normalize features (0-1)
  const nw = w/100, nm = m/120, nt = t/60, nv = v/5;
  const z  = wts[0]*nw + wts[1]*nm + wts[2]*nt + wts[3]*nv + wts[4];
  const prob = sigmoid(z);
  const pct  = Math.round(prob * 100);
  const cls  = prob >= 0.5 ? 1 : 0;

  // Prediction display
  document.getElementById('xaiProbPct').textContent = pct + '%';
  document.getElementById('xaiProbPct').style.color = prob > 0.7 ? 'var(--primary)' : prob > 0.3 ? 'var(--warning)' : 'var(--danger)';
  document.getElementById('xaiConfBar').style.width = pct + '%';

  const actions = {water: ['PUMP_OFF','PUMP_ON'], fan: ['FAN_OFF','FAN_ON'], multi: ['Class 0','Class 1']};
  const icons   = {water: ['🚿','🚿'], fan: ['💨','💨'], multi: ['❌','✅']};
  document.getElementById('xaiAction').textContent = actions[model][cls];
  document.getElementById('xaiActionIcon').textContent = icons[model][cls];
  document.getElementById('xaiClass').textContent = `Class ${cls} | p=${prob.toFixed(3)}`;

  // Servo
  drawMiniServo(Math.round(prob * 180));

  // SHAP-like feature contributions
  const contribs = [
    Math.abs(wts[0] * nw),
    Math.abs(wts[1] * nm),
    Math.abs(wts[2] * nt),
    Math.abs(wts[3] * nv),
    Math.abs(wts[4]),
  ];
  const totalAbs = contribs.reduce((a,b)=>a+b, 1e-7);
  contribs.forEach((c,i) => {
    const pctBar = Math.round((c/totalAbs)*100);
    document.getElementById('sv'+i).textContent = c.toFixed(3);
    document.getElementById('fb'+i).style.width = pctBar + '%';
  });

  // Heatmap
  buildHeatmap(nw, nm, nt, nv);

  // Explanation
  const dominant = featureNames[contribs.slice(0,4).indexOf(Math.max(...contribs.slice(0,4)))];
  const decision = cls === 1 ? 'activate' : 'keep off';
  const confidence = pct > 80 ? 'very confident' : pct > 60 ? 'moderately confident' : 'uncertain';
  document.getElementById('xaiExplanation').innerHTML =
    `The model is <strong>${confidence} (${pct}%)</strong> in its decision to <strong>${decision}</strong> the ${model === 'fan' ? 'fan' : 'pump'}.<br><br>` +
    `📌 Most influential sensor: <strong style="color:var(--primary)">${dominant}</strong> (contributed ${Math.round((contribs.slice(0,4).indexOf(Math.max(...contribs.slice(0,4)))+1)/4*contribs[contribs.slice(0,4).indexOf(Math.max(...contribs.slice(0,4)))]/totalAbs*100)}% of decision weight).<br><br>` +
    (prob > 0.5
      ? `⚠️ With water at ${w}% and mic at ${m}dB, the model determines action is needed.`
      : `✅ With current sensor values, no action is required. Monitor if water drops below 40%.`);
}

function buildHeatmap(nw, nm, nt, nv) {
  const hm = document.getElementById('heatmap');
  hm.innerHTML = '';
  for (let i = 0; i < 40; i++) {
    const fx = (i % 10) / 9;
    const fy = Math.floor(i / 10) / 3;
    const val = nw * fx + nm * fy + nt * (1 - fx) * 0.3 + nv * 0.1;
    const norm = Math.min(1, Math.max(0, val));
    const r = Math.round((1 - norm) * 239 + norm * 6);
    const g = Math.round((1 - norm) * 68 + norm * 214);
    const b = Math.round((1 - norm) * 68 + norm * 160);
    const cell = document.createElement('div');
    cell.className = 'heat-cell';
    cell.style.background = `rgb(${r},${g},${b})`;
    cell.style.opacity = '0.8';
    hm.appendChild(cell);
  }
}

function drawMiniServo(angle) {
  const cv = document.getElementById('xaiServo');
  const ctx = cv.getContext('2d');
  const cx = cv.width/2, cy = cv.height-4, r = 50;
  ctx.clearRect(0,0,cv.width,cv.height);
  ctx.beginPath(); ctx.arc(cx,cy,r,Math.PI,0);
  ctx.strokeStyle='rgba(255,255,255,.1)'; ctx.lineWidth=8; ctx.stroke();
  const endR = Math.PI+(angle/180)*Math.PI;
  ctx.beginPath(); ctx.arc(cx,cy,r,Math.PI,endR);
  ctx.strokeStyle='#06d6a0'; ctx.lineWidth=8; ctx.stroke();
  const nx=cx+Math.cos(endR)*(r-4);const ny=cy+Math.sin(endR)*(r-4);
  ctx.beginPath();ctx.moveTo(cx,cy);ctx.lineTo(nx,ny);
  ctx.strokeStyle='#06d6a0';ctx.lineWidth=2;ctx.stroke();
  ctx.fillStyle='#f0f0ff';ctx.font='bold 11px JetBrains Mono';ctx.textAlign='center';ctx.fillText(angle+'°',cx,cy-14);
}

function startLiveXAI() {
  if (liveInterval) { clearInterval(liveInterval); liveInterval = null; event.target.textContent='⚡ Live Mode'; return; }
  event.target.textContent='⏹ Stop Live';
  liveInterval = setInterval(() => {
    document.getElementById('slWater').value = Math.round(50+30*Math.sin(Date.now()/5000)+Math.random()*10);
    document.getElementById('slMic').value   = Math.round(40+25*Math.random());
    document.getElementById('slTemp').value  = Math.round(25+5*Math.random());
    updateXAI();
  }, 1500);
}

updateXAI();
</script>
</body>
</html>
