/**
 * Componentes Compartilhados
 * Ondeline Tech - App do Técnico
 *
 * Gera elementos de UI reutilizáveis (bottom nav, etc.)
 */

const AppComponents = {
    /**
     * Renderiza a bottom navigation bar
     * Substitui <nav id="bottom-nav"></nav> com a nav completa
     */
    renderBottomNav() {
        const container = document.getElementById('bottom-nav');
        if (!container) return;

        const currentPage = window.location.pathname.split('/').pop() || 'index.html';

        const items = [
            { href: 'dashboard.html', icon: 'home', label: 'Início' },
            { href: 'mapa.html', icon: 'map', label: 'Rotas' },
            { href: 'ponto.html', icon: 'punch_clock', label: 'Ponto' },
            { href: 'consultar.html', icon: 'groups', label: 'Clientes' },
            { href: 'ajustes.html', icon: 'settings', label: 'Ajustes' }
        ];

        const links = items.map(item => {
            const isActive = currentPage === item.href;
            const colorClass = isActive ? 'text-primary' : 'text-gray-400 dark:text-gray-500';
            const fontClass = isActive ? 'font-semibold' : 'font-medium';
            const fillStyle = isActive ? ' style="font-variation-settings: \'FILL\' 1"' : '';

            return `<a class="flex flex-col items-center gap-0.5 ${colorClass}" href="${item.href}">` +
                `<span class="material-symbols-outlined text-[26px]"${fillStyle}>${item.icon}</span>` +
                `<span class="text-[10px] ${fontClass}">${item.label}</span>` +
                `</a>`;
        }).join('\n');

        // Link de admin (oculto por padrão)
        const adminLink = `<a id="nav-admin" class="hidden flex flex-col items-center gap-0.5 text-gray-400 dark:text-gray-500" href="admin.html">` +
            `<span class="material-symbols-outlined text-[26px]">admin_panel_settings</span>` +
            `<span class="text-[10px] font-medium">Admin</span>` +
            `</a>`;

        container.className = 'fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-black/80 ios-blur border-t border-gray-200 dark:border-white/10 safe-bottom z-50';
        container.innerHTML = `<div class="flex justify-around items-center h-16">\n${links}\n${adminLink}\n</div>`;

        // Mostra admin se for administrador
        try {
            var userData = localStorage.getItem('user_data');
            if (userData) {
                var user = JSON.parse(userData);
                if (user.role === 'admin') {
                    var adminEl = document.getElementById('nav-admin');
                    if (adminEl) adminEl.classList.remove('hidden');
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
                        '<h3 class="text-lg font-bold text-gray-900 dark:text-white">' + (options.title || 'Confirmar') + '</h3>' +
                    '</div>' +
                    '<p class="text-gray-600 dark:text-gray-400 text-sm mb-6 leading-relaxed">' + (options.message || 'Tem certeza?') + '</p>' +
                    '<div class="flex gap-3">' +
                        '<button id="confirm-modal-cancel" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium text-sm transition-colors hover:bg-gray-100 dark:hover:bg-gray-700">' +
                            (options.cancelText || 'Cancelar') +
                        '</button>' +
                        '<button id="confirm-modal-confirm" class="flex-1 px-4 py-2.5 rounded-xl ' + colors.btn + ' text-white font-medium text-sm transition-colors">' +
                            (options.confirmText || 'Confirmar') +
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
     * Inicializa todos os componentes compartilhados
     */
    init() {
        this.renderBottomNav();
    }
};

// Auto-inicializa quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    AppComponents.init();
});

window.AppComponents = AppComponents;
