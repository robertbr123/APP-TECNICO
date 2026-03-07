<!DOCTYPE html>
<html class="light" lang="pt-BR"><head>
<title>Detalhes do Cliente - Ondeline</title>
<?php include 'partials/head.php'; ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex h-auto min-h-screen w-full flex-col bg-background-light dark:bg-background-dark overflow-x-hidden">
<div class="flex items-center bg-white dark:bg-background-dark px-4 py-3 sticky top-0 z-20 border-b border-gray-100 dark:border-gray-800">
<button onclick="window.history.back()" class="text-primary flex size-10 items-center justify-start">
<span class="material-symbols-outlined">arrow_back_ios</span>
</button>
<h1 class="text-[#111318] dark:text-white text-lg font-bold flex-1 text-center pr-10">Detalhes do Cliente</h1>
</div>
<div class="bg-white dark:bg-background-dark px-4 py-6 border-b border-gray-100 dark:border-gray-800">
<div class="flex items-center gap-4">
<div class="size-16 rounded-full bg-primary/10 flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-3xl">person</span>
</div>
<div class="flex-1">
<div class="flex items-center gap-2 mb-0.5">
<h2 id="client-name" class="text-[#111318] dark:text-white text-xl font-bold">Carregando...</h2>
<span id="client-status" class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">...</span>
</div>
<div class="flex items-center gap-3 flex-wrap">
<p id="client-cpf" class="text-[#616f89] dark:text-gray-400 text-sm">CPF: ...</p>
<span class="text-gray-300 dark:text-gray-600 text-sm">|</span>
<p id="client-birth" class="text-[#616f89] dark:text-gray-400 text-sm"></p>
<span id="client-phone-sep" class="text-gray-300 dark:text-gray-600 text-sm hidden">|</span>
<a id="client-phone" href="#" class="text-primary dark:text-blue-400 text-sm font-medium flex items-center gap-1 hidden">
<span class="material-symbols-outlined text-sm" style="font-size:14px">chat</span>
<span id="client-phone-text"></span>
</a>
</div>
<div id="connection-status" class="flex items-center gap-1.5 mt-1">
<div id="status-dot" class="w-2.5 h-2.5 rounded-full bg-gray-300 animate-pulse"></div>
<span id="status-text" class="text-xs text-gray-400">Verificando conexão...</span>
</div>
</div>
</div>
</div>
<div class="flex flex-col gap-4 p-4">
<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center gap-2 mb-4">
<span class="material-symbols-outlined text-primary text-xl">router</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Informações do Serviço</h3>
</div>
<div class="grid grid-cols-1 gap-4">
<div>
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">Plano</p>
<p id="client-plan" class="text-[#111318] dark:text-white font-semibold">Carregando...</p>
</div>
<div class="grid grid-cols-2 gap-4">
<div>
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">Usuário PPPoE</p>
<p id="client-pppoe" class="text-[#111318] dark:text-white font-mono text-sm">...</p>
</div>
<div>
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">Senha</p>
<div class="flex items-center gap-2">
<p id="client-password" class="text-[#111318] dark:text-white font-mono text-sm" data-password="">••••••••</p>
<button id="btn-toggle-password" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
<span class="material-symbols-outlined text-sm text-[#616f89]">visibility</span>
</button>
</div>
</div>
</div>
<div>
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">Vencimento</p>
<p id="client-due" class="text-[#111318] dark:text-white font-semibold">...</p>
</div>
</div>
</section>
<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">cloud_sync</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Contrato SGP</h3>
</div>
<button id="btn-sync-sgp" onclick="syncFromSGP()" class="text-xs bg-primary/10 text-primary font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1 hover:bg-primary/20 transition-colors">
<span class="material-symbols-outlined text-sm">sync</span>
Sincronizar
</button>
</div>
<div class="grid grid-cols-1 gap-3">
<div>
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">Nº Contrato</p>
<p id="client-contrato" class="text-[#111318] dark:text-white font-semibold">Não vinculado</p>
</div>
<div>
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">MAC / Serial (SGP)</p>
<p id="client-mac" class="text-[#111318] dark:text-white font-mono text-sm">—</p>
</div>
<div id="connection-detail" class="hidden">
<p class="text-[#616f89] dark:text-gray-400 text-xs font-medium uppercase mb-1">Status Conexão</p>
<div id="connection-msg" class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-gray-300"></div>
<span class="text-sm text-gray-400">—</span>
</div>
</div>
</div>
</section>

<!-- Gerenciamento WiFi via TR069 -->
<section id="wifi-section" class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">wifi</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Configuração WiFi</h3>
</div>
<div id="wifi-status-badge" class="flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
<div id="wifi-status-dot" class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></div>
<span id="wifi-status-text" class="text-[10px] font-medium text-gray-500">Verificando...</span>
</div>
</div>

<!-- Estado: Carregando -->
<div id="wifi-loading" class="flex flex-col items-center py-8">
<div class="w-10 h-10 border-3 border-primary border-t-transparent rounded-full animate-spin mb-3"></div>
<p class="text-sm text-gray-500">Buscando dispositivo no TR069...</p>
</div>

<!-- Estado: Sem PPPoE -->
<div id="wifi-no-pppoe" class="hidden flex flex-col items-center py-6 text-center">
<div class="w-14 h-14 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mb-3">
<span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-2xl">warning</span>
</div>
<p class="text-sm font-medium text-gray-700 dark:text-gray-300">PPPoE não configurado</p>
<p class="text-xs text-gray-500 mt-1">Configure o usuário PPPoE do cliente para gerenciar o WiFi</p>
</div>

<!-- Estado: Dispositivo não encontrado -->
<div id="wifi-not-found" class="hidden flex flex-col items-center py-6 text-center">
<div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3">
<span class="material-symbols-outlined text-gray-400 text-2xl">router</span>
</div>
<p class="text-sm font-medium text-gray-700 dark:text-gray-300">Dispositivo não encontrado</p>
<p class="text-xs text-gray-500 mt-1">O equipamento ainda não se conectou ao TR069</p>
<button onclick="loadWifiInfo()" class="mt-3 text-xs bg-primary/10 text-primary font-semibold px-4 py-2 rounded-lg flex items-center gap-1 hover:bg-primary/20 transition-colors">
<span class="material-symbols-outlined text-sm">refresh</span>
Tentar novamente
</button>
</div>

<!-- Estado: Erro de conexão -->
<div id="wifi-error" class="hidden flex flex-col items-center py-6 text-center">
<div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-3">
<span class="material-symbols-outlined text-red-500 text-2xl">error</span>
</div>
<p class="text-sm font-medium text-gray-700 dark:text-gray-300">Erro ao conectar com TR069</p>
<p id="wifi-error-msg" class="text-xs text-gray-500 mt-1">Verifique se o serviço está online</p>
<button onclick="loadWifiInfo()" class="mt-3 text-xs bg-primary/10 text-primary font-semibold px-4 py-2 rounded-lg flex items-center gap-1 hover:bg-primary/20 transition-colors">
<span class="material-symbols-outlined text-sm">refresh</span>
Tentar novamente
</button>
</div>

<!-- Estado: Sucesso - Mostra informações -->
<div id="wifi-info" class="hidden">
<!-- Informações do Dispositivo -->
<div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 mb-4">
<div class="flex items-center gap-2 mb-2">
<span class="material-symbols-outlined text-gray-500 text-lg">router</span>
<span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Equipamento</span>
</div>
<div class="grid grid-cols-2 gap-2">
<div>
<p class="text-[10px] text-gray-500 uppercase">Fabricante</p>
<p id="wifi-manufacturer" class="text-sm font-semibold text-gray-800 dark:text-white">—</p>
</div>
<div>
<p class="text-[10px] text-gray-500 uppercase">Modelo</p>
<p id="wifi-model" class="text-sm font-semibold text-gray-800 dark:text-white">—</p>
</div>
<div>
<p class="text-[10px] text-gray-500 uppercase">Serial</p>
<p id="wifi-serial" class="text-xs font-mono text-gray-600 dark:text-gray-400">—</p>
</div>
<div>
<p class="text-[10px] text-gray-500 uppercase">IP</p>
<p id="wifi-ip" class="text-xs font-mono text-gray-600 dark:text-gray-400">—</p>
</div>
</div>
</div>

<!-- Configurações WiFi -->
<div class="grid grid-cols-1 gap-3">
<div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
<span class="material-symbols-outlined text-blue-600 dark:text-blue-400">wifi</span>
</div>
<div>
<p class="text-[10px] text-blue-600 dark:text-blue-400 uppercase font-medium">Nome da Rede (SSID)</p>
<p id="wifi-ssid" class="text-base font-bold text-gray-900 dark:text-white">—</p>
</div>
</div>
<button onclick="openEditWifiModal('ssid')" class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800/40 transition-colors">
<span class="material-symbols-outlined text-lg">edit</span>
</button>
</div>

<div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400">key</span>
</div>
<div>
<p class="text-[10px] text-purple-600 dark:text-purple-400 uppercase font-medium">Senha WiFi</p>
<div class="flex items-center gap-2">
<p id="wifi-password-display" class="text-base font-bold text-gray-900 dark:text-white font-mono">••••••••</p>
<button onclick="toggleWifiPassword()" class="text-purple-500 hover:text-purple-700">
<span id="wifi-password-icon" class="material-symbols-outlined text-sm">visibility</span>
</button>
</div>
</div>
</div>
<button onclick="openEditWifiModal('password')" class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-800/40 transition-colors">
<span class="material-symbols-outlined text-lg">edit</span>
</button>
</div>

<div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
<span class="material-symbols-outlined text-green-600 dark:text-green-400">security</span>
</div>
<div>
<p class="text-[10px] text-green-600 dark:text-green-400 uppercase font-medium">Segurança</p>
<p id="wifi-security" class="text-sm font-semibold text-gray-900 dark:text-white">—</p>
</div>
</div>
</div>
</div>

<!-- Botão de Editar Tudo -->
<button onclick="openEditWifiModal('all')" class="w-full mt-4 h-12 bg-primary/10 text-primary rounded-xl font-semibold text-sm flex items-center justify-center gap-2 hover:bg-primary/20 transition-colors">
<span class="material-symbols-outlined">settings</span>
Configurar WiFi Completo
</button>

<!-- Última atualização -->
<p id="wifi-last-contact" class="text-[10px] text-gray-400 text-center mt-3">Último contato: —</p>
</div>
</section>

<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center gap-2 mb-4">
<span class="material-symbols-outlined text-primary text-xl">location_on</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Endereço</h3>
</div>
<p id="client-address" class="text-[#111318] dark:text-white text-sm mb-4 leading-relaxed">
                    Carregando endereço...
                </p>
<div id="mini-map" class="w-full h-40 rounded-xl bg-gray-200 dark:bg-gray-800 overflow-hidden flex items-center justify-center">
<div class="text-center text-gray-400">
<span class="material-symbols-outlined text-2xl">map</span>
<p class="text-xs mt-1">Carregando mapa...</p>
</div>
</div>
</section>
<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">photo_library</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Fotos da Instalação</h3>
</div>
<span id="photo-count" class="text-gray-400 text-xs">Carregando...</span>
</div>
<div id="photos-container" class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
<div class="min-w-[100px] h-24 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
<div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
</div>
</div>
</section>
<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center gap-2 mb-2">
<span class="material-symbols-outlined text-primary text-xl">sticky_note_2</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Observações</h3>
</div>
<p id="client-observation" class="text-[#616f89] dark:text-gray-400 text-sm italic">
                    Carregando...
                </p>
</section>

<!-- Histórico de OS do Cliente -->
<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">assignment</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Histórico de OS</h3>
</div>
<span id="os-count" class="text-gray-400 text-xs">Carregando...</span>
</div>
<div id="os-history-container" class="space-y-3">
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl animate-pulse">
<div class="size-10 rounded-full bg-gray-200 dark:bg-gray-700"></div>
<div class="flex-1 space-y-2">
<div class="h-3.5 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
<div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
</div>
</div>
</div>
</section>

<!-- Histórico de Checklists do Cliente -->
<section class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-green-500 text-xl">checklist</span>
<h3 class="text-[#111318] dark:text-white text-base font-bold">Histórico de Checklists</h3>
</div>
<span id="checklist-count" class="text-gray-400 text-xs">Carregando...</span>
</div>
<div id="checklist-history-container" class="space-y-3">
<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl animate-pulse">
<div class="size-10 rounded-full bg-gray-200 dark:bg-gray-700"></div>
<div class="flex-1 space-y-2">
<div class="h-3.5 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
<div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
</div>
</div>
</div>
</section>
</div>
<div class="p-4 flex flex-col gap-3 mb-10">
<!-- Botões de Ação (Agendar visível para todos) -->
<button onclick="openScheduleMaintenance()" class="w-full h-14 bg-primary text-white rounded-2xl font-bold text-base flex items-center justify-center gap-2 shadow-lg shadow-primary/20">
<span class="material-symbols-outlined">calendar_today</span>
                Agendar Manutenção
            </button>

<!-- Botões Admin Only (Editar e Excluir) -->
<div id="admin-actions" class="hidden flex flex-col gap-3">
<button onclick="editClient()" class="w-full h-14 bg-white dark:bg-gray-900 text-primary border-2 border-primary rounded-2xl font-bold text-base flex items-center justify-center gap-2">
<span class="material-symbols-outlined">edit</span>
                Editar Cliente
            </button>
<button onclick="confirmDeleteClient()" class="w-full h-14 bg-red-500 text-white rounded-2xl font-bold text-base flex items-center justify-center gap-2 shadow-lg shadow-red-500/20">
<span class="material-symbols-outlined">delete</span>
                Excluir Cliente
            </button>
</div>
</div>
</div>

<!-- Modal de Edição WiFi -->
<div id="wifi-edit-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-end sm:items-center justify-center">
<div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-t-3xl sm:rounded-2xl p-6 pb-10 sm:pb-6" onclick="event.stopPropagation()">
<div class="flex items-center justify-between mb-5">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">wifi</span>
<h3 id="wifi-modal-title" class="text-lg font-bold text-gray-900 dark:text-white">Configurar WiFi</h3>
</div>
<button onclick="closeWifiModal()" class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
<span class="material-symbols-outlined text-gray-500">close</span>
</button>
</div>

<form id="wifi-edit-form" onsubmit="submitWifiChanges(event)">
<!-- Campo SSID -->
<div id="wifi-field-ssid" class="mb-4">
<label class="text-xs font-semibold text-gray-500 uppercase mb-2 block">Nome da Rede (SSID)</label>
<input type="text" id="wifi-input-ssid" name="ssid" 
class="w-full h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-4 text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
placeholder="Digite o nome da rede WiFi"
maxlength="32">
<p class="text-[10px] text-gray-400 mt-1">Máximo 32 caracteres</p>
</div>

<!-- Campo Senha -->
<div id="wifi-field-password" class="mb-4">
<label class="text-xs font-semibold text-gray-500 uppercase mb-2 block">Senha WiFi</label>
<div class="relative">
<input type="password" id="wifi-input-password" name="password" 
class="w-full h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-4 pr-12 text-sm font-mono focus:ring-2 focus:ring-primary focus:border-transparent"
placeholder="Digite a nova senha"
minlength="8"
maxlength="63">
<button type="button" onclick="toggleModalPassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
<span id="modal-password-icon" class="material-symbols-outlined text-xl">visibility</span>
</button>
</div>
<p class="text-[10px] text-gray-400 mt-1">Mínimo 8 caracteres</p>
</div>

<!-- Campo Segurança (só aparece em edição completa) -->
<div id="wifi-field-security" class="mb-4 hidden">
<label class="text-xs font-semibold text-gray-500 uppercase mb-2 block">Modo de Segurança</label>
<select id="wifi-input-security" name="security_mode"
class="w-full h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-4 text-sm">
<option value="">Manter atual</option>
<option value="WPA2-PSK">WPA2-PSK (Recomendado)</option>
<option value="WPA-WPA2-PSK">WPA/WPA2-PSK (Compatibilidade)</option>
<option value="WPA-PSK">WPA-PSK (Legado)</option>
</select>
</div>

<!-- Aviso -->
<div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-3 mb-4">
<div class="flex gap-2">
<span class="material-symbols-outlined text-yellow-600 text-lg flex-shrink-0">info</span>
<div>
<p class="text-xs text-yellow-800 dark:text-yellow-300 font-medium">Importante</p>
<p class="text-[11px] text-yellow-700 dark:text-yellow-400 mt-0.5">As alterações serão aplicadas no próximo contato do equipamento com o servidor (geralmente em até 10 minutos).</p>
</div>
</div>
</div>

<!-- Botões -->
<div class="flex gap-3">
<button type="button" onclick="closeWifiModal()" 
class="flex-1 h-12 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold">
Cancelar
</button>
<button type="submit" id="wifi-submit-btn"
class="flex-1 h-12 bg-primary text-white rounded-xl font-semibold flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-lg">save</span>
Salvar
</button>
</div>
</form>
</div>
</div>

<!-- Scripts do App -->
<script src="js/api.js"></script>
<script src="js/app.js"></script>
<script>
    var clientPassword = ''; // Armazena a senha real
    var passwordVisible = false;
    var currentCpf = '';
    var currentContrato = '';
    var currentServidor = '';
    var currentClientData = null; // Armazena dados do cliente para edição
    
    // =====================================================
    // VARIÁVEIS WIFI / TR069
    // =====================================================
    var currentPppoe = '';
    var wifiData = null;
    var wifiPasswordVisible = false;
    var modalPasswordVisible = false;
    
    document.addEventListener('DOMContentLoaded', async function() {
        // Verifica se é admin e mostra botões de editar/excluir
        checkAdminAccess();
        
        // Pega o CPF da URL
        var params = new URLSearchParams(window.location.search);
        var cpf = params.get('cpf');
        currentCpf = cpf;
        
        console.log('Carregando detalhes do cliente:', cpf);
        
        if (!cpf) {
            alert('CPF não informado');
            window.history.back();
            return;
        }
        
        // Configura toggle de senha
        var btnToggle = document.getElementById('btn-toggle-password');
        if (btnToggle) {
            btnToggle.onclick = function() {
                togglePassword();
            };
        }
        
        // Carrega as fotos
        await loadPhotos(cpf);
        
        // Carrega dados do cliente
        await loadClientData(cpf);
        
        // Carrega histórico de OS do cliente
        await loadOsHistory(cpf);
        
        // Carrega histórico de Checklists do cliente
        await loadChecklistHistory(cpf);
    });
    
    function togglePassword() {
        var passwordEl = document.getElementById('client-password');
        var btnToggle = document.getElementById('btn-toggle-password');
        var icon = btnToggle.querySelector('.material-symbols-outlined');
        
        passwordVisible = !passwordVisible;
        
        if (passwordVisible) {
            passwordEl.textContent = clientPassword || 'Não informada';
            icon.textContent = 'visibility_off';
        } else {
            passwordEl.textContent = '••••••••';
            icon.textContent = 'visibility';
        }
    }
    
    async function loadPhotos(cpf) {
        var container = document.getElementById('photos-container');
        var countEl = document.getElementById('photo-count');
        
        try {
            var response = await fetch('/api/get-fotos.php?cpf=' + encodeURIComponent(cpf));
            var result = await response.json();
            
            console.log('Fotos:', result);
            
            if (result.success && result.data && result.data.length > 0) {
                countEl.textContent = result.data.length + ' foto(s)';
                
                container.innerHTML = result.data.map(function(photo) {
                    var typeLabel = {
                        'router': 'Roteador',
                        'cabling': 'Cabeamento',
                        'signal': 'Sinal',
                        'checklist': 'Checklist',
                        'installation': 'Instalação',
                        'other': 'Outro'
                    }[photo.type] || photo.type;
                    
                    return '<div class="min-w-[100px] flex-shrink-0 cursor-pointer" onclick="openPhoto(\'' + photo.url + '\')">' +
                        '<div class="h-24 rounded-lg bg-gray-100 dark:bg-gray-800 overflow-hidden">' +
                            '<img src="' + photo.url + '" alt="' + typeLabel + '" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML=\'<div class=\\\'flex items-center justify-center h-full text-gray-400\\\'><span class=\\\'material-symbols-outlined\\\'>broken_image</span></div>\'">' +
                        '</div>' +
                        '<p class="text-[10px] text-gray-500 mt-1 text-center truncate">' + typeLabel + '</p>' +
                    '</div>';
                }).join('');
            } else {
                countEl.textContent = '0 fotos';
                container.innerHTML = '<div class="flex flex-col items-center justify-center w-full py-6 cursor-pointer" onclick="openUploadPhoto()">' +
                    '<div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-2">' +
                        '<span class="material-symbols-outlined text-primary text-3xl">add_a_photo</span>' +
                    '</div>' +
                    '<p class="text-sm font-medium text-gray-500">Adicionar fotos</p>' +
                    '<p class="text-[11px] text-gray-400 mt-0.5">Toque para cadastrar</p>' +
                '</div>';
            }
        } catch (error) {
            console.error('Erro ao carregar fotos:', error);
            countEl.textContent = '0 fotos';
            container.innerHTML = '<div class="flex flex-col items-center justify-center w-full py-6 cursor-pointer" onclick="openUploadPhoto()">' +
                '<div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-2">' +
                    '<span class="material-symbols-outlined text-primary text-3xl">add_a_photo</span>' +
                '</div>' +
                '<p class="text-sm font-medium text-gray-500">Adicionar fotos</p>' +
                '<p class="text-[11px] text-gray-400 mt-0.5">Toque para cadastrar</p>' +
            '</div>';
        }
    }
    
    // Carrega histórico de OS do cliente
    async function loadOsHistory(cpf) {
        var container = document.getElementById('os-history-container');
        var countEl = document.getElementById('os-count');
        
        try {
            var token = localStorage.getItem('auth_token');
            var response = await fetch('/api/work-orders.php?client_cpf=' + encodeURIComponent(cpf), {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            var result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                countEl.textContent = result.data.length + ' OS';
                
                var statusColors = {
                    'open': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'assigned': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'in_progress': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                    'completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'cancelled': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                };
                
                var statusLabels = {
                    'open': 'Aberta',
                    'assigned': 'Atribuída',
                    'in_progress': 'Em Andamento',
                    'completed': 'Concluída',
                    'cancelled': 'Cancelada'
                };
                
                var typeLabels = {
                    'installation': 'Instalação',
                    'repair': 'Reparo',
                    'maintenance': 'Manutenção',
                    'migration': 'Migração',
                    'removal': 'Remoção',
                    'other': 'Outro'
                };
                
                var typeIcons = {
                    'installation': 'cable',
                    'repair': 'build',
                    'maintenance': 'engineering',
                    'migration': 'swap_horiz',
                    'removal': 'delete',
                    'other': 'help'
                };
                
                container.innerHTML = result.data.map(function(os) {
                    var statusClass = statusColors[os.status] || 'bg-gray-100 text-gray-600';
                    var statusLabel = statusLabels[os.status] || os.status;
                    var typeLabel = typeLabels[os.type] || os.type;
                    var typeIcon = typeIcons[os.type] || 'assignment';
                    var date = os.scheduled_date ? new Date(os.scheduled_date).toLocaleDateString('pt-BR') : new Date(os.created_at).toLocaleDateString('pt-BR');
                    
                    return '<div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" onclick="openOsDetail(' + os.id + ')">' +
                        '<div class="size-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">' +
                            '<span class="material-symbols-outlined text-primary text-lg">' + typeIcon + '</span>' +
                        '</div>' +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="flex items-center justify-between gap-2 mb-1">' +
                                '<p class="text-sm font-semibold text-gray-900 dark:text-white truncate">' + escapeHtml(os.order_number) + '</p>' +
                                '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ' + statusClass + '">' + statusLabel + '</span>' +
                            '</div>' +
                            '<p class="text-xs text-gray-600 dark:text-gray-400 truncate">' + typeLabel + ' - ' + escapeHtml(os.description || '').substring(0, 50) + '</p>' +
                            '<div class="flex items-center gap-2 mt-1">' +
                                '<span class="text-[10px] text-gray-400 flex items-center gap-0.5">' +
                                    '<span class="material-symbols-outlined text-xs">calendar_today</span>' +
                                    date +
                                '</span>' +
                                (os.assigned_name ? '<span class="text-[10px] text-gray-400 flex items-center gap-0.5"><span class="material-symbols-outlined text-xs">person</span>' + escapeHtml(os.assigned_name) + '</span>' : '') +
                            '</div>' +
                        '</div>' +
                        '<span class="material-symbols-outlined text-gray-400 text-lg">chevron_right</span>' +
                    '</div>';
                }).join('');
            } else {
                countEl.textContent = '0 OS';
                container.innerHTML = '<div class="flex flex-col items-center justify-center py-6 text-center">' +
                    '<div class="size-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-2">' +
                        '<span class="material-symbols-outlined text-gray-400 text-xl">assignment</span>' +
                    '</div>' +
                    '<p class="text-sm text-gray-500">Nenhuma OS encontrada</p>' +
                    '<p class="text-[11px] text-gray-400 mt-0.5">Este cliente não possui ordens de serviço</p>' +
                '</div>';
            }
        } catch (error) {
            console.error('Erro ao carregar histórico de OS:', error);
            countEl.textContent = 'Erro';
            container.innerHTML = '<div class="flex flex-col items-center justify-center py-6 text-center">' +
                '<span class="material-symbols-outlined text-red-400 text-2xl mb-2">error</span>' +
                '<p class="text-sm text-red-500">Erro ao carregar</p>' +
            '</div>';
        }
    }
    
    // Abre detalhes da OS
    function openOsDetail(osId) {
        window.location.href = 'ordens.php?os=' + osId;
    }
    
    // Carrega histórico de Checklists do cliente
    async function loadChecklistHistory(cpf) {
        var container = document.getElementById('checklist-history-container');
        var countEl = document.getElementById('checklist-count');
        
        try {
            var token = localStorage.getItem('auth_token');
            var response = await fetch('/api/checklist.php?client_cpf=' + encodeURIComponent(cpf), {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            var result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                countEl.textContent = result.data.length + ' checklist' + (result.data.length > 1 ? 's' : '');
                
                var statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'in_progress': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'cancelled': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                };
                
                var statusLabels = {
                    'pending': 'Pendente',
                    'in_progress': 'Em Andamento',
                    'completed': 'Concluído',
                    'cancelled': 'Cancelado'
                };
                
                var approvalColors = {
                    'pending': 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    'pending_approval': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                    'approved': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'rejected': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                };
                
                var approvalLabels = {
                    'pending': 'Aguardando',
                    'pending_approval': 'Aguarda Aprovação',
                    'approved': 'Aprovado',
                    'rejected': 'Rejeitado'
                };
                
                var typeLabels = {
                    'new': 'Nova Instalação',
                    'migration': 'Migração',
                    'repair': 'Reparo',
                    'maintenance': 'Manutenção'
                };
                
                var typeIcons = {
                    'new': 'cable',
                    'migration': 'swap_horiz',
                    'repair': 'build',
                    'maintenance': 'engineering'
                };
                
                container.innerHTML = result.data.map(function(chk) {
                    var statusClass = statusColors[chk.status] || 'bg-gray-100 text-gray-600';
                    var statusLabel = statusLabels[chk.status] || chk.status;
                    var approvalClass = approvalColors[chk.approval_status] || 'bg-gray-100 text-gray-600';
                    var approvalLabel = approvalLabels[chk.approval_status] || chk.approval_status;
                    var typeLabel = typeLabels[chk.installation_type] || chk.installation_type;
                    var typeIcon = typeIcons[chk.installation_type] || 'checklist';
                    var date = new Date(chk.created_at).toLocaleDateString('pt-BR');
                    var checklistNumber = chk.checklist_number || 'CHK-' + chk.id;
                    
                    return '<div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" onclick="openChecklistDetail(' + chk.id + ')">' +
                        '<div class="size-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">' +
                            '<span class="material-symbols-outlined text-green-600 dark:text-green-400 text-lg">' + typeIcon + '</span>' +
                        '</div>' +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="flex items-center justify-between gap-2 mb-1">' +
                                '<p class="text-sm font-semibold text-gray-900 dark:text-white truncate">' + escapeHtml(checklistNumber) + '</p>' +
                                '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ' + approvalClass + '">' + approvalLabel + '</span>' +
                            '</div>' +
                            '<p class="text-xs text-gray-600 dark:text-gray-400 truncate">' + typeLabel + '</p>' +
                            '<div class="flex items-center gap-2 mt-1">' +
                                '<span class="text-[10px] text-gray-400 flex items-center gap-0.5">' +
                                    '<span class="material-symbols-outlined text-xs">calendar_today</span>' +
                                    date +
                                '</span>' +
                                '<span class="text-[10px] font-bold px-1.5 py-0.5 rounded ' + statusClass + '">' + statusLabel + '</span>' +
                                (chk.technician_name ? '<span class="text-[10px] text-gray-400 flex items-center gap-0.5"><span class="material-symbols-outlined text-xs">person</span>' + escapeHtml(chk.technician_name) + '</span>' : '') +
                            '</div>' +
                        '</div>' +
                        '<span class="material-symbols-outlined text-gray-400 text-lg">chevron_right</span>' +
                    '</div>';
                }).join('');
            } else {
                countEl.textContent = '0';
                container.innerHTML = '<div class="flex flex-col items-center justify-center py-6 text-center">' +
                    '<div class="size-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-2">' +
                        '<span class="material-symbols-outlined text-gray-400 text-xl">checklist</span>' +
                    '</div>' +
                    '<p class="text-sm text-gray-500">Nenhum checklist encontrado</p>' +
                    '<p class="text-[11px] text-gray-400 mt-0.5">Este cliente não possui checklists registrados</p>' +
                '</div>';
            }
        } catch (error) {
            console.error('Erro ao carregar histórico de checklists:', error);
            countEl.textContent = 'Erro';
            container.innerHTML = '<div class="flex flex-col items-center justify-center py-6 text-center">' +
                '<span class="material-symbols-outlined text-red-400 text-2xl mb-2">error</span>' +
                '<p class="text-sm text-red-500">Erro ao carregar</p>' +
            '</div>';
        }
    }
    
    // Abre detalhes do Checklist
    function openChecklistDetail(checklistId) {
        window.location.href = 'checklist.php?id=' + checklistId;
    }

    async function loadClientData(cpf) {
        try {
            // Usa API.getClient para obter todos os campos (incluindo pppoe e password)
            var result = await API.getClient(cpf);

            console.log('Cliente:', result);

            // Compatibilidade: resultado pode ser objeto ou array
            var client = null;
            if (result.success && result.data) {
                client = Array.isArray(result.data) ? result.data[0] : result.data;
            }

            if (client) {
                
                // Registra a visualização no log de auditoria
                logClientView(client);
                
                // Nome
                var nameEl = document.getElementById('client-name');
                if (nameEl) nameEl.textContent = client.name || 'Sem nome';
                
                // Status
                var statusEl = document.getElementById('client-status');
                if (statusEl) {
                    if (client.status === 'ativo') {
                        statusEl.textContent = 'Ativo';
                        statusEl.className = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase';
                    } else {
                        statusEl.textContent = 'Inadimplente';
                        statusEl.className = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase';
                    }
                }
                
                // CPF
                var cpfEl = document.getElementById('client-cpf');
                if (cpfEl) cpfEl.textContent = 'CPF: ' + formatCPF(client.cpf);

                // Data de Nascimento
                var birthEl = document.getElementById('client-birth');
                if (birthEl) {
                    if (client.birthDate && client.birthDate !== '0000-00-00') {
                        var parts = client.birthDate.split('-');
                        birthEl.textContent = 'Nasc: ' + parts[2] + '/' + parts[1] + '/' + parts[0];
                    } else {
                        birthEl.textContent = 'Nasc: Não informado';
                    }
                }

                // Telefone com link WhatsApp
                var phoneRaw = client.phone || client.phone_number || '';
                var phoneEl = document.getElementById('client-phone');
                var phoneSep = document.getElementById('client-phone-sep');
                var phoneText = document.getElementById('client-phone-text');
                if (phoneEl && phoneRaw) {
                    var digits = phoneRaw.replace(/\D/g, '');
                    if (digits.length >= 10) {
                        var whatsNumber = digits.startsWith('55') ? digits : '55' + digits;
                        phoneEl.href = 'https://wa.me/' + whatsNumber;
                        phoneEl.target = '_blank';
                        phoneEl.rel = 'noopener';
                        phoneText.textContent = phoneRaw;
                        phoneEl.classList.remove('hidden');
                        phoneSep.classList.remove('hidden');
                    }
                }

                // Plano
                var planEl = document.getElementById('client-plan');
                if (planEl) {
                    var planNames = {
                        6: 'Plano Básico - R$ 130',
                        7: 'Plano 100 - R$ 100',
                        8: 'Plano Avançado - R$ 180',
                        9: 'Plano Intermediário - R$ 150'
                    };
                    planEl.textContent = planNames[client.planId] || ('Plano ID: ' + client.planId);
                }
                
                // PPPoE
                var pppoeEl = document.getElementById('client-pppoe');
                if (pppoeEl) pppoeEl.textContent = client.pppoe || client.pppoe_user || client.usuario_pppoe || 'Não informado';
                
                // Armazena PPPoE para uso no WiFi
                currentPppoe = client.pppoe || client.pppoe_user || client.usuario_pppoe || '';

                // Senha (armazena para toggle)
                clientPassword = client.password || client.pppoe_password || client.senha || '';
                var passwordEl = document.getElementById('client-password');
                if (passwordEl) {
                    passwordEl.textContent = clientPassword ? '••••••••' : 'Não informada';
                }
                
                // Vencimento
                var dueEl = document.getElementById('client-due');
                if (dueEl) dueEl.textContent = 'Todo dia ' + (client.dueDay || '10');
                
                // Endereço
                var addressEl = document.getElementById('client-address');
                var fullAddress = '';
                if (addressEl) {
                    var endereco = (client.address || 'Endereço não informado');
                    if (client.number) endereco += ', ' + client.number;
                    if (client.complement) endereco += ' - ' + client.complement;
                    if (client.neighborhood) endereco += '<br>' + client.neighborhood;
                    if (client.city || client.state) {
                        endereco += '<br>';
                        if (client.city) endereco += client.city;
                        if (client.city && client.state) endereco += ' - ';
                        if (client.state) endereco += client.state;
                    }
                    addressEl.innerHTML = endereco;

                    // Monta endereço completo para o mapa
                    var addressParts = [];
                    if (client.address) addressParts.push(client.address);
                    if (client.number) addressParts.push(client.number);
                    if (client.neighborhood) addressParts.push(client.neighborhood);
                    if (client.city) addressParts.push(client.city);
                    if (client.state) addressParts.push(client.state);
                    fullAddress = addressParts.join(', ');
                }

                // Carrega mini mapa com a localização
                if (fullAddress || client.city) {
                    loadMiniMap(fullAddress || client.city);
                }
                
                // Observações
                var obsEl = document.getElementById('client-observation');
                if (obsEl) {
                    obsEl.textContent = client.observation ? '"' + client.observation + '"' : 'Nenhuma observação registrada.';
                }
                
                // Contrato SGP
                var contratoEl = document.getElementById('client-contrato');
                var macEl = document.getElementById('client-mac');
                
                if (client.contrato) {
                    currentContrato = client.contrato;
                    if (contratoEl) contratoEl.textContent = 'Contrato #' + client.contrato;
                    if (macEl) macEl.textContent = client.serial || '—';
                    
                    // Se tem contrato, verifica o status online/offline e status do contrato
                    await checkConnectionStatus(client.contrato, client.servidor || '');
                    await checkContractStatus(client.contrato, client.servidor || '');
                } else {
                    if (contratoEl) contratoEl.textContent = 'Não vinculado';
                    setConnectionStatus('unknown', 'Contrato não vinculado. Clique em Sincronizar.');
                }
                
                // Carrega informações WiFi via TR069
                await loadWifiInfo();
            } else {
                alert('Cliente não encontrado');
            }
        } catch (error) {
            console.error('Erro ao carregar cliente:', error);
            alert('Erro ao carregar dados do cliente');
        }
    }
    
    // =====================================================
    // Verifica status de conexão (Online/Offline) via SGP
    // =====================================================
    async function checkConnectionStatus(contrato, servidor) {
        setConnectionStatus('loading', 'Verificando conexão...');
        
        try {
            var token = localStorage.getItem('auth_token') || '';
            var headers = {
                'Content-Type': 'application/json'
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }
            
            var bodyData = {
                action: 'verificar_acesso',
                contrato: contrato
            };
            if (servidor) bodyData.servidor = servidor;
            
            var response = await fetch('/api/sgp-status.php', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(bodyData)
            });
            
            var result = await response.json();
            console.log('Status conexão SGP:', result);
            
            if (result.success && result.data) {
                var msg = result.data.msg || '';
                var isOnline = msg.toLowerCase().indexOf('online') !== -1;
                
                if (isOnline) {
                    setConnectionStatus('online', msg);
                } else {
                    setConnectionStatus('offline', msg || 'Serviço Offline');
                }
                
                // Mostra detalhes da conexão
                var detailEl = document.getElementById('connection-detail');
                var msgEl = document.getElementById('connection-msg');
                if (detailEl && msgEl) {
                    detailEl.classList.remove('hidden');
                    var dotColor = isOnline ? 'bg-green-500' : 'bg-red-500';
                    var textColor = isOnline ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    msgEl.innerHTML = '<div class="w-3 h-3 rounded-full ' + dotColor + '"></div>' +
                        '<span class="text-sm font-semibold ' + textColor + '">' + (msg || 'Sem resposta') + '</span>';
                }
            } else {
                setConnectionStatus('error', 'Erro ao verificar');
            }
        } catch (error) {
            console.error('Erro ao verificar status:', error);
            setConnectionStatus('error', 'Erro de conexão');
        }
    }
    
    // =====================================================
    // Atualiza indicador visual de status (bolinha + texto)
    // =====================================================
    function setConnectionStatus(status, message) {
        var dot = document.getElementById('status-dot');
        var text = document.getElementById('status-text');
        
        if (!dot || !text) return;
        
        // Remove animate-pulse de todos
        dot.classList.remove('animate-pulse');
        
        switch (status) {
            case 'online':
                dot.className = 'w-2.5 h-2.5 rounded-full bg-green-500 shadow-sm shadow-green-500/50';
                text.className = 'text-xs font-semibold text-green-600 dark:text-green-400';
                text.textContent = message || 'Online';
                break;
            case 'offline':
                dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50';
                text.className = 'text-xs font-semibold text-red-600 dark:text-red-400';
                text.textContent = message || 'Offline';
                break;
            case 'loading':
                dot.className = 'w-2.5 h-2.5 rounded-full bg-yellow-400 animate-pulse';
                text.className = 'text-xs text-yellow-600 dark:text-yellow-400';
                text.textContent = message || 'Verificando...';
                break;
            case 'error':
                dot.className = 'w-2.5 h-2.5 rounded-full bg-orange-400';
                text.className = 'text-xs text-orange-500';
                text.textContent = message || 'Erro';
                break;
            default:
                dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-300';
                text.className = 'text-xs text-gray-400';
                text.textContent = message || 'Desconhecido';
        }
    }
    
    // =====================================================
    // Consulta status do contrato (Ativo/Suspenso/Cancelado)
    // =====================================================
    async function checkContractStatus(contrato, servidor) {
        try {
            var token = localStorage.getItem('auth_token') || '';
            var headers = {
                'Content-Type': 'application/json'
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }
            
            var bodyData = {
                action: 'consultar_cliente',
                contrato: contrato
            };
            if (servidor) bodyData.servidor = servidor;
            
            var response = await fetch('/api/sgp-status.php', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(bodyData)
            });
            
            var result = await response.json();
            console.log('Status contrato SGP:', result);
            
            if (result.success && result.data) {
                console.log('Dados consultacliente completo:', JSON.stringify(result.data));
                var statusDisplay = '';
                
                // O status vem dentro de contratos[0].contratoStatusDisplay
                if (result.data.contratos && result.data.contratos.length > 0) {
                    var c = result.data.contratos[0];
                    statusDisplay = c.contratoStatusDisplay || c.status || c.contratoStatus || '';
                    
                    // Se contratoStatus é numérico (1=Ativo, 2=Suspenso, 3=Cancelado)
                    if (!statusDisplay && typeof c.contratoStatus === 'number') {
                        var statusMap = {1: 'Ativo', 2: 'Suspenso', 3: 'Cancelado'};
                        statusDisplay = statusMap[c.contratoStatus] || '';
                    }
                }
                
                // Fallback para campos na raiz
                if (!statusDisplay) {
                    statusDisplay = result.data.contratoStatusDisplay || result.data.status || '';
                }
                
                if (statusDisplay) {
                    setContractStatusBadge(statusDisplay);
                }
            }
        } catch (error) {
            console.error('Erro ao consultar status do contrato:', error);
        }
    }
    
    // =====================================================
    // Atualiza badge de status do contrato
    // =====================================================
    function setContractStatusBadge(status) {
        var badge = document.getElementById('client-status');
        if (!badge) return;
        
        badge.textContent = status || 'Desconhecido';
        
        var s = status.toLowerCase();
        
        if (s === 'ativo') {
            badge.className = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase';
        } else if (s === 'suspenso') {
            badge.className = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase';
        } else if (s === 'cancelado') {
            badge.className = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase';
        } else {
            badge.className = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase';
        }
    }
    
    // =====================================================
    // Sincroniza dados do SGP (busca contrato e MAC)
    // =====================================================
    async function syncFromSGP() {
        if (!currentCpf) {
            alert('CPF não disponível');
            return;
        }
        
        var btn = document.getElementById('btn-sync-sgp');
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div> Buscando...';
        btn.disabled = true;
        
        try {
            var token = localStorage.getItem('auth_token') || '';
            var headers = {
                'Content-Type': 'application/json'
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }
            
            // 1. Busca dados do cliente no SGP pelo CPF
            var response = await fetch('/api/sgp-status.php', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    action: 'buscar_cliente',
                    cpf: currentCpf
                })
            });
            
            var result = await response.json();
            console.log('SGP Clientes:', result);
            
            if (!result.success || !result.data || !result.data.clientes || result.data.clientes.length === 0) {
                alert('Cliente não encontrado no SGP. Verifique o CPF.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                return;
            }
            
            var sgpClient = result.data.clientes[0];
            var contrato = null;
            var mac = null;
            
            // 2. Extrai contrato e MAC dos dados do SGP
            if (sgpClient.contratos && sgpClient.contratos.length > 0) {
                var c = sgpClient.contratos[0];
                contrato = c.id || c.contrato;

                // Busca MAC em servicos
                if (c.servicos && c.servicos.length > 0) {
                    var svc = c.servicos[0];
                    mac = svc.mac || svc.endereco_mac || svc.mac_address || svc.serial || null;
                }
                // Busca MAC diretamente no contrato
                if (!mac) {
                    mac = c.mac || c.endereco_mac || c.mac_address || null;
                }
            }
            // Busca MAC na raiz do cliente SGP
            if (!mac) {
                mac = sgpClient.mac || sgpClient.endereco_mac || sgpClient.mac_address || null;
            }
            
            if (!contrato) {
                alert('Nenhum contrato encontrado para este cliente no SGP.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                return;
            }
            
            // 3. Salva contrato e MAC no banco de dados local
            var saveResponse = await fetch('/api/sgp-status.php', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    action: 'salvar_contrato',
                    cpf: currentCpf,
                    contrato: contrato.toString(),
                    mac: mac
                })
            });
            
            var saveResult = await saveResponse.json();
            console.log('Salvar contrato:', saveResult);
            
            if (saveResult.success) {
                currentContrato = contrato.toString();
                currentServidor = result.servidor || '';
                
                // Atualiza a UI
                var contratoEl = document.getElementById('client-contrato');
                var macEl = document.getElementById('client-mac');
                if (contratoEl) contratoEl.textContent = 'Contrato #' + contrato;
                if (macEl) macEl.textContent = mac || '—';
                
                // 4. Agora verifica o status online/offline e status do contrato
                await checkConnectionStatus(contrato, currentServidor);
                await checkContractStatus(contrato, currentServidor);
                
                // Feedback de sucesso
                btn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span> Sincronizado!';
                btn.classList.remove('bg-primary/10', 'text-primary');
                btn.classList.add('bg-green-100', 'text-green-600');
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-100', 'text-green-600');
                    btn.classList.add('bg-primary/10', 'text-primary');
                    btn.disabled = false;
                }, 3000);
            } else {
                // Mesmo se não salvou no banco local, ainda mostra os dados do SGP
                console.warn('Não salvou no banco local:', saveResult.message);
                currentContrato = contrato.toString();
                currentServidor = result.servidor || '';
                
                var contratoEl = document.getElementById('client-contrato');
                var macEl = document.getElementById('client-mac');
                if (contratoEl) contratoEl.textContent = 'Contrato #' + contrato;
                if (macEl) macEl.textContent = mac || '—';
                
                // Verifica status mesmo sem salvar
                await checkConnectionStatus(contrato, currentServidor);
                await checkContractStatus(contrato, currentServidor);
                
                btn.innerHTML = '<span class="material-symbols-outlined text-sm">warning</span> Parcial';
                btn.classList.remove('bg-primary/10', 'text-primary');
                btn.classList.add('bg-yellow-100', 'text-yellow-600');
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-yellow-100', 'text-yellow-600');
                    btn.classList.add('bg-primary/10', 'text-primary');
                    btn.disabled = false;
                }, 3000);
            }
            
        } catch (error) {
            console.error('Erro ao sincronizar SGP:', error);
            alert('Erro ao comunicar com o SGP. Tente novamente.');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }
    
    function openPhoto(url) {
        // Abre a foto em tela cheia
        var modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4';
        modal.onclick = function() { modal.remove(); };
        modal.innerHTML = '<img src="' + url + '" class="max-w-full max-h-full object-contain rounded-lg">' +
            '<button class="absolute top-4 right-4 text-white bg-black/50 rounded-full p-2">' +
                '<span class="material-symbols-outlined">close</span>' +
            '</button>';
        document.body.appendChild(modal);
    }
    
    // =====================================================
    // Upload de foto da instalação
    // =====================================================
    function openUploadPhoto() {
        if (!currentCpf) return;
        
        var modal = document.createElement('div');
        modal.id = 'upload-modal';
        modal.className = 'fixed inset-0 bg-black/60 z-50 flex items-end justify-center';
        modal.innerHTML = 
            '<div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-t-3xl p-6 pb-10 animate-slide-up" onclick="event.stopPropagation()">' +
                '<div class="flex items-center justify-between mb-5">' +
                    '<h3 class="text-lg font-bold text-gray-900 dark:text-white">Adicionar Foto</h3>' +
                    '<button onclick="closeUploadModal()" class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">' +
                        '<span class="material-symbols-outlined text-gray-500">close</span>' +
                    '</button>' +
                '</div>' +
                '<div class="mb-4">' +
                    '<label class="text-xs font-semibold text-gray-500 uppercase mb-2 block">Tipo da foto</label>' +
                    '<select id="upload-photo-type" class="w-full h-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white px-4 text-sm">' +
                        '<option value="router">Roteador</option>' +
                        '<option value="cabling">Cabeamento</option>' +
                        '<option value="signal">Sinal</option>' +
                        '<option value="other">Outro</option>' +
                    '</select>' +
                '</div>' +
                '<div id="upload-preview" class="hidden mb-4">' +
                    '<img id="upload-preview-img" class="w-full h-40 object-cover rounded-xl" />' +
                '</div>' +
                '<div class="flex gap-3">' +
                    '<label class="flex-1 h-14 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-2xl font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer">' +
                        '<span class="material-symbols-outlined">photo_camera</span>' +
                        'Câmera' +
                        '<input type="file" accept="image/*" capture="environment" class="hidden" onchange="previewUploadPhoto(this)" />' +
                    '</label>' +
                    '<label class="flex-1 h-14 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-2xl font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer">' +
                        '<span class="material-symbols-outlined">photo_library</span>' +
                        'Galeria' +
                        '<input type="file" accept="image/*" class="hidden" onchange="previewUploadPhoto(this)" />' +
                    '</label>' +
                '</div>' +
                '<button id="btn-upload-send" onclick="sendUploadPhoto()" class="w-full h-14 bg-primary text-white rounded-2xl font-bold text-base flex items-center justify-center gap-2 mt-4 hidden">' +
                    '<span class="material-symbols-outlined">cloud_upload</span>' +
                    'Enviar Foto' +
                '</button>' +
            '</div>';
        modal.onclick = function(e) { if (e.target === modal) closeUploadModal(); };
        document.body.appendChild(modal);
    }
    
    var uploadSelectedFile = null;
    
    function previewUploadPhoto(input) {
        if (input.files && input.files[0]) {
            uploadSelectedFile = input.files[0];
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('upload-preview');
                var img = document.getElementById('upload-preview-img');
                if (preview && img) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                var sendBtn = document.getElementById('btn-upload-send');
                if (sendBtn) sendBtn.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    async function sendUploadPhoto() {
        if (!uploadSelectedFile || !currentCpf) return;
        
        var btn = document.getElementById('btn-upload-send');
        btn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Enviando...';
        btn.disabled = true;
        
        var type = document.getElementById('upload-photo-type').value;
        var token = localStorage.getItem('auth_token') || '';
        
        var formData = new FormData();
        formData.append('photo', uploadSelectedFile);
        formData.append('cpf', currentCpf);
        formData.append('type', type);
        
        try {
            var response = await fetch('/api/upload-foto.php', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                body: formData
            });
            
            var result = await response.json();
            console.log('Upload foto:', result);
            
            if (result.success) {
                closeUploadModal();
                // Recarrega as fotos
                await loadPhotos(currentCpf);
            } else {
                alert('Erro ao enviar: ' + (result.message || 'Tente novamente'));
                btn.innerHTML = '<span class="material-symbols-outlined">cloud_upload</span> Enviar Foto';
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Erro no upload:', error);
            alert('Erro ao enviar foto. Verifique sua conexão.');
            btn.innerHTML = '<span class="material-symbols-outlined">cloud_upload</span> Enviar Foto';
            btn.disabled = false;
        }
    }
    
    function closeUploadModal() {
        var modal = document.getElementById('upload-modal');
        if (modal) modal.remove();
        uploadSelectedFile = null;
    }
    
    function formatCPF(cpf) {
        if (!cpf) return '';
        cpf = cpf.toString().replace(/\D/g, '');
        if (cpf.length !== 11) return cpf;
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    
    // Carrega mini mapa usando OpenStreetMap via Nominatim geocoding
    async function loadMiniMap(address) {
        var mapContainer = document.getElementById('mini-map');
        if (!mapContainer) return;

        try {
            // Geocodifica o endereço usando Nominatim (OpenStreetMap)
            var geocodeUrl = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1';
            var response = await fetch(geocodeUrl, {
                headers: { 'Accept-Language': 'pt-BR' }
            });
            var results = await response.json();

            if (results && results.length > 0) {
                var lat = results[0].lat;
                var lon = results[0].lon;

                // Usa iframe do OpenStreetMap para mostrar o mapa
                mapContainer.innerHTML = '<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border-radius: 12px;" src="https://www.openstreetmap.org/export/embed.html?bbox=' + (parseFloat(lon) - 0.005) + '%2C' + (parseFloat(lat) - 0.003) + '%2C' + (parseFloat(lon) + 0.005) + '%2C' + (parseFloat(lat) + 0.003) + '&layer=mapnik&marker=' + lat + '%2C' + lon + '"></iframe>';
            } else {
                mapContainer.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-gray-400"><span class="material-symbols-outlined text-2xl">location_off</span><p class="text-xs mt-1">Localização não encontrada</p></div>';
            }
        } catch (error) {
            console.error('Erro ao carregar mapa:', error);
            mapContainer.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-gray-400"><span class="material-symbols-outlined text-2xl">map</span><p class="text-xs mt-1">Erro ao carregar mapa</p></div>';
        }
    }

    // Registra visualização do cliente no log de auditoria
    async function logClientView(client) {
        try {
            var token = localStorage.getItem('auth_token');
            if (!token) return;
            
            await fetch('/api/audit-log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    action_type: 'client_viewed',
                    action_description: 'Visualizou detalhes do cliente',
                    entity_type: 'client',
                    entity_id: client.cpf,
                    entity_name: client.name,
                    details: {
                        plan: client.planId,
                        city: client.city,
                        status: client.status
                    }
                })
            });
        } catch (error) {
            console.error('Erro ao registrar visualização:', error);
        }
    }
    
    // =====================================================
    // Verificação de Admin e Ações de Admin
    // =====================================================
    
    function checkAdminAccess() {
        try {
            var userData = localStorage.getItem('user_data');
            if (userData) {
                var user = JSON.parse(userData);
                if (user.role === 'admin') {
                    var adminActions = document.getElementById('admin-actions');
                    if (adminActions) {
                        adminActions.classList.remove('hidden');
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao verificar acesso admin:', error);
        }
    }
    
    function editClient() {
        if (!currentCpf) return;
        // Redireciona para página de edição com CPF
        window.location.href = 'novo-cadastro.php?edit=' + encodeURIComponent(currentCpf);
    }
    
    function confirmDeleteClient() {
        if (!currentCpf) return;
        
        var clientName = document.getElementById('client-name')?.textContent || 'este cliente';
        
        // Modal de confirmação
        var modal = document.createElement('div');
        modal.id = 'delete-modal';
        modal.className = 'fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4';
        modal.innerHTML = 
            '<div class="bg-white dark:bg-gray-900 w-full max-w-sm rounded-2xl p-6 shadow-2xl" onclick="event.stopPropagation()">' +
                '<div class="flex flex-col items-center text-center">' +
                    '<div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">' +
                        '<span class="material-symbols-outlined text-red-500 text-3xl">delete_forever</span>' +
                    '</div>' +
                    '<h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Excluir Cliente</h3>' +
                    '<p class="text-gray-500 dark:text-gray-400 text-sm mb-6">' +
                        'Tem certeza que deseja excluir <strong class="text-gray-900 dark:text-white">' + clientName + '</strong>?<br>' +
                        '<span class="text-red-500">Esta ação não pode ser desfeita.</span>' +
                    '</p>' +
                    '<div class="flex gap-3 w-full">' +
                        '<button onclick="closeDeleteModal()" class="flex-1 h-12 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold">' +
                            'Cancelar' +
                        '</button>' +
                        '<button onclick="deleteClient()" id="btn-confirm-delete" class="flex-1 h-12 bg-red-500 text-white rounded-xl font-semibold flex items-center justify-center gap-2">' +
                            '<span class="material-symbols-outlined text-sm">delete</span>' +
                            'Excluir' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        modal.onclick = function(e) { if (e.target === modal) closeDeleteModal(); };
        document.body.appendChild(modal);
    }
    
    function closeDeleteModal() {
        var modal = document.getElementById('delete-modal');
        if (modal) modal.remove();
    }
    
    async function deleteClient() {
        if (!currentCpf) return;
        
        var btn = document.getElementById('btn-confirm-delete');
        if (btn) {
            btn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>';
            btn.disabled = true;
        }
        
        try {
            var result = await API.deleteClient(currentCpf);
            
            if (result.success) {
                closeDeleteModal();
                
                // Mostra mensagem de sucesso
                var toast = document.createElement('div');
                toast.className = 'fixed top-20 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 flex items-center gap-2';
                toast.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Cliente excluído com sucesso';
                document.body.appendChild(toast);
                
                // Redireciona após 1.5 segundos
                setTimeout(function() {
                    window.location.href = 'consultar.php';
                }, 1500);
            } else {
                alert('Erro ao excluir: ' + (result.message || 'Tente novamente'));
                if (btn) {
                    btn.innerHTML = '<span class="material-symbols-outlined text-sm">delete</span> Excluir';
                    btn.disabled = false;
                }
            }
        } catch (error) {
            console.error('Erro ao excluir cliente:', error);
            alert('Erro ao excluir cliente. Verifique sua conexão.');
            if (btn) {
                btn.innerHTML = '<span class="material-symbols-outlined text-sm">delete</span> Excluir';
                btn.disabled = false;
            }
        }
    }
    
    // =====================================================
    // FUNÇÕES DE GERENCIAMENTO WIFI / TR069
    // =====================================================
    
    /**
     * Carrega informações WiFi do dispositivo via TR069
     */
    async function loadWifiInfo() {
        // Esconde todos os estados
        hideAllWifiStates();
        document.getElementById('wifi-loading').classList.remove('hidden');
        setWifiStatusBadge('loading', 'Carregando...');
        
        // Verifica se tem PPPoE configurado
        if (!currentPppoe) {
            hideAllWifiStates();
            document.getElementById('wifi-no-pppoe').classList.remove('hidden');
            setWifiStatusBadge('warning', 'Sem PPPoE');
            return;
        }
        
        try {
            var token = localStorage.getItem('auth_token') || '';
            var headers = {
                'Content-Type': 'application/json'
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }
            
            // Primeiro verifica se o TR069 está online
            var healthResponse = await fetch('/api/tr069.php?action=health', { headers: headers });
            var healthResult = await healthResponse.json();
            
            if (!healthResult.success) {
                hideAllWifiStates();
                document.getElementById('wifi-error').classList.remove('hidden');
                document.getElementById('wifi-error-msg').textContent = 'Serviço TR069 indisponível';
                setWifiStatusBadge('error', 'Offline');
                return;
            }
            
            // Busca o dispositivo pelo PPPoE
            var response = await fetch('/api/tr069.php?action=get_device&pppoe=' + encodeURIComponent(currentPppoe), {
                headers: headers
            });
            var result = await response.json();
            
            console.log('TR069 Device:', result);
            
            if (!result.success) {
                hideAllWifiStates();
                if (response.status === 404) {
                    document.getElementById('wifi-not-found').classList.remove('hidden');
                    setWifiStatusBadge('warning', 'Não encontrado');
                } else {
                    document.getElementById('wifi-error').classList.remove('hidden');
                    document.getElementById('wifi-error-msg').textContent = result.error || 'Erro desconhecido';
                    setWifiStatusBadge('error', 'Erro');
                }
                return;
            }
            
            // Sucesso - armazena dados e exibe
            wifiData = result;
            
            // Preenche informações do dispositivo
            document.getElementById('wifi-manufacturer').textContent = result.device?.manufacturer || '—';
            document.getElementById('wifi-model').textContent = result.device?.model || '—';
            document.getElementById('wifi-serial').textContent = result.device?.serial_number || '—';
            document.getElementById('wifi-ip').textContent = result.device?.ip_address || '—';
            
            // Preenche informações WiFi
            document.getElementById('wifi-ssid').textContent = result.wifi?.ssid || 'Não configurado';
            document.getElementById('wifi-security').textContent = formatSecurityMode(result.wifi?.security_mode, result.wifi?.encryption);
            
            // Formata último contato
            var lastContact = result.device?.last_contact;
            if (lastContact) {
                var date = new Date(lastContact);
                document.getElementById('wifi-last-contact').textContent = 'Último contato: ' + date.toLocaleString('pt-BR');
            } else {
                document.getElementById('wifi-last-contact').textContent = 'Último contato: —';
            }
            
            // Status online/offline
            if (result.device?.is_online) {
                setWifiStatusBadge('online', 'Online');
            } else {
                setWifiStatusBadge('offline', 'Offline');
            }
            
            // Mostra a seção de informações
            hideAllWifiStates();
            document.getElementById('wifi-info').classList.remove('hidden');
            
        } catch (error) {
            console.error('Erro ao carregar WiFi:', error);
            hideAllWifiStates();
            document.getElementById('wifi-error').classList.remove('hidden');
            document.getElementById('wifi-error-msg').textContent = 'Erro de conexão: ' + error.message;
            setWifiStatusBadge('error', 'Erro');
        }
    }
    
    /**
     * Esconde todos os estados da seção WiFi
     */
    function hideAllWifiStates() {
        document.getElementById('wifi-loading').classList.add('hidden');
        document.getElementById('wifi-no-pppoe').classList.add('hidden');
        document.getElementById('wifi-not-found').classList.add('hidden');
        document.getElementById('wifi-error').classList.add('hidden');
        document.getElementById('wifi-info').classList.add('hidden');
    }
    
    /**
     * Atualiza o badge de status WiFi
     */
    function setWifiStatusBadge(status, text) {
        var badge = document.getElementById('wifi-status-badge');
        var dot = document.getElementById('wifi-status-dot');
        var textEl = document.getElementById('wifi-status-text');
        
        textEl.textContent = text;
        dot.classList.remove('animate-pulse');
        
        switch (status) {
            case 'online':
                badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/30';
                dot.className = 'w-2 h-2 rounded-full bg-green-500';
                textEl.className = 'text-[10px] font-medium text-green-600 dark:text-green-400';
                break;
            case 'offline':
                badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-100 dark:bg-red-900/30';
                dot.className = 'w-2 h-2 rounded-full bg-red-500';
                textEl.className = 'text-[10px] font-medium text-red-600 dark:text-red-400';
                break;
            case 'warning':
                badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30';
                dot.className = 'w-2 h-2 rounded-full bg-yellow-500';
                textEl.className = 'text-[10px] font-medium text-yellow-600 dark:text-yellow-400';
                break;
            case 'error':
                badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-100 dark:bg-red-900/30';
                dot.className = 'w-2 h-2 rounded-full bg-red-500';
                textEl.className = 'text-[10px] font-medium text-red-600 dark:text-red-400';
                break;
            case 'loading':
            default:
                badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800';
                dot.className = 'w-2 h-2 rounded-full bg-gray-400 animate-pulse';
                textEl.className = 'text-[10px] font-medium text-gray-500';
                break;
        }
    }
    
    /**
     * Formata o modo de segurança
     */
    function formatSecurityMode(mode, encryption) {
        if (!mode) return '—';
        var formatted = mode;
        if (encryption) {
            formatted += ' / ' + encryption;
        }
        return formatted;
    }
    
    /**
     * Toggle de visibilidade da senha WiFi (na seção)
     */
    function toggleWifiPassword() {
        wifiPasswordVisible = !wifiPasswordVisible;
        var displayEl = document.getElementById('wifi-password-display');
        var iconEl = document.getElementById('wifi-password-icon');
        
        if (wifiPasswordVisible && wifiData?.wifi?.password) {
            var pass = wifiData.wifi.password;
            displayEl.textContent = (pass === '[hidden]') ? '(Protegida)' : pass;
            iconEl.textContent = 'visibility_off';
        } else {
            displayEl.textContent = '••••••••';
            iconEl.textContent = 'visibility';
        }
    }
    
    /**
     * Abre modal de edição WiFi
     */
    function openEditWifiModal(mode) {
        var modal = document.getElementById('wifi-edit-modal');
        var titleEl = document.getElementById('wifi-modal-title');
        var fieldSsid = document.getElementById('wifi-field-ssid');
        var fieldPassword = document.getElementById('wifi-field-password');
        var fieldSecurity = document.getElementById('wifi-field-security');
        
        // Reseta formulário
        document.getElementById('wifi-edit-form').reset();
        
        // Configura os campos visíveis baseado no modo
        switch (mode) {
            case 'ssid':
                titleEl.textContent = 'Alterar Nome da Rede';
                fieldSsid.classList.remove('hidden');
                fieldPassword.classList.add('hidden');
                fieldSecurity.classList.add('hidden');
                // Preenche com valor atual
                if (wifiData?.wifi?.ssid) {
                    document.getElementById('wifi-input-ssid').value = wifiData.wifi.ssid;
                }
                break;
            case 'password':
                titleEl.textContent = 'Alterar Senha WiFi';
                fieldSsid.classList.add('hidden');
                fieldPassword.classList.remove('hidden');
                fieldSecurity.classList.add('hidden');
                break;
            case 'all':
            default:
                titleEl.textContent = 'Configurar WiFi';
                fieldSsid.classList.remove('hidden');
                fieldPassword.classList.remove('hidden');
                fieldSecurity.classList.remove('hidden');
                // Preenche com valor atual
                if (wifiData?.wifi?.ssid) {
                    document.getElementById('wifi-input-ssid').value = wifiData.wifi.ssid;
                }
                break;
        }
        
        // Mostra modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.onclick = function(e) { if (e.target === modal) closeWifiModal(); };
    }
    
    /**
     * Fecha modal de edição WiFi
     */
    function closeWifiModal() {
        var modal = document.getElementById('wifi-edit-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalPasswordVisible = false;
    }
    
    /**
     * Toggle senha no modal
     */
    function toggleModalPassword() {
        modalPasswordVisible = !modalPasswordVisible;
        var input = document.getElementById('wifi-input-password');
        var icon = document.getElementById('modal-password-icon');
        
        input.type = modalPasswordVisible ? 'text' : 'password';
        icon.textContent = modalPasswordVisible ? 'visibility_off' : 'visibility';
    }
    
    /**
     * Submete alterações WiFi
     */
    async function submitWifiChanges(event) {
        event.preventDefault();
        
        if (!currentPppoe) {
            alert('PPPoE não configurado');
            return;
        }
        
        var btn = document.getElementById('wifi-submit-btn');
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Salvando...';
        btn.disabled = true;
        
        try {
            // Coleta os dados do formulário
            var ssid = document.getElementById('wifi-input-ssid').value.trim();
            var password = document.getElementById('wifi-input-password').value;
            var security = document.getElementById('wifi-input-security').value;
            
            // Verifica se pelo menos um campo foi preenchido
            if (!ssid && !password && !security) {
                alert('Preencha pelo menos um campo para alterar');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                return;
            }
            
            // Valida senha mínima
            if (password && password.length < 8) {
                alert('A senha deve ter no mínimo 8 caracteres');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                return;
            }
            
            // Prepara dados para enviar
            var data = {
                action: 'change_wifi',
                pppoe: currentPppoe
            };
            if (ssid) data.ssid = ssid;
            if (password) data.password = password;
            if (security) data.security_mode = security;
            
            var token = localStorage.getItem('auth_token') || '';
            var response = await fetch('/api/tr069.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(data)
            });
            
            var result = await response.json();
            console.log('WiFi Change Result:', result);
            
            if (result.success) {
                closeWifiModal();
                
                // Mostra mensagem de sucesso
                showToast('success', 'Configurações enviadas! Serão aplicadas no próximo contato do equipamento.');
                
                // Atualiza a interface local imediatamente
                if (ssid) {
                    document.getElementById('wifi-ssid').textContent = ssid;
                    if (wifiData && wifiData.wifi) {
                        wifiData.wifi.ssid = ssid;
                    }
                }
                
                // Recarrega informações após 2 segundos
                setTimeout(function() {
                    loadWifiInfo();
                }, 2000);
            } else {
                alert('Erro: ' + (result.error || 'Não foi possível salvar as alterações'));
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Erro ao salvar WiFi:', error);
            alert('Erro de conexão ao salvar alterações');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }
    
    /**
     * Exibe toast de notificação
     */
    function showToast(type, message) {
        var bgColor = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-gray-700');
        var icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');
        
        var toast = document.createElement('div');
        toast.className = 'fixed top-20 left-1/2 -translate-x-1/2 ' + bgColor + ' text-white px-5 py-3 rounded-xl shadow-lg z-50 flex items-center gap-2 max-w-xs text-sm';
        toast.innerHTML = '<span class="material-symbols-outlined text-lg">' + icon + '</span> ' + message;
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.style.transition = 'opacity 0.3s';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    }
    
    function openScheduleMaintenance() {
        // Placeholder para agendar manutenção
        alert('Funcionalidade de agendamento em desenvolvimento.\n\nCliente: ' + (document.getElementById('client-name')?.textContent || currentCpf));
    }
</script>
</body></html>