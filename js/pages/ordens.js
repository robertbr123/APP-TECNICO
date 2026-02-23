/**
 * Módulo de Ordens de Serviço
 * Ondeline Tech - App do Técnico
 */

(function() {
    var orders = [];
    var currentFilter = 'all';
    var searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function() {
        var user = JSON.parse(localStorage.getItem('user_data') || '{}');
        var isAdmin = user.role === 'admin';

        // Only admin can create OS
        var fabBtn = document.getElementById('btn-new-os');
        if (fabBtn && !isAdmin) {
            fabBtn.classList.add('hidden');
        }

        loadStats();
        loadOrders();
        setupEventListeners();
        setupNotificationBadge();
        if (isAdmin) loadTechnicians();

        // Check URL params for detail view
        var params = new URLSearchParams(window.location.search);
        if (params.get('id')) {
            setTimeout(function() { showOrderDetail(params.get('id')); }, 500);
        }
    });

    function setupEventListeners() {
        // New OS modal
        document.getElementById('btn-new-os').addEventListener('click', openNewModal);
        document.getElementById('modal-close').addEventListener('click', closeNewModal);
        document.getElementById('modal-overlay').addEventListener('click', closeNewModal);

        // Detail modal
        document.getElementById('detail-close').addEventListener('click', closeDetailModal);
        document.getElementById('detail-overlay').addEventListener('click', closeDetailModal);

        // Form
        document.getElementById('form-new-os').addEventListener('submit', handleCreateOS);

        // CPF mask
        document.getElementById('os-client-cpf').addEventListener('input', function(e) {
            var v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            e.target.value = v;
        });

        // Filter tabs
        document.querySelectorAll('.os-filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.os-filter-btn').forEach(function(b) {
                    b.classList.remove('active', 'bg-gradient-to-r', 'from-primary', 'to-blue-500', 'text-white', 'shadow-lg', 'shadow-primary/30', 'font-semibold');
                    b.classList.add('bg-white/60', 'dark:bg-gray-800/60', 'border', 'border-gray-200/50', 'dark:border-white/10', 'text-gray-600', 'dark:text-gray-400', 'font-medium');
                });
                btn.classList.add('active', 'bg-gradient-to-r', 'from-primary', 'to-blue-500', 'text-white', 'shadow-lg', 'shadow-primary/30', 'font-semibold');
                btn.classList.remove('bg-white/60', 'dark:bg-gray-800/60', 'border', 'border-gray-200/50', 'dark:border-white/10', 'text-gray-600', 'dark:text-gray-400', 'font-medium');

                currentFilter = btn.dataset.status;
                loadOrders();
            });
        });

        // Search
        document.getElementById('search-input').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadOrders(e.target.value);
            }, 300);
        });
    }

    async function loadStats() {
        try {
            var response = await API.get('work-orders.php', { action: 'stats' });
            if (response.success) {
                var s = response.data;
                document.getElementById('stat-open').textContent = (parseInt(s.open) || 0) + (parseInt(s.assigned) || 0);
                document.getElementById('stat-progress').textContent = s.in_progress || 0;
                document.getElementById('stat-completed').textContent = s.completed || 0;
                document.getElementById('stat-today-os').textContent = s.today || 0;
            }
        } catch (e) { /* stats failed */ }
    }

    async function loadOrders(search) {
        try {
            var params = { limit: 50 };
            if (currentFilter !== 'all') params.status = currentFilter;
            if (search) params.search = search;

            var response = await API.get('work-orders.php', params);
            if (response.success) {
                orders = response.data;
                document.getElementById('os-count').textContent = response.total + ' ordens';
                renderOrders(orders);
            }
        } catch (e) {
            showError('Erro ao carregar ordens: ' + e.message);
        }
    }

    function renderOrders(list) {
        var container = document.getElementById('orders-list');
        var empty = document.getElementById('empty-state');

        if (list.length === 0) {
            container.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');

        var priorityMap = {
            urgent: { color: 'text-red-600 bg-red-50 dark:bg-red-900/20', label: 'Urgente', icon: 'priority_high' },
            high: { color: 'text-orange-600 bg-orange-50 dark:bg-orange-900/20', label: 'Alta', icon: 'arrow_upward' },
            medium: { color: 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20', label: 'Média', icon: 'remove' },
            low: { color: 'text-green-600 bg-green-50 dark:bg-green-900/20', label: 'Baixa', icon: 'arrow_downward' }
        };

        var statusMap = {
            open: { color: 'bg-blue-500', label: 'Aberta' },
            assigned: { color: 'bg-indigo-500', label: 'Atribuída' },
            in_progress: { color: 'bg-orange-500', label: 'Em Andamento' },
            completed: { color: 'bg-green-500', label: 'Concluída' },
            cancelled: { color: 'bg-gray-400', label: 'Cancelada' }
        };

        var typeMap = {
            installation: 'Instalação',
            repair: 'Reparo',
            maintenance: 'Manutenção',
            migration: 'Migração',
            removal: 'Remoção',
            other: 'Outro'
        };

        var html = '';
        list.forEach(function(order) {
            var p = priorityMap[order.priority] || priorityMap.medium;
            var s = statusMap[order.status] || statusMap.open;
            var typeName = typeMap[order.type] || order.type;

            var scheduledInfo = '';
            if (order.scheduled_date) {
                var d = new Date(order.scheduled_date + 'T00:00:00');
                scheduledInfo = '<span class="text-[11px] text-gray-400 flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">event</span>' +
                    d.toLocaleDateString('pt-BR') + (order.scheduled_time ? ' ' + order.scheduled_time.substring(0,5) : '') + '</span>';
            }

            html += '<div class="bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm cursor-pointer active:scale-[0.98] transition-transform" data-order-id="' + order.id + '">' +
                '<div class="flex items-start justify-between mb-2">' +
                    '<div class="flex items-center gap-2">' +
                        '<span class="text-xs font-bold text-primary">' + order.order_number + '</span>' +
                        '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white ' + s.color + '">' + s.label + '</span>' +
                    '</div>' +
                    '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ' + p.color + '">' + p.label + '</span>' +
                '</div>' +
                '<h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">' + order.client_name + '</h3>' +
                '<p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">' + order.description + '</p>' +
                '<div class="flex items-center justify-between mt-3">' +
                    '<div class="flex items-center gap-2">' +
                        '<span class="text-[11px] text-gray-400 flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">build</span>' + typeName + '</span>' +
                        scheduledInfo +
                    '</div>' +
                    (order.assigned_name ? '<span class="text-[11px] text-gray-400 flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">person</span>' + order.assigned_name + '</span>' : '') +
                '</div>' +
            '</div>';
        });

        container.innerHTML = html;

        // Attach click handlers
        container.querySelectorAll('[data-order-id]').forEach(function(el) {
            el.addEventListener('click', function() {
                showOrderDetail(el.dataset.orderId);
            });
        });
    }

    async function showOrderDetail(id) {
        var modal = document.getElementById('modal-detail');
        var content = document.getElementById('detail-content');

        try {
            var response = await API.get('work-orders.php', { action: 'get', id: id });
            if (!response.success) {
                showError('OS não encontrada');
                return;
            }

            var order = response.data;
            document.getElementById('detail-title').textContent = 'OS ' + order.order_number;

            var statusMap = {
                open: 'Aberta', assigned: 'Atribuída', in_progress: 'Em Andamento',
                completed: 'Concluída', cancelled: 'Cancelada'
            };
            var priorityMap = { low: 'Baixa', medium: 'Média', high: 'Alta', urgent: 'Urgente' };
            var typeMap = {
                installation: 'Instalação', repair: 'Reparo', maintenance: 'Manutenção',
                migration: 'Migração', removal: 'Remoção', other: 'Outro'
            };

            var html = '<div class="space-y-4">';

            // Status & Priority
            html += '<div class="grid grid-cols-3 gap-2">' +
                '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl text-center"><p class="text-[10px] text-gray-500 uppercase font-bold">Status</p><p class="text-sm font-bold text-gray-900 dark:text-white">' + (statusMap[order.status] || order.status) + '</p></div>' +
                '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl text-center"><p class="text-[10px] text-gray-500 uppercase font-bold">Prioridade</p><p class="text-sm font-bold text-gray-900 dark:text-white">' + (priorityMap[order.priority] || order.priority) + '</p></div>' +
                '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl text-center"><p class="text-[10px] text-gray-500 uppercase font-bold">Tipo</p><p class="text-sm font-bold text-gray-900 dark:text-white">' + (typeMap[order.type] || order.type) + '</p></div>' +
                '</div>';

            // Client info
            html += '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl">' +
                '<p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Cliente</p>' +
                '<p class="text-sm font-bold text-gray-900 dark:text-white">' + order.client_name + '</p>' +
                (order.client_cpf ? '<p class="text-xs text-gray-500">CPF: ' + order.client_cpf + '</p>' : '') +
                '</div>';

            // Description
            html += '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl">' +
                '<p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Descrição</p>' +
                '<p class="text-sm text-gray-700 dark:text-gray-300">' + order.description + '</p>' +
                '</div>';

            // Resolution
            if (order.resolution) {
                html += '<div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-xl">' +
                    '<p class="text-[10px] text-green-600 uppercase font-bold mb-1">Resolução</p>' +
                    '<p class="text-sm text-gray-700 dark:text-gray-300">' + order.resolution + '</p>' +
                    '</div>';
            }

            // Schedule
            if (order.scheduled_date) {
                html += '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl">' +
                    '<p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Agendamento</p>' +
                    '<p class="text-sm text-gray-700 dark:text-gray-300">' + new Date(order.scheduled_date + 'T00:00:00').toLocaleDateString('pt-BR') +
                    (order.scheduled_time ? ' às ' + order.scheduled_time.substring(0,5) : '') + '</p>' +
                    '</div>';
            }

            // Assigned
            if (order.assigned_name) {
                html += '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl">' +
                    '<p class="text-[10px] text-gray-500 uppercase font-bold mb-1">Técnico Responsável</p>' +
                    '<p class="text-sm font-bold text-gray-900 dark:text-white">' + order.assigned_name + '</p>' +
                    '</div>';
            }

            // Dates
            html += '<div class="grid grid-cols-2 gap-2">';
            if (order.created_at) {
                html += '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl"><p class="text-[10px] text-gray-500 uppercase font-bold">Criada em</p><p class="text-xs text-gray-700 dark:text-gray-300">' + new Date(order.created_at).toLocaleString('pt-BR') + '</p></div>';
            }
            if (order.completed_at) {
                html += '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-xl"><p class="text-[10px] text-gray-500 uppercase font-bold">Concluída em</p><p class="text-xs text-gray-700 dark:text-gray-300">' + new Date(order.completed_at).toLocaleString('pt-BR') + '</p></div>';
            }
            html += '</div>';

            // Action buttons
            html += '<div class="space-y-2 pt-2">';

            if (order.status === 'open' || order.status === 'assigned') {
                html += '<button onclick="window._osStartOrder(' + order.id + ')" class="w-full py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold rounded-xl text-sm active:scale-[0.98] transition-transform flex items-center justify-center gap-2">' +
                    '<span class="material-symbols-outlined text-lg">play_arrow</span>Iniciar Atendimento</button>';
            }

            if (order.status === 'in_progress') {
                html += '<button onclick="window._osCompleteOrder(' + order.id + ')" class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold rounded-xl text-sm active:scale-[0.98] transition-transform flex items-center justify-center gap-2">' +
                    '<span class="material-symbols-outlined text-lg">check_circle</span>Concluir OS</button>';
            }

            if (order.status !== 'completed' && order.status !== 'cancelled') {
                html += '<button onclick="window._osCancelOrder(' + order.id + ')" class="w-full py-3 bg-gray-100 dark:bg-gray-800 text-red-500 font-bold rounded-xl text-sm active:scale-[0.98] transition-transform">Cancelar OS</button>';
            }

            html += '</div></div>';
            content.innerHTML = html;
            modal.classList.remove('hidden');
        } catch (e) {
            showError('Erro ao carregar detalhes: ' + e.message);
        }
    }

    // Global action handlers
    window._osStartOrder = async function(id) {
        try {
            var locationData = {};
            try {
                var pos = await new Promise(function(resolve, reject) {
                    navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000 });
                });
                locationData.latitude = pos.coords.latitude;
                locationData.longitude = pos.coords.longitude;
            } catch (e) { /* location not available */ }

            var response = await API.put('work-orders.php', { id: id, action: 'start', ...locationData });
            if (response.success) {
                showSuccess('OS iniciada!');
                closeDetailModal();
                loadOrders();
                loadStats();
            }
        } catch (e) {
            showError('Erro ao iniciar OS: ' + e.message);
        }
    };

    window._osCompleteOrder = async function(id) {
        var resolution = prompt('Descreva a resolução do serviço:');
        if (!resolution) return;

        try {
            var response = await API.put('work-orders.php', { id: id, action: 'complete', resolution: resolution });
            if (response.success) {
                showSuccess('OS concluída com sucesso!');
                closeDetailModal();
                loadOrders();
                loadStats();
            }
        } catch (e) {
            showError('Erro ao concluir OS: ' + e.message);
        }
    };

    window._osCancelOrder = async function(id) {
        if (typeof AppComponents !== 'undefined' && AppComponents.showConfirmModal) {
            var confirmed = await AppComponents.showConfirmModal({
                title: 'Cancelar OS',
                message: 'Tem certeza que deseja cancelar esta ordem de serviço?',
                confirmText: 'Sim, Cancelar',
                type: 'danger',
                icon: 'cancel'
            });
            if (!confirmed) return;
        }

        try {
            var response = await API.put('work-orders.php', { id: id, action: 'cancel' });
            if (response.success) {
                showSuccess('OS cancelada');
                closeDetailModal();
                loadOrders();
                loadStats();
            }
        } catch (e) {
            showError('Erro ao cancelar OS: ' + e.message);
        }
    };

    async function handleCreateOS(e) {
        e.preventDefault();

        var clientName = document.getElementById('os-client-name').value.trim();
        var description = document.getElementById('os-description').value.trim();

        if (!clientName || !description) {
            showError('Preencha o nome do cliente e a descrição');
            return;
        }

        var cpfRaw = document.getElementById('os-client-cpf').value.replace(/\D/g, '');
        var priority = document.querySelector('input[name="priority"]:checked');
        var assignedSelect = document.getElementById('os-assigned');
        var assignedTo = assignedSelect ? assignedSelect.value : null;
        var assignedName = assignedTo ? assignedSelect.options[assignedSelect.selectedIndex].dataset.name : null;

        var data = {
            action: 'create',
            client_name: clientName,
            client_cpf: cpfRaw || null,
            type: document.getElementById('os-type').value,
            priority: priority ? priority.value : 'medium',
            description: description,
            scheduled_date: document.getElementById('os-date').value || null,
            scheduled_time: document.getElementById('os-time').value || null,
            assigned_to: assignedTo || null,
            assigned_name: assignedName || null
        };

        try {
            showLoading('Criando OS...');
            var response = await API.post('work-orders.php', data);
            hideLoading();

            if (response.success) {
                showSuccess('OS ' + response.data.order_number + ' criada!');
                closeNewModal();
                document.getElementById('form-new-os').reset();
                loadOrders();
                loadStats();
            } else {
                showError(response.message || 'Erro ao criar OS');
            }
        } catch (e) {
            hideLoading();
            showError('Erro: ' + e.message);
        }
    }

    function openNewModal() {
        document.getElementById('modal-new-os').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeNewModal() {
        document.getElementById('modal-new-os').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function closeDetailModal() {
        document.getElementById('modal-detail').classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function loadTechnicians() {
        try {
            var response = await API.get('users.php');
            if (response.success) {
                var select = document.getElementById('os-assigned');
                if (!select) return;
                response.data.forEach(function(u) {
                    var opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = u.full_name || u.username;
                    opt.dataset.name = u.full_name || u.username;
                    select.appendChild(opt);
                });
            }
        } catch (e) { /* failed to load technicians */ }
    }

    async function setupNotificationBadge() {
        try {
            var response = await API.get('notifications.php', { action: 'unread_count' });
            if (response.success && response.data.count > 0) {
                var badge = document.getElementById('notif-badge');
                badge.textContent = response.data.count > 9 ? '9+' : response.data.count;
                badge.classList.remove('hidden');
            }
        } catch (e) { /* badge failed */ }
    }
})();
