<!DOCTYPE html>
<html class="light" lang="pt-BR"><head>
<title>Vincular Equipamento - Ondeline</title>
<?php include 'partials/head.php'; ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex h-auto min-h-screen w-full flex-col bg-background-light dark:bg-background-dark group/design-root overflow-x-hidden">
    <!-- Header -->
    <header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
        <div class="flex items-center justify-between px-4 h-16">
            <a href="dashboard.php" class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 btn-press">
                <span class="material-symbols-outlined">arrow_back_ios</span>
            </a>
            <h2 class="text-gray-900 dark:text-white text-lg font-bold flex-1 text-center">Vincular Equipamento</h2>
            <div class="size-10"></div>
        </div>
    </header>

    <div class="flex flex-col gap-4 p-4 pb-32">
        <!-- Ícone ilustrativo com glassmorphism -->
        <div class="flex flex-col items-center py-8 stagger-item opacity-0" style="animation-delay: 0.1s">
            <div class="size-28 rounded-3xl glass-premium flex items-center justify-center mb-4 shadow-xl">
                <div class="size-20 rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-white" style="font-variation-settings: 'FILL' 1">router</span>
                </div>
            </div>
            <h3 class="text-gray-900 dark:text-white text-xl font-bold">Vincular Roteador</h3>
            <p class="text-gray-500 text-sm text-center mt-2 max-w-xs">Insira o número de série do equipamento e selecione o cliente para vincular.</p>
        </div>

        <!-- Campo Serial com glass effect -->
        <div class="glass-premium rounded-2xl p-5 stagger-item opacity-0" style="animation-delay: 0.2s">
            <div class="flex items-center gap-3 mb-4">
                <div class="size-10 rounded-xl bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-purple-600 dark:text-purple-400" style="font-variation-settings: 'FILL' 1">qr_code_2</span>
                </div>
                <h3 class="text-gray-900 dark:text-white text-lg font-bold">Dados do Equipamento</h3>
            </div>
            
            <label class="flex flex-col">
                <p class="text-gray-700 dark:text-gray-300 text-sm font-semibold leading-normal pb-2">Número de Série</p>
                <div class="relative">
                    <input id="field-serial" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-gray-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 focus:border-primary h-14 placeholder:text-gray-400 px-4 pr-12 text-base font-mono font-medium leading-normal uppercase transition-all" placeholder="Ex: SN123456789" type="text" value="" required/>
                    <button id="btn-scan" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 hover:scale-105 transition-all btn-press">
                        <span class="material-symbols-outlined text-xl">qr_code_scanner</span>
                    </button>
                </div>
                <p class="text-gray-500 text-xs mt-2">Digite ou escaneie o código de barras/QR do equipamento</p>
            </label>
            
            <!-- Motivo da Vinculação -->
            <label class="flex flex-col mt-5">
                <p class="text-gray-700 dark:text-gray-300 text-sm font-semibold leading-normal pb-2">Motivo</p>
                <select id="field-reason" class="form-input flex w-full min-w-0 rounded-xl text-gray-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 focus:border-primary h-12 text-base font-normal leading-normal transition-all">
                    <option value="">Selecione um motivo</option>
                    <option value="installation">Instalação</option>
                    <option value="defect">Defeito no equipamento</option>
                    <option value="upgrade">Upgrade de equipamento</option>
                    <option value="transfer">Transferência de unidade</option>
                    <option value="theft">Roubo ou furto</option>
                    <option value="other">Outro motivo</option>
                </select>
            </label>
            
            <label class="flex flex-col mt-4">
                <p class="text-gray-700 dark:text-gray-300 text-sm font-semibold leading-normal pb-2">Descrição do Motivo (opcional)</p>
                <textarea id="field-reason-desc" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-gray-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 focus:border-primary min-h-[80px] px-4 py-3 text-base font-normal leading-normal transition-all" placeholder="Descreva mais detalhes sobre a troca..."></textarea>
            </label>
        </div>

        <!-- Busca de Cliente com glass effect -->
        <div class="glass-premium rounded-2xl p-5 stagger-item opacity-0" style="animation-delay: 0.3s">
            <div class="flex items-center gap-3 mb-4">
                <div class="size-10 rounded-xl bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400" style="font-variation-settings: 'FILL' 1">person_search</span>
                </div>
                <h3 class="text-gray-900 dark:text-white text-lg font-bold">Selecionar Cliente</h3>
            </div>
            
            <label class="flex flex-col mb-4">
                <p class="text-gray-700 dark:text-gray-300 text-sm font-semibold leading-normal pb-2">Buscar por Nome ou CPF</p>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                    <input id="field-search" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-gray-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/60 focus:border-primary h-12 placeholder:text-gray-400 pl-10 pr-4 text-base font-normal leading-normal transition-all" placeholder="Digite o nome ou CPF do cliente" type="text" value=""/>
                </div>
            </label>

            <!-- Lista de resultados da busca -->
            <div id="search-results" class="space-y-2 max-h-64 overflow-y-auto hidden">
                <!-- Resultados serão inseridos aqui via JS -->
            </div>

            <!-- Cliente selecionado -->
            <div id="selected-client" class="hidden mt-4 p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-500/10 dark:to-emerald-500/10 border border-green-200 dark:border-green-500/30 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="size-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/30">
                        <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1">person</span>
                    </div>
                    <div class="flex-1">
                        <p id="selected-name" class="text-gray-900 dark:text-white font-semibold"></p>
                        <p id="selected-cpf" class="text-gray-600 dark:text-gray-400 text-sm"></p>
                        <p id="selected-address" class="text-gray-500 text-xs mt-1"></p>
                    </div>
                    <button id="btn-clear-selection" class="p-2 rounded-full bg-white/50 dark:bg-gray-700 text-gray-500 hover:bg-white dark:hover:bg-gray-600 transition-all btn-press">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <!-- Serial atual do cliente (se houver) -->
                <div id="current-serial-container" class="hidden mt-3 pt-3 border-t border-green-200 dark:border-green-500/30">
                    <p class="text-xs text-orange-600 dark:text-orange-400 font-medium flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        Equipamento atual: <span id="current-serial" class="font-mono"></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Box com glass -->
        <div class="glass-premium rounded-2xl p-4 flex items-start gap-3 border-blue-200/50 dark:border-blue-500/20 stagger-item opacity-0" style="animation-delay: 0.4s">
            <div class="size-10 rounded-xl bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
            </div>
            <div>
                <p class="text-blue-800 dark:text-blue-300 text-sm font-semibold">Dica</p>
                <p class="text-blue-600 dark:text-blue-400 text-xs mt-1 leading-relaxed">Verifique se o número de série está correto antes de vincular. Esta ação substituirá o equipamento anterior do cliente.</p>
            </div>
        </div>
    </div>

    <!-- Botão Vincular fixo com glassmorphism -->
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-white/60 dark:bg-black/60 backdrop-blur-xl border-t border-gray-200/50 dark:border-white/10 flex justify-center items-center z-40 safe-bottom">
        <button id="btn-vincular" disabled class="w-full max-w-[480px] bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-500 hover:to-violet-500 disabled:from-gray-400 disabled:to-gray-500 dark:disabled:from-gray-600 dark:disabled:to-gray-700 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-500/30 disabled:shadow-none transition-all flex items-center justify-center gap-2 btn-press">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">link</span>
            Vincular Equipamento
        </button>
    </div>
</div>

<!-- Modal do Scanner de Código de Barras -->
<div id="scanner-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80" onclick="closeScanner()"></div>
    <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 bg-white dark:bg-gray-900 rounded-2xl overflow-hidden max-w-md mx-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Escanear Código de Barras</h3>
            <button onclick="closeScanner()" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                <span class="material-symbols-outlined text-gray-500">close</span>
            </button>
        </div>
        <div id="scanner-container" class="relative bg-black aspect-[4/3]">
            <div id="scanner-reader" class="w-full h-full"></div>
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="w-3/4 h-16 border-2 border-primary rounded-lg relative">
                    <div class="absolute inset-0 bg-primary/10"></div>
                    <div class="absolute left-0 right-0 top-1/2 h-0.5 bg-red-500 animate-pulse"></div>
                </div>
            </div>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">Posicione o código de barras dentro da área destacada</p>
            <button onclick="closeScanner()" class="w-full mt-4 py-3 px-4 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Scripts do App -->
<script src="js/api.js"></script>
<script src="js/app.js"></script>
<script src="js/feedback.js"></script>
<script src="js/ui-enhancements.js"></script>
<script src="js/animations.js"></script>
<script>
    // Inicializa animações stagger
    document.addEventListener('DOMContentLoaded', function() {
        // Ativa as animações stagger
        document.querySelectorAll('.stagger-item').forEach(function(el, index) {
            el.classList.add('animate-stagger-in');
        });
    });
</script>
<script>
    // Script de backup para vincular equipamento
    var selectedClient = null;
    
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('=== Vincular Equipamento Iniciado ===');
        
        // Registra o acesso à página de vincular equipamento no log de auditoria
        logPageAccess('link_equipment_page', 'Acessou página de vincular equipamento');
        
        var searchInput = document.getElementById('field-search');
        var searchResults = document.getElementById('search-results');
        var serialInput = document.getElementById('field-serial');
        var btnVincular = document.getElementById('btn-vincular');
        var selectedClientDiv = document.getElementById('selected-client');
        var btnClearSelection = document.getElementById('btn-clear-selection');
        var reasonSelect = document.getElementById('field-reason');
        
        // Verifica se veio do cadastro de novo cliente
        var urlParams = new URLSearchParams(window.location.search);
        var fromCadastro = urlParams.get('from') === 'cadastro';
        var clientCpf = urlParams.get('cpf');
        var clientName = urlParams.get('name');
        var clientAddress = urlParams.get('address');
        
        if (fromCadastro && clientCpf && clientName) {
            console.log('Cliente vindo do cadastro:', clientName, clientCpf);
            
            // Seleciona automaticamente o cliente
            selectClient({
                cpf: clientCpf,
                name: decodeURIComponent(clientName),
                address: clientAddress ? decodeURIComponent(clientAddress) : '',
                serial: ''
            });
            
            // Seleciona "Instalação" como motivo
            if (reasonSelect) {
                reasonSelect.value = 'installation';
            }
            
            // Mostra mensagem informativa
            showSuccess('Cliente cadastrado! Agora vincule o equipamento.');
            
            // Foca no campo de serial
            if (serialInput) {
                setTimeout(function() {
                    serialInput.focus();
                }, 500);
            }
        }
        
        console.log('Elementos encontrados:', {
            searchInput: !!searchInput,
            searchResults: !!searchResults,
            serialInput: !!serialInput,
            btnVincular: !!btnVincular
        });
        
        var searchTimeout;
        
        // Verifica token
        var token = localStorage.getItem('auth_token');
        console.log('Token presente:', !!token);
        if (!token) {
            console.warn('AVISO: Token não encontrado! Usuário pode não estar logado.');
        }
        
        // Busca de clientes
        if (searchInput) {
            console.log('Adicionando listener de busca...');
            
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                var query = e.target.value.trim();
                console.log('Input detectado:', query);
                
                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    searchResults.innerHTML = '';
                    return;
                }
                
                // Mostra loading
                searchResults.innerHTML = '<div class="p-4 text-center text-gray-500"><span class="material-symbols-outlined animate-spin">sync</span> Buscando...</div>';
                searchResults.classList.remove('hidden');
                
                searchTimeout = setTimeout(async function() {
                    console.log('Iniciando busca por:', query);
                    
                    try {
                        // Usa endpoint de busca com autenticação para filtro por cidade
                        var url = '/api/search-clients.php?search=' + encodeURIComponent(query) + '&limit=10';
                        console.log('URL da requisição:', url);

                        var fetchOptions = {};
                        if (token) {
                            fetchOptions.headers = { 'Authorization': 'Bearer ' + token };
                        }
                        var response = await fetch(url, fetchOptions);
                        console.log('Status da resposta:', response.status);
                        
                        var responseText = await response.text();
                        console.log('Resposta raw:', responseText);
                        
                        var result;
                        try {
                            result = JSON.parse(responseText);
                        } catch (parseError) {
                            console.error('Erro ao parsear JSON:', parseError);
                            searchResults.innerHTML = '<div class="p-4 text-center text-red-500">Erro: Resposta inválida do servidor</div>';
                            return;
                        }
                        
                        console.log('Resultado parseado:', result);
                        
                        if (result.success && result.data && result.data.length > 0) {
                            console.log('Encontrados', result.data.length, 'clientes');
                            
                            searchResults.innerHTML = result.data.map(function(client) {
                                var cpfFormatado = formatCPF(client.cpf || '');
                                var endereco = (client.address || '') + ', ' + (client.number || '') + ' - ' + (client.city || '');
                                
                                return '<div class="client-item p-3 rounded-lg bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 mb-2" ' +
                                    'data-cpf="' + (client.cpf || '') + '" ' +
                                    'data-name="' + (client.name || '').replace(/"/g, '&quot;') + '" ' +
                                    'data-address="' + endereco.replace(/"/g, '&quot;') + '" ' +
                                    'data-serial="' + (client.serial || '') + '">' +
                                    '<div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">' +
                                        '<span class="material-symbols-outlined text-blue-600 dark:text-blue-400">person</span>' +
                                    '</div>' +
                                    '<div class="flex-1 min-w-0">' +
                                        '<p class="font-medium text-gray-900 dark:text-white truncate">' + (client.name || 'Sem nome') + '</p>' +
                                        '<p class="text-xs text-gray-500 dark:text-gray-400">' + cpfFormatado + '</p>' +
                                    '</div>' +
                                    (client.serial ? '<span class="material-symbols-outlined text-orange-500" title="Já possui equipamento">router</span>' : '') +
                                '</div>';
                            }).join('');
                            
                            // Adiciona eventos de clique
                            searchResults.querySelectorAll('.client-item').forEach(function(el) {
                                el.onclick = function() {
                                    selectClient({
                                        cpf: el.dataset.cpf,
                                        name: el.dataset.name,
                                        address: el.dataset.address,
                                        serial: el.dataset.serial
                                    });
                                };
                            });
                        } else if (result.success && (!result.data || result.data.length === 0)) {
                            console.log('Nenhum cliente encontrado');
                            searchResults.innerHTML = '<div class="p-4 text-center text-gray-500"><span class="material-symbols-outlined text-2xl mb-2">search_off</span><br>Nenhum cliente encontrado</div>';
                        } else {
                            console.error('Erro na resposta:', result.message);
                            searchResults.innerHTML = '<div class="p-4 text-center text-red-500">Erro: ' + (result.message || 'Erro desconhecido') + '</div>';
                        }
                    } catch (error) {
                        console.error('Erro na busca:', error);
                        searchResults.innerHTML = '<div class="p-4 text-center text-red-500">Erro de conexão: ' + error.message + '</div>';
                    }
                }, 400);
            });
        } else {
            console.error('ERRO: Campo de busca não encontrado!');
        }
        
        // Selecionar cliente
        function selectClient(client) {
            console.log('Cliente selecionado:', client);
            selectedClient = client;
            
            document.getElementById('selected-name').textContent = client.name || 'Sem nome';
            document.getElementById('selected-cpf').textContent = formatCPF(client.cpf || '');
            document.getElementById('selected-address').textContent = client.address || 'Endereço não informado';
            
            var currentSerialContainer = document.getElementById('current-serial-container');
            if (client.serial && client.serial.trim()) {
                document.getElementById('current-serial').textContent = client.serial;
                currentSerialContainer.classList.remove('hidden');
            } else {
                currentSerialContainer.classList.add('hidden');
            }
            
            searchResults.classList.add('hidden');
            selectedClientDiv.classList.remove('hidden');
            searchInput.disabled = true;
            
            checkButton();
        }
        
        // Limpar seleção
        if (btnClearSelection) {
            btnClearSelection.onclick = function() {
                console.log('Limpando seleção');
                selectedClient = null;
                selectedClientDiv.classList.add('hidden');
                searchInput.value = '';
                searchInput.disabled = false;
                searchInput.focus();
                checkButton();
            };
        }
        
        // Serial input
        if (serialInput) {
            serialInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
                checkButton();
            });
        }
        
        // Verificar se pode habilitar botão
        function checkButton() {
            var hasSerial = serialInput && serialInput.value.trim().length >= 3;
            var hasClient = selectedClient !== null;
            btnVincular.disabled = !(hasSerial && hasClient);
            console.log('Check button:', { hasSerial: hasSerial, hasClient: hasClient, disabled: btnVincular.disabled });
        }
        
        // Vincular
        if (btnVincular) {
            btnVincular.onclick = async function() {
                if (!selectedClient || !serialInput.value.trim()) {
                    showError('Preencha todos os campos');
                    return;
                }
                
                var reason = document.getElementById('field-reason').value;
                var reasonDesc = document.getElementById('field-reason-desc').value;
                
                if (!reason) {
                    showWarning('Por favor, selecione o motivo da vinculação');
                    return;
                }
                
                if (reason === 'other' && !reasonDesc.trim()) {
                    showWarning('Por favor, descreva o motivo');
                    return;
                }
                
                console.log('Vinculando equipamento...');
                btnVincular.disabled = true;
                btnVincular.innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span> Vinculando...';
                
                try {
                    var payload = {
                        cpf: selectedClient.cpf,
                        serial: serialInput.value.trim().toUpperCase(),
                        reason: reason,
                        reason_description: reasonDesc
                    };
                    
                    // Verifica se está offline
                    if (!navigator.onLine) {
                        saveOffline('link_equipment', payload);
                        showSuccess('Equipamento salvo offline! Será sincronizado quando reconectar.');
                        setTimeout(function() {
                            window.location.href = 'dashboard.php';
                        }, 2000);
                        return;
                    }
                    
                    var response = await fetch('/api/vincular.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + (token || '')
                        },
                        body: JSON.stringify(payload)
                    });
                    
                    var result = await response.json();
                    console.log('Resultado vincular:', result);
                    
                    if (result.success) {
                        showSuccess('Equipamento vinculado com sucesso!');
                        showPostLinkPanel(result.data);
                    } else {
                        showError('Erro: ' + (result.message || 'Erro desconhecido'));
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    
                    // Salva offline se falhar
                    if (!navigator.onLine) {
                        saveOffline('link_equipment', payload);
                        showSuccess('Salvo offline! Será sincronizado quando reconectar.');
                        setTimeout(function() {
                            window.location.href = 'dashboard.php';
                        }, 2000);
                    } else {
                        showError('Erro ao vincular: ' + error.message);
                    }
                } finally {
                    btnVincular.disabled = false;
                    btnVincular.innerHTML = '<span class="material-symbols-outlined">link</span> Vincular Equipamento';
                    checkButton();
                }
            };
        }
        
        // Formatar CPF
        function formatCPF(cpf) {
            if (!cpf) return '';
            cpf = cpf.toString().replace(/\D/g, '');
            if (cpf.length !== 11) return cpf;
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        
        // Exibe painel de pós-vínculo com aviso de estoque e botões de navegação
        function showPostLinkPanel(data) {
            // Oculta botão fixo de vincular
            var footerBar = document.querySelector('.fixed.bottom-0');
            if (footerBar) footerBar.classList.add('hidden');

            // Monta aviso de estoque (se serial não encontrado no estoque)
            var inventoryWarning = '';
            if (data && data.inventory_found === false) {
                inventoryWarning =
                    '<div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 mb-4">' +
                        '<span class="material-symbols-outlined text-amber-500 flex-shrink-0 mt-0.5">warning</span>' +
                        '<div>' +
                            '<p class="text-amber-800 dark:text-amber-300 font-semibold text-sm">Serial não encontrado no estoque</p>' +
                            '<p class="text-amber-700 dark:text-amber-400 text-xs mt-1 leading-relaxed">O vínculo foi registrado, mas não houve baixa automática no estoque. ' +
                            'Cadastre o equipamento na aba <strong>Estoque</strong> para rastreamento completo.</p>' +
                        '</div>' +
                    '</div>';
            }

            // Botão "Ver Timeline"
            var clientCpf = (data && data.client_cpf) ? data.client_cpf : '';
            var clientName = (data && data.client_name) ? data.client_name : 'Cliente';
            var timelineBtn = clientCpf
                ? '<a href="detalher.php?cpf=' + encodeURIComponent(clientCpf) + '" ' +
                    'class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-gradient-to-r from-purple-600 to-violet-600 text-white font-semibold shadow-lg shadow-purple-500/30 hover:from-purple-500 hover:to-violet-500 transition-all btn-press">' +
                    '<span class="material-symbols-outlined" style="font-variation-settings:\'FILL\' 1">timeline</span>' +
                    'Ver Timeline de ' + clientName.split(' ')[0] +
                  '</a>'
                : '';

            var panel =
                '<div id="post-link-panel" class="glass-premium rounded-2xl p-5 stagger-item animate-stagger-in mt-2">' +
                    '<div class="flex items-center gap-3 mb-5">' +
                        '<div class="size-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/30">' +
                            '<span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:\'FILL\' 1">check_circle</span>' +
                        '</div>' +
                        '<div>' +
                            '<p class="text-gray-900 dark:text-white font-bold">Vinculado com sucesso!</p>' +
                            '<p class="text-gray-500 text-xs">O equipamento foi registrado na timeline do cliente.</p>' +
                        '</div>' +
                    '</div>' +
                    inventoryWarning +
                    '<div class="flex gap-3">' +
                        timelineBtn +
                        '<a href="dashboard.php" ' +
                            'class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all btn-press">' +
                            '<span class="material-symbols-outlined">home</span>' +
                            'Dashboard' +
                        '</a>' +
                    '</div>' +
                '</div>';

            // Injeta o painel no container principal
            var container = document.querySelector('.flex.flex-col.gap-4.p-4');
            if (container) {
                container.insertAdjacentHTML('beforeend', panel);
                // Scrolla até o painel
                document.getElementById('post-link-panel').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        console.log('=== Inicialização concluída ===');
    });

    // ===== SCANNER DE CÓDIGO DE BARRAS =====
    var html5QrCode = null;
    var scannerModal = document.getElementById('scanner-modal');
    var btnScan = document.getElementById('btn-scan');
    
    // Abre o scanner
    if (btnScan) {
        btnScan.onclick = function() {
            openScanner();
        };
    }
    
    function openScanner() {
        scannerModal.classList.remove('hidden');
        startScanner();
    }
    
    function closeScanner() {
        scannerModal.classList.add('hidden');
        stopScanner();
    }
    
    function startScanner() {
        var config = {
            fps: 10,
            qrbox: { width: 280, height: 80 },
            formatsToSupport: [
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.QR_CODE
            ]
        };
        
        html5QrCode = new Html5Qrcode('scanner-reader');
        
        html5QrCode.start(
            { facingMode: 'environment' },
            config,
            function(decodedText, decodedResult) {
                console.log('Código lido:', decodedText);
                
                // Preenche o campo de serial
                var serialInput = document.getElementById('field-serial');
                if (serialInput) {
                    serialInput.value = decodedText.toUpperCase();
                    // Dispara evento de input para atualizar o botão
                    serialInput.dispatchEvent(new Event('input'));
                }
                
                // Feedback de sucesso
                if (typeof showSuccess === 'function') {
                    showSuccess('Código lido: ' + decodedText);
                }
                
                // Fecha o scanner
                closeScanner();
            },
            function(errorMessage) {
                // Ignora erros de leitura contínua
            }
        ).catch(function(err) {
            console.error('Erro ao iniciar scanner:', err);
            if (typeof showError === 'function') {
                showError('Erro ao acessar câmera. Verifique as permissões.');
            }
            closeScanner();
        });
    }
    
    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(function() {
                console.log('Scanner parado');
                html5QrCode.clear();
            }).catch(function(err) {
                console.error('Erro ao parar scanner:', err);
            });
        }
    }

    // Registra acesso à página no log de auditoria
    async function logPageAccess(actionType, actionDescription) {
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
                    action_type: actionType,
                    action_description: actionDescription,
                    entity_type: 'page',
                    entity_id: 'vincular-equipamento.php',
                    entity_name: 'Página de Vincular Equipamento'
                })
            });
        } catch (error) {
            console.error('Erro ao registrar acesso:', error);
        }
    }
</script>
</body></html>
