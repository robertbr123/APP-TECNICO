<!DOCTYPE html>
<html class="light" lang="pt-BR"><head>
<title>Meu Desempenho - Ondeline</title>
<?php include 'partials/head.php'; ?>
<style>
.progress-ring {
        transform: rotate(-90deg);
    }
    .progress-ring circle {
        transition: stroke-dashoffset 1s ease-in-out;
    }
    .bar-animate {
        transition: height 0.8s ease-out;
    }
</style>
</head>
<body class="min-h-screen">

<!-- Header -->
<header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
<div class="flex items-center justify-between px-4 h-16">
<button onclick="window.history.back()" class="flex items-center justify-center size-10 rounded-full text-primary">
<span class="material-symbols-outlined text-[26px]">arrow_back_ios</span>
</button>
<h1 class="text-[17px] font-bold tracking-tight">Meu Desempenho</h1>
<div class="size-10"></div>
</div>
</header>

<main class="px-4 pb-32">

<!-- Card motivacional -->
<section class="py-6">
<div id="motivation-card" class="bg-gradient-to-br from-primary to-blue-700 rounded-ios-xl p-6 text-white shadow-lg shadow-primary/25">
<div class="flex items-start gap-4">
<div class="text-4xl" id="motivation-emoji">&#128170;</div>
<div class="flex-1">
<p id="motivation-title" class="text-lg font-bold leading-tight">Carregando...</p>
<p id="motivation-subtitle" class="text-sm text-white/80 mt-1">Aguarde enquanto buscamos seus dados</p>
</div>
</div>
</div>
</section>

<!-- Progresso mensal -->
<section class="mb-6">
<div class="bg-white dark:bg-card-dark rounded-ios-xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-center justify-between mb-4">
<div>
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Sua Meta Mensal</p>
<p id="user-name-display" class="text-xs text-primary font-medium mt-0.5"></p>
<p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
<span id="month-count">0</span><span class="text-base font-normal text-gray-400">/<span id="month-goal">10</span></span>
</p>
</div>
<div class="relative">
<svg class="progress-ring" width="80" height="80">
<circle cx="40" cy="40" r="34" stroke-width="6" fill="none" class="stroke-gray-100 dark:stroke-gray-800"/>
<circle id="progress-circle" cx="40" cy="40" r="34" stroke-width="6" fill="none" class="stroke-primary" stroke-linecap="round" stroke-dasharray="213.63" stroke-dashoffset="213.63"/>
</svg>
<div class="absolute inset-0 flex items-center justify-center">
<span id="progress-percent" class="text-sm font-bold text-gray-900 dark:text-white">0%</span>
</div>
</div>
</div>
<div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
<div id="progress-bar" class="bg-primary h-2 rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
</div>
</div>
</section>

<!-- Stats cards -->
<section class="grid grid-cols-2 gap-3 mb-6">
<div class="bg-white dark:bg-card-dark p-4 rounded-ios-xl border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-start justify-between mb-3">
<div class="p-2 rounded-ios bg-blue-50 dark:bg-blue-500/10 text-primary">
<span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">today</span>
</div>
</div>
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Hoje</p>
<p id="stat-today" class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">-</p>
</div>

<div class="bg-white dark:bg-card-dark p-4 rounded-ios-xl border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-start justify-between mb-3">
<div class="p-2 rounded-ios bg-green-50 dark:bg-green-500/10 text-green-500">
<span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">date_range</span>
</div>
</div>
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Semana</p>
<p id="stat-week" class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">-</p>
</div>

<div class="bg-white dark:bg-card-dark p-4 rounded-ios-xl border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-start justify-between mb-3">
<div class="p-2 rounded-ios bg-purple-50 dark:bg-purple-500/10 text-purple-500">
<span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">calendar_month</span>
</div>
</div>
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Este Mes</p>
<p id="stat-month" class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">-</p>
</div>

<div class="bg-white dark:bg-card-dark p-4 rounded-ios-xl border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-start justify-between mb-3">
<div class="p-2 rounded-ios bg-orange-50 dark:bg-orange-500/10 text-orange-500">
<span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">emoji_events</span>
</div>
</div>
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Ranking</p>
<p id="stat-ranking" class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">-</p>
</div>
</section>

<!-- Comparacao com mes anterior + Streak -->
<section class="grid grid-cols-2 gap-3 mb-6">
<div id="comparison-card" class="bg-white dark:bg-card-dark p-4 rounded-ios-xl border border-gray-100 dark:border-white/5 shadow-sm">
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">vs. Mes Anterior</p>
<div class="flex items-center gap-2">
<span id="comparison-icon" class="material-symbols-outlined text-green-500 text-[20px]">trending_up</span>
<span id="comparison-value" class="text-lg font-bold text-gray-900 dark:text-white">-</span>
</div>
<p id="comparison-detail" class="text-xs text-gray-400 mt-1">Carregando...</p>
</div>

<div class="bg-white dark:bg-card-dark p-4 rounded-ios-xl border border-gray-100 dark:border-white/5 shadow-sm">
<p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">Sequencia</p>
<div class="flex items-center gap-2">
<span class="text-xl">&#128293;</span>
<span id="streak-value" class="text-lg font-bold text-gray-900 dark:text-white">-</span>
</div>
<p class="text-xs text-gray-400 mt-1">dias consecutivos</p>
</div>
</section>

<!-- Grafico diario -->
<section class="mb-6">
<div class="bg-white dark:bg-card-dark rounded-ios-xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-center justify-between mb-4">
<h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Cadastros no Mes</h3>
<span class="text-xs text-gray-400" id="chart-month-label">-</span>
</div>
<div id="daily-chart" class="flex items-end gap-[3px] h-32 overflow-x-auto">
<div class="flex items-center justify-center w-full h-full text-gray-400 text-sm">Carregando...</div>
</div>
</div>
</section>

<!-- Ranking da Equipe (admin only) -->
<section id="leaderboard-section" class="mb-6 hidden">
<div class="bg-white dark:bg-card-dark rounded-ios-xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
<div class="flex items-center justify-between mb-4">
<div>
<h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Ranking da Equipe</h3>
<p id="leaderboard-month" class="text-xs text-gray-400 mt-0.5"></p>
</div>
<div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5">
<button id="tab-cadastros" onclick="switchLeaderboardTab('cadastros')" class="px-3 py-1.5 text-xs font-semibold rounded-md bg-primary text-white">Cadastros</button>
<button id="tab-checklists" onclick="switchLeaderboardTab('checklists')" class="px-3 py-1.5 text-xs font-semibold rounded-md text-gray-500">Checklists</button>
</div>
</div>
<div id="leaderboard-list" class="space-y-2">
<p class="text-gray-400 text-center text-sm py-4">Carregando...</p>
</div>
</div>
</section>

</main>

<?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>

<!-- Scripts -->
<script src="js/api.js"></script>
<script src="js/components.js"></script>
<script src="js/app.js"></script>
</body></html>
