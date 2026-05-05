// ============================================================
// PDVo - JavaScript Global (app.js)
// ============================================================

const API = {
  BASE: './api',

  async request(method, endpoint, body = null) {
    const isFormData = body instanceof FormData;
    const opts = {
      method,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    };
    if (body) {
      if (isFormData) {
        opts.body = body;
      } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
      }
    }
    try {
      const res = await fetch(API.BASE + endpoint, opts);
      if (res.status === 401 && !endpoint.includes('login.php')) {
        localStorage.removeItem('user');
        window.location.href = 'login.html';
        return null;
      }
      const json = await res.json();
      return { ok: res.ok, status: res.status, data: json };
    } catch (e) {
      console.error('Erro API:', e);
      return { ok: false, data: { error: 'Erro de conexão com o servidor.' } };
    }
  },

  get:    (ep)       => API.request('GET',    ep),
  post:   (ep, body) => API.request('POST',   ep, body),
  put:    (ep, body) => API.request('PUT',    ep, body),
  delete: (ep)       => API.request('DELETE', ep),
};

// ---- Tema ----
function applyTema(tema) {
  const t = tema || 'light';
  document.documentElement.setAttribute('data-tema', t);
  localStorage.setItem('tema', t);
}

function toggleTema() {
  const atual = document.documentElement.getAttribute('data-tema') || 'light';
  const novo  = atual === 'dark' ? 'light' : 'dark';
  applyTema(novo);
  // Salva preferência no servidor sem bloquear
  API.post('/configuracoes.php', { tema: novo }).catch(() => {});
  // Atualiza ícone do botão na sidebar
  const btn = document.getElementById('tema-toggle-btn');
  if (btn) btn.innerHTML = temaIcon(novo);
}

function temaIcon(tema) {
  if (tema === 'dark') {
    return `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z"/></svg> Modo Claro`;
  }
  return `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg> Modo Escuro`;
}

// ---- Toast ----
function toast(msg, type = 'info', duration = 4000) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2';
    document.body.appendChild(container);
  }
  const colors = {
    success: 'bg-green-600',
    error:   'bg-red-600',
    warning: 'bg-yellow-500',
    info:    'bg-blue-600',
  };
  const t = document.createElement('div');
  t.className = `${colors[type] || colors.info} text-white px-5 py-3 rounded-xl shadow-2xl text-sm font-bold min-w-[200px] transition-all duration-300`;
  t.textContent = msg;
  container.appendChild(t);
  setTimeout(() => {
    t.classList.add('opacity-0', 'translate-x-10');
    setTimeout(() => t.remove(), 300);
  }, duration);
}

// ---- Formatadores ----
function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return isNaN(d) ? dateStr : d.toLocaleString('pt-BR');
}

function formatNum(n, decimals = 2) {
  const num = parseFloat(n || 0);
  if (num % 1 === 0) return num.toString();
  return num.toFixed(decimals);
}

// ---- Auth check ----
async function checkAuth() {
  const res = await API.get('/me.php');
  if (!res || !res.ok) {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
    return null;
  }

  const user = res.data.user;
  localStorage.setItem('user', JSON.stringify(user));

  // Aplica tema salvo na sessão ou no localStorage
  const tema = res.data.tema || localStorage.getItem('tema') || 'light';
  applyTema(tema);

  const nameEl = document.getElementById('user-name');
  if (nameEl) nameEl.textContent = user.nome || 'Usuário';

  const roleEl = document.getElementById('user-role');
  if (roleEl) roleEl.textContent = user.perfil === 'admin' ? 'Acesso Total' : (user.perfil === 'gerente' ? 'Gerente' : 'Operador');

  return user;
}

// ---- Logout ----
async function logout() {
  try { await API.post('/logout.php'); } catch (_) {}
  localStorage.removeItem('user');
  localStorage.removeItem('tema');
  window.location.href = 'login.html';
}

// ---- Active nav link ----
function setActiveNav() {
  const path = window.location.pathname.split('/').pop() || 'dashboard.html';
  document.querySelectorAll('[data-nav]').forEach(link => {
    const navItem = link.getAttribute('data-nav');
    link.classList.remove('active');
    if (navItem === path) link.classList.add('active');
  });
}

// ---- Init ----
document.addEventListener('DOMContentLoaded', async () => {
  // Aplica o tema salvo imediatamente antes de qualquer fetch (evita flash)
  const temaLocal = localStorage.getItem('tema') || 'light';
  applyTema(temaLocal);

  if (!window.location.pathname.includes('login.html')) {
    await checkAuth();
    setActiveNav();
  }
});
