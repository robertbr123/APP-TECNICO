<!DOCTYPE html>
<html class="light" lang="pt-BR">
<head>
    <title>Painel do Técnico - Ondeline</title>
    <?php include 'partials/head.php'; ?>
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
<div class="flex items-center justify-between px-4 h-16">
<div class="flex items-center gap-3">
<div class="relative">
<div id="user-avatar" class="size-10 rounded-full border border-gray-100 dark:border-gray-800 bg-center bg-cover bg-primary/10 flex items-center justify-center">
<span class="material-symbols-outlined text-primary text-xl">person</span>
</div>
<div class="absolute bottom-0 right-0 size-3 bg-green-500 border-2 border-white dark:border-black rounded-full"></div>
</div>
<div class="flex flex-col">
<span id="header-user-name" class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white leading-none">Ondeline</span>
<span id="header-user-role" class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tecnico de Campo</span>
</div>
</div>
<button class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
<span class="material-symbols-outlined text-[22px]">notifications</span>
</button>
</div>
</header>
<main class="px-4 pb-32">
<section class="pt-4 pb-2">
<h1 id="greeting-text" class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Olá!</h1>
<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gerencie suas atividades de hoje.</p>
</section>
    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 pt-4 bento-grid">
        
        <!-- Card 1: Cadastros Hoje (Grande - Sparkline) -->
        <div class="col-span-2 glass-premium p-6 rounded-ios-xl shadow-lg relative overflow-hidden group card-interactive">
            <div class="absolute top-0 right-0 p-4 opacity-50">
                <span class="material-symbols-outlined text-[48px] text-primary/10">person_add</span>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Cadastros Hoje</p>
                <div class="flex items-baseline gap-2">
                    <span id="stat-today" class="text-4xl font-bold text-gray-900 dark:text-white tracking-tight">-</span>
                    <span id="stat-trend" class="text-sm font-medium text-green-500 flex items-center bg-green-500/10 px-2 py-0.5 rounded-full opacity-0 transition-opacity duration-500">
                        <span class="material-symbols-outlined text-[14px] mr-1">trending_up</span>
                        <span id="trend-value">+0%</span>
                    </span>
                </div>
                <!-- Mini Sparkline Animado -->
                <div class="mt-4 h-12 flex items-end gap-1 opacity-70">
                    <div class="w-1/6 bg-primary/20 rounded-t-sm sparkline-bar" style="height: 30%"></div>
                    <div class="w-1/6 bg-primary/30 rounded-t-sm sparkline-bar" style="height: 50%"></div>
                    <div class="w-1/6 bg-primary/40 rounded-t-sm sparkline-bar" style="height: 40%"></div>
                    <div class="w-1/6 bg-primary/60 rounded-t-sm sparkline-bar" style="height: 70%"></div>
                    <div class="w-1/6 bg-primary/80 rounded-t-sm sparkline-bar" style="height: 60%"></div>
                    <div class="w-1/6 bg-primary rounded-t-sm sparkline-bar shadow-[0_0_10px_rgba(19,91,236,0.5)]" style="height: 90%"></div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Clientes -->
        <div class="col-span-1 glass-premium p-5 rounded-ios-xl shadow-lg flex flex-col justify-between group card-interactive">
            <div class="flex justify-between items-start">
                <div class="p-2 rounded-xl bg-green-500/10 text-green-500">
                    <span class="material-symbols-outlined text-[24px]">groups</span>
                </div>
            </div>
            <div class="mt-4">
                <span id="stat-total" class="text-3xl font-bold text-gray-900 dark:text-white block">-</span>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total</p>
            </div>
        </div>

        <!-- Card 3: Metas -->
        <div class="col-span-1 bg-gradient-to-br from-primary to-blue-600 p-5 rounded-ios-xl shadow-lg shadow-primary/30 flex flex-col justify-between text-white group card-interactive">
            <div class="flex justify-between items-start opacity-90">
                <span class="material-symbols-outlined text-[24px]">flag</span>
            </div>
            <div class="mt-4">
                <div class="flex items-end gap-1 mb-1">
                    <span id="stat-meta" class="text-3xl font-bold">-</span>
                </div>
                <p class="text-xs font-semibold opacity-80 uppercase">Meta Mensal</p>
                <!-- Progress Bar com shimmer -->
                <div class="w-full h-1.5 bg-black/20 rounded-full mt-3 overflow-hidden">
                    <div id="meta-progress" class="h-full bg-white/90 rounded-full transition-all duration-1000 ease-out progress-shimmer" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Card 4: Técnicos Online — Admin Only -->
        <div id="card-tech-online" class="hidden col-span-2 glass-premium p-5 rounded-ios-xl shadow-lg group card-interactive">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-purple-500/10 text-purple-500">
                        <span class="material-symbols-outlined text-[22px]" style="font-variation-settings:'FILL' 1">engineering</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Técnicos Online</p>
                        <span id="stat-tech-online" class="text-3xl font-bold text-gray-900 dark:text-white">-</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span id="tech-live-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                        <span class="size-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>Ao vivo
                    </span>
                    <span id="tech-last-update" class="text-[10px] text-gray-400 dark:text-gray-500">—</span>
                </div>
            </div>
            <!-- OS Status Row -->
            <div class="grid grid-cols-3 gap-2 mb-3" id="os-status-row">
                <div class="text-center p-2 rounded-xl bg-yellow-50 dark:bg-yellow-900/20">
                    <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400" id="os-open-count">-</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-medium">Abertas</p>
                </div>
                <div class="text-center p-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20">
                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400" id="os-progress-count">-</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-medium">Em Andamento</p>
                </div>
                <div class="text-center p-2 rounded-xl bg-green-50 dark:bg-green-900/20">
                    <p class="text-lg font-bold text-green-600 dark:text-green-400" id="os-done-count">-</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-medium">Concluídas</p>
                </div>
            </div>
            <!-- Lista prévia dos técnicos -->
            <div id="tech-list-preview" class="space-y-1.5"></div>
        </div>
    </div>

    <!-- Quick Actions (Fab-like buttons in grid) -->
    <div class="grid grid-cols-4 gap-3 mb-10">
        <button id="btn-novo" class="col-span-2 bg-gray-900 dark:bg-white text-white dark:text-black p-4 rounded-ios-xl shadow-lg flex items-center justify-center gap-2 font-bold active:scale-[0.97] transition-all btn-press">
            <span class="material-symbols-outlined">add</span>
            <span>Novo</span>
        </button>
        <a href="mapa.php" class="col-span-1 bg-white/70 dark:bg-card-dark/70 backdrop-blur-md p-4 rounded-ios-xl shadow border border-white/20 dark:border-white/5 flex items-center justify-center text-primary active:scale-[0.97] transition-all">
            <span class="material-symbols-outlined">map</span>
        </a>
        <a href="ponto.php" class="col-span-1 bg-white/70 dark:bg-card-dark/70 backdrop-blur-md p-4 rounded-ios-xl shadow border border-white/20 dark:border-white/5 flex items-center justify-center text-orange-500 active:scale-[0.97] transition-all">
            <span class="material-symbols-outlined">schedule</span>
        </a>
    </div>

    <!-- Recent Activities List (Staggered Animation Wrapper) -->
    <section>
        <div class="flex items-center justify-between mb-4 px-1">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                Atividades Recentes
                <span class="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full pulse-ring">HOJE</span>
            </h2>
            <a href="historico.php" class="text-primary text-xs font-bold uppercase tracking-wide hover:opacity-70 btn-press">Ver tudo</a>
        </div>
        
        <div class="space-y-3 stagger-list" id="recent-activities">
            <a href="consultar.php?mode=recent" class="glass-premium p-4 rounded-ios-xl flex items-center gap-4 shadow-sm cursor-pointer card-interactive">
                <div class="size-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-green-400 to-green-600 text-white shadow-lg shadow-green-500/20">
                    <span class="material-symbols-outlined text-[24px]">person_add</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[15px] font-bold text-gray-900 dark:text-white truncate">Novos Clientes</p>
                    <p id="last-reg-info" class="text-[13px] text-gray-500 dark:text-gray-400 font-medium">Ver lista completa</p>
                </div>
                <div class="size-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward_ios</span>
                </div>
            </a>

            <a href="vincular-equipamento.php" class="glass-premium p-4 rounded-ios-xl flex items-center gap-4 shadow-sm card-interactive">
                <div class="size-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 text-white shadow-lg shadow-purple-500/20">
                    <span class="material-symbols-outlined text-[24px]">router</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[15px] font-bold text-gray-900 dark:text-white truncate">Equipamentos</p>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400 font-medium">Gerenciar vínculos</p>
                </div>
                <div class="size-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                     <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward_ios</span>
                </div>
            </a>
            
            <a href="checklist.php" class="glass-premium p-4 rounded-ios-xl flex items-center gap-4 shadow-sm card-interactive">
                <div class="size-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-orange-400 to-red-500 text-white shadow-lg shadow-orange-500/20">
                    <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1">support_agent</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[15px] font-bold text-gray-900 dark:text-white truncate">Suporte Técnico</p>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400 font-medium">Registrar atendimentos</p>
                </div>
                <div class="size-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                     <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward_ios</span>
                </div>
            </a>

            <a href="ordens.php" id="link-ordens" class="glass-premium p-4 rounded-ios-xl flex items-center gap-4 shadow-sm card-interactive">
                <div class="size-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-600 text-white shadow-lg shadow-blue-500/20">
                    <span class="material-symbols-outlined text-[24px]">assignment</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p id="link-ordens-title" class="text-[15px] font-bold text-gray-900 dark:text-white truncate">Ordens de Serviço</p>
                    <p id="link-ordens-subtitle" class="text-[13px] text-gray-500 dark:text-gray-400 font-medium">Gerenciar OS</p>
                </div>
                <div class="size-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                     <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward_ios</span>
                </div>
            </a>

            <a href="relatorios.php" id="link-relatorios" class="glass-premium p-4 rounded-ios-xl flex items-center gap-4 shadow-sm card-interactive admin-only">
                <div class="size-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-teal-600 text-white shadow-lg shadow-teal-500/20">
                    <span class="material-symbols-outlined text-[24px]">analytics</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[15px] font-bold text-gray-900 dark:text-white truncate">Relatórios</p>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400 font-medium">Exportar PDF / CSV</p>
                </div>
                <div class="size-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                     <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward_ios</span>
                </div>
            </a>

            <a href="admin.php" id="link-admin" class="glass-premium p-4 rounded-ios-xl flex items-center gap-4 shadow-sm card-interactive admin-only">
                <div class="size-12 flex items-center justify-center rounded-2xl bg-gradient-to-br from-rose-400 to-pink-600 text-white shadow-lg shadow-rose-500/20">
                    <span class="material-symbols-outlined text-[24px]">admin_panel_settings</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[15px] font-bold text-gray-900 dark:text-white truncate">Administração</p>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400 font-medium">Painel administrativo</p>
                </div>
                <div class="size-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                     <span class="material-symbols-outlined text-gray-400 text-sm">arrow_forward_ios</span>
                </div>
            </a>
        </div>
    </section>
</main>

<!-- Action Sheet iOS Style para Novo -->
<div id="action-sheet-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998] opacity-0 invisible transition-all duration-300"></div>
<div id="action-sheet" class="fixed bottom-0 left-0 right-0 z-[9999] transform translate-y-full transition-transform duration-300 ease-out">
    <div class="mx-3 mb-3 safe-bottom">
        <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl overflow-hidden shadow-2xl">
            <div class="px-4 py-3 border-b border-gray-200/50 dark:border-white/10">
                <p class="text-center text-sm font-semibold text-gray-500 dark:text-gray-400">O que você deseja cadastrar?</p>
            </div>
            <button id="action-cliente" class="w-full px-4 py-4 flex items-center gap-4 hover:bg-gray-100 dark:hover:bg-white/5 active:bg-gray-200 dark:active:bg-white/10 transition-colors border-b border-gray-200/50 dark:border-white/10">
                <div class="size-12 rounded-2xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/30">
                    <span class="material-symbols-outlined text-white text-[24px]" style="font-variation-settings: 'FILL' 1">person_add</span>
                </div>
                <div class="flex-1 text-left">
                    <p class="text-[17px] font-semibold text-gray-900 dark:text-white">Novo Cliente</p>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Cadastrar um novo cliente</p>
                </div>
                <span class="material-symbols-outlined text-gray-400 text-xl">chevron_right</span>
            </button>
            <button id="action-suporte" class="w-full px-4 py-4 flex items-center gap-4 hover:bg-gray-100 dark:hover:bg-white/5 active:bg-gray-200 dark:active:bg-white/10 transition-colors">
                <div class="size-12 rounded-2xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center shadow-lg shadow-orange-500/30">
                    <span class="material-symbols-outlined text-white text-[24px]" style="font-variation-settings: 'FILL' 1">support_agent</span>
                </div>
                <div class="flex-1 text-left">
                    <p class="text-[17px] font-semibold text-gray-900 dark:text-white">Suporte Técnico</p>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400">Registrar atendimento</p>
                </div>
                <span class="material-symbols-outlined text-gray-400 text-xl">chevron_right</span>
            </button>
        </div>
        <button id="action-cancel" class="w-full mt-2 bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl px-4 py-4 text-center shadow-2xl">
            <span class="text-[17px] font-semibold text-primary">Cancelar</span>
        </button>
    </div>
</div>

<?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>

<script src="js/api.js"></script>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script src="js/ui-enhancements.js"></script>
<script src="js/app.js"></script>
<script>
(function() {
    const btnNovo = document.getElementById('btn-novo');
    const overlay = document.getElementById('action-sheet-overlay');
    const sheet = document.getElementById('action-sheet');
    const btnCancel = document.getElementById('action-cancel');
    const actionCliente = document.getElementById('action-cliente');
    const actionSuporte = document.getElementById('action-suporte');
    function openSheet() {
        overlay.classList.remove('invisible', 'opacity-0');
        overlay.classList.add('opacity-100');
        sheet.classList.remove('translate-y-full');
        document.body.style.overflow = 'hidden';
        if (typeof UIEnhancements !== 'undefined') UIEnhancements.hapticMedium();
    }
    function closeSheet() {
        overlay.classList.add('opacity-0');
        sheet.classList.add('translate-y-full');
        setTimeout(() => { overlay.classList.add('invisible'); document.body.style.overflow = ''; }, 300);
    }
    btnNovo.addEventListener('click', openSheet);
    overlay.addEventListener('click', closeSheet);
    btnCancel.addEventListener('click', closeSheet);
    actionCliente.addEventListener('click', function() {
        if (typeof UIEnhancements !== 'undefined') UIEnhancements.hapticLight();
        closeSheet(); setTimeout(() => { window.location.href = 'novo-cadastro.php'; }, 200);
    });
    actionSuporte.addEventListener('click', function() {
        if (typeof UIEnhancements !== 'undefined') UIEnhancements.hapticLight();
        closeSheet(); setTimeout(() => { window.location.href = 'checklist.php'; }, 200);
    });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const greetingEl = document.getElementById('greeting-text');
    const avatarEl = document.getElementById('user-avatar');
    const userNameEl = document.getElementById('header-user-name');
    let userName = 'Técnico';
    let userPhoto = null;
    try {
        const userData = localStorage.getItem('user_data');
        if (userData) { const user = JSON.parse(userData); userName = user.full_name || user.name || user.username || 'Técnico'; userPhoto = user.photo || null; }
    } catch (e) {}
    if (greetingEl && typeof UIEnhancements !== 'undefined') {
        const greeting = UIEnhancements.getGreeting();
        const emoji = UIEnhancements.getTimeEmoji();
        const firstName = userName.split(' ')[0];
        greetingEl.textContent = greeting + ', ' + firstName + '! ' + emoji;
    }
    if (avatarEl && typeof UIEnhancements !== 'undefined') UIEnhancements.setAvatar(avatarEl, userName, userPhoto);
    if (userNameEl) userNameEl.textContent = userName;
    if (typeof API !== 'undefined' && API.isAuthenticated && API.isAuthenticated()) {
        try {
            const response = await API.getProfile();
            if (response && response.success && response.data) {
                const profile = response.data;
                const currentUser = JSON.parse(localStorage.getItem('user_data') || '{}');
                localStorage.setItem('user_data', JSON.stringify({...currentUser, ...profile}));
                if (profile.photo && avatarEl && typeof UIEnhancements !== 'undefined') UIEnhancements.setAvatar(avatarEl, userName, profile.photo);
            }
        } catch (e) {}
    }
    const statToday = document.getElementById('stat-today');
    const statTotal = document.getElementById('stat-total');
    const statTrend = document.getElementById('stat-trend');
    setTimeout(() => {
        if (statToday && statToday.textContent === '-') Animations.countUp(statToday, 12, 800);
        if (statTotal && statTotal.textContent === '-') Animations.countUp(statTotal, 847, 1200);
        if (statTrend) setTimeout(() => { statTrend.style.opacity = '1'; }, 500);
    }, 400);

    // ── Painel Técnicos Online — Admin Only ──
    try {
        const ud = JSON.parse(localStorage.getItem('user_data') || '{}');
        if (ud.role === 'admin') {
            const card = document.getElementById('card-tech-online');
            if (card) card.classList.remove('hidden');
            loadTechnicianWidget();
            setInterval(loadTechnicianWidget, 60000); // Atualiza a cada 60s
        }
    } catch (e) {}
});

async function loadTechnicianWidget() {
    try {
        // Técnicos online
        const techRes = await API.getActiveTechnicians();
        if (techRes && techRes.success) {
            const techs = techRes.data || [];
            const onlineCount = techs.filter(t => t.is_active == 1 && parseInt(t.minutes_ago || 999) < 60).length;

            const statEl = document.getElementById('stat-tech-online');
            if (statEl) statEl.textContent = onlineCount;

            // Timestamp da última atualização
            const lastUpdateEl = document.getElementById('tech-last-update');
            if (lastUpdateEl) {
                const now = new Date();
                lastUpdateEl.textContent = 'Atualizado ' + now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            }

            // Lista prévia dos técnicos (máx 4)
            const listEl = document.getElementById('tech-list-preview');
            if (listEl && techs.length > 0) {
                const activityLabels = {
                    app_active: 'Ativo', os_started: 'Em OS', break: 'Em pausa',
                    app_background: 'Inativo', offline: 'Offline'
                };
                const activityColors = {
                    app_active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    os_started: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                    break: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    app_background: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
                    offline: 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600'
                };

                listEl.innerHTML = techs.slice(0, 4).map(t => {
                    const initials = (t.full_name || t.username || '?').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
                    const minsAgo = parseInt(t.minutes_ago || 999);
                    const isOnline = t.is_active == 1 && minsAgo < 60;
                    const activity = t.last_activity || (isOnline ? 'app_active' : 'offline');
                    const actLabel = activityLabels[activity] || activity.replace('_', ' ');
                    const actColor = activityColors[activity] || activityColors.offline;

                    return `<div class="flex items-center gap-2 py-1">
                        <div class="relative flex-shrink-0">
                            <div class="size-7 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-[10px] font-bold text-purple-700 dark:text-purple-300">${initials}</div>
                            ${isOnline ? '<div class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full bg-green-500 border border-white dark:border-gray-900"></div>' : ''}
                        </div>
                        <span class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">${escapeHtmlDash(t.full_name || t.username)}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium ${actColor}">${actLabel}</span>
                    </div>`;
                }).join('');

                if (techs.length > 4) {
                    listEl.innerHTML += `<p class="text-[10px] text-gray-400 dark:text-gray-600 text-center mt-1">+${techs.length - 4} técnicos</p>`;
                }
            } else if (listEl) {
                listEl.innerHTML = '<p class="text-xs text-gray-400 dark:text-gray-500 text-center py-1">Nenhum técnico online agora</p>';
            }
        }

        // OS de hoje
        const osRes = await API.getWorkOrders({ limit: 500 });
        if (osRes && osRes.success) {
            const today = new Date().toISOString().split('T')[0];
            const allOS = osRes.data || [];
            const todayOS = allOS.filter(o => (o.created_at || '').startsWith(today));
            const openEl = document.getElementById('os-open-count');
            const progEl = document.getElementById('os-progress-count');
            const doneEl = document.getElementById('os-done-count');
            if (openEl) openEl.textContent = allOS.filter(o => o.status === 'open' || o.status === 'assigned').length;
            if (progEl) progEl.textContent = allOS.filter(o => o.status === 'in_progress').length;
            if (doneEl) doneEl.textContent = todayOS.filter(o => o.status === 'completed').length;
        }
    } catch (e) {
        console.warn('[Dashboard] Erro ao atualizar widget de técnicos:', e);
    }
}

function escapeHtmlDash(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
</body>
</html>
