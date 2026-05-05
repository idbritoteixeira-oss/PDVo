// ============================================================
// PDVo - Sidebar + Painel Assinatura + Bell Notification
// ============================================================

function renderSidebar(activePage) {
  const storageUser = localStorage.getItem('user');
  let user = { nome: 'Usuário', perfil: 'operador' };
  if (storageUser) {
    try { user = JSON.parse(storageUser); } catch (e) {}
  }

  const userRole = user.perfil || 'operador';
  const initials = (user.nome || 'U').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();

  const nav = [
    { href: 'dashboard.html',    icon: 'home',      label: 'Dashboard',     roles: ['admin','gerente','operador'] },
    { href: 'caixa.html',        icon: 'cash',      label: 'Caixa / PDV',   roles: ['admin','gerente','operador'] },
    { href: 'produtos.html',     icon: 'box',       label: 'Produtos',      roles: ['admin','gerente','operador'] },
    { href: 'categorias.html',   icon: 'tag',       label: 'Categorias',    roles: ['admin','gerente'] },
    { href: 'estoque.html',      icon: 'warehouse', label: 'Estoque',       roles: ['admin','gerente'] },
    { href: 'vendas.html',       icon: 'receipt',   label: 'Vendas',        roles: ['admin','gerente'] },
    { href: 'relatorios.html',   icon: 'chart',     label: 'Relatórios',    roles: ['admin','gerente'] },
    { href: 'usuarios.html',     icon: 'users',     label: 'Usuários',      roles: ['admin'] },
    { href: 'configuracoes.html',icon: 'settings',  label: 'Configurações', roles: ['admin'] },
    { href: 'assinatura.html',   icon: 'star',      label: 'Assinatura',    roles: ['admin'] },
  ];

  const icons = {
    home:      `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>`,
    cash:      `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>`,
    box:       `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>`,
    tag:       `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>`,
    warehouse: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>`,
    receipt:   `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>`,
    chart:     `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>`,
    users:     `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>`,
    settings:  `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>`,
    star:      `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>`,
    logout:    `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>`,
    moon:      `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>`,
    sun:       `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z"/>`,
    bell:      `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>`,
    support:   `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>`,
  };

  const svgIcon = (name, size = 17) =>
    `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="flex-shrink:0">${icons[name] || icons.box}</svg>`;

  const filteredNav = nav.filter(item => item.roles.includes(userRole));
  const temaAtual   = document.documentElement.getAttribute('data-tema') || localStorage.getItem('tema') || 'light';

  const sidebarHtml = `
    <style>
      @keyframes bell-ring {
        0%,100%{transform:rotate(0)}
        10%{transform:rotate(14deg)}
        20%{transform:rotate(-12deg)}
        30%{transform:rotate(10deg)}
        40%{transform:rotate(-8deg)}
        50%{transform:rotate(6deg)}
        60%{transform:rotate(-4deg)}
        70%{transform:rotate(2deg)}
        80%{transform:rotate(-2deg)}
        90%{transform:rotate(1deg)}
      }
      .bell-ring-anim { animation: bell-ring 1.4s ease infinite; transform-origin: top center; }
      #bell-btn { position:relative; cursor:pointer; }
      #bell-badge {
        position:absolute; top:-4px; right:-4px;
        background:#ef4444; color:#fff;
        font-size:9px; font-weight:900;
        min-width:16px; height:16px;
        border-radius:99px;
        display:flex; align-items:center; justify-content:center;
        padding:0 3px;
        pointer-events:none;
      }
      /* Notification panel */
      #notif-panel {
        position:fixed;
        top:0; right:0; bottom:0;
        width:min(380px,100vw);
        background:var(--card-bg,#1e293b);
        border-left:1px solid var(--border,rgba(255,255,255,.1));
        z-index:9999;
        transform:translateX(100%);
        transition:transform .3s cubic-bezier(.4,0,.2,1);
        display:flex; flex-direction:column;
        overflow:hidden;
        box-shadow:-8px 0 32px rgba(0,0,0,.4);
      }
      #notif-panel.open { transform:translateX(0); }
      #notif-overlay {
        position:fixed; inset:0; background:rgba(0,0,0,.5);
        z-index:9998; display:none;
      }
      #notif-overlay.open { display:block; }
      .notif-header {
        padding:1.25rem 1.5rem;
        border-bottom:1px solid var(--border,rgba(255,255,255,.08));
        display:flex; align-items:center; justify-content:space-between;
        flex-shrink:0;
      }
      .notif-tabs {
        display:flex; gap:.5rem; padding:.75rem 1rem;
        border-bottom:1px solid var(--border,rgba(255,255,255,.06));
        flex-shrink:0;
      }
      .notif-tab {
        flex:1; padding:.5rem; border-radius:.6rem;
        font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        cursor:pointer; border:1px solid transparent; transition:all .15s;
        text-align:center;
      }
      .notif-tab.active { background:var(--accent,#2563eb); color:#fff; }
      .notif-tab:not(.active) { border-color:rgba(255,255,255,.08); color:var(--text-muted,#94a3b8); }
      .notif-body { flex:1; overflow-y:auto; padding:.75rem; }
      .notif-item {
        padding:.9rem 1rem;
        border-radius:.8rem;
        margin-bottom:.5rem;
        border:1px solid rgba(255,255,255,.06);
        background:rgba(255,255,255,.03);
      }
      .ticket-form { padding:1rem; border-top:1px solid var(--border,rgba(255,255,255,.08)); flex-shrink:0; }
      .ticket-form textarea {
        width:100%; background:var(--input-bg,#0f172a);
        border:1px solid var(--border,rgba(255,255,255,.1));
        color:var(--text-primary,#f1f5f9); border-radius:.6rem;
        padding:.7rem; font-size:.8rem; resize:none; outline:none;
        font-family:inherit;
      }
      .ticket-form textarea:focus { border-color:#3b82f6; }
    </style>

    <button id="menu-btn-off" class="menu-toggle" onclick="toggleSidebar()" aria-label="Menu">
      ${svgIcon('home', 22)}
    </button>

    <aside class="sidebar" id="sidebar">
      <div class="logo">
        <span>🛒 PDVo</span>
        <div style="display:flex;align-items:center;gap:.5rem">
          <!-- Bell button -->
          <button id="bell-btn" onclick="abrirPainelNotif(event)" aria-label="Notificações"
            style="background:none;border:none;padding:.3rem;color:var(--text-muted,#94a3b8);transition:color .15s;"
            onmouseenter="this.style.color='var(--text-primary,#f1f5f9)'"
            onmouseleave="this.style.color='var(--text-muted,#94a3b8)'">
            <span id="bell-icon-wrap">${svgIcon('bell', 18)}</span>
            <span id="bell-badge" style="display:none">0</span>
          </button>
          <button class="md:hidden p-1 opacity-50 hover:opacity-100 transition-opacity" onclick="toggleSidebar()" aria-label="Fechar menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <nav>
        ${filteredNav.map(item => `
          <a href="${item.href}" data-nav="${item.href}" class="${(activePage && item.href === activePage) ? 'active' : ''}">
            ${svgIcon(item.icon)}
            <span>${item.label}</span>
          </a>
        `).join('')}
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar">${initials}</div>
          <div style="min-width:0;flex:1">
            <p style="font-size:.78rem;font-weight:700;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${user.nome || 'Usuário'}</p>
            <p style="font-size:.65rem;color:var(--text-subtle);text-transform:capitalize">${userRole}</p>
          </div>
        </div>
        <button id="tema-toggle-btn" class="tema-toggle" onclick="toggleTema()">
          ${temaIcon(temaAtual)}
        </button>
        <button class="btn-logout" onclick="logout()">
          ${svgIcon('logout', 15)}
          Sair do Sistema
        </button>
      </div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- ===== NOTIFICATION PANEL ===== -->
    <div id="notif-overlay" onclick="fecharPainelNotif()"></div>
    <div id="notif-panel">
      <div class="notif-header">
        <div>
          <p style="font-size:.95rem;font-weight:800;color:var(--text-primary,#f1f5f9)">Notificações</p>
          <p style="font-size:.7rem;color:var(--text-subtle,#64748b)" id="notif-sub">Carregando...</p>
        </div>
        <button onclick="fecharPainelNotif()"
          style="background:none;border:none;cursor:pointer;color:var(--text-muted,#94a3b8);padding:.3rem;">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="notif-tabs">
        <button class="notif-tab active" id="nt-avisos"  onclick="switchNotifTab('avisos')">🔔 Avisos</button>
        <button class="notif-tab"        id="nt-suporte" onclick="switchNotifTab('suporte')">🎟 Suporte</button>
      </div>

      <div class="notif-body" id="notif-body">
        <div style="text-align:center;padding:2rem;color:var(--text-subtle,#64748b);font-size:.8rem;">Carregando...</div>
      </div>

      <!-- Ticket form (shown in suporte tab) -->
      <div class="ticket-form" id="ticket-form-area" style="display:none">
        <p style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted,#94a3b8);margin-bottom:.5rem;">Abrir Novo Ticket</p>
        <input id="ticket-assunto" placeholder="Assunto"
          style="width:100%;background:var(--input-bg,#0f172a);border:1px solid var(--border,rgba(255,255,255,.1));color:var(--text-primary,#f1f5f9);border-radius:.6rem;padding:.6rem .8rem;font-size:.8rem;outline:none;margin-bottom:.5rem;font-family:inherit;"
          onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor=''" />
        <textarea id="ticket-msg" rows="3" placeholder="Descreva seu problema ou dúvida..."></textarea>
        <button onclick="abrirTicket()"
          style="width:100%;margin-top:.5rem;background:#2563eb;color:#fff;border:none;border-radius:.6rem;padding:.65rem;font-size:.75rem;font-weight:700;cursor:pointer;transition:background .15s;"
          onmouseenter="this.style.background='#1d4ed8'" onmouseleave="this.style.background='#2563eb'">
          Enviar Ticket
        </button>
      </div>
    </div>
  `;

  ['sidebar', 'sidebar-overlay', 'menu-btn-off', 'notif-panel', 'notif-overlay'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.remove();
  });

  document.body.insertAdjacentHTML('afterbegin', sidebarHtml);

  // Render painel assinatura e inicializa notificações
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      renderPainelAssinatura();
      inicializarNotificacoes();
    }, { once: true });
  } else {
    requestAnimationFrame(() => {
      renderPainelAssinatura();
      inicializarNotificacoes();
    });
  }
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (!sidebar || !overlay) return;
  const isOpen = sidebar.classList.toggle('open');
  sidebar.style.transform = isOpen ? 'translateX(0)' : 'translateX(-100%)';
  overlay.style.display   = isOpen ? 'block' : 'none';
  document.body.style.overflow = isOpen ? 'hidden' : '';
}

// temaIcon definido em app.js

// ============================================================
// NOTIFICAÇÕES + SUPORTE
// ============================================================
let _notifTab = 'avisos';
let _tickets  = [];

async function inicializarNotificacoes() {
  try {
    const res = await fetch('./api/suporte.php?action=contagem_abertos', { credentials: 'same-origin' });
    if (!res.ok) return;
    const d = await res.json();
    const naoLidos = (d.nao_lidos || 0);
    const badge    = document.getElementById('bell-badge');
    if (badge) {
      if (naoLidos > 0) {
        badge.textContent = naoLidos > 9 ? '9+' : naoLidos;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
    const wrap = document.getElementById('bell-icon-wrap');
    if (wrap) {
      if (naoLidos > 0) {
        wrap.classList.add('bell-ring-anim');
      } else {
        wrap.classList.remove('bell-ring-anim');
      }
    }
  } catch (e) {}
}

function abrirPainelNotif(e) {
  e.stopPropagation();
  const panel   = document.getElementById('notif-panel');
  const overlay = document.getElementById('notif-overlay');
  if (!panel) return;
  panel.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
  switchNotifTab(_notifTab);
}

function fecharPainelNotif() {
  const panel   = document.getElementById('notif-panel');
  const overlay = document.getElementById('notif-overlay');
  if (panel)   panel.classList.remove('open');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

function switchNotifTab(tab) {
  _notifTab = tab;
  ['avisos','suporte'].forEach(t => {
    document.getElementById(`nt-${t}`)?.classList.toggle('active', t === tab);
  });
  const formArea = document.getElementById('ticket-form-area');
  if (formArea) formArea.style.display = tab === 'suporte' ? 'block' : 'none';

  if (tab === 'avisos')  renderAvisos();
  if (tab === 'suporte') carregarTickets();
}

async function renderAvisos() {
  const body = document.getElementById('notif-body');
  if (!body) return;

  let user = null;
  try { user = JSON.parse(localStorage.getItem('user')); } catch(e) {}

  const avisos = [];

  // Notificação de boas-vindas (exibida apenas uma vez)
  const welcomeKey = 'pdvo_welcome_shown_v1';
  if (!localStorage.getItem(welcomeKey)) {
    avisos.push({
      tipo: 'success',
      titulo: `👋 Bem-vindo ao PDVo${user?.nome ? ', ' + user.nome.split(' ')[0] : ''}!`,
      msg: 'Seu sistema está pronto para uso. Acesse <strong>Caixa / PDV</strong> para começar a vender. Em caso de dúvidas, use a aba <strong>Suporte</strong> aqui mesmo.'
    });
    localStorage.setItem(welcomeKey, '1');
  }

  // Vencimento de assinatura
  if (user?.plano?.vencimento) {
    const diff = Math.ceil((new Date(user.plano.vencimento.replace(/-/g,'/')) - new Date()) / 86400000);
    if (diff <= 0) {
      avisos.push({ tipo:'danger', titulo:'Assinatura Expirada', msg:'Sua assinatura venceu. Renove para continuar usando o sistema.' });
    } else if (diff <= 7) {
      avisos.push({ tipo:'warning', titulo:`Assinatura vence em ${diff} dia${diff!==1?'s':''}`, msg:'Acesse "Assinatura" para renovar e não perder o acesso.' });
    } else {
      avisos.push({ tipo:'info', titulo:`Plano ${user.plano.rank||'Ativo'}`, msg:`Seu plano vence em ${diff} dia${diff!==1?'s':''}. Tudo certo!` });
    }
  }

  if (!avisos.length) {
    body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-subtle,#64748b);font-size:.8rem;">Nenhum aviso no momento.</div>';
    document.getElementById('notif-sub').textContent = '0 avisos';
    return;
  }

  const colors = { danger:'#ef4444', warning:'#f59e0b', info:'#3b82f6', success:'#22c55e' };
  const bgs    = { danger:'rgba(239,68,68,.08)', warning:'rgba(245,158,11,.08)', info:'rgba(59,130,246,.08)', success:'rgba(34,197,94,.08)' };

  body.innerHTML = avisos.map(a => `
    <div class="notif-item" style="border-color:${colors[a.tipo]}30;background:${bgs[a.tipo]}">
      <p style="font-size:.8rem;font-weight:700;color:${colors[a.tipo]};margin-bottom:.25rem;">${a.titulo}</p>
      <p style="font-size:.75rem;color:var(--text-muted,#94a3b8);line-height:1.5;">${a.msg}</p>
    </div>
  `).join('');
  document.getElementById('notif-sub').textContent = `${avisos.length} aviso${avisos.length!==1?'s':''}`;
}

async function carregarTickets() {
  const body = document.getElementById('notif-body');
  if (!body) return;
  body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-subtle,#64748b);font-size:.8rem;">Carregando...</div>';
  document.getElementById('notif-sub').textContent = 'Seus tickets de suporte';

  try {
    const res = await fetch('./api/suporte.php?action=listar', { credentials: 'same-origin' });
    if (!res.ok) throw new Error();
    const data = await res.json();
    _tickets = data.tickets || [];

    // Zera badge
    const badge = document.getElementById('bell-badge');
    if (badge) badge.style.display = 'none';

    if (!_tickets.length) {
      body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-subtle,#64748b);font-size:.8rem;">Nenhum ticket aberto.<br><span style="font-size:.7rem">Use o formulário abaixo para abrir um chamado.</span></div>';
      return;
    }

    const stColors = { aguardando_sa:'#f59e0b', aguardando_cliente:'#3b82f6', aberto:'#94a3b8', fechado:'#64748b' };
    const stLabels = { aguardando_sa:'⏳ Aguardando SA', aguardando_cliente:'💬 Resposta recebida!', aberto:'🔵 Aberto', fechado:'✓ Fechado' };

    body.innerHTML = _tickets.map(t => `
      <div class="notif-item" style="${t.status==='aguardando_cliente'?'border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.06)':''}">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.4rem;">
          <p style="font-size:.8rem;font-weight:700;color:var(--text-primary,#f1f5f9);flex:1;">${esc(t.assunto)}</p>
          <span style="font-size:.65rem;font-weight:700;color:${stColors[t.status]||'#94a3b8'};white-space:nowrap;flex-shrink:0;">${stLabels[t.status]||t.status}</span>
        </div>
        <p style="font-size:.72rem;color:var(--text-muted,#94a3b8);margin-bottom:.4rem;">${esc(t.mensagem.substring(0,100))}${t.mensagem.length>100?'...':''}</p>
        ${t.resposta_sa ? `
          <div style="margin-top:.5rem;padding:.6rem .8rem;background:rgba(59,130,246,.1);border-radius:.5rem;border:1px solid rgba(59,130,246,.2);">
            <p style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#60a5fa;margin-bottom:.25rem;">Resposta do Suporte</p>
            <p style="font-size:.75rem;color:var(--text-primary,#f1f5f9);">${esc(t.resposta_sa)}</p>
          </div>
        ` : ''}
        <div style="display:flex;gap:.4rem;margin-top:.6rem;flex-wrap:wrap;">
          ${t.status !== 'fechado' ? `<button onclick="fecharTicketCliente(${t.id})" style="font-size:.65rem;font-weight:700;padding:.25rem .7rem;border-radius:.4rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:var(--text-muted,#94a3b8);cursor:pointer;">Fechar</button>` : `<button onclick="reabrirTicket(${t.id})" style="font-size:.65rem;font-weight:700;padding:.25rem .7rem;border-radius:.4rem;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);color:#60a5fa;cursor:pointer;">Reabrir</button>`}
          <span style="font-size:.65rem;color:var(--text-subtle,#64748b);">${new Date(t.created_at).toLocaleDateString('pt-BR')}</span>
        </div>
      </div>
    `).join('');
  } catch(e) {
    body.innerHTML = '<div style="text-align:center;padding:2rem;color:#f87171;font-size:.8rem;">Erro ao carregar tickets.</div>';
  }
}

async function abrirTicket() {
  const assunto  = document.getElementById('ticket-assunto')?.value.trim();
  const mensagem = document.getElementById('ticket-msg')?.value.trim();
  if (!assunto || !mensagem) { toast('Preencha assunto e mensagem.', 'warning'); return; }

  try {
    const res  = await fetch('./api/suporte.php?action=abrir', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ assunto, mensagem })
    });
    const data = await res.json();
    if (!res.ok) { toast(data.error || 'Erro ao abrir ticket.', 'error'); return; }
    toast('Ticket aberto! Aguarde resposta do suporte.', 'success');
    document.getElementById('ticket-assunto').value = '';
    document.getElementById('ticket-msg').value     = '';
    carregarTickets();
  } catch(e) { toast('Erro de conexão.', 'error'); }
}

async function fecharTicketCliente(id) {
  try {
    await fetch('./api/suporte.php?action=fechar', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ticket_id: id })
    });
    carregarTickets();
  } catch(e) {}
}

async function reabrirTicket(id) {
  try {
    await fetch('./api/suporte.php?action=reabrir', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ticket_id: id })
    });
    carregarTickets();
  } catch(e) {}
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ============================================================
// Painel Assinatura — renderizado centralmente
// ============================================================
function renderPainelAssinatura() {
  const el = document.getElementById('painel-assinatura');
  if (!el) return;

  let user = null;
  try { user = JSON.parse(localStorage.getItem('user')); } catch (e) {}
  if (!user || !user.plano) { el.style.display = 'none'; return; }

  const rank       = user.plano.rank || 'Basic';
  const dataVenc   = user.plano.vencimento;
  const hoje       = new Date();
  const vencimento = new Date((dataVenc || '').replace(/-/g, '/'));

  const diffMs   = vencimento - hoje;
  const diffDays = Math.ceil(diffMs / 86_400_000);
  let pct = Math.min(100, Math.max(0, (diffDays / 30) * 100));

  let barColor = 'linear-gradient(90deg,#2563eb,#7c3aed)';
  let badgeCls = 'badge-info';
  let badgeText = 'Ativo';
  let msgHtml  = `Renova em <strong>${diffDays} dia${diffDays !== 1 ? 's' : ''}</strong>`;

  if (diffDays <= 0) {
    barColor = 'linear-gradient(90deg,#dc2626,#b91c1c)';
    badgeCls = 'badge-danger';
    badgeText = 'Expirado';
    msgHtml  = '<span style="color:#ef4444;font-weight:700">Assinatura expirada — Renove agora</span>';
    pct = 100;
  } else if (diffDays <= 7) {
    barColor = 'linear-gradient(90deg,#f59e0b,#d97706)';
    badgeCls = 'badge-warning';
    badgeText = 'Expira em breve';
    msgHtml  = `Atenção: <strong>${diffDays} dia${diffDays !== 1 ? 's' : ''}</strong> restantes`;
  }

  const vencFormatado = isNaN(vencimento) ? '—' : vencimento.toLocaleDateString('pt-BR');
  const rankColors = { Basic:'#6b7280', PRO:'#2563eb', Super:'#7c3aed', Ultra:'#0891b2' };
  const rankColor  = rankColors[rank] || '#2563eb';

  // Indicações (async-fetched)
  const slug = user.slug || '';
  const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
  const shareUrl = slug ? `${baseUrl}/assinatura.html?ref=${slug}` : '';
  const indHtml = slug ? `
    <div class="plano-card" id="ind-card" style="margin-top:.75rem">
      <p style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:var(--text-muted);text-transform:uppercase;margin-bottom:.5rem">🤝 Indique e Ganhe</p>
      <div id="ind-stats" style="display:flex;gap:.75rem;margin-bottom:.6rem">
        <div style="flex:1;background:rgba(255,255,255,.04);border-radius:.5rem;padding:.4rem .6rem;text-align:center">
          <p style="font-size:1rem;font-weight:900;color:#60a5fa" id="ind-stat-total">—</p>
          <p style="font-size:.6rem;color:var(--text-subtle)">indicações</p>
        </div>
        <div style="flex:1;background:rgba(255,255,255,.04);border-radius:.5rem;padding:.4rem .6rem;text-align:center">
          <p style="font-size:1rem;font-weight:900;color:#4ade80" id="ind-stat-validados">—</p>
          <p style="font-size:.6rem;color:var(--text-subtle)">validadas</p>
        </div>
        <div style="flex:1;background:rgba(255,255,255,.04);border-radius:.5rem;padding:.4rem .6rem;text-align:center">
          <p style="font-size:.85rem;font-weight:900;color:#fbbf24" id="ind-stat-valor">—</p>
          <p style="font-size:.6rem;color:var(--text-subtle)">ganho</p>
        </div>
      </div>
      <p style="font-size:.7rem;color:var(--text-muted);margin:0 0 .35rem">Link de indicação:</p>
      <div style="display:flex;gap:.4rem;align-items:center;margin-bottom:.4rem">
        <code style="flex:1;background:var(--input-bg,#0f172a);border:1px solid rgba(255,255,255,.1);border-radius:.5rem;padding:.35rem .6rem;font-size:.65rem;color:#60a5fa;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${shareUrl}">${shareUrl}</code>
        <button onclick="navigator.clipboard.writeText('${shareUrl}').then(()=>typeof toast==='function'?toast('Link copiado!','success'):alert('Copiado!'))"
          style="background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.25);color:#60a5fa;border-radius:.5rem;padding:.35rem .6rem;font-size:.65rem;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0">
          Copiar
        </button>
      </div>
      <a href="https://wa.me/?text=${encodeURIComponent('Experimente o PDVo! Use meu link para se cadastrar: ' + shareUrl)}" target="_blank" rel="noopener"
        style="display:flex;align-items:center;justify-content:center;gap:.4rem;width:100%;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.2);color:#4ade80;border-radius:.5rem;padding:.4rem .7rem;font-size:.7rem;font-weight:700;text-decoration:none;margin-bottom:.4rem">
        📲 Compartilhar no WhatsApp
      </a>
      <a href="assinatura.html" style="display:block;text-align:center;font-size:.68rem;color:var(--text-subtle);text-decoration:underline;">Ver minhas indicações →</a>
    </div>
  ` : '';

  el.innerHTML = `
    <div class="plano-card mt-6 mb-8">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.75rem">
        <div>
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem">
            <span style="font-size:.7rem;font-weight:800;letter-spacing:.08em;color:var(--text-muted);text-transform:uppercase">Plano</span>
            <span style="font-size:1rem;font-weight:900;color:${rankColor};letter-spacing:-.01em">${rank}</span>
          </div>
          <p style="font-size:.8rem;color:var(--text-muted);margin:0">${msgHtml}</p>
        </div>
        <span class="badge ${badgeCls}" style="flex-shrink:0;margin-top:2px">${badgeText}</span>
      </div>

      <div class="plano-progress">
        <div class="plano-progress-bar" style="width:0%;background:${barColor}" id="plano-bar-inner"></div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-subtle)">Vencimento</span>
        <span style="font-size:.78rem;font-weight:700;color:var(--text-muted)">${vencFormatado}</span>
      </div>
    </div>
    ${indHtml}
  `;

  requestAnimationFrame(() => {
    setTimeout(() => {
      const bar = document.getElementById('plano-bar-inner');
      if (bar) bar.style.width = pct + '%';
    }, 300);
  });

  // Fetch referral stats async if slug is present
  if (slug) {
    (async () => {
      try {
        const res  = await fetch(`./api/assinatura.php?action=minhas_indicacoes&slug=${encodeURIComponent(slug)}`, { credentials: 'same-origin' });
        if (!res.ok) return;
        const d = await res.json();
        if (!d.ok) return;
        const setEl = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
        setEl('ind-stat-total',    d.total    ?? 0);
        setEl('ind-stat-validados', d.validados ?? 0);
        setEl('ind-stat-valor',    'R$ ' + parseFloat(d.valor_ganho || 0).toFixed(2).replace('.', ','));
      } catch (_) {}
    })();
  }
}
