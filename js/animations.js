/**
 * Animations Module
 * Ondeline Tech - Premium UI Animations
 * 
 * Provides celebration animations, success feedback, and UI effects
 * Works without any npm dependencies - pure vanilla JS + CSS
 */

const Animations = {
    
    /**
     * Initializes stagger animation for a list container
     * @param {string|HTMLElement} container - Selector or element
     * @param {number} delay - Optional delay before starting (ms)
     */
    staggerList(container, delay = 100) {
        const el = typeof container === 'string' ? document.querySelector(container) : container;
        if (!el) return;
        
        setTimeout(() => {
            el.classList.add('loaded');
        }, delay);
    },

    /**
     * Initializes Bento Grid animations
     * @param {string|HTMLElement} container - Selector or element
     */
    bentogrid(container) {
        const el = typeof container === 'string' ? document.querySelector(container) : container;
        if (!el) return;
        
        el.classList.add('bento-grid');
        setTimeout(() => {
            el.classList.add('loaded');
        }, 100);
    },

    /**
     * Shows a success animation (checkmark in circle)
     * @param {Object} options - Configuration
     * @param {string} options.message - Success message
     * @param {number} options.duration - How long to show (ms)
     * @param {Function} options.onComplete - Callback when done
     */
    success(options = {}) {
        const {
            message = 'Sucesso!',
            duration = 2000,
            onComplete = null
        } = options;

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[9999] flex items-center justify-center modal-backdrop animate-overlay';
        
        overlay.innerHTML = `
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-2xl flex flex-col items-center gap-4 bounce-in">
                <div class="relative">
                    <svg class="w-20 h-20" viewBox="0 0 52 52">
                        <circle class="animate-circle-fill" cx="26" cy="26" r="25" fill="#22c55e"/>
                        <path class="animate-checkmark" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 17-17"/>
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">${message}</p>
            </div>
        `;

        document.body.appendChild(overlay);

        // Auto remove
        setTimeout(() => {
            overlay.classList.add('animate-overlay');
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
                if (onComplete) onComplete();
            }, 300);
        }, duration);
        
        // Click to dismiss
        overlay.addEventListener('click', () => {
            overlay.remove();
            if (onComplete) onComplete();
        });
    },

    /**
     * Shows confetti celebration
     * @param {Object} options - Configuration
     * @param {number} options.count - Number of confetti pieces
     * @param {number} options.duration - Animation duration (ms)
     */
    confetti(options = {}) {
        const {
            count = 50,
            duration = 3000
        } = options;

        const colors = ['#135bec', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        const container = document.createElement('div');
        container.className = 'fixed inset-0 pointer-events-none z-[9999] overflow-hidden';
        document.body.appendChild(container);

        for (let i = 0; i < count; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.cssText = `
                left: ${Math.random() * 100}%;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                width: ${Math.random() * 10 + 5}px;
                height: ${Math.random() * 10 + 5}px;
                animation-delay: ${Math.random() * 0.5}s;
                animation-duration: ${duration / 1000 + Math.random()}s;
                border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
            `;
            container.appendChild(confetti);
        }

        setTimeout(() => container.remove(), duration + 1000);
    },

    /**
     * Shows success with confetti combo
     * @param {string} message - Success message
     * @param {Function} onComplete - Callback when done
     */
    celebrate(message = 'Concluído!', onComplete = null) {
        this.confetti({ count: 60 });
        this.success({ message, duration: 2500, onComplete });
    },

    /**
     * Animates a number counting up
     * @param {HTMLElement} element - Element to animate
     * @param {number} target - Target number
     * @param {number} duration - Animation duration (ms)
     * @param {string} suffix - Suffix to add (%, etc)
     */
    countUp(element, target, duration = 1000, suffix = '') {
        if (!element) return;
        
        const start = 0;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (target - start) * easeOut);
            
            element.textContent = current + suffix;
            element.classList.add('animate-count');
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    },

    /**
     * Shows skeleton loading state for an element
     * @param {HTMLElement} container - Container element
     * @param {number} count - Number of skeleton items
     * @param {string} type - 'card', 'list', 'line'
     */
    showSkeleton(container, count = 3, type = 'card') {
        if (!container) return;
        
        let html = '';
        
        for (let i = 0; i < count; i++) {
            if (type === 'card') {
                html += `
                    <div class="skeleton-card flex items-center gap-4">
                        <div class="skeleton-circle w-12 h-12 shrink-0"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton-line w-3/4 h-4"></div>
                            <div class="skeleton-line w-1/2 h-3"></div>
                        </div>
                    </div>
                `;
            } else if (type === 'list') {
                html += `
                    <div class="skeleton-card p-4">
                        <div class="flex items-center gap-4">
                            <div class="skeleton-circle w-10 h-10"></div>
                            <div class="flex-1">
                                <div class="skeleton-line w-2/3 h-4 mb-2"></div>
                                <div class="skeleton-line w-1/3 h-3"></div>
                            </div>
                            <div class="skeleton w-8 h-8 rounded-full"></div>
                        </div>
                    </div>
                `;
            } else if (type === 'line') {
                html += `<div class="skeleton-line h-4 mb-2" style="width: ${60 + Math.random() * 40}%"></div>`;
            }
        }
        
        container.innerHTML = html;
    },

    /**
     * Adds ripple effect to an element
     * @param {HTMLElement} element - Element to add ripple to
     */
    addRipple(element) {
        if (!element) return;
        element.classList.add('ripple');
    },

    /**
     * Loads Lottie animation (requires Lottie CDN)
     * @param {HTMLElement} container - Container for animation
     * @param {string} animationPath - URL to Lottie JSON
     * @param {Object} options - Lottie options
     */
    lottie(container, animationPath, options = {}) {
        // Check if Lottie is loaded
        if (typeof lottie === 'undefined') {
            console.warn('Lottie not loaded. Add: <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>');
            return null;
        }
        
        return lottie.loadAnimation({
            container: container,
            renderer: 'svg',
            loop: options.loop ?? false,
            autoplay: options.autoplay ?? true,
            path: animationPath,
            ...options
        });
    },

    /**
     * Pre-built Lottie success animation
     * @param {HTMLElement} container - Container element
     */
    lottieSuccess(container) {
        return this.lottie(container, 'https://assets3.lottiefiles.com/packages/lf20_jbrw3hcz.json', {
            loop: false,
            autoplay: true
        });
    },

    /**
     * Pre-built Lottie loading animation
     * @param {HTMLElement} container - Container element
     */
    lottieLoading(container) {
        return this.lottie(container, 'https://assets5.lottiefiles.com/packages/lf20_p8bfn5to.json', {
            loop: true,
            autoplay: true
        });
    },

    /**
     * Shows a toast notification with animation
     * @param {string} message - Message to show
     * @param {string} type - 'success', 'error', 'info', 'warning'
     * @param {number} duration - Duration in ms
     */
    toast(message, type = 'info', duration = 3000) {
        const icons = {
            success: 'check_circle',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-primary'
        };
        
        const toast = document.createElement('div');
        toast.className = `fixed bottom-24 left-4 right-4 mx-auto max-w-sm ${colors[type]} text-white px-4 py-3 rounded-ios-lg shadow-lg flex items-center gap-3 z-[9999] animate-slide-up`;
        
        toast.innerHTML = `
            <span class="material-symbols-outlined">${icons[type]}</span>
            <span class="flex-1 text-sm font-medium">${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /**
     * Initialize all animations on page load
     * Call this in your page's DOMContentLoaded
     */
    init() {
        // Auto-init stagger lists
        document.querySelectorAll('.stagger-list').forEach(el => {
            this.staggerList(el);
        });
        
        // Auto-init bento grids
        document.querySelectorAll('.bento-grid').forEach(el => {
            setTimeout(() => el.classList.add('loaded'), 100);
        });
        
        // Auto-add ripple to buttons with .ripple class
        document.querySelectorAll('.btn-press, .ripple').forEach(el => {
            this.addRipple(el);
        });
    }
};

// Auto-init on DOMContentLoaded if not already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Animations.init());
} else {
    Animations.init();
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Animations;
}
