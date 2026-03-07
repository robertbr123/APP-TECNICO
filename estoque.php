<!DOCTYPE html>
<html class="light" lang="pt-BR"><head>
<title>Gestão de Estoque - Ondeline</title>
<?php include 'partials/head.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
@media print {
    header, nav, #modal-add, #modal-print-label, .tab-btn, #btn-add,
    .no-print, #equipment-list .flex.gap-2, [class*="bottom-"],
    #filter-type, #filter-status, #search-input,
    .tab-content:not(#tab-list) { display: none !important; }
    body { background: white !important; }
    .label-grid { display: grid !important; grid-template-columns: repeat(4, 1fr); gap: 8px; padding: 10mm; }
    .label-item { border: 1px solid #ddd; border-radius: 8px; padding: 8px; text-align: center; break-inside: avoid; page-break-inside: avoid; }
    .label-item .qr-canvas canvas { width: 80px !important; height: 80px !important; }
    .label-item p { font-size: 8px; margin: 2px 0; font-family: monospace; }
}
</style>
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
<section class="py-6">
<h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gestão de Estoque</h1>
<p class="text-[15px] text-gray-500 dark:text-gray-400 mt-1.5">Controle de equipamentos e materiais</p>
</section>

<!-- Tabs -->
<div class="bg-white dark:bg-card-dark px-4 py-3 rounded-ios-xl shadow-sm border border-gray-100 dark:border-white/5 mb-6 overflow-x-auto">
<div class="flex gap-2 min-w-max">
<button class="tab-btn active px-4 py-2 rounded-lg text-sm font-medium bg-primary text-white" data-tab="list">Lista</button>
<button class="tab-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400" data-tab="stats">Estatísticas</button>
<button class="tab-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400" data-tab="movements">Movimentações</button>
<button class="tab-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400" data-tab="alerts">Alertas</button>
<button class="tab-btn px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400" data-tab="labels">Etiquetas</button>
</div>
</div>

<!-- Tab: Lista -->
<div id="tab-list" class="tab-content">
<!-- Filtros -->
<div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-white/5 mb-4">
<div class="grid grid-cols-2 gap-3 mb-3">
<select id="filter-type" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-[#000000] dark:text-white">
<option value="">Todos os tipos</option>
<option value="router">Roteador</option>
<option value="modem">Modem</option>
<option value="onu">ONU</option>
<option value="cpe">CPE</option>
<option value="antenna">Antena</option>
<option value="other">Outro</option>
</select>
<select id="filter-status" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-[#000000] dark:text-white">
<option value="">Todos os status</option>
<option value="available">Disponível</option>
<option value="in_use">Em uso</option>
<option value="maintenance">Manutenção</option>
<option value="defective">Defeituoso</option>
<option value="lost">Perdido</option>
</select>
</div>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
<input id="search-input" class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-[#000000] dark:text-white" placeholder="Buscar por serial, modelo ou marca">
</div>
</div>

<!-- Botão Adicionar -->
<button id="btn-add" class="w-full h-[50px] bg-primary hover:bg-blue-600 active:scale-[0.98] transition-all text-white rounded-ios-xl flex items-center justify-center gap-2 font-semibold text-base shadow-lg shadow-primary/25 mb-4">
<span class="material-symbols-outlined text-[20px]">add</span>
                Adicionar Equipamento
            </button>

<!-- Lista de equipamentos -->
<div id="equipment-list" class="space-y-3">
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
</div>
</div>

<!-- Tab: Estatísticas -->
<div id="tab-stats" class="tab-content hidden">
<div class="bg-gradient-to-r from-primary to-blue-600 rounded-ios-xl p-6 text-white mb-4 shadow-lg shadow-primary/25">
<p class="text-sm opacity-80 mb-1">Valor Total do Estoque</p>
<p class="text-3xl font-bold" id="total-value">R$ 0,00</p>
</div>

<div class="grid grid-cols-2 gap-4 mb-4">
<div class="bg-green-50 dark:bg-green-500/10 rounded-ios-xl p-4">
<p class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Disponíveis</p>
<p class="text-2xl font-bold text-green-600 dark:text-green-400" id="stat-available">0</p>
</div>
<div class="bg-blue-50 dark:bg-blue-500/10 rounded-ios-xl p-4">
<p class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Em Uso</p>
<p class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="stat-in-use">0</p>
</div>
</div>

<div id="stats-by-type" class="space-y-3">
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
</div>
</div>

<!-- Tab: Movimentações -->
<div id="tab-movements" class="tab-content hidden">
<div id="movements-list" class="space-y-3">
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
    <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
    <div class="flex-1 space-y-2">
        <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
    </div>
</div>
</div>
</div>

<!-- Tab: Alertas -->
<div id="tab-alerts" class="tab-content hidden">
<div id="alerts-list" class="space-y-3">
<p class="text-gray-500 dark:text-gray-400 text-center py-8">Nenhum alerta encontrado</p>
</div>
</div>

<!-- Tab: Etiquetas QR Code -->
<div id="tab-labels" class="tab-content hidden">
<div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-white/5 mb-4">
    <div class="flex items-center gap-3 mb-3">
        <span class="material-symbols-outlined text-primary text-2xl">qr_code_2</span>
        <div>
            <h3 class="font-bold text-[#111318] dark:text-white">Imprimir Etiquetas</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Selecione os equipamentos e imprima as etiquetas QR</p>
        </div>
    </div>
    <div class="relative mb-3">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        <input id="label-search" class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-[#000000] dark:text-white" placeholder="Buscar equipamento para etiqueta">
    </div>
    <div class="flex gap-2 mb-3">
        <button onclick="selectAllLabels()" class="flex-1 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium">
            <span class="material-symbols-outlined text-sm align-middle">select_all</span> Selecionar Todos
        </button>
        <button onclick="clearLabelSelection()" class="flex-1 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium">
            <span class="material-symbols-outlined text-sm align-middle">deselect</span> Limpar
        </button>
        <button id="btn-print-selected" onclick="printSelectedLabels()" class="flex-1 py-2 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-40" disabled>
            <span class="material-symbols-outlined text-sm align-middle">print</span> Imprimir
        </button>
    </div>
</div>
<div id="label-equipment-list" class="space-y-2">
    <p class="text-gray-500 dark:text-gray-400 text-center py-8">
        <span class="material-symbols-outlined animate-spin block text-3xl mb-2">sync</span>
        Carregando equipamentos...
    </p>
</div>
</div>
</main>

<!-- Modal Adicionar Equipamento -->
<div id="modal-add" class="fixed inset-0 bg-black/50 flex items-end justify-center z-50 hidden">
<div class="bg-white dark:bg-card-dark w-full max-w-[600px] rounded-t-3xl p-6 max-h-[90vh] overflow-y-auto">
<div class="flex items-center justify-between mb-6">
<h3 class="text-xl font-bold text-[#111318] dark:text-white">Adicionar Equipamento</h3>
<button id="btn-close-modal" class="size-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
<span class="material-symbols-outlined text-gray-500">close</span>
</button>
</div>

<form id="form-add" class="space-y-4">
<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Número de Série *</label>
<div class="relative">
<input type="text" id="input-serial" class="w-full px-4 py-3 pr-14 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white" placeholder="SN123456789" required>
<button type="button" id="btn-scan-serial" title="Escanear QR / Código de Barras"
    class="absolute right-2 top-1/2 -translate-y-1/2 size-9 rounded-lg bg-gradient-to-br from-purple-500 to-violet-600 text-white flex items-center justify-center hover:from-purple-600 hover:to-violet-700 active:scale-95 transition-all shadow-md">
    <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1">qr_code_scanner</span>
</button>
</div>
</div>

<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Modelo *</label>
<input type="text" id="input-model" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white" placeholder="Ex: Archer C6" required>
</div>

<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Marca</label>
<input type="text" id="input-brand" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white" placeholder="Ex: TP-Link">
</div>

<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Tipo</label>
<select id="input-type" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white">
<option value="router">Roteador</option>
<option value="modem">Modem</option>
<option value="onu">ONU</option>
<option value="cpe">CPE</option>
<option value="antenna">Antena</option>
<option value="other">Outro</option>
</select>
</div>

<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Localização</label>
<input type="text" id="input-location" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white" placeholder="Ex: Estoque Principal">
</div>

<div class="grid grid-cols-2 gap-4">
<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Data de Compra</label>
<input type="date" id="input-purchase-date" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white">
</div>
<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Preço (R$)</label>
<input type="number" id="input-price" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#000000] dark:text-white" placeholder="0.00" step="0.01">
</div>
</div>

<div>
<label class="block text-sm font-semibold text-[#111318] dark:text-gray-300 mb-2">Observações</label>
<textarea id="input-notes" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 min-h-[100px] text-[#000000] dark:text-white" placeholder="Observações sobre o equipamento"></textarea>
</div>

<button type="submit" class="w-full py-4 rounded-ios-xl bg-primary hover:bg-primary/90 text-white font-bold flex items-center justify-center gap-2 shadow-lg shadow-primary/25">
<span class="material-symbols-outlined">add</span>
Adicionar Equipamento
</button>
</form>
</div>
</div>

<!-- Modal Impressão de Etiquetas -->
<div id="modal-print-label" class="fixed inset-0 bg-black/60 flex items-end justify-center z-50 hidden">
<div class="bg-white dark:bg-card-dark w-full max-w-[600px] rounded-t-3xl p-6 max-h-[90vh] overflow-y-auto">
<div class="flex items-center justify-between mb-4">
    <div>
        <h3 class="text-xl font-bold text-[#111318] dark:text-white">Prévia das Etiquetas</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400" id="label-count-text">0 etiquetas selecionadas</p>
    </div>
    <div class="flex gap-2">
        <button onclick="doPrint()" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold flex items-center gap-1.5 shadow-md shadow-primary/30">
            <span class="material-symbols-outlined text-lg">print</span>Imprimir
        </button>
        <button id="btn-close-print-modal" class="size-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <span class="material-symbols-outlined text-gray-500">close</span>
        </button>
    </div>
</div>
<div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3 mb-4">
    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">Prévia da impressão — layout real pode variar</p>
</div>
<!-- Grid de etiquetas para prévia -->
<div id="labels-preview-grid" class="grid grid-cols-2 gap-3 label-grid">
    <!-- Etiquetas geradas via JS -->
</div>
</div>
</div>

<?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>

<!-- Scripts do App -->
<script src="js/api.js"></script>
<script src="js/utils.js"></script>
<script src="js/components.js"></script>
<script src="js/app.js"></script>
<script src="js/feedback.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Tab navigation
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            
            // Remove active from all
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active', 'bg-primary', 'text-white');
                b.classList.add('bg-gray-100', 'text-gray-600');
            });
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            
            // Add active to clicked
            btn.classList.add('active', 'bg-primary', 'text-white');
            btn.classList.remove('bg-gray-100', 'text-gray-600');
            document.getElementById(`tab-${tab}`).classList.remove('hidden');
            
            // Load content
            if (tab === 'stats') loadStatistics();
            if (tab === 'movements') loadMovements();
            if (tab === 'alerts') loadAlerts();
        });
    });

    // Modal
    document.getElementById('btn-add').addEventListener('click', () => {
        document.getElementById('modal-add').classList.remove('hidden');
    });
    
    document.getElementById('btn-close-modal').addEventListener('click', () => {
        document.getElementById('modal-add').classList.add('hidden');
    });

    // Form
    document.getElementById('form-add').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const data = {
            action: 'add',
            serial_number: document.getElementById('input-serial').value,
            model: document.getElementById('input-model').value,
            brand: document.getElementById('input-brand').value,
            type: document.getElementById('input-type').value,
            location: document.getElementById('input-location').value,
            purchase_date: document.getElementById('input-purchase-date').value,
            purchase_price: document.getElementById('input-price').value,
            notes: document.getElementById('input-notes').value
        };

        this.querySelector('button[type="submit"]').disabled = true;
        this.querySelector('button[type="submit"]').innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span> Adicionando...';

        try {
            const response = await API.inventory(data);
            if (response.success) {
                App.showToast('Equipamento adicionado com sucesso!', 'success');
                document.getElementById('modal-add').classList.add('hidden');
                document.getElementById('form-add').reset();
                loadEquipmentList();
            } else {
                App.showToast('Erro: ' + response.message, 'error');
            }
        } catch (error) {
            App.showToast('Erro ao adicionar: ' + error.message, 'error');
        } finally {
            this.querySelector('button[type="submit"]').disabled = false;
            this.querySelector('button[type="submit"]').innerHTML = '<span class="material-symbols-outlined">add</span> Adicionar Equipamento';
        }
    });

    // Filters
    const filterType = document.getElementById('filter-type');
    const filterStatus = document.getElementById('filter-status');
    const searchInput = document.getElementById('search-input');

    const debouncedLoad = Utils.debounce(loadEquipmentList, 500);
    filterType.addEventListener('change', debouncedLoad);
    filterStatus.addEventListener('change', debouncedLoad);
    searchInput.addEventListener('input', debouncedLoad);

    // Load initial data
    loadEquipmentList();
});

async function loadEquipmentList() {
    const container = document.getElementById('equipment-list');
    const type = document.getElementById('filter-type').value;
    const status = document.getElementById('filter-status').value;
    const search = document.getElementById('search-input').value;

    container.innerHTML = `
        <div class="animate-pulse space-y-3">
            <div class="bg-gray-100 dark:bg-gray-700 rounded-ios-xl p-4 h-20"></div>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-ios-xl p-4 h-20"></div>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-ios-xl p-4 h-20"></div>
        </div>`;

    try {
        const response = await fetch(`/api/inventory.php?action=list&status=${status}&type=${type}&search=${search}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(equip => {
                const statusColors = {
                    available: 'bg-green-100 text-green-600',
                    in_use: 'bg-blue-100 text-blue-600',
                    maintenance: 'bg-yellow-100 text-yellow-600',
                    defective: 'bg-red-100 text-red-600',
                    lost: 'bg-gray-100 text-gray-600'
                };
                const statusLabels = {
                    available: 'Disponível',
                    in_use: 'Em uso',
                    maintenance: 'Manutenção',
                    defective: 'Defeituoso',
                    lost: 'Perdido'
                };
                const statusClass = statusColors[equip.status] || 'bg-gray-100 text-gray-600';
                const statusLabel = statusLabels[equip.status] || equip.status;

                return `
                    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-white/5">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-[#111318] dark:text-white">${equip.serial_number}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">${equip.brand} ${equip.model}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">${statusLabel}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                            <p><span class="material-symbols-outlined text-sm align-middle">category</span> ${equip.type}</p>
                            <p><span class="material-symbols-outlined text-sm align-middle">location_on</span> ${equip.location}</p>
                            ${equip.client_name ? `<p><span class="material-symbols-outlined text-sm align-middle">person</span> ${equip.client_name}</p>` : ''}
                        </div>
                        <div class="flex gap-2 mt-3">
                            ${equip.status === 'available' ? `
                                <button onclick="checkoutEquipment(${equip.id}, '${equip.serial_number}')" class="flex-1 py-2 rounded-lg bg-green-500 hover:bg-green-600 text-white text-sm font-medium active:scale-[0.98]">
                                    <span class="material-symbols-outlined">output</span>
                                    Entregar
                                </button>
                            ` : ''}
                            ${equip.status === 'in_use' ? `
                                <button onclick="returnEquipment(${equip.id}, '${equip.serial_number}')" class="flex-1 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium active:scale-[0.98]">
                                    <span class="material-symbols-outlined">input</span>
                                    Retornar
                                </button>
                            ` : ''}
                            <button onclick="editEquipment(${equip.id})" class="flex-1 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium active:scale-[0.98]">
                                <span class="material-symbols-outlined">edit</span>
                                Editar
                            </button>
                            <button onclick="printSingleLabel(${JSON.stringify(equip).replace(/"/g, '&quot;')})" title="Imprimir Etiqueta QR" class="py-2 px-3 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-sm font-medium active:scale-[0.98]">
                                <span class="material-symbols-outlined">qr_code</span>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-12">
                    <span class="material-symbols-outlined text-5xl text-gray-300 dark:text-gray-600">inventory_2</span>
                    <p class="font-semibold text-gray-500 dark:text-gray-400 mt-3">Nenhum equipamento encontrado</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Ajuste os filtros ou adicione um novo equipamento</p>
                </div>`;
        }
    } catch (error) {
        container.innerHTML = `
            <div class="text-center py-12">
                <span class="material-symbols-outlined text-5xl text-red-300 dark:text-red-700">error_outline</span>
                <p class="font-semibold text-red-500 mt-3">Erro ao carregar</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">${error.message}</p>
                <button onclick="loadEquipmentList()" class="mt-4 px-4 py-2 bg-primary text-white text-sm rounded-lg">
                    Tentar novamente
                </button>
            </div>`;
    }
}

async function loadStatistics() {
    const container = document.getElementById('stats-by-type');
    if (!container) return; // Se não estiver na página, não faz nada
    
    try {
        const response = await fetch('/api/inventory.php?action=statistics', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        const result = await response.json();

        if (result.success) {
            // Atualiza elementos se existirem
            const totalValueEl = document.getElementById('total-value');
            const statAvailableEl = document.getElementById('stat-available');
            const statInUseEl = document.getElementById('stat-in-use');
            
            if (totalValueEl) totalValueEl.textContent = Utils.formatCurrency(result.data.summary.total_value);
            if (statAvailableEl) statAvailableEl.textContent = result.data.summary.total_available;
            if (statInUseEl) statInUseEl.textContent = result.data.summary.total_in_use;

            container.innerHTML = result.data.by_status_type.map(stat => `
                <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-white/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-[#111318] dark:text-white capitalize">${stat.type}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">${stat.status}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-primary">${stat.count}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">${Utils.formatCurrency(stat.total_value)}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-red-500 text-center py-8">' + (result.message || 'Erro ao carregar estatísticas') + '</p>';
            
            // Se as tabelas não existirem, mostra botão para setup
            if (result.message && result.message.includes('Tabelas de estoque não configuradas')) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-gray-500 mb-4">Tabelas de estoque não configuradas</p>
                        <button onclick="setupInventory()" class="px-4 py-2 bg-primary text-white rounded-lg">
                            Configurar Estoque
                        </button>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        container.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar: ' + error.message + '</p>';
    }
}

// Função para configurar estoque
async function setupInventory() {
    try {
        const response = await fetch('/api/setup-inventory.php');
        const result = await response.json();
        
        if (result.success) {
            App.showToast('Estoque configurado com sucesso!', 'success');
            loadEquipmentList();
            loadStatistics();
        } else {
            App.showToast('Erro: ' + result.message, 'error');
        }
    } catch (error) {
        App.showToast('Erro ao configurar: ' + error.message, 'error');
    }
}

async function loadMovements() {
    const container = document.getElementById('movements-list');
    container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8"><span class="material-symbols-outlined animate-spin">sync</span> Carregando...</p>';

    try {
        const response = await fetch('/api/inventory.php?action=movements&limit=20', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(mov => {
                const typeLabels = {
                    in: 'Entrada',
                    out: 'Saída',
                    transfer: 'Transferência',
                    maintenance: 'Manutenção',
                    defective: 'Defeito',
                    lost: 'Perdido'
                };
                const typeColors = {
                    in: 'text-green-600',
                    out: 'text-blue-600',
                    transfer: 'text-purple-600',
                    maintenance: 'text-yellow-600',
                    defective: 'text-red-600',
                    lost: 'text-gray-600'
                };

                return `
                    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-white/5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-[#111318] dark:text-white">${mov.serial_number}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">${mov.brand} ${mov.model}</p>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    <p><span class="material-symbols-outlined text-sm align-middle">person</span> ${mov.username}</p>
                                    <p><span class="material-symbols-outlined text-sm align-middle">schedule</span> ${new Date(mov.created_at).toLocaleString('pt-BR')}</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium ${typeColors[mov.movement_type]}">${typeLabels[mov.movement_type]}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">Nenhuma movimentação encontrada</p>';
        }
    } catch (error) {
        console.error('Erro ao carregar movimentações:', error);
    }
}

async function loadAlerts() {
    const container = document.getElementById('alerts-list');
    container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8"><span class="material-symbols-outlined animate-spin">sync</span> Carregando...</p>';

    try {
        const response = await fetch('/api/inventory.php?action=alerts', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(alert => {
                const severityColors = {
                    info: 'bg-blue-100 text-blue-600',
                    warning: 'bg-yellow-100 text-yellow-600',
                    critical: 'bg-red-100 text-red-600'
                };
                const severityLabels = {
                    info: 'Informação',
                    warning: 'Atenção',
                    critical: 'Crítico'
                };
                const severityClass = severityColors[alert.severity] || 'bg-gray-100 text-gray-600';
                const severityLabel = severityLabels[alert.severity] || alert.severity;

                return `
                    <div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-white/5">
                        <div class="flex items-start gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium ${severityClass}">${severityLabel}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">${new Date(alert.created_at).toLocaleString('pt-BR')}</span>
                                </div>
                                <p class="text-sm text-[#111318] dark:text-white">${alert.message}</p>
                            </div>
                            <button onclick="resolveAlert(${alert.id})" class="px-3 py-1 rounded-lg bg-green-500 hover:bg-green-600 text-white text-sm font-medium active:scale-[0.98]">
                                Resolver
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">Nenhum alerta encontrado</p>';
        }
    } catch (error) {
        console.error('Erro ao carregar alertas:', error);
    }
}

async function checkoutEquipment(id, serial) {
    const cpf = prompt(`Informe o CPF do cliente para entregar o equipamento ${serial}:`);
    if (!cpf) return;

    const notes = prompt('Observações (opcional):', '');

    try {
        App.showLoading(true);
        const response = await fetch('/api/inventory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: JSON.stringify({
                action: 'checkout',
                equipment_id: id,
                client_cpf: cpf,
                notes: notes
            })
        });

        const result = await response.json();
        if (result.success) {
            App.showToast('Equipamento entregue ao cliente!', 'success');
            loadEquipmentList();
        } else {
            App.showToast('Erro: ' + result.message, 'error');
        }
    } catch (error) {
        App.showToast('Erro ao entregar: ' + error.message, 'error');
    } finally {
        App.showLoading(false);
    }
}

async function returnEquipment(id, serial) {
    const confirmReturn = await AppComponents.showConfirmModal({
        title: 'Retornar Equipamento',
        message: 'Deseja retornar o equipamento ' + serial + ' ao estoque?',
        confirmText: 'Retornar',
        icon: 'inventory',
        type: 'warning'
    });
    if (!confirmReturn) return;

    const status = prompt('Status do equipamento:\n1. available (Disponível)\n2. defective (Defeituoso)\n\nDigite 1 ou 2:', '1');
    if (!status) return;

    const statusMap = { '1': 'available', '2': 'defective' };
    const finalStatus = statusMap[status] || 'available';
    const location = prompt('Localização:', 'Estoque Principal');
    const notes = prompt('Observações (opcional):', '');

    try {
        App.showLoading(true);
        const response = await fetch('/api/inventory.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: JSON.stringify({
                action: 'return',
                id: id,
                status: finalStatus,
                location: location,
                notes: notes
            })
        });

        const result = await response.json();
        if (result.success) {
            App.showToast('Equipamento retornado ao estoque!', 'success');
            loadEquipmentList();
        } else {
            App.showToast('Erro: ' + result.message, 'error');
        }
    } catch (error) {
        App.showToast('Erro ao retornar: ' + error.message, 'error');
    } finally {
        App.showLoading(false);
    }
}

function editEquipment(id) {
    App.showToast('Funcionalidade de edição em desenvolvimento', 'info');
}

async function resolveAlert(alertId) {
    try {
        const response = await fetch('/api/inventory.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: JSON.stringify({
                action: 'resolve_alert',
                alert_id: alertId
            })
        });

        const result = await response.json();
        if (result.success) {
            App.showToast('Alerta resolvido!', 'success');
            loadAlerts();
        } else {
            App.showToast('Erro: ' + result.message, 'error');
        }
    } catch (error) {
        App.showToast('Erro ao resolver: ' + error.message, 'error');
    }
}

// Add inventory method to API
API.inventory = async function(data) {
    const response = await fetch('/api/inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
        },
        body: JSON.stringify(data)
    });
    return await response.json();
};

// Função de estatísticas para o app.js
async function loadInventoryStats() {
    console.log('loadInventoryStats chamado');
    // Carrega dados básicos
    try {
        await loadEquipmentList();
        // Carrega estatísticas se a aba stats existir
        if (document.getElementById('tab-stats')) {
            await loadStatistics();
        }
    } catch (error) {
        console.error('Erro em loadInventoryStats:', error);
    }
}

// Expõe funções globalmente para o app.js
window.loadEquipmentList = loadEquipmentList;
window.loadInventoryStats = loadInventoryStats;

// ══════════════════════════════════════════════
// QR CODE — SCANNER NO FORMULÁRIO
// ══════════════════════════════════════════════

document.getElementById('btn-scan-serial').addEventListener('click', function() {
    if (typeof BarcodeScanner === 'undefined') {
        // Carrega o scanner dinamicamente se ainda não carregou
        const script = document.createElement('script');
        script.src = 'js/scanner.js';
        script.onload = openSerialScanner;
        document.head.appendChild(script);
    } else {
        openSerialScanner();
    }
});

function openSerialScanner() {
    BarcodeScanner.open(function(result) {
        const input = document.getElementById('input-serial');
        // Suporta formato EQUIP|id|serial|model|brand ou texto direto
        if (result.startsWith('EQUIP|')) {
            const parts = result.split('|');
            if (parts[2]) {
                input.value = parts[2];
                // Auto-preenche modelo e marca se disponíveis
                if (parts[3] && document.getElementById('input-model').value === '') {
                    document.getElementById('input-model').value = parts[3];
                }
                if (parts[4] && document.getElementById('input-brand').value === '') {
                    document.getElementById('input-brand').value = parts[4];
                }
            }
        } else {
            input.value = result;
        }
        input.dispatchEvent(new Event('input'));
        if (typeof UIEnhancements !== 'undefined') UIEnhancements.hapticLight?.();
    }, { title: 'Escanear Serial do Equipamento' });
}

// ══════════════════════════════════════════════
// QR CODE — GERAÇÃO E IMPRESSÃO DE ETIQUETAS
// ══════════════════════════════════════════════

let selectedEquipForLabels = new Set();
let allEquipForLabels = [];

// Carrega lista de equipamentos para a aba de etiquetas
async function loadLabelEquipmentList(searchQuery) {
    const container = document.getElementById('label-equipment-list');
    const search = searchQuery || document.getElementById('label-search')?.value || '';

    try {
        const response = await fetch(`/api/inventory.php?action=list&search=${encodeURIComponent(search)}&limit=200`, {
            headers: { 'Authorization': 'Bearer ' + localStorage.getItem('auth_token') }
        });
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            allEquipForLabels = result.data;
            renderLabelList(result.data);
        } else {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">Nenhum equipamento encontrado</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar: ' + error.message + '</p>';
    }
}

function renderLabelList(equipList) {
    const container = document.getElementById('label-equipment-list');
    container.innerHTML = equipList.map(equip => {
        const isSelected = selectedEquipForLabels.has(equip.id);
        return `
        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all
            ${isSelected ? 'bg-primary/5 border-primary/30 dark:bg-primary/10' : 'bg-white dark:bg-card-dark border-gray-100 dark:border-white/5'}">
            <input type="checkbox" class="label-checkbox size-5 rounded accent-primary cursor-pointer"
                data-id="${equip.id}" ${isSelected ? 'checked' : ''}
                onchange="toggleLabelSelection(${equip.id}, this.checked, this.closest('label'))">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-[#111318] dark:text-white truncate">${equip.serial_number}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">${equip.brand || '—'} ${equip.model} · ${equip.type}</p>
            </div>
            <div class="size-10 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0" id="qr-mini-${equip.id}">
                <span class="material-symbols-outlined text-gray-400 text-xl">qr_code</span>
            </div>
        </label>`;
    }).join('');
    updatePrintButton();
}

function toggleLabelSelection(id, checked, labelEl) {
    if (checked) {
        selectedEquipForLabels.add(id);
        labelEl.classList.add('bg-primary/5', 'border-primary/30', 'dark:bg-primary/10');
        labelEl.classList.remove('bg-white', 'dark:bg-card-dark', 'border-gray-100', 'dark:border-white/5');
    } else {
        selectedEquipForLabels.delete(id);
        labelEl.classList.remove('bg-primary/5', 'border-primary/30', 'dark:bg-primary/10');
        labelEl.classList.add('bg-white', 'dark:bg-card-dark', 'border-gray-100', 'dark:border-white/5');
    }
    updatePrintButton();
}

function selectAllLabels() {
    allEquipForLabels.forEach(e => selectedEquipForLabels.add(e.id));
    renderLabelList(allEquipForLabels);
}

function clearLabelSelection() {
    selectedEquipForLabels.clear();
    renderLabelList(allEquipForLabels);
}

function updatePrintButton() {
    const btn = document.getElementById('btn-print-selected');
    const count = selectedEquipForLabels.size;
    if (btn) {
        btn.disabled = count === 0;
        btn.innerHTML = `<span class="material-symbols-outlined text-sm align-middle">print</span> Imprimir (${count})`;
    }
}

function generateQrContent(equip) {
    return `EQUIP|${equip.id}|${equip.serial_number}|${equip.model}|${equip.brand || ''}`;
}

function createQrElement(equip, size) {
    const wrapper = document.createElement('div');
    wrapper.className = 'qr-canvas';
    new QRCode(wrapper, {
        text: generateQrContent(equip),
        width: size,
        height: size,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
    return wrapper;
}

function printSelectedLabels() {
    const selected = allEquipForLabels.filter(e => selectedEquipForLabels.has(e.id));
    if (selected.length === 0) return;
    openPrintModal(selected);
}

function printSingleLabel(equip) {
    openPrintModal([equip]);
}

function openPrintModal(equipList) {
    const modal = document.getElementById('modal-print-label');
    const grid = document.getElementById('labels-preview-grid');
    const countText = document.getElementById('label-count-text');

    countText.textContent = `${equipList.length} etiqueta${equipList.length !== 1 ? 's' : ''} selecionada${equipList.length !== 1 ? 's' : ''}`;
    grid.innerHTML = '';

    equipList.forEach(equip => {
        const typeLabels = { router: 'Roteador', modem: 'Modem', onu: 'ONU', cpe: 'CPE', antenna: 'Antena', other: 'Outro' };
        const card = document.createElement('div');
        card.className = 'label-item border border-gray-200 dark:border-gray-700 rounded-xl p-3 text-center bg-white dark:bg-gray-900 flex flex-col items-center gap-1.5';

        const qrContainer = createQrElement(equip, 96);
        card.appendChild(qrContainer);

        const info = document.createElement('div');
        info.innerHTML = `
            <p class="text-[10px] font-bold text-gray-900 dark:text-white truncate max-w-[110px] mx-auto" title="${equip.serial_number}">${equip.serial_number}</p>
            <p class="text-[9px] text-gray-500 dark:text-gray-400">${equip.brand || ''} ${equip.model}</p>
            <p class="text-[9px] text-gray-400 dark:text-gray-500">${typeLabels[equip.type] || equip.type}</p>
            <p class="text-[8px] text-gray-300 dark:text-gray-600">ID: ${equip.id}</p>
        `;
        card.appendChild(info);
        grid.appendChild(card);
    });

    modal.classList.remove('hidden');
}

function doPrint() {
    window.print();
}

document.getElementById('btn-close-print-modal').addEventListener('click', function() {
    document.getElementById('modal-print-label').classList.add('hidden');
});

// Busca na aba de etiquetas
document.addEventListener('DOMContentLoaded', function() {
    // Delay para garantir que o label-search existe
    setTimeout(() => {
        const labelSearch = document.getElementById('label-search');
        if (labelSearch) {
            labelSearch.addEventListener('input', Utils.debounce(function() {
                loadLabelEquipmentList(this.value);
            }, 400));
        }
    }, 100);
});

// Carrega etiquetas quando a aba é aberta
const originalTabHandler = document.querySelectorAll('.tab-btn');
originalTabHandler.forEach(btn => {
    btn.addEventListener('click', function() {
        if (this.dataset.tab === 'labels' && allEquipForLabels.length === 0) {
            loadLabelEquipmentList('');
        }
    });
});

// Expõe funções de etiqueta globalmente
window.printSingleLabel = printSingleLabel;
window.printSelectedLabels = printSelectedLabels;
window.selectAllLabels = selectAllLabels;
window.clearLabelSelection = clearLabelSelection;
window.toggleLabelSelection = toggleLabelSelection;
</script>
</body></html>