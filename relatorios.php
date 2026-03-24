<!DOCTYPE html>
<html class="light" lang="pt-BR"><head>
<title>Relatórios - Ondeline</title>
<?php include 'partials/head.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
<div class="flex items-center justify-between px-4 h-16">
<div class="flex items-center gap-3">
<a href="dashboard.php" class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
    <span class="material-symbols-outlined text-[22px]">arrow_back</span>
</a>
<div class="flex flex-col">
    <span class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white leading-none">Relatórios</span>
    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Exportar Dados</span>
</div>
</div>
<button id="btn-notification" class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 relative">
    <span class="material-symbols-outlined text-[22px]">notifications</span>
    <span id="notif-badge" class="hidden absolute -top-0.5 -right-0.5 size-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">0</span>
</button>
</div>
</header>

<main class="px-4 pb-32 pt-4">
<!-- Date Range -->
<div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm mb-4">
    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-lg">date_range</span>
        Período
    </h3>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-gray-500 block mb-1">De</label>
            <input type="date" id="date-from" class="w-full px-3 py-2.5 bg-gray-100 dark:bg-gray-800 rounded-xl text-sm border-0 focus:ring-2 focus:ring-primary/50"/>
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Até</label>
            <input type="date" id="date-to" class="w-full px-3 py-2.5 bg-gray-100 dark:bg-gray-800 rounded-xl text-sm border-0 focus:ring-2 focus:ring-primary/50"/>
        </div>
    </div>
</div>

<!-- Tabs de navegacao -->
<div class="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 mb-4 overflow-x-auto">
    <button onclick="switchReportTab('exportar')" id="rtab-exportar" class="flex-1 px-3 py-2 text-xs font-bold rounded-lg bg-primary text-white whitespace-nowrap">Exportar</button>
    <button onclick="switchReportTab('ranking')" id="rtab-ranking" class="flex-1 px-3 py-2 text-xs font-bold rounded-lg text-gray-500 whitespace-nowrap">Ranking</button>
    <button onclick="switchReportTab('tecnico')" id="rtab-tecnico" class="flex-1 px-3 py-2 text-xs font-bold rounded-lg text-gray-500 whitespace-nowrap">Por Tecnico</button>
</div>

<!-- TAB: Exportar Dados -->
<div id="tab-content-exportar" class="space-y-3">
    <!-- Clients Report -->
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">groups</span>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Clientes</h3>
                <p class="text-[13px] text-gray-500 dark:text-gray-400">Cadastros, planos, instaladores</p>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="generateReport('clients', 'csv')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">table_chart</span> CSV
            </button>
            <button onclick="generateReport('clients', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>

    <!-- Ficha Completa de Cadastro -->
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center shadow-lg shadow-rose-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">contact_page</span>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Ficha de Cadastro Completa</h3>
                <p class="text-[13px] text-gray-500 dark:text-gray-400">Nome, CPF, nasc., endereço, PPPoE, senha, plano</p>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="generateReport('cadastro_completo', 'csv')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">table_chart</span> CSV
            </button>
            <button onclick="generateReport('cadastro_completo', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>

    <!-- Checklists Report -->
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-cyan-400 to-teal-500 flex items-center justify-center shadow-lg shadow-teal-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">checklist</span>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Checklists / Suporte</h3>
                <p class="text-[13px] text-gray-500 dark:text-gray-400">Instalacoes, reparos, manutencoes</p>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="generateReport('checklists', 'csv')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">table_chart</span> CSV
            </button>
            <button onclick="generateReport('checklists', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>

    <!-- Time Clock Report -->
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">schedule</span>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Registro de Ponto</h3>
                <p class="text-[13px] text-gray-500 dark:text-gray-400">Entradas, saidas, horas trabalhadas</p>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="generateReport('timeclock', 'csv')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">table_chart</span> CSV
            </button>
            <button onclick="generateReport('timeclock', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>

    <!-- Inventory Report -->
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-purple-400 to-violet-500 flex items-center justify-center shadow-lg shadow-purple-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">inventory_2</span>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Estoque</h3>
                <p class="text-[13px] text-gray-500 dark:text-gray-400">Equipamentos, status, localizacao</p>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="generateReport('inventory', 'csv')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">table_chart</span> CSV
            </button>
            <button onclick="generateReport('inventory', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>

    <!-- Work Orders Report -->
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">assignment</span>
            </div>
            <div class="flex-1">
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Ordens de Servico</h3>
                <p class="text-[13px] text-gray-500 dark:text-gray-400">OS, status, tecnicos</p>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="generateReport('work_orders', 'csv')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">table_chart</span> CSV
            </button>
            <button onclick="generateReport('work_orders', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>
</div>

<!-- TAB: Ranking -->
<div id="tab-content-ranking" class="hidden">
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center shadow-lg shadow-yellow-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">emoji_events</span>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Ranking Consolidado</h3>
                <p class="text-[13px] text-gray-500">Cadastros + Checklists + OS por tecnico</p>
            </div>
        </div>
        <div id="ranking-container" class="space-y-2">
            <p class="text-gray-400 text-center text-sm py-6">Clique em "Gerar Ranking" para carregar</p>
        </div>
        <div class="flex gap-2 mt-4">
            <button onclick="loadRanking()" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-primary text-white rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">leaderboard</span> Gerar Ranking
            </button>
            <button onclick="generateReport('ranking', 'pdf')" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> PDF
            </button>
        </div>
    </div>
</div>

<!-- TAB: Por Tecnico -->
<div id="tab-content-tecnico" class="hidden">
    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="size-12 rounded-2xl bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <span class="material-symbols-outlined text-white text-[24px]">person_search</span>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900 dark:text-white">Relatorio por Tecnico</h3>
                <p class="text-[13px] text-gray-500">Todos os servicos realizados pelo tecnico</p>
            </div>
        </div>

        <!-- Seletor de tecnico -->
        <div class="mb-4">
            <label class="text-xs text-gray-500 block mb-1">Selecionar Tecnico</label>
            <select id="technician-select" class="w-full px-3 py-2.5 bg-gray-100 dark:bg-gray-800 rounded-xl text-sm border-0 focus:ring-2 focus:ring-primary/50 text-gray-900 dark:text-white">
                <option value="">-- Selecione um tecnico --</option>
            </select>
        </div>

        <!-- Resumo do tecnico -->
        <div id="tech-summary" class="hidden mb-4">
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-green-50 dark:bg-green-900/10 p-3 rounded-xl text-center">
                    <p id="tech-cadastros" class="text-xl font-bold text-green-600">0</p>
                    <p class="text-[10px] text-gray-500 font-medium">Cadastros</p>
                </div>
                <div class="bg-cyan-50 dark:bg-cyan-900/10 p-3 rounded-xl text-center">
                    <p id="tech-checklists" class="text-xl font-bold text-cyan-600">0</p>
                    <p class="text-[10px] text-gray-500 font-medium">Checklists</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/10 p-3 rounded-xl text-center">
                    <p id="tech-ordens" class="text-xl font-bold text-blue-600">0</p>
                    <p class="text-[10px] text-gray-500 font-medium">Ordens</p>
                </div>
            </div>
        </div>

        <!-- Lista de servicos -->
        <div id="tech-services-list" class="space-y-2 max-h-[400px] overflow-y-auto"></div>

        <!-- Botoes -->
        <div class="flex gap-2 mt-4">
            <button onclick="loadTechnicianReport()" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-primary text-white rounded-xl text-xs font-bold active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-base">search</span> Buscar
            </button>
            <button onclick="exportTechnicianPDF()" id="btn-tech-pdf" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-xl text-xs font-bold active:scale-95 transition-transform disabled:opacity-40" disabled>
                <span class="material-symbols-outlined text-base">picture_as_pdf</span> Imprimir PDF
            </button>
        </div>
    </div>
</div>
</main>

<?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>

<script src="js/api.js"></script>
<script src="js/utils.js"></script>
<script src="js/components.js"></script>
<script src="js/animations.js"></script>
<script src="js/ui-enhancements.js"></script>
<script src="js/feedback.js"></script>
<script src="js/app.js"></script>
<script src="js/pages/relatorios.js"></script>
</body></html>
