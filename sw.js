/**
 * Service Worker - PWA
 * Ondeline Tech - App do Técnico
 */

const APP_VERSION = 'v1.7.0';
const CACHE_NAME = `ondeline-tech-${APP_VERSION}`;
const STATIC_CACHE = `ondeline-static-${APP_VERSION}`;
const DYNAMIC_CACHE = `ondeline-dynamic-${APP_VERSION}`;
const DYNAMIC_CACHE_LIMIT = 50; // Máximo de itens no cache dinâmico

// Arquivos estáticos para cache
const STATIC_ASSETS = [
    '/',
    '/login.php',
    '/dashboard.php',
    '/novo-cadastro.php',
    '/consultar.php',
    '/detalher.php',
    '/ajustes.php',
    '/vincular-equipamento.php',
    '/historico.php',
    '/mapa.php',
    '/ponto.php',
    '/estoque.php',
    '/auditoria.php',
    '/checklist.php',
    '/admin.php',
    '/ordens.php',
    '/relatorios.php',
    '/manifest.json',
    '/js/api.js',
    '/js/app.js',
    '/js/animations.js',
    '/js/ui-enhancements.js',
    '/js/feedback.js',
    '/js/utils.js',
    '/js/geolocation.js',
    '/js/components.js',
    '/js/scanner.js',
    '/js/pages/login.js',
    '/js/pages/dashboard.js',
    '/js/pages/cadastro.js',
    '/js/pages/consulta.js',
    '/js/pages/detalhes.js',
    '/js/pages/vincular.js',
    '/js/pages/ajustes.js',
    '/js/pages/historico.js',
    '/js/pages/ordens.js',
    '/js/pages/relatorios.js',
    '/css/transitions.css',
    '/logo.png'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            // Usa addAll em bloco; erros individuais não quebram a instalação
            return Promise.allSettled(
                STATIC_ASSETS.map(url => cache.add(url).catch(() => null))
            );
        }).then(() => self.skipWaiting())
    );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
                        .map((name) => caches.delete(name))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Intercepta requisições
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignora requisições que não são HTTP/HTTPS (ex: chrome-extension://)
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Ignora requisições para a API (sempre busca do servidor)
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Para HTML e JS, usa Network First (sempre busca atualização)
    if (request.method === 'GET') {
        const isPageOrScript = url.pathname.endsWith('.html') ||
                               url.pathname.endsWith('.js') ||
                               url.pathname.endsWith('.css') ||
                               url.pathname === '/';

        if (isPageOrScript) {
            event.respondWith(networkFirst(request));
        } else {
            // Para imagens, fontes etc, usa cache primeiro
            event.respondWith(cacheFirst(request));
        }
    }
});

/**
 * Limita o tamanho do cache dinâmico (LRU)
 */
async function trimCache(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
        await cache.delete(keys[0]);
        if (keys.length - 1 > maxItems) {
            await trimCache(cacheName, maxItems);
        }
    }
}

/**
 * Estratégia Cache First
 * Tenta o cache primeiro, se não encontrar, busca na rede
 */
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);

        // Cacheia a resposta para uso futuro (somente URLs http/https)
        const url = new URL(request.url);
        if (networkResponse.ok && url.protocol.startsWith('http')) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
            trimCache(DYNAMIC_CACHE, DYNAMIC_CACHE_LIMIT);
        }

        return networkResponse;
    } catch (error) {
        // Se offline e não tem cache, retorna página offline
        if (request.headers.get('accept')?.includes('text/html')) {
            return caches.match('/dashboard.html');
        }
        throw error;
    }
}

/**
 * Estratégia Network First
 * Tenta a rede primeiro, se falhar, usa o cache
 */
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Cacheia respostas GET bem-sucedidas da API
        if (networkResponse.ok && request.method === 'GET') {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
            trimCache(DYNAMIC_CACHE, DYNAMIC_CACHE_LIMIT);
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Retorna erro offline para API
        return new Response(
            JSON.stringify({ 
                success: false, 
                message: 'Você está offline. Verifique sua conexão.',
                offline: true 
            }),
            { 
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Listener de mensagens do cliente
self.addEventListener('message', (event) => {
    // Responder com a versão atual
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: APP_VERSION });
        return;
    }
    
    // Retornar token de auth para sync em background
    if (event.data && event.data.type === 'GET_AUTH_TOKEN') {
        // O client responde via MessageChannel
        return;
    }
    
    // Client salvou dados offline - registra sync
    if (event.data && event.data.type === 'OFFLINE_DATA_SAVED') {
        registerBackgroundSync();
        return;
    }
    
    // Skip waiting para atualizar imediatamente
    if (event.data && (event.data.type === 'SKIP_WAITING' || event.data === 'skipWaiting')) {
        self.skipWaiting();
        return;
    }
});

// Sincronização em background (quando voltar online)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-clients' || event.tag === 'sync-offline-queue') {
        event.waitUntil(syncOfflineQueue());
    }
});

/**
 * Sincroniza fila offline via IndexedDB/localStorage
 * Tenta processar cada item da fila diretamente do SW
 */
async function syncOfflineQueue() {
    // Primeiro tenta processar direto no SW (via API sync endpoint)
    try {
        const allClients = await self.clients.matchAll();
        
        // Pega o token de auth do primeiro client disponível
        let authToken = null;
        for (const client of allClients) {
            const response = await new Promise((resolve) => {
                const channel = new MessageChannel();
                channel.port1.onmessage = (e) => resolve(e.data);
                client.postMessage({ type: 'GET_AUTH_TOKEN' }, [channel.port2]);
                // Timeout de 2s
                setTimeout(() => resolve(null), 2000);
            });
            if (response && response.token) {
                authToken = response.token;
                break;
            }
        }

        if (authToken) {
            // Tenta sincronizar via API
            const syncResponse = await fetch('/api/sync.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + authToken
                }
            });
            
            if (syncResponse.ok) {
                const result = await syncResponse.json();
                // Notifica os clients sobre o resultado
                allClients.forEach(client => {
                    client.postMessage({ 
                        type: 'SYNC_COMPLETE', 
                        data: result 
                    });
                });
                return;
            }
        }

        // Fallback: notifica clients para que eles façam o sync
        allClients.forEach(client => {
            client.postMessage({ type: 'SYNC_OFFLINE' });
        });
    } catch (error) {
        // Se falhar, notifica clients para retry manual
        const allClients = await self.clients.matchAll();
        allClients.forEach(client => {
            client.postMessage({ type: 'SYNC_FAILED', error: error.message });
        });
    }
}

/**
 * Registra sync quando detecta operações offline sendo salvas
 */
async function registerBackgroundSync() {
    try {
        await self.registration.sync.register('sync-offline-queue');
    } catch (e) {
        // BackgroundSync não suportado - fallback via message
    }
}

// Push notifications
self.addEventListener('push', (event) => {
    let data = {
        title: 'Ondeline Tech',
        body: 'Nova notificação',
        url: '/dashboard.html'
    };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: { url: data.url },
        actions: [
            { action: 'open', title: 'Abrir' },
            { action: 'close', title: 'Fechar' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action !== 'close') {
        const url = event.notification.data?.url || '/dashboard.html';
        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
                for (const client of windowClients) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                return clients.openWindow(url);
            })
        );
    }
});
