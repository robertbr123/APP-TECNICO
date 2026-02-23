/**
 * Consulta Page Module
 * Ondeline Tech - App do Técnico
 */

App.initConsultaPage = async function() {
    this.setupBottomNavigation();
};

App.loadClients = async function(search = '') {
    const container = document.getElementById('clients-container') || document.querySelector('.flex.flex-col.gap-3.p-4');
    if (!container) return;

    this.showLoading(true);

    try {
        const params = search ? { search } : {};
        const response = await API.getClients(params);

        if (response.success) {
            this.renderClientsList(response.data, container);

            const countEl = document.querySelector('.text-sm.text-\\[\\#616f89\\]');
            if (countEl) {
                countEl.textContent = `${response.data.length} clientes encontrados`;
            }
        }
    } catch (error) {
        this.showToast('Erro ao carregar clientes', 'error');
    } finally {
        this.showLoading(false);
    }
};

App.renderClientsList = function(clients, container) {
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
                    <p class="text-[#111318] dark:text-white text-base font-bold leading-tight">${escapeHtml(client.name)}</p>
                    <p class="text-[#616f89] dark:text-gray-400 text-sm font-normal leading-normal">CPF: ${escapeHtml(App.formatCPF(client.cpf))}</p>
                    <p class="text-[#616f89] dark:text-gray-400 text-xs font-normal leading-normal mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">location_on</span>
                        ${escapeHtml(addressText)}
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

    container.querySelectorAll('.btn-details').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const cpf = e.target.closest('[data-cpf]').dataset.cpf;
            window.location.href = `detalher.html?cpf=${cpf}`;
        });
    });

    container.querySelectorAll('[data-cpf]').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', (e) => {
            if (!e.target.closest('.btn-details')) {
                const cpf = card.dataset.cpf;
                window.location.href = `detalher.html?cpf=${cpf}`;
            }
        });
    });
};

App.searchClients = function(term) {
    this.loadClients(term);
};
