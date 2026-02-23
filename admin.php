<!DOCTYPE html>
<html class="light" lang="pt-BR">
<head>
    <title>Painel Administrativo - Ondeline</title>
<?php include 'partials/head.php'; ?>
<style type="text/tailwindcss">
    .tab-active {
        border-bottom: 2px solid #135bec;
        color: #135bec;
    }
</style>
</head>
<body class="min-h-screen">
    
    <!-- Header -->
    <header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
        <div class="flex items-center justify-between px-4 h-14">
            <div class="text-gray-900 dark:text-white cursor-pointer" onclick="history.back()">
                <span class="material-symbols-outlined">arrow_back_ios</span>
            </div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex-1 text-center">Painel Admin</h2>
            <div class="w-8"></div>
        </div>
    </header>

    <div class="p-4 pb-32">
        
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-blue-500">people</span>
                    <span class="text-xs text-gray-500">Usuários</span>
                </div>
                <p id="stat-clients" class="text-2xl font-bold text-gray-900 dark:text-white">-</p>
            </div>
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-orange-500">pending_actions</span>
                    <span class="text-xs text-gray-500">P/ Aprovar</span>
                </div>
                <p id="stat-pending" class="text-2xl font-bold text-gray-900 dark:text-white">-</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-card-light dark:bg-card-dark rounded-ios-lg shadow-sm mb-4 overflow-hidden">
            <div class="flex overflow-x-auto">
                <button onclick="showTab('templates')" id="tab-templates" class="tab-btn flex-1 px-4 py-3 text-sm font-medium tab-active">Templates</button>
                <button onclick="showTab('users')" id="tab-users" class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-600">Usuários</button>
                <button onclick="showTab('checklists')" id="tab-checklists" class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-600">Checklists</button>
                <button onclick="showTab('auditoria')" id="tab-auditoria" class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-600">Auditoria</button>
                <button onclick="showTab('push')" id="tab-push" class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-600">Push</button>
                <button onclick="showTab('config')" id="tab-config" class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-600">Config</button>
            </div>
        </div>

        <!-- Templates Panel -->
        <div id="panel-templates" class="tab-content">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Templates do Checklist</h3>
                    <button onclick="openTemplateModal()" class="px-3 py-2 bg-green-500 text-white rounded-lg text-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> Novo
                    </button>
                </div>
                <div id="templates-list" class="space-y-2">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Panel -->
        <div id="panel-users" class="tab-content hidden">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Gerenciar Usuários</h3>
                    <button onclick="openUserModal()" class="px-3 py-2 bg-green-500 text-white rounded-lg text-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> Novo
                    </button>
                </div>
                <div id="users-list" class="space-y-2">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checklists Panel -->
        <div id="panel-checklists" class="tab-content hidden">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Todos os Checklists</h3>
                <div id="checklists-list" class="space-y-2">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auditoria Panel -->
        <div id="panel-auditoria" class="tab-content hidden">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Logs de Auditoria</h3>
                    <button onclick="loadAuditLogs()" class="flex items-center gap-1 text-primary text-sm font-medium hover:opacity-70">
                        <span class="material-symbols-outlined text-lg">refresh</span>
                        Atualizar
                    </button>
                </div>

                <!-- Filtros -->
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <select id="audit-filter-user" onchange="loadAuditLogs()"
                                class="w-full h-10 px-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm border-0 focus:ring-2 focus:ring-primary/50">
                                <option value="">Todos os usuários</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <input type="text" id="audit-search-user" placeholder="Buscar técnico..."
                                class="w-full h-10 px-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm border-0 focus:ring-2 focus:ring-primary/50">
                        </div>
                    </div>

                    <!-- Filtros por tipo -->
                    <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none;scrollbar-width:none;">
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-primary text-white text-xs font-medium" data-action="" onclick="setAuditActionFilter(this)">Todos</button>
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium" data-action="login" onclick="setAuditActionFilter(this)">Logins</button>
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium" data-action="client_created" onclick="setAuditActionFilter(this)">Cadastros</button>
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium" data-action="client_viewed" onclick="setAuditActionFilter(this)">Visualizações</button>
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium" data-action="equipment_linked" onclick="setAuditActionFilter(this)">Vinculações</button>
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium" data-action="client_updated" onclick="setAuditActionFilter(this)">Edições</button>
                        <button class="audit-filter-btn flex-shrink-0 h-8 px-3 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium" data-action="client_deleted" onclick="setAuditActionFilter(this)">Exclusões</button>
                    </div>

                    <!-- Filtros de data -->
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-[10px] text-gray-500 mb-1 block">De:</label>
                            <input type="date" id="audit-start-date" onchange="loadAuditLogs()"
                                class="w-full h-10 px-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm border-0 focus:ring-2 focus:ring-primary/50">
                        </div>
                        <div class="flex-1">
                            <label class="text-[10px] text-gray-500 mb-1 block">Até:</label>
                            <input type="date" id="audit-end-date" onchange="loadAuditLogs()"
                                class="w-full h-10 px-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm border-0 focus:ring-2 focus:ring-primary/50">
                        </div>
                    </div>
                </div>

                <!-- Contador -->
                <div class="flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-primary text-lg">history</span>
                    <span id="audit-total-count" class="font-semibold text-gray-900 dark:text-white">Carregando...</span>
                </div>

                <!-- Lista de Logs -->
                <div id="audit-logs-list" class="space-y-2 max-h-[60vh] overflow-y-auto">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                        <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Push Notifications Panel -->
        <div id="panel-push" class="tab-content hidden">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm space-y-4">
                <!-- Status e Ativar Notificações -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-500">notifications</span>
                            <span class="font-medium text-sm">Notificações neste dispositivo</span>
                        </div>
                        <span id="push-permission-status" class="text-xs px-2 py-1 rounded-full bg-gray-200 dark:bg-gray-600">Verificando...</span>
                    </div>
                    <p id="push-permission-help" class="text-xs text-gray-500 mb-3"></p>
                    <button id="btn-enable-push" onclick="enablePushNotifications()" class="w-full py-2 bg-blue-500 text-white rounded-lg text-sm font-medium flex items-center justify-center gap-2 disabled:opacity-50">
                        <span class="material-symbols-outlined text-sm">notifications_active</span>
                        Ativar Notificações
                    </button>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <h3 class="font-semibold text-gray-900 dark:text-white">Enviar Notificação Push</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500">Título</label>
                        <input type="text" id="push-title" value="Ondeline Tech" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Mensagem *</label>
                        <textarea id="push-body" rows="3" placeholder="Digite a mensagem..." class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 text-sm resize-none"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">URL (ao clicar)</label>
                        <input type="text" id="push-url" value="/dashboard.php" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Destinatário</label>
                        <select id="push-target" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600 text-sm">
                            <option value="">Todos os técnicos</option>
                        </select>
                    </div>
                </div>

                <button onclick="sendPushNotification()" class="w-full py-3 bg-primary text-white rounded-xl font-medium flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">notifications_active</span>
                    Enviar Notificação
                </button>

                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Dispositivos com push ativo</p>
                        <div class="flex gap-2">
                            <button onclick="testPushConfig()" class="text-xs text-orange-500">Testar Config</button>
                            <button onclick="loadPushSubscriptions()" class="text-xs text-primary">Atualizar</button>
                        </div>
                    </div>
                    <div id="push-subs-list" class="space-y-1">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                            <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
                            <div class="size-9 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3.5 bg-gray-200 dark:bg-gray-600 rounded w-2/3"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/3"></div>
                            </div>
                        </div>
                    </div>
                    <div id="push-test-result" class="mt-2 text-xs hidden"></div>
                </div>
            </div>
        </div>

        <!-- Config Panel -->
        <div id="panel-config" class="tab-content hidden">
            <div class="bg-card-light dark:bg-card-dark rounded-ios-lg p-4 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Configurações</h3>
                
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="text-sm font-medium">Meta Mensal</p>
                        <p class="text-xs text-gray-500">Instalações esperadas</p>
                    </div>
                    <input type="number" id="monthly-goal" value="10" class="w-20 px-2 py-1 rounded border text-center">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button onclick="clearCache()" class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg text-center">
                        <span class="material-symbols-outlined text-orange-500">cleaning_services</span>
                        <p class="text-xs font-medium mt-1">Limpar Cache</p>
                    </button>
                    <button onclick="showInfo('Backup em desenvolvimento.')" class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-center">
                        <span class="material-symbols-outlined text-purple-500">backup</span>
                        <p class="text-xs font-medium mt-1">Backup BD</p>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal User -->
    <div id="modal-user" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-card-light dark:bg-card-dark rounded-ios-lg max-w-sm w-full p-4 max-h-[90vh] overflow-y-auto">
            <h3 class="font-bold text-lg mb-4" id="user-title">Novo Usuário</h3>
            <input type="hidden" id="user-id">
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-gray-500">Nome Completo *</label>
                    <input type="text" id="user-fullname" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Usuário *</label>
                    <input type="text" id="user-username" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Email</label>
                    <input type="email" id="user-email" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Senha <span id="user-pass-hint">*</span></label>
                    <input type="password" id="user-password" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                    <p class="text-[10px] text-gray-500" id="user-pass-help">Mínimo 6 caracteres</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Função</label>
                    <select id="user-role" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                        <option value="tecnico">Técnico</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Cidade</label>
                    <input type="text" id="user-city" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Cargo</label>
                    <input type="text" id="user-cargo" placeholder="Ex: Técnico de Campo, Suporte" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button onclick="closeUserModal()" class="flex-1 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancelar</button>
                <button onclick="saveUser()" class="flex-1 py-2 bg-green-500 text-white rounded-lg">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal Template -->
    <div id="modal-template" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-card-light dark:bg-card-dark rounded-ios-lg max-w-sm w-full p-4">
            <h3 class="font-bold text-lg mb-4" id="tmpl-title">Novo Template</h3>
            <input type="hidden" id="tmpl-id">
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-gray-500">Nome da Tarefa</label>
                    <input type="text" id="tmpl-name" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Categoria</label>
                    <select id="tmpl-category" class="w-full px-3 py-2 rounded-lg border dark:bg-gray-700 dark:border-gray-600">
                        <option value="pre_installation">Pré-instalação</option>
                        <option value="installation">Instalação</option>
                        <option value="configuration">Configuração</option>
                        <option value="testing">Testes</option>
                        <option value="documentation">Documentação</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Aplica-se a:</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" class="tmpl-type" value="new" checked> Nova</label>
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" class="tmpl-type" value="migration" checked> Migração</label>
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" class="tmpl-type" value="repair" checked> Reparo</label>
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" class="tmpl-type" value="maintenance" checked> Manutenção</label>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button onclick="closeTemplateModal()" class="flex-1 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancelar</button>
                <button onclick="saveTemplate()" class="flex-1 py-2 bg-green-500 text-white rounded-lg">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Bottom Nav -->
    <?php $activePage = 'ajustes'; include 'partials/bottom-nav.php'; ?>

    <script src="js/api.js"></script>
    <script src="js/feedback.js"></script>
    <script src="js/components.js"></script>
    <script>
        // Registra Service Worker (necessário para push notifications)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(err => {
                console.warn('SW registration failed:', err);
            });
        }

        // Verifica admin
        const user = API.getUser();
        if (!user || user.role !== 'admin') {
            location.href = 'dashboard.php';
        }

        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('tab-active', 'text-blue-500'));
            document.getElementById('panel-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.add('tab-active');
            
            if (tab === 'templates') loadTemplates();
            if (tab === 'users') loadUsers();
            if (tab === 'checklists') loadChecklists();
            if (tab === 'auditoria') initAuditoria();
            if (tab === 'push') loadPushSubscriptions();
        }

        let templatesData = [];
        
        async function loadTemplates() {
            try {
                const res = await API.getChecklistTemplates();
                if (res.success) {
                    templatesData = res.data;
                    renderTemplates();
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        function renderTemplates() {
            const cats = {
                'pre_installation': 'Pré-instalação',
                'installation': 'Instalação',
                'configuration': 'Configuração',
                'testing': 'Testes',
                'documentation': 'Documentação'
            };
            document.getElementById('templates-list').innerHTML = templatesData.map(t => `
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(t.task_name)}</p>
                        <p class="text-xs text-gray-500">${escapeHtml(cats[t.task_category])} • ${escapeHtml(t.applicable_types)}</p>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="editTemplate(${t.id})" class="p-2 text-blue-500 hover:bg-blue-100 rounded-lg">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                        <button onclick="deleteTemplate(${t.id})" class="p-2 text-red-500 hover:bg-red-100 rounded-lg">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        function openTemplateModal(id = null) {
            document.getElementById('modal-template').classList.remove('hidden');
            if (id) {
                const templateId = parseInt(id);
                const t = templatesData.find(x => parseInt(x.id) === templateId);
                if (!t) {
                    showError('Template não encontrado.');
                    return;
                }
                document.getElementById('tmpl-title').textContent = 'Editar Template';
                document.getElementById('tmpl-id').value = t.id;
                document.getElementById('tmpl-name').value = t.task_name || '';
                document.getElementById('tmpl-category').value = t.task_category || '';
                document.querySelectorAll('.tmpl-type').forEach(cb => {
                    const types = t.applicable_types || '';
                    cb.checked = types.includes(cb.value);
                });
            } else {
                document.getElementById('tmpl-title').textContent = 'Novo Template';
                document.getElementById('tmpl-id').value = '';
                document.getElementById('tmpl-name').value = '';
                document.querySelectorAll('.tmpl-type').forEach(cb => cb.checked = true);
            }
        }
        
        function closeTemplateModal() {
            document.getElementById('modal-template').classList.add('hidden');
        }
        
        async function saveTemplate() {
            const id = document.getElementById('tmpl-id').value;
            const name = document.getElementById('tmpl-name').value;
            const category = document.getElementById('tmpl-category').value;
            const types = Array.from(document.querySelectorAll('.tmpl-type:checked')).map(cb => cb.value).join(',');
            
            if (!name) {
                showError('Nome é obrigatório.');
                return;
            }
            
            try {
                let res;
                if (id) {
                    res = await API.updateTemplate(id, {
                        task_name: name,
                        task_category: category,
                        applicable_types: types
                    });
                } else {
                    res = await API.createTemplate({
                        task_name: name,
                        task_category: category,
                        applicable_types: types,
                        is_required: 0
                    });
                }
                
                if (res.success) {
                    showSuccess('Template salvo!');
                    closeTemplateModal();
                    loadTemplates();
                } else {
                    showError('Erro: ' + res.message);
                }
            } catch (e) {
                showError('Erro ao salvar template.');
            }
        }

        async function editTemplate(id) {
            openTemplateModal(id);
        }

        async function deleteTemplate(id) {
            const confirmed = await AppComponents.showConfirmModal({
                title: 'Excluir Template',
                message: 'Tem certeza que deseja excluir este template? Esta acao nao pode ser desfeita.',
                confirmText: 'Excluir',
                icon: 'delete',
                type: 'danger'
            });
            if (!confirmed) return;
            try {
                const res = await API.deleteTemplate(id);
                if (res.success) {
                    showSuccess('Template excluído!');
                    loadTemplates();
                } else {
                    showError('Erro: ' + res.message);
                }
            } catch (e) {
                showError('Erro ao excluir template.');
            }
        }

        async function loadChecklists() {
            try {
                const res = await API.getChecklists({ limit: 50 });
                if (res.success) {
                    document.getElementById('checklists-list').innerHTML = res.data.map(c => `
                        <div class="flex justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <p class="text-sm font-medium">${escapeHtml(c.client_name)}</p>
                                <p class="text-xs text-gray-500">${escapeHtml(c.technician_name)}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded ${c.approval_status === 'pending_approval' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100'}">${escapeHtml(c.approval_status || c.status)}</span>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function runSetup() {
            const confirmed = await AppComponents.showConfirmModal({
                title: 'Reconfigurar Templates',
                message: 'Deseja reconfigurar todos os templates? Isso pode sobrescrever configuracoes existentes.',
                confirmText: 'Reconfigurar',
                icon: 'settings_backup_restore',
                type: 'warning'
            });
            if (!confirmed) return;
            fetch('/api/setup-checklist.php')
                .then(r => r.json())
                .then(d => showSuccess(d.message));
        }

        function clearCache() {
            localStorage.clear();
            caches.keys().then(names => names.forEach(n => caches.delete(n)));
            showSuccess('Cache limpo!');
            setTimeout(() => location.reload(), 800);
        }

        // Users
        let usersData = [];
        
        async function loadUsers() {
            try {
                const res = await API.getUsers();
                if (res.success) {
                    usersData = res.data;
                    renderUsers();
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        function renderUsers() {
            const list = document.getElementById('users-list');
            if (usersData.length === 0) {
                list.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhum usuário encontrado</p>';
                return;
            }
            
            list.innerHTML = usersData.map(u => `
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(u.full_name)}</p>
                        <p class="text-xs text-gray-500">${escapeHtml(u.username)} • ${u.role === 'admin' ? 'Administrador' : 'Técnico'} ${u.active == 0 ? '• <span class="text-red-500">Inativo</span>' : ''}</p>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="editUser(${u.id})" class="p-2 text-blue-500 hover:bg-blue-100 rounded-lg">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                        <button onclick="deleteUser(${u.id})" class="p-2 text-red-500 hover:bg-red-100 rounded-lg">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        function openUserModal(id = null) {
            document.getElementById('modal-user').classList.remove('hidden');
            if (id) {
                // Converte para número para comparação consistente
                const userId = parseInt(id);
                const u = usersData.find(x => parseInt(x.id) === userId);
                
                if (!u) {
                    showError('Usuário não encontrado.');
                    return;
                }
                
                document.getElementById('user-title').textContent = 'Editar Usuário';
                document.getElementById('user-id').value = u.id;
                document.getElementById('user-fullname').value = u.full_name || '';
                document.getElementById('user-username').value = u.username || '';
                document.getElementById('user-email').value = u.email || '';
                document.getElementById('user-role').value = u.role || 'tecnico';
                document.getElementById('user-city').value = u.city || '';
                document.getElementById('user-cargo').value = u.cargo || '';
                document.getElementById('user-password').value = '';
                document.getElementById('user-pass-hint').textContent = '(deixe em branco para não alterar)';
                document.getElementById('user-username').disabled = true;
            } else {
                document.getElementById('user-title').textContent = 'Novo Usuário';
                document.getElementById('user-id').value = '';
                document.getElementById('user-fullname').value = '';
                document.getElementById('user-username').value = '';
                document.getElementById('user-email').value = '';
                document.getElementById('user-role').value = 'tecnico';
                document.getElementById('user-city').value = '';
                document.getElementById('user-cargo').value = '';
                document.getElementById('user-password').value = '';
                document.getElementById('user-pass-hint').textContent = '*';
                document.getElementById('user-username').disabled = false;
            }
        }
        
        function closeUserModal() {
            document.getElementById('modal-user').classList.add('hidden');
        }
        
        async function saveUser() {
            const id = document.getElementById('user-id').value;
            const data = {
                full_name: document.getElementById('user-fullname').value,
                email: document.getElementById('user-email').value,
                role: document.getElementById('user-role').value,
                city: document.getElementById('user-city').value,
                cargo: document.getElementById('user-cargo').value,
                password: document.getElementById('user-password').value
            };
            
            if (!data.full_name) {
                showError('Nome completo é obrigatório.');
                return;
            }

            if (!id) {
                data.username = document.getElementById('user-username').value;
                if (!data.username || !data.password) {
                    showError('Usuário e senha são obrigatórios.');
                    return;
                }
                if (data.password.length < 6) {
                    showError('Senha deve ter no mínimo 6 caracteres.');
                    return;
                }
            }
            
            try {
                let res;
                if (id) {
                    // Remove senha se estiver vazia
                    if (!data.password) delete data.password;
                    res = await API.updateUser(id, data);
                } else {
                    res = await API.createUser(data);
                }
                
                if (res.success) {
                    showSuccess('Usuário salvo!');
                    closeUserModal();
                    loadUsers();
                } else {
                    showError('Erro: ' + res.message);
                }
            } catch (e) {
                showError('Erro ao salvar usuário.');
            }
        }

        async function editUser(id) {
            openUserModal(id);
        }

        async function deleteUser(id) {
            const confirmed = await AppComponents.showConfirmModal({
                title: 'Desativar Usuario',
                message: 'Tem certeza que deseja desativar este usuario? Ele nao podera mais acessar o sistema.',
                confirmText: 'Desativar',
                icon: 'person_off',
                type: 'danger'
            });
            if (!confirmed) return;
            try {
                const res = await API.deleteUser(id);
                if (res.success) {
                    showSuccess('Usuário desativado!');
                    loadUsers();
                } else {
                    showError('Erro: ' + res.message);
                }
            } catch (e) {
                showError('Erro ao desativar usuário.');
            }
        }

        // Stats
        async function loadStats() {
            try {
                const res = await API.getChecklists({});
                if (res.success) {
                    const pending = res.data.filter(c => c.approval_status === 'pending_approval').length;
                    document.getElementById('stat-pending').textContent = pending;
                }
                
                const usersRes = await API.getUsers();
                if (usersRes.success) {
                    document.getElementById('stat-clients').textContent = usersRes.count;
                }
            } catch (e) {}
        }

        loadStats();
        loadTemplates();
        loadUsers();
        loadPushSubscriptions();

        // ==============================
        // Push Notifications
        // ==============================

        // VAPID Public Key (deve ser a mesma do servidor)
        const VAPID_PUBLIC_KEY = 'BCxBeZ9LpHe2nfk3QMHYdNrXdxB7E2hyIefVm7u6yGN5js0nvxXiNGRQE8FZC5E5MiHzqHDSAl1JIkvRrw25iMU';

        // Verifica o status da permissão de notificações
        function checkPushPermission() {
            const statusEl = document.getElementById('push-permission-status');
            const helpEl = document.getElementById('push-permission-help');
            const btnEl = document.getElementById('btn-enable-push');

            if (!('Notification' in window) || !('PushManager' in window)) {
                statusEl.textContent = 'Não suportado';
                statusEl.className = 'text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                helpEl.textContent = 'Seu navegador não suporta notificações push.';
                btnEl.disabled = true;
                btnEl.classList.add('hidden');
                return;
            }

            const permission = Notification.permission;

            if (permission === 'granted') {
                statusEl.textContent = 'Ativado';
                statusEl.className = 'text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                helpEl.textContent = 'Notificações ativas neste dispositivo.';
                btnEl.textContent = 'Reativar Notificações';
                btnEl.innerHTML = '<span class="material-symbols-outlined text-sm">refresh</span> Reativar Notificações';
            } else if (permission === 'denied') {
                statusEl.textContent = 'Bloqueado';
                statusEl.className = 'text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                helpEl.textContent = 'Permissão bloqueada. Vá em Configurações do navegador > Site > Notificações para desbloquear.';
                btnEl.disabled = true;
                btnEl.classList.add('opacity-50');
            } else {
                statusEl.textContent = 'Desativado';
                statusEl.className = 'text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                helpEl.textContent = 'Clique no botão abaixo para ativar notificações.';
            }
        }

        // Converte base64 para Uint8Array (para applicationServerKey)
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; i++) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        // Ativa notificações push
        async function enablePushNotifications() {
            const btnEl = document.getElementById('btn-enable-push');
            btnEl.disabled = true;
            btnEl.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Ativando...';

            try {
                // 1. Solicita permissão
                const permission = await Notification.requestPermission();
                
                if (permission !== 'granted') {
                    showError('Permissão de notificação negada.');
                    checkPushPermission();
                    btnEl.disabled = false;
                    btnEl.innerHTML = '<span class="material-symbols-outlined text-sm">notifications_active</span> Ativar Notificações';
                    return;
                }

                // 2. Verifica se Service Worker está registrado
                const registration = await navigator.serviceWorker.ready;

                // 3. Obtém ou cria subscription
                let subscription = await registration.pushManager.getSubscription();
                
                if (!subscription) {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                    });
                }

                // 4. Envia subscription para o servidor
                const res = await API.subscribePush(subscription);

                if (res.success) {
                    showSuccess('Notificações ativadas com sucesso!');
                    // Remove flags de dismissed do localStorage
                    localStorage.removeItem('push-banner-dismissed');
                    localStorage.removeItem('push-banner-dismissed-at');
                    loadPushSubscriptions();
                } else {
                    showError('Erro ao registrar: ' + res.message);
                }

            } catch (err) {
                console.error('Erro ao ativar push:', err);
                showError('Erro ao ativar notificações: ' + err.message);
            }

            checkPushPermission();
            btnEl.disabled = false;
            btnEl.innerHTML = '<span class="material-symbols-outlined text-sm">notifications_active</span> Ativar Notificações';
        }

        // Verifica permissão ao carregar a página
        checkPushPermission();

        async function loadPushSubscriptions() {
            const list = document.getElementById('push-subs-list');
            if (!list) return;
            try {
                const res = await API.listPushSubscriptions();
                if (res.success && res.data.length > 0) {
                    list.innerHTML = res.data.map(s => `
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="text-xs font-medium text-gray-900 dark:text-white">${escapeHtml(s.full_name || s.username)}</p>
                                <p class="text-[10px] text-gray-500">${new Date(s.created_at).toLocaleDateString('pt-BR')}</p>
                            </div>
                            <span class="size-2 rounded-full bg-green-500"></span>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p class="text-xs text-gray-500 text-center py-2">Nenhum dispositivo registrado</p>';
                }
            } catch (e) {
                list.innerHTML = '<p class="text-xs text-red-500 text-center py-2">Erro ao carregar</p>';
            }
        }

        // Preenche select de destinatários com usuários cadastrados
        async function loadPushTargets() {
            try {
                const res = await API.getUsers();
                if (res.success) {
                    const select = document.getElementById('push-target');
                    res.data.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.full_name || u.username;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {}
        }
        loadPushTargets();

        async function sendPushNotification() {
            const title = document.getElementById('push-title').value.trim() || 'Ondeline Tech';
            const body = document.getElementById('push-body').value.trim();
            const url = document.getElementById('push-url').value.trim() || '/dashboard.php';
            const userId = document.getElementById('push-target').value || null;

            if (!body) {
                showError('Digite uma mensagem para enviar.');
                return;
            }

            try {
                const res = await API.sendPush(title, body, url, userId ? parseInt(userId) : null);
                if (res.success) {
                    showSuccess(res.message);
                    document.getElementById('push-body').value = '';
                } else {
                    showError('Erro: ' + res.message);
                }
            } catch (e) {
                showError('Erro ao enviar notificação.');
            }
        }

        // ==============================
        // Auditoria
        // ==============================
        let auditFilters = { action_type: '', user_id: '', username: '', start_date: '', end_date: '' };
        let auditInitialized = false;
        let auditSearchTimeout = null;

        function initAuditoria() {
            if (!auditInitialized) {
                const today = new Date().toISOString().split('T')[0];
                const sevenDaysAgo = new Date();
                sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
                document.getElementById('audit-start-date').value = sevenDaysAgo.toISOString().split('T')[0];
                document.getElementById('audit-end-date').value = today;

                // Busca com debounce
                document.getElementById('audit-search-user').addEventListener('input', function(e) {
                    clearTimeout(auditSearchTimeout);
                    auditSearchTimeout = setTimeout(() => {
                        auditFilters.username = e.target.value.trim();
                        loadAuditLogs();
                    }, 500);
                });

                auditInitialized = true;
            }
            loadAuditLogs();
        }

        function setAuditActionFilter(btn) {
            document.querySelectorAll('.audit-filter-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white');
                b.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            });
            btn.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            btn.classList.add('bg-primary', 'text-white');
            auditFilters.action_type = btn.dataset.action;
            loadAuditLogs();
        }

        async function loadAuditLogs() {
            const container = document.getElementById('audit-logs-list');
            const countEl = document.getElementById('audit-total-count');
            if (!container) return;

            // Atualiza filtros de data
            auditFilters.start_date = document.getElementById('audit-start-date')?.value || '';
            auditFilters.end_date = document.getElementById('audit-end-date')?.value || '';
            auditFilters.user_id = document.getElementById('audit-filter-user')?.value || '';

            container.innerHTML = '<div class="flex items-center justify-center py-8"><div class="w-8 h-8 border-3 border-primary border-t-transparent rounded-full animate-spin"></div></div>';

            try {
                let url = '/api/get-audit-logs.php?limit=100';
                if (auditFilters.action_type) url += '&action_type=' + encodeURIComponent(auditFilters.action_type);
                if (auditFilters.user_id) url += '&user_id=' + encodeURIComponent(auditFilters.user_id);
                if (auditFilters.username) url += '&username=' + encodeURIComponent(auditFilters.username);
                if (auditFilters.start_date) url += '&start_date=' + encodeURIComponent(auditFilters.start_date);
                if (auditFilters.end_date) url += '&end_date=' + encodeURIComponent(auditFilters.end_date);

                const response = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + localStorage.getItem('auth_token') }
                });
                const result = await response.json();

                if (result.success) {
                    countEl.textContent = result.total + ' registro(s)';

                    // Popula dropdown de usuarios
                    if (result.data && result.data.length > 0) {
                        populateAuditUserDropdown(result.data);
                    }

                    if (result.data && result.data.length > 0) {
                        container.innerHTML = result.data.map(log => renderAuditLogCard(log)).join('');
                    } else {
                        container.innerHTML = '<div class="text-center py-8"><span class="material-symbols-outlined text-4xl text-gray-300">history</span><p class="text-gray-500 text-sm mt-2">Nenhum registro encontrado</p></div>';
                    }
                } else {
                    throw new Error(result.message || 'Erro');
                }
            } catch (error) {
                countEl.textContent = 'Erro';
                container.innerHTML = '<div class="text-center py-8"><span class="material-symbols-outlined text-4xl text-red-300">error</span><p class="text-red-500 text-sm mt-2">' + escapeHtml(error.message) + '</p></div>';
            }
        }

        function populateAuditUserDropdown(logs) {
            const select = document.getElementById('audit-filter-user');
            if (!select) return;
            const currentValue = select.value;
            const usersMap = {};
            logs.forEach(log => {
                if (log.user_id && !usersMap[log.user_id]) {
                    usersMap[log.user_id] = log.user_full_name || log.username || 'Usuário ' + log.user_id;
                }
            });
            const existingCount = select.options.length;
            const newUsers = Object.keys(usersMap);
            if (newUsers.length + 1 === existingCount) return;

            select.innerHTML = '<option value="">Todos os usuários</option>';
            newUsers.sort((a, b) => usersMap[a].localeCompare(usersMap[b])).forEach(userId => {
                const opt = document.createElement('option');
                opt.value = userId;
                opt.textContent = usersMap[userId];
                select.appendChild(opt);
            });
            if (currentValue) select.value = currentValue;
        }

        function renderAuditLogCard(log) {
            const icons = {
                'login': 'login', 'new_client_page': 'add_circle', 'client_created': 'person_add',
                'client_viewed': 'visibility', 'link_equipment_page': 'devices', 'equipment_linked': 'router',
                'client_updated': 'edit', 'client_deleted': 'delete'
            };
            const colors = {
                'login': 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
                'new_client_page': 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
                'client_created': 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                'client_viewed': 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
                'link_equipment_page': 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
                'equipment_linked': 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
                'client_updated': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
                'client_deleted': 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
            };
            const labels = {
                'login': 'Login', 'new_client_page': 'Acesso Cadastro', 'client_created': 'Novo Cadastro',
                'client_viewed': 'Visualização', 'link_equipment_page': 'Acesso Vincular',
                'equipment_linked': 'Vinculação', 'client_updated': 'Edição', 'client_deleted': 'Exclusão'
            };

            const icon = icons[log.action_type] || 'history';
            const color = colors[log.action_type] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
            const label = labels[log.action_type] || log.action_type;
            const userName = log.user_full_name || log.username || 'Sistema';

            let detailsHtml = '';
            if (log.details) {
                const detailLabels = { 'plan': 'Plano', 'city': 'Cidade', 'status': 'Status', 'old_serial': 'Serial Antigo', 'new_serial': 'Serial Novo', 'phone': 'Telefone' };
                const parts = Object.keys(log.details).filter(k => log.details[k]).map(k =>
                    '<span class="text-[10px] text-gray-500">' + (detailLabels[k] || k) + ': ' + escapeHtml(log.details[k]) + '</span>'
                ).join(' | ');
                if (parts) detailsHtml = '<div class="mt-1">' + parts + '</div>';
            }

            return `<div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="size-9 rounded-full ${color} flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-lg">${icon}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${label}</p>
                        <p class="text-[10px] text-gray-400 flex-shrink-0" title="${escapeHtml(log.created_at_formatted)}">${escapeHtml(log.created_at_relative)}</p>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">${escapeHtml(log.action_description)}</p>
                    ${log.entity_name ? '<p class="text-xs text-gray-500">' + escapeHtml(log.entity_name) + '</p>' : ''}
                    ${detailsHtml}
                    <p class="text-[10px] text-gray-400 mt-1">${escapeHtml(userName)}</p>
                </div>
            </div>`;
        }

        // Testa configuração de push
        async function testPushConfig() {
            const resultEl = document.getElementById('push-test-result');
            resultEl.classList.remove('hidden');
            resultEl.innerHTML = '<p class="text-blue-500">Testando configuração...</p>';

            try {
                const res = await fetch('/api/push.php?test=1', {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('token')
                    }
                });
                const data = await res.json();

                if (data.success) {
                    let html = '<div class="bg-gray-100 dark:bg-gray-700 rounded p-2 max-h-40 overflow-y-auto">';
                    html += '<p class="font-medium mb-1">' + (data.all_passed ? '✅ Todos os testes OK' : '⚠️ Alguns testes falharam') + '</p>';
                    
                    for (const [key, value] of Object.entries(data.tests)) {
                        const icon = value === true ? '✓' : (value === false ? '✗' : '');
                        const color = value === true ? 'text-green-600' : (value === false ? 'text-red-600' : 'text-gray-600');
                        html += `<p class="${color}">${icon} ${escapeHtml(key)}: ${typeof value === 'boolean' ? (value ? 'OK' : 'FALHOU') : escapeHtml(String(value))}</p>`;
                    }
                    html += '</div>';
                    resultEl.innerHTML = html;
                } else {
                    resultEl.innerHTML = '<p class="text-red-500">Erro: ' + escapeHtml(data.message) + '</p>';
                }
            } catch (e) {
                resultEl.innerHTML = '<p class="text-red-500">Erro ao testar: ' + escapeHtml(e.message) + '</p>';
            }
        }

    </script>
</body>
</html>
