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
// Indicador de Status de Conexão
// =====================================================

// Cria indicador de conexão se não existir
function ensureConnectionIndicator() {
    var indicator = document.getElementById('connection-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'connection-indicator';
        indicator.className = 'fixed top-20 right-4 z-[9999] flex items-center gap-2 px-3 py-2 rounded-full ' +
            'bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-300';
        indicator.innerHTML = 
            '<div class="size-2 rounded-full bg-green-500" id="connection-dot"></div>' +
            '<span class="text-xs font-medium text-gray-700 dark:text-gray-300" id="connection-text">Online</span>';
        document.body.appendChild(indicator);
    }
    return indicator;
}

// Atualiza status da conexão
function updateConnectionStatus(online) {
    var dot = document.getElementById('connection-dot');
    var text = document.getElementById('connection-text');
    
    if (online) {
        dot.className = 'size-2 rounded-full bg-green-500';
        text.textContent = 'Online';
        text.className = 'text-xs font-medium text-gray-700 dark:text-gray-300';
    } else {
        dot.className = 'size-2 rounded-full bg-red-500 animate-pulse';
        text.textContent = 'Offline';
        text.className = 'text-xs font-medium text-red-600 dark:text-red-400';
    }
}

// Inicia monitoramento de conexão
function startConnectionMonitoring() {
    ensureConnectionIndicator();
    updateConnectionStatus(navigator.onLine);
    
    // Eventos de mudança de status
    window.addEventListener('online', function() {
        updateConnectionStatus(true);
        showInfo('Conexão restaurada. Sincronizando dados...');
        syncOfflineQueue();
    });
    
    window.addEventListener('offline', function() {
        updateConnectionStatus(false);
        showWarning('Você está offline. Os dados serão salvos localmente.');
    });
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
        console.log('Dados salvos offline:', actionType);
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
// Sincronização Offline
// =====================================================

// Sincroniza fila offline
async function syncOfflineQueue() {
    if (!navigator.onLine) {
        console.log('Offline, pulando sincronização');
        return;
    }
    
    var queue = getOfflineQueue();
    if (queue.length === 0) {
        console.log('Fila vazia');
        return;
    }
    
    console.log('Sincronizando ' + queue.length + ' itens...');
    
    for (var i = 0; i < queue.length; i++) {
        var item = queue[i];
        console.log('Processando item:', item.action_type, item.id);
        
        try {
            var token = localStorage.getItem('authToken');
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
                    url = '/api/cadastro.php';
                    break;
                case 'link_equipment':
                    url = '/api/vincular.php';
                    break;
                case 'upload_photo':
                    url = '/api/upload-foto.php';
                    break;
                default:
                    console.error('Tipo de ação desconhecido:', item.action_type);
                    clearOfflineItem(item.id);
                    continue;
            }
            
            var response = await fetch(url, {
                method: method,
                headers: headers,
                body: JSON.stringify(item.data)
            });
            
            var result = await response.json();
            
            if (result.success) {
                console.log('Item sincronizado com sucesso:', item.id);
                clearOfflineItem(item.id);
            } else {
                console.error('Erro ao sincronizar item:', item.id, result.message);
            }
        } catch (error) {
            console.error('Erro ao processar item:', item.id, error);
        }
    }
    
    var remaining = getOfflineQueue().length;
    if (remaining === 0) {
        showSuccess('Todos os dados foram sincronizados!');
    } else {
        showWarning(remaining + ' itens ainda precisam ser sincronizados.');
    }
}

// =====================================================
// Inicialização
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    startConnectionMonitoring();
    
    // Tenta sincronizar ao carregar se estiver online
    if (navigator.onLine) {
        setTimeout(function() {
            syncOfflineQueue();
        }, 2000);
    }
});