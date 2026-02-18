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
            
            if (isActive) {
                // Item ativo com pill glassmorphism
                return `<a class="nav-item-active relative flex flex-col items-center justify-center px-4 py-2 rounded-2xl bg-gradient-to-br from-primary/20 to-blue-500/10 border border-primary/30 shadow-lg shadow-primary/20 transition-all duration-300" href="${item.href}" data-nav-item>` +
                    `<div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-primary/5 to-transparent"></div>` +
                    `<span class="material-symbols-outlined text-primary text-[26px] relative z-10 drop-shadow-sm" style="font-variation-settings: 'FILL' 1">${item.icon}</span>` +
                    `<span class="text-[10px] font-bold text-primary relative z-10 mt-0.5">${item.label}</span>` +
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
                        const isActive = currentPage === 'admin.html';
                        
                        const adminLink = isActive 
                            ? `<a class="nav-item-active relative flex flex-col items-center justify-center px-4 py-2 rounded-2xl bg-gradient-to-br from-purple-500/20 to-violet-500/10 border border-purple-500/30 shadow-lg shadow-purple-500/20 transition-all duration-300" href="admin.html" data-nav-item>` +
                                `<div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-purple-500/5 to-transparent"></div>` +
                                `<span class="material-symbols-outlined text-purple-500 text-[26px] relative z-10 drop-shadow-sm" style="font-variation-settings: 'FILL' 1">admin_panel_settings</span>` +
                                `<span class="text-[10px] font-bold text-purple-500 relative z-10 mt-0.5">Admin</span>` +
                                `</a>`
                            : `<a class="nav-item flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-300 hover:bg-gray-100 dark:hover:bg-white/5 active:scale-95" href="admin.html" data-nav-item>` +
                                `<span class="material-symbols-outlined nav-icon text-gray-400 dark:text-gray-500 text-[24px] transition-all duration-300" style="font-variation-settings: 'FILL' 0">admin_panel_settings</span>` +
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
