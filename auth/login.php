<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
if ($auth->isLoggedIn()) { header('Location: ../dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SmartEdge ML Sandbox</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
body{background:var(--bg-dark);display:flex;min-height:100vh}
.auth-left{
  flex:1;background:linear-gradient(135deg,rgba(6,214,160,.08) 0%,rgba(167,139,250,.08) 100%);
  border-right:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:40px;
}
.auth-right{width:480px;display:flex;align-items:center;justify-content:center;padding:40px}
.auth-card{width:100%;max-width:400px}
.auth-brand{display:flex;align-items:center;gap:10px;margin-bottom:40px;font-size:1.2rem;font-weight:800}
.auth-title{font-size:1.75rem;font-weight:800;margin-bottom:8px}
.auth-sub{color:var(--text-secondary);font-size:.875rem;margin-bottom:32px}
.otp-inputs{display:flex;gap:10px;justify-content:center;margin:20px 0}
.otp-input{
  width:50px;height:56px;text-align:center;font-size:1.4rem;font-weight:700;
  background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius);
  color:var(--text-primary);outline:none;transition:var(--transition);font-family:'JetBrains Mono',monospace;
}
.otp-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.divider{display:flex;align-items:center;gap:12px;margin:24px 0;color:var(--text-muted);font-size:.8rem}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
.demo-info{background:rgba(6,214,160,.05);border:1px solid rgba(6,214,160,.2);border-radius:var(--radius);padding:14px 18px;font-size:.8rem;color:var(--text-secondary);margin-top:24px}
.demo-info strong{color:var(--primary)}

/* Left panel content */
.auth-showcase{text-align:center;max-width:400px}
.showcase-icon{font-size:80px;margin-bottom:20px;display:block}
.showcase-title{font-size:1.75rem;font-weight:800;margin-bottom:12px}
.showcase-desc{color:var(--text-secondary);margin-bottom:32px;font-size:.95rem}
.showcase-features{display:flex;flex-direction:column;gap:12px;text-align:left}
.sf-item{
  display:flex;align-items:center;gap:12px;
  background:rgba(255,255,255,.03);border:1px solid var(--border);
  border-radius:var(--radius);padding:12px 16px;
  font-size:.85rem;color:var(--text-secondary);
}
.sf-icon{font-size:20px}
@media(max-width:900px){.auth-left{display:none}.auth-right{width:100%}}
</style>
</head>
<body>
<div class="auth-left">
  <div class="auth-showcase">
    <span class="showcase-icon">🧠</span>
    <h2 class="showcase-title">Welcome Back, <span class="gradient-text">Explorer!</span></h2>
    <p class="showcase-desc">Your ESP32 experiments and ML models are waiting. Let's build something amazing today.</p>
    <div class="showcase-features">
      <div class="sf-item"><span class="sf-icon">📡</span> Real-time MQTT sensor streaming</div>
      <div class="sf-item"><span class="sf-icon">🤖</span> Interactive ML playground with animations</div>
      <div class="sf-item"><span class="sf-icon">🎮</span> Gamified learning with XP and badges</div>
      <div class="sf-item"><span class="sf-icon">📊</span> Live dashboard & hardware control</div>
    </div>
  </div>
</div>

<div class="auth-right">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="brand-logo">🧠</div>
      SmartEdge <span class="gradient-text">ML</span>
    </div>

    <!-- LOGIN FORM -->
    <div id="loginForm">
      <h1 class="auth-title">Sign In</h1>
      <p class="auth-sub">Enter your credentials to access your ML workspace</p>

      <div id="loginAlert"></div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="loginEmail" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;justify-content:space-between">
          Password
          <a href="#" onclick="showForgot()" style="color:var(--primary);font-size:.8rem;text-decoration:none">Forgot Password?</a>
        </label>
        <input type="password" class="form-control" id="loginPassword" placeholder="••••••••" required>
      </div>
      <button class="btn btn-primary" style="width:100%" onclick="doLogin()">
        🔐 Sign In
      </button>

      <div class="divider">or</div>
      <a href="register.php" class="btn btn-outline" style="width:100%">Create New Account</a>

      <div class="demo-info">
        <strong>Demo Admin:</strong> admin@smartedge.local / password<br>
        <strong>Note:</strong> First setup: import <code>smartedge_schema.sql</code> in phpMyAdmin
      </div>
    </div>

    <!-- FORGOT PASSWORD FORM -->
    <div id="forgotForm" style="display:none">
      <h1 class="auth-title">Reset Password</h1>
      <p class="auth-sub">Enter your email to receive a reset OTP</p>
      <div id="forgotAlert"></div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="forgotEmail" placeholder="you@example.com">
      </div>
      <button class="btn btn-primary" style="width:100%" onclick="doRequestReset()">Send Reset OTP</button>
      <button class="btn btn-outline" style="width:100%;margin-top:10px" onclick="showLogin()">← Back to Login</button>
    </div>

    <!-- OTP RESET FORM -->
    <div id="resetOTPForm" style="display:none">
      <h1 class="auth-title">Enter OTP</h1>
      <p class="auth-sub">Check your email and enter the 6-digit code</p>
      <div id="resetAlert"></div>
      <div class="otp-inputs" id="resetOTPInputs">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" class="form-control" id="newPassword" placeholder="Min 8 characters">
      </div>
      <button class="btn btn-primary" style="width:100%" onclick="doResetPassword()">Reset Password</button>
    </div>
  </div>
</div>

<script>
const API = '../api/index.php';

function showAlert(id, msg, type='error') {
  document.getElementById(id).innerHTML = `<div class="alert alert-${type==='error'?'error':'success'}">${msg}</div>`;
}

async function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pass  = document.getElementById('loginPassword').value;
  if (!email || !pass) { showAlert('loginAlert','Please fill in all fields.'); return; }
  const btn = event.target;
  btn.disabled = true; btn.textContent = 'Signing in…';
  try {
    const r = await fetch(`${API}?action=login`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,password:pass})});
    const d = await r.json();
    if (d.success) {
      showAlert('loginAlert', `Welcome back, ${d.name}! Redirecting…`, 'success');
      setTimeout(() => { window.location.href = d.role==='admin' ? '../admin/index.php' : '../dashboard.php'; }, 800);
    } else {
      showAlert('loginAlert', d.message || 'Login failed.');
      btn.disabled = false; btn.innerHTML = '🔐 Sign In';
    }
  } catch(e) { showAlert('loginAlert','Network error. Is XAMPP running?'); btn.disabled=false; btn.innerHTML='🔐 Sign In'; }
}

function showForgot() {
  document.getElementById('loginForm').style.display='none';
  document.getElementById('forgotForm').style.display='block';
}
function showLogin() {
  document.getElementById('forgotForm').style.display='none';
  document.getElementById('resetOTPForm').style.display='none';
  document.getElementById('loginForm').style.display='block';
}

async function doRequestReset() {
  const email = document.getElementById('forgotEmail').value.trim();
  if (!email) { showAlert('forgotAlert','Enter your email.'); return; }
  const r = await fetch(`${API}?action=request_reset`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email})});
  const d = await r.json();
  showAlert('forgotAlert', d.message, d.success?'success':'error');
  if (d.success) {
    setTimeout(() => {
      document.getElementById('forgotForm').style.display='none';
      document.getElementById('resetOTPForm').style.display='block';
    }, 1000);
  }
}

async function doResetPassword() {
  const inputs = document.querySelectorAll('#resetOTPInputs .otp-input');
  const otp = [...inputs].map(i=>i.value).join('');
  const email = document.getElementById('forgotEmail').value.trim();
  const pass  = document.getElementById('newPassword').value;
  if (otp.length !== 6 || !pass) { showAlert('resetAlert','Fill in OTP and new password.'); return; }
  const r = await fetch(`${API}?action=reset_password`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,otp,password:pass})});
  const d = await r.json();
  showAlert('resetAlert', d.message, d.success?'success':'error');
  if (d.success) setTimeout(showLogin, 1500);
}

// OTP input auto-advance
document.querySelectorAll('.otp-input').forEach((el,i,arr)=>{
  el.addEventListener('input',()=>{if(el.value&&arr[i+1])arr[i+1].focus()});
  el.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!el.value&&arr[i-1])arr[i-1].focus()});
});

// Enter key
document.addEventListener('keydown', e => {
  if (e.key === 'Enter') { if (document.getElementById('loginForm').style.display !== 'none') doLogin(); }
});
</script>
</body>
</html>
