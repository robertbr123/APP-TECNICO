/**
 * Detalhes Page Module
 * Ondeline Tech - App do Técnico
 */

App.initDetalhesPage = async function() {
    const urlParams = new URLSearchParams(window.location.search);
    const cpf = urlParams.get('cpf');

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
    this.showLoading(false);
};

App.loadClientPhotos = async function(cpf) {
    const container = document.getElementById('photos-container');
    const countEl = document.getElementById('photo-count');

    if (!container) return;

    try {
        const response = await API.getPhotos(cpf);

        if (response.success && response.data.length > 0) {
            const photos = response.data;
            countEl.textContent = `${photos.length} foto(s)`;

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
        countEl.textContent = 'Erro';
        container.innerHTML = `
            <div class="w-full h-24 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-400">
                <span class="text-xs">Erro ao carregar fotos</span>
            </div>
        `;
    }
};

App.renderClientDetails = function(client) {
    const nameEl = document.querySelector('h2');
    if (nameEl) nameEl.textContent = client.name;

    const cpfEl = document.querySelector('p.text-sm');
    if (cpfEl) cpfEl.textContent = `CPF: ${this.formatCPF(client.cpf)}`;

    const planEl = document.querySelector('.font-semibold');
    if (planEl) planEl.textContent = client.plan;

    const dueEl = document.querySelectorAll('.font-semibold')[1];
    if (dueEl) dueEl.textContent = `Todo dia ${client.due_date}`;

    const addressEl = document.querySelector('.leading-relaxed');
    if (addressEl) {
        addressEl.innerHTML = `
            ${client.address}, ${client.number}${client.complement ? `, ${client.complement}` : ''}<br/>
            ${client.city}<br/>
        `;
    }

    const obsEl = document.querySelector('.italic');
    if (obsEl) obsEl.textContent = `"${client.observation || 'Sem observações'}"`;
};
