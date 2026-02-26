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
<title>Pipeline Visualizer — SmartEdge</title>
<link rel="stylesheet" href="../assets/css/main.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.pipeline-bg{
  background:radial-gradient(ellipse at center,rgba(6,214,160,.05) 0%,transparent 70%);
  border-radius:var(--radius-lg);border:1px solid var(--border);
  padding:48px 32px;margin-bottom:28px;position:relative;overflow:hidden;
}
.node-detail{
  background:var(--bg-card2);border:1px solid var(--border);
  border-radius:var(--radius);padding:16px;font-size:.8rem;
  color:var(--text-secondary);margin-top:8px;text-align:center;
}
.node-detail strong{color:var(--primary)}
.latency-bar{
  display:flex;align-items:center;gap:12px;
  padding:10px 16px;background:var(--bg-card2);border-radius:var(--radius-sm);
  margin-bottom:8px;font-size:.8rem;
}
.latency-fill{height:6px;border-radius:3px;flex:1;background:var(--bg-input);overflow:hidden}
.latency-inner{height:100%;border-radius:3px;background:var(--grad-primary);transition:width .6s}
.data-ticker{
  font-family:'JetBrains Mono',monospace;font-size:.75rem;
  color:var(--primary);padding:12px 16px;
  background:var(--bg-card2);border-radius:var(--radius-sm);
  border:1px solid rgba(6,214,160,.2);height:140px;
  overflow-y:auto;display:flex;flex-direction:column;gap:4px;
}
.ticker-line{animation:fadeIn .3s ease}
.stage-info{
  display:grid;grid-template-columns:repeat(6,1fr);gap:12px;
  margin-bottom:28px;
}
.stage-card{
  background:var(--bg-card2);border:1px solid var(--border);
  border-radius:var(--radius);padding:16px 12px;text-align:center;
  transition:var(--transition);cursor:pointer;
}
.stage-card.active{border-color:var(--primary);background:rgba(6,214,160,.08);box-shadow:0 0 12px var(--primary-glow)}
.stage-card.completed{border-color:rgba(6,214,160,.4);background:rgba(6,214,160,.04)}
.stage-card .stt{font-size:22px;margin-bottom:6px}
.stage-card .stl{font-size:.65rem;font-weight:600;color:var(--text-muted);text-transform:uppercase}
.stage-card .stv{font-size:.75rem;margin-top:4px;color:var(--text-secondary)}
</style>
</head>
<body>
<nav class="navbar">
  <a href="../index.php" class="navbar-brand"><div class="brand-logo">🧠</div><span>SmartEdge <span class="gradient-text">ML</span></span></a>
  <div class="navbar-nav">
    <a href="../dashboard.php" class="nav-link">📊 Dashboard</a>
    <a href="../ml/playground.php" class="nav-link">🎛️ ML Playground</a>
    <a href="index.php" class="nav-link active">🔗 Pipeline</a>
    <a href="../datasets/index.php" class="nav-link">📁 Datasets</a>
  </div>
  <div class="navbar-right">
    <div class="flex gap-4">
      <span class="online-dot"></span>
      <span id="pipeStatus" style="font-size:.875rem;color:var(--text-muted)">Ready</span>
    </div>
    <button class="btn btn-primary btn-sm" onclick="runPipeline()">▶ Run Pipeline</button>
    <button class="btn btn-outline btn-sm" onclick="stopPipeline()">⏹ Stop</button>
  </div>
</nav>

<div style="margin-top:72px;padding:28px">
  <div class="flex between" style="margin-bottom:24px">
    <div>
      <h1 style="font-size:1.5rem;margin-bottom:4px">🔗 Pipeline Execution Visualizer</h1>
      <p style="color:var(--text-secondary);font-size:.875rem">Watch data flow from ESP32 hardware through to ML predictions and hardware actions in real-time</p>
    </div>
    <div class="flex gap-4">
      <select class="form-control" style="width:auto" id="pipeMode">
        <option value="demo">Demo Mode (No Hardware)</option>
        <option value="live">Live Hardware Mode</option>
      </select>
      <select class="form-control" style="width:auto" id="pipeSpeed">
        <option value="1000">Normal Speed</option>
        <option value="300">Fast Speed</option>
        <option value="2000">Slow Speed</option>
      </select>
    </div>
  </div>

  <!-- Stage Info Cards -->
  <div class="stage-info" id="stageInfo">
    <div class="stage-card" id="sc0">
      <div class="stt">📡</div>
      <div class="stl">ESP32 Sensor</div>
      <div class="stv" id="sv0">Idle</div>
    </div>
    <div class="stage-card" id="sc1">
      <div class="stt">🔗</div>
      <div class="stl">MQTT Broker</div>
      <div class="stv" id="sv1">—</div>
    </div>
    <div class="stage-card" id="sc2">
      <div class="stt">⚙️</div>
      <div class="stl">PHP Backend</div>
      <div class="stv" id="sv2">—</div>
    </div>
    <div class="stage-card" id="sc3">
      <div class="stt">🤖</div>
      <div class="stl">ML Model</div>
      <div class="stv" id="sv3">—</div>
    </div>
    <div class="stage-card" id="sc4">
      <div class="stt">🎯</div>
      <div class="stl">Prediction</div>
      <div class="stv" id="sv4">—</div>
    </div>
    <div class="stage-card" id="sc5">
      <div class="stt">🔌</div>
      <div class="stl">Hardware Action</div>
      <div class="stv" id="sv5">—</div>
    </div>
  </div>

  <!-- Main Pipeline Visualization -->
  <div class="pipeline-bg">
    <div class="pipeline-container" id="pipelineContainer">
      <div class="pipeline-node" id="pn0">
        <div class="node-circle" id="nc0">📡</div>
        <div class="node-label" id="nl0">Sensor</div>
        <div class="node-detail" id="nd0">ESP32<br><strong id="sensorReadout">—</strong></div>
      </div>
      <div class="pipeline-arrow" id="pa0"></div>

      <div class="pipeline-node" id="pn1">
        <div class="node-circle" id="nc1">🔗</div>
        <div class="node-label" id="nl1">MQTT</div>
        <div class="node-detail" id="nd1">HiveMQ<br><strong id="mqttTopic">—</strong></div>
      </div>
      <div class="pipeline-arrow" id="pa1"></div>

      <div class="pipeline-node" id="pn2">
        <div class="node-circle" id="nc2">⚙️</div>
        <div class="node-label" id="nl2">Backend</div>
        <div class="node-detail" id="nd2">PHP + MySQL<br><strong id="dbRows">—</strong></div>
      </div>
      <div class="pipeline-arrow" id="pa2"></div>

      <div class="pipeline-node" id="pn3">
        <div class="node-circle" id="nc3">🤖</div>
        <div class="node-label" id="nl3">ML Model</div>
        <div class="node-detail" id="nd3">Gradient Descent<br><strong id="mlEpoch">—</strong></div>
      </div>
      <div class="pipeline-arrow" id="pa3"></div>

      <div class="pipeline-node" id="pn4">
        <div class="node-circle" id="nc4">🎯</div>
        <div class="node-label" id="nl4">Prediction</div>
        <div class="node-detail" id="nd4">Class Output<br><strong id="mlPred">—</strong></div>
      </div>
      <div class="pipeline-arrow" id="pa4"></div>

      <div class="pipeline-node" id="pn5">
        <div class="node-circle" id="nc5">🔌</div>
        <div class="node-label" id="nl5">Hardware</div>
        <div class="node-detail" id="nd5">Actuator<br><strong id="hwAction">—</strong></div>
      </div>
    </div>

    <!-- Data packet animation overlay -->
    <canvas id="packetCanvas" style="position:absolute;inset:0;pointer-events:none;width:100%;height:100%"></canvas>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:28px">
    <!-- Latency Monitor -->
    <div class="card">
      <h3 style="font-size:.875rem;margin-bottom:16px">⏱️ Stage Latencies</h3>
      <div id="latencyList">
        <div class="latency-bar"><span style="width:80px;color:var(--text-muted)">ESP32→MQTT</span><div class="latency-fill"><div class="latency-inner" id="lat0" style="width:0%"></div></div><span id="latv0" style="color:var(--primary);font-family:'JetBrains Mono',monospace">—</span></div>
        <div class="latency-bar"><span style="width:80px;color:var(--text-muted)">MQTT→PHP</span><div class="latency-fill"><div class="latency-inner" id="lat1" style="width:0%"></div></div><span id="latv1" style="color:var(--primary);font-family:'JetBrains Mono',monospace">—</span></div>
        <div class="latency-bar"><span style="width:80px;color:var(--text-muted)">PHP→ML</span><div class="latency-fill"><div class="latency-inner" id="lat2" style="width:0%"></div></div><span id="latv2" style="color:var(--primary);font-family:'JetBrains Mono',monospace">—</span></div>
        <div class="latency-bar"><span style="width:80px;color:var(--text-muted)">ML→HW</span><div class="latency-fill"><div class="latency-inner" id="lat3" style="width:0%"></div></div><span id="latv3" style="color:var(--primary);font-family:'JetBrains Mono',monospace">—</span></div>
      </div>
      <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:.8rem">
        <span style="color:var(--text-muted)">Total Latency</span>
        <span style="color:var(--primary);font-weight:700;font-family:'JetBrains Mono',monospace" id="totalLat">—</span>
      </div>
    </div>

    <!-- Data Ticker -->
    <div class="card">
      <h3 style="font-size:.875rem;margin-bottom:12px">📋 Data Stream</h3>
      <div class="data-ticker" id="dataTicker">
        <span style="color:var(--text-muted)">[waiting for pipeline…]</span>
      </div>
    </div>

    <!-- Pipeline Stats -->
    <div class="card">
      <h3 style="font-size:.875rem;margin-bottom:16px">📊 Pipeline Stats</h3>
      <div style="display:flex;flex-direction:column;gap:12px">
        <div class="flex between"><span style="color:var(--text-muted);font-size:.85rem">Runs Completed</span><span style="font-weight:700;color:var(--primary)" id="runCount">0</span></div>
        <div class="flex between"><span style="color:var(--text-muted);font-size:.85rem">Data Points Processed</span><span style="font-weight:700;color:var(--primary)" id="dataCount">0</span></div>
        <div class="flex between"><span style="color:var(--text-muted);font-size:.85rem">Avg Accuracy</span><span style="font-weight:700;color:var(--primary)" id="avgAcc">—</span></div>
        <div class="flex between"><span style="color:var(--text-muted);font-size:.85rem">HW Commands Sent</span><span style="font-weight:700;color:var(--warning)" id="hwCount">0</span></div>
        <div class="flex between"><span style="color:var(--text-muted);font-size:.85rem">Session Uptime</span><span style="font-weight:700;color:var(--info)" id="uptime">00:00</span></div>
      </div>
    </div>
  </div>

  <!-- Throughput Chart -->
  <div class="card">
    <h3 style="font-size:.875rem;margin-bottom:16px">📈 Pipeline Throughput (msgs/sec)</h3>
    <canvas id="throughputChart" height="80"></canvas>
  </div>
</div>

<div class="toast-container" id="toasts"></div>

<script>
const API = '../api/index.php';
const STAGES = ['Sensor','MQTT','Backend','ML Model','Prediction','Hardware'];
let pipeRunning=false, runCount=0, dataCount=0, hwCount=0;
let throughputH=[], uptimeSeconds=0;
let uptimeInterval=null, throughputChart=null;
let accHistory=[];

// ── Pipeline Run ─────────────────────────────────────────────
async function runPipeline() {
  if (pipeRunning) return;
  pipeRunning=true;
  document.getElementById('pipeStatus').textContent='Running…';
  resetStages();
  const speed=parseInt(document.getElementById('pipeSpeed').value);

  // Generate sensor reading
  const water=Math.round(55+35*Math.sin(Date.now()/4000)+Math.random()*10);
  const mic  =Math.round(40+30*Math.random());

  // ── Stage 0: ESP32 Sensor ──
  await activateStage(0, `W:${water}% M:${mic}dB`, `Water: ${water}%, Mic: ${mic}dB`, speed);
  document.getElementById('sensorReadout').textContent=`${water}% / ${mic}dB`;
  log(`[ESP32] Sensor read → water=${water}% mic=${mic}dB`);

  // ── Stage 1: MQTT ──
  const topic=`smartedge/sensor/water`;
  await activateStage(1, topic, topic, speed);
  document.getElementById('mqttTopic').textContent=topic;
  log(`[MQTT] Published → ${topic} payload={value:${water}}`);

  // ── Stage 2: PHP Backend ──
  await activateStage(2, 'Stored ✓', 'INSERT sensor_data', speed);
  document.getElementById('dbRows').textContent=`+1 row`;
  log(`[PHP] Received MQTT message, stored in sensor_data table`);
  dataCount++;

  // ── Stage 3: ML Model ──
  await activateStage(3, 'Predicting…', 'Forward pass', speed);
  const sigmoid=z=>1/(1+Math.exp(-z));
  const w1=0.04, w2=-0.02, b=-2;
  const z=w1*water+w2*mic+b;
  const prob=sigmoid(z);
  const epoch=Math.round(Math.random()*100+50);
  document.getElementById('mlEpoch').textContent=`p=${prob.toFixed(3)}`;
  log(`[ML] Forward pass → z=${z.toFixed(3)} σ(z)=${prob.toFixed(3)}`);

  // ── Stage 4: Prediction ──
  const threshold=0.5;
  const predClass=prob>=threshold?1:0;
  const confidence=(prob*100).toFixed(1);
  const action=predClass===1?'PUMP_ON':'PUMP_OFF';
  await activateStage(4, predClass===1?`Class 1 (${confidence}%)`:`Class 0 (${confidence}%)`, `conf: ${confidence}%`, speed);
  document.getElementById('mlPred').textContent=`${action} [${confidence}%]`;
  log(`[Predict] Class=${predClass} confidence=${confidence}% action=${action}`);
  accHistory.push(prob);

  // Update servo viz
  const servoAngle=Math.round(prob*180);
  document.getElementById('sv3').textContent=`Epoch ${epoch}`;
  document.getElementById('sv4').textContent=`${confidence}% conf`;

  // ── Stage 5: Hardware Action ──
  await activateStage(5, action, `GPIO → ${action}`, speed);
  document.getElementById('hwAction').textContent=action;
  log(`[HW] Sent MQTT cmd → smartedge/cmd/ESP32_001/pump ${action}`);

  // Send actual command
  try {
    await fetch(`${API}?action=send_command`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({device_id:'ESP32_001',actuator:'pump',command:action})});
    hwCount++;
  } catch {}

  // Stats
  runCount++;
  dataCount+=6;
  document.getElementById('runCount').textContent=runCount;
  document.getElementById('dataCount').textContent=dataCount;
  document.getElementById('hwCount').textContent=hwCount;
  document.getElementById('avgAcc').textContent=(accHistory.reduce((a,b)=>a+b,0)/accHistory.length*100).toFixed(1)+'%';

  // Throughput
  updateThroughput();

  pipeRunning=false;
  document.getElementById('pipeStatus').textContent=`Run #${runCount} complete`;
  showToast(`✅ Pipeline run #${runCount} complete — ${action}`,'success');
}

function stopPipeline() {
  pipeRunning=false;
  resetStages();
  document.getElementById('pipeStatus').textContent='Stopped';
}

// ── Stage Animation ───────────────────────────────────────────
function resetStages() {
  for(let i=0;i<6;i++){
    document.getElementById(`nc${i}`).className='node-circle';
    document.getElementById(`nl${i}`).className='node-label';
    document.getElementById(`sc${i}`).className='stage-card';
    document.getElementById(`sv${i}`).textContent='—';
  }
  for(let i=0;i<5;i++){
    document.getElementById(`pa${i}`).className='pipeline-arrow';
  }
}

async function activateStage(idx, value, detail, speed) {
  // Activate node
  document.getElementById(`nc${idx}`).className='node-circle active';
  document.getElementById(`nl${idx}`).className='node-label active';
  document.getElementById(`sc${idx}`).className='stage-card active';
  document.getElementById(`sv${idx}`).textContent=value;

  // Latency simulation
  const lat=Math.round(15+Math.random()*80);
  if(idx<4){
    document.getElementById(`lat${idx}`).style.width=Math.min(100,lat)+'%';
    document.getElementById(`latv${idx}`).textContent=lat+'ms';
  }

  await sleep(speed);

  // Complete
  document.getElementById(`nc${idx}`).className='node-circle completed';
  document.getElementById(`sc${idx}`).className='stage-card completed';
  if(idx<5) document.getElementById(`pa${idx}`).className='pipeline-arrow active';

  // Total latency
  const lats=['lat0','lat1','lat2','lat3'].map(id=>parseInt(document.getElementById(id).style.width)||0);
  const totalMs=Math.round(lats.reduce((a,b)=>a+b,0)*1.5);
  document.getElementById('totalLat').textContent=totalMs+'ms';
}

function sleep(ms){return new Promise(r=>setTimeout(r,ms))}

// ── Data Ticker ────────────────────────────────────────────────
function log(msg) {
  const ticker=document.getElementById('dataTicker');
  const ts=new Date().toLocaleTimeString();
  const line=document.createElement('div');
  line.className='ticker-line';
  line.innerHTML=`<span style="color:var(--text-muted)">${ts}</span> ${msg}`;
  ticker.appendChild(line);
  ticker.scrollTop=ticker.scrollHeight;
  if(ticker.children.length>30) ticker.removeChild(ticker.firstChild);
}

// ── Throughput Chart ──────────────────────────────────────────
function updateThroughput() {
  const now=new Date().toLocaleTimeString();
  const msgs=Math.round(Math.random()*8+2);
  if(throughputH.length>20){throughputChart.data.labels.shift();throughputChart.data.datasets[0].data.shift();}
  throughputChart.data.labels.push(now);
  throughputChart.data.datasets[0].data.push(msgs);
  throughputChart.update();
}

// ── Uptime ────────────────────────────────────────────────────
function startUptime(){
  uptimeInterval=setInterval(()=>{
    uptimeSeconds++;
    const m=String(Math.floor(uptimeSeconds/60)).padStart(2,'0');
    const s=String(uptimeSeconds%60).padStart(2,'0');
    document.getElementById('uptime').textContent=`${m}:${s}`;
  },1000);
}

function showToast(msg,type='info'){
  const t=document.createElement('div');t.className='toast';
  const c={success:'var(--primary)',error:'var(--danger)',info:'var(--info)'};
  t.innerHTML=`<span style="color:${c[type]||c.info}">●</span> ${msg}`;
  document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),5000);
}

// ── Init ─────────────────────────────────────────────────────
throughputChart=new Chart(document.getElementById('throughputChart').getContext('2d'),{
  type:'bar',
  data:{labels:[],datasets:[{label:'msgs/sec',data:[],backgroundColor:'rgba(6,214,160,.5)',borderColor:'#06d6a0',borderWidth:1,borderRadius:4}]},
  options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#6b7280',font:{size:10}},grid:{display:false}},y:{ticks:{color:'#6b7280',font:{size:10}},grid:{color:'rgba(255,255,255,.04)'}}}}
});

startUptime();
log('[System] Pipeline visualizer ready. Click "Run Pipeline" to start.');

// Auto-run in demo mode
setInterval(()=>{
  if(document.getElementById('pipeMode').value==='demo' && !pipeRunning) {
    runPipeline();
  }
},5000);
</script>
</body>
</html>
