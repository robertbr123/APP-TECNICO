/**
 * Service Worker - PWABuilder / Workbox
 * Ondeline Tech - App do Técnico
 * Versão: v3.0.0
 */

importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js');

const APP_VERSION = 'v3.0.3';

// ─────────────────────────────────────────────
// Configuração do Workbox
// ─────────────────────────────────────────────
workbox.setConfig({ debug: false });

const { registerRoute, setCatchHandler } = workbox.routing;

const {
    NetworkFirst,
    StaleWhileRevalidate,
    CacheFirst
} = workbox.strategies;

const { CacheableResponsePlugin } = workbox.cacheableResponse;
const { ExpirationPlugin } = workbox.expiration;
const { precacheAndRoute, cleanupOutdatedCaches, matchPrecache } = workbox.precaching;
const { BackgroundSyncPlugin } = workbox.backgroundSync;

// Limpa caches de versões antigas
cleanupOutdatedCaches();

// ─────────────────────────────────────────────
// Precache - Assets estáticos críticos
// ─────────────────────────────────────────────
precacheAndRoute([
    { url: '/offline.php',              revision: APP_VERSION },
    { url: '/manifest.json',            revision: APP_VERSION },
    { url: '/logo.png',                 revision: APP_VERSION },
    { url: '/icons/icon-192.png',       revision: APP_VERSION },
    { url: '/icons/icon-512.png',       revision: APP_VERSION },
    { url: '/css/transitions.css',      revision: APP_VERSION },
    { url: '/js/api.js',                revision: APP_VERSION },
    { url: '/js/app.js',                revision: APP_VERSION },
    { url: '/js/animations.js',         revision: APP_VERSION },
    { url: '/js/ui-enhancements.js',    revision: APP_VERSION },
    { url: '/js/feedback.js',           revision: APP_VERSION },
    { url: '/js/utils.js',              revision: APP_VERSION },
    { url: '/js/geolocation.js',        revision: APP_VERSION },
    { url: '/js/components.js',         revision: APP_VERSION },
    { url: '/js/scanner.js',            revision: APP_VERSION },
    { url: '/js/pages/login.js',        revision: APP_VERSION },
    { url: '/js/pages/dashboard.js',    revision: APP_VERSION },
    { url: '/js/pages/cadastro.js',     revision: APP_VERSION },
    { url: '/js/pages/consulta.js',     revision: APP_VERSION },
    { url: '/js/pages/detalhes.js',     revision: APP_VERSION },
    { url: '/js/pages/vincular.js',     revision: APP_VERSION },
    { url: '/js/pages/ajustes.js',      revision: APP_VERSION },
    { url: '/js/pages/historico.js',    revision: APP_VERSION },
    { url: '/js/pages/ordens.js',       revision: APP_VERSION },
    { url: '/js/pages/relatorios.js',   revision: APP_VERSION }
]);

// ─────────────────────────────────────────────
// Estratégia: Fontes Google - CacheFirst (30 dias)
// ─────────────────────────────────────────────
registerRoute(
    ({ url }) =>
        url.origin === 'https://fonts.googleapis.com' ||
        url.origin === 'https://fonts.gstatic.com',
    new CacheFirst({
        cacheName: 'google-fonts',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 20, maxAgeSeconds: 30 * 24 * 60 * 60 })
        ]
    })
);

// ─────────────────────────────────────────────
// Estratégia: Imagens locais - CacheFirst (7 dias)
// ─────────────────────────────────────────────
registerRoute(
    ({ request }) => request.destination === 'image',
    new CacheFirst({
        cacheName: 'images-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 150, maxAgeSeconds: 7 * 24 * 60 * 60 })
        ]
    })
);

// ─────────────────────────────────────────────
// Estratégia: CSS e JS - StaleWhileRevalidate
// ─────────────────────────────────────────────
registerRoute(
    ({ request }) =>
        request.destination === 'style' ||
        request.destination === 'script',
    new StaleWhileRevalidate({
        cacheName: 'assets-cache',
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 7 * 24 * 60 * 60 })
        ]
    })
);

// ─────────────────────────────────────────────
// Estratégia: API - NetworkFirst (fallback cache 24h)
// ─────────────────────────────────────────────
registerRoute(
    ({ url }) => url.pathname.startsWith('/api/'),
    new NetworkFirst({
        cacheName: 'api-cache',
        networkTimeoutSeconds: 3,
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 24 * 60 * 60 })
        ]
    })
);

// ─────────────────────────────────────────────
// Estratégia: Páginas PHP - NetworkFirst (fallback cache)
// ─────────────────────────────────────────────
registerRoute(
    ({ request }) => request.mode === 'navigate',
    new NetworkFirst({
        cacheName: 'pages-cache',
        networkTimeoutSeconds: 3,
        plugins: [
            new CacheableResponsePlugin({ statuses: [0, 200] }),
            new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 24 * 60 * 60 })
        ]
    })
);

// ─────────────────────────────────────────────
// Fallback offline para navegação
// ─────────────────────────────────────────────
setCatchHandler(async ({ request }) => {
    if (request.destination === 'document') {
        return matchPrecache('/offline.php');
    }
    if (request.destination === 'image') {
        return new Response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#f0f0f0"/><text x="100" y="100" text-anchor="middle" fill="#999" font-size="14">Offline</text></svg>',
            { headers: { 'Content-Type': 'image/svg+xml' } }
        );
    }
    return Response.error();
});

// ─────────────────────────────────────────────
// Background Sync - Fila de requisições offline
// ─────────────────────────────────────────────
const bgSyncPlugin = new BackgroundSyncPlugin('ondeline-sync-queue', {
    maxRetentionTime: 24 * 60, // 24 horas em minutos
    onSync: async ({ queue }) => {
        let entry;
        while ((entry = await queue.shiftRequest())) {
            try {
                await fetch(entry.request);
            } catch (error) {
                await queue.unshiftRequest(entry);
                throw error;
            }
        }
    }
});

// Sync para POST de cadastro e OS (quando offline)
// Captura requests que falharam e reexecuta automaticamente ao reconectar
registerRoute(
    ({ url, request }) =>
        request.method === 'POST' && (
            url.pathname.startsWith('/api/clients') ||      // cadastro de clientes
            url.pathname.startsWith('/api/work-orders') ||  // ordens de serviço
            url.pathname.startsWith('/api/checklist') ||    // checklists
            url.pathname.startsWith('/api/inventory') ||    // estoque
            url.pathname.startsWith('/api/time-clock')      // ponto
        ),
    new NetworkFirst({
        cacheName: 'post-cache',
        plugins: [bgSyncPlugin]
    }),
    'POST'
);

// ─────────────────────────────────────────────
// Periodic Background Sync
// ─────────────────────────────────────────────
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
    if (event.tag === 'sync-location') {
        event.waitUntil(syncLocation());
    }
});

async function syncData() {
    try {
        const allClients = await self.clients.matchAll();
        allClients.forEach(client => {
            client.postMessage({ type: 'PERIODIC_SYNC', tag: 'sync-data' });
        });
    } catch (e) {}
}

async function syncLocation() {
    try {
        const allClients = await self.clients.matchAll();
        allClients.forEach(client => {
            client.postMessage({ type: 'PERIODIC_SYNC', tag: 'sync-location' });
        });
    } catch (e) {}
}

// ─────────────────────────────────────────────
// Skip Waiting / Activate imediato
// ─────────────────────────────────────────────
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// ─────────────────────────────────────────────
// Mensagens do cliente
// ─────────────────────────────────────────────
self.addEventListener('message', (event) => {
    if (!event.data) return;

    switch (event.data.type) {
        case 'GET_VERSION':
            event.ports[0]?.postMessage({ version: APP_VERSION });
            break;

        case 'SKIP_WAITING':
        case 'skipWaiting':
            self.skipWaiting();
            break;

        case 'OFFLINE_DATA_SAVED':
            registerSync();
            break;

        case 'CLEAR_CACHE':
            clearAllCaches();
            break;
    }
});

// Tag separado do BackgroundSyncPlugin ('ondeline-sync-queue')
// para não misturar os dois fluxos de sync
async function registerSync() {
    try {
        await self.registration.sync.register('sync-offline-queue');
    } catch (e) {}
}

// ─────────────────────────────────────────────
// Sync listener - offline_queue manual
// Chamado quando OFFLINE_DATA_SAVED → registerSync() → browser reconecta
// Notifica os clients para chamar POST /api/sync.php
// ─────────────────────────────────────────────
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-offline-queue') {
        event.waitUntil(processOfflineQueue());
    }
});

async function processOfflineQueue() {
    const allClients = await self.clients.matchAll({ includeUncontrolled: true });

    if (allClients.length > 0) {
        // App está aberto: avisa o cliente para processar
        allClients.forEach(client => {
            client.postMessage({ type: 'SYNC_OFFLINE' });
        });
        return;
    }

    // App está fechado: tenta sincronizar direto do SW via /api/sync.php
    // Usa o token armazenado no cache se disponível
    try {
        const cache = await caches.open('api-cache');
        const authResponse = await cache.match('/api/user.php');
        if (!authResponse) return; // sem token, não é possível autenticar

        // Envia sync sem corpo — o servidor usa a sessão/token do cache
        await fetch('/api/sync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
    } catch (e) {
        // Sem conexão ainda — o browser vai tentar novamente automaticamente
    }
}

async function clearAllCaches() {
    const keys = await caches.keys();
    await Promise.all(keys.map(k => caches.delete(k)));
}

// ─────────────────────────────────────────────
// Push Notifications - PWABuilder App Capabilities
// ─────────────────────────────────────────────
self.addEventListener('push', (event) => {
    let data = {
        title: 'Ondeline Tech',
        body: 'Nova notificação',
        url: '/dashboard.php',
        type: 'info',
        tag: 'default',
        id: null
    };

    if (event.data) {
        try { data = { ...data, ...event.data.json() }; }
        catch (e) { data.body = event.data.text(); }
    }

    const actionMap = {
        work_order: [
            { action: 'view_order',  title: 'Ver OS',      icon: '/icons/icon-192.png' },
            { action: 'dismiss',     title: 'Depois',       icon: '/icons/icon-192.png' }
        ],
        sync: [
            { action: 'sync_now',    title: 'Sincronizar', icon: '/icons/icon-192.png' },
            { action: 'dismiss',     title: 'Ignorar',      icon: '/icons/icon-192.png' }
        ],
        checklist: [
            { action: 'view_checklist', title: 'Ver Checklist', icon: '/icons/icon-192.png' },
            { action: 'dismiss',        title: 'Depois',         icon: '/icons/icon-192.png' }
        ],
        default: [
            { action: 'open',    title: 'Abrir',     icon: '/icons/icon-192.png' },
            { action: 'dismiss', title: 'Dispensar', icon: '/icons/icon-192.png' }
        ]
    };

    const actions = actionMap[data.type] ?? actionMap.default;

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            vibrate: [100, 50, 100],
            tag: data.tag,
            renotify: true,
            requireInteraction: data.type === 'work_order',
            data: { url: data.url, type: data.type, id: data.id },
            actions
        })
    );
});

// ─────────────────────────────────────────────
// Notification Click - Roteamento contextual
// ─────────────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const { id, url } = event.notification.data || {};
    let target = url || '/dashboard.php';

    switch (event.action) {
        case 'dismiss':       return;
        case 'view_order':    target = `/ordens.php?id=${id}`; break;
        case 'start_order':   target = `/ordens.php?id=${id}&action=start`; break;
        case 'sync_now':      event.waitUntil(syncData()); return;
        case 'view_checklist': target = `/checklist.php?id=${id}`; break;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const client of list) {
                if (client.url.includes(target) && 'focus' in client) return client.focus();
            }
            return clients.openWindow(target);
        })
    );
});

// ─────────────────────────────────────────────
// Share Target - Recebe arquivos compartilhados
// Usa registerRoute para integrar corretamente com Workbox
// ─────────────────────────────────────────────
registerRoute(
    ({ url, request }) =>
        url.pathname === '/share-target.php' && request.method === 'POST',
    async ({ request }) => {
        const formData = await request.formData();
        const file  = formData.get('file');
        const title = formData.get('title') || '';
        const text  = formData.get('text')  || '';
        const link  = formData.get('url')   || '';

        // Armazena metadados no cache temporário para a página ler
        const cache = await caches.open('share-target-cache');
        const body  = JSON.stringify({ title, text, url: link, hasFile: !!file });
        await cache.put('/share-data', new Response(body, {
            headers: { 'Content-Type': 'application/json' }
        }));

        const redirectUrl = file
            ? '/upload-foto.php?shared=1'
            : `/novo-cadastro.php?title=${encodeURIComponent(title)}&text=${encodeURIComponent(text)}`;

        return Response.redirect(redirectUrl, 303);
    },
    'POST'
);
