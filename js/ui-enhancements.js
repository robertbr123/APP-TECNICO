/**
 * UI Enhancements Module
 * Ondeline Tech - Premium UI Features
 * 
 * Advanced UI features: Dynamic gradients, avatars, empty states, haptic feedback
 * Works without npm - pure vanilla JS
 */

const UIEnhancements = {

    // =========================================
    // 1. GRADIENTES DINÂMICOS (Hora do Dia)
    // =========================================
    
    /**
     * Aplica gradiente dinâmico baseado na hora do dia
     * @param {HTMLElement} element - Elemento para aplicar (geralmente body ou main)
     */
    applyDynamicGradient(element = document.body) {
        const hour = new Date().getHours();
        let gradient;
        
        // Determina o gradiente baseado na hora
        if (hour >= 5 && hour < 8) {
            // Amanhecer (5h-8h) - tons dourados suaves
            gradient = 'linear-gradient(135deg, rgba(255, 237, 213, 0.4) 0%, rgba(254, 243, 199, 0.2) 50%, rgba(255, 255, 255, 0) 100%)';
        } else if (hour >= 8 && hour < 12) {
            // Manhã (8h-12h) - tons claros energéticos
            gradient = 'linear-gradient(135deg, rgba(219, 234, 254, 0.3) 0%, rgba(237, 233, 254, 0.2) 50%, rgba(255, 255, 255, 0) 100%)';
        } else if (hour >= 12 && hour < 17) {
            // Tarde (12h-17h) - neutro/produtivo
            gradient = 'linear-gradient(135deg, rgba(240, 249, 255, 0.3) 0%, rgba(224, 242, 254, 0.2) 50%, rgba(255, 255, 255, 0) 100%)';
        } else if (hour >= 17 && hour < 20) {
            // Entardecer (17h-20h) - tons quentes sunset
            gradient = 'linear-gradient(135deg, rgba(255, 237, 213, 0.4) 0%, rgba(254, 215, 170, 0.2) 50%, rgba(255, 255, 255, 0) 100%)';
        } else {
            // Noite (20h-5h) - tons frios relaxantes
            gradient = 'linear-gradient(135deg, rgba(199, 210, 254, 0.3) 0%, rgba(224, 231, 255, 0.2) 50%, rgba(255, 255, 255, 0) 100%)';
        }
        
        // Aplica gradiente overlay
        const isDark = document.documentElement.classList.contains('dark');
        
        if (isDark) {
            // Para dark mode, usa gradientes mais sutis
            if (hour >= 20 || hour < 5) {
                gradient = 'linear-gradient(135deg, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.3) 100%)';
            } else {
                gradient = 'linear-gradient(135deg, rgba(51, 65, 85, 0.3) 0%, rgba(30, 41, 59, 0.2) 100%)';
            }
        }
        
        // Cria ou atualiza o elemento de gradiente
        let gradientEl = document.getElementById('dynamic-gradient');
        if (!gradientEl) {
            gradientEl = document.createElement('div');
            gradientEl.id = 'dynamic-gradient';
            gradientEl.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                pointer-events: none;
                z-index: -1;
                transition: background 1s ease;
            `;
            document.body.insertBefore(gradientEl, document.body.firstChild);
        }
        
        gradientEl.style.background = gradient;
    },
    
    /**
     * Retorna saudação baseada na hora
     * @returns {string}
     */
    getGreeting() {
        const hour = new Date().getHours();
        if (hour >= 5 && hour < 12) return 'Bom dia';
        if (hour >= 12 && hour < 18) return 'Boa tarde';
        return 'Boa noite';
    },
    
    /**
     * Retorna emoji baseado na hora
     * @returns {string}
     */
    getTimeEmoji() {
        const hour = new Date().getHours();
        if (hour >= 5 && hour < 8) return '🌅';
        if (hour >= 8 && hour < 12) return '☀️';
        if (hour >= 12 && hour < 17) return '🌤️';
        if (hour >= 17 && hour < 20) return '🌇';
        return '🌙';
    },

    // =========================================
    // 2. AVATAR COM INICIAL COLORIDA
    // =========================================
    
    /**
     * Gera cor única baseada no nome (hash)
     * @param {string} name - Nome do usuário
     * @returns {string} - Cor hex
     */
    getColorFromName(name) {
        if (!name) return '#6366f1'; // Fallback indigo
        
        // Lista de cores vibrantes
        const colors = [
            '#ef4444', // red
            '#f97316', // orange
            '#f59e0b', // amber
            '#84cc16', // lime
            '#22c55e', // green
            '#14b8a6', // teal
            '#06b6d4', // cyan
            '#0ea5e9', // sky
            '#3b82f6', // blue
            '#6366f1', // indigo
            '#8b5cf6', // violet
            '#a855f7', // purple
            '#d946ef', // fuchsia
            '#ec4899', // pink
            '#f43f5e', // rose
        ];
        
        // Gera hash simples do nome
        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        // Converte para índice
        const index = Math.abs(hash) % colors.length;
        return colors[index];
    },
    
    /**
     * Gera gradiente baseado no nome
     * @param {string} name - Nome do usuário
     * @returns {string} - CSS gradient
     */
    getGradientFromName(name) {
        const baseColor = this.getColorFromName(name);
        // Cria versão mais escura para gradiente
        return `linear-gradient(135deg, ${baseColor} 0%, ${this.darkenColor(baseColor, 20)} 100%)`;
    },
    
    /**
     * Escurece uma cor hex
     * @param {string} hex - Cor hex
     * @param {number} percent - Porcentagem para escurecer
     * @returns {string}
     */
    darkenColor(hex, percent) {
        const num = parseInt(hex.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) - amt;
        const G = (num >> 8 & 0x00FF) - amt;
        const B = (num & 0x0000FF) - amt;
        return '#' + (
            0x1000000 +
            (R < 0 ? 0 : R > 255 ? 255 : R) * 0x10000 +
            (G < 0 ? 0 : G > 255 ? 255 : G) * 0x100 +
            (B < 0 ? 0 : B > 255 ? 255 : B)
        ).toString(16).slice(1);
    },
    
    /**
     * Obtém iniciais do nome
     * @param {string} name - Nome completo
     * @returns {string} - Iniciais (máx 2 caracteres)
     */
    getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) {
            return parts[0].substring(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    },
    
    /**
     * Cria avatar HTML com inicial colorida
     * @param {string} name - Nome do usuário
     * @param {string} photoUrl - URL da foto (opcional)
     * @param {string} size - Classe de tamanho (ex: 'size-10', 'size-12')
     * @returns {string} - HTML do avatar
     */
    createAvatar(name, photoUrl = null, size = 'size-10') {
        if (photoUrl) {
            return `<div class="${size} rounded-full bg-center bg-cover border border-gray-100 dark:border-gray-800" style="background-image: url('${photoUrl}')"></div>`;
        }
        
        const initials = this.getInitials(name);
        const gradient = this.getGradientFromName(name);
        const fontSize = size.includes('12') || size.includes('14') || size.includes('16') ? 'text-lg' : 'text-sm';
        
        return `<div class="${size} rounded-full flex items-center justify-center text-white font-bold ${fontSize}" style="background: ${gradient}">${initials}</div>`;
    },
    
    /**
     * Aplica avatar com inicial em um elemento existente
     * @param {HTMLElement} element - Elemento do avatar
     * @param {string} name - Nome do usuário
     * @param {string} photoUrl - URL da foto (opcional)
     */
    setAvatar(element, name, photoUrl = null) {
        if (!element) return;
        
        if (photoUrl) {
            element.style.backgroundImage = `url('${photoUrl}')`;
            element.style.backgroundSize = 'cover';
            element.style.backgroundPosition = 'center';
            element.innerHTML = '';
        } else {
            const initials = this.getInitials(name);
            const gradient = this.getGradientFromName(name);
            element.style.background = gradient;
            element.innerHTML = `<span class="text-white font-bold">${initials}</span>`;
        }
    },

    // =========================================
    // 3. EMPTY STATES ILUSTRADOS
    // =========================================
    
    /**
     * SVG ilustrações inline (não depende de CDN)
     */
    illustrations: {
        noResults: `<svg viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-36 mx-auto">
            <circle cx="100" cy="70" r="50" fill="currentColor" opacity="0.1"/>
            <circle cx="100" cy="70" r="35" fill="currentColor" opacity="0.1"/>
            <path d="M85 65 L95 75 L115 55" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity="0.3"/>
            <path d="M75 100 Q100 120 125 100" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.4"/>
            <circle cx="85" cy="60" r="5" fill="currentColor" opacity="0.4"/>
            <circle cx="115" cy="60" r="5" fill="currentColor" opacity="0.4"/>
            <text x="100" y="140" text-anchor="middle" fill="currentColor" opacity="0.6" font-size="12">Nenhum resultado</text>
        </svg>`,
        
        emptyList: `<svg viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-36 mx-auto">
            <rect x="40" y="30" width="120" height="20" rx="4" fill="currentColor" opacity="0.1"/>
            <rect x="40" y="60" width="100" height="20" rx="4" fill="currentColor" opacity="0.08"/>
            <rect x="40" y="90" width="80" height="20" rx="4" fill="currentColor" opacity="0.06"/>
            <circle cx="160" cy="110" r="25" fill="currentColor" opacity="0.15"/>
            <path d="M150 110 L160 120 L175 100" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.4"/>
        </svg>`,
        
        noClients: `<svg viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-36 mx-auto">
            <circle cx="100" cy="50" r="30" fill="currentColor" opacity="0.1"/>
            <circle cx="100" cy="45" r="15" fill="currentColor" opacity="0.15"/>
            <path d="M70 100 Q100 80 130 100" fill="currentColor" opacity="0.1"/>
            <path d="M60 110 Q100 85 140 110 L140 130 L60 130 Z" fill="currentColor" opacity="0.08"/>
            <line x1="85" y1="70" x2="115" y2="70" stroke="currentColor" stroke-width="2" opacity="0.3"/>
            <text x="100" y="145" text-anchor="middle" fill="currentColor" opacity="0.5" font-size="10">Sem clientes</text>
        </svg>`,
        
        error: `<svg viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-36 mx-auto">
            <circle cx="100" cy="70" r="45" fill="#ef4444" opacity="0.1"/>
            <circle cx="100" cy="70" r="30" fill="#ef4444" opacity="0.15"/>
            <line x1="85" y1="55" x2="115" y2="85" stroke="#ef4444" stroke-width="4" stroke-linecap="round" opacity="0.6"/>
            <line x1="115" y1="55" x2="85" y2="85" stroke="#ef4444" stroke-width="4" stroke-linecap="round" opacity="0.6"/>
            <text x="100" y="135" text-anchor="middle" fill="#ef4444" opacity="0.7" font-size="11">Ops! Algo deu errado</text>
        </svg>`,
        
        offline: `<svg viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-36 mx-auto">
            <path d="M60 90 Q80 60 100 70 Q120 80 140 60" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.2"/>
            <path d="M70 100 Q90 80 100 85 Q115 90 130 75" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity="0.3"/>
            <circle cx="100" cy="110" r="15" fill="currentColor" opacity="0.15"/>
            <line x1="60" y1="50" x2="140" y2="130" stroke="#ef4444" stroke-width="3" stroke-linecap="round" opacity="0.5"/>
            <text x="100" y="145" text-anchor="middle" fill="currentColor" opacity="0.5" font-size="10">Sem conexão</text>
        </svg>`,
        
        success: `<svg viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-36 mx-auto">
            <circle cx="100" cy="70" r="45" fill="#22c55e" opacity="0.1"/>
            <circle cx="100" cy="70" r="30" fill="#22c55e" opacity="0.15"/>
            <path d="M80 70 L95 85 L125 55" stroke="#22c55e" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" opacity="0.7"/>
        </svg>`
    },
    
    /**
     * Cria empty state com ilustração
     * @param {string} type - 'noResults', 'emptyList', 'noClients', 'error', 'offline', 'success'
     * @param {string} title - Título
     * @param {string} description - Descrição
     * @param {Object} action - { text: string, onClick: Function } (opcional)
     * @returns {string} - HTML do empty state
     */
    createEmptyState(type = 'emptyList', title = '', description = '', action = null) {
        const illustration = this.illustrations[type] || this.illustrations.emptyList;
        
        let actionHtml = '';
        if (action) {
            actionHtml = `<button class="mt-4 px-6 py-2 bg-primary text-white rounded-xl font-medium text-sm btn-press" onclick="${action.onClick}">${action.text}</button>`;
        }
        
        return `
            <div class="flex flex-col items-center justify-center py-12 px-4 text-gray-400 dark:text-gray-500 animate-fadeIn">
                <div class="mb-4 text-primary/60">${illustration}</div>
                ${title ? `<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">${title}</h3>` : ''}
                ${description ? `<p class="text-sm text-center max-w-xs">${description}</p>` : ''}
                ${actionHtml}
            </div>
        `;
    },
    
    /**
     * Aplica empty state em um container
     * @param {HTMLElement|string} container - Elemento ou seletor
     * @param {string} type - Tipo da ilustração
     * @param {string} title - Título
     * @param {string} description - Descrição
     */
    showEmptyState(container, type, title, description) {
        const el = typeof container === 'string' ? document.querySelector(container) : container;
        if (!el) return;
        
        el.innerHTML = this.createEmptyState(type, title, description);
    },

    // =========================================
    // 4. HAPTIC FEEDBACK
    // =========================================
    
    /**
     * Vibração leve (feedback tátil)
     * @param {number} duration - Duração em ms (padrão: 10)
     */
    hapticLight(duration = 10) {
        if ('vibrate' in navigator) {
            navigator.vibrate(duration);
        }
    },
    
    /**
     * Vibração média
     */
    hapticMedium() {
        if ('vibrate' in navigator) {
            navigator.vibrate(25);
        }
    },
    
    /**
     * Vibração forte (para erros/alertas)
     */
    hapticHeavy() {
        if ('vibrate' in navigator) {
            navigator.vibrate([30, 50, 30]);
        }
    },
    
    /**
     * Padrão de sucesso
     */
    hapticSuccess() {
        if ('vibrate' in navigator) {
            navigator.vibrate([10, 50, 20]);
        }
    },
    
    /**
     * Padrão de erro
     */
    hapticError() {
        if ('vibrate' in navigator) {
            navigator.vibrate([50, 100, 50, 100, 50]);
        }
    },
    
    /**
     * Adiciona haptic feedback a botões automaticamente
     * @param {string} selector - Seletor CSS dos botões
     * @param {string} type - 'light', 'medium', 'heavy'
     */
    addHapticToButtons(selector = '.btn-haptic, .btn-press, button[type="submit"]', type = 'light') {
        document.querySelectorAll(selector).forEach(btn => {
            if (btn.dataset.haptic) return; // Já tem
            btn.dataset.haptic = 'true';
            
            btn.addEventListener('click', () => {
                switch(type) {
                    case 'medium': this.hapticMedium(); break;
                    case 'heavy': this.hapticHeavy(); break;
                    default: this.hapticLight();
                }
            });
        });
    },

    // =========================================
    // 5. OPTIMISTIC UI
    // =========================================
    
    /**
     * Executa ação com UI otimista
     * Mostra sucesso imediato e reverte se der erro
     * @param {Object} options - Configuração
     * @param {Function} options.action - Função async que retorna Promise
     * @param {Function} options.onOptimistic - Callback para atualizar UI otimisticamente
     * @param {Function} options.onSuccess - Callback de sucesso
     * @param {Function} options.onError - Callback de erro (para reverter)
     * @param {string} options.successMessage - Mensagem de sucesso
     * @param {string} options.errorMessage - Mensagem de erro
     */
    async optimistic(options) {
        const {
            action,
            onOptimistic,
            onSuccess,
            onError,
            successMessage = 'Salvo!',
            errorMessage = 'Erro ao salvar. Tente novamente.'
        } = options;
        
        // 1. Aplica mudança otimista imediatamente
        if (onOptimistic) {
            onOptimistic();
        }
        
        // 2. Feedback tátil de sucesso
        this.hapticSuccess();
        
        // 3. Mostra toast de sucesso (assumindo que vai funcionar)
        this.showQuickToast(successMessage, 'success');
        
        try {
            // 4. Executa a ação real
            const result = await action();
            
            // 5. Callback de sucesso
            if (onSuccess) {
                onSuccess(result);
            }
            
            return result;
        } catch (error) {
            // 6. Reverte em caso de erro
            this.hapticError();
            this.showQuickToast(errorMessage, 'error');
            
            if (onError) {
                onError(error);
            }
            
            throw error;
        }
    },
    
    /**
     * Toast rápido para optimistic UI
     * @param {string} message - Mensagem
     * @param {string} type - 'success', 'error'
     */
    showQuickToast(message, type = 'info') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-primary'
        };
        
        const icons = {
            success: 'check_circle',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };
        
        // Remove toast existente
        const existing = document.querySelector('.quick-toast');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = `quick-toast fixed top-20 left-4 right-4 mx-auto max-w-sm ${colors[type]} text-white px-4 py-3 rounded-ios-lg shadow-lg flex items-center gap-3 z-[9999]`;
        toast.style.animation = 'slideInRight 0.3s ease-out';
        
        toast.innerHTML = `
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">${icons[type]}</span>
            <span class="flex-1 text-sm font-medium">${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideInLeft 0.3s ease-out reverse';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    },

    // =========================================
    // INICIALIZAÇÃO
    // =========================================
    
    /**
     * Inicializa todos os enhancements
     */
    init() {
        // Aplica gradiente dinâmico
        this.applyDynamicGradient();
        
        // Atualiza gradiente a cada hora
        setInterval(() => this.applyDynamicGradient(), 60 * 60 * 1000);
        
        // Adiciona haptic aos botões
        this.addHapticToButtons();
        
        // Re-aplica ao mudar tema
        const observer = new MutationObserver(() => {
            this.applyDynamicGradient();
        });
        observer.observe(document.documentElement, { 
            attributes: true, 
            attributeFilter: ['class'] 
        });
    }
};

// Auto-init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => UIEnhancements.init());
} else {
    UIEnhancements.init();
}

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIEnhancements;
}
