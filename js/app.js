/**
 * App Principal - Lógica de Navegação e Inicialização
 * Ondeline Tech - App do Técnico
 */

const App = {
    /**
     * Inicialização do App
     */
    init() {
        this.registerServiceWorker();
        this.checkAuth();
        this.setupEventListeners();
        this.initCurrentPage();
    },

    /**
     * Registra o Service Worker para PWA
     */
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                // Primeiro, desregistra qualquer SW antigo
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (const registration of registrations) {
                    await registration.unregister();
                    console.log('ServiceWorker antigo removido');
                }
                
                // Registra o novo SW
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('ServiceWorker registrado:', registration);
            } catch (error) {
                console.log('Erro ao registrar ServiceWorker:', error);
            }
        }
    },

    /**
     * Verifica autenticação
     */
    checkAuth() {
        const publicPages = ['login.html'];
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';

        if (!API.isAuthenticated() && !publicPages.includes(currentPage)) {
            window.location.href = 'login.html';
            return false;
        }

        if (API.isAuthenticated() && currentPage === 'login.html') {
            window.location.href = 'dashboard.html';
            return false;
        }

        return true;
    },

    /**
     * Configura event listeners globais
     */
    setupEventListeners() {
        // Toggle de tema (dark/light mode)
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-theme-toggle]')) {
                this.toggleTheme();
            }

            // Logout
            if (e.target.closest('[data-logout]')) {
                e.preventDefault();
                API.logout();
            }
        });

        // Detecta preferência de tema do sistema
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    document.documentElement.classList.toggle('dark', e.matches);
                }
            });
        }

        // Aplica tema salvo ou do sistema
        this.applyTheme();
    },

    /**
     * Toggle do tema
     */
    toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    },

    /**
     * Aplica o tema salvo
     */
    applyTheme() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    },

    /**
     * Inicializa a página atual
     */
    initCurrentPage() {
        const page = window.location.pathname.split('/').pop() || 'index.html';
        
        switch (page) {
            case 'login.html':
                this.initLoginPage();
                break;
            case 'dashboard.html':
                this.initDashboardPage();
                break;
            case 'novo-cadastro.html':
                this.initCadastroPage();
                break;
            case 'consultar.html':
                this.initConsultaPage();
                break;
            case 'detalher.html':
                this.initDetalhesPage();
                break;
            case 'vincular-equipamento.html':
                this.initVincularPage();
                break;
        }
    },

    /**
     * Mostra toast de notificação
     */
    showToast(message, type = 'info') {
        // Remove toast existente
        const existingToast = document.querySelector('.app-toast');
        if (existingToast) existingToast.remove();

        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const toast = document.createElement('div');
        toast.className = `app-toast fixed top-20 left-1/2 -translate-x-1/2 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg z-50 animate-fade-in`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    },

    /**
     * Mostra loading
     */
    showLoading(show = true) {
        let loader = document.getElementById('app-loader');
        
        if (show) {
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'app-loader';
                loader.className = 'fixed inset-0 bg-black/30 flex items-center justify-center z-50';
                loader.innerHTML = `
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl flex flex-col items-center gap-3">
                        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-gray-600 dark:text-gray-300 text-sm font-medium">Carregando...</span>
                    </div>
                `;
                document.body.appendChild(loader);
            }
        } else {
            if (loader) loader.remove();
        }
    },

    // ==========================================
    // PÁGINAS ESPECÍFICAS
    // ==========================================

    /**
     * Página de Login
     */
    initLoginPage() {
        const form = document.querySelector('form') || document.querySelector('button')?.closest('div').parentElement;
        const usernameInput = document.querySelector('input[type="text"]');
        const passwordInput = document.querySelector('input[type="password"]');
        const submitBtn = document.querySelector('button');
        const togglePassword = document.querySelector('.material-symbols-outlined[class*="visibility"]')?.closest('div');

        // Toggle visibilidade da senha
        if (togglePassword) {
            togglePassword.addEventListener('click', () => {
                const icon = togglePassword.querySelector('.material-symbols-outlined');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    passwordInput.type = 'password';
                    icon.textContent = 'visibility';
                }
            });
        }

        // Submit do login
        if (submitBtn) {
            submitBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                
                const username = usernameInput?.value?.trim();
                const password = passwordInput?.value;

                if (!username || !password) {
                    this.showToast('Preencha todos os campos', 'warning');
                    return;
                }

                this.showLoading(true);
                submitBtn.disabled = true;

                try {
                    const response = await API.login(username, password);
                    if (response.success) {
                        this.showToast('Login realizado com sucesso!', 'success');
                        setTimeout(() => {
                            window.location.href = 'dashboard.html';
                        }, 500);
                    } else {
                        this.showToast(response.message || 'Erro ao fazer login', 'error');
                    }
                } catch (error) {
                    this.showToast(error.message || 'Erro ao conectar com o servidor', 'error');
                } finally {
                    this.showLoading(false);
                    submitBtn.disabled = false;
                }
            });
        }
    },

    /**
     * Página do Dashboard
     */
    async initDashboardPage() {
        const user = API.getUser();
        
        // Atualiza nome do usuário
        const greetingEl = document.querySelector('h1');
        if (greetingEl && user) {
            greetingEl.textContent = `Olá, ${user.full_name || user.username}!`;
        }

        // Configura navegação
        this.setupBottomNavigation();

        // Botão de cadastrar
        const cadastrarBtn = document.querySelector('button');
        if (cadastrarBtn && cadastrarBtn.textContent.includes('Cadastrar')) {
            cadastrarBtn.addEventListener('click', () => {
                window.location.href = 'novo-cadastro.html';
            });
        }

        // Carrega estatísticas
        this.showLoading(true);
        try {
            const response = await API.getDashboard();
            if (response.success) {
                this.updateDashboardStats(response.data);
            }
        } catch (error) {
            console.error('Erro ao carregar dashboard:', error);
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Atualiza estatísticas do dashboard
     */
    updateDashboardStats(data) {
        // Atualiza cadastros de hoje
        const statToday = document.getElementById('stat-today');
        if (statToday) statToday.textContent = data.totals.today || '0';

        // Atualiza total de clientes
        const statTotal = document.getElementById('stat-total');
        if (statTotal) statTotal.textContent = data.totals.clients || '0';

        // Atualiza último cadastro
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
    },

    /**
     * Página de Cadastro
     */
    async initCadastroPage() {
        // Inicializa array para armazenar fotos pendentes
        this.pendingPhotos = [];

        // Carrega planos e instaladores
        await this.loadSelectOptions();

        // Botão voltar
        const backBtn = document.querySelector('[data-icon="ArrowLeft"]') || 
                       document.querySelector('.material-symbols-outlined');
        if (backBtn) {
            backBtn.closest('div').addEventListener('click', () => {
                window.history.back();
            });
        }

        // Botão salvar
        const saveBtn = document.querySelector('button');
        if (saveBtn && saveBtn.textContent.includes('Salvar')) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSaveClient();
            });
        }

        // Máscara de CPF
        const cpfInput = document.getElementById('field-cpf');
        if (cpfInput) {
            cpfInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            });
        }
        
        // Máscara de telefone
        const phoneInput = document.getElementById('field-phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            });
        }
        
        // Máscara de CEP
        const cepInputMask = document.getElementById('field-cep');
        if (cepInputMask) {
            cepInputMask.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });
        }

        // Busca CEP automática
        const cepInput = document.getElementById('field-cep') || document.querySelector('input[placeholder*="00000-000"]');
        if (cepInput) {
            cepInput.addEventListener('blur', async (e) => {
                const cep = e.target.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    try {
                        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                        const data = await response.json();
                        if (!data.erro) {
                            const ruaInput = document.getElementById('field-street');
                            const cidadeInput = document.getElementById('field-city');
                            if (ruaInput) ruaInput.value = data.logradouro;
                            if (cidadeInput) cidadeInput.value = `${data.localidade} - ${data.uf}`;
                        }
                    } catch (error) {
                        console.error('Erro ao buscar CEP:', error);
                    }
                }
            });
        }

        // Inicializa upload de fotos
        this.initPhotoUpload();
    },

    /**
     * Inicializa funcionalidade de upload de fotos
     */
    initPhotoUpload() {
        const photoUploads = document.querySelectorAll('.photo-upload');
        
        photoUploads.forEach(container => {
            const input = container.querySelector('input[type="file"]');
            const preview = container.querySelector('.photo-preview');
            const icon = container.querySelector('.photo-icon');
            const addBtn = container.querySelector('.photo-add-btn');
            const removeBtn = container.querySelector('.photo-remove-btn');
            const photoType = container.dataset.type;

            if (!input) return;

            // Evento de seleção de arquivo
            input.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // Valida tipo
                if (!file.type.startsWith('image/')) {
                    this.showToast('Selecione apenas imagens', 'warning');
                    return;
                }

                // Valida tamanho (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    this.showToast('Imagem muito grande (máx 10MB)', 'warning');
                    return;
                }

                // Mostra preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    icon.classList.add('hidden');
                    addBtn.classList.add('hidden');
                    removeBtn.classList.remove('hidden');
                    removeBtn.classList.add('flex');

                    // Armazena a foto para upload posterior
                    this.pendingPhotos = this.pendingPhotos.filter(p => p.type !== photoType);
                    this.pendingPhotos.push({
                        type: photoType,
                        file: file,
                        base64: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            });

            // Botão remover foto
            if (removeBtn) {
                removeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    preview.src = '';
                    preview.classList.add('hidden');
                    icon.classList.remove('hidden');
                    addBtn.classList.remove('hidden');
                    removeBtn.classList.add('hidden');
                    removeBtn.classList.remove('flex');
                    input.value = '';

                    // Remove do array de fotos pendentes
                    this.pendingPhotos = this.pendingPhotos.filter(p => p.type !== photoType);
                });
            }
        });
    },

    /**
     * Faz upload das fotos pendentes após salvar cliente
     */
    async uploadPendingPhotos(cpf) {
        if (!this.pendingPhotos || this.pendingPhotos.length === 0) {
            return { success: true, uploaded: 0 };
        }

        let uploaded = 0;
        let errors = [];

        for (const photo of this.pendingPhotos) {
            try {
                const response = await API.uploadPhoto(cpf, photo.base64, photo.type);
                if (response.success) {
                    uploaded++;
                } else {
                    errors.push(photo.type);
                }
            } catch (error) {
                console.error(`Erro ao enviar foto ${photo.type}:`, error);
                errors.push(photo.type);
            }
        }

        return { 
            success: errors.length === 0, 
            uploaded, 
            errors 
        };
    },

    /**
     * Carrega opções de selects (planos e instaladores)
     */
    async loadSelectOptions() {
        try {
            const [plansResponse, installersResponse] = await Promise.all([
                API.getPlans(),
                API.getInstallers()
            ]);

            // Atualiza select de planos
            const planSelect = document.getElementById('field-plan');
            if (planSelect && plansResponse.success && plansResponse.data.length > 0) {
                planSelect.innerHTML = plansResponse.data.map(plan => 
                    `<option value="${plan.name}">${plan.name}</option>`
                ).join('');
            }
        } catch (error) {
            console.error('Erro ao carregar opções:', error);
        }
    },

    /**
     * Salva novo cliente
     */
    async handleSaveClient() {
        // Coleta dados usando IDs dos campos
        const getValue = (id) => {
            const el = document.getElementById(id);
            return el ? el.value.trim() : '';
        };

        // Mapeamento para as colunas do banco de dados
        const data = {
            name: getValue('field-name'),
            cpf: getValue('field-cpf'),
            phone: getValue('field-phone'),
            birthDate: getValue('field-dob'),
            cep: getValue('field-cep'),
            city: getValue('field-city'),
            address: getValue('field-street'),
            number: getValue('field-number'),
            complement: getValue('field-complement'),
            planId: getValue('field-plan'),
            pppoe: getValue('field-pppoe-user'),
            password: getValue('field-pppoe-pass'),
            dueDay: parseInt(getValue('field-due-date').replace(/\D/g, '')) || 10,
            observation: getValue('field-observations'),
            status: 'ativo',
            active: 1
        };

        console.log('Dados a serem salvos:', data);

        // Validação básica
        if (!data.name) {
            this.showToast('Preencha o nome do cliente', 'warning');
            return;
        }
        if (!data.cpf) {
            this.showToast('Preencha o CPF do cliente', 'warning');
            return;
        }

        this.showLoading(true);

        try {
            console.log('Enviando para API...');
            const response = await API.createClient(data);
            console.log('Resposta da API:', response);
            
            if (response.success) {
                // Faz upload das fotos
                const cpf = data.cpf.replace(/\D/g, '');
                const photoResult = await this.uploadPendingPhotos(cpf);
                
                if (photoResult.uploaded > 0) {
                    this.showToast(`Cliente cadastrado com ${photoResult.uploaded} foto(s)!`, 'success');
                } else {
                    this.showToast('Cliente cadastrado com sucesso!', 'success');
                }
                
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1000);
            } else {
                this.showToast(response.message || 'Erro ao cadastrar', 'error');
            }
        } catch (error) {
            this.showToast(error.message || 'Erro ao salvar cliente', 'error');
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Página de Consulta
     */
    async initConsultaPage() {
        this.setupBottomNavigation();

        // Botão voltar
        const backBtn = document.querySelector('.material-symbols-outlined');
        if (backBtn) {
            backBtn.closest('div').addEventListener('click', () => {
                window.history.back();
            });
        }

        // Campo de busca
        const searchInput = document.querySelector('input[placeholder*="Buscar"]');
        let searchTimeout;
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchClients(e.target.value);
                }, 500);
            });
        }

        // Botão flutuante de adicionar
        const addBtn = document.querySelector('.fixed.bottom-8');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                window.location.href = 'novo-cadastro.html';
            });
        }

        // Carrega clientes
        await this.loadClients();
    },

    /**
     * Carrega lista de clientes
     */
    async loadClients(search = '') {
        const container = document.getElementById('clients-container') || document.querySelector('.flex.flex-col.gap-3.p-4');
        if (!container) {
            console.error('Container de clientes não encontrado');
            return;
        }

        this.showLoading(true);

        try {
            const params = search ? { search } : {};
            const response = await API.getClients(params);
            
            if (response.success) {
                this.renderClientsList(response.data, container);
                
                // Atualiza contador
                const countEl = document.querySelector('.text-sm.text-\\[\\#616f89\\]');
                if (countEl) {
                    countEl.textContent = `${response.data.length} clientes encontrados`;
                }
            }
        } catch (error) {
            console.error('Erro ao carregar clientes:', error);
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Renderiza lista de clientes
     */
    renderClientsList(clients, container) {
        if (clients.length === 0) {
            container.innerHTML = `
                <div class="text-center py-10">
                    <span class="material-symbols-outlined text-6xl text-gray-300">person_off</span>
                    <p class="text-gray-500 mt-4">Nenhum cliente encontrado</p>
                </div>
            `;
            return;
        }

        container.innerHTML = clients.map(client => {
            const isActive = client.active == 1 || client.status === 'ativo';
            const statusColor = isActive ? 'green' : 'red';
            const statusText = isActive ? 'Ativo' : 'Inativo';
            const addressText = [client.address, client.number, client.city].filter(Boolean).join(', ') || 'Endereço não informado';
            
            return `
            <div class="flex items-stretch justify-between gap-4 rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm border border-gray-100 dark:border-gray-800" 
                 data-cpf="${client.cpf}">
                <div class="flex flex-[2_2_0px] flex-col justify-between">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block w-2 h-2 rounded-full bg-${statusColor}-500"></span>
                            <p class="text-${statusColor}-600 dark:text-${statusColor}-400 text-xs font-bold uppercase tracking-wider leading-normal">${statusText}</p>
                        </div>
                        <p class="text-[#111318] dark:text-white text-base font-bold leading-tight">${client.name}</p>
                        <p class="text-[#616f89] dark:text-gray-400 text-sm font-normal leading-normal">CPF: ${this.formatCPF(client.cpf)}</p>
                        <p class="text-[#616f89] dark:text-gray-400 text-xs font-normal leading-normal mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            ${addressText}
                        </p>
                    </div>
                    <button class="mt-4 flex min-w-[140px] max-w-fit cursor-pointer items-center justify-center overflow-hidden rounded-lg h-9 px-4 bg-primary text-white gap-1 text-sm font-semibold leading-normal btn-details">
                        <span class="truncate">Ver Detalhes</span>
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                </div>
                <div class="w-24 h-24 bg-center bg-no-repeat bg-cover rounded-xl flex-shrink-0 bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-4xl text-primary">person</span>
                </div>
            </div>
        `;
        }).join('');

        // Event listeners para os botões
        container.querySelectorAll('.btn-details').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const cpf = e.target.closest('[data-cpf]').dataset.cpf;
                window.location.href = `detalher.html?cpf=${cpf}`;
            });
        });

        // Também adiciona clique no card inteiro
        container.querySelectorAll('[data-cpf]').forEach(card => {
            card.style.cursor = 'pointer';
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-details')) {
                    const cpf = card.dataset.cpf;
                    window.location.href = `detalher.html?cpf=${cpf}`;
                }
            });
        });
    },

    /**
     * Busca clientes
     */
    searchClients(term) {
        this.loadClients(term);
    },

    /**
     * Página de Detalhes
     */
    async initDetalhesPage() {
        const urlParams = new URLSearchParams(window.location.search);
        const cpf = urlParams.get('cpf');

        // Botão voltar
        const backBtn = document.querySelector('.material-symbols-outlined');
        if (backBtn) {
            backBtn.closest('button').addEventListener('click', () => {
                window.history.back();
            });
        }

        if (!cpf) {
            this.showToast('CPF não informado', 'error');
            return;
        }

        this.showLoading(true);

        try {
            const response = await API.getClient(cpf);
            if (response.success) {
                this.renderClientDetails(response.data);
                // Carrega fotos do cliente
                this.loadClientPhotos(cpf);
            } else {
                this.showToast('Cliente não encontrado', 'error');
            }
        } catch (error) {
            this.showToast('Erro ao carregar cliente', 'error');
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Carrega fotos do cliente
     */
    async loadClientPhotos(cpf) {
        const container = document.getElementById('photos-container');
        const countEl = document.getElementById('photo-count');
        
        if (!container) return;

        try {
            const response = await API.getPhotos(cpf);
            
            if (response.success && response.data.length > 0) {
                const photos = response.data;
                countEl.textContent = `${photos.length} foto(s)`;
                
                // Tipos de foto com ícones
                const typeLabels = {
                    'router': 'Roteador',
                    'cabling': 'Cabeamento',
                    'signal': 'Sinal',
                    'other': 'Outros'
                };
                
                container.innerHTML = photos.map(photo => `
                    <div class="flex-shrink-0 relative group" data-photo-id="${photo.id}">
                        <div class="min-w-[100px] h-24 rounded-lg bg-cover bg-center cursor-pointer" 
                             style="background-image: url('${photo.url}');"
                             onclick="App.openPhotoModal('${photo.url}', '${typeLabels[photo.type] || photo.type}')">
                        </div>
                        <span class="absolute bottom-1 left-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded">
                            ${typeLabels[photo.type] || photo.type}
                        </span>
                    </div>
                `).join('');
            } else {
                countEl.textContent = 'Nenhuma foto';
                container.innerHTML = `
                    <div class="w-full h-24 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <span class="material-symbols-outlined text-2xl">no_photography</span>
                            <p class="text-xs mt-1">Sem fotos</p>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erro ao carregar fotos:', error);
            countEl.textContent = 'Erro';
            container.innerHTML = `
                <div class="w-full h-24 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-400">
                    <span class="text-xs">Erro ao carregar fotos</span>
                </div>
            `;
        }
    },

    /**
     * Abre modal para visualizar foto em tela cheia
     */
    openPhotoModal(url, title) {
        // Remove modal existente
        const existingModal = document.getElementById('photo-modal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'photo-modal';
        modal.className = 'fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <button class="absolute top-4 right-4 text-white p-2" onclick="this.parentElement.remove()">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>
            <div class="absolute top-4 left-4 text-white">
                <p class="text-sm font-medium">${title}</p>
            </div>
            <img src="${url}" class="max-w-full max-h-full object-contain rounded-lg" alt="${title}">
        `;
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
        
        document.body.appendChild(modal);
    },

    /**
     * Renderiza detalhes do cliente
     */
    renderClientDetails(client) {
        // Nome e CPF
        const nameEl = document.querySelector('h2');
        if (nameEl) nameEl.textContent = client.name;

        const cpfEl = document.querySelector('p.text-sm');
        if (cpfEl) cpfEl.textContent = `CPF: ${this.formatCPF(client.cpf)}`;

        // Plano
        const planEl = document.querySelector('.font-semibold');
        if (planEl) planEl.textContent = client.plan;

        // Vencimento
        const dueEl = document.querySelectorAll('.font-semibold')[1];
        if (dueEl) dueEl.textContent = `Todo dia ${client.due_date}`;

        // Endereço
        const addressEl = document.querySelector('.leading-relaxed');
        if (addressEl) {
            addressEl.innerHTML = `
                ${client.address}, ${client.number}${client.complement ? `, ${client.complement}` : ''}<br/>
                ${client.city}<br/>
            `;
        }

        // Observações
        const obsEl = document.querySelector('.italic');
        if (obsEl) obsEl.textContent = `"${client.observation || 'Sem observações'}"`;
    },

    /**
     * Configura navegação inferior
     */
    setupBottomNavigation() {
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const text = link.querySelector('span:last-child')?.textContent?.toLowerCase();
                
                switch (text) {
                    case 'início':
                        window.location.href = 'dashboard.html';
                        break;
                    case 'clientes':
                        window.location.href = 'consultar.html';
                        break;
                    case 'ajustes':
                        // Página de ajustes (pode criar depois)
                        break;
                }
            });
        });
    },

    /**
     * Página de Vincular Equipamento
     */
    async initVincularPage() {
        this.selectedClient = null;

        // Botão voltar
        const backBtn = document.querySelector('[data-icon="ArrowLeft"]');
        if (backBtn) {
            backBtn.closest('div').addEventListener('click', () => {
                window.history.back();
            });
        }

        const serialInput = document.getElementById('field-serial');
        const searchInput = document.getElementById('field-search');
        const searchResults = document.getElementById('search-results');
        const selectedClientDiv = document.getElementById('selected-client');
        const btnVincular = document.getElementById('btn-vincular');
        const btnClearSelection = document.getElementById('btn-clear-selection');
        const btnScan = document.getElementById('btn-scan');

        // Transforma serial em maiúsculo
        if (serialInput) {
            serialInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
                this.checkVincularButton();
            });
        }

        // Busca de clientes
        let searchTimeout;
        const self = this; // Guarda referência para usar no setTimeout
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }

                searchTimeout = setTimeout(async () => {
                    await self.searchClientsForVinculo(query);
                }, 300);
            });
        }

        // Limpar seleção
        if (btnClearSelection) {
            btnClearSelection.addEventListener('click', () => {
                this.selectedClient = null;
                selectedClientDiv.classList.add('hidden');
                searchInput.value = '';
                searchInput.disabled = false;
                this.checkVincularButton();
            });
        }

        // Botão de scan (placeholder - pode implementar scanner real depois)
        if (btnScan) {
            btnScan.addEventListener('click', () => {
                this.showToast('Scanner em desenvolvimento', 'info');
            });
        }

        // Vincular equipamento
        if (btnVincular) {
            btnVincular.addEventListener('click', async () => {
                await this.handleVincularEquipamento();
            });
        }
    },

    /**
     * Busca clientes para vinculação
     */
    async searchClientsForVinculo(query) {
        const searchResults = document.getElementById('search-results');
        console.log('Buscando clientes:', query);
        
        try {
            const response = await API.getClients({ search: query, limit: 10 });
            console.log('Resposta da busca:', response);
            
            if (response.success && response.data.length > 0) {
                searchResults.innerHTML = response.data.map(client => `
                    <div class="client-result p-3 rounded-lg bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-3" data-cpf="${client.cpf}" data-name="${client.name}" data-address="${client.address || ''}, ${client.number || ''} - ${client.city || ''}" data-serial="${client.serial || ''}">
                        <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">person</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[#111318] dark:text-white font-medium truncate">${client.name}</p>
                            <p class="text-[#616f89] text-xs">${this.formatCPF(client.cpf)}</p>
                        </div>
                        ${client.serial ? '<span class="material-symbols-outlined text-orange-500 text-lg" title="Já possui equipamento">router</span>' : ''}
                    </div>
                `).join('');
                searchResults.classList.remove('hidden');

                // Adiciona eventos de clique nos resultados
                searchResults.querySelectorAll('.client-result').forEach(el => {
                    el.addEventListener('click', () => {
                        this.selectClientForVinculo({
                            cpf: el.dataset.cpf,
                            name: el.dataset.name,
                            address: el.dataset.address,
                            serial: el.dataset.serial
                        });
                    });
                });
            } else {
                searchResults.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <span class="material-symbols-outlined text-3xl mb-2">search_off</span>
                        <p class="text-sm">Nenhum cliente encontrado</p>
                    </div>
                `;
                searchResults.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erro ao buscar clientes:', error);
            this.showToast('Erro ao buscar clientes', 'error');
        }
    },

    /**
     * Seleciona cliente para vinculação
     */
    selectClientForVinculo(client) {
        this.selectedClient = client;
        
        const searchResults = document.getElementById('search-results');
        const selectedClientDiv = document.getElementById('selected-client');
        const searchInput = document.getElementById('field-search');
        
        document.getElementById('selected-name').textContent = client.name;
        document.getElementById('selected-cpf').textContent = this.formatCPF(client.cpf);
        document.getElementById('selected-address').textContent = client.address;
        
        // Mostra serial atual se existir
        const currentSerialContainer = document.getElementById('current-serial-container');
        const currentSerial = document.getElementById('current-serial');
        if (client.serial && client.serial.trim()) {
            currentSerial.textContent = client.serial;
            currentSerialContainer.classList.remove('hidden');
        } else {
            currentSerialContainer.classList.add('hidden');
        }
        
        searchResults.classList.add('hidden');
        selectedClientDiv.classList.remove('hidden');
        searchInput.disabled = true;
        
        this.checkVincularButton();
    },

    /**
     * Verifica se pode habilitar botão de vincular
     */
    checkVincularButton() {
        const serialInput = document.getElementById('field-serial');
        const btnVincular = document.getElementById('btn-vincular');
        
        const hasSerial = serialInput && serialInput.value.trim().length >= 3;
        const hasClient = this.selectedClient !== null;
        
        btnVincular.disabled = !(hasSerial && hasClient);
    },

    /**
     * Vincula equipamento ao cliente
     */
    async handleVincularEquipamento() {
        const serialInput = document.getElementById('field-serial');
        const serial = serialInput.value.trim().toUpperCase();
        
        if (!this.selectedClient || !serial) {
            this.showToast('Preencha todos os campos', 'warning');
            return;
        }

        this.showLoading(true);

        try {
            const response = await API.updateClient({
                cpf: this.selectedClient.cpf,
                serial: serial
            });

            if (response.success) {
                this.showToast('Equipamento vinculado com sucesso!', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1000);
            } else {
                this.showToast(response.message || 'Erro ao vincular', 'error');
            }
        } catch (error) {
            console.error('Erro ao vincular equipamento:', error);
            this.showToast('Erro ao vincular equipamento', 'error');
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Formata CPF
     */
    formatCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
};

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Exporta para uso global
window.App = App;
