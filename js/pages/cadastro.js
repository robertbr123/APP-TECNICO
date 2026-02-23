/**
 * Cadastro Page Module
 * Ondeline Tech - App do Técnico
 */

App.initCadastroPage = async function() {
    this.pendingPhotos = [];

    await this.loadSelectOptions();

    const backBtn = document.querySelector('[data-icon="ArrowLeft"]') ||
                   document.querySelector('.material-symbols-outlined');
    if (backBtn) {
        backBtn.closest('div').addEventListener('click', () => {
            window.history.back();
        });
    }

    const saveBtn = document.getElementById('btn-salvar-cadastro');
    const self = this;
    const fullUrl = (window.location.href + window.location.pathname).toLowerCase();
    const isNovoCadastroPage = fullUrl.indexOf('novo-cadastro') !== -1;

    if (saveBtn && !isNovoCadastroPage) {
        saveBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await self.handleSaveClient();
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
                        const estadoInput = document.getElementById('field-state');
                        if (ruaInput) ruaInput.value = data.logradouro;
                        if (cidadeInput) cidadeInput.value = data.localidade;
                        if (estadoInput) estadoInput.value = data.uf;
                    }
                } catch (error) {
                    // CEP lookup failed silently
                }
            }
        });
    }

    this.initPhotoUpload();
};

App.handleSaveClient = async function() {
    if (this._isSubmitting) return;
    this._isSubmitting = true;

    const getValue = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    };

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

    // Validação com feedback visual
    this.clearFieldErrors();

    if (!data.name) {
        this.showFieldError('field-name', 'Nome é obrigatório');
        this.showToast('Preencha o nome do cliente', 'warning');
        this._isSubmitting = false;
        return;
    }
    if (!data.cpf) {
        this.showFieldError('field-cpf', 'CPF é obrigatório');
        this.showToast('Preencha o CPF do cliente', 'warning');
        this._isSubmitting = false;
        return;
    }

    const cpfValidation = Utils.validateCPF(data.cpf);
    if (!cpfValidation.valid) {
        this.showFieldError('field-cpf', cpfValidation.message || 'CPF inválido');
        this.showToast(cpfValidation.message || 'CPF inválido', 'error');
        this._isSubmitting = false;
        return;
    }

    this.showLoading(true);

    try {
        if (!navigator.onLine) {
            const cpf = data.cpf.replace(/\D/g, '');
            Utils.saveOffline('create_client', data);

            if (this.pendingPhotos && this.pendingPhotos.length > 0) {
                this.pendingPhotos.forEach(p => {
                    Utils.saveOffline('upload_photo', {
                        type: p.type,
                        base64: p.base64,
                        client_cpf: cpf
                    });
                });
            }

            this.showToast('Salvo offline! Será sincronizado quando reconectar.', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1000);
            return;
        }

        const response = await API.createClient(data);

        if (response.success) {
            const cpf = data.cpf.replace(/\D/g, '');
            const photoResult = await this.uploadPendingPhotos(cpf);

            if (photoResult.uploaded > 0) {
                this.showToast(`Cliente cadastrado com ${photoResult.uploaded} foto(s)!`, 'success');
            } else {
                this.showToast('Cliente cadastrado com sucesso!', 'success');
            }

            setTimeout(() => {
                window.location.href = `checklist.html?client_cpf=${cpf}&client_name=${encodeURIComponent(data.name)}`;
            }, 1000);
        } else {
            this.showToast(response.message || 'Erro ao cadastrar', 'error');
        }
    } catch (error) {
        this.showToast(error.message || 'Erro ao salvar cliente', 'error');
    } finally {
        this.showLoading(false);
        this._isSubmitting = false;
    }
};

App.initPhotoUpload = function() {
    const photoUploads = document.querySelectorAll('.photo-upload');

    photoUploads.forEach(container => {
        const input = container.querySelector('input[type="file"]');
        const preview = container.querySelector('.photo-preview');
        const icon = container.querySelector('.photo-icon');
        const addBtn = container.querySelector('.photo-add-btn');
        const removeBtn = container.querySelector('.photo-remove-btn');
        const photoType = container.dataset.type;

        if (!input) return;

        input.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                App.showToast('Selecione apenas imagens', 'warning');
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                App.showToast('Imagem muito grande (máx 10MB)', 'warning');
                return;
            }

            try {
                const compressed = await Utils.compressImage(file, {
                    maxWidth: 1200,
                    maxHeight: 1200,
                    quality: 0.7
                });

                preview.src = compressed;
                preview.classList.remove('hidden');
                icon.classList.add('hidden');
                addBtn.classList.add('hidden');
                removeBtn.classList.remove('hidden');
                removeBtn.classList.add('flex');

                App.pendingPhotos = App.pendingPhotos.filter(p => p.type !== photoType);
                App.pendingPhotos.push({
                    type: photoType,
                    file: file,
                    base64: compressed
                });
            } catch (err) {
                console.error('Erro ao comprimir imagem:', err);
                App.showToast('Erro ao processar imagem', 'error');
            }
        });

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

                App.pendingPhotos = App.pendingPhotos.filter(p => p.type !== photoType);
            });
        }
    });
};

App.uploadPendingPhotos = async function(cpf) {
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
            errors.push(photo.type);
        }
    }

    return { success: errors.length === 0, uploaded, errors };
};

App.loadSelectOptions = async function() {
    try {
        const [plansResponse, installersResponse] = await Promise.all([
            API.getPlans(),
            API.getInstallers()
        ]);

        const planSelect = document.getElementById('field-plan');
        if (planSelect && plansResponse.success && plansResponse.data.length > 0) {
            planSelect.innerHTML = plansResponse.data.map(plan =>
                `<option value="${plan.name}">${plan.name}</option>`
            ).join('');
        }
    } catch (error) {
        // Options loading failed silently
    }
};
