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
<title>Datasets — SmartEdge</title>
<link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<nav class="navbar">
  <a href="../index.php" class="navbar-brand"><div class="brand-logo">🧠</div><span>SmartEdge <span class="gradient-text">ML</span></span></a>
  <div class="navbar-nav">
    <a href="../dashboard.php" class="nav-link">📊 Dashboard</a>
    <a href="../ml/playground.php" class="nav-link">🎛️ ML Playground</a>
    <a href="index.php" class="nav-link active">📁 Datasets</a>
  </div>
</nav>

<div style="margin-top:72px;padding:28px">
  <div class="flex between" style="margin-bottom:24px">
    <div>
      <h1 style="font-size:1.5rem">📁 Live Dataset Generator</h1>
      <p style="color:var(--text-secondary)">MQTT sensor data automatically collected and exportable as CSV for ML training</p>
    </div>
    <div class="flex gap-4">
      <button class="btn btn-primary" onclick="generateDataset()">⚡ Generate From MQTT</button>
    </div>
  </div>

  <!-- Generator Card -->
  <div class="card" style="margin-bottom:24px">
    <h3 style="font-size:.875rem;margin-bottom:16px">🔧 Dataset Generator Settings</h3>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px">
      <div class="form-group" style="margin:0">
        <label class="form-label">Device ID</label>
        <select class="form-control" id="genDevice"><option value="ESP32_001">ESP32_001</option></select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Sensor Type</label>
        <select class="form-control" id="genSensor">
          <option value="water_level">Water Level</option>
          <option value="mic">Microphone</option>
          <option value="all">All Sensors</option>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Session ID (optional)</label>
        <input type="text" class="form-control" id="genSession" placeholder="Auto-generated">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Label Column</label>
        <select class="form-control" id="genLabel">
          <option value="threshold">Threshold (value > 50 = 1)</option>
          <option value="binary">Binary (0/1)</option>
        </select>
      </div>
    </div>
    <div id="genResult"></div>
  </div>

  <!-- Stats Row -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px" class="stagger">
    <div class="card card-stat"><div class="stat-icon green">📁</div><div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Datasets</div></div></div>
    <div class="card card-stat"><div class="stat-icon blue">📊</div><div><div class="stat-value" id="statRows">—</div><div class="stat-label">Total Rows</div></div></div>
    <div class="card card-stat"><div class="stat-icon purple">📡</div><div><div class="stat-value" id="statMQTT">—</div><div class="stat-label">From MQTT</div></div></div>
    <div class="card card-stat"><div class="stat-icon pink">⬆️</div><div><div class="stat-value" id="statUploads">—</div><div class="stat-label">Uploaded</div></div></div>
  </div>

  <!-- Datasets Table -->
  <div class="card">
    <div class="flex between" style="margin-bottom:16px">
      <h3 style="font-size:.875rem">All Datasets</h3>
      <input type="text" class="form-control" style="max-width:200px" placeholder="🔍 Search…" oninput="filterDatasets(this.value)">
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>ID</th><th>Name</th><th>Source</th><th>Rows</th><th>Device</th><th>Session</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody id="dsTable"><tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<div class="toast-container" id="toasts"></div>

<script>
const API='../api/index.php';
let allDs=[];

async function loadDatasets() {
  const r=await fetch(`${API}?action=datasets`);
  const d=await r.json();
  allDs=d.data||[];
  renderDs(allDs);
  document.getElementById('statTotal').textContent=allDs.length;
  const totalRows=allDs.reduce((a,ds)=>a+parseInt(ds.row_count||0),0);
  document.getElementById('statRows').textContent=totalRows.toLocaleString();
  document.getElementById('statMQTT').textContent=allDs.filter(ds=>ds.source==='mqtt').length;
  document.getElementById('statUploads').textContent=allDs.filter(ds=>ds.source==='upload').length;
}

function renderDs(datasets) {
  document.getElementById('dsTable').innerHTML=datasets.length?datasets.map(ds=>`
    <tr>
      <td style="font-size:.75rem;color:var(--text-muted)">#${ds.id}</td>
      <td><strong>${ds.name}</strong></td>
      <td><span class="badge badge-${ds.source==='mqtt'?'success':'info'}">${ds.source}</span></td>
      <td style="font-weight:700;color:var(--primary)">${(ds.row_count||0).toLocaleString()}</td>
      <td><code style="font-family:'JetBrains Mono',monospace;font-size:.75rem">${ds.device_id||'—'}</code></td>
      <td style="font-size:.75rem;color:var(--text-muted)">${ds.session_id||'—'}</td>
      <td style="font-size:.75rem;color:var(--text-muted)">${new Date(ds.created_at).toLocaleDateString()}</td>
      <td>
        <div class="flex gap-4">
          <a href="${API}?action=download_dataset&id=${ds.id}" class="btn btn-outline btn-sm">⬇️ CSV</a>
          <a href="../ml/playground.php?dataset_id=${ds.id}" class="btn btn-secondary btn-sm">🎛️ Train</a>
        </div>
      </td>
    </tr>
  `).join(''):'<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">No datasets yet.<br>Generate one from MQTT data or run experiments!</td></tr>';
}

function filterDatasets(q) {
  renderDs(allDs.filter(ds=>ds.name.toLowerCase().includes(q.toLowerCase())));
}

async function generateDataset() {
  const btn=event.target; btn.disabled=true; btn.textContent='Generating…';
  const r=await fetch(`${API}?action=generate_dataset`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
    device_id: document.getElementById('genDevice').value,
    sensor_type: document.getElementById('genSensor').value,
    session_id: document.getElementById('genSession').value||undefined,
  })});
  const d=await r.json();
  btn.disabled=false; btn.textContent='⚡ Generate From MQTT';
  document.getElementById('genResult').innerHTML=d.success
    ?`<div class="alert alert-success">✅ Dataset generated! ${d.rows} rows of ${document.getElementById('genSensor').value} data. File: ${d.file}</div>`
    :`<div class="alert alert-error">❌ ${d.error}</div>`;
  if(d.success) loadDatasets();
}

function showToast(msg,type){const t=document.createElement('div');t.className='toast';t.innerHTML=`<span style="color:${type==='success'?'var(--primary)':'var(--danger)'}">●</span> ${msg}`;document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),4000);}
loadDatasets();
</script>
</body>
</html>
