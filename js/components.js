/**
 * Componentes Compartilhados
 * Ondeline Tech - App do Técnico
 *
 * Gera elementos de UI reutilizáveis (bottom nav, etc.)
 */

const AppComponents = {
    /**
     * Renderiza a bottom navigation bar com estilo Glassmorphism/Bento Grid
     * Substitui <nav id="bottom-nav"></nav> com a nav completa
     */
    renderBottomNav() {
        const container = document.getElementById('bottom-nav');
        if (!container) return;

        const currentPage = window.location.pathname.split('/').pop() || 'index.php';

        // Ícones modernos estilo iOS com cores individuais
        const items = [
            { href: 'dashboard.php', icon: 'cottage', label: 'Início', color: 'primary', gradient: 'from-blue-500 to-indigo-600' },
            { href: 'mapa.php', icon: 'explore', label: 'Rotas', color: 'emerald', gradient: 'from-emerald-500 to-teal-600' },
            { href: 'ponto.php', icon: 'schedule', label: 'Ponto', color: 'orange', gradient: 'from-orange-500 to-amber-600' },
            { href: 'consultar.php', icon: 'group', label: 'Clientes', color: 'purple', gradient: 'from-purple-500 to-violet-600' },
            { href: 'ajustes.php', icon: 'tune', label: 'Ajustes', color: 'gray', gradient: 'from-gray-500 to-slate-600' }
        ];

        const colorMap = {
            primary: { active: 'text-blue-600', bg: 'from-blue-500/20 to-indigo-500/10', border: 'border-blue-500/30', shadow: 'shadow-blue-500/20' },
            emerald: { active: 'text-emerald-600', bg: 'from-emerald-500/20 to-teal-500/10', border: 'border-emerald-500/30', shadow: 'shadow-emerald-500/20' },
            orange: { active: 'text-orange-600', bg: 'from-orange-500/20 to-amber-500/10', border: 'border-orange-500/30', shadow: 'shadow-orange-500/20' },
            purple: { active: 'text-purple-600', bg: 'from-purple-500/20 to-violet-500/10', border: 'border-purple-500/30', shadow: 'shadow-purple-500/20' },
            gray: { active: 'text-gray-600 dark:text-gray-300', bg: 'from-gray-500/20 to-slate-500/10', border: 'border-gray-500/30', shadow: 'shadow-gray-500/20' }
        };

        const links = items.map(item => {
            const isActive = currentPage === item.href;
            const colors = colorMap[item.color];
            
            if (isActive) {
                // Item ativo com pill glassmorphism colorido
                return `<a class="nav-item-active relative flex flex-col items-center justify-center px-4 py-2 rounded-2xl bg-gradient-to-br ${colors.bg} border ${colors.border} shadow-lg ${colors.shadow} transition-all duration-300" href="${item.href}" data-nav-item>` +
                    `<div class="absolute inset-0 rounded-2xl bg-gradient-to-br ${colors.bg} opacity-50"></div>` +
                    `<span class="material-symbols-outlined ${colors.active} text-[26px] relative z-10 drop-shadow-sm" style="font-variation-settings: 'FILL' 1">${item.icon}</span>` +
                    `<span class="text-[10px] font-bold ${colors.active} relative z-10 mt-0.5">${item.label}</span>` +
                    `</a>`;
            } else {
                // Item inativo minimalista
                return `<a class="nav-item flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-300 hover:bg-gray-100 dark:hover:bg-white/5 active:scale-95" href="${item.href}" data-nav-item>` +
                    `<span class="material-symbols-outlined nav-icon text-gray-400 dark:text-gray-500 text-[24px] transition-all duration-300" style="font-variation-settings: 'FILL' 0">${item.icon}</span>` +
                    `<span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 mt-0.5 transition-colors duration-300">${item.label}</span>` +
                    `</a>`;
            }
        }).join('\n');

        // Container com glassmorphism
        container.className = 'fixed bottom-0 left-0 right-0 z-50 safe-bottom';
        container.innerHTML = `
            <div class="mx-3 mb-3 px-2 py-2 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-white/10 rounded-3xl shadow-2xl shadow-black/10 dark:shadow-black/40">
                <div class="flex justify-around items-center">
                    ${links}
                </div>
            </div>
        `;

        // Adiciona efeitos de hover/touch nos links inativos
        container.querySelectorAll('.nav-item:not(.nav-item-active)').forEach(link => {
            link.addEventListener('mouseenter', () => {
                const icon = link.querySelector('.nav-icon');
                const label = link.querySelector('span:last-child');
                if (icon) {
                    icon.style.fontVariationSettings = "'FILL' 0.5";
                    icon.classList.remove('text-gray-400', 'dark:text-gray-500');
                    icon.classList.add('text-gray-600', 'dark:text-gray-300');
                }
                if (label) {
                    label.classList.remove('text-gray-400', 'dark:text-gray-500');
                    label.classList.add('text-gray-600', 'dark:text-gray-300');
                }
            });
            link.addEventListener('mouseleave', () => {
                const icon = link.querySelector('.nav-icon');
                const label = link.querySelector('span:last-child');
                if (icon) {
                    icon.style.fontVariationSettings = "'FILL' 0";
                    icon.classList.add('text-gray-400', 'dark:text-gray-500');
                    icon.classList.remove('text-gray-600', 'dark:text-gray-300');
                }
                if (label) {
                    label.classList.add('text-gray-400', 'dark:text-gray-500');
                    label.classList.remove('text-gray-600', 'dark:text-gray-300');
                }
            });
            // Haptic feedback ao clicar
            link.addEventListener('click', () => {
                if (typeof UIEnhancements !== 'undefined') {
                    UIEnhancements.hapticLight();
                }
            });
        });

        // Haptic no item ativo também
        container.querySelectorAll('.nav-item-active').forEach(link => {
            link.addEventListener('click', () => {
                if (typeof UIEnhancements !== 'undefined') {
                    UIEnhancements.hapticLight();
                }
            });
        });

        // Verifica admin e adiciona link se necessário
        this._checkAdminAccess(container);
    },

    /**
     * Verifica se usuário é admin e adiciona link de admin na nav
     */
    _checkAdminAccess(container) {
        try {
            const userData = localStorage.getItem('user') || localStorage.getItem('user_data');
            if (userData) {
                const user = JSON.parse(userData);
                if (user.role === 'admin') {
                    const navContainer = container.querySelector('.flex.justify-around');
                    if (navContainer) {
                        const currentPage = window.location.pathname.split('/').pop();
                        const isActive = currentPage === 'admin.php';
                        
                        const adminLink = isActive 
                            ? `<a class="nav-item-active relative flex flex-col items-center justify-center px-4 py-2 rounded-2xl bg-gradient-to-br from-rose-500/20 to-pink-500/10 border border-rose-500/30 shadow-lg shadow-rose-500/20 transition-all duration-300" href="admin.php" data-nav-item>` +
                                `<div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-rose-500/5 to-transparent"></div>` +
                                `<span class="material-symbols-outlined text-rose-600 text-[26px] relative z-10 drop-shadow-sm" style="font-variation-settings: 'FILL' 1">shield_person</span>` +
                                `<span class="text-[10px] font-bold text-rose-600 relative z-10 mt-0.5">Admin</span>` +
                                `</a>`
                            : `<a class="nav-item flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-300 hover:bg-gray-100 dark:hover:bg-white/5 active:scale-95" href="admin.php" data-nav-item>` +
                                `<span class="material-symbols-outlined nav-icon text-gray-400 dark:text-gray-500 text-[24px] transition-all duration-300" style="font-variation-settings: 'FILL' 0">shield_person</span>` +
                                `<span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 mt-0.5 transition-colors duration-300">Admin</span>` +
                                `</a>`;
                        
                        navContainer.insertAdjacentHTML('beforeend', adminLink);
                    }
                }
            }
        } catch (e) { /* ignore */ }
    },

    /**
     * Modal de confirmação reutilizável
     * @param {Object} options - { title, message, confirmText, cancelText, icon, type }
     * @param {string} options.type - 'danger' | 'warning' | 'info'
     * @returns {Promise<boolean>}
     */
    showConfirmModal(options) {
        return new Promise(function(resolve) {
            var existing = document.getElementById('confirm-modal');
            if (existing) existing.remove();

            var type = options.type || 'danger';
            var colorMap = {
                danger:  { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-500', btn: 'bg-red-500 hover:bg-red-600' },
                warning: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-500', btn: 'bg-yellow-500 hover:bg-yellow-600' },
                info:    { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-500', btn: 'bg-blue-500 hover:bg-blue-600' }
            };
            var colors = colorMap[type] || colorMap.danger;

            var overlay = document.createElement('div');
            overlay.id = 'confirm-modal';
            overlay.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 animate-fadeIn';
            overlay.innerHTML =
                '<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm w-full shadow-2xl transform transition-all">' +
                    '<div class="flex items-center gap-3 mb-4">' +
                        '<div class="size-10 rounded-full ' + colors.bg + ' flex items-center justify-center">' +
                            '<span class="material-symbols-outlined ' + colors.text + '">' + (options.icon || 'warning') + '</span>' +
                        '</div>' +
                        '<h3 class="text-lg font-bold text-gray-900 dark:text-white">' + escapeHtml(options.title || 'Confirmar') + '</h3>' +
                    '</div>' +
                    '<p class="text-gray-600 dark:text-gray-400 text-sm mb-6 leading-relaxed">' + escapeHtml(options.message || 'Tem certeza?') + '</p>' +
                    '<div class="flex gap-3">' +
                        '<button id="confirm-modal-cancel" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium text-sm transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">' +
                            escapeHtml(options.cancelText || 'Cancelar') +
                        '</button>' +
                        '<button id="confirm-modal-confirm" class="flex-1 px-4 py-2.5 rounded-xl ' + colors.btn + ' text-white font-medium text-sm transition-colors">' +
                            escapeHtml(options.confirmText || 'Confirmar') +
                        '</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(overlay);

            function close(result) {
                overlay.remove();
                resolve(result);
            }

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) close(false);
            });
            document.getElementById('confirm-modal-cancel').addEventListener('click', function() { close(false); });
            document.getElementById('confirm-modal-confirm').addEventListener('click', function() { close(true); });

            var handleEsc = function(e) {
                if (e.key === 'Escape') {
                    close(false);
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        });
    },

    /**
     * Centro de Notificações In-App
     * Configura o botão de notificações do header e renderiza o painel
     */
    setupNotifications() {
        // Find the notification bell button in header
        var buttons = document.querySelectorAll('header button');
        var notifBtn = null;
        buttons.forEach(function(btn) {
            var icon = btn.querySelector('.material-symbols-outlined');
            if (icon && icon.textContent.trim() === 'notifications') {
                notifBtn = btn;
            }
        });

        if (!notifBtn) return;

        // Add relative positioning and badge
        notifBtn.style.position = 'relative';
        if (!notifBtn.querySelector('.notif-badge-dot')) {
            var badge = document.createElement('span');
            badge.className = 'notif-badge-dot hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1';
            badge.textContent = '0';
            notifBtn.appendChild(badge);
        }

        // Fetch unread count
        this._updateNotifBadge(notifBtn);

        // Poll every 60 seconds
        setInterval(function() {
            AppComponents._updateNotifBadge(notifBtn);
        }, 60000);

        // Open notification center on click
        notifBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            AppComponents._openNotificationCenter();
        });
    },

    async _updateNotifBadge(btn) {
        try {
            if (typeof API === 'undefined' || !API.isAuthenticated()) return;
            var response = await API.get('notifications.php', { action: 'unread_count' });
            if (response.success) {
                var count = response.data.count;
                var badge = btn.querySelector('.notif-badge-dot');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 9 ? '9+' : count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            }
        } catch (e) { /* badge update failed */ }
    },

    async _openNotificationCenter() {
        // Remove existing
        var existing = document.getElementById('notification-center');
        if (existing) { existing.remove(); return; }

        var panel = document.createElement('div');
        panel.id = 'notification-center';
        panel.className = 'fixed inset-0 z-[9998]';
        panel.innerHTML =
            '<div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="notif-backdrop"></div>' +
            '<div class="absolute top-0 right-0 w-full max-w-md h-full bg-white dark:bg-gray-900 shadow-2xl transform transition-transform">' +
                '<div class="flex items-center justify-between px-4 h-16 border-b border-gray-200 dark:border-gray-800 safe-top">' +
                    '<h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">' +
                        '<span class="material-symbols-outlined text-primary">notifications</span>Notificações' +
                    '</h2>' +
                    '<div class="flex items-center gap-2">' +
                        '<button id="notif-mark-all" class="text-xs font-bold text-primary px-3 py-1.5 bg-primary/10 rounded-lg">Ler Todas</button>' +
                        '<button id="notif-close" class="size-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">' +
                            '<span class="material-symbols-outlined text-gray-500 text-xl">close</span>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<div id="notif-list" class="overflow-y-auto h-[calc(100%-4rem)] p-4 space-y-2">' +
                    '<div class="animate-pulse space-y-2">' +
                        '<div class="h-16 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>' +
                        '<div class="h-16 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(panel);

        // Close handlers
        document.getElementById('notif-backdrop').addEventListener('click', function() { panel.remove(); });
        document.getElementById('notif-close').addEventListener('click', function() { panel.remove(); });

        // Mark all read
        document.getElementById('notif-mark-all').addEventListener('click', async function() {
            try {
                await API.put('notifications.php', { action: 'mark_all_read' });
                AppComponents._loadNotifications();
                // Update badge
                var buttons = document.querySelectorAll('header button');
                buttons.forEach(function(btn) {
                    var icon = btn.querySelector('.material-symbols-outlined');
                    if (icon && icon.textContent.trim() === 'notifications') {
                        AppComponents._updateNotifBadge(btn);
                    }
                });
                if (typeof showSuccess === 'function') showSuccess('Todas marcadas como lidas');
            } catch (e) { /* failed */ }
        });

        // Load notifications
        this._loadNotifications();
    },

    async _loadNotifications() {
        var container = document.getElementById('notif-list');
        if (!container) return;

        try {
            var response = await API.get('notifications.php', { limit: 30 });
            if (!response.success) return;

            var notifications = response.data;

            if (notifications.length === 0) {
                container.innerHTML =
                    '<div class="text-center py-16">' +
                        '<span class="material-symbols-outlined text-[48px] text-gray-300 dark:text-gray-600">notifications_off</span>' +
                        '<p class="text-sm text-gray-400 dark:text-gray-500 mt-3 font-medium">Nenhuma notificação</p>' +
                    '</div>';
                return;
            }

            var typeIcons = {
                info: { icon: 'info', color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-900/20' },
                success: { icon: 'check_circle', color: 'text-green-500', bg: 'bg-green-50 dark:bg-green-900/20' },
                warning: { icon: 'warning', color: 'text-yellow-500', bg: 'bg-yellow-50 dark:bg-yellow-900/20' },
                error: { icon: 'error', color: 'text-red-500', bg: 'bg-red-50 dark:bg-red-900/20' }
            };

            var html = '';
            notifications.forEach(function(notif) {
                var t = typeIcons[notif.type] || typeIcons.info;
                var isRead = notif.read == 1;
                var timeAgo = AppComponents._timeAgo(notif.created_at);

                html += '<div class="flex items-start gap-3 p-3 rounded-xl transition-colors cursor-pointer ' +
                    (isRead ? 'bg-gray-50/50 dark:bg-gray-800/30' : 'bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700') +
                    '" data-notif-id="' + notif.id + '"' +
                    (notif.action_url ? ' data-url="' + escapeHtml(notif.action_url) + '"' : '') + '>' +
                    '<div class="size-9 rounded-full ' + t.bg + ' flex items-center justify-center flex-shrink-0 mt-0.5">' +
                        '<span class="material-symbols-outlined ' + t.color + ' text-lg">' + t.icon + '</span>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-' + (isRead ? 'medium' : 'bold') + ' text-gray-900 dark:text-white truncate">' + escapeHtml(notif.title) + '</p>' +
                        '<p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5">' + escapeHtml(notif.message) + '</p>' +
                        '<p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">' + timeAgo + '</p>' +
                    '</div>' +
                    (!isRead ? '<div class="size-2 rounded-full bg-primary flex-shrink-0 mt-2"></div>' : '') +
                '</div>';
            });

            container.innerHTML = html;

            // Click handlers
            container.querySelectorAll('[data-notif-id]').forEach(function(el) {
                el.addEventListener('click', async function() {
                    var id = el.dataset.notifId;
                    var url = el.dataset.url;

                    // Mark as read
                    try {
                        await API.put('notifications.php', { action: 'mark_read', id: id });
                    } catch (e) { /* failed */ }

                    if (url) {
                        window.location.href = url;
                    } else {
                        // Just mark read visually
                        el.classList.remove('bg-white', 'dark:bg-gray-800', 'shadow-sm', 'border', 'border-gray-100', 'dark:border-gray-700');
                        el.classList.add('bg-gray-50/50', 'dark:bg-gray-800/30');
                        var dot = el.querySelector('.bg-primary');
                        if (dot) dot.remove();
                    }
                });
            });
        } catch (e) {
            container.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Erro ao carregar notificações</p>';
        }
    },

    _timeAgo(dateStr) {
        var now = new Date();
        var date = new Date(dateStr);
        var diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'Agora mesmo';
        if (diff < 3600) return Math.floor(diff / 60) + ' min atrás';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd atrás';
        return date.toLocaleDateString('pt-BR');
    },

    /**
     * Renderiza o header compartilhado
     * Substitui <header id="shared-header" data-title="Título" data-back="dashboard.php"></header>
     * Atributos opcionais:
     *   data-title: título da página
     *   data-back: URL para o botão voltar (se omitido, não mostra botão voltar)
     *   data-notif: "true" para mostrar botão de notificações
     */
    renderHeader() {
        var header = document.getElementById('shared-header');
        if (!header) return;

        var title = header.dataset.title || '';
        var backUrl = header.dataset.back || '';
        var showNotif = header.dataset.notif === 'true';

        var leftBtn = backUrl
            ? '<a href="' + backUrl + '" class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 btn-press">' +
                '<span class="material-symbols-outlined">arrow_back_ios</span>' +
              '</a>'
            : '<div class="size-10"></div>';

        var rightBtn = showNotif
            ? '<button class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 btn-press relative">' +
                '<span class="material-symbols-outlined">notifications</span>' +
                '<span id="notif-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">0</span>' +
              '</button>'
            : '<div class="size-10"></div>';

        header.className = 'sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top';
        header.innerHTML =
            '<div class="flex items-center justify-between px-4 h-16">' +
                leftBtn +
                '<h2 class="text-gray-900 dark:text-white text-lg font-bold flex-1 text-center">' + title + '</h2>' +
                rightBtn +
            '</div>';
    },

    /**
     * Inicializa todos os componentes compartilhados
     */
    init() {
        this.renderHeader();
        this.renderBottomNav();
        this.setupNotifications();
    },

    // ==========================================
    // SKELETON LOADERS
    // ==========================================

    /**
     * Gera HTML de skeleton loader para listas de cards
     * @param {number} count - Quantidade de skeletons
     * @param {string} type - Tipo: 'card', 'list', 'stat'
     */
    skeleton(count = 3, type = 'card') {
        const items = [];
        for (let i = 0; i < count; i++) {
            if (type === 'card') {
                items.push(`
                    <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm animate-pulse">
                        <div class="flex items-center gap-3">
                            <div class="size-11 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                            </div>
                            <div class="h-6 w-16 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        </div>
                    </div>
                `);
            } else if (type === 'list') {
                items.push(`
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                `);
            } else if (type === 'stat') {
                items.push(`
                    <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm animate-pulse">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="size-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                        </div>
                        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-12 mt-2"></div>
                    </div>
                `);
            }
        }
        return items.join('');
    },

    /**
     * Mostra skeleton em um container
     */
    showSkeleton(containerId, count = 3, type = 'card') {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = this.skeleton(count, type);
        }
    }
};

// Auto-inicializa quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    AppComponents.init();
});

window.AppComponents = AppComponents;
