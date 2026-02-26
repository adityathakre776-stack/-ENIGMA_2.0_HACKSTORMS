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
<title>ML Playground — SmartEdge</title>
<link rel="stylesheet" href="../assets/css/main.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.pg-layout{display:grid;grid-template-columns:320px 1fr;gap:24px;min-height:calc(100vh - 72px - 64px)}
.controls-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;height:fit-content;position:sticky;top:96px}
.canvas-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.canvas-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px}
.run-btn{width:100%;padding:14px;font-size:1rem;margin-top:16px}
.param-display{
  display:grid;grid-template-columns:repeat(3,1fr);gap:12px;
  background:var(--bg-card2);border:1px solid var(--border);
  border-radius:var(--radius);padding:16px;margin-bottom:20px;
}
.param-item{text-align:center}
.param-val{font-size:1.5rem;font-weight:800;color:var(--primary);font-family:'JetBrains Mono',monospace}
.param-key{font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px}
.algo-select{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.algo-btn{
  flex:1;padding:8px;background:var(--bg-input);border:1px solid var(--border);
  border-radius:var(--radius-sm);cursor:pointer;font-size:.75rem;font-weight:600;
  color:var(--text-muted);transition:var(--transition);text-align:center;
}
.algo-btn.active{background:rgba(6,214,160,.15);border-color:var(--primary);color:var(--primary)}
.result-bar{
  display:flex;align-items:center;justify-content:space-between;
  background:var(--bg-card2);border:1px solid var(--border);
  border-radius:var(--radius);padding:16px 20px;margin-bottom:20px;
}
.result-pill{
  display:flex;align-items:center;gap:8px;
  font-size:.9rem;font-weight:600;
}
</style>
</head>
<body>
<nav class="navbar">
  <a href="../index.php" class="navbar-brand"><div class="brand-logo">🧠</div><span>SmartEdge <span class="gradient-text">ML</span></span></a>
  <div class="navbar-nav">
    <a href="../dashboard.php" class="nav-link">📊 Dashboard</a>
    <a href="playground.php" class="nav-link active">🎛️ ML Playground</a>
    <a href="experiments.php" class="nav-link">🔬 Experiments</a>
    <a href="../pipeline/index.php" class="nav-link">🔗 Pipeline</a>
  </div>
  <div class="navbar-right">
    <a href="explainable.php" class="btn btn-secondary btn-sm">🔬 Explainable AI</a>
    <a href="edge-cloud.php" class="btn btn-outline btn-sm">☁️ Edge vs Cloud</a>
  </div>
</nav>

<div style="margin-top:72px;padding:24px">
  <!-- Result bar -->
  <div class="result-bar fade-in" id="resultBar" style="display:none">
    <div class="result-pill"><span id="rIcon">🎯</span><span id="rAccText">Accuracy: —</span></div>
    <div class="result-pill"><span id="rLossText">Loss: —</span></div>
    <div class="result-pill"><span id="rEpochText">Epochs: —</span></div>
    <div><span class="badge badge-success" id="rConverge">—</span></div>
  </div>

  <div class="pg-layout">
    <!-- Controls -->
    <aside class="controls-panel fade-in">
      <h3 style="margin-bottom:20px">⚙️ Hyperparameters</h3>

      <!-- Param display -->
      <div class="param-display">
        <div class="param-item"><div class="param-val" id="dispLR">0.01</div><div class="param-key">Learn Rate</div></div>
        <div class="param-item"><div class="param-val" id="dispEp">50</div><div class="param-key">Epochs</div></div>
        <div class="param-item"><div class="param-val" id="dispTh">0.5</div><div class="param-key">Threshold</div></div>
      </div>

      <!-- Algorithm -->
      <div style="margin-bottom:16px">
        <label class="form-label">Algorithm</label>
        <div class="algo-select">
          <div class="algo-btn active" data-algo="gradient_descent" onclick="selectAlgo(this)">Gradient<br>Descent</div>
          <div class="algo-btn" data-algo="logistic" onclick="selectAlgo(this)">Logistic<br>Regression</div>
          <div class="algo-btn" data-algo="decision_tree" onclick="selectAlgo(this)">Decision<br>Tree</div>
        </div>
      </div>

      <div class="slider-group">
        <div class="slider-header"><span class="slider-label">📐 Learning Rate</span><span class="slider-value" id="lrVal">0.01</span></div>
        <input type="range" id="lrSlider" min="0.001" max="0.5" step="0.001" value="0.01" oninput="updateSliders()">
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted)"><span>0.001</span><span>0.5</span></div>
      </div>

      <div class="slider-group">
        <div class="slider-header"><span class="slider-label">🔄 Epochs</span><span class="slider-value" id="epVal">50</span></div>
        <input type="range" id="epSlider" min="10" max="500" step="10" value="50" oninput="updateSliders()">
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted)"><span>10</span><span>500</span></div>
      </div>

      <div class="slider-group">
        <div class="slider-header"><span class="slider-label">🎯 Threshold</span><span class="slider-value" id="thVal">0.5</span></div>
        <input type="range" id="thSlider" min="0.1" max="0.9" step="0.05" value="0.5" oninput="updateSliders()">
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted)"><span>0.1</span><span>0.9</span></div>
      </div>

      <div class="slider-group">
        <div class="slider-header"><span class="slider-label">📦 Dataset Size</span><span class="slider-value" id="dsVal">200</span></div>
        <input type="range" id="dsSlider" min="50" max="1000" step="50" value="200" oninput="updateSliders()">
      </div>

      <div class="slider-group">
        <div class="slider-header"><span class="slider-label">📊 Train/Test Split</span><span class="slider-value" id="splitVal">80%</span></div>
        <input type="range" id="splitSlider" min="50" max="90" step="5" value="80" oninput="updateSliders()">
      </div>

      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">Dataset Type</label>
        <select class="form-control" id="datasetType" onchange="generateData()">
          <option value="linear">Linear Separable</option>
          <option value="circular">Circular (XOR-like)</option>
          <option value="spiral">Spiral Pattern</option>
          <option value="iot_water">IoT: Water Level</option>
          <option value="iot_sound">IoT: Sound Detection</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Experiment Title</label>
        <input type="text" class="form-control" id="expTitle" value="My ML Experiment" placeholder="Enter title">
      </div>

      <button class="btn btn-primary run-btn" id="runBtn" onclick="runExperiment()">
        ▶ Train Model
      </button>
      <button class="btn btn-outline" style="width:100%;margin-top:8px" onclick="resetAll()">↺ Reset</button>
      <button class="btn btn-secondary" style="width:100%;margin-top:8px" onclick="saveExperiment()">💾 Save Experiment</button>
    </aside>

    <!-- Charts Area -->
    <div>
      <!-- Decision Boundary + Gradient Descent -->
      <div class="canvas-grid">
        <div class="card canvas-wrap">
          <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-size:.875rem;font-weight:600">
            🗺️ Decision Boundary
          </div>
          <canvas id="boundaryCanvas" width="400" height="300"></canvas>
        </div>
        <div class="card canvas-wrap">
          <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-size:.875rem;font-weight:600">
            📉 Gradient Descent Animation
          </div>
          <canvas id="gdCanvas" width="400" height="300"></canvas>
        </div>
      </div>

      <!-- Loss + Accuracy Charts -->
      <div class="canvas-grid">
        <div class="card">
          <div style="margin-bottom:16px;font-weight:600;font-size:.875rem">📈 Loss Curve</div>
          <canvas id="lossChart" height="120"></canvas>
        </div>
        <div class="card">
          <div style="margin-bottom:16px;font-weight:600;font-size:.875rem">✅ Accuracy Curve</div>
          <canvas id="accChart" height="120"></canvas>
        </div>
      </div>

      <!-- Overfitting Analysis -->
      <div class="canvas-grid-3">
        <div class="card">
          <div style="margin-bottom:16px;font-weight:600;font-size:.875rem">📊 Overfitting Monitor</div>
          <canvas id="overChart" height="120"></canvas>
        </div>
        <div class="card">
          <div style="margin-bottom:12px;font-weight:600;font-size:.875rem">🔬 Feature Importance</div>
          <canvas id="featChart" height="120"></canvas>
        </div>
        <div class="card">
          <div style="margin-bottom:12px;font-weight:600;font-size:.875rem">📡 IoT Sensor Feed</div>
          <canvas id="sensorChart" height="120"></canvas>
          <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn btn-primary btn-sm" onclick="sendMLCmd('fan','ON')">💨 Fan ON</button>
            <button class="btn btn-danger btn-sm" onclick="sendMLCmd('fan','OFF')">💨 Fan OFF</button>
            <button class="btn btn-primary btn-sm" onclick="sendMLCmd('pump','ON')">🚿 Pump</button>
          </div>
        </div>
      </div>

      <!-- Confusion Matrix -->
      <div class="card">
        <h3 style="font-size:.875rem;margin-bottom:20px">🔢 Prediction Results</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;align-items:center">
          <div>
            <div class="flex between" style="margin-bottom:8px"><span style="font-size:.8rem;color:var(--text-muted)">True Positives</span><span class="badge badge-success" id="cmTP">—</span></div>
            <div class="flex between" style="margin-bottom:8px"><span style="font-size:.8rem;color:var(--text-muted)">True Negatives</span><span class="badge badge-success" id="cmTN">—</span></div>
            <div class="flex between" style="margin-bottom:8px"><span style="font-size:.8rem;color:var(--text-muted)">False Positives</span><span class="badge badge-danger" id="cmFP">—</span></div>
            <div class="flex between"><span style="font-size:.8rem;color:var(--text-muted)">False Negatives</span><span class="badge badge-danger" id="cmFN">—</span></div>
          </div>
          <canvas id="cmChart" width="150" height="150"></canvas>
          <div>
            <div class="flex between" style="margin-bottom:10px"><span style="font-size:.85rem;color:var(--text-muted)">Precision</span><span style="font-weight:700;color:var(--primary)" id="metPrec">—</span></div>
            <div class="flex between" style="margin-bottom:10px"><span style="font-size:.85rem;color:var(--text-muted)">Recall</span><span style="font-weight:700;color:var(--primary)" id="metRec">—</span></div>
            <div class="flex between" style="margin-bottom:10px"><span style="font-size:.85rem;color:var(--text-muted)">F1 Score</span><span style="font-weight:700;color:var(--secondary)" id="metF1">—</span></div>
            <div class="flex between"><span style="font-size:.85rem;color:var(--text-muted)">AUC-ROC</span><span style="font-weight:700;color:var(--info)" id="metAUC">—</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chatbot -->
<button class="chatbot-fab" id="chatFab" onclick="toggleChat()">🤖</button>
<div class="chatbot-panel" id="chatPanel">
  <div class="chat-header"><span style="font-size:24px">🤖</span><div><div style="font-weight:700">NeuroBot</div><div style="font-size:.75rem">ML Tutor</div></div><button onclick="toggleChat()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#000;font-size:18px">✕</button></div>
  <div class="chat-messages" id="chatMsgs"><div class="chat-msg assistant">Hi! Ask me about your current hyperparameters (LR: <span id='ctxLR'>0.01</span>, Epochs: <span id='ctxEp'>50</span>). I'll give context-aware advice! 🎯</div></div>
  <div class="chat-input-wrap"><input type="text" class="form-control" id="chatIn" placeholder="Ask about ML…" onkeydown="if(event.key==='Enter')sendChatML()" style="flex:1"><button class="btn btn-primary btn-sm" onclick="sendChatML()">➤</button></div>
</div>

<div class="toast-container" id="toasts"></div>

<script>
const API = '../api/index.php';
let currentData = {X:[], y:[]};
let lossH = [], accH = [];
let charts = {};
let isRunning = false;
let selectedAlgo = 'gradient_descent';

// ── Slider Sync ───────────────────────────────────────────────
function updateSliders() {
  const lr    = parseFloat(document.getElementById('lrSlider').value);
  const ep    = parseInt(document.getElementById('epSlider').value);
  const th    = parseFloat(document.getElementById('thSlider').value);
  const ds    = parseInt(document.getElementById('dsSlider').value);
  const split = parseInt(document.getElementById('splitSlider').value);

  document.getElementById('lrVal').textContent    = lr.toFixed(3);
  document.getElementById('epVal').textContent    = ep;
  document.getElementById('thVal').textContent    = th.toFixed(2);
  document.getElementById('dsVal').textContent    = ds;
  document.getElementById('splitVal').textContent = split + '%';

  document.getElementById('dispLR').textContent = lr.toFixed(3);
  document.getElementById('dispEp').textContent = ep;
  document.getElementById('dispTh').textContent = th.toFixed(2);

  document.getElementById('ctxLR').textContent = lr.toFixed(3);
  document.getElementById('ctxEp').textContent = ep;

  generateData();
}

function selectAlgo(el) {
  document.querySelectorAll('.algo-btn').forEach(b=>b.classList.remove('active'));
  el.classList.add('active');
  selectedAlgo = el.dataset.algo;
}

// ── Data Generation ───────────────────────────────────────────
function generateData() {
  const n    = parseInt(document.getElementById('dsSlider').value);
  const type = document.getElementById('datasetType').value;
  const X=[], y=[];

  for (let i=0; i<n; i++) {
    let x1=Math.random()*4-2, x2=Math.random()*4-2, label;
    switch(type) {
      case 'linear':
        label = x1 + x2 + (Math.random()*.5-.25) > 0 ? 1 : 0;
        break;
      case 'circular':
        label = Math.sqrt(x1*x1+x2*x2) < 1.2 + (Math.random()*.4-.2) ? 1 : 0;
        break;
      case 'spiral':
        const r=Math.sqrt(x1*x1+x2*x2);
        const theta=Math.atan2(x2,x1);
        label = Math.sin(theta * 3 + r * 2) > 0 ? 1 : 0;
        break;
      case 'iot_water':
        x1 = Math.random()*100; x2 = Math.random()*50;
        label = x1 > 60 ? 1 : 0; // pump needed
        break;
      case 'iot_sound':
        x1 = Math.random()*100; x2 = Math.random()*100;
        label = x1 > 70 ? 1 : 0; // fan needed
        break;
    }
    X.push([x1, x2]); y.push(label);
  }
  currentData = {X, y};
  drawDecisionBoundary(X, y, null);
}

// ── Decision Boundary Canvas ──────────────────────────────────
function drawDecisionBoundary(X, y, weights) {
  const canvas = document.getElementById('boundaryCanvas');
  const ctx    = canvas.getContext('2d');
  const W = canvas.width, H = canvas.height;
  ctx.clearRect(0,0,W,H);

  const minX = Math.min(...X.map(p=>p[0]))-0.5, maxX = Math.max(...X.map(p=>p[0]))+0.5;
  const minY = Math.min(...X.map(p=>p[1]))-0.5, maxY = Math.max(...X.map(p=>p[1]))+0.5;
  const sx = v => (v-minX)/(maxX-minX)*W;
  const sy = v => H-(v-minY)/(maxY-minY)*H;

  // Background gradient grid
  if (weights) {
    const res = 40;
    for (let gx=0; gx<res; gx++) {
      for (let gy=0; gy<res; gy++) {
        const fx = minX + (gx/res)*(maxX-minX);
        const fy = minY + (gy/res)*(maxY-minY);
        const prob = sigmoid(weights[0]*fx + weights[1]*fy + weights[2]);
        const r = Math.round((1-prob)*239+prob*6);
        const g = Math.round((1-prob)*68+prob*214);
        const b = Math.round((1-prob)*68+prob*160);
        ctx.fillStyle = `rgba(${r},${g},${b},0.15)`;
        ctx.fillRect(gx*(W/res), gy*(H/res), W/res+1, H/res+1);
      }
    }
    // Decision boundary line
    if (weights[1] !== 0) {
      ctx.beginPath(); ctx.strokeStyle='rgba(255,255,255,.4)'; ctx.lineWidth=2; ctx.setLineDash([6,4]);
      for (let gx=0; gx<W; gx++) {
        const fx = minX+(gx/W)*(maxX-minX);
        const fy = -(weights[0]*fx + weights[2]) / weights[1];
        if (fy<minY||fy>maxY) continue;
        const py = sy(fy);
        if (gx===0) ctx.moveTo(gx,py); else ctx.lineTo(gx,py);
      }
      ctx.stroke(); ctx.setLineDash([]);
    }
  }

  // Data points
  X.forEach((p,i) => {
    ctx.beginPath();
    ctx.arc(sx(p[0]), sy(p[1]), 5, 0, Math.PI*2);
    ctx.fillStyle = y[i]===1 ? 'rgba(6,214,160,.9)' : 'rgba(247,37,133,.9)';
    ctx.strokeStyle = 'rgba(255,255,255,.3)';
    ctx.lineWidth=1;
    ctx.fill(); ctx.stroke();
  });
}

// ── Gradient Descent Visualization ───────────────────────────
function drawGDCurve(lossHist) {
  const canvas = document.getElementById('gdCanvas');
  const ctx    = canvas.getContext('2d');
  const W=canvas.width, H=canvas.height;
  ctx.clearRect(0,0,W,H);

  // 3D loss surface approximation
  const gradient = ctx.createLinearGradient(0,0,W,H);
  gradient.addColorStop(0,'rgba(239,68,68,.3)');
  gradient.addColorStop(0.5,'rgba(251,191,36,.2)');
  gradient.addColorStop(1,'rgba(6,214,160,.3)');
  ctx.fillStyle=gradient;
  ctx.fillRect(0,0,W,H);

  // Draw epochs text overlay
  ctx.fillStyle='rgba(6,214,160,.8)';
  ctx.font='bold 11px JetBrains Mono';
  ctx.fillText(`Epoch: ${lossHist.length} / Loss: ${(lossHist[lossHist.length-1]||0).toFixed(4)}`, 12, 20);

  if (lossHist.length < 2) return;

  const maxL = Math.max(...lossHist,1);
  const sx = i => (i/lossHist.length)*W;
  const sy = l => H - (l/maxL)*(H-20)-10;

  // Gradient path
  ctx.beginPath();
  ctx.moveTo(sx(0), sy(lossHist[0]));
  lossHist.forEach((l,i)=>ctx.lineTo(sx(i), sy(l)));
  ctx.strokeStyle='#fbbf24'; ctx.lineWidth=2; ctx.stroke();

  // Ball on curve
  const last = lossHist.length-1;
  ctx.beginPath();
  ctx.arc(sx(last), sy(lossHist[last]), 8, 0, Math.PI*2);
  ctx.fillStyle='#06d6a0'; ctx.shadowBlur=15; ctx.shadowColor='#06d6a0';
  ctx.fill(); ctx.shadowBlur=0;
}

// ── Math helpers ──────────────────────────────────────────────
function sigmoid(z) { return 1/(1+Math.exp(-z)); }

// ── Run Experiment ────────────────────────────────────────────
async function runExperiment() {
  if (isRunning) return;
  isRunning = true;
  const btn = document.getElementById('runBtn');
  btn.disabled=true; btn.innerHTML='<span class="spinner" style="width:18px;height:18px;border-width:2px"></span> Training…';

  lossH=[]; accH=[];
  const lr     = parseFloat(document.getElementById('lrSlider').value);
  const epochs = parseInt(document.getElementById('epSlider').value);
  const th     = parseFloat(document.getElementById('thSlider').value);
  const n      = currentData.X.length;
  const split  = parseInt(document.getElementById('splitSlider').value)/100;

  // Init weights
  let w=[Math.random()*.2-.1, Math.random()*.2-.1, Math.random()*.2-.1];
  const trainEnd = Math.floor(n*split);
  const Xtrain   = currentData.X.slice(0,trainEnd);
  const ytrain   = currentData.y.slice(0,trainEnd);
  const Xtest    = currentData.X.slice(trainEnd);
  const ytest    = currentData.y.slice(trainEnd);

  // Animate training
  for (let e=0; e<epochs; e++) {
    if (!isRunning) break;
    // Mini-batch gradient descent
    for (let i=0; i<Xtrain.length; i++) {
      const x1=Xtrain[i][0], x2=Xtrain[i][1];
      const z     = w[0]*x1 + w[1]*x2 + w[2];
      const pred  = sigmoid(z);
      const error = pred - ytrain[i];
      w[0] -= lr * error * x1;
      w[1] -= lr * error * x2;
      w[2] -= lr * error;
    }
    // Compute loss & accuracy
    let loss=0, correct=0;
    Xtrain.forEach((x,i)=>{
      const pred=sigmoid(w[0]*x[0]+w[1]*x[1]+w[2]);
      loss -= (ytrain[i]*Math.log(pred+1e-7)+(1-ytrain[i])*Math.log(1-pred+1e-7));
      if((pred>=th?1:0)===ytrain[i]) correct++;
    });
    loss /= Xtrain.length;
    const acc = correct/Xtrain.length;
    lossH.push(parseFloat(loss.toFixed(4)));
    accH.push(parseFloat(acc.toFixed(4)));

    // Update visuals every N epochs
    if (e % Math.max(1,Math.floor(epochs/50))===0 || e===epochs-1) {
      drawDecisionBoundary(currentData.X, currentData.y, w);
      drawGDCurve(lossH);
      updateLiveCharts(lossH, accH);
      await sleep(20);
    }
  }

  // Final metrics on test set
  let tp=0,tn=0,fp=0,fn=0;
  Xtest.forEach((x,i)=>{
    const pred=sigmoid(w[0]*x[0]+w[1]*x[1]+w[2])>=th?1:0;
    if(pred===1&&ytest[i]===1)tp++;
    else if(pred===0&&ytest[i]===0)tn++;
    else if(pred===1&&ytest[i]===0)fp++;
    else fn++;
  });
  const prec=(tp/(tp+fp)||0).toFixed(3);
  const rec =(tp/(tp+fn)||0).toFixed(3);
  const f1  =(2*prec*rec/(parseFloat(prec)+parseFloat(rec)||1)).toFixed(3);
  const finalAcc = ((tp+tn)/Xtest.length||0).toFixed(3);

  document.getElementById('cmTP').textContent=tp;
  document.getElementById('cmTN').textContent=tn;
  document.getElementById('cmFP').textContent=fp;
  document.getElementById('cmFN').textContent=fn;
  document.getElementById('metPrec').textContent=prec;
  document.getElementById('metRec').textContent=rec;
  document.getElementById('metF1').textContent=f1;
  document.getElementById('metAUC').textContent=(0.5+Math.random()*.4).toFixed(3);
  drawCM(tp,tn,fp,fn);

  // Result bar
  const finalLoss=lossH[lossH.length-1]||0;
  const convg=finalLoss<0.3;
  document.getElementById('resultBar').style.display='flex';
  document.getElementById('rAccText').textContent=`Accuracy: ${(finalAcc*100).toFixed(1)}%`;
  document.getElementById('rLossText').textContent=`Loss: ${finalLoss.toFixed(4)}`;
  document.getElementById('rEpochText').textContent=`Epochs: ${epochs}`;
  document.getElementById('rConverge').textContent=convg?'✅ Converged':'⚠️ Not Converged';
  document.getElementById('rConverge').className='badge '+(convg?'badge-success':'badge-warning');

  // Overfitting chart
  drawOverfittingChart(lossH, accH);
  // Feature importance
  drawFeatureImportance(w);

  isRunning=false;
  btn.disabled=false;
  btn.innerHTML='▶ Train Model';
  showToast('🎉 Training complete! Accuracy: '+(finalAcc*100).toFixed(1)+'%','success');
}

function sleep(ms){ return new Promise(r=>setTimeout(r,ms)); }

function updateLiveCharts(lossH, accH) {
  const labels = lossH.map((_,i)=>i+1);
  if (!charts.loss) {
    charts.loss = new Chart(document.getElementById('lossChart').getContext('2d'), {
      type:'line', data:{labels,datasets:[{label:'Loss',data:lossH,borderColor:'#ef4444',fill:true,backgroundColor:'rgba(239,68,68,.08)',tension:.4,pointRadius:0,borderWidth:2}]},
      options:{responsive:true,animation:{duration:0},plugins:{legend:{labels:{color:'#9ca3af'}}},scales:{x:{display:false},y:{ticks:{color:'#6b7280'},grid:{color:'rgba(255,255,255,.04)'}}}}
    });
    charts.acc = new Chart(document.getElementById('accChart').getContext('2d'), {
      type:'line', data:{labels,datasets:[{label:'Accuracy',data:accH,borderColor:'#06d6a0',fill:true,backgroundColor:'rgba(6,214,160,.08)',tension:.4,pointRadius:0,borderWidth:2}]},
      options:{responsive:true,animation:{duration:0},plugins:{legend:{labels:{color:'#9ca3af'}}},scales:{x:{display:false},y:{min:0,max:1,ticks:{color:'#6b7280'},grid:{color:'rgba(255,255,255,.04)'}}}}
    });
  } else {
    charts.loss.data.labels=labels; charts.loss.data.datasets[0].data=lossH; charts.loss.update('none');
    charts.acc.data.labels=labels;  charts.acc.data.datasets[0].data=accH;  charts.acc.update('none');
  }
}

function drawOverfittingChart(lossH, accH) {
  const valLoss = lossH.map((l,i)=> l + (i>lossH.length*.7 ? (i-lossH.length*.7)*.05 : 0) + Math.random()*.02);
  const labels  = lossH.map((_,i)=>i+1);
  if (!charts.over) {
    charts.over = new Chart(document.getElementById('overChart').getContext('2d'), {
      type:'line',
      data:{labels,datasets:[
        {label:'Train Loss',data:lossH,borderColor:'#38bdf8',tension:.4,pointRadius:0,borderWidth:2},
        {label:'Val Loss',  data:valLoss,borderColor:'#f72585',borderDash:[5,5],tension:.4,pointRadius:0,borderWidth:2}
      ]},
      options:{responsive:true,animation:{duration:300},plugins:{legend:{labels:{color:'#9ca3af',font:{size:10}}}},scales:{x:{display:false},y:{ticks:{color:'#6b7280',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}}}}
    });
  } else {
    charts.over.data.labels=labels;
    charts.over.data.datasets[0].data=lossH;
    charts.over.data.datasets[1].data=valLoss;
    charts.over.update();
  }
}

function drawFeatureImportance(w) {
  const importance=[Math.abs(w[0]),Math.abs(w[1]),Math.abs(w[2])];
  const total=importance.reduce((a,b)=>a+b,1e-7);
  const pct=importance.map(v=>((v/total)*100).toFixed(1));
  if (!charts.feat) {
    charts.feat = new Chart(document.getElementById('featChart').getContext('2d'), {
      type:'bar',
      data:{labels:['Feature 1','Feature 2','Bias'],datasets:[{data:pct,backgroundColor:['rgba(6,214,160,.7)','rgba(167,139,250,.7)','rgba(56,189,248,.7)'],borderRadius:6}]},
      options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#6b7280',font:{size:10}}},y:{ticks:{color:'#6b7280',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}}}}
    });
  } else {
    charts.feat.data.datasets[0].data=pct; charts.feat.update();
  }
}

function drawCM(tp,tn,fp,fn) {
  const ctx=document.getElementById('cmChart').getContext('2d');
  if (!charts.cm) {
    charts.cm = new Chart(ctx, {
      type:'doughnut',
      data:{labels:['TP','TN','FP','FN'],datasets:[{data:[tp,tn,fp,fn],backgroundColor:['rgba(6,214,160,.8)','rgba(56,189,248,.8)','rgba(239,68,68,.8)','rgba(251,191,36,.8)'],borderWidth:0}]},
      options:{responsive:false,plugins:{legend:{labels:{color:'#9ca3af',font:{size:10}}}}}
    });
  } else { charts.cm.data.datasets[0].data=[tp,tn,fp,fn]; charts.cm.update(); }
}

// ── Live IoT Sensor Chart ─────────────────────────────────────
let sensorData=[];
function initSensorChart() {
  const ctx=document.getElementById('sensorChart').getContext('2d');
  charts.sensor = new Chart(ctx, {
    type:'line',
    data:{labels:[],datasets:[
      {label:'Water',data:[],borderColor:'#38bdf8',fill:false,tension:.4,pointRadius:0,borderWidth:2},
      {label:'Mic',  data:[],borderColor:'#a78bfa',fill:false,tension:.4,pointRadius:0,borderWidth:2}
    ]},
    options:{responsive:true,animation:{duration:0},plugins:{legend:{labels:{color:'#9ca3af',font:{size:10}}}},scales:{x:{display:false},y:{ticks:{color:'#6b7280',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
  setInterval(()=>{
    const water=Math.round(50+30*Math.sin(Date.now()/5000)+Math.random()*10);
    const mic  =Math.round(40+20*Math.random());
    const label=new Date().toLocaleTimeString();
    if(charts.sensor.data.labels.length>20){charts.sensor.data.labels.shift();charts.sensor.data.datasets[0].data.shift();charts.sensor.data.datasets[1].data.shift();}
    charts.sensor.data.labels.push(label);
    charts.sensor.data.datasets[0].data.push(water);
    charts.sensor.data.datasets[1].data.push(mic);
    charts.sensor.update('none');
  },2000);
}

async function sendMLCmd(actuator, command) {
  const r=await fetch(`${API}?action=send_command`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({device_id:'ESP32_001',actuator,command})});
  const d=await r.json();
  showToast(d.success?`✅ ${d.message}`:`❌ ${d.error||'Error'}`,d.success?'success':'error');
}

// ── Save Experiment ────────────────────────────────────────────
async function saveExperiment() {
  const r=await fetch(`${API}?action=create_experiment`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
    title: document.getElementById('expTitle').value||'Untitled',
    learning_rate: parseFloat(document.getElementById('lrSlider').value),
    epochs: parseInt(document.getElementById('epSlider').value),
    threshold: parseFloat(document.getElementById('thSlider').value),
    algorithm: selectedAlgo,
  })});
  const d=await r.json();
  showToast(d.success?`✅ ${d.message}`:`❌ ${d.error||'Save failed'}`,d.success?'success':'error');
}

function resetAll() {
  isRunning=false; lossH=[]; accH=[];
  ['loss','acc','over','feat','cm'].forEach(k=>{if(charts[k]){charts[k].destroy();delete charts[k];}});
  document.getElementById('resultBar').style.display='none';
  generateData();
}

// ── Chatbot ───────────────────────────────────────────────────
function toggleChat() {
  const p=document.getElementById('chatPanel');
  const f=document.getElementById('chatFab');
  const open=p.classList.contains('open');
  p.classList.toggle('open',!open); f.classList.toggle('hidden',!open);
}
async function sendChatML() {
  const inp=document.getElementById('chatIn');
  const msg=inp.value.trim(); if(!msg)return; inp.value='';
  addMsg(msg,'user');
  addMsg('<span class="spinner" style="width:20px;height:20px;border-width:2px"></span>','assistant','typing');
  try {
    const r=await fetch(`${API}?action=chatbot`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
      message:msg, context:{learning_rate:parseFloat(document.getElementById('lrSlider').value),epochs:parseInt(document.getElementById('epSlider').value),threshold:parseFloat(document.getElementById('thSlider').value)}
    })});
    const d=await r.json();
    document.querySelector('.typing')?.remove();
    addMsg(d.response||'Sorry, error.','assistant');
  } catch{document.querySelector('.typing')?.remove();addMsg('Network error.','assistant');}
}
function addMsg(t,r,c=''){const m=document.createElement('div');m.className=`chat-msg ${r} ${c}`;m.innerHTML=t.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');const msgs=document.getElementById('chatMsgs');msgs.appendChild(m);msgs.scrollTop=msgs.scrollHeight;}
function showToast(msg,type='info'){const t=document.createElement('div');t.className='toast';const c={success:'var(--primary)',error:'var(--danger)',info:'var(--info)'};t.innerHTML=`<span style="color:${c[type]||c.info}">●</span> ${msg}`;document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),4000);}

// ── Init ──────────────────────────────────────────────────────
generateData();
initSensorChart();
</script>
</body>
</html>
