/**
 * App Principal - Core e Inicialização
 * Ondeline Tech - App do Técnico
 *
 * Módulos de página em js/pages/*.js são carregados dinamicamente
 */

const App = {
    _themeCheckInterval: null,
    _isSubmitting: false,

    init() {
        this.registerServiceWorker();
        this.checkAuth();
        this.setupEventListeners();
        this.startAutoThemeCheck();
        this.setupOnlineOfflineListeners();
        this.startLocationTracking();
        this.loadPageModule();
    },

    /**
     * Carrega módulo JS da página atual dinamicamente
     */
    loadPageModule() {
        const page = window.location.pathname.split('/').pop() || 'index.php';

        const pageModules = {
            'login.php': 'login',
            'dashboard.php': 'dashboard',
            'novo-cadastro.php': 'cadastro',
            'consultar.php': 'consulta',
            'detalher.php': 'detalhes',
            'vincular-equipamento.php': 'vincular',
            'ajustes.php': 'ajustes',
            'historico.php': 'historico',
            'ordens.php': 'ordens',
            'relatorios.php': 'relatorios'
        };

        const initMap = {
            'login.php': 'initLoginPage',
            'dashboard.php': 'initDashboardPage',
            'novo-cadastro.php': 'initCadastroPage',
            'consultar.php': 'initConsultaPage',
            'detalher.php': 'initDetalhesPage',
            'vincular-equipamento.php': 'initVincularPage',
            'ajustes.php': 'initAjustesPage',
            'historico.php': 'initHistoricoPage'
        };

        const moduleName = pageModules[page];

        if (moduleName) {
            const script = document.createElement('script');
            script.src = `/js/pages/${moduleName}.js`;
            script.onload = () => {
                const fn = initMap[page];
                if (fn && typeof this[fn] === 'function') {
                    this[fn]();
                }
            };
            document.head.appendChild(script);
        } else {
            // Páginas sem módulo separado (mapa, ponto, estoque)
            this.initCurrentPage(page);
        }
    },

    initCurrentPage(page) {
        switch (page) {
            case 'mapa.php': this.initMapaPage(); break;
            case 'ponto.php': this.initPontoPage(); break;
            case 'estoque.php': this.initEstoquePage(); break;
        }
    },

    // ==========================================
    // SERVICE WORKER
    // ==========================================

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed') {
                            this.showToast('Nova versão disponível! Atualizando...', 'info');
                            if (registration.waiting) registration.waiting.postMessage('skipWaiting');
                        }
                        if (newWorker.state === 'activated') window.location.reload();
                    });
                });
                if (registration.waiting) registration.waiting.postMessage('skipWaiting');
                registration.update();

                // Escuta mensagens do SW para sync offline
                navigator.serviceWorker.addEventListener('message', function(event) {
                    if (!event.data) return;
                    
                    // SW pede o token de auth para sync em background
                    if (event.data.type === 'GET_AUTH_TOKEN' && event.ports[0]) {
                        const token = localStorage.getItem('auth_token');
                        event.ports[0].postMessage({ token: token });
                        return;
                    }
                    
                    // SW completou o sync automaticamente
                    if (event.data.type === 'SYNC_COMPLETE') {
                        if (typeof showSuccess === 'function') {
                            showSuccess('Dados sincronizados automaticamente!');
                        } else if (typeof App !== 'undefined' && App.showToast) {
                            App.showToast('Dados sincronizados!', 'success');
                        }
                        // Limpa fila local
                        localStorage.removeItem('offlineQueue');
                        return;
                    }
                    
                    // Sync falhou - tenta via client
                    if (event.data.type === 'SYNC_FAILED' || event.data.type === 'SYNC_OFFLINE') {
                        if (typeof syncOfflineQueue === 'function') {
                            syncOfflineQueue();
                        }
                        return;
                    }
                });

                // Mostra banner para ativar notificações após pequeno delay (requer gesto do usuário)
                // Só mostra no dashboard para não ser intrusivo
                const currentPage = window.location.pathname.split('/').pop() || 'index.php';
                if (API.isAuthenticated() && currentPage === 'dashboard.php') {
                    setTimeout(() => {
                        this.showPushBanner(registration);
                    }, 1500); // Delay de 1.5s para não parecer spam
                }
            } catch (error) { /* SW registration failed */ }
        }
    },

    // Registra subscription de push notifications
    async subscribePush(registration) {
        if (!('PushManager' in window)) return;
        if (!('Notification' in window)) return;

        try {
            // Solicita permissão apenas se ainda não foi decidido
            if (Notification.permission === 'denied') return;

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            const vapidPublicKey = 'BCxBeZ9LpHe2nfk3QMHYdNrXdxB7E2hyIefVm7u6yGN5js0nvxXiNGRQE8FZC5E5MiHzqHDSAl1JIkvRrw25iMU';

            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                });
            }

            await API.subscribePush(subscription);
        } catch (err) {
            // Push subscription falhou silenciosamente (normal em alguns browsers)
        }
    },

    // Exibe banner fixo pedindo permissão para notificações (requer clique do usuário)
    showPushBanner(registration) {
        // Verifica se o navegador suporta push
        if (!('PushManager' in window) || !('Notification' in window)) return;
        
        // Não mostra se já existe banner
        if (document.getElementById('push-banner')) return;
        
        // Não mostra se permissão já foi concedida ou negada definitivamente
        if (Notification.permission === 'granted' || Notification.permission === 'denied') return;
        
        // Limpa flag antiga se existir
        if (localStorage.getItem('push-banner-dismissed')) {
            localStorage.removeItem('push-banner-dismissed');
        }
        
        // Verifica se o usuário já dismissiu o banner recentemente (24 horas)
        const dismissedAt = localStorage.getItem('push-banner-dismissed-at');
        if (dismissedAt) {
            const hoursSinceDismiss = (Date.now() - parseInt(dismissedAt)) / (1000 * 60 * 60);
            if (hoursSinceDismiss < 24) return; // Espera 24h antes de mostrar novamente
        }

        const banner = document.createElement('div');
        banner.id = 'push-banner';
        banner.className = 'fixed bottom-20 left-4 right-4 bg-blue-600 text-white rounded-2xl p-4 z-40 flex items-center gap-3 shadow-lg animate-slide-up';
        banner.innerHTML = `
            <span class="material-symbols-outlined text-2xl flex-shrink-0">notifications</span>
            <div class="flex-1">
                <p class="text-sm font-semibold">Ativar notificações</p>
                <p class="text-xs opacity-80">Receba alertas importantes</p>
            </div>
            <button id="push-banner-allow" class="bg-white text-blue-600 text-xs font-bold px-3 py-1.5 rounded-xl flex-shrink-0">Ativar</button>
            <button id="push-banner-dismiss" class="text-white/70 ml-1 flex-shrink-0"><span class="material-symbols-outlined text-xl">close</span></button>
        `;
        document.body.appendChild(banner);

        document.getElementById('push-banner-dismiss').addEventListener('click', () => {
            banner.remove();
            localStorage.setItem('push-banner-dismissed-at', Date.now().toString());
        });
        document.getElementById('push-banner-allow').addEventListener('click', async () => {
            banner.remove();
            localStorage.removeItem('push-banner-dismissed'); // Remove flag antiga
            localStorage.removeItem('push-banner-dismissed-at');
            await this.subscribePush(registration);
        });
    },

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; i++) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    },

    // ==========================================
    // AUTENTICAÇÃO
    // ==========================================

    checkAuth() {
        const publicPages = ['login.php'];
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        if (!API.isAuthenticated() && !publicPages.includes(currentPage)) {
            window.location.href = 'login.php';
            return false;
        }
        if (API.isAuthenticated() && publicPages.includes(currentPage)) {
            window.location.href = 'dashboard.php';
            return false;
        }
        return true;
    },

    // ==========================================
    // TEMA
    // ==========================================

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-theme-toggle]')) this.toggleTheme();
            if (e.target.closest('[data-logout]')) {
                e.preventDefault();
                this.confirmLogout();
            }
        });
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) document.documentElement.classList.toggle('dark', e.matches);
            });
        }
        this.applyTheme();
    },

    toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    },

    applyTheme() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else if (savedTheme === 'light') {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        } else {
            this.applyAutoTimeTheme();
        }
    },

    applyAutoTimeTheme() {
        const hour = new Date().getHours();
        const isDark = hour < 6 || hour >= 18;
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.classList.toggle('light', !isDark);
    },

    startAutoThemeCheck() {
        if (this._themeCheckInterval) clearInterval(this._themeCheckInterval);
        this._themeCheckInterval = setInterval(() => {
            const savedTheme = localStorage.getItem('theme');
            if (!savedTheme || savedTheme === 'auto') this.applyAutoTimeTheme();
        }, 60000);
    },

    // ==========================================
    // ONLINE/OFFLINE
    // ==========================================

    setupOnlineOfflineListeners() {
        window.addEventListener('online', () => {
            // Tenta BackgroundSync primeiro, depois fallback manual
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.ready.then(registration => {
                    if ('sync' in registration) {
                        registration.sync.register('sync-offline-queue').catch(() => {
                            // Fallback se BackgroundSync nao for suportado
                            if (typeof syncOfflineQueue === 'function') syncOfflineQueue();
                        });
                    } else {
                        if (typeof syncOfflineQueue === 'function') syncOfflineQueue();
                    }
                });
            } else {
                if (typeof syncOfflineQueue === 'function') syncOfflineQueue();
            }
            this.showToast('Conexão restaurada! Sincronizando...', 'success');
        });
        window.addEventListener('offline', () => {
            this.showToast('Sem conexão! Dados serão salvos offline', 'warning');
        });
    },

    // ==========================================
    // UI HELPERS
    // ==========================================

    showToast(message, type = 'info') {
        const existingToast = document.querySelector('.app-toast');
        if (existingToast) existingToast.remove();
        const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500', info: 'bg-blue-500' };
        const toast = document.createElement('div');
        toast.className = `app-toast fixed top-20 left-1/2 -translate-x-1/2 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg z-50 animate-fade-in`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    },

    showLoading(show = true) {
        let loader = document.getElementById('app-loader');
        if (show) {
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'app-loader';
                loader.className = 'fixed inset-0 bg-black/30 flex items-center justify-center z-50';
                loader.innerHTML = `
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl flex flex-col items-center gap-3">
                        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-gray-600 dark:text-gray-300 text-sm font-medium">Carregando...</span>
                    </div>
                `;
                document.body.appendChild(loader);
            }
        } else {
            if (loader) loader.remove();
        }
    },

    formatCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    },

    // ==========================================
    // NAVEGAÇÃO COMPARTILHADA
    // ==========================================

    setupBottomNavigation() {
        const navMap = {
            'início': 'dashboard.php', 'inicio': 'dashboard.php',
            'rotas': 'mapa.php', 'ponto': 'ponto.php',
            'clientes': 'consultar.php', 'ajustes': 'ajustes.php'
        };
        document.querySelectorAll('nav a').forEach(link => {
            const text = link.querySelector('span:last-child')?.textContent?.trim()?.toLowerCase();
            if (text && navMap[text] && (!link.getAttribute('href') || link.getAttribute('href') === '#')) {
                link.setAttribute('href', navMap[text]);
            }
        });
    },

    updateHeaderProfile(user) {
        if (!user) return;
        const avatarEl = document.getElementById('user-avatar');
        const nameEl = document.getElementById('header-user-name');
        const roleEl = document.getElementById('header-user-role');
        if (avatarEl && user.photo) {
            avatarEl.style.backgroundImage = `url("${user.photo}")`;
            const iconEl = avatarEl.querySelector('.material-symbols-outlined');
            if (iconEl) iconEl.style.display = 'none';
        }
        if (nameEl) nameEl.textContent = user.full_name || user.username;
        if (roleEl) {
            if (user.cargo) { roleEl.textContent = user.cargo; }
            else {
                const roleLabels = { admin: 'Administrador', tecnico: 'Tecnico de Campo' };
                roleEl.textContent = roleLabels[user.role] || user.role || 'Tecnico';
            }
        }
    },

    // ==========================================
    // MODAL DE FOTO (melhorado para mobile)
    // ==========================================

    openPhotoModal(url, title) {
        const existingModal = document.getElementById('photo-modal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'photo-modal';
        modal.className = 'fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <button class="absolute top-4 right-4 text-white p-4 z-10 min-w-[48px] min-h-[48px] flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm" id="photo-modal-close">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>
            <div class="absolute top-4 left-4 text-white z-10"><p class="text-sm font-medium">${title}</p></div>
            <div class="absolute top-16 left-1/2 -translate-x-1/2 text-white/50 text-xs z-10">Arraste para baixo para fechar</div>
            <img src="${url}" class="max-w-full max-h-full object-contain rounded-lg" alt="${title}" id="photo-modal-img">
        `;
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        document.getElementById('photo-modal-close').addEventListener('click', () => modal.remove());

        const img = document.getElementById('photo-modal-img');
        let startY = 0, currentY = 0, isDragging = false;

        img.addEventListener('touchstart', (e) => { startY = e.touches[0].clientY; isDragging = true; img.style.transition = 'none'; });
        img.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            if (diff > 0) {
                img.style.transform = `translateY(${diff}px)`;
                modal.style.backgroundColor = `rgba(0,0,0,${Math.max(0.2, 0.9 - diff / 400)})`;
            }
        });
        img.addEventListener('touchend', () => {
            if (!isDragging) return;
            isDragging = false;
            const diff = currentY - startY;
            if (diff > 100) {
                img.style.transition = 'transform 0.2s';
                img.style.transform = 'translateY(100vh)';
                setTimeout(() => modal.remove(), 200);
            } else {
                img.style.transition = 'transform 0.2s';
                img.style.transform = 'translateY(0)';
                modal.style.backgroundColor = 'rgba(0,0,0,0.9)';
            }
        });

        const handleEsc = (e) => { if (e.key === 'Escape') { modal.remove(); document.removeEventListener('keydown', handleEsc); } };
        document.addEventListener('keydown', handleEsc);
    },

    // ==========================================
    // LOGOUT COM CONFIRMAÇÃO
    // ==========================================

    confirmLogout() {
        const existingModal = document.getElementById('logout-modal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'logout-modal';
        modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm w-full shadow-xl">
                <div class="flex items-center gap-3 mb-4">
                    <div class="size-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <span class="material-symbols-outlined text-red-500">logout</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sair da conta</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">Tem certeza que deseja sair? Dados offline não sincronizados podem ser perdidos.</p>
                <div class="flex gap-3">
                    <button id="logout-cancel" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium text-sm">Cancelar</button>
                    <button id="logout-confirm" class="flex-1 px-4 py-2.5 rounded-xl bg-red-500 text-white font-medium text-sm">Sair</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        document.getElementById('logout-cancel').addEventListener('click', () => modal.remove());
        document.getElementById('logout-confirm').addEventListener('click', () => { modal.remove(); API.logout(); });
    },

    // ==========================================
    // VALIDAÇÃO DE CAMPOS
    // ==========================================

    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        field.classList.add('ring-2', 'ring-red-500', 'border-red-500');
        const existingError = field.parentElement.querySelector('.field-error-msg');
        if (existingError) existingError.remove();
        const errorEl = document.createElement('p');
        errorEl.className = 'field-error-msg text-red-500 text-xs mt-1';
        errorEl.textContent = message;
        field.parentElement.appendChild(errorEl);
        const clearError = () => {
            field.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
            const msg = field.parentElement.querySelector('.field-error-msg');
            if (msg) msg.remove();
            field.removeEventListener('input', clearError);
        };
        field.addEventListener('input', clearError);
    },

    clearFieldErrors() {
        document.querySelectorAll('.ring-red-500').forEach(el => el.classList.remove('ring-2', 'ring-red-500', 'border-red-500'));
        document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
    },

    // ==========================================
    // PÁGINAS SEM MÓDULO SEPARADO
    // ==========================================

    async initMapaPage() {
        this.setupBottomNavigation();
        this.updateHeaderProfile(API.getUser());
    },

    async initPontoPage() {
        this.setupBottomNavigation();
        this.updateHeaderProfile(API.getUser());
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await API.getTimeClock({ date: today });
            if (response.success && response.data) {
                const record = response.data;
                const statusEl = document.getElementById('clock-status');
                const btnEntry = document.getElementById('btn-clock-entry');
                const btnExit = document.getElementById('btn-clock-exit');
                if (statusEl) {
                    if (record.exit_time) {
                        statusEl.textContent = 'Ponto completo hoje';
                        statusEl.className = 'text-green-600 font-medium';
                        if (btnEntry) btnEntry.disabled = true;
                        if (btnExit) btnExit.disabled = true;
                    } else if (record.entry_time) {
                        statusEl.textContent = `Entrada: ${record.entry_time}`;
                        statusEl.className = 'text-blue-600 font-medium';
                        if (btnEntry) btnEntry.disabled = true;
                        if (btnExit) btnExit.disabled = false;
                    } else {
                        statusEl.textContent = 'Aguardando entrada';
                        statusEl.className = 'text-gray-600';
                        if (btnEntry) btnEntry.disabled = false;
                        if (btnExit) btnExit.disabled = true;
                    }
                }
            }
        } catch (error) { /* Time clock check failed */ }
    },

    async initEstoquePage() {
        this.setupBottomNavigation();
        this.updateHeaderProfile(API.getUser());
        if (typeof window.loadInventoryStats === 'function') window.loadInventoryStats();
        if (typeof window.loadEquipmentList === 'function') window.loadEquipmentList();
    },

    // ========================================
    // RASTREAMENTO DE LOCALIZAÇÃO DO TÉCNICO
    // ========================================
    _locationTrackingInterval: null,
    
    /**
     * Inicia rastreamento de localização do técnico
     * Envia posição a cada 5 minutos para permitir auto-atribuição de OS
     */
    startLocationTracking() {
        // Só rastreia se estiver logado e for técnico
        const userData = API.getUser();
        if (!userData || !userData.user_id) return;
        
        // Não rastreia admin (opcional)
        // if (userData.role === 'admin') return;
        
        // Verifica suporte a geolocalização
        if (!navigator.geolocation) {
            console.debug('[LocationTracking] Geolocation não suportada');
            return;
        }
        
        // Função para enviar localização
        const sendLocation = async () => {
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: false, // Economia de bateria
                        timeout: 10000,
                        maximumAge: 60000 // Cache de 1 minuto
                    });
                });
                
                const token = localStorage.getItem('auth_token');
                if (!token) return;
                
                await fetch('/api/technician-location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        action: 'update',
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        heading: position.coords.heading,
                        speed: position.coords.speed,
                        last_activity: 'app_active'
                    })
                });
                
                console.debug('[LocationTracking] Localização enviada');
            } catch (err) {
                // Falha silenciosa - não é crítico
                console.debug('[LocationTracking] Erro:', err.message);
            }
        };
        
        // Envia imediatamente
        sendLocation();
        
        // Envia a cada 5 minutos
        this._locationTrackingInterval = setInterval(sendLocation, 5 * 60 * 1000);
        
        // Marca como inativo ao sair
        window.addEventListener('beforeunload', () => {
            const token = localStorage.getItem('auth_token');
            if (!token) return;
            
            // Usa sendBeacon para garantir envio antes de fechar
            navigator.sendBeacon('/api/technician-location.php', JSON.stringify({
                action: 'inactive'
            }));
        });
        
        // Também atualiza quando o app volta do background
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                sendLocation();
            }
        });
    },
    
    /**
     * Para o rastreamento de localização
     */
    stopLocationTracking() {
        if (this._locationTrackingInterval) {
            clearInterval(this._locationTrackingInterval);
            this._locationTrackingInterval = null;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
window.App = App;
