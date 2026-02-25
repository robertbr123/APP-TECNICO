// =====================================================
// Sistema de Feedback Visual - Toasts e Loading
// =====================================================

// Cria elemento de toast se não existir
function ensureToastContainer() {
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2 p-4 pointer-events-none';
        document.body.appendChild(container);
    }
    return container;
}

// Mostra um toast notification
function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration || 3000;
    
    var container = ensureToastContainer();
    
    // Define ícone e cor baseado no tipo
    var config = {
        info: {
            icon: 'info',
            bgColor: 'bg-blue-500',
            iconColor: 'text-blue-500',
            bgLight: 'bg-blue-50'
        },
        success: {
            icon: 'check_circle',
            bgColor: 'bg-green-500',
            iconColor: 'text-green-500',
            bgLight: 'bg-green-50'
        },
        warning: {
            icon: 'warning',
            bgColor: 'bg-yellow-500',
            iconColor: 'text-yellow-500',
            bgLight: 'bg-yellow-50'
        },
        error: {
            icon: 'error',
            bgColor: 'bg-red-500',
            iconColor: 'text-red-500',
            bgLight: 'bg-red-50'
        }
    }[type] || config.info;
    
    var toast = document.createElement('div');
    toast.className = 'toast pointer-events-auto min-w-[300px] max-w-md ' +
        'bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 ' +
        'p-4 flex items-start gap-3 transform translate-x-full opacity-0 transition-all duration-300 ease-out';
    
    toast.innerHTML = 
        '<div class="size-8 rounded-full ' + config.bgLight + ' dark:bg-gray-700 flex items-center justify-center flex-shrink-0">' +
            '<span class="material-symbols-outlined ' + config.iconColor + '">' + config.icon + '</span>' +
        '</div>' +
        '<div class="flex-1 min-w-0">' +
            '<p class="text-sm font-medium text-gray-900 dark:text-white break-words">' + message + '</p>' +
        '</div>' +
        '<button class="close-toast text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0">' +
            '<span class="material-symbols-outlined text-lg">close</span>' +
        '</button>';
    
    container.appendChild(toast);
    
    // Anima entrada
    requestAnimationFrame(function() {
        toast.classList.remove('translate-x-full', 'opacity-0');
    });
    
    // Botão fechar
    toast.querySelector('.close-toast').onclick = function() {
        hideToast(toast);
    };
    
    // Auto-hide após duration
    var timeout = setTimeout(function() {
        hideToast(toast);
    }, duration);
    
    // Pausa em hover
    toast.onmouseenter = function() {
        clearTimeout(timeout);
    };
    
    toast.onmouseleave = function() {
        timeout = setTimeout(function() {
            hideToast(toast);
        }, duration);
    };
}

// Esconde um toast
function hideToast(toast) {
    toast.classList.add('translate-x-full', 'opacity-0');
    setTimeout(function() {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

// Atalhos convenientes
function showSuccess(message, duration) {
    showToast(message, 'success', duration);
}

function showError(message, duration) {
    showToast(message, 'error', duration);
}

function showWarning(message, duration) {
    showToast(message, 'warning', duration);
}

function showInfo(message, duration) {
    showToast(message, 'info', duration);
}

// =====================================================
// Loading Overlay
// =====================================================

// Cria elemento de loading se não existir
function ensureLoadingOverlay() {
    var overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'fixed inset-0 z-[9998] bg-black/50 backdrop-blur-sm flex items-center justify-center hidden';
        overlay.innerHTML = 
            '<div class="bg-white dark:bg-gray-800 rounded-2xl p-8 flex flex-col items-center gap-4 shadow-2xl">' +
                '<div class="size-16 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>' +
                '<p class="text-gray-900 dark:text-white font-medium text-lg">Carregando...</p>' +
                '<p class="text-gray-500 dark:text-gray-400 text-sm" id="loading-message">Aguarde um momento</p>' +
            '</div>';
        document.body.appendChild(overlay);
    }
    return overlay;
}

// Mostra loading
function showLoading(message) {
    var overlay = ensureLoadingOverlay();
    if (message) {
        var messageEl = document.getElementById('loading-message');
        if (messageEl) messageEl.textContent = message;
    }
    overlay.classList.remove('hidden');
}

// Esconde loading
function hideLoading() {
    var overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

// Wrapper para promessas com loading
function withLoading(promise, message) {
    showLoading(message);
    return promise.finally(function() {
        hideLoading();
    });
}

// =====================================================
// Indicador de Status de Conexão (Persistente)
// =====================================================

// Cria indicador de conexão persistente no header
function ensureConnectionIndicator() {
    var indicator = document.getElementById('connection-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'connection-indicator';
        indicator.className = 'fixed top-0 left-0 right-0 z-[10000] transition-all duration-300 transform -translate-y-full';
        indicator.innerHTML = 
            '<div class="bg-red-500 text-white text-center py-2 px-4 flex items-center justify-center gap-2 safe-top">' +
                '<span class="material-symbols-outlined text-sm animate-pulse">cloud_off</span>' +
                '<span class="text-xs font-medium">Você está offline - Os dados serão salvos localmente</span>' +
            '</div>';
        document.body.appendChild(indicator);
    }
    return indicator;
}

// Cria mini indicador de conexão no canto
function ensureMiniConnectionIndicator() {
    var mini = document.getElementById('connection-mini-indicator');
    if (!mini) {
        mini = document.createElement('div');
        mini.id = 'connection-mini-indicator';
        mini.className = 'fixed top-20 right-4 z-[9999] flex items-center gap-2 px-3 py-2 rounded-full ' +
            'bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-300 opacity-0 pointer-events-none';
        mini.innerHTML = 
            '<div class="size-2 rounded-full bg-green-500" id="connection-dot"></div>' +
            '<span class="text-xs font-medium text-gray-700 dark:text-gray-300" id="connection-text">Online</span>';
        document.body.appendChild(mini);
    }
    return mini;
}

// Atualiza status da conexão com indicador persistente
function updateConnectionStatus(online) {
    var banner = document.getElementById('connection-indicator');
    var mini = document.getElementById('connection-mini-indicator');
    var dot = document.getElementById('connection-dot');
    var text = document.getElementById('connection-text');
    
    if (!banner) banner = ensureConnectionIndicator();
    if (!mini) mini = ensureMiniConnectionIndicator();
    
    if (online) {
        // Esconde o banner offline
        banner.classList.add('-translate-y-full');
        
        // Mostra mini indicador brevemente
        if (dot) {
            dot.className = 'size-2 rounded-full bg-green-500';
        }
        if (text) {
            text.textContent = 'Online';
            text.className = 'text-xs font-medium text-green-600 dark:text-green-400';
        }
        mini.classList.remove('opacity-0', 'pointer-events-none');
        
        // Esconde após 3 segundos
        setTimeout(function() {
            mini.classList.add('opacity-0', 'pointer-events-none');
        }, 3000);
    } else {
        // Mostra banner offline no topo
        banner.classList.remove('-translate-y-full');
        
        // Mostra mini indicador permanente
        if (dot) {
            dot.className = 'size-2 rounded-full bg-red-500 animate-pulse';
        }
        if (text) {
            text.textContent = 'Offline';
            text.className = 'text-xs font-medium text-red-600 dark:text-red-400';
        }
        mini.classList.remove('opacity-0', 'pointer-events-none');
    }
}

// Inicia monitoramento de conexão
function startConnectionMonitoring() {
    ensureConnectionIndicator();
    ensureMiniConnectionIndicator();
    updateConnectionStatus(navigator.onLine);

    // Mostra badge se há itens pendentes
    updateOfflineQueueBadge();

    // Eventos de mudança de status
    window.addEventListener('online', function() {
        updateConnectionStatus(true);
        showSuccess('Conexão restaurada!');
        
        // Agenda sync com pequeno delay para estabilizar conexão
        setTimeout(function() {
            if (navigator.onLine) {
                showInfo('Sincronizando dados...');
                syncOfflineQueue();
            }
        }, 1500);
    });

    window.addEventListener('offline', function() {
        updateConnectionStatus(false);
        showWarning('Você está offline. Os dados serão salvos localmente.');
        updateOfflineQueueBadge();
    });
    
    // Retry automático com backoff para itens falhados
    setInterval(function() {
        if (navigator.onLine && getOfflineQueue().length > 0) {
            syncOfflineQueue();
        }
    }, 60000); // Retry a cada 1 minuto
}

// =====================================================
// Offline Storage Helpers
// =====================================================

// Salva dados offline
function saveOffline(actionType, data) {
    try {
        var offlineData = JSON.parse(localStorage.getItem('offlineQueue') || '[]');
        var username = localStorage.getItem('username') || 'unknown';
        var userId = localStorage.getItem('userId');
        
        offlineData.push({
            id: Date.now(),
            action_type: actionType,
            data: data,
            username: username,
            user_id: userId,
            created_at: new Date().toISOString()
        });
        
        localStorage.setItem('offlineQueue', JSON.stringify(offlineData));
        return true;
    } catch (error) {
        console.error('Erro ao salvar offline:', error);
        return false;
    }
}

// Obter fila offline
function getOfflineQueue() {
    try {
        return JSON.parse(localStorage.getItem('offlineQueue') || '[]');
    } catch (error) {
        return [];
    }
}

// Limpar item da fila offline
function clearOfflineItem(id) {
    try {
        var offlineData = getOfflineQueue();
        offlineData = offlineData.filter(function(item) {
            return item.id !== id;
        });
        localStorage.setItem('offlineQueue', JSON.stringify(offlineData));
        return true;
    } catch (error) {
        console.error('Erro ao limpar item offline:', error);
        return false;
    }
}

// =====================================================
// Sincronização Offline com Retry e Backoff Exponencial
// =====================================================

// Máximo de tentativas por item
const MAX_SYNC_RETRIES = 3;

// Valida item offline antes de sincronizar
function validateOfflineItem(item) {
    if (!item || !item.action_type || !item.data) {
        return { valid: false, error: 'Item inválido ou incompleto' };
    }
    
    switch (item.action_type) {
        case 'create_client':
            if (!item.data.cpf || !item.data.name) {
                return { valid: false, error: 'Cliente sem CPF ou nome' };
            }
            break;
        case 'update_client':
            if (!item.data.cpf) {
                return { valid: false, error: 'Atualização sem CPF' };
            }
            break;
        case 'delete_client':
            if (!item.data.cpf) {
                return { valid: false, error: 'Exclusão sem CPF' };
            }
            break;
        case 'link_equipment':
            if (!item.data.serial || !item.data.cpf) {
                return { valid: false, error: 'Equipamento sem serial ou CPF' };
            }
            break;
    }
    
    return { valid: true };
}

// Verifica se token JWT está expirado
function isTokenExpired() {
    try {
        var token = localStorage.getItem('auth_token');
        if (!token) return true;
        
        // Decodifica payload do JWT (parte do meio)
        var parts = token.split('.');
        if (parts.length !== 3) return true;
        
        var payload = JSON.parse(atob(parts[1]));
        if (!payload.exp) return false; // Sem expiração definida
        
        // Verifica se expirou (com margem de 5 minutos)
        var now = Math.floor(Date.now() / 1000);
        return payload.exp < (now + 300);
    } catch (e) {
        return true;
    }
}

// Calcula delay com backoff exponencial
function getBackoffDelay(attempt) {
    return Math.min(1000 * Math.pow(2, attempt), 30000); // Max 30s
}

// Fila de conflitos pendentes para resolução
var pendingConflicts = [];

// Sincroniza fila offline com feedback visual e retry
async function syncOfflineQueue() {
    if (!navigator.onLine) return;
    
    // Verifica se token está expirado
    if (isTokenExpired()) {
        showWarning('Sessão expirada. Faça login novamente para sincronizar.');
        return;
    }

    var queue = getOfflineQueue();
    if (queue.length === 0) {
        updateOfflineQueueBadge(0);
        return;
    }

    var total = queue.length;
    var synced = 0;
    var failed = 0;
    var conflicts = 0;
    var failedItems = [];
    pendingConflicts = [];

    showSyncProgress(total, 0, 0);

    for (var i = 0; i < queue.length; i++) {
        var item = queue[i];
        
        // Valida item antes de sincronizar
        var validation = validateOfflineItem(item);
        if (!validation.valid) {
            console.warn('Item offline inválido:', validation.error, item);
            clearOfflineItem(item.id);
            failed++;
            updateSyncProgress(total, synced, failed);
            continue;
        }
        
        // Inicializa contador de tentativas
        item.attempts = item.attempts || 0;

        var result = await syncSingleItem(item);
        
        if (result.success) {
            clearOfflineItem(item.id);
            synced++;
        } else if (result.conflict) {
            // Conflito detectado - adiciona à lista para resolução
            conflicts++;
            pendingConflicts.push({
                id: item.id,
                localData: result.localData,
                serverData: result.serverData,
                item: item,
                action_type: item.action_type
            });
        } else {
            item.attempts++;
            if (item.attempts >= MAX_SYNC_RETRIES) {
                failed++;
                failedItems.push(item);
            } else {
                // Salva com contador de tentativas incrementado
                updateOfflineItem(item);
            }
        }
        updateSyncProgress(total, synced, failed);
    }

    hideSyncProgress();
    updateOfflineQueueBadge(getOfflineQueue().length);

    // Mostra modal de conflitos se houver
    if (conflicts > 0) {
        showConflictResolver();
        return;
    }

    if (failed === 0 && synced > 0) {
        showSuccess('Todos os ' + synced + ' itens foram sincronizados!');
    } else if (failed > 0) {
        showWarning(synced + ' sincronizados, ' + failed + ' falharam após ' + MAX_SYNC_RETRIES + ' tentativas.');
    }
}

// Sincroniza um único item com tratamento de erro robusto e detecção de conflitos
async function syncSingleItem(item) {
    try {
        var token = localStorage.getItem('auth_token');
        var headers = {
            'Content-Type': 'application/json'
        };
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        var url = '';
        var method = 'POST';

        switch (item.action_type) {
            case 'create_client':
                url = '/api/clients.php';
                break;
            case 'update_client':
                url = '/api/clients.php';
                method = 'PUT';
                break;
            case 'delete_client':
                url = '/api/clients.php';
                method = 'DELETE';
                break;
            case 'link_equipment':
                url = '/api/inventory.php';
                break;
            case 'unlink_equipment':
                url = '/api/inventory.php';
                method = 'DELETE';
                break;
            case 'upload_photo':
                url = '/api/upload.php';
                break;
            default:
                // Ação desconhecida, remove da fila
                return { success: true };
        }

        var response = await fetch(url, {
            method: method,
            headers: headers,
            body: JSON.stringify(item.data)
        });

        // Verifica erro de autenticação
        if (response.status === 401) {
            showWarning('Sessão expirada. Faça login novamente.');
            return { success: false, error: 'auth' };
        }
        
        // Verifica conflito (409 Conflict)
        if (response.status === 409) {
            var conflictData = await response.json();
            return { 
                success: false, 
                error: 'conflict',
                conflict: true,
                serverData: conflictData.data || conflictData.server_data,
                localData: item.data,
                item: item
            };
        }

        // Tenta parsear JSON com tratamento de erro
        var result;
        var responseText = await response.text();
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Resposta não é JSON válido:', responseText);
            return { success: false, error: 'parse' };
        }
        
        // Verifica se servidor retornou indicação de conflito
        if (result.conflict === true) {
            return {
                success: false,
                error: 'conflict',
                conflict: true,
                serverData: result.data || result.server_data,
                localData: item.data,
                item: item
            };
        }

        return { success: result.success === true };
    } catch (error) {
        console.error('Erro ao sincronizar item:', error);
        return { success: false, error: 'network' };
    }
}

// Atualiza item na fila offline (para incrementar attempts)
function updateOfflineItem(updatedItem) {
    try {
        var queue = getOfflineQueue();
        for (var i = 0; i < queue.length; i++) {
            if (queue[i].id === updatedItem.id) {
                queue[i] = updatedItem;
                break;
            }
        }
        localStorage.setItem('offlineQueue', JSON.stringify(queue));
    } catch (e) {
        console.error('Erro ao atualizar item offline:', e);
    }
}

// =====================================================
// Sync Progress UI
// =====================================================

function showSyncProgress(total, synced, failed) {
    var container = document.getElementById('sync-progress');
    if (!container) {
        container = document.createElement('div');
        container.id = 'sync-progress';
        container.className = 'fixed bottom-20 left-4 right-4 z-[9997] bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-4 transition-all duration-300';
        document.body.appendChild(container);
    }
    container.classList.remove('hidden');
    updateSyncProgress(total, synced, failed);
}

function updateSyncProgress(total, synced, failed) {
    var container = document.getElementById('sync-progress');
    if (!container) return;

    var progress = total > 0 ? Math.round(((synced + failed) / total) * 100) : 0;

    container.innerHTML =
        '<div class="flex items-center gap-3 mb-2">' +
            '<div class="size-8 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">' +
                '<span class="material-symbols-outlined text-blue-500 text-lg" style="animation: spin 1s linear infinite">sync</span>' +
            '</div>' +
            '<div class="flex-1">' +
                '<p class="text-sm font-medium text-gray-900 dark:text-white">Sincronizando dados...</p>' +
                '<p class="text-xs text-gray-500 dark:text-gray-400">' + (synced + failed) + ' de ' + total + ' itens</p>' +
            '</div>' +
            '<span class="text-sm font-bold text-blue-500">' + progress + '%</span>' +
        '</div>' +
        '<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">' +
            '<div class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: ' + progress + '%"></div>' +
        '</div>' +
        (failed > 0 ? '<p class="text-xs text-red-500 mt-1">' + failed + ' item(ns) falharam</p>' : '');
}

function hideSyncProgress() {
    var container = document.getElementById('sync-progress');
    if (container) {
        setTimeout(function() {
            container.classList.add('hidden');
        }, 2000);
    }
}

// =====================================================
// Offline Queue Badge
// =====================================================

function updateOfflineQueueBadge(count) {
    if (count === undefined) {
        count = getOfflineQueue().length;
    }
    var badge = document.getElementById('offline-queue-badge');
    if (count === 0) {
        if (badge) badge.remove();
        return;
    }
    if (!badge) {
        badge = document.createElement('div');
        badge.id = 'offline-queue-badge';
        badge.className = 'fixed top-20 left-4 z-[9998] flex items-center gap-2 px-3 py-2 rounded-full bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 cursor-pointer transition-all hover:scale-105';
        badge.onclick = function() { showSyncDashboard(); };
        document.body.appendChild(badge);
    }
    badge.innerHTML =
        '<span class="material-symbols-outlined text-yellow-600 text-sm">cloud_upload</span>' +
        '<span class="text-xs font-medium text-yellow-700 dark:text-yellow-400">' + count + ' pendente(s)</span>' +
        '<span class="text-xs text-yellow-500">Ver detalhes</span>';
}

// =====================================================
// Dashboard de Sincronização
// =====================================================

function showSyncDashboard() {
    var overlay = document.getElementById('sync-dashboard-overlay');
    var dashboard = document.getElementById('sync-dashboard');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sync-dashboard-overlay';
        overlay.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] transition-opacity duration-300';
        overlay.onclick = function() { hideSyncDashboard(); };
        document.body.appendChild(overlay);
    }
    
    if (!dashboard) {
        dashboard = document.createElement('div');
        dashboard.id = 'sync-dashboard';
        dashboard.className = 'fixed inset-x-4 top-1/2 -translate-y-1/2 z-[10000] max-w-lg mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden transition-all duration-300 transform scale-95 opacity-0';
        document.body.appendChild(dashboard);
    }
    
    updateSyncDashboard();
    
    // Anima entrada
    requestAnimationFrame(function() {
        overlay.classList.add('opacity-100');
        dashboard.classList.remove('scale-95', 'opacity-0');
        dashboard.classList.add('scale-100', 'opacity-100');
    });
}

function hideSyncDashboard() {
    var overlay = document.getElementById('sync-dashboard-overlay');
    var dashboard = document.getElementById('sync-dashboard');
    
    if (overlay) {
        overlay.classList.remove('opacity-100');
        setTimeout(function() { overlay.remove(); }, 300);
    }
    if (dashboard) {
        dashboard.classList.add('scale-95', 'opacity-0');
        setTimeout(function() { dashboard.remove(); }, 300);
    }
}

function updateSyncDashboard() {
    var dashboard = document.getElementById('sync-dashboard');
    if (!dashboard) return;
    
    var queue = getOfflineQueue();
    var isOnline = navigator.onLine;
    var tokenExpired = isTokenExpired();
    
    // Agrupa por tipo de ação
    var grouped = {};
    queue.forEach(function(item) {
        var type = item.action_type || 'unknown';
        if (!grouped[type]) grouped[type] = [];
        grouped[type].push(item);
    });
    
    var actionLabels = {
        'create_client': { label: 'Novos Clientes', icon: 'person_add', color: 'green' },
        'update_client': { label: 'Atualizações', icon: 'edit', color: 'blue' },
        'delete_client': { label: 'Exclusões', icon: 'delete', color: 'red' },
        'link_equipment': { label: 'Vinculações', icon: 'link', color: 'purple' },
        'unlink_equipment': { label: 'Desvinculações', icon: 'link_off', color: 'orange' },
        'upload_photo': { label: 'Fotos', icon: 'photo_camera', color: 'cyan' },
        'unknown': { label: 'Outros', icon: 'help', color: 'gray' }
    };
    
    var itemsHtml = '';
    Object.keys(grouped).forEach(function(type) {
        var config = actionLabels[type] || actionLabels['unknown'];
        var items = grouped[type];
        
        itemsHtml += 
            '<div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-800">' +
                '<div class="flex items-center gap-3">' +
                    '<div class="size-10 rounded-full bg-' + config.color + '-100 dark:bg-' + config.color + '-900/30 flex items-center justify-center">' +
                        '<span class="material-symbols-outlined text-' + config.color + '-500 text-lg">' + config.icon + '</span>' +
                    '</div>' +
                    '<div>' +
                        '<p class="text-sm font-medium text-gray-900 dark:text-white">' + config.label + '</p>' +
                        '<p class="text-xs text-gray-500">' + items.length + ' item(ns)</p>' +
                    '</div>' +
                '</div>' +
                '<button onclick="clearOfflineItemsByType(\'' + type + '\')" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Remover todos">' +
                    '<span class="material-symbols-outlined text-lg">delete_sweep</span>' +
                '</button>' +
            '</div>';
    });
    
    if (queue.length === 0) {
        itemsHtml = 
            '<div class="py-8 text-center">' +
                '<span class="material-symbols-outlined text-5xl text-green-500 mb-2">cloud_done</span>' +
                '<p class="text-gray-500 dark:text-gray-400">Tudo sincronizado!</p>' +
            '</div>';
    }
    
    var statusColor = isOnline ? (tokenExpired ? 'yellow' : 'green') : 'red';
    var statusText = isOnline ? (tokenExpired ? 'Sessão expirada' : 'Online') : 'Offline';
    var statusIcon = isOnline ? (tokenExpired ? 'warning' : 'wifi') : 'wifi_off';
    
    dashboard.innerHTML = 
        '<div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4">' +
            '<div class="flex items-center justify-between">' +
                '<div class="flex items-center gap-3">' +
                    '<span class="material-symbols-outlined text-white text-2xl">sync</span>' +
                    '<div>' +
                        '<h2 class="text-lg font-bold text-white">Fila de Sincronização</h2>' +
                        '<p class="text-sm text-white/80">' + queue.length + ' item(ns) pendente(s)</p>' +
                    '</div>' +
                '</div>' +
                '<button onclick="hideSyncDashboard()" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors">' +
                    '<span class="material-symbols-outlined">close</span>' +
                '</button>' +
            '</div>' +
        '</div>' +
        
        '<div class="p-4">' +
            // Status de conexão
            '<div class="flex items-center gap-3 p-3 rounded-xl bg-' + statusColor + '-50 dark:bg-' + statusColor + '-900/20 mb-4">' +
                '<span class="material-symbols-outlined text-' + statusColor + '-500">' + statusIcon + '</span>' +
                '<div class="flex-1">' +
                    '<p class="text-sm font-medium text-' + statusColor + '-700 dark:text-' + statusColor + '-400">' + statusText + '</p>' +
                    '<p class="text-xs text-' + statusColor + '-600 dark:text-' + statusColor + '-500">' + 
                        (isOnline ? (tokenExpired ? 'Faça login novamente para sincronizar' : 'Pronto para sincronizar') : 'Aguardando conexão...') + 
                    '</p>' +
                '</div>' +
            '</div>' +
            
            // Lista de itens
            '<div class="max-h-64 overflow-y-auto">' +
                itemsHtml +
            '</div>' +
            
            // Ações
            '<div class="flex gap-3 mt-4">' +
                '<button onclick="syncOfflineQueue(); updateSyncDashboard();" ' +
                    'class="flex-1 py-3 px-4 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white rounded-xl font-semibold flex items-center justify-center gap-2 transition-colors" ' +
                    ((!isOnline || tokenExpired || queue.length === 0) ? 'disabled' : '') + '>' +
                    '<span class="material-symbols-outlined text-lg">sync</span>' +
                    'Sincronizar Agora' +
                '</button>' +
                '<button onclick="clearAllOfflineItems(); updateSyncDashboard();" ' +
                    'class="py-3 px-4 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl font-semibold flex items-center justify-center gap-2 transition-colors" ' +
                    (queue.length === 0 ? 'disabled' : '') + '>' +
                    '<span class="material-symbols-outlined text-lg">delete</span>' +
                '</button>' +
            '</div>' +
            
            // Info de último sync
            '<p class="text-xs text-center text-gray-400 mt-3">' +
                'Retry automático: a cada 1 minuto quando online' +
            '</p>' +
        '</div>';
}

// Limpa itens por tipo
function clearOfflineItemsByType(type) {
    if (!confirm('Remover todos os itens de "' + type + '"? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        var queue = getOfflineQueue();
        queue = queue.filter(function(item) {
            return item.action_type !== type;
        });
        localStorage.setItem('offlineQueue', JSON.stringify(queue));
        updateOfflineQueueBadge();
        updateSyncDashboard();
        showSuccess('Itens removidos!');
    } catch (e) {
        showError('Erro ao remover itens');
    }
}

// Limpa todos os itens offline
function clearAllOfflineItems() {
    if (!confirm('Remover TODOS os itens pendentes? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        localStorage.setItem('offlineQueue', '[]');
        updateOfflineQueueBadge();
        updateSyncDashboard();
        showSuccess('Fila limpa!');
    } catch (e) {
        showError('Erro ao limpar fila');
    }
}

// =====================================================
// Sistema de Resolução de Conflitos
// =====================================================

var currentConflictIndex = 0;

// Labels para campos do cliente
var fieldLabels = {
    name: 'Nome',
    cpf: 'CPF',
    phone: 'Telefone',
    email: 'Email',
    cep: 'CEP',
    address: 'Endereço',
    number: 'Número',
    complement: 'Complemento',
    neighborhood: 'Bairro',
    city: 'Cidade',
    state: 'Estado',
    planId: 'Plano',
    plan: 'Plano',
    pppoe: 'PPPoE Usuário',
    pppoe_user: 'PPPoE Usuário',
    password: 'PPPoE Senha',
    pppoe_pass: 'PPPoE Senha',
    dueDay: 'Dia Vencimento',
    due_date: 'Dia Vencimento',
    observation: 'Observação',
    serial: 'Serial',
    birthDate: 'Data Nascimento',
    dob: 'Data Nascimento'
};

// Mostra modal de resolução de conflitos
function showConflictResolver() {
    if (pendingConflicts.length === 0) {
        showSuccess('Todos os conflitos foram resolvidos!');
        return;
    }
    
    currentConflictIndex = 0;
    renderConflictModal();
}

function renderConflictModal() {
    var conflict = pendingConflicts[currentConflictIndex];
    if (!conflict) {
        hideConflictResolver();
        return;
    }
    
    var overlay = document.getElementById('conflict-overlay');
    var modal = document.getElementById('conflict-modal');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'conflict-overlay';
        overlay.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[10001] transition-opacity duration-300';
        document.body.appendChild(overlay);
    }
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'conflict-modal';
        modal.className = 'fixed inset-4 z-[10002] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col transition-all duration-300 transform';
        document.body.appendChild(modal);
    }
    
    var localData = conflict.localData || {};
    var serverData = conflict.serverData || {};
    
    // Encontra todas as chaves únicas
    var allKeys = {};
    Object.keys(localData).forEach(function(k) { allKeys[k] = true; });
    Object.keys(serverData).forEach(function(k) { allKeys[k] = true; });
    
    // Filtra campos relevantes (não vazios em pelo menos um lado)
    var relevantKeys = Object.keys(allKeys).filter(function(key) {
        var local = localData[key];
        var server = serverData[key];
        return (local !== undefined && local !== '' && local !== null) ||
               (server !== undefined && server !== '' && server !== null);
    });
    
    // Gera HTML de comparação
    var comparisonHtml = '';
    var mergedData = {};
    
    relevantKeys.forEach(function(key) {
        var localVal = localData[key] !== undefined ? localData[key] : '';
        var serverVal = serverData[key] !== undefined ? serverData[key] : '';
        var isDifferent = String(localVal) !== String(serverVal);
        var label = fieldLabels[key] || key;
        
        // Inicializa merged com valor do servidor por padrão
        mergedData[key] = serverVal;
        
        if (isDifferent) {
            comparisonHtml +=
                '<div class="border-b border-gray-100 dark:border-gray-800 py-3">' +
                    '<div class="flex items-center justify-between mb-2">' +
                        '<span class="text-sm font-semibold text-gray-700 dark:text-gray-300">' + label + '</span>' +
                        '<span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">Diferente</span>' +
                    '</div>' +
                    '<div class="grid grid-cols-2 gap-3">' +
                        '<div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border-2 border-transparent cursor-pointer transition-all hover:border-blue-400" ' +
                            'onclick="selectConflictValue(\'' + key + '\', \'local\', this)" data-conflict-field="' + key + '" data-source="local">' +
                            '<div class="flex items-center gap-2 mb-1">' +
                                '<span class="material-symbols-outlined text-blue-500 text-sm">phone_android</span>' +
                                '<span class="text-xs font-medium text-blue-600 dark:text-blue-400">Local</span>' +
                            '</div>' +
                            '<p class="text-sm text-gray-900 dark:text-white break-words">' + escapeHtml(String(localVal || '(vazio)')) + '</p>' +
                        '</div>' +
                        '<div class="p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border-2 border-green-400 cursor-pointer transition-all hover:border-green-400" ' +
                            'onclick="selectConflictValue(\'' + key + '\', \'server\', this)" data-conflict-field="' + key + '" data-source="server">' +
                            '<div class="flex items-center gap-2 mb-1">' +
                                '<span class="material-symbols-outlined text-green-500 text-sm">cloud</span>' +
                                '<span class="text-xs font-medium text-green-600 dark:text-green-400">Servidor</span>' +
                            '</div>' +
                            '<p class="text-sm text-gray-900 dark:text-white break-words">' + escapeHtml(String(serverVal || '(vazio)')) + '</p>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }
    });
    
    // Salva merged data no modal para uso posterior
    modal.dataset.mergedData = JSON.stringify(mergedData);
    modal.dataset.conflictId = conflict.id;
    
    var actionLabel = {
        'create_client': 'Criar Cliente',
        'update_client': 'Atualizar Cliente',
        'delete_client': 'Excluir Cliente',
        'link_equipment': 'Vincular Equipamento',
        'unlink_equipment': 'Desvincular Equipamento'
    }[conflict.action_type] || conflict.action_type;
    
    modal.innerHTML =
        // Header
        '<div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-4 flex-shrink-0">' +
            '<div class="flex items-center justify-between">' +
                '<div class="flex items-center gap-3">' +
                    '<span class="material-symbols-outlined text-white text-2xl">sync_problem</span>' +
                    '<div>' +
                        '<h2 class="text-lg font-bold text-white">Conflito Detectado</h2>' +
                        '<p class="text-sm text-white/80">' + (currentConflictIndex + 1) + ' de ' + pendingConflicts.length + '</p>' +
                    '</div>' +
                '</div>' +
                '<button onclick="skipConflict()" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors">' +
                    '<span class="material-symbols-outlined">skip_next</span>' +
                '</button>' +
            '</div>' +
        '</div>' +
        
        // Info
        '<div class="px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800 flex-shrink-0">' +
            '<div class="flex items-start gap-3">' +
                '<span class="material-symbols-outlined text-yellow-600 text-lg">info</span>' +
                '<div>' +
                    '<p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Ação: ' + actionLabel + '</p>' +
                    '<p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">' +
                        'Os dados locais diferem do servidor. Clique nos campos para escolher qual valor manter.' +
                    '</p>' +
                '</div>' +
            '</div>' +
        '</div>' +
        
        // Comparison list (scrollable)
        '<div class="flex-1 overflow-y-auto p-4">' +
            (comparisonHtml || '<p class="text-center text-gray-500 py-8">Nenhuma diferença encontrada</p>') +
        '</div>' +
        
        // Actions
        '<div class="p-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">' +
            '<div class="grid grid-cols-3 gap-3">' +
                '<button onclick="resolveConflict(\'local\')" class="py-3 px-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold text-sm flex flex-col items-center gap-1 transition-colors">' +
                    '<span class="material-symbols-outlined text-lg">phone_android</span>' +
                    'Manter Local' +
                '</button>' +
                '<button onclick="resolveConflict(\'merge\')" class="py-3 px-2 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold text-sm flex flex-col items-center gap-1 transition-colors">' +
                    '<span class="material-symbols-outlined text-lg">merge</span>' +
                    'Mesclar' +
                '</button>' +
                '<button onclick="resolveConflict(\'server\')" class="py-3 px-2 bg-green-500 hover:bg-green-600 text-white rounded-xl font-semibold text-sm flex flex-col items-center gap-1 transition-colors">' +
                    '<span class="material-symbols-outlined text-lg">cloud</span>' +
                    'Manter Servidor' +
                '</button>' +
            '</div>' +
            '<button onclick="discardConflict()" class="w-full mt-3 py-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-medium transition-colors">' +
                'Descartar alteração local' +
            '</button>' +
        '</div>';
}

// Seleciona valor para um campo específico (para merge)
function selectConflictValue(fieldKey, source, element) {
    var modal = document.getElementById('conflict-modal');
    if (!modal) return;
    
    try {
        var mergedData = JSON.parse(modal.dataset.mergedData || '{}');
        var conflict = pendingConflicts[currentConflictIndex];
        
        if (source === 'local') {
            mergedData[fieldKey] = conflict.localData[fieldKey];
        } else {
            mergedData[fieldKey] = conflict.serverData[fieldKey];
        }
        
        modal.dataset.mergedData = JSON.stringify(mergedData);
        
        // Atualiza visual dos cards
        var cards = document.querySelectorAll('[data-conflict-field="' + fieldKey + '"]');
        cards.forEach(function(card) {
            if (card.dataset.source === source) {
                card.classList.add('border-2');
                if (source === 'local') {
                    card.classList.remove('border-transparent');
                    card.classList.add('border-blue-400');
                } else {
                    card.classList.remove('border-transparent');
                    card.classList.add('border-green-400');
                }
            } else {
                card.classList.remove('border-blue-400', 'border-green-400');
                card.classList.add('border-transparent');
            }
        });
    } catch (e) {
        console.error('Erro ao selecionar valor:', e);
    }
}

// Resolve conflito com a opção escolhida
async function resolveConflict(resolution) {
    var conflict = pendingConflicts[currentConflictIndex];
    if (!conflict) return;
    
    var modal = document.getElementById('conflict-modal');
    var dataToSend;
    
    switch (resolution) {
        case 'local':
            // Usa dados locais
            dataToSend = conflict.localData;
            break;
        case 'server':
            // Usa dados do servidor (descarta local)
            clearOfflineItem(conflict.id);
            nextConflict();
            return;
        case 'merge':
            // Usa dados mesclados
            try {
                dataToSend = JSON.parse(modal.dataset.mergedData || '{}');
            } catch (e) {
                dataToSend = conflict.localData;
            }
            break;
    }
    
    // Marca como forçar (ignorar verificação de conflito no servidor)
    dataToSend._force_update = true;
    dataToSend._resolved_conflict = true;
    
    // Atualiza item na fila e tenta sincronizar novamente
    var item = conflict.item;
    item.data = dataToSend;
    updateOfflineItem(item);
    
    // Tenta sincronizar o item resolvido
    showLoading('Aplicando resolução...');
    var result = await syncSingleItem(item);
    hideLoading();
    
    if (result.success) {
        clearOfflineItem(conflict.id);
        showSuccess('Conflito resolvido!');
    } else {
        showError('Falha ao aplicar resolução. Tente novamente.');
    }
    
    nextConflict();
}

// Descarta alteração local (mantém servidor)
function discardConflict() {
    if (!confirm('Descartar sua alteração local? Os dados do servidor serão mantidos.')) {
        return;
    }
    
    var conflict = pendingConflicts[currentConflictIndex];
    if (conflict) {
        clearOfflineItem(conflict.id);
    }
    
    nextConflict();
}

// Pula para próximo conflito
function skipConflict() {
    currentConflictIndex++;
    if (currentConflictIndex >= pendingConflicts.length) {
        currentConflictIndex = 0;
    }
    renderConflictModal();
}

// Vai para próximo conflito ou fecha modal
function nextConflict() {
    pendingConflicts.splice(currentConflictIndex, 1);
    
    if (pendingConflicts.length === 0) {
        hideConflictResolver();
        showSuccess('Todos os conflitos foram resolvidos!');
        updateOfflineQueueBadge();
        return;
    }
    
    if (currentConflictIndex >= pendingConflicts.length) {
        currentConflictIndex = 0;
    }
    
    renderConflictModal();
}

// Esconde modal de conflitos
function hideConflictResolver() {
    var overlay = document.getElementById('conflict-overlay');
    var modal = document.getElementById('conflict-modal');
    
    if (overlay) {
        overlay.remove();
    }
    if (modal) {
        modal.remove();
    }
    
    pendingConflicts = [];
    updateOfflineQueueBadge();
}

// Escape HTML para prevenir XSS
function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// =====================================================
// Inicialização
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Migra dados antigos de offline_queue para offlineQueue (consolidação)
    try {
        var oldQueue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
        if (oldQueue.length > 0) {
            var currentQueue = JSON.parse(localStorage.getItem('offlineQueue') || '[]');
            oldQueue.forEach(function(item) {
                currentQueue.push({
                    id: item.id || Date.now(),
                    action_type: item.actionType || item.action_type,
                    data: item.data,
                    username: item.username || localStorage.getItem('username') || 'unknown',
                    user_id: item.user_id || localStorage.getItem('userId'),
                    created_at: item.timestamp || item.created_at || new Date().toISOString()
                });
            });
            localStorage.setItem('offlineQueue', JSON.stringify(currentQueue));
            localStorage.removeItem('offline_queue');
        }
    } catch (e) { /* migration failed silently */ }

    startConnectionMonitoring();

    // Tenta sincronizar ao carregar se estiver online
    if (navigator.onLine) {
        setTimeout(function() {
            syncOfflineQueue();
        }, 2000);
    }
});