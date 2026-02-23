/**
 * Dashboard Page Module
 * Ondeline Tech - App do Técnico
 */

App.initDashboardPage = async function() {
    const user = API.getUser();

    const greetingEl = document.querySelector('h1');
    if (greetingEl && user) {
        greetingEl.textContent = `Olá, ${user.full_name.split(' ')[0] || user.username}!`;
    }

    this.updateHeaderProfile(user);
    this.setupBottomNavigation();

    // Hide admin-only links for non-admin users
    if (!user || user.role !== 'admin') {
        document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'none');
    }

    // Hide real items initially to show skeleton
    document.querySelectorAll('#recent-activities > a').forEach(el => el.classList.add('hidden'));

    // Remove legacy loading (full screen)
    // this.showLoading(true); 

    try {
        // Simulate network delay for skeleton demo (remove in production)
        const response = await API.getDashboard();
        
        if (response.success) {
            this.updateDashboardStats(response.data);
            // Small delay to ensure smooth transition
            setTimeout(() => this.revealDashboardContent(), 300);
        } else {
            this.showToast('Erro ao carregar dashboard', 'error');
            this.revealDashboardContent(); // Show even if error (empty state)
        }
    } catch (error) {
        console.error(error);
        this.showToast('Erro de conexão', 'error');
        this.revealDashboardContent();
    }
};

App.revealDashboardContent = function() {
    // 1. Remove Skeleton Items
    const skeletons = document.querySelectorAll('.skeleton-item');
    skeletons.forEach(el => {
        el.classList.add('opacity-0', 'transition-opacity', 'duration-300');
        setTimeout(() => el.remove(), 300);
    });

    // 2. Reveal Real Activity Items (Staggered)
    const realItems = document.querySelectorAll('#recent-activities > a');
    realItems.forEach((el, index) => {
        el.classList.remove('hidden');
        el.classList.add('stagger-item');
        // Manually set delay if CSS nth-child isn't enough
        el.style.animationDelay = `${index * 100}ms`;
    });

    // 3. Stop Pulsing on Stats
    const statElements = ['stat-today', 'stat-total'];
    statElements.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('animate-pulse', 'bg-gray-200', 'dark:bg-gray-700', 'rounded', 'h-10', 'w-16', 'h-8', 'w-12', 'text-transparent');
        }
    });
};

App.updateDashboardStats = function(data) {
    const statToday = document.getElementById('stat-today');
    if (statToday) statToday.textContent = data.totals.today || '0';

    const statTotal = document.getElementById('stat-total');
    if (statTotal) {
        const user = API.getUser();
        const isAdmin = user && user.role === 'admin';
        if (isAdmin) {
            statTotal.textContent = data.totals.clients || '0';
        } else {
            statTotal.textContent = '100+'; // Placeholder if not admin
        }
    }

    // Meta mensal real
    if (data.meta) {
        const statMeta = document.getElementById('stat-meta');
        const metaProgress = document.getElementById('meta-progress');
        if (statMeta) {
            if (typeof Animations !== 'undefined' && Animations.countUp) {
                Animations.countUp(statMeta, data.meta.percent, 1000, '%');
            } else {
                statMeta.textContent = data.meta.percent + '%';
            }
        }
        if (metaProgress) {
            setTimeout(() => {
                metaProgress.style.width = data.meta.percent + '%';
            }, 300);
        }
    }

    if (data.lastRegistration) {
        const lastRegInfo = document.getElementById('last-reg-info');
        if (lastRegInfo) {
            const createdAt = new Date(data.lastRegistration.created_at);
            const timeStr = createdAt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            lastRegInfo.textContent = `${data.lastRegistration.name.split(' ')[0]} • ${timeStr}`;
        }
    }
};
