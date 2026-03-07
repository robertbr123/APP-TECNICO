<!DOCTYPE html>
<html class="light" lang="pt-BR">
<head>
    <title>Suporte Técnico - Ondeline</title>
<?php include 'partials/head.php'; ?>
<style>
.checkbox-checked {
            background-color: #135bec;
            border-color: #135bec;
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        .category-header {
            transition: all 0.2s ease;
        }
        .task-item {
            transition: all 0.2s ease;
        }
        .task-item:hover {
            background-color: rgba(19, 91, 236, 0.05);
        }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <div class="relative flex h-auto min-h-screen w-full flex-col bg-background-light dark:bg-background-dark">
        <!-- Header -->
        <header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
            <div class="flex items-center justify-between px-4 h-16">
                <a href="dashboard.php" class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 btn-press">
                    <span class="material-symbols-outlined">arrow_back_ios</span>
                </a>
                <h2 class="text-gray-900 dark:text-white text-lg font-bold flex-1 text-center">Suporte Técnico</h2>
                <div class="size-10"></div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <div class="flex flex-col gap-4 p-4 pb-32">
            <!-- Seleção de Cliente -->
            <div id="client-selection" class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm">
                <h3 class="text-[#111318] dark:text-white text-base font-semibold mb-3">Selecionar Cliente</h3>
                <div class="relative">
                    <input 
                        type="text" 
                        id="client-search" 
                        class="w-full h-12 px-4 pr-12 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#111318] dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50"
                        placeholder="Buscar por nome ou CPF..."
                        autocomplete="off"
                    />
                    <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                </div>
                <div id="search-results" class="hidden mt-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto"></div>
                
                <!-- Cliente Selecionado -->
                <div id="selected-client" class="hidden mt-4 p-3 bg-primary/10 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div class="flex-1">
                            <p id="selected-name" class="text-[#111318] dark:text-white font-medium"></p>
                            <p id="selected-cpf" class="text-sm text-gray-600 dark:text-gray-400"></p>
                        </div>
                        <button id="btn-change-client" class="text-primary text-sm font-medium">Trocar</button>
                    </div>
                </div>

                <!-- Tipo de Instalação -->
                <div class="mt-4">
                    <label class="text-sm text-gray-600 dark:text-gray-400 block mb-2">Tipo de Instalação</label>
                    <select id="installation-type" class="w-full h-12 px-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#111318] dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="new">Nova Instalação</option>
                        <option value="migration">Migração</option>
                        <option value="repair">Reparo</option>
                        <option value="maintenance">Manutenção</option>
                    </select>
                </div>

                <button id="btn-start-checklist" class="w-full mt-4 h-12 bg-primary text-white rounded-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Iniciar Checklist
                </button>
            </div>

            <!-- Checklist em Andamento -->
            <div id="checklist-active" class="hidden">
                <!-- Info do Cliente e Tipo -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Cliente</p>
                            <p id="active-client-name" class="text-[#111318] dark:text-white font-semibold">-</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tipo</p>
                            <p id="active-installation-type" class="text-primary font-semibold">-</p>
                        </div>
                    </div>
                </div>

                <!-- Progresso -->
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[#111318] dark:text-white font-semibold">Progresso</span>
                        <span id="progress-text" class="text-primary font-bold">0%</span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div id="progress-bar" class="h-full bg-primary progress-bar" style="width: 0%"></div>
                    </div>
                    <p id="progress-detail" class="text-sm text-gray-600 dark:text-gray-400 mt-2">0 de 0 tarefas concluídas</p>
                </div>

                <!-- Tarefas por Categoria -->
                <div id="tasks-container" class="mt-4 space-y-4">
                    <!-- As tarefas serão inseridas aqui dinamicamente -->
                </div>

                <!-- Observações -->
                <div class="mt-4 bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm">
                    <label class="text-sm text-gray-600 dark:text-gray-400 block mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">edit_note</span>
                        Observações
                    </label>
                    <textarea 
                        id="checklist-notes" 
                        class="w-full h-24 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[#111318] dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none"
                        placeholder="Adicione observações sobre a instalação..."></textarea>
                </div>

                <!-- Ações -->
                <div class="mt-6 space-y-3">
                    <button id="btn-complete" class="w-full h-12 bg-green-500 text-white rounded-lg font-semibold disabled:opacity-50">
                        Finalizar Instalação
                    </button>
                    <button id="btn-cancel" class="w-full h-12 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium">
                        Cancelar
                    </button>
                </div>
            </div>

            <!-- Lista de Checklists Anteriores -->
            <div id="checklists-list" class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[#111318] dark:text-white text-base font-semibold">Checklists Recentes</h3>
                    <div id="admin-counts" class="flex gap-2"></div>
                </div>
                
                <!-- Filtros para Admin -->
                <div id="admin-filters" class="hidden mb-4">
                    <div class="flex gap-2 overflow-x-auto pb-2">
                        <button onclick="loadRecentChecklists(false)" class="px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium whitespace-nowrap">
                            Meus Checklists
                        </button>
                        <button onclick="loadRecentChecklists(true)" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium whitespace-nowrap">
                            Todos os Checklists
                        </button>
                    </div>
                    <div class="flex gap-2 mt-2 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-yellow-400 rounded-full"></span> Aguardando Aprovação
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span> Aprovado
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span> Rejeitado
                        </span>
                    </div>
                </div>
                
                <div id="checklists-container" class="space-y-3">
                    <p class="text-gray-500 text-center py-4">Nenhum checklist encontrado</p>
                </div>

                <!-- Paginação -->
                <div id="pagination-controls" class="hidden mt-4 flex items-center justify-between">
                    <button id="btn-prev-page" onclick="changePage(-1)" class="flex items-center gap-1 px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-lg text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="material-symbols-outlined text-base">chevron_left</span>
                        Anterior
                    </button>
                    <span id="pagination-info" class="text-sm text-gray-500 dark:text-gray-400"></span>
                    <button id="btn-next-page" onclick="changePage(1)" class="flex items-center gap-1 px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-lg text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                        Próximo
                        <span class="material-symbols-outlined text-base">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navegação Inferior -->
        <?php $activePage = 'inicio'; include 'partials/bottom-nav.php'; ?>
    </div>

    <!-- Modal de Foto -->
    <div id="photo-modal" class="fixed inset-0 bg-black/90 z-50 hidden flex-col items-center justify-center p-4">
        <div class="absolute top-4 right-4">
            <button id="btn-close-photo" class="text-white p-2">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>
        </div>
        <video id="camera-video" class="max-w-full max-h-[70vh] rounded-lg" autoplay playsinline></video>
        <canvas id="camera-canvas" class="hidden"></canvas>
        <div class="mt-4 flex gap-4">
            <button id="btn-capture" class="size-16 rounded-full bg-white flex items-center justify-center">
                <div class="size-12 rounded-full border-4 border-primary"></div>
            </button>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="fixed top-20 left-1/2 -translate-x-1/2 bg-gray-800 text-white px-6 py-3 rounded-xl shadow-lg z-50 hidden"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="js/api.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/feedback.js"></script>
    <script src="js/components.js"></script>
    <script src="js/app.js"></script>
    <script>
        // Debug: verifica se API está carregada
        console.log('API loaded:', typeof API !== 'undefined');
        console.log('API.getChecklists:', typeof API.getChecklists);
        console.log('API.createChecklist:', typeof API.createChecklist);
        
        // Estado
        let selectedClient = null;
        let currentChecklist = null;
        let currentInstallationType = 'new';
        let tasks = [];
        let currentTaskId = null;
        let currentPage = 1;
        let totalPages = 1;
        let currentShowAll = null;

        // Categorias
        const categories = {
            'pre_installation': 'Pré-instalação',
            'installation': 'Instalação',
            'configuration': 'Configuração',
            'testing': 'Testes',
            'documentation': 'Documentação'
        };

        // Labels dos tipos de instalação
        const installationTypeLabels = {
            'new': 'Nova Instalação',
            'migration': 'Migração',
            'repair': 'Reparo',
            'maintenance': 'Manutenção'
        };

        // Mapeamento de quais categorias mostrar por tipo
        const categoryFilterByType = {
            'new': ['pre_installation', 'installation', 'configuration', 'testing', 'documentation'],
            'migration': ['pre_installation', 'installation', 'configuration', 'testing', 'documentation'],
            'repair': ['pre_installation', 'installation', 'configuration', 'testing', 'documentation'],
            'maintenance': ['pre_installation', 'configuration', 'testing', 'documentation']
        };

        // Inicialização
        document.addEventListener('DOMContentLoaded', async () => {
            await loadUserData();
            initEventListeners();
            loadRecentChecklists();
            
            // Verifica se veio de cadastro de cliente
            checkUrlParams();
        });
        
        // Verifica parâmetros da URL (redirecionamento do cadastro)
        function checkUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const clientCpf = urlParams.get('client_cpf');
            const clientName = urlParams.get('client_name');
            
            if (clientCpf && clientName) {
                console.log('[Checklist] Redirecionado do cadastro:', clientCpf, clientName);
                selectClient(clientCpf, decodeURIComponent(clientName));
                
                // Limpa os parâmetros da URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function initEventListeners() {
            // Busca de cliente
            const searchInput = document.getElementById('client-search');
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    document.getElementById('search-results').classList.add('hidden');
                    return;
                }

                searchTimeout = setTimeout(() => searchClients(query), 300);
            });

            // Trocar cliente
            document.getElementById('btn-change-client').addEventListener('click', () => {
                selectedClient = null;
                document.getElementById('selected-client').classList.add('hidden');
                document.getElementById('client-search').disabled = false;
                document.getElementById('client-search').value = '';
                document.getElementById('client-search').focus();
                updateStartButton();
            });

            // Iniciar checklist
            document.getElementById('btn-start-checklist').addEventListener('click', startChecklist);

            // Finalizar instalação
            document.getElementById('btn-complete').addEventListener('click', completeChecklist);

            // Cancelar
            document.getElementById('btn-cancel').addEventListener('click', async () => {
                const confirmed = await AppComponents.showConfirmModal({
                    title: 'Cancelar Checklist',
                    message: 'Tem certeza que deseja cancelar? O progresso sera perdido.',
                    confirmText: 'Cancelar',
                    icon: 'cancel',
                    type: 'warning'
                });
                if (confirmed) location.reload();
            });

            // Fechar modal de foto
            document.getElementById('btn-close-photo').addEventListener('click', closePhotoModal);
            document.getElementById('btn-capture').addEventListener('click', capturePhoto);
        }

        // Busca clientes
        async function searchClients(query) {
            try {
                const response = await API.getClients({ search: query, limit: 10 });
                if (response.success) {
                    renderSearchResults(response.data);
                }
            } catch (error) {
                console.error('Erro ao buscar clientes:', error);
            }
        }

        // Renderiza resultados da busca
        function renderSearchResults(clients) {
            const container = document.getElementById('search-results');
            
            if (clients.length === 0) {
                container.innerHTML = '<div class="p-3 text-gray-500 text-center">Nenhum cliente encontrado</div>';
            } else {
                container.innerHTML = clients.map(client => `
                    <div class="client-result p-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0" 
                         data-cpf="${client.cpf}" 
                         data-name="${client.name}">
                        <p class="text-[#111318] dark:text-white font-medium">${client.name}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">CPF: ${formatCPF(client.cpf)}</p>
                    </div>
                `).join('');

                container.querySelectorAll('.client-result').forEach(el => {
                    el.addEventListener('click', () => selectClient(el.dataset.cpf, el.dataset.name));
                });
            }
            
            container.classList.remove('hidden');
        }

        // Seleciona cliente
        function selectClient(cpf, name) {
            selectedClient = { cpf, name };
            document.getElementById('selected-name').textContent = name;
            document.getElementById('selected-cpf').textContent = formatCPF(cpf);
            document.getElementById('selected-client').classList.remove('hidden');
            document.getElementById('search-results').classList.add('hidden');
            document.getElementById('client-search').disabled = true;
            updateStartButton();
        }

        // Atualiza botão de iniciar
        function updateStartButton() {
            document.getElementById('btn-start-checklist').disabled = !selectedClient;
        }

        // Função para atualizar localização do cliente (direto, sem depender de API.updateClientLocation)
        async function updateClientLocationDirect(cpf, locationData) {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('/api/clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + (token || '')
                },
                body: JSON.stringify({
                    action: 'update_location',
                    cpf: cpf,
                    latitude: locationData.latitude,
                    longitude: locationData.longitude,
                    accuracy: locationData.accuracy
                })
            });
            return await response.json();
        }

        // Captura localização do técnico
        function getTechnicianLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    resolve(null);
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        });
                    },
                    (error) => {
                        console.warn('[Checklist] Erro ao obter localização:', error.message);
                        resolve(null);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
        }
        
        // Dados do cliente selecionado (incluindo lat/long)
        let selectedClientData = null;

        // Inicia checklist
        async function startChecklist() {
            if (!selectedClient) return;
            
            // Verifica se API está disponível
            if (!checkAPI()) {
                showToast('Carregando API... Tente novamente em instantes', 'warning');
                return;
            }

            console.log('[Checklist] Iniciando checklist para cliente:', selectedClient.cpf);
            
            // Mostra loading
            const btnStart = document.getElementById('btn-start-checklist');
            const originalText = btnStart.textContent;
            btnStart.disabled = true;
            btnStart.textContent = 'Obtendo localização...';
            
            try {
                // 1. Captura localização do técnico
                showToast('Obtendo sua localização...', 'info');
                const techLocation = await getTechnicianLocation();
                console.log('[Checklist] Localização do técnico:', techLocation);
                
                if (!techLocation) {
                    console.warn('[Checklist] Não foi possível obter localização do técnico');
                    showToast('Não foi possível obter sua localização. Verifique as permissões.', 'warning');
                }
                
                // 2. Busca dados completos do cliente para verificar se tem lat/long
                let clientData = null;
                try {
                    const clientResponse = await API.getClient(selectedClient.cpf);
                    console.log('[Checklist] Resposta API.getClient:', clientResponse);
                    if (clientResponse.success) {
                        clientData = clientResponse.data;
                        selectedClientData = clientData;
                        console.log('[Checklist] Dados do cliente:', clientData);
                        console.log('[Checklist] Cliente latitude:', clientData?.latitude);
                        console.log('[Checklist] Cliente longitude:', clientData?.longitude);
                    }
                } catch (e) {
                    console.error('[Checklist] Erro ao buscar dados do cliente:', e);
                }
                
                // 3. Verifica se cliente tem localização
                const clientLat = clientData?.latitude;
                const clientLng = clientData?.longitude;
                const clientHasLocation = clientLat && clientLng && 
                    clientLat !== '0' && clientLng !== '0' && 
                    clientLat !== 0 && clientLng !== 0 &&
                    clientLat !== null && clientLng !== null &&
                    clientLat !== '' && clientLng !== '';
                
                console.log('[Checklist] clientHasLocation:', clientHasLocation);
                console.log('[Checklist] techLocation disponível:', !!techLocation);
                
                let saveClientLocation = false;
                
                // 4. Se cliente não tem localização e técnico tem, pergunta se quer salvar
                if (!clientHasLocation && techLocation) {
                    console.log('[Checklist] Mostrando pergunta para salvar localização');
                    const confirmSave = await AppComponents.showConfirmModal({
                        title: 'Salvar Localizacao',
                        message: 'O cliente "' + selectedClient.name + '" nao possui localizacao cadastrada. Voce esta na casa do cliente? Se sim, salvaremos esta localizacao no cadastro.',
                        confirmText: 'Salvar',
                        cancelText: 'Nao salvar',
                        icon: 'location_on',
                        type: 'info'
                    });

                    if (confirmSave) {
                        saveClientLocation = true;
                        showToast('Salvando localização do cliente...', 'info');
                        
                        // Salva localização no cliente
                        try {
                            console.log('[Checklist] Enviando localização para API:', {
                                cpf: selectedClient.cpf,
                                latitude: techLocation.latitude,
                                longitude: techLocation.longitude,
                                accuracy: techLocation.accuracy
                            });
                            
                            const updateResponse = await updateClientLocationDirect(selectedClient.cpf, {
                                latitude: techLocation.latitude,
                                longitude: techLocation.longitude,
                                accuracy: techLocation.accuracy
                            });
                            
                            console.log('[Checklist] Resposta da API:', updateResponse);
                            
                            if (updateResponse.success) {
                                showToast('✓ Localização do cliente salva!', 'success');
                            } else {
                                console.error('[Checklist] Erro ao salvar localização:', updateResponse.message);
                                showToast('Erro: ' + updateResponse.message, 'error');
                            }
                        } catch (e) {
                            console.error('[Checklist] Erro ao salvar localização do cliente:', e);
                            showToast('Erro ao salvar localização: ' + e.message, 'error');
                        }
                    }
                } else {
                    console.log('[Checklist] Motivo para não perguntar:', {
                        clientHasLocation,
                        techLocationNull: !techLocation
                    });
                }
                
                btnStart.textContent = 'Criando checklist...';
                
                currentInstallationType = document.getElementById('installation-type').value;
                console.log('[Checklist] Tipo de instalação:', currentInstallationType);
                
                // 5. Cria checklist com localização do técnico
                const checklistData = {
                    client_cpf: selectedClient.cpf,
                    installation_type: currentInstallationType
                };
                
                // Adiciona localização do técnico se disponível
                if (techLocation) {
                    checklistData.tech_latitude = techLocation.latitude;
                    checklistData.tech_longitude = techLocation.longitude;
                    checklistData.tech_accuracy = techLocation.accuracy;
                }
                
                const response = await API.createChecklist(checklistData);
                
                console.log('[Checklist] Resposta createChecklist:', response);

                if (response.success) {
                    currentChecklist = response.data.id;
                    console.log('[Checklist] Checklist criado com ID:', currentChecklist);
                    btnStart.textContent = originalText;
                    btnStart.disabled = false;
                    showChecklist(selectedClient.name, currentInstallationType);
                    await loadTasks();
                } else {
                    console.error('[Checklist] Erro ao criar:', response.message);
                    showToast(response.message, 'error');
                    btnStart.textContent = originalText;
                    btnStart.disabled = false;
                    
                    // Se as tabelas não existirem
                    if (response.message && (response.message.includes('tabela') || response.message.includes('table') || response.message.includes('não configuradas'))) {
                        showSetupButton();
                    }
                }
            } catch (error) {
                console.error('[Checklist] Erro ao criar checklist:', error);
                showToast('Erro ao iniciar checklist: ' + error.message, 'error');
                btnStart.textContent = originalText;
                btnStart.disabled = false;
            }
        }

        // Mostra tela de checklist
        function showChecklist(clientName = null, installType = null) {
            document.getElementById('client-selection').classList.add('hidden');
            document.getElementById('checklists-list').classList.add('hidden');
            document.getElementById('checklist-active').classList.remove('hidden');
            
            // Atualiza info do cliente
            if (clientName) {
                document.getElementById('active-client-name').textContent = clientName;
            }
            if (installType) {
                document.getElementById('active-installation-type').textContent = installationTypeLabels[installType] || installType;
            }
        }

        // Carrega tarefas
        async function loadTasks() {
            try {
                console.log('[Checklist] Carregando tarefas para checklist:', currentChecklist);
                const response = await API.getChecklist(currentChecklist);
                console.log('[Checklist] Response getChecklist:', response);
                
                if (response.success && response.data) {
                    tasks = response.data.items || [];
                    console.log('[Checklist] Tasks carregadas:', tasks.length, 'itens');
                    console.log('[Checklist] Primeira task:', tasks[0]);
                    
                    // Verifica se os IDs são válidos
                    const invalidTasks = tasks.filter(t => !t.id);
                    if (invalidTasks.length > 0) {
                        console.error('[Checklist] Tasks sem ID:', invalidTasks);
                    }
                    
                    console.log('[Checklist] Itens obrigatórios:', tasks.filter(t => t.is_required).length);
                    console.log('[Checklist] Itens concluídos:', tasks.filter(t => t.is_completed).length);
                    
                    renderTasks();
                    updateProgress();
                } else {
                    console.error('[Checklist] Erro na resposta:', response.message);
                    showToast('Erro ao carregar tarefas: ' + (response.message || 'Resposta inválida'), 'error');
                }
            } catch (error) {
                console.error('[Checklist] Erro ao carregar tarefas:', error);
                showToast('Erro ao carregar tarefas: ' + error.message, 'error');
            }
        }

        // Renderiza tarefas
        function renderTasks() {
            const container = document.getElementById('tasks-container');
            
            if (!tasks || tasks.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-4">Nenhuma tarefa encontrada</p>';
                return;
            }

            // Agrupa por categoria
            const grouped = {};
            tasks.forEach(task => {
                if (!grouped[task.task_category]) {
                    grouped[task.task_category] = [];
                }
                grouped[task.task_category].push(task);
            });

            // Filtra categorias baseado no tipo de instalação
            const allowedCategories = categoryFilterByType[currentInstallationType] || Object.keys(categories);
            
            // Define a primeira categoria como expandida, o resto colapsado
            let isFirstCategory = true;

            container.innerHTML = Object.entries(categories).map(([key, label]) => {
                // Só mostra categorias permitidas para este tipo
                if (!allowedCategories.includes(key)) return '';
                
                const categoryTasks = grouped[key] || [];
                if (categoryTasks.length === 0) return '';

                const completedCount = categoryTasks.filter(t => t.is_completed).length;
                // Apenas a primeira categoria começa expandida
                const expanded = isFirstCategory;
                isFirstCategory = false;

                return `
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm overflow-hidden">
                        <div class="category-header p-4 flex items-center justify-between cursor-pointer"
                             onclick="toggleCategory('${key}')">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary">
                                    ${getCategoryIcon(key)}
                                </span>
                                <div>
                                    <h4 class="text-[#111318] dark:text-white font-semibold">${label}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        ${completedCount}/${categoryTasks.length} concluídas
                                    </p>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-gray-400 category-arrow" id="arrow-${key}">
                                ${expanded ? 'expand_less' : 'expand_more'}
                            </span>
                        </div>
                        <div class="category-tasks ${expanded ? '' : 'hidden'}" id="tasks-${key}">
                            ${categoryTasks.map(task => {
                                const taskId = parseInt(task.id);
                                const taskName = String(task.task_name || '').replace(/"/g, '&quot;');
                                const isCompleted = task.is_completed;
                                return `
                                <div class="task-item p-4 border-t border-gray-100 dark:border-gray-800 flex items-start gap-3" data-task-id="${taskId}">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <input type="checkbox"
                                               id="task-${taskId}"
                                               class="w-6 h-6 rounded border-2 border-gray-300 dark:border-gray-600 cursor-pointer appearance-none checked:bg-primary checked:border-primary checked:bg-[url('data:image/svg+xml,%3csvg viewBox=%220 0 16 16%22 fill=%22white%22 xmlns=%22http://www.w3.org/2000/svg%22%3e%3cpath d=%22M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z%22/%3e%3c/svg%3e')]"
                                               ${isCompleted ? 'checked' : ''}
                                               onchange="toggleTask(${taskId}, this.checked)">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[#111318] dark:text-white ${isCompleted ? 'line-through text-gray-500' : ''}">
                                            ${taskName}
                                        </p>
                                        ${task.notes ? `<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">${task.notes}</p>` : ''}
                                    </div>
                                    <button onclick="openPhotoModal(${taskId})"
                                            class="flex-shrink-0 p-2 text-gray-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined">photo_camera</span>
                                    </button>
                                </div>
                            `}).join('')}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Toggle categoria
        function toggleCategory(key) {
            const tasksEl = document.getElementById(`tasks-${key}`);
            const arrowEl = document.getElementById(`arrow-${key}`);
            tasksEl.classList.toggle('hidden');
            arrowEl.textContent = tasksEl.classList.contains('hidden') ? 'expand_more' : 'expand_less';
        }

        // Get ícone da categoria
        function getCategoryIcon(category) {
            const icons = {
                'pre_installation': 'inventory_2',
                'installation': 'build',
                'configuration': 'settings',
                'testing': 'speed',
                'documentation': 'description'
            };
            return icons[category] || 'check_circle';
        }

        // Toggle tarefa (atualização otimista)
        async function toggleTask(taskId, completed) {
            // Converte taskId para número inteiro
            taskId = parseInt(taskId);
            console.log('[Checklist] Toggle task:', taskId, 'completed:', completed);
            
            if (!taskId || isNaN(taskId)) {
                console.error('[Checklist] ID da tarefa inválido:', taskId);
                showToast('Erro: ID da tarefa inválido', 'error');
                return;
            }
            
            // Atualiza local primeiro (resposta imediata)
            const task = tasks.find(t => parseInt(t.id) === taskId);
            if (task) {
                task.is_completed = completed;
            } else {
                console.error('[Checklist] Tarefa não encontrada:', taskId);
                showToast('Erro: Tarefa não encontrada', 'error');
                return;
            }
            renderTasks();
            updateProgress();

            // Sincroniza com servidor
            try {
                let response;
                if (completed) {
                    console.log('[Checklist] Chamando API.completeChecklistItem:', taskId);
                    response = await API.completeChecklistItem(taskId);
                } else {
                    console.log('[Checklist] Chamando API.uncheckChecklistItem:', taskId);
                    response = await API.uncheckChecklistItem(taskId);
                }
                
                console.log('[Checklist] Resposta da API:', response);
                
                if (!response || !response.success) {
                    throw new Error(response?.message || 'Erro desconhecido');
                }
                
                showToast(completed ? 'Item marcado!' : 'Item desmarcado!', 'success');
            } catch (error) {
                console.error('[Checklist] Erro ao atualizar tarefa:', error);
                // Reverte em caso de erro
                if (task) {
                    task.is_completed = !completed;
                }
                renderTasks();
                updateProgress();
                showToast('Erro: ' + (error.message || 'Falha ao atualizar'), 'error');
            }
        }
        
        // Marca item como N/A (Não Aplicável)
        async function markAsNA(taskId, event) {
            event.stopPropagation();
            
            const naConfirmed = await AppComponents.showConfirmModal({
                title: 'Nao Aplicavel',
                message: 'Marcar este item como "Nao Aplicavel"? Esta acao nao pode ser desfeita.',
                confirmText: 'Marcar N/A',
                icon: 'do_not_disturb',
                type: 'warning'
            });
            if (!naConfirmed) return;
            
            try {
                await API.markChecklistItemNA(taskId);
                
                // Atualiza local
                const task = tasks.find(t => t.id === taskId);
                if (task) {
                    task.is_completed = true;
                    task.notes = '[N/A - Não Aplicável]';
                }
                
                renderTasks();
                updateProgress();
                showToast('Item marcado como N/A', 'success');
            } catch (error) {
                console.error('Erro ao marcar N/A:', error);
                showToast('Erro: ' + error.message, 'error');
            }
        }

        // Atualiza progresso
        function updateProgress() {
            const total = tasks.length;
            const completed = tasks.filter(t => t.is_completed).length;
            const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

            document.getElementById('progress-bar').style.width = `${percent}%`;
            document.getElementById('progress-text').textContent = `${percent}%`;
            document.getElementById('progress-detail').textContent = `${completed} de ${total} tarefas concluídas`;

            // Botão de finalizar sempre verde e habilitado
            const btnComplete = document.getElementById('btn-complete');
            btnComplete.disabled = false;
            btnComplete.textContent = 'Finalizar Instalação';
            btnComplete.classList.remove('bg-yellow-500');
            btnComplete.classList.add('bg-green-500');
        }

        // Abre modal de foto
        function openPhotoModal(taskId) {
            currentTaskId = taskId;
            document.getElementById('photo-modal').classList.remove('hidden');
            document.getElementById('photo-modal').classList.add('flex');
            initCamera();
        }

        // Fecha modal de foto
        function closePhotoModal() {
            document.getElementById('photo-modal').classList.add('hidden');
            document.getElementById('photo-modal').classList.remove('flex');
            stopCamera();
        }

        // Inicializa câmera
        let stream = null;
        async function initCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                document.getElementById('camera-video').srcObject = stream;
            } catch (error) {
                console.error('Erro ao acessar câmera:', error);
                showToast('Erro ao acessar câmera', 'error');
            }
        }

        // Para câmera
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }

        // Captura foto e faz upload COM MARCA D'ÁGUA
        async function capturePhoto() {
            if (!currentTaskId || !selectedClient) {
                showToast('Erro: Nenhuma tarefa selecionada', 'error');
                return;
            }
            
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            // === MARCA D'ÁGUA ===
            const width = canvas.width;
            const height = canvas.height;
            const padding = 12;
            const lineHeight = 18;
            const lines = [];
            
            // Data e hora
            const now = new Date();
            const dateStr = now.toLocaleDateString('pt-BR');
            const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            lines.push('📅 ' + dateStr + ' ' + timeStr);
            
            // Localização GPS (obtém no momento da captura)
            try {
                if (navigator.geolocation) {
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 5000,
                            maximumAge: 60000
                        });
                    });
                    const lat = position.coords.latitude.toFixed(6);
                    const lon = position.coords.longitude.toFixed(6);
                    const acc = position.coords.accuracy ? ' (±' + Math.round(position.coords.accuracy) + 'm)' : '';
                    lines.push('📍 ' + lat + ', ' + lon + acc);
                }
            } catch (geoErr) {
                console.warn('Não foi possível obter GPS para marca d\'água:', geoErr.message);
            }
            
            // Nome do técnico
            try {
                const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
                const tech = userData.full_name || userData.name || userData.username || 'Técnico';
                lines.push('👤 ' + tech);
            } catch (e) {}
            
            if (lines.length > 0) {
                const blockHeight = (lines.length * lineHeight) + (padding * 2);
                
                // Fundo semi-transparente no rodapé
                ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
                ctx.fillRect(0, height - blockHeight, width, blockHeight);
                
                // Texto branco
                ctx.fillStyle = '#FFFFFF';
                ctx.font = '500 14px Inter, -apple-system, BlinkMacSystemFont, sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'top';
                
                lines.forEach((line, index) => {
                    const y = height - blockHeight + padding + (index * lineHeight);
                    ctx.fillText(line, padding, y);
                });
                
                // Logo Ondeline Tech
                ctx.font = '600 11px Inter, -apple-system, BlinkMacSystemFont, sans-serif';
                ctx.textAlign = 'right';
                ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                ctx.fillText('Ondeline Tech', width - padding, height - padding - 4);
            }
            
            const base64 = canvas.toDataURL('image/jpeg', 0.8);
            
            try {
                showToast('Enviando foto...', 'info');
                
                // Upload da foto
                const uploadResponse = await API.uploadPhoto(selectedClient.cpf, base64, 'checklist');
                console.log('[Checklist] Upload response:', uploadResponse);
                
                if (!uploadResponse.success) {
                    throw new Error(uploadResponse.message || 'Erro no upload');
                }
                
                // Atualiza o item do checklist com a URL da foto
                const photoUrl = uploadResponse.data.url;
                await API.completeChecklistItem(currentTaskId, 'Foto anexada', photoUrl);
                
                // Atualiza localmente
                const task = tasks.find(t => parseInt(t.id) === parseInt(currentTaskId));
                if (task) {
                    task.is_completed = true;
                    task.photo_url = photoUrl;
                    task.notes = 'Foto anexada';
                }
                
                renderTasks();
                updateProgress();
                closePhotoModal();
                showToast('✓ Foto anexada com sucesso!', 'success');
            } catch (error) {
                console.error('[Checklist] Erro ao salvar foto:', error);
                showToast('Erro: ' + (error.message || 'Falha ao salvar foto'), 'error');
            }
        }

        // Finaliza checklist
        async function completeChecklist() {
            console.log('[Checklist] Finalizando checklist:', currentChecklist);
            
            if (!currentChecklist) {
                showToast('Erro: Nenhum checklist ativo', 'error');
                return;
            }
            
            // Confirmação final
            const finishConfirmed = await AppComponents.showConfirmModal({
                title: 'Finalizar Instalacao',
                message: 'Tem certeza que deseja finalizar esta instalacao? Verifique se todos os itens foram concluidos corretamente.',
                confirmText: 'Finalizar',
                icon: 'check_circle',
                type: 'info'
            });
            if (!finishConfirmed) return;
            
            // Mostra loading
            const btnComplete = document.getElementById('btn-complete');
            const originalText = btnComplete.textContent;
            btnComplete.disabled = true;
            btnComplete.textContent = 'Finalizando...';
            
            try {
                console.log('[Checklist] Chamando API.completeChecklist:', currentChecklist);
                const response = await API.completeChecklist(currentChecklist);
                console.log('[Checklist] Resposta:', response);
                
                if (response.success) {
                    showToast('✓ Checklist enviado para aprovação!', 'success');
                    setTimeout(() => {
                        alert('Seu checklist foi enviado para aprovação do administrador.\n\nVocê receberá uma notificação quando for aprovado ou caso precise de correções.');
                        location.reload();
                    }, 500);
                } else {
                    btnComplete.disabled = false;
                    btnComplete.textContent = originalText;
                    showToast(response.message || 'Erro ao finalizar', 'error');
                }
            } catch (error) {
                console.error('[Checklist] Erro ao finalizar:', error);
                btnComplete.disabled = false;
                btnComplete.textContent = originalText;
                showToast('Erro: ' + (error.message || 'Falha ao finalizar'), 'error');
            }
        }
        
        // Mostra modal com itens pendentes
        function showPendingItemsModal(pendingItems) {
            // Cria modal dinamicamente se não existir
            let modal = document.getElementById('pending-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'pending-modal';
                modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
                document.body.appendChild(modal);
            }
            
            const itemsList = pendingItems.map(item => `
                <li class="py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-red-500">error</span>
                        <div>
                            <p class="font-medium text-[#111318] dark:text-white">${item.task_name}</p>
                            <p class="text-sm text-gray-500">${categories[item.task_category] || item.task_category}</p>
                        </div>
                    </div>
                </li>
            `).join('');
            
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-md w-full max-h-[80vh] overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-[#111318] dark:text-white">
                            ⚠️ Itens Obrigatórios Pendentes
                        </h3>
                        <button onclick="closePendingModal()" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto max-h-[50vh]">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Você precisa concluir os seguintes <strong>${pendingItems.length} item(s)</strong> antes de finalizar:
                        </p>
                        <ul class="space-y-1">
                            ${itemsList}
                        </ul>
                    </div>
                    <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex gap-3">
                        <button onclick="closePendingModal()" class="flex-1 py-2 px-4 bg-primary text-white rounded-lg font-medium">
                            Ver Itens
                        </button>
                    </div>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // Fecha modal de pendentes
        function closePendingModal() {
            const modal = document.getElementById('pending-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
        
        // Mostra botão de configuração se tabelas não existirem
        function showSetupButton() {
            const container = document.getElementById('checklists-container');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-8 bg-white dark:bg-gray-900 rounded-xl p-4">
                        <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">build</span>
                        <p class="text-gray-500 mb-4">Tabelas de checklist não configuradas</p>
                        <button onclick="setupChecklistTables()" class="px-4 py-2 bg-primary text-white rounded-lg">
                            Configurar Checklist
                        </button>
                    </div>
                `;
            }
        }
        
        // Configura checklist - exposta globalmente
        window.setupChecklistTables = async function() {
            try {
                const response = await fetch('/api/setup-checklist.php');
                const result = await response.json();
                
                if (result.success) {
                    showToast('Checklist configurado! ' + result.templates_count + ' templates criados', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Erro: ' + result.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao configurar: ' + error.message, 'error');
            }
        }

        // Verifica se API está disponível
        function checkAPI() {
            if (typeof API === 'undefined') {
                console.error('API não carregada');
                return false;
            }
            if (typeof API.getChecklists !== 'function') {
                console.error('API.getChecklists não disponível');
                return false;
            }
            return true;
        }

        // Estado do usuário
        let currentUser = null;
        let isAdmin = false;

        // Carrega dados do usuário
        async function loadUserData() {
            try {
                const user = API.getUser();
                currentUser = user;
                isAdmin = user?.role === 'admin';
                console.log('User loaded:', user?.username, 'Admin:', isAdmin);
                
                // Atualiza interface baseado no role
                if (isAdmin) {
                    document.getElementById('admin-filters')?.classList.remove('hidden');
                    // Mostra botão de estoque na navegação
                    const navEstoque = document.getElementById('nav-estoque');
                    if (navEstoque) {
                        navEstoque.classList.remove('hidden');
                        navEstoque.classList.add('flex');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar usuário:', error);
            }
        }

        // Carrega checklists recentes
        async function loadRecentChecklists(showAll = null, page = 1) {
            // Aguarda API estar disponível
            if (!checkAPI()) {
                console.log('Aguardando API...');
                setTimeout(() => loadRecentChecklists(showAll, page), 500);
                return;
            }

            // Admin vê todos por padrão
            if (showAll === null) showAll = currentShowAll !== null ? currentShowAll : isAdmin;

            // Reset page quando muda filtro
            if (showAll !== currentShowAll) {
                page = 1;
            }
            currentShowAll = showAll;
            currentPage = page;

            // Atualiza visual dos botões de filtro
            const filterBtns = document.querySelectorAll('#admin-filters .flex.gap-2:first-child button');
            filterBtns.forEach((btn, i) => {
                if ((i === 0 && !showAll) || (i === 1 && showAll)) {
                    btn.className = 'px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium whitespace-nowrap';
                } else {
                    btn.className = 'px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium whitespace-nowrap';
                }
            });

            try {
                const params = { limit: 10, page: page };
                if (isAdmin && showAll) {
                    params.mine = 'false';
                }

                const response = await API.getChecklists(params);
                if (response.success) {
                    if (response.is_admin !== undefined) isAdmin = response.is_admin;
                    if (response.data.length > 0) {
                        renderChecklistsList(response.data, response.counts);
                    } else {
                        document.getElementById('checklists-container').innerHTML =
                            '<p class="text-gray-500 text-center py-4">Nenhum checklist encontrado</p>';
                    }

                    // Atualiza paginação
                    if (response.pagination) {
                        totalPages = response.pagination.pages || 1;
                        updatePagination(currentPage, totalPages, response.pagination.total);
                    } else {
                        document.getElementById('pagination-controls').classList.add('hidden');
                    }

                    // Mostra contadores para admin
                    if (isAdmin && response.counts) {
                        renderAdminCounts(response.counts);
                    }
                } else if (response.message &&
                          (response.message.includes('tabela') || response.message.includes('não configuradas'))) {
                    showSetupButton();
                }
            } catch (error) {
                console.error('Erro ao carregar checklists:', error);
            }
        }

        function changePage(delta) {
            const newPage = currentPage + delta;
            if (newPage >= 1 && newPage <= totalPages) {
                loadRecentChecklists(currentShowAll, newPage);
                // Scroll para a seção de checklists
                document.getElementById('checklists-list').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function updatePagination(page, pages, total) {
            const controls = document.getElementById('pagination-controls');
            if (pages <= 1) {
                controls.classList.add('hidden');
                return;
            }
            controls.classList.remove('hidden');

            document.getElementById('pagination-info').textContent = `${page} de ${pages}`;
            document.getElementById('btn-prev-page').disabled = page <= 1;
            document.getElementById('btn-next-page').disabled = page >= pages;
        }
        
        // Renderiza contadores para admin
        function renderAdminCounts(counts) {
            let html = '';
            if (counts.pending_approval > 0) {
                html += `<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">${counts.pending_approval} aguardando aprovação</span>`;
            }
            if (html) {
                document.getElementById('admin-counts').innerHTML = html;
            }
        }

        // Renderiza lista de checklists
        function renderChecklistsList(checklists, counts = null) {
            const container = document.getElementById('checklists-container');
            
            container.innerHTML = checklists.map(checklist => {
                // Status de progresso
                const statusColors = {
                    'pending': 'bg-gray-100 text-gray-700',
                    'in_progress': 'bg-blue-100 text-blue-700',
                    'completed': 'bg-green-100 text-green-700',
                    'cancelled': 'bg-red-100 text-red-700'
                };
                
                const statusLabels = {
                    'pending': 'Pendente',
                    'in_progress': 'Em andamento',
                    'completed': 'Concluído',
                    'cancelled': 'Cancelado'
                };
                
                // Status de aprovação
                const approvalColors = {
                    'pending': '',
                    'pending_approval': 'bg-yellow-100 text-yellow-700 border border-yellow-300',
                    'approved': 'bg-green-100 text-green-700 border border-green-300',
                    'rejected': 'bg-red-100 text-red-700 border border-red-300'
                };
                
                const approvalLabels = {
                    'pending': '',
                    'pending_approval': '⚠️ Aguardando Aprovação',
                    'approved': '✓ Aprovado',
                    'rejected': '✕ Rejeitado'
                };
                
                const showApproval = checklist.approval_status && checklist.approval_status !== 'pending';
                const isPendingApproval = checklist.approval_status === 'pending_approval' ||
                    (checklist.status === 'completed' && checklist.approval_status !== 'approved' && checklist.approval_status !== 'rejected');
                const isRejected = checklist.approval_status === 'rejected';
                
                // Verifica se checklist pode ser continuado (pendente ou em andamento)
                const canContinue = (checklist.status === 'pending' || checklist.status === 'in_progress') && 
                                   parseInt(checklist.technician_id) === parseInt(currentUser?.id);
                
                // Debug para verificar condições
                console.log('[Checklist] canContinue check:', {
                    status: checklist.status,
                    technician_id: checklist.technician_id,
                    currentUser_id: currentUser?.id,
                    canContinue: canContinue
                });
                
                // Calcula prazo restante (3 dias a partir de created_at)
                let deadlineInfo = '';
                let isExpired = false;
                if (canContinue) {
                    const createdDate = new Date(checklist.created_at);
                    const deadlineDate = new Date(createdDate.getTime() + (3 * 24 * 60 * 60 * 1000)); // +3 dias
                    const now = new Date();
                    const diffMs = deadlineDate - now;
                    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                    const diffDays = Math.floor(diffHours / 24);
                    const remainingHours = diffHours % 24;
                    
                    if (diffMs <= 0) {
                        isExpired = true;
                        deadlineInfo = `
                            <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400 font-medium">
                                    ⚠️ Prazo expirado! Este checklist não pode mais ser editado.
                                </p>
                            </div>
                        `;
                    } else if (diffHours <= 24) {
                        deadlineInfo = `
                            <div class="mt-2 p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <p class="text-xs text-orange-600 dark:text-orange-400 font-medium">
                                    ⏰ Prazo: ${remainingHours}h restantes para finalizar
                                </p>
                            </div>
                        `;
                    } else {
                        deadlineInfo = `
                            <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                    ⏰ Prazo: ${diffDays} dia(s) e ${remainingHours}h para finalizar
                                </p>
                            </div>
                        `;
                    }
                }

                // Botões de ação para admin
                let adminActions = '';
                if (isAdmin && isPendingApproval) {
                    adminActions = `
                        <div class="flex gap-2 mt-2">
                            <button onclick="event.stopPropagation(); viewChecklistReport(${checklist.id})"
                                    class="flex-1 py-2 px-3 bg-blue-500 text-white rounded-lg text-sm font-bold shadow-sm">
                                📄 Ver Relatório
                            </button>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button onclick="event.stopPropagation(); approveChecklist(${checklist.id})"
                                    class="flex-1 py-2 px-3 bg-green-500 text-white rounded-lg text-sm font-bold shadow-sm">
                                ✓ Aprovar
                            </button>
                            <button onclick="event.stopPropagation(); showRejectModal(${checklist.id})"
                                    class="flex-1 py-2 px-3 bg-red-500 text-white rounded-lg text-sm font-bold shadow-sm">
                                ✕ Rejeitar
                            </button>
                        </div>
                        <div class="flex gap-2 mt-1">
                            <button onclick="event.stopPropagation(); deleteChecklist(${checklist.id})"
                                    class="py-1 px-3 text-red-600 hover:bg-red-50 rounded-lg text-xs">
                                Excluir
                            </button>
                        </div>
                    `;
                } else if (isAdmin) {
                    adminActions = `
                        <div class="flex gap-2 mt-2">
                            <button onclick="event.stopPropagation(); viewChecklistReport(${checklist.id})"
                                    class="flex-1 py-2 px-3 bg-blue-500 text-white rounded-lg text-sm font-medium shadow-sm">
                                📄 Ver Relatório
                            </button>
                            <button onclick="event.stopPropagation(); deleteChecklist(${checklist.id})"
                                    class="py-1.5 px-3 text-red-600 hover:bg-red-50 rounded-lg text-sm">
                                Excluir
                            </button>
                        </div>
                    `;
                } else if (isRejected && parseInt(checklist.technician_id) === parseInt(currentUser?.id)) {
                    // Técnico pode reabrir checklist rejeitado
                    adminActions = `
                        <div class="mt-2">
                            <button onclick="reopenChecklist(${checklist.id})" 
                                    class="w-full py-1.5 px-3 bg-primary text-white rounded-lg text-sm font-medium">
                                ↻ Reabrir para Correção
                            </button>
                        </div>
                    `;
                } else if (canContinue && !isExpired) {
                    // Técnico pode continuar checklist pendente/em andamento
                    adminActions = `
                        <div class="mt-2">
                            <button onclick="event.stopPropagation(); continueChecklist(${checklist.id}, '${checklist.client_name.replace(/'/g, "\\'")}', '${checklist.installation_type}')" 
                                    class="w-full py-2 px-3 bg-primary text-white rounded-lg text-sm font-bold shadow-sm">
                                ▶ Continuar Checklist
                            </button>
                        </div>
                    `;
                }
                
                // Mostra nome do técnico para admin
                const techInfo = isAdmin ? `
                    <p class="text-xs text-gray-500">
                        <span class="material-symbols-outlined text-xs align-middle">person</span>
                        ${checklist.technician_name || 'Técnico'}
                    </p>
                ` : '';
                
                // Motivo da rejeição
                const rejectionInfo = isRejected && checklist.rejection_reason ? `
                    <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded text-xs text-red-700 dark:text-red-400">
                        <strong>Motivo:</strong> ${checklist.rejection_reason}
                        ${checklist.rejection_notes ? `<br>${checklist.rejection_notes}` : ''}
                    </div>
                ` : '';

                return `
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg ${isPendingApproval ? 'ring-2 ring-yellow-400' : ''} ${canContinue && !isExpired ? 'ring-2 ring-primary cursor-pointer active:scale-[0.98] transition-transform' : ''}"
                         ${canContinue && !isExpired ? `onclick="continueChecklist(${checklist.id}, '${checklist.client_name.replace(/'/g, "\\'")}', '${checklist.installation_type}')"` : ''}>
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-[#111318] dark:text-white font-medium truncate">${checklist.client_name}</p>
                                ${techInfo}
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    ${new Date(checklist.created_at).toLocaleDateString('pt-BR')}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[checklist.status]}">
                                    ${statusLabels[checklist.status]}
                                </span>
                                ${showApproval ? `
                                    <span class="px-3 py-1 rounded-full text-xs font-medium ${approvalColors[checklist.approval_status]}">
                                        ${approvalLabels[checklist.approval_status]}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        ${deadlineInfo}
                        ${rejectionInfo}
                        ${adminActions}
                    </div>
                `;
            }).join('');
        }
        
        // Aprova checklist (admin)
        async function approveChecklist(id) {
            const approveConfirmed = await AppComponents.showConfirmModal({
                title: 'Aprovar Checklist',
                message: 'Confirmar aprovacao deste checklist?',
                confirmText: 'Aprovar',
                icon: 'check',
                type: 'info'
            });
            if (!approveConfirmed) return;
            
            try {
                const response = await API.approveChecklist(id);
                if (response.success) {
                    showToast('Checklist aprovado!', 'success');
                    loadRecentChecklists(true);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao aprovar', 'error');
            }
        }
        
        // Mostra modal de rejeição
        function showRejectModal(id) {
            const reason = prompt('Motivo da rejeição (obrigatório):');
            if (!reason) return;
            
            const notes = prompt('Observações adicionais (opcional):') || '';
            
            rejectChecklist(id, reason, notes);
        }
        
        // Rejeita checklist (admin)
        async function rejectChecklist(id, reason, notes) {
            try {
                const response = await API.rejectChecklist(id, reason, notes);
                if (response.success) {
                    showToast('Checklist rejeitado', 'success');
                    loadRecentChecklists(true);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao rejeitar', 'error');
            }
        }
        
        // Reabre checklist rejeitado (técnico)
        async function reopenChecklist(id) {
            const reopenConfirmed = await AppComponents.showConfirmModal({
                title: 'Reabrir Checklist',
                message: 'Reabrir este checklist para correcao? Voce precisara corrigir os itens e enviar novamente para aprovacao.',
                confirmText: 'Reabrir',
                icon: 'restart_alt',
                type: 'warning'
            });
            if (!reopenConfirmed) return;
            
            try {
                const response = await API.reopenChecklist(id);
                if (response.success) {
                    showToast('Checklist reaberto!', 'success');
                    location.reload();
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao reabrir', 'error');
            }
        }
        
        // Continua checklist pendente/em andamento
        async function continueChecklist(id, clientName, installationType) {
            try {
                showToast('Carregando checklist...', 'info');
                
                // Verifica se ainda está dentro do prazo (3 dias)
                const response = await API.getChecklist(id);
                
                if (!response.success) {
                    showToast('Erro ao carregar checklist', 'error');
                    return;
                }
                
                const checklist = response.data;
                
                // Verifica prazo
                const createdDate = new Date(checklist.created_at);
                const deadlineDate = new Date(createdDate.getTime() + (3 * 24 * 60 * 60 * 1000));
                const now = new Date();
                
                if (now > deadlineDate) {
                    showToast('Prazo expirado! Este checklist não pode mais ser editado.', 'error');
                    loadRecentChecklists();
                    return;
                }
                
                // Calcula tempo restante
                const diffMs = deadlineDate - now;
                const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                const diffDays = Math.floor(diffHours / 24);
                const remainingHours = diffHours % 24;
                
                let timeMessage = '';
                if (diffHours <= 24) {
                    timeMessage = `⚠️ Atenção: Você tem apenas ${remainingHours}h para finalizar este checklist!`;
                } else {
                    timeMessage = `Você tem ${diffDays} dia(s) e ${remainingHours}h para finalizar este checklist.`;
                }
                
                // Mostra aviso sobre o prazo
                const continueConfirmed = await AppComponents.showConfirmModal({
                    title: 'Continuar Checklist',
                    message: 'Continuar checklist de ' + clientName + '? ' + timeMessage,
                    confirmText: 'Continuar',
                    icon: 'play_arrow',
                    type: 'info'
                });
                if (!continueConfirmed) return;
                
                // Define o checklist atual
                currentChecklist = id;
                currentInstallationType = installationType || checklist.installation_type;
                selectedClient = {
                    cpf: checklist.client_cpf,
                    name: clientName
                };
                
                // Mostra a tela de checklist
                showChecklist(clientName, currentInstallationType);
                
                // Carrega as tarefas
                tasks = checklist.items || [];
                console.log('[Checklist] Continuando checklist:', id, 'com', tasks.length, 'tarefas');
                
                renderTasks();
                updateProgress();
                
                showToast('Checklist carregado! Continue de onde parou.', 'success');
                
            } catch (error) {
                console.error('Erro ao continuar checklist:', error);
                showToast('Erro ao carregar checklist', 'error');
            }
        }
        
        // Deleta checklist (admin)
        async function deleteChecklist(id) {
            const deleteConfirmed = await AppComponents.showConfirmModal({
                title: 'Excluir Checklist',
                message: 'Tem certeza que deseja EXCLUIR este checklist permanentemente? Esta acao nao pode ser desfeita.',
                confirmText: 'Excluir',
                icon: 'delete_forever',
                type: 'danger'
            });
            if (!deleteConfirmed) return;
            
            try {
                const response = await API.deleteChecklist(id);
                if (response.success) {
                    showToast('Checklist excluído', 'success');
                    loadRecentChecklists(true);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Erro ao excluir', 'error');
            }
        }

        // Formata CPF
        function formatCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        // Variável para armazenar dados do relatório atual
        let currentReportData = null;
        let currentClientData = null;

        // Visualiza relatório do checklist (admin)
        async function viewChecklistReport(checklistId) {
            try {
                showToast('Carregando relatório...', 'info');
                const response = await API.getChecklist(checklistId);
                
                if (!response.success) {
                    showToast('Erro ao carregar relatório', 'error');
                    return;
                }
                
                currentReportData = response.data;
                
                // Busca dados adicionais do cliente (pppoe, mac, etc)
                try {
                    const clientResponse = await API.getClient(response.data.checklist.client_cpf);
                    if (clientResponse.success) {
                        currentClientData = clientResponse.data;
                        console.log('[Relatório] Dados do cliente:', currentClientData);
                    }
                } catch (e) {
                    console.error('Erro ao buscar dados do cliente:', e);
                    currentClientData = null;
                }
                
                renderReportModal(currentReportData);
            } catch (error) {
                console.error('Erro ao carregar relatório:', error);
                showToast('Erro ao carregar relatório', 'error');
            }
        }

        // Renderiza modal do relatório
        function renderReportModal(data) {
            const checklist = data.checklist;
            const items = data.items || [];
            const history = data.history || [];
            
            // Remove modal anterior se existir
            let existingModal = document.getElementById('report-modal');
            if (existingModal) existingModal.remove();
            
            // Cria modal
            const modal = document.createElement('div');
            modal.id = 'report-modal';
            modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto';
            
            // Agrupa itens por categoria
            const grouped = {};
            items.forEach(item => {
                if (!grouped[item.task_category]) grouped[item.task_category] = [];
                grouped[item.task_category].push(item);
            });
            
            // Calcula estatísticas
            const totalItems = items.length;
            const completedItems = items.filter(i => i.is_completed).length;
            const completionRate = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;
            
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-900 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
                    <!-- Header -->
                    <div class="bg-primary text-white p-4 flex items-center justify-between sticky top-0">
                        <div>
                            <h2 class="text-xl font-bold">📋 Ordem de Serviço</h2>
                            <p class="text-sm opacity-90">Nº ${checklist.id} - ${new Date(checklist.created_at).toLocaleDateString('pt-BR')}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="generatePDF()" class="px-4 py-2 bg-white text-primary rounded-lg font-semibold text-sm hover:bg-gray-100 transition-colors">
                                ⬇️ Baixar OS
                            </button>
                            <button onclick="closeReportModal()" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div id="report-content" class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                        <!-- Info Geral -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Cliente</p>
                                <p class="text-lg font-semibold text-[#111318] dark:text-white">${checklist.client_name}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">CPF: ${formatCPF(checklist.client_cpf)}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Técnico</p>
                                <p class="text-lg font-semibold text-[#111318] dark:text-white">${checklist.technician_name}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">${installationTypeLabels[checklist.installation_type] || checklist.installation_type}</p>
                            </div>
                        </div>
                        
                        <!-- Progresso -->
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-[#111318] dark:text-white">Progresso</span>
                                <span class="text-primary font-bold">${completionRate}%</span>
                            </div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: ${completionRate}%"></div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">${completedItems} de ${totalItems} itens concluídos</p>
                        </div>
                        
                        ${checklist.tech_latitude && checklist.tech_longitude ? `
                        <!-- Localização Verificada -->
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 rounded-lg mb-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-green-600">location_on</span>
                                <span class="text-sm font-bold text-green-700 dark:text-green-400">LOCALIZAÇÃO VERIFICADA</span>
                            </div>
                            <p class="text-sm text-green-700 dark:text-green-400">
                                Lat: ${parseFloat(checklist.tech_latitude).toFixed(6)} | 
                                Lng: ${parseFloat(checklist.tech_longitude).toFixed(6)}
                                ${checklist.tech_location_accuracy ? ` (±${Math.round(checklist.tech_location_accuracy)}m)` : ''}
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-500 mt-1">
                                Capturada em: ${checklist.location_captured_at 
                                    ? new Date(checklist.location_captured_at).toLocaleString('pt-BR') 
                                    : new Date(checklist.created_at).toLocaleString('pt-BR')}
                            </p>
                            <a href="https://www.google.com/maps?q=${checklist.tech_latitude},${checklist.tech_longitude}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 mt-2 text-sm text-green-700 dark:text-green-400 hover:underline">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                                Ver no Google Maps
                            </a>
                        </div>
                        ` : ''}
                        
                        <!-- Itens Concluídos -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-[#111318] dark:text-white border-b pb-2">
                                ✓ Itens Concluídos (${completedItems})
                            </h3>
                            ${Object.entries(categories).map(([key, label]) => {
                                const catItems = (grouped[key] || []).filter(i => i.is_completed);
                                if (catItems.length === 0) return '';
                                return `
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <div class="bg-green-100 dark:bg-green-900/30 px-4 py-2 font-semibold text-[#111318] dark:text-white">
                                            ${label} (${catItems.length})
                                        </div>
                                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                            ${catItems.map(item => `
                                                <div class="px-4 py-3 flex items-start gap-3 bg-green-50 dark:bg-green-900/20">
                                                    <span class="material-symbols-outlined text-green-500">check_circle</span>
                                                    <div class="flex-1">
                                                        <p class="text-[#111318] dark:text-white font-medium">${item.task_name}</p>
                                                        ${item.notes ? `<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">📝 ${item.notes}</p>` : ''}
                                                        ${item.photo_url ? `
                                                            <div class="mt-2">
                                                                <img src="${item.photo_url}" alt="Foto" class="max-w-xs max-h-48 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open('${item.photo_url}', '_blank')">
                                                                <p class="text-xs text-blue-500 mt-1">📷 Clique para ampliar</p>
                                                            </div>
                                                        ` : ''}
                                                        ${item.completed_at ? `<p class="text-xs text-gray-400 mt-2">✓ Concluído em ${new Date(item.completed_at).toLocaleString('pt-BR')}</p>` : ''}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                        
                        <!-- Itens Pendentes (apenas para referência) -->
                        ${items.filter(i => !i.is_completed).length > 0 ? `
                            <div class="mt-6 space-y-4">
                                <h3 class="text-lg font-bold text-gray-500 dark:text-gray-400 border-b pb-2">
                                    ○ Itens Não Concluídos (${items.filter(i => !i.is_completed).length})
                                </h3>
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500">
                                        ${items.filter(i => !i.is_completed).map(i => i.task_name).join(', ')}
                                    </p>
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- Observações -->
                        ${checklist.notes ? `
                            <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                                <h4 class="font-semibold text-[#111318] dark:text-white mb-2">📝 Observações</h4>
                                <p class="text-gray-700 dark:text-gray-300">${checklist.notes}</p>
                            </div>
                        ` : ''}
                        
                        <!-- Histórico -->
                        ${history.length > 0 ? `
                            <div class="mt-6">
                                <h4 class="font-semibold text-[#111318] dark:text-white mb-3">📜 Histórico</h4>
                                <div class="space-y-2">
                                    ${history.map(h => `
                                        <div class="text-sm text-gray-600 dark:text-gray-400 border-l-2 border-gray-300 dark:border-gray-600 pl-3">
                                            <span class="font-medium">${h.action_by_name}</span> - ${h.action} 
                                            <span class="text-xs">(${new Date(h.created_at).toLocaleString('pt-BR')})</span>
                                            ${h.notes ? `<p class="text-xs mt-1">${h.notes}</p>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        // Fecha modal do relatório
        function closeReportModal() {
            const modal = document.getElementById('report-modal');
            if (modal) modal.remove();
        }

        // Função auxiliar para carregar imagem como base64
        async function loadImageAsBase64(url) {
            try {
                const response = await fetch(url);
                const blob = await response.blob();
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            } catch (error) {
                console.error('Erro ao carregar imagem:', error);
                return null;
            }
        }

        // Gera PDF do relatório como Ordem de Serviço
        async function generatePDF() {
            if (!currentReportData) return;
            
            showToast('Gerando Ordem de Serviço...', 'info');
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const checklist = currentReportData.checklist;
            const items = currentReportData.items || [];
            const client = currentClientData || {};
            
            // Configurações
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const margin = 15;
            let y = 15;
            
            // === CABEÇALHO ===
            doc.setFillColor(19, 91, 236);
            doc.rect(0, 0, pageWidth, 35, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.text('ORDEM DE SERVIÇO', pageWidth / 2, 18, { align: 'center' });
            doc.setFontSize(12);
            doc.setFont(undefined, 'normal');
            doc.text(`Nº ${checklist.id} - ${new Date(checklist.created_at).toLocaleDateString('pt-BR')}`, pageWidth / 2, 28, { align: 'center' });
            
            // === DADOS DO CLIENTE ===
            y = 45;
            doc.setFillColor(240, 240, 245);
            doc.rect(margin, y, pageWidth - margin * 2, 50, 'F');
            
            y += 8;
            doc.setTextColor(19, 91, 236);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('DADOS DO CLIENTE', margin + 5, y);
            
            y += 8;
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            
            // Coluna 1
            doc.setFont(undefined, 'bold');
            doc.text('Nome:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            doc.text(checklist.client_name || '-', margin + 25, y);
            
            // Coluna 2
            doc.setFont(undefined, 'bold');
            doc.text('CPF:', pageWidth / 2, y);
            doc.setFont(undefined, 'normal');
            doc.text(formatCPF(checklist.client_cpf), pageWidth / 2 + 15, y);
            
            y += 7;
            doc.setFont(undefined, 'bold');
            doc.text('Telefone:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.phone || client.phone_number || '-', margin + 30, y);
            
            doc.setFont(undefined, 'bold');
            doc.text('Plano:', pageWidth / 2, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.planId || '-', pageWidth / 2 + 18, y);
            
            y += 7;
            doc.setFont(undefined, 'bold');
            doc.text('Endereço:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            const endereco = client.address ? `${client.address}, ${client.number || 'S/N'} - ${client.city || ''}` : '-';
            doc.text(endereco.substring(0, 60), margin + 30, y);
            
            y += 7;
            doc.setFont(undefined, 'bold');
            doc.text('CEP:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.cep || '-', margin + 17, y);
            
            doc.setFont(undefined, 'bold');
            doc.text('Bairro:', pageWidth / 2, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.neighborhood || '-', pageWidth / 2 + 20, y);
            
            // === DADOS DE CONEXÃO ===
            y += 18;
            doc.setFillColor(255, 248, 230);
            doc.rect(margin, y, pageWidth - margin * 2, 30, 'F');
            doc.setDrawColor(255, 193, 7);
            doc.rect(margin, y, pageWidth - margin * 2, 30, 'S');
            
            y += 8;
            doc.setTextColor(200, 150, 0);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('DADOS DE CONEXÃO', margin + 5, y);
            
            y += 8;
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            
            // PPPoE
            doc.setFont(undefined, 'bold');
            doc.text('PPPoE:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.pppoe || 'Não informado', margin + 25, y);
            
            // Senha PPPoE
            doc.setFont(undefined, 'bold');
            doc.text('Senha:', pageWidth / 2, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.password ? '******' : 'Não informada', pageWidth / 2 + 20, y);
            
            y += 7;
            // MAC / Serial do equipamento
            doc.setFont(undefined, 'bold');
            doc.text('Serial/MAC:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            doc.text(client.serial || 'Não informado', margin + 35, y);
            
            // === DADOS DO SERVIÇO ===
            y += 18;
            doc.setFillColor(230, 245, 255);
            doc.rect(margin, y, pageWidth - margin * 2, 25, 'F');
            
            y += 8;
            doc.setTextColor(19, 91, 236);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('DADOS DO SERVIÇO', margin + 5, y);
            
            y += 8;
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            
            doc.setFont(undefined, 'bold');
            doc.text('Tipo:', margin + 5, y);
            doc.setFont(undefined, 'normal');
            doc.text(installationTypeLabels[checklist.installation_type] || checklist.installation_type, margin + 18, y);
            
            doc.setFont(undefined, 'bold');
            doc.text('Técnico:', pageWidth / 2, y);
            doc.setFont(undefined, 'normal');
            doc.text(checklist.technician_name || '-', pageWidth / 2 + 25, y);
            
            // === LOCALIZAÇÃO DO TÉCNICO ===
            if (checklist.tech_latitude && checklist.tech_longitude) {
                y += 15;
                doc.setFillColor(240, 255, 240);
                doc.rect(margin, y, pageWidth - margin * 2, 20, 'F');
                doc.setDrawColor(34, 139, 34);
                doc.rect(margin, y, pageWidth - margin * 2, 20, 'S');
                
                y += 8;
                doc.setTextColor(34, 139, 34);
                doc.setFontSize(11);
                doc.setFont(undefined, 'bold');
                doc.text('📍 LOCALIZAÇÃO VERIFICADA', margin + 5, y);
                
                y += 6;
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(9);
                doc.setFont(undefined, 'normal');
                
                const lat = parseFloat(checklist.tech_latitude).toFixed(6);
                const lng = parseFloat(checklist.tech_longitude).toFixed(6);
                const accuracy = checklist.tech_location_accuracy ? `±${Math.round(checklist.tech_location_accuracy)}m` : '';
                const capturedAt = checklist.location_captured_at 
                    ? new Date(checklist.location_captured_at).toLocaleString('pt-BR') 
                    : new Date(checklist.created_at).toLocaleString('pt-BR');
                
                doc.text(`Lat: ${lat}  |  Lng: ${lng}  ${accuracy}  |  Capturada em: ${capturedAt}`, margin + 5, y);
                
                y += 5;
            }
            
            // === PROGRESSO ===
            y += 18;
            const totalItems = items.length;
            const completedItems = items.filter(i => i.is_completed).length;
            const completionRate = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;
            
            doc.setFont(undefined, 'bold');
            doc.setFontSize(11);
            doc.text(`Progresso: ${completionRate}% (${completedItems}/${totalItems} itens)`, margin, y);
            
            // Barra de progresso
            y += 5;
            doc.setDrawColor(200, 200, 200);
            doc.setFillColor(230, 230, 230);
            doc.rect(margin, y, pageWidth - margin * 2, 6, 'FD');
            if (completionRate > 0) {
                doc.setFillColor(19, 91, 236);
                doc.rect(margin, y, (pageWidth - margin * 2) * (completionRate / 100), 6, 'F');
            }
            
            // === ITENS CONCLUÍDOS ===
            y += 15;
            doc.setFont(undefined, 'bold');
            doc.setFontSize(12);
            doc.setTextColor(34, 139, 34);
            doc.text(`✓ ITENS CONCLUÍDOS (${completedItems})`, margin, y);
            
            // Agrupa por categoria (apenas concluídos)
            const grouped = {};
            items.filter(i => i.is_completed).forEach(item => {
                if (!grouped[item.task_category]) grouped[item.task_category] = [];
                grouped[item.task_category].push(item);
            });
            
            y += 6;
            
            // Processa cada categoria
            for (const [key, label] of Object.entries(categories)) {
                const catItems = grouped[key] || [];
                if (catItems.length === 0) continue;
                
                // Verifica se precisa de nova página
                if (y > 260) {
                    doc.addPage();
                    y = 20;
                }
                
                doc.setFont(undefined, 'bold');
                doc.setFontSize(11);
                doc.setTextColor(19, 91, 236);
                doc.text(`${label} (${catItems.length})`, margin, y);
                y += 6;
                
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(9);
                doc.setFont(undefined, 'normal');
                
                // Processa cada item da categoria
                for (const item of catItems) {
                    // Verifica espaço na página
                    if (y > 250) {
                        doc.addPage();
                        y = 20;
                    }
                    
                    const itemText = `✓ ${item.task_name}`;
                    const splitText = doc.splitTextToSize(itemText, pageWidth - margin * 2 - 10);
                    doc.text(splitText, margin + 5, y);
                    y += splitText.length * 4 + 2;
                    
                    if (item.notes) {
                        doc.setTextColor(100, 100, 100);
                        const notesText = doc.splitTextToSize(`Notas: ${item.notes}`, pageWidth - margin * 2 - 15);
                        doc.text(notesText, margin + 10, y);
                        y += notesText.length * 3 + 2;
                        doc.setTextColor(0, 0, 0);
                    }
                    
                    // Adiciona a imagem se existir
                    if (item.photo_url) {
                        try {
                            const base64Image = await loadImageAsBase64(item.photo_url);
                            if (base64Image) {
                                // Verifica espaço para a imagem
                                if (y + 50 > pageHeight - 20) {
                                    doc.addPage();
                                    y = 20;
                                }
                                
                                // Adiciona a imagem (max 60x50mm)
                                doc.addImage(base64Image, 'JPEG', margin + 10, y, 60, 45);
                                y += 50;
                            }
                        } catch (e) {
                            console.error('Erro ao adicionar imagem ao PDF:', e);
                            doc.setTextColor(200, 0, 0);
                            doc.text(`[Erro ao carregar imagem]`, margin + 10, y);
                            y += 4;
                            doc.setTextColor(0, 0, 0);
                        }
                    }
                }
                
                y += 4;
            }
            
            // Itens pendentes (resumo)
            const pendingItems = items.filter(i => !i.is_completed);
            if (pendingItems.length > 0) {
                if (y > 250) {
                    doc.addPage();
                    y = 20;
                }
                y += 10;
                doc.setFont(undefined, 'bold');
                doc.setFontSize(10);
                doc.setTextColor(150, 150, 150);
                doc.text(`○ Itens não concluídos (${pendingItems.length}):`, margin, y);
                y += 5;
                doc.setFont(undefined, 'normal');
                doc.setFontSize(8);
                const pendingText = pendingItems.map(i => i.task_name).join(', ');
                const splitPending = doc.splitTextToSize(pendingText, pageWidth - margin * 2);
                doc.text(splitPending, margin, y);
            }
            
            // Observações
            if (checklist.notes) {
                if (y > 220) {
                    doc.addPage();
                    y = 20;
                }
                y += 10;
                doc.setTextColor(0, 0, 0);
                doc.setFont(undefined, 'bold');
                doc.setFontSize(11);
                doc.text('OBSERVAÇÕES:', margin, y);
                y += 6;
                doc.setFont(undefined, 'normal');
                doc.setFontSize(9);
                const notesText = doc.splitTextToSize(checklist.notes, pageWidth - margin * 2);
                doc.text(notesText, margin, y);
                y += notesText.length * 4;
            }
            
            // === ASSINATURAS ===
            // Verifica se precisa de nova página para as assinaturas
            if (y > 230) {
                doc.addPage();
                y = 20;
            }
            
            y += 20;
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            doc.text('ASSINATURAS', pageWidth / 2, y, { align: 'center' });
            
            y += 15;
            // Linha para assinatura do técnico
            const lineWidth = 70;
            const lineStartLeft = margin + 10;
            const lineStartRight = pageWidth - margin - lineWidth - 10;
            
            doc.setDrawColor(0, 0, 0);
            doc.line(lineStartLeft, y, lineStartLeft + lineWidth, y);
            doc.line(lineStartRight, y, lineStartRight + lineWidth, y);
            
            y += 5;
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text('Técnico Responsável', lineStartLeft + lineWidth / 2, y, { align: 'center' });
            doc.text('Cliente', lineStartRight + lineWidth / 2, y, { align: 'center' });
            
            y += 4;
            doc.setFontSize(8);
            doc.setTextColor(100, 100, 100);
            doc.text(checklist.technician_name || '', lineStartLeft + lineWidth / 2, y, { align: 'center' });
            doc.text(checklist.client_name || '', lineStartRight + lineWidth / 2, y, { align: 'center' });
            
            // Data
            y += 15;
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(9);
            const dataFinalizacao = checklist.completed_at 
                ? new Date(checklist.completed_at).toLocaleDateString('pt-BR') 
                : new Date().toLocaleDateString('pt-BR');
            doc.text(`Data: ${dataFinalizacao}`, pageWidth / 2, y, { align: 'center' });
            
            // Footer em todas as páginas
            const totalPages = doc.internal.getNumberOfPages();
            for (let i = 1; i <= totalPages; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150, 150, 150);
                doc.text(
                    `Ondeline Tech - Ordem de Serviço Nº ${checklist.id} | Página ${i} de ${totalPages} | Gerado em ${new Date().toLocaleString('pt-BR')}`, 
                    margin, 
                    doc.internal.pageSize.getHeight() - 8
                );
            }
            
            // Download com nome de Ordem de Serviço
            const fileName = `OS-${checklist.id}-${checklist.client_name.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_]/g, '')}.pdf`;
            doc.save(fileName);
            showToast('Ordem de Serviço gerada com sucesso!', 'success');
        }

        // Toast
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `fixed top-20 left-1/2 -translate-x-1/2 px-6 py-3 rounded-xl shadow-lg z-50 ${
                type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-gray-800'
            } text-white`;
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    </script>
</body>
</html>
