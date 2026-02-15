/**
 * Componentes Compartilhados
 * Ondeline Tech - App do Técnico
 *
 * Gera elementos de UI reutilizáveis (bottom nav, etc.)
 */

const Components = {
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
     * Inicializa todos os componentes compartilhados
     */
    init() {
        this.renderBottomNav();
    }
};

// Auto-inicializa quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    Components.init();
});

window.Components = Components;
