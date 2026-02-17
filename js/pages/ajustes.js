/**
 * Ajustes Page Module
 * Ondeline Tech - App do Técnico
 */

App.initAjustesPage = async function() {
    this.setupBottomNavigation();
    const user = API.getUser();

    try {
        const response = await API.getProfile();
        if (response.success) {
            const profile = response.data;
            API.setUser({ ...user, ...profile });

            const avatarEl = document.getElementById('profile-avatar');
            const nameEl = document.getElementById('profile-name');
            const emailEl = document.getElementById('profile-email');
            const roleEl = document.getElementById('profile-role');
            const cityEl = document.getElementById('profile-city');

            if (avatarEl && profile.photo) {
                avatarEl.style.backgroundImage = `url("${profile.photo}")`;
                const iconEl = avatarEl.querySelector('.material-symbols-outlined');
                if (iconEl) iconEl.style.display = 'none';
            }
            if (nameEl) nameEl.textContent = profile.full_name || profile.username;
            if (emailEl) emailEl.textContent = profile.email || 'Sem email';
            if (roleEl) {
                if (profile.cargo) {
                    roleEl.textContent = profile.cargo;
                } else {
                    const roleLabels = { admin: 'Administrador', tecnico: 'Tecnico de Campo' };
                    roleEl.textContent = roleLabels[profile.role] || profile.role || 'Tecnico';
                }
            }
            if (cityEl) cityEl.textContent = profile.city || 'Nao definida';

            const editName = document.getElementById('edit-name');
            const editEmail = document.getElementById('edit-email');
            const editCity = document.getElementById('edit-city');
            const editCargo = document.getElementById('edit-cargo');
            if (editName) editName.value = profile.full_name || '';
            if (editEmail) editEmail.value = profile.email || '';
            if (editCity) editCity.value = profile.city || '';
            if (editCargo) editCargo.value = profile.cargo || '';
        }
    } catch (error) {
        // Profile loading failed silently
    }

    // Upload de foto de perfil
    const photoInput = document.getElementById('photo-input');
    const avatarBtn = document.getElementById('profile-avatar-btn');
    if (avatarBtn && photoInput) {
        avatarBtn.addEventListener('click', () => photoInput.click());
        photoInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                App.showToast('Selecione apenas imagens', 'warning');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                App.showToast('Imagem muito grande (max 5MB)', 'warning');
                return;
            }

            App.showLoading(true);
            try {
                const response = await API.uploadProfilePhoto(file);
                if (response.success) {
                    const avatarEl = document.getElementById('profile-avatar');
                    if (avatarEl) {
                        avatarEl.style.backgroundImage = `url("${response.data.photo}")`;
                        const iconEl = avatarEl.querySelector('.material-symbols-outlined');
                        if (iconEl) iconEl.style.display = 'none';
                    }
                    const currentUser = API.getUser();
                    API.setUser({ ...currentUser, photo: response.data.photo });
                    App.showToast('Foto atualizada!', 'success');
                } else {
                    App.showToast(response.message || 'Erro ao enviar foto', 'error');
                }
            } catch (error) {
                App.showToast('Erro ao enviar foto', 'error');
            } finally {
                App.showLoading(false);
            }
        });
    }

    // Salvar perfil
    const saveBtn = document.getElementById('btn-save-profile');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const data = {
                full_name: document.getElementById('edit-name')?.value?.trim(),
                email: document.getElementById('edit-email')?.value?.trim(),
                city: document.getElementById('edit-city')?.value?.trim(),
                cargo: document.getElementById('edit-cargo')?.value?.trim()
            };

            App.showLoading(true);
            try {
                const response = await API.updateProfile(data);
                if (response.success) {
                    API.setUser({ ...API.getUser(), ...response.data });
                    const nameEl = document.getElementById('profile-name');
                    const emailEl = document.getElementById('profile-email');
                    const cityEl = document.getElementById('profile-city');
                    if (nameEl) nameEl.textContent = response.data.full_name || '';
                    if (emailEl) emailEl.textContent = response.data.email || 'Sem email';
                    if (cityEl) cityEl.textContent = response.data.city || 'Nao definida';
                    App.showToast('Perfil atualizado!', 'success');
                } else {
                    App.showToast(response.message || 'Erro ao atualizar', 'error');
                }
            } catch (error) {
                App.showToast('Erro ao atualizar perfil', 'error');
            } finally {
                App.showLoading(false);
            }
        });
    }

    // Seletor de tema
    const themeOptions = document.querySelectorAll('[data-theme-option]');
    const currentTheme = localStorage.getItem('theme') || 'auto';
    themeOptions.forEach(option => {
        const value = option.dataset.themeOption;
        if (value === currentTheme || (!localStorage.getItem('theme') && value === 'auto')) {
            option.classList.add('ring-2', 'ring-primary', 'bg-primary/5');
        }
        option.addEventListener('click', () => {
            themeOptions.forEach(o => o.classList.remove('ring-2', 'ring-primary', 'bg-primary/5'));
            option.classList.add('ring-2', 'ring-primary', 'bg-primary/5');
            if (value === 'auto') {
                localStorage.removeItem('theme');
                App.applyAutoTimeTheme();
            } else {
                localStorage.setItem('theme', value);
                App.applyTheme();
            }
        });
    });

    // Limpar cache
    const clearCacheBtn = document.getElementById('btn-clear-cache');
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', async () => {
            const confirmed = await Components.showConfirmModal({
                title: 'Limpar Cache',
                message: 'Limpar todo o cache e dados do app? Isso deslogara voce e recarregara a pagina.',
                confirmText: 'Limpar',
                icon: 'delete_sweep',
                type: 'danger'
            });
            if (confirmed) {
                App.showLoading(true);

                try {
                    if ('serviceWorker' in navigator) {
                        const registrations = await navigator.serviceWorker.getRegistrations();
                        await Promise.all(registrations.map(reg => reg.unregister()));
                    }

                    const cacheNames = await caches.keys();
                    await Promise.all(cacheNames.map(name => caches.delete(name)));

                    localStorage.clear();
                    sessionStorage.clear();

                    const dbs = await window.indexedDB?.databases?.() || [];
                    await Promise.all(dbs.map(db => {
                        return new Promise((resolve) => {
                            const req = indexedDB.deleteDatabase(db.name);
                            req.onsuccess = resolve;
                            req.onerror = resolve;
                        });
                    }));

                    App.showLoading(false);
                    App.showToast('Cache limpo! Recarregando...', 'success');

                    setTimeout(() => {
                        window.location.href = '/login.html?nocache=' + Date.now();
                    }, 1000);
                } catch (error) {
                    App.showLoading(false);
                    App.showToast('Erro ao limpar cache. Tente manualmente.', 'error');
                }
            }
        });
    }

    // Logout
    const logoutBtn = document.getElementById('btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            App.confirmLogout();
        });
    }
};
