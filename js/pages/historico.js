/**
 * Historico Page Module
 * Ondeline Tech - App do Técnico
 */

App.initHistoricoPage = async function() {
    this.setupBottomNavigation();

    try {
        const response = await API.getHistorico();
        if (!response.success) {
            this.showToast('Erro ao carregar dados', 'error');
            return;
        }

        const d = response.data;

        const userNameDisplay = document.getElementById('user-name-display');
        if (userNameDisplay && d.username) {
            userNameDisplay.textContent = `\u{1F464} ${d.username}`;
        }

        const statToday = document.getElementById('stat-today');
        const statWeek = document.getElementById('stat-week');
        const statMonth = document.getElementById('stat-month');
        const statRanking = document.getElementById('stat-ranking');

        if (statToday) statToday.textContent = d.todayInstallations;
        if (statWeek) statWeek.textContent = d.weekInstallations;
        if (statMonth) statMonth.textContent = d.monthInstallations;
        if (statRanking) {
            statRanking.textContent = d.ranking > 0 ? `#${d.ranking}` : '-';
        }

        const monthCount = document.getElementById('month-count');
        const monthGoal = document.getElementById('month-goal');
        const progressPercent = document.getElementById('progress-percent');
        const progressBar = document.getElementById('progress-bar');
        const progressCircle = document.getElementById('progress-circle');

        const goal = d.monthlyGoal || 10;
        const monthInstalls = d.monthInstallations || 0;
        const percent = goal > 0
            ? Math.min(100, Math.round((monthInstalls / goal) * 100))
            : 0;

        if (monthCount) monthCount.textContent = monthInstalls;
        if (monthGoal) monthGoal.textContent = goal;
        if (progressPercent) progressPercent.textContent = `${percent}%`;
        if (progressBar) progressBar.style.width = `${percent}%`;

        if (progressCircle) {
            const circumference = 213.63;
            const offset = circumference - (percent / 100) * circumference;
            setTimeout(() => {
                progressCircle.style.strokeDashoffset = offset;
            }, 100);
        }

        const compIcon = document.getElementById('comparison-icon');
        const compValue = document.getElementById('comparison-value');
        const compDetail = document.getElementById('comparison-detail');

        if (compValue && compDetail) {
            const diff = d.monthInstallations - d.prevMonthInstallations;
            const percentChange = d.prevMonthInstallations > 0
                ? Math.round((diff / d.prevMonthInstallations) * 100)
                : (d.monthInstallations > 0 ? 100 : 0);

            if (diff >= 0) {
                if (compIcon) {
                    compIcon.textContent = 'trending_up';
                    compIcon.className = 'material-symbols-outlined text-green-500 text-[20px]';
                }
                compValue.textContent = `+${percentChange}%`;
            } else {
                if (compIcon) {
                    compIcon.textContent = 'trending_down';
                    compIcon.className = 'material-symbols-outlined text-red-500 text-[20px]';
                }
                compValue.textContent = `${percentChange}%`;
            }
            compDetail.textContent = `${d.prevMonthInstallations} no mes anterior`;
        }

        const streakValue = document.getElementById('streak-value');
        if (streakValue) streakValue.textContent = d.streak;

        this.renderDailyChart(d.dailyBreakdown);

        const chartLabel = document.getElementById('chart-month-label');
        if (chartLabel) {
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            const now = new Date();
            chartLabel.textContent = `${months[now.getMonth()]} ${now.getFullYear()}`;
        }

        this.setMotivationMessage(d);

    } catch (error) {
        this.showToast('Erro ao carregar dados de desempenho', 'error');

        // Mostra estado de erro no card de motivação
        const titleEl = document.getElementById('motivation-title');
        const subtitleEl = document.getElementById('motivation-subtitle');
        const emojiEl = document.getElementById('motivation-emoji');
        if (titleEl) titleEl.textContent = 'Erro ao carregar';
        if (subtitleEl) subtitleEl.textContent = 'Verifique sua conexão e tente novamente.';
        if (emojiEl) emojiEl.innerHTML = '&#x26A0;&#xFE0F;';

        // Mostra estado de erro no gráfico
        const chartEl = document.getElementById('daily-chart');
        if (chartEl) {
            chartEl.innerHTML = `
                <div class="flex flex-col items-center justify-center w-full h-full text-gray-400">
                    <span class="material-symbols-outlined text-3xl">signal_disconnected</span>
                    <p class="text-xs mt-1">Sem dados</p>
                </div>`;
        }

        // Preenche comparativo com "-"
        const compDetail = document.getElementById('comparison-detail');
        if (compDetail) compDetail.textContent = 'Indisponível';
    }
};

App.setMotivationMessage = function(data) {
    const titleEl = document.getElementById('motivation-title');
    const subtitleEl = document.getElementById('motivation-subtitle');
    const emojiEl = document.getElementById('motivation-emoji');
    const cardEl = document.getElementById('motivation-card');

    if (!titleEl || !subtitleEl) return;

    const percent = data.monthlyGoal > 0
        ? Math.round((data.monthInstallations / data.monthlyGoal) * 100)
        : 0;

    let title, subtitle, emoji, gradient;

    if (data.ranking > 0 && data.ranking <= 3) {
        const medals = ['&#129351;', '&#129352;', '&#129353;'];
        emoji = medals[data.ranking - 1];
        title = `Top ${data.ranking}! Voce e destaque!`;
        subtitle = 'Continue nesse ritmo incrivel, voce esta entre os melhores da equipe!';
        gradient = 'from-yellow-500 to-orange-500';
    } else if (percent >= 100) {
        emoji = '&#127942;';
        title = 'Meta atingida!';
        subtitle = 'Parabens! Voce bateu a meta do mes. Que tal superar ainda mais?';
        gradient = 'from-green-500 to-emerald-600';
    } else if (percent >= 80) {
        emoji = '&#128293;';
        title = 'Quase la!';
        subtitle = `Faltam apenas ${data.monthlyGoal - data.monthInstallations} para bater a meta. Voce consegue!`;
        gradient = 'from-orange-500 to-red-500';
    } else if (percent >= 50) {
        emoji = '&#128170;';
        title = 'Bom progresso!';
        subtitle = 'Voce esta no caminho certo. Mantenha o foco e continue evoluindo!';
        gradient = 'from-primary to-blue-700';
    } else if (data.todayInstallations > 0) {
        emoji = '&#11088;';
        title = 'Bom trabalho hoje!';
        subtitle = `Voce ja fez ${data.todayInstallations} cadastro(s) hoje. Cada um conta!`;
        gradient = 'from-primary to-blue-700';
    } else {
        emoji = '&#128640;';
        title = 'Vamos comecar!';
        subtitle = 'Cada instalacao conta. Faca a diferenca hoje!';
        gradient = 'from-primary to-blue-700';
    }

    if (emojiEl) emojiEl.innerHTML = emoji;
    titleEl.textContent = title;
    subtitleEl.textContent = subtitle;
    if (cardEl && gradient !== 'from-primary to-blue-700') {
        cardEl.className = `bg-gradient-to-br ${gradient} rounded-ios-xl p-6 text-white shadow-lg`;
    }
};

App.renderDailyChart = function(dailyData) {
    const chartEl = document.getElementById('daily-chart');
    if (!chartEl) return;

    const now = new Date();
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    const today = now.getDate();

    const dataMap = {};
    let maxVal = 1;
    dailyData.forEach(item => {
        const day = parseInt(item.date.split('-')[2]);
        dataMap[day] = parseInt(item.total);
        if (dataMap[day] > maxVal) maxVal = dataMap[day];
    });

    let html = '';
    for (let day = 1; day <= daysInMonth; day++) {
        const val = dataMap[day] || 0;
        const heightPercent = maxVal > 0 ? (val / maxVal) * 100 : 0;
        const isToday = day === today;
        const isFuture = day > today;

        const barColor = isFuture
            ? 'bg-gray-100 dark:bg-gray-800'
            : (isToday ? 'bg-primary' : (val > 0 ? 'bg-primary/60' : 'bg-gray-200 dark:bg-gray-700'));

        const minHeight = isFuture ? '4px' : (val > 0 ? `${Math.max(heightPercent, 8)}%` : '4px');

        html += `<div class="flex flex-col items-center gap-1 flex-1 min-w-[8px]">
            <div class="w-full rounded-t-sm bar-animate ${barColor}" style="height: ${minHeight}" title="Dia ${day}: ${val}"></div>
            ${isToday ? `<span class="text-[8px] font-bold text-primary">${day}</span>` : (day % 5 === 0 || day === 1 ? `<span class="text-[8px] text-gray-400">${day}</span>` : '')}
        </div>`;
    }

    chartEl.innerHTML = html;

    setTimeout(() => {
        chartEl.querySelectorAll('.bar-animate').forEach(bar => {
            bar.style.height = bar.style.height;
        });
    }, 100);
};
