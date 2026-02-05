/**
 * Service Worker - PWA
 * Ondeline Tech - App do Técnico
 */

const CACHE_NAME = 'ondeline-tech-v2';
const STATIC_CACHE = 'ondeline-static-v2';
const DYNAMIC_CACHE = 'ondeline-dynamic-v2';

// Arquivos estáticos para cache
const STATIC_ASSETS = [
    '/',
    '/login.html',
    '/dashboard.html',
    '/novo-cadastro.html',
    '/consultar.html',
    '/detalher.html',
    '/manifest.json',
    '/js/api.js',
    '/js/app.js',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    // CDNs importantes
    'https://cdn.tailwindcss.com?plugins=forms,container-queries',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Instalando Service Worker...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Cacheando arquivos estáticos');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('[SW] Instalação concluída');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[SW] Erro na instalação:', error);
            })
    );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Ativando Service Worker...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
                        .map((name) => {
                            console.log('[SW] Removendo cache antigo:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Ativação concluída');
                return self.clients.claim();
            })
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

    // Para arquivos estáticos, tenta cache primeiro
    if (request.method === 'GET') {
        event.respondWith(cacheFirst(request));
    }
});

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

// Sincronização em background (quando voltar online)
self.addEventListener('sync', (event) => {
    console.log('[SW] Sync event:', event.tag);
    
    if (event.tag === 'sync-clients') {
        event.waitUntil(syncPendingClients());
    }
});

/**
 * Sincroniza clientes pendentes salvos offline
 */
async function syncPendingClients() {
    // Implementar sincronização de dados offline
    console.log('[SW] Sincronizando clientes pendentes...');
}

// Push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push recebido:', event);
    
    const options = {
        body: event.data?.text() || 'Nova notificação do Ondeline Tech',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            { action: 'explore', title: 'Ver detalhes' },
            { action: 'close', title: 'Fechar' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Ondeline Tech', options)
    );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notificação clicada:', event.action);
    
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/dashboard.html')
        );
    }
});
