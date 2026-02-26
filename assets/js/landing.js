// ==========================================
// SmartEdge ML Sandbox — Landing Page JS
// ==========================================

(function() {
  'use strict';

  // Particle Background
  const canvas = document.getElementById('particleCanvas');
  if (!canvas) return;

  const ctx    = canvas.getContext('2d');
  let W, H, particles = [], animId;

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function Particle() {
    this.reset();
  }
  Particle.prototype.reset = function() {
    this.x  = Math.random() * W;
    this.y  = Math.random() * H;
    this.vx = (Math.random() - 0.5) * 0.4;
    this.vy = (Math.random() - 0.5) * 0.4;
    this.r  = Math.random() * 1.5 + 0.5;
    this.alpha = Math.random() * 0.5 + 0.1;
    this.color = Math.random() > 0.5 ? '#06d6a0' : '#a78bfa';
  };
  Particle.prototype.update = function() {
    this.x += this.vx; this.y += this.vy;
    if (this.x < 0 || this.x > W || this.y < 0 || this.y > H) this.reset();
  };
  Particle.prototype.draw = function() {
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
    ctx.fillStyle = this.color;
    ctx.globalAlpha = this.alpha;
    ctx.fill();
  };

  function initParticles(n = 120) {
    particles = Array.from({length: n}, () => new Particle());
  }

  function drawConnections() {
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 100) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = '#06d6a0';
          ctx.globalAlpha = (1 - dist / 100) * 0.08;
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }
  }

  function animate() {
    ctx.clearRect(0, 0, W, H);
    ctx.globalAlpha = 1;
    particles.forEach(p => { p.update(); p.draw(); });
    drawConnections();
    animId = requestAnimationFrame(animate);
  }

  window.addEventListener('resize', () => { resize(); initParticles(); });
  resize(); initParticles(); animate();

  // ── Mini Charts on Hero ────────────────────────────────────
  function drawMiniChart(canvasId, data, color) {
    const cv = document.getElementById(canvasId);
    if (!cv) return;
    const c = cv.getContext('2d');
    const W = cv.width, H = cv.height;
    const min = Math.min(...data), max = Math.max(...data);
    c.clearRect(0, 0, W, H);
    c.fillStyle = 'rgba(15,15,35,.6)';
    c.fillRect(0, 0, W, H);
    c.beginPath();
    data.forEach((v, i) => {
      const x = (i / (data.length - 1)) * W;
      const y = H - ((v - min) / (max - min + 0.001)) * (H - 8) - 4;
      i === 0 ? c.moveTo(x, y) : c.lineTo(x, y);
    });
    c.strokeStyle = color;
    c.lineWidth = 1.5;
    c.stroke();
  }

  function generateLossCurve(n = 40) {
    let v = 0.98;
    return Array.from({length: n}, () => { v = Math.max(0.05, v - 0.022 * Math.random() * 2); return v; });
  }
  function generateAccCurve(n = 40) {
    let v = 0.1;
    return Array.from({length: n}, () => { v = Math.min(0.97, v + 0.02 * Math.random() * 2); return v; });
  }

  drawMiniChart('miniLossChart', generateLossCurve(), '#ef4444');
  drawMiniChart('miniAccChart', generateAccCurve(), '#06d6a0');

  // ── Demo Sensor Animation ─────────────────────────────────
  setInterval(() => {
    const water = Math.round(60 + 20 * Math.sin(Date.now() / 4000) + Math.random() * 8);
    const mic   = Math.round(38 + 15 * Math.random());
    const servo = Math.round(90 + 45 * Math.sin(Date.now() / 6000));

    const waterEl = document.getElementById('demoWater');
    const micEl   = document.getElementById('demoMic');
    const servoEl = document.getElementById('demoServo');
    const fanEl   = document.getElementById('demoFan');

    if (waterEl) waterEl.textContent = water + '%';
    if (micEl)   micEl.textContent   = mic + 'dB';
    if (servoEl) servoEl.textContent = servo + '°';
    if (fanEl)   { fanEl.textContent = mic > 50 ? 'ON' : 'OFF'; fanEl.className = 'ms-val ' + (mic > 50 ? 'success' : ''); }
  }, 1800);

  // ── MQTT Node Animation on Hero ───────────────────────────
  let step = 0;
  const mpNodes = document.querySelectorAll('.mp-node');
  const mpArrows = document.querySelectorAll('.mp-arrow');

  function animatePipeline() {
    mpNodes.forEach((n, i) => {
      n.classList.remove('mp-active', 'running');
      if (i < step) n.classList.add('mp-active');
      if (i === step) n.classList.add('running');
    });
    mpArrows.forEach((a, i) => {
      a.classList.toggle('active', i < step);
    });
    step = (step + 1) % (mpNodes.length + 1);
  }

  setInterval(animatePipeline, 1200);

  // ── Pipeline Flow Entrance Animation ─────────────────────
  const steps = document.querySelectorAll('.pf-step');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const delay = parseInt(e.target.dataset.delay || 0);
        setTimeout(() => {
          e.target.style.opacity = '1';
          e.target.style.transform = 'translateY(0)';
        }, delay * 120);
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.2 });

  steps.forEach(s => {
    s.style.opacity = '0';
    s.style.transform = 'translateY(30px)';
    s.style.transition = 'all .5s ease';
    observer.observe(s);
  });

  // Smooth scroll for nav links
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      e.preventDefault();
      const target = document.querySelector(a.getAttribute('href'));
      if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
  });

})();
