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
                    // Atualiza o token se cidade foi alterada (garante filtro de município imediato)
                    if (response.token) {
                        API.setToken(response.token);
                    }
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
            const confirmed = await AppComponents.showConfirmModal({
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
                        window.location.href = '/login.php?nocache=' + Date.now();
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

    App.initPushNotifications();
    App.initOfflineStatus();
};

// ==========================================
// PUSH NOTIFICATIONS
// ==========================================
App.initPushNotifications = async function() {
    const btn        = document.getElementById('btn-push-toggle');
    const statusText = document.getElementById('push-status-text');
    const iconWrap   = document.getElementById('push-icon-wrap');
    if (!btn || !statusText) return;

    const VAPID_PUBLIC_KEY = 'BCxBeZ9LpHe2nfk3QMHYdNrXdxB7E2hyIefVm7u6yGN5js0nvxXiNGRQE8FZC5E5MiHzqHDSAl1JIkvRrw25iMU';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
    }

    if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        statusText.textContent = 'Não suportado neste dispositivo';
        return;
    }

    const dot = document.getElementById('push-toggle-dot');

    const setOn = () => {
        btn.classList.remove('bg-gray-200');
        btn.classList.add('bg-primary');
        if (dot) { dot.classList.remove('translate-x-0.5'); dot.classList.add('translate-x-5'); }
        statusText.textContent = 'Ativas — você receberá alertas de OS e sincronização';
        btn.disabled = false;
    };

    const setOff = () => {
        btn.classList.remove('bg-primary');
        btn.classList.add('bg-gray-200');
        if (dot) { dot.classList.remove('translate-x-5'); dot.classList.add('translate-x-0.5'); }
        statusText.textContent = 'Desativadas — toque para ativar';
        btn.disabled = false;
    };

    if (Notification.permission === 'denied') {
        statusText.textContent = 'Bloqueadas — habilite nas configurações do navegador';
        btn.disabled = true;
        return;
    }

    try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        sub ? setOn() : setOff();
    } catch {
        setOff();
    }

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        const isOn = btn.classList.contains('bg-primary');
        const token = localStorage.getItem('auth_token');

        if (isOn) {
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) await sub.unsubscribe();
                await fetch('/api/push.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token }
                });
                setOff();
                App.showToast('Notificações desativadas', 'info');
            } catch (e) {
                App.showToast('Erro ao desativar: ' + e.message, 'error');
                btn.disabled = false;
            }
        } else {
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    statusText.textContent = 'Permissão negada — habilite nas configurações';
                    btn.disabled = true;
                    return;
                }
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                });
                const res  = await fetch('/api/push.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: JSON.stringify({ action: 'subscribe', subscription: sub.toJSON() })
                });
                const data = await res.json();
                if (data.success) {
                    setOn();
                    App.showToast('Notificações ativadas!', 'success');
                } else {
                    throw new Error(data.message || 'Erro no servidor');
                }
            } catch (e) {
                App.showToast('Erro ao ativar: ' + e.message, 'error');
                btn.disabled = false;
            }
        }
    });
};

// ==========================================
// STATUS OFFLINE / FILA PENDENTE
// ==========================================
App.initOfflineStatus = function() {
    const countEl    = document.getElementById('offline-queue-count');
    const syncBtn    = document.getElementById('btn-sync-now');
    const statusIcon = document.getElementById('online-status-icon');
    const statusText = document.getElementById('online-status-text');
    const iconWrap   = document.getElementById('online-icon-wrap');

    const updateConnectionUI = (online) => {
        if (!statusIcon || !statusText) return;
        if (online) {
            statusIcon.textContent = 'wifi';
            statusIcon.className   = 'material-symbols-outlined text-green-500 text-xl';
            statusText.textContent = 'Online — dados sincronizados automaticamente';
            if (iconWrap) iconWrap.className = 'size-10 rounded-full bg-green-50 dark:bg-green-500/10 flex items-center justify-center flex-shrink-0';
        } else {
            statusIcon.textContent = 'wifi_off';
            statusIcon.className   = 'material-symbols-outlined text-red-500 text-xl';
            statusText.textContent = 'Offline — dados serão enviados ao reconectar';
            if (iconWrap) iconWrap.className = 'size-10 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0';
        }
    };

    const updateQueueUI = () => {
        if (!countEl) return;
        try {
            const queue = JSON.parse(localStorage.getItem('offlineQueue') || '[]');
            const count = queue.length;
            if (count === 0) {
                countEl.textContent = 'Nenhum item pendente';
                if (syncBtn) syncBtn.classList.add('hidden');
            } else {
                countEl.textContent = count + ' item(s) aguardando sincronização';
                if (syncBtn) syncBtn.classList.remove('hidden');
            }
        } catch {
            countEl.textContent = 'Nenhum item pendente';
        }
    };

    updateConnectionUI(navigator.onLine);
    updateQueueUI();

    window.addEventListener('online',  () => { updateConnectionUI(true);  updateQueueUI(); });
    window.addEventListener('offline', () => { updateConnectionUI(false); updateQueueUI(); });

    if (syncBtn) {
        syncBtn.addEventListener('click', async () => {
            syncBtn.disabled    = true;
            syncBtn.textContent = 'Sincronizando...';
            if (typeof syncOfflineQueue === 'function') await syncOfflineQueue();
            updateQueueUI();
            syncBtn.disabled    = false;
            syncBtn.textContent = 'Sincronizar';
        });
    }
};
