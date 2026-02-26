<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
$auth->requireAdmin();
$stats = db()->fetchAll("SELECT COUNT(*) AS c, role FROM users GROUP BY role");
$statsMap = array_column($stats, 'c', 'role');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — SmartEdge</title>
<link rel="stylesheet" href="../assets/css/main.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.admin-header-bar{
  background:linear-gradient(135deg,rgba(247,37,133,.08),rgba(167,139,250,.08));
  border:1px solid rgba(167,139,250,.2);
  border-radius:var(--radius-lg);padding:24px 32px;margin-bottom:28px;
  display:flex;align-items:center;justify-content:space-between;
}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:2000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);display:none}
.modal-overlay.open{display:flex}
.modal{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px;min-width:440px;max-width:560px;width:100%;position:relative}
</style>
</head>
<body>
<nav class="navbar">
  <a href="../index.php" class="navbar-brand"><div class="brand-logo">🧠</div><span>SmartEdge <span class="gradient-text">ML</span></span></a>
  <div class="navbar-nav">
    <a href="../dashboard.php" class="nav-link">📊 Dashboard</a>
    <a href="index.php" class="nav-link active" style="color:var(--warning)">🛡️ Admin</a>
    <a href="users.php" class="nav-link">👥 Users</a>
    <a href="devices.php" class="nav-link">📱 Devices</a>
    <a href="experiments.php" class="nav-link">🔬 Experiments</a>
  </div>
  <div class="navbar-right">
    <a href="../auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
  </div>
</nav>

<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Admin</div>
    <a href="index.php" class="sidebar-link active"><span class="icon">🛡️</span> Overview</a>
    <a href="users.php" class="sidebar-link"><span class="icon">👥</span> Users</a>
    <a href="devices.php" class="sidebar-link"><span class="icon">📱</span> Devices</a>
    <a href="experiments.php" class="sidebar-link"><span class="icon">🔬</span> Experiments</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Platform</div>
    <a href="../dashboard.php" class="sidebar-link"><span class="icon">📊</span> Dashboard</a>
    <a href="../ml/playground.php" class="sidebar-link"><span class="icon">🎛️</span> ML Playground</a>
    <a href="../pipeline/index.php" class="sidebar-link"><span class="icon">🔗</span> Pipeline</a>
  </div>
</aside>

<main class="main-content">
  <!-- Admin Header -->
  <div class="admin-header-bar fade-in">
    <div>
      <h1 style="font-size:1.5rem;margin-bottom:4px">🛡️ Admin Control Panel</h1>
      <p style="color:var(--text-secondary);font-size:.875rem">SmartEdge ML Sandbox — Full system management</p>
    </div>
    <div class="flex gap-4">
      <span class="badge badge-warning">Super Admin</span>
      <span id="adminTime" style="font-family:'JetBrains Mono',monospace;font-size:.8rem;color:var(--text-muted)"></span>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="widget-row stagger" style="grid-template-columns:repeat(6,1fr)">
    <div class="card card-stat">
      <div class="stat-icon green">👥</div>
      <div><div class="stat-value" id="asTotalUsers">—</div><div class="stat-label">Total Users</div></div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon blue">👨‍🎓</div>
      <div><div class="stat-value" id="asStudents">—</div><div class="stat-label">Students</div></div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon purple">📡</div>
      <div><div class="stat-value" id="asDevices">—</div><div class="stat-label">Devices</div></div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon pink">🔬</div>
      <div><div class="stat-value" id="asExps">—</div><div class="stat-label">Experiments</div></div>
    </div>
    <div class="card card-stat">
      <div class="stat-icon green">📊</div>
      <div><div class="stat-value" id="asSensors">—</div><div class="stat-label">Sensor Rows</div></div>
    </div>
    <div class="card card-stat" style="background:rgba(239,68,68,.05);border-color:rgba(239,68,68,.2)">
      <div class="stat-icon pink">⏳</div>
      <div><div class="stat-value" id="asPending" style="color:var(--danger)">—</div><div class="stat-label">Pending Devices</div></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('overview',this)">📊 Overview</button>
    <button class="tab-btn" onclick="switchTab('users',this)">👥 Users</button>
    <button class="tab-btn" onclick="switchTab('devices',this)">📡 Devices</button>
    <button class="tab-btn" onclick="switchTab('activity',this)">📋 Activity Log</button>
    <button class="tab-btn" onclick="switchTab('learners',this)">🏆 Top Learners</button>
  </div>

  <!-- Overview Tab -->
  <div class="tab-panel active" id="tab-overview">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
      <div class="card">
        <h3 style="font-size:.875rem;margin-bottom:16px">📈 Experiment Trends</h3>
        <canvas id="expTrendChart" height="120"></canvas>
      </div>
      <div class="card">
        <h3 style="font-size:.875rem;margin-bottom:16px">📡 Device Activity</h3>
        <canvas id="deviceActChart" height="120"></canvas>
      </div>
    </div>
    <div class="card">
      <h3 style="font-size:.875rem;margin-bottom:16px">🏆 Top Performing Learners</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>XP Points</th><th>Level</th></tr></thead>
          <tbody id="topLearnersTable"><tr><td colspan="5" style="text-align:center;color:var(--text-muted)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Users Tab -->
  <div class="tab-panel" id="tab-users">
    <div class="flex between" style="margin-bottom:16px">
      <input type="text" class="form-control" style="max-width:300px" placeholder="🔍 Search users…" oninput="filterUsers(this.value)">
      <div class="flex gap-4">
        <select class="form-control" style="width:auto" id="roleFilter" onchange="loadUsers()">
          <option value="">All Roles</option>
          <option value="student">Students</option>
          <option value="admin">Admins</option>
        </select>
      </div>
    </div>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Status</th><th>XP</th><th>Last Login</th><th>Actions</th></tr></thead>
          <tbody id="usersTable"><tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted)">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Devices Tab -->
  <div class="tab-panel" id="tab-devices">
    <div class="card table-wrap">
      <table>
        <thead><tr><th>Device ID</th><th>Name</th><th>Owner</th><th>Status</th><th>Approved</th><th>Last Seen</th><th>Actions</th></tr></thead>
        <tbody id="devicesTable"><tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- Activity Log Tab -->
  <div class="tab-panel" id="tab-activity">
    <div class="card table-wrap">
      <table>
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
        <tbody id="activityTable"><tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted)">Loading…</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- Top Learners Tab -->
  <div class="tab-panel" id="tab-learners">
    <div id="leaderboard" style="display:flex;flex-direction:column;gap:12px"></div>
  </div>
</main>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h3 style="margin-bottom:20px">✏️ Edit User</h3>
    <input type="hidden" id="editUserId">
    <div class="form-group"><label class="form-label">Name</label><input type="text" class="form-control" id="editName"></div>
    <div class="form-group"><label class="form-label">Role</label><select class="form-control" id="editRole"><option value="student">Student</option><option value="admin">Admin</option></select></div>
    <div class="form-group"><label class="form-label">Status</label><select class="form-control" id="editActive"><option value="1">Active</option><option value="0">Suspended</option></select></div>
    <div class="flex gap-4" style="margin-top:20px">
      <button class="btn btn-primary" onclick="saveUser()" style="flex:1">Save Changes</button>
      <button class="btn btn-outline" onclick="closeModal()" style="flex:1">Cancel</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toasts"></div>

<script>
const API='../api/index.php';
let allUsers=[],allDevices=[];

// ── Tab Switching ─────────────────────────────────────────────
function switchTab(tab, btn) {
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+tab).classList.add('active');
  btn.classList.add('active');

  if(tab==='users') loadUsers();
  else if(tab==='devices') loadDevices();
  else if(tab==='activity') loadActivity();
  else if(tab==='learners') loadLeaderboard();
}

// ── Load Stats ────────────────────────────────────────────────
async function loadAdminStats() {
  const r=await fetch(`${API}?action=admin_stats`);
  const d=await r.json();
  document.getElementById('asTotalUsers').textContent=d.total_users??'—';
  document.getElementById('asStudents').textContent=d.total_users-1??'—';
  document.getElementById('asDevices').textContent=d.total_devices??'—';
  document.getElementById('asExps').textContent=d.total_experiments??'—';
  document.getElementById('asSensors').textContent=(d.total_sensor_rows??0).toLocaleString();
  document.getElementById('asPending').textContent=d.pending_devices??'—';

  // Top learners in overview
  if(d.top_learners?.length){
    document.getElementById('topLearnersTable').innerHTML = d.top_learners.map((u,i)=>`
      <tr><td>${i+1}</td><td><strong>${u.name}</strong></td><td style="color:var(--text-muted)">${u.email}</td>
      <td><span style="color:var(--primary);font-weight:700">${(u.xp_points??0).toLocaleString()}</span></td>
      <td><span class="badge badge-success">Lv.${u.level??1}</span></td></tr>
    `).join('');
  }

  // Activity in recent logs
  if(d.recent_logs?.length){
    document.getElementById('activityTable').innerHTML=d.recent_logs.map(l=>`
      <tr>
        <td style="font-family:'JetBrains Mono',monospace;font-size:.75rem;color:var(--text-muted)">${new Date(l.created_at).toLocaleString()}</td>
        <td>${l.name??'System'}</td>
        <td><span class="badge badge-info">${l.action}</span></td>
        <td style="color:var(--text-muted);font-size:.8rem">${l.details??''}</td>
        <td style="font-family:'JetBrains Mono',monospace;font-size:.75rem">${l.ip_address??''}</td>
      </tr>
    `).join('');
  }

  drawDemoCharts();
}

function drawDemoCharts() {
  const labels=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const expData=labels.map(()=>Math.round(Math.random()*20+5));
  const devData=labels.map(()=>Math.round(Math.random()*4));

  new Chart(document.getElementById('expTrendChart').getContext('2d'),{
    type:'line',data:{labels,datasets:[{label:'Experiments',data:expData,borderColor:'#06d6a0',fill:true,backgroundColor:'rgba(6,214,160,.08)',tension:.4,pointRadius:3,borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{labels:{color:'#9ca3af'}}},scales:{x:{ticks:{color:'#6b7280'}},y:{ticks:{color:'#6b7280'},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
  new Chart(document.getElementById('deviceActChart').getContext('2d'),{
    type:'bar',data:{labels,datasets:[{label:'Online Devices',data:devData,backgroundColor:'rgba(167,139,250,.5)',borderColor:'#a78bfa',borderWidth:1,borderRadius:4}]},
    options:{responsive:true,plugins:{legend:{labels:{color:'#9ca3af'}}},scales:{x:{ticks:{color:'#6b7280'}},y:{ticks:{color:'#6b7280',stepSize:1},grid:{color:'rgba(255,255,255,.04)'}}}}
  });
}

// ── Users CRUD ────────────────────────────────────────────────
async function loadUsers() {
  const r=await fetch(`${API}?action=admin_users`);
  const d=await r.json();
  allUsers=d.data||[];
  renderUsers(allUsers);
}

function renderUsers(users) {
  document.getElementById('usersTable').innerHTML=users.length?users.map(u=>`
    <tr>
      <td style="font-size:.75rem;color:var(--text-muted)">#${u.id}</td>
      <td><strong>${u.name}</strong></td>
      <td style="color:var(--text-secondary)">${u.email}</td>
      <td><span class="badge badge-${u.role==='admin'?'warning':'info'}">${u.role}</span></td>
      <td>${u.is_verified?'<span class="badge badge-success">✓ Yes</span>':'<span class="badge badge-warning">✗ No</span>'}</td>
      <td>${u.is_active?'<span class="online-dot"></span>':'<span class="offline-dot"></span>'}</td>
      <td style="color:var(--primary);font-weight:700">${(u.xp_points??0).toLocaleString()}</td>
      <td style="font-size:.75rem;color:var(--text-muted)">${u.last_login?new Date(u.last_login).toLocaleDateString():'Never'}</td>
      <td>
        <div class="flex gap-4">
          <button class="btn btn-secondary btn-sm" onclick='editUser(${JSON.stringify(u)})'>Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id},'${u.name}')">Del</button>
        </div>
      </td>
    </tr>
  `).join(''):'<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted)">No users found</td></tr>';
}

function filterUsers(q) {
  const filtered=allUsers.filter(u=>u.name.toLowerCase().includes(q.toLowerCase())||u.email.toLowerCase().includes(q.toLowerCase()));
  renderUsers(filtered);
}

function editUser(u) {
  document.getElementById('editUserId').value=u.id;
  document.getElementById('editName').value=u.name;
  document.getElementById('editRole').value=u.role;
  document.getElementById('editActive').value=u.is_active;
  document.getElementById('editModal').classList.add('open');
}

async function saveUser() {
  const r=await fetch(`${API}?action=update_user`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({
    id:document.getElementById('editUserId').value,
    name:document.getElementById('editName').value,
    role:document.getElementById('editRole').value,
    is_active:document.getElementById('editActive').value
  })});
  const d=await r.json();
  if(d.success){closeModal();loadUsers();showToast('✅ User updated','success');}
  else showToast('❌ '+d.error,'error');
}

async function deleteUser(id,name) {
  if(!confirm(`Delete user "${name}"? This cannot be undone.`)) return;
  const r=await fetch(`${API}?action=delete_user`,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
  const d=await r.json();
  if(d.success){loadUsers();showToast('✅ User deleted','success');}
  else showToast('❌ '+(d.error||'Error'),'error');
}

function closeModal(){document.getElementById('editModal').classList.remove('open')}

// ── Devices ─────────────────────────────────────────────────
async function loadDevices() {
  const r=await fetch(`${API}?action=devices`);
  const d=await r.json();
  allDevices=d.data||[];
  document.getElementById('devicesTable').innerHTML=allDevices.length?allDevices.map(dev=>`
    <tr>
      <td><code style="font-family:'JetBrains Mono',monospace;color:var(--primary)">${dev.device_id}</code></td>
      <td><strong>${dev.device_name}</strong></td>
      <td>${dev.owner_name??'<span style="color:var(--text-muted)">Unassigned</span>'}</td>
      <td>${dev.is_online?'<span class="online-dot"></span> Online':'<span class="offline-dot"></span> Offline'}</td>
      <td>${dev.is_approved?'<span class="badge badge-success">✓ Approved</span>':'<span class="badge badge-warning">⏳ Pending</span>'}</td>
      <td style="font-size:.75rem;color:var(--text-muted)">${dev.last_seen?new Date(dev.last_seen).toLocaleString():'Never'}</td>
      <td>
        <div class="flex gap-4">
          ${!dev.is_approved?`<button class="btn btn-primary btn-sm" onclick="approveDevice(${dev.id})">✓ Approve</button>`:''}
          <button class="btn btn-danger btn-sm" onclick="deleteDevice(${dev.id})">Del</button>
        </div>
      </td>
    </tr>
  `).join(''):'<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">No devices found</td></tr>';
}

async function approveDevice(id) {
  const r=await fetch(`${API}?action=approve_device`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({device_id:id})});
  const d=await r.json();
  if(d.success){loadDevices();showToast('✅ Device approved','success');}
}

async function deleteDevice(id) {
  if(!confirm('Delete this device?')) return;
  const r=await fetch(`${API}?action=delete_device`,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
  const d=await r.json();
  if(d.success){loadDevices();showToast('✅ Device deleted','success');}
}

// ── Activity ──────────────────────────────────────────────────
async function loadActivity() {
  const r=await fetch(`${API}?action=admin_stats`);
  const d=await r.json();
  document.getElementById('activityTable').innerHTML=(d.recent_logs||[]).map(l=>`
    <tr>
      <td style="font-family:'JetBrains Mono',monospace;font-size:.75rem;color:var(--text-muted)">${new Date(l.created_at).toLocaleString()}</td>
      <td>${l.name??'System'}</td>
      <td><span class="badge badge-info">${l.action}</span></td>
      <td style="color:var(--text-muted);font-size:.8rem">${l.details??''}</td>
      <td style="font-family:'JetBrains Mono',monospace;font-size:.75rem">${l.ip_address??''}</td>
    </tr>
  `).join('');
}

// ── Leaderboard ───────────────────────────────────────────────
async function loadLeaderboard() {
  const r=await fetch(`${API}?action=admin_stats`);
  const d=await r.json();
  const medals=['🥇','🥈','🥉'];
  document.getElementById('leaderboard').innerHTML=(d.top_learners||[]).map((u,i)=>`
    <div class="card flex between" style="padding:18px 24px">
      <div class="flex gap-4">
        <span style="font-size:1.75rem">${medals[i]||'#'+(i+1)}</span>
        <div>
          <div style="font-weight:700">${u.name}</div>
          <div style="font-size:.8rem;color:var(--text-muted)">${u.email}</div>
        </div>
      </div>
      <div class="flex gap-4">
        <div style="text-align:center">
          <div style="font-size:1.5rem;font-weight:800;color:var(--primary)">${(u.xp_points??0).toLocaleString()}</div>
          <div style="font-size:.7rem;color:var(--text-muted)">XP POINTS</div>
        </div>
        <span class="badge badge-success">Level ${u.level??1}</span>
      </div>
    </div>
  `).join('') || '<div class="card" style="text-align:center;padding:40px;color:var(--text-muted)">No students yet</div>';
}

function showToast(msg,type='info'){const t=document.createElement('div');t.className='toast';const c={success:'var(--primary)',error:'var(--danger)',info:'var(--info)'};t.innerHTML=`<span style="color:${c[type]||c.info}">●</span> ${msg}`;document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),4000);}

// Clock
setInterval(()=>{document.getElementById('adminTime').textContent=new Date().toLocaleTimeString()},1000);
document.getElementById('adminTime').textContent=new Date().toLocaleTimeString();

loadAdminStats();
</script>
</body>
</html>
