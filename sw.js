// PDVo — Service Worker v1
// Estratégia: Cache-first para assets estáticos, Network-first para API

const CACHE_NAME   = 'pdvo-cache-v1';
const OFFLINE_PAGE = './login.html';

const PRECACHE = [
  './login.html',
  './dashboard.html',
  './caixa.html',
  './produtos.html',
  './categorias.html',
  './estoque.html',
  './vendas.html',
  './configuracoes.html',
  './usuarios.html',
  './assets/css/pdv.css',
  './manifest.json',
];

// ── INSTALL: pré-caching dos assets principais ──────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE).catch(() => {}))
      .then(() => self.skipWaiting())
  );
});

// ── ACTIVATE: limpa caches antigos ──────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// ── FETCH: intercepta requisições ───────────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignora extensões do Chrome e outros protocolos
  if (!url.protocol.startsWith('http')) return;

  // ── API: Network-first (sem cache) ──
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(request)
        .then(res => {
          // Para GET de config pública, cacheia brevemente
          if (request.method === 'GET' && res.ok) {
            const clone = res.clone();
            caches.open(CACHE_NAME).then(c => c.put(request, clone));
          }
          return res;
        })
        .catch(() => {
          // Se offline e for GET, tenta cache
          if (request.method === 'GET') {
            return caches.match(request).then(cached =>
              cached || new Response(
                JSON.stringify({ error: 'Sem conexão. Verifique sua internet.' }),
                { status: 503, headers: { 'Content-Type': 'application/json' } }
              )
            );
          }
          return new Response(
            JSON.stringify({ error: 'Sem conexão. Tente novamente.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
          );
        })
    );
    return;
  }

  // ── CDN externo: cache-first ──
  if (url.hostname !== self.location.hostname) {
    event.respondWith(
      caches.match(request).then(cached => {
        if (cached) return cached;
        return fetch(request).then(res => {
          if (res.ok) {
            const clone = res.clone();
            caches.open(CACHE_NAME).then(c => c.put(request, clone));
          }
          return res;
        }).catch(() => cached);
      })
    );
    return;
  }

  // ── Assets locais: stale-while-revalidate ──
  event.respondWith(
    caches.open(CACHE_NAME).then(cache =>
      cache.match(request).then(cached => {
        const fetchPromise = fetch(request).then(res => {
          if (res.ok) cache.put(request, res.clone());
          return res;
        }).catch(() => null);
        return cached || fetchPromise || caches.match(OFFLINE_PAGE);
      })
    )
  );
});

// ── PUSH: notificações (preparado para uso futuro) ──────────
self.addEventListener('push', event => {
  if (!event.data) return;
  let payload;
  try { payload = event.data.json(); } catch { payload = { title: 'PDVo', body: event.data.text() }; }
  event.waitUntil(
    self.registration.showNotification(payload.title || 'PDVo', {
      body:    payload.body || '',
      icon:    './assets/icons/icon.php?size=192',
      badge:   './assets/icons/icon.php?size=96',
      vibrate: [100, 50, 100],
      data:    { url: payload.url || './dashboard.html' },
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data?.url || './dashboard.html')
  );
});
