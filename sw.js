/**
 * Service Worker - PWA
 * Ondeline Tech - App do Técnico
 */

const APP_VERSION = 'v2.0.2';
const CACHE_NAME = `ondeline-tech-${APP_VERSION}`;
const STATIC_CACHE = `ondeline-static-${APP_VERSION}`;
const DYNAMIC_CACHE = `ondeline-dynamic-${APP_VERSION}`;
const DYNAMIC_CACHE_LIMIT = 100; // Máximo de itens no cache dinâmico (aumentado para CDN)

// Arquivos estáticos para cache (páginas PHP + assets)
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

// CDN externo para pré-cachear (fontes e estilos críticos)
const CDN_ASSETS = [
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            // Cache de assets estáticos locais
            caches.open(STATIC_CACHE).then((cache) => {
                return Promise.allSettled(
                    STATIC_ASSETS.map(url => cache.add(url).catch(() => null))
                );
            }),
            // Cache de CDN externos (fontes, ícones)
            caches.open(DYNAMIC_CACHE).then((cache) => {
                return Promise.allSettled(
                    CDN_ASSETS.map(url => cache.add(url).catch(() => null))
                );
            })
        ]).then(() => self.skipWaiting())
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

    // CDN externo (Google Fonts, TailwindCSS) - cache first para performance
    if (url.hostname !== self.location.hostname) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Para PHP, JS e CSS, usa Network First (sempre busca atualização)
    if (request.method === 'GET') {
        const isPageOrScript = url.pathname.endsWith('.php') ||
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
        // Se offline e não tem cache, retorna dashboard como fallback
        if (request.headers.get('accept')?.includes('text/html')) {
            const fallback = await caches.match('/dashboard.php');
            if (fallback) return fallback;
        }
        throw error;
    }
}

/**
 * Estratégia Network First com Cache Inteligente
 * Tenta a rede primeiro, se falhar, usa o cache
 * Adiciona timestamp para controle de expiração
 */
async function networkFirst(request) {
    const url = new URL(request.url);
    
    try {
        const networkResponse = await fetch(request);
        
        // Cacheia respostas GET bem-sucedidas da API
        if (networkResponse.ok && request.method === 'GET') {
            const cache = await caches.open(DYNAMIC_CACHE);
            
            // Clona resposta e adiciona timestamp no header para controle de expiração
            const responseToCache = networkResponse.clone();
            cache.put(request, responseToCache);
            trimCache(DYNAMIC_CACHE, DYNAMIC_CACHE_LIMIT);
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            // Marca resposta como vinda do cache
            const headers = new Headers(cachedResponse.headers);
            headers.set('X-From-Cache', 'true');
            
            return new Response(cachedResponse.body, {
                status: cachedResponse.status,
                statusText: cachedResponse.statusText,
                headers: headers
            });
        }
        
        // Retorna erro offline para API
        return new Response(
            JSON.stringify({ 
                success: false, 
                message: 'Você está offline. Verifique sua conexão.',
                offline: true,
                cached: false
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

// Push notifications melhoradas
self.addEventListener('push', (event) => {
    let data = {
        title: 'Ondeline Tech',
        body: 'Nova notificação',
        url: '/dashboard.php',
        type: 'info',
        tag: 'default'
    };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    // Define ações baseadas no tipo de notificação
    let actions = [
        { action: 'open', title: 'Abrir', icon: '/icons/open.png' },
        { action: 'dismiss', title: 'Dispensar', icon: '/icons/close.png' }
    ];
    
    // Ações contextuais por tipo
    if (data.type === 'work_order') {
        actions = [
            { action: 'view_order', title: 'Ver OS', icon: '/icons/view.png' },
            { action: 'start_order', title: 'Iniciar', icon: '/icons/play.png' },
            { action: 'dismiss', title: 'Depois', icon: '/icons/close.png' }
        ];
    } else if (data.type === 'sync') {
        actions = [
            { action: 'sync_now', title: 'Sincronizar', icon: '/icons/sync.png' },
            { action: 'dismiss', title: 'Ignorar', icon: '/icons/close.png' }
        ];
    } else if (data.type === 'checklist') {
        actions = [
            { action: 'view_checklist', title: 'Ver Checklist', icon: '/icons/checklist.png' },
            { action: 'dismiss', title: 'Depois', icon: '/icons/close.png' }
        ];
    }

    const options = {
        body: data.body,
        icon: '/logo.png',
        badge: '/logo.png',
        vibrate: [100, 50, 100],
        tag: data.tag,
        renotify: true,
        requireInteraction: data.type === 'work_order', // Mantém na tela para OS
        data: { 
            url: data.url,
            type: data.type,
            id: data.id
        },
        actions: actions.slice(0, 2) // Máximo 2 ações em mobile
    };

    event.waitUntil(
        (async () => {
            // Atualiza badge com contagem de notificações
            const notifications = await self.registration.getNotifications();
            const count = notifications.length + 1;
            
            if ('setAppBadge' in navigator) {
                navigator.setAppBadge(count).catch(() => {});
            }
            
            return self.registration.showNotification(data.title, options);
        })()
    );
});

// Clique na notificação com ações contextuais
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    // Limpa badge quando interagir
    if ('clearAppBadge' in navigator) {
        navigator.clearAppBadge().catch(() => {});
    }
    
    const notificationData = event.notification.data || {};
    let targetUrl = notificationData.url || '/dashboard.php';
    
    // Processa ações específicas
    switch (event.action) {
        case 'dismiss':
            return; // Apenas fecha
        
        case 'view_order':
            targetUrl = `/ordens.php?id=${notificationData.id}`;
            break;
            
        case 'start_order':
            targetUrl = `/ordens.php?id=${notificationData.id}&action=start`;
            break;
            
        case 'sync_now':
            // Dispara sync em background
            event.waitUntil(syncOfflineQueue());
            return;
            
        case 'view_checklist':
            targetUrl = `/checklist.php?id=${notificationData.id}`;
            break;
            
        default:
            // Ação padrão: abre a URL
            break;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            for (const client of windowClients) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(targetUrl);
        })
    );
});
