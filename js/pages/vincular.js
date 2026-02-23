/**
 * Vincular Equipamento Page Module
 * Ondeline Tech - App do Técnico
 */

App.initVincularPage = async function() {
    this.selectedClient = null;

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

    if (serialInput) {
        serialInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
            App.checkVincularButton();
        });
    }

    let searchTimeout;
    const self = this;
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

    if (btnClearSelection) {
        btnClearSelection.addEventListener('click', () => {
            this.selectedClient = null;
            selectedClientDiv.classList.add('hidden');
            searchInput.value = '';
            searchInput.disabled = false;
            this.checkVincularButton();
        });
    }

    if (btnScan) {
        btnScan.addEventListener('click', () => {
            this.showToast('Scanner em desenvolvimento', 'info');
        });
    }

    if (btnVincular) {
        btnVincular.addEventListener('click', async () => {
            await this.handleVincularEquipamento();
        });
    }
};

App.searchClientsForVinculo = async function(query) {
    const searchResults = document.getElementById('search-results');
    try {
        const response = await API.getClients({ search: query, limit: 10 });

        if (response.success && response.data.length > 0) {
            searchResults.innerHTML = response.data.map(client => `
                <div class="client-result p-3 rounded-lg bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-3" data-cpf="${client.cpf}" data-name="${client.name}" data-address="${client.address || ''}, ${client.number || ''} - ${client.city || ''}" data-serial="${client.serial || ''}">
                    <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">person</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[#111318] dark:text-white font-medium truncate">${client.name}</p>
                        <p class="text-[#616f89] text-xs">${App.formatCPF(client.cpf)}</p>
                    </div>
                    ${client.serial ? '<span class="material-symbols-outlined text-orange-500 text-lg" title="Já possui equipamento">router</span>' : ''}
                </div>
            `).join('');
            searchResults.classList.remove('hidden');

            searchResults.querySelectorAll('.client-result').forEach(el => {
                el.addEventListener('click', () => {
                    App.selectClientForVinculo({
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
        this.showToast('Erro ao buscar clientes', 'error');
    }
};

App.selectClientForVinculo = function(client) {
    this.selectedClient = client;

    const searchResults = document.getElementById('search-results');
    const selectedClientDiv = document.getElementById('selected-client');
    const searchInput = document.getElementById('field-search');

    document.getElementById('selected-name').textContent = client.name;
    document.getElementById('selected-cpf').textContent = this.formatCPF(client.cpf);
    document.getElementById('selected-address').textContent = client.address;

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
};

App.checkVincularButton = function() {
    const serialInput = document.getElementById('field-serial');
    const btnVincular = document.getElementById('btn-vincular');

    const hasSerial = serialInput && serialInput.value.trim().length >= 3;
    const hasClient = this.selectedClient !== null;

    btnVincular.disabled = !(hasSerial && hasClient);
};

App.handleVincularEquipamento = async function() {
    if (this._isSubmitting) return;
    this._isSubmitting = true;

    const serialInput = document.getElementById('field-serial');
    const serial = serialInput.value.trim().toUpperCase();

    if (!this.selectedClient || !serial) {
        this.showToast('Preencha todos os campos', 'warning');
        this._isSubmitting = false;
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
                window.location.href = 'dashboard.php';
            }, 1000);
        } else {
            this.showToast(response.message || 'Erro ao vincular', 'error');
        }
    } catch (error) {
        this.showToast('Erro ao vincular equipamento', 'error');
    } finally {
        this.showLoading(false);
        this._isSubmitting = false;
    }
};
