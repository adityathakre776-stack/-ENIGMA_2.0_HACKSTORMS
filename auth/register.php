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
<title>Register — SmartEdge ML Sandbox</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
body{background:var(--bg-dark);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:40px 20px}
.register-wrap{display:grid;grid-template-columns:1fr 1fr;gap:60px;max-width:960px;width:100%;align-items:center}
.reg-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:44px}
.auth-brand{display:flex;align-items:center;gap:10px;margin-bottom:32px;font-size:1.1rem;font-weight:800;text-decoration:none;color:var(--text-primary)}
.auth-title{font-size:1.75rem;font-weight:800;margin-bottom:8px}
.auth-sub{color:var(--text-secondary);font-size:.875rem;margin-bottom:28px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.otp-box{background:rgba(6,214,160,.06);border:1px solid rgba(6,214,160,.3);border-radius:var(--radius);padding:24px;text-align:center;display:none}
.otp-inputs{display:flex;gap:10px;justify-content:center;margin:16px 0}
.otp-input{width:50px;height:56px;text-align:center;font-size:1.4rem;font-weight:700;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius);color:var(--text-primary);outline:none;transition:var(--transition);font-family:'JetBrains Mono',monospace}
.otp-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.strength-bar{height:4px;border-radius:2px;background:var(--bg-input);margin-top:8px;overflow:hidden}
.strength-fill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s}
.reg-info{padding:20px}
.reg-info h2{font-size:1.6rem;margin-bottom:16px}
.reg-info p{color:var(--text-secondary);margin-bottom:24px}
.reg-steps{display:flex;flex-direction:column;gap:16px}
.rs-item{display:flex;gap:16px;align-items:flex-start}
.rs-num{
  width:36px;height:36px;border-radius:50%;
  background:var(--grad-primary);color:#000;font-weight:800;font-size:.9rem;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.rs-text h4{margin-bottom:4px;font-size:.9rem}
.rs-text p{color:var(--text-muted);font-size:.8rem}
@media(max-width:768px){.register-wrap{grid-template-columns:1fr}.reg-info{display:none}.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="register-wrap">
  <div class="reg-info fade-in">
    <a href="../index.php" class="auth-brand" style="display:inline-flex;margin-bottom:40px">
      <div class="brand-logo">🧠</div>
      SmartEdge <span class="gradient-text">ML</span>
    </a>
    <h2>Start Your ML <span class="gradient-text">Journey</span></h2>
    <p>Join hundreds of students learning Machine Learning through real IoT hardware experiments.</p>
    <div class="reg-steps">
      <div class="rs-item">
        <div class="rs-num">1</div>
        <div class="rs-text">
          <h4>Create Your Account</h4>
          <p>Register with email — OTP verification keeps it secure</p>
        </div>
      </div>
      <div class="rs-item">
        <div class="rs-num">2</div>
        <div class="rs-text">
          <h4>Register Your ESP32</h4>
          <p>Link your hardware device with a unique Device ID</p>
        </div>
      </div>
      <div class="rs-item">
        <div class="rs-num">3</div>
        <div class="rs-text">
          <h4>Start Experimenting</h4>
          <p>Stream sensor data, train ML models, control hardware — all visually!</p>
        </div>
      </div>
    </div>
  </div>

  <div class="reg-card fade-in" style="animation-delay:.1s">
    <a href="../index.php" class="auth-brand">
      <div class="brand-logo">🧠</div>
      SmartEdge <span class="gradient-text">ML</span>
    </a>

    <!-- REGISTER FORM -->
    <div id="registerForm">
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-sub">Join the ML Learning Sandbox</p>
      <div id="regAlert"></div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" id="regName" placeholder="John Doe">
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-control" id="regRole">
            <option value="student">Student</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="regEmail" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" id="regPass" placeholder="Min 8 characters" oninput="checkStrength(this.value)">
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div id="strengthText" style="font-size:.75rem;color:var(--text-muted);margin-top:4px"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="regPassConfirm" placeholder="Re-enter password">
      </div>
      <div class="form-group" style="display:flex;align-items:flex-start;gap:10px">
        <input type="checkbox" id="regTerms" style="width:auto;margin-top:2px">
        <label for="regTerms" style="font-size:.8rem;color:var(--text-muted);cursor:pointer">
          I agree to SmartEdge Terms of Service and understand this is an educational IoT platform
        </label>
      </div>
      <button class="btn btn-primary" style="width:100%" onclick="doRegister()">🚀 Create Account</button>
      <p style="text-align:center;margin-top:20px;font-size:.85rem;color:var(--text-muted)">
        Already have an account? <a href="login.php" style="color:var(--primary)">Sign In</a>
      </p>
    </div>

    <!-- OTP VERIFICATION -->
    <div class="otp-box" id="otpBox">
      <div style="font-size:48px;margin-bottom:8px">📧</div>
      <h3>Verify Your Email</h3>
      <p style="color:var(--text-secondary);font-size:.875rem;margin-bottom:4px">We sent a 6-digit OTP to</p>
      <strong id="otpEmailDisplay" style="color:var(--primary)"></strong>
      <div id="otpAlert"></div>
      <div class="otp-inputs">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
        <input class="otp-input" maxlength="1" type="text">
      </div>
      <button class="btn btn-primary" style="width:100%" onclick="doVerifyOTP()">✅ Verify & Continue</button>
      <button class="btn btn-outline" style="width:100%;margin-top:10px" onclick="resendOTP()">🔄 Resend OTP</button>
    </div>

    <!-- SUCCESS -->
    <div id="successBox" style="display:none;text-align:center;padding:20px">
      <div style="font-size:64px;margin-bottom:16px">🎉</div>
      <h2>You're In!</h2>
      <p style="color:var(--text-secondary);margin-bottom:24px">Account verified successfully. Redirecting to login…</p>
      <div class="spinner"></div>
    </div>
  </div>
</div>

<script>
const API = '../api/index.php';
let registeredEmail = '';

function showAlert(id, msg, type='error') {
  document.getElementById(id).innerHTML = `<div class="alert alert-${type==='error'?'error':'success'}" style="margin:12px 0">${msg}</div>`;
}

function checkStrength(val) {
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');
  const levels = [
    {w:'25%', c:'#ef4444', t:'Weak'},
    {w:'50%', c:'#fbbf24', t:'Fair'},
    {w:'75%', c:'#38bdf8', t:'Good'},
    {w:'100%', c:'#06d6a0', t:'Strong'},
  ];
  const l = levels[Math.max(0, score-1)] || levels[0];
  fill.style.width = l.w; fill.style.background = l.c;
  text.textContent = val ? `Strength: ${l.t}` : '';
}

async function doRegister() {
  const name  = document.getElementById('regName').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const pass  = document.getElementById('regPass').value;
  const passC = document.getElementById('regPassConfirm').value;
  const role  = document.getElementById('regRole').value;
  const terms = document.getElementById('regTerms').checked;

  if (!name||!email||!pass||!passC) { showAlert('regAlert','All fields are required.'); return; }
  if (pass !== passC) { showAlert('regAlert','Passwords do not match.'); return; }
  if (pass.length < 8) { showAlert('regAlert','Password must be at least 8 characters.'); return; }
  if (!terms) { showAlert('regAlert','Please accept terms to continue.'); return; }

  const btn = event.target; btn.disabled=true; btn.textContent='Creating…';
  try {
    const r = await fetch(`${API}?action=register`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({name, email, password:pass, role})
    });
    const d = await r.json();
    if (d.success) {
      registeredEmail = email;
      document.getElementById('registerForm').style.display='none';
      document.getElementById('otpBox').style.display='block';
      document.getElementById('otpEmailDisplay').textContent = email;
    } else {
      showAlert('regAlert', d.message || 'Registration failed.');
      btn.disabled=false; btn.textContent='🚀 Create Account';
    }
  } catch(e) { showAlert('regAlert','Network error.'); btn.disabled=false; btn.textContent='🚀 Create Account'; }
}

async function doVerifyOTP() {
  const inputs = document.querySelectorAll('#otpBox .otp-input');
  const otp = [...inputs].map(i=>i.value).join('');
  if (otp.length !== 6) { showAlert('otpAlert','Enter the 6-digit OTP.'); return; }
  const r = await fetch(`${API}?action=verify_otp`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:registeredEmail,otp})});
  const d = await r.json();
  if (d.success) {
    document.getElementById('otpBox').style.display='none';
    document.getElementById('successBox').style.display='block';
    setTimeout(() => window.location.href='login.php', 2500);
  } else { showAlert('otpAlert', d.message, 'error'); }
}

async function resendOTP() {
  const r = await fetch(`${API}?action=request_reset`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:registeredEmail})});
  showAlert('otpAlert','New OTP sent!','success');
}

// OTP input auto-advance
document.querySelectorAll('.otp-input').forEach((el,i,arr)=>{
  el.addEventListener('input',()=>{if(el.value&&arr[i+1])arr[i+1].focus()});
  el.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!el.value&&arr[i-1])arr[i-1].focus()});
});
</script>
</body>
</html>
