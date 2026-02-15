/**
 * Dashboard Page Module
 * Ondeline Tech - App do Técnico
 */

App.initDashboardPage = async function() {
    const user = API.getUser();

    const greetingEl = document.querySelector('h1');
    if (greetingEl && user) {
        greetingEl.textContent = `Olá, ${user.full_name || user.username}!`;
    }

    this.updateHeaderProfile(user);
    this.setupBottomNavigation();

    const cadastrarBtn = document.querySelector('button');
    if (cadastrarBtn && cadastrarBtn.textContent.includes('Cadastrar')) {
        cadastrarBtn.addEventListener('click', () => {
            window.location.href = 'novo-cadastro.html';
        });
    }

    this.showLoading(true);
    try {
        const response = await API.getDashboard();
        if (response.success) {
            this.updateDashboardStats(response.data);
        } else {
            this.showToast('Erro ao carregar dashboard', 'error');
        }
    } catch (error) {
        this.showToast('Erro ao carregar dashboard', 'error');
    } finally {
        this.showLoading(false);
    }
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
            statTotal.textContent = '100+';
        }
    }

    if (data.lastRegistration) {
        const lastRegInfo = document.getElementById('last-reg-info');
        const lastRegPlan = document.getElementById('last-reg-plan');

        if (lastRegInfo) {
            const createdAt = new Date(data.lastRegistration.created_at);
            const timeStr = createdAt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            lastRegInfo.textContent = `${data.lastRegistration.name} • ${timeStr}`;
        }

        if (lastRegPlan) {
            lastRegPlan.textContent = data.lastRegistration.planId || 'Sem plano';
        }
    }
};
