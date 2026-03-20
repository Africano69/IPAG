// ============================================================
//  main.js — JavaScript global do sistema
// ============================================================

// ── SIDEBAR TOGGLE ────────────────────────────────────────────
const wrapper     = document.getElementById('wrapper');
const sidebar     = document.getElementById('sidebar');
const sidebarTgl  = document.getElementById('sidebarToggle');
const topbarTgl   = document.getElementById('topbarToggle');

function isMobile() { return window.innerWidth <= 768; }

// Desktop: recolhe / expande
if (sidebarTgl) {
  sidebarTgl.addEventListener('click', () => {
    if (!isMobile()) {
      sidebar.classList.toggle('collapsed');
      wrapper.classList.toggle('collapsed');
      localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
    }
  });
}

// Mobile: abre overlay
let overlay = null;
if (topbarTgl) {
  topbarTgl.addEventListener('click', () => {
    if (isMobile()) {
      sidebar.classList.toggle('mobile-open');
      if (sidebar.classList.contains('mobile-open')) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', closeMobileSidebar);
        document.body.appendChild(overlay);
      } else {
        closeMobileSidebar();
      }
    }
  });
}

function closeMobileSidebar() {
  sidebar.classList.remove('mobile-open');
  if (overlay) { overlay.remove(); overlay = null; }
}

// Restaura estado recolhido (desktop)
(function restoreState() {
  if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
    sidebar.classList.add('collapsed');
    wrapper.classList.add('collapsed');
  }
})();

// ── TOAST ─────────────────────────────────────────────────────
function toast(msg, type = 'info', duration = 3500) {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const t = document.createElement('div');
  const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="fa ${icons[type] || icons.info}"></i> ${escapeHtml(msg)}`;
  container.appendChild(t);
  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transform = 'translateX(60px)';
    t.style.transition = 'opacity .3s, transform .3s';
    setTimeout(() => t.remove(), 300);
  }, duration);
}

// ── HELPERS ───────────────────────────────────────────────────
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));
}

// Fecha modais ao clicar fora (redundante mas seguro)
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});

// Fecha modais com ESC
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
  }
});

// ── AUTO-HIDE ALERTS ──────────────────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 5000);
});

// ── CONFIRM delete via data attributes ────────────────────────
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });
});
