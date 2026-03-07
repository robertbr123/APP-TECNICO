<!DOCTYPE html>
<html class="light" lang="pt-BR"><head>
<title>Mapa de Clientes - Ondeline</title>
<?php include 'partials/head.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
#map {
        height: calc(100dvh - 140px);
        width: 100%;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
    }
    .leaflet-popup-content {
        font-family: 'Inter', sans-serif;
    }
    .custom-marker {
        background: #135bec;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    .marker-active {
        background: #22c55e;
        border-color: #16a34a;
    }
    .marker-pending {
        background: #f59e0b;
    }
    .marker-inactive {
        background: #ef4444;
    }

    /* Marcador de técnico — roxo pulsante */
    .marker-technician {
        background: #7c3aed;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(124,58,237,0.5);
        animation: tech-pulse 2s ease-in-out infinite;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-family: 'Material Symbols Outlined';
        font-size: 14px;
    }
    .marker-technician-inactive {
        background: #9ca3af;
        animation: none;
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
    @keyframes tech-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(124,58,237,0.5); }
        50%       { box-shadow: 0 0 0 8px rgba(124,58,237,0); }
    }
</style>
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-40 w-full bg-white/80 dark:bg-black/80 ios-blur border-b border-gray-200/50 dark:border-white/10 safe-top">
<div class="flex items-center justify-between px-4 h-16">
<div class="flex items-center gap-3">
<div class="relative">
<div id="user-avatar" class="size-10 rounded-full border border-gray-100 dark:border-gray-800 bg-center bg-cover bg-primary/10 flex items-center justify-center">
<span class="material-symbols-outlined text-primary text-xl">person</span>
</div>
<div class="absolute bottom-0 right-0 size-3 bg-green-500 border-2 border-white dark:border-black rounded-full"></div>
</div>
<div class="flex flex-col">
<span id="header-user-name" class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white leading-none">Ondeline</span>
<span id="header-user-role" class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tecnico de Campo</span>
</div>
</div>
<button class="flex items-center justify-center size-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
<span class="material-symbols-outlined text-[22px]">notifications</span>
</button>
</div>
</header>

<main class="pb-32">
<!-- Busca de Clientes -->
<div class="bg-white/80 dark:bg-card-dark/80 backdrop-blur-xl px-4 py-3 border-b border-gray-100/50 dark:border-white/5">
<div class="relative">
    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xl">search</span>
    <input type="text" id="map-search" placeholder="Buscar por nome, CPF ou PPPoE..." class="w-full pl-10 pr-10 py-2.5 bg-gray-100 dark:bg-gray-800 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-500 border-0 focus:ring-2 focus:ring-primary/50"/>
    <button id="map-search-clear" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
        <span class="material-symbols-outlined text-xl">close</span>
    </button>
</div>
<div id="search-results" class="hidden mt-2 max-h-48 overflow-y-auto rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg"></div>
</div>

<!-- Filtros com glassmorphism -->
<div class="bg-white/80 dark:bg-card-dark/80 backdrop-blur-xl px-4 py-3 border-b border-gray-100/50 dark:border-white/5 overflow-x-auto">
<div class="flex gap-2 min-w-max items-center">
<button class="filter-btn active px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-primary to-blue-500 text-white shadow-lg shadow-primary/30 transition-all" data-filter="all">
    <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1">grid_view</span>
        Todos
    </span>
</button>
<button class="filter-btn px-4 py-2 rounded-full text-sm font-medium bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-gray-200/50 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 transition-all" data-filter="active">
    <span class="flex items-center gap-1.5">
        <span class="size-2 rounded-full bg-green-500"></span>
        Ativos
    </span>
</button>
<button class="filter-btn px-4 py-2 rounded-full text-sm font-medium bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-gray-200/50 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 transition-all" data-filter="pending">
    <span class="flex items-center gap-1.5">
        <span class="size-2 rounded-full bg-orange-500"></span>
        Pendentes
    </span>
</button>
<button class="filter-btn px-4 py-2 rounded-full text-sm font-medium bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-gray-200/50 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-700 transition-all" data-filter="inactive">
    <span class="flex items-center gap-1.5">
        <span class="size-2 rounded-full bg-red-500"></span>
        Inativos
    </span>
</button>
<!-- Botão Técnicos — visível somente para admin -->
<button id="btn-technicians" class="hidden px-4 py-2 rounded-full text-sm font-medium bg-white/60 dark:bg-gray-800/60 backdrop-blur-sm border border-gray-200/50 dark:border-white/10 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all" onclick="toggleTechnicians()">
    <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1">engineering</span>
        Técnicos
    </span>
</button>
<button id="btn-reload" class="ml-auto px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg shadow-blue-500/30 flex items-center gap-1.5 hover:shadow-blue-500/50 transition-all btn-press" onclick="loadClients()">
    <span class="material-symbols-outlined text-lg">refresh</span>
    Atualizar
</button>
</div>
</div>

<!-- Mapa -->
<div id="map"></div>

<!-- Estatísticas com glassmorphism -->
<div class="fixed bottom-20 left-3 right-3 z-30">
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border border-gray-200/50 dark:border-white/10 rounded-2xl shadow-2xl shadow-black/10 dark:shadow-black/40 p-4">
<div class="grid grid-cols-4 gap-3" id="stats-grid">
<div class="text-center p-2 rounded-xl bg-gray-50/50 dark:bg-white/5">
<p class="text-2xl font-bold bg-gradient-to-br from-gray-700 to-gray-900 dark:from-white dark:to-gray-300 bg-clip-text text-transparent" id="stat-total">0</p>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</p>
</div>
<div class="text-center p-2 rounded-xl bg-green-50/50 dark:bg-green-500/10">
<p class="text-2xl font-bold text-green-600" id="stat-active">0</p>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ativos</p>
</div>
<div class="text-center p-2 rounded-xl bg-orange-50/50 dark:bg-orange-500/10">
<p class="text-2xl font-bold text-orange-500" id="stat-pending">0</p>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendentes</p>
</div>
<div class="text-center p-2 rounded-xl bg-blue-50/50 dark:bg-blue-500/10">
<p class="text-2xl font-bold text-blue-600" id="stat-with-location">0</p>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com GPS</p>
</div>
</div>
<!-- Linha de técnicos — visível somente admin -->
<div id="tech-stats-row" class="hidden grid grid-cols-3 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-white/10">
<div class="text-center p-2 rounded-xl bg-purple-50/50 dark:bg-purple-500/10">
<p class="text-2xl font-bold text-purple-600" id="stat-tech-active">0</p>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Em campo</p>
</div>
<div class="text-center p-2 rounded-xl bg-indigo-50/50 dark:bg-indigo-500/10">
<p class="text-2xl font-bold text-indigo-500" id="stat-tech-total">0</p>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Técnicos</p>
</div>
<div class="text-center p-2 rounded-xl bg-gray-50/50 dark:bg-white/5 flex flex-col items-center justify-center gap-1">
<span id="tech-refresh-indicator" class="size-2 rounded-full bg-green-500 animate-pulse"></span>
<p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ao vivo 30s</p>
</div>
</div>
</div>
</div>
</main>

<?php $activePage = 'rotas'; include 'partials/bottom-nav.php'; ?>

<!-- Scripts do App -->
<script src="js/api.js"></script>
<script src="js/utils.js"></script>
<script src="js/components.js"></script>
<script src="js/app.js"></script>
<script src="js/feedback.js"></script>
<script src="js/ui-enhancements.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    let map = null;
    let markers = [];
    let clients = [];
    let currentFilter = 'all';
    let routeLayer = null;

    // ── Técnicos (admin only) ──
    let techMarkers = [];
    let showTechnicians = true;
    let techRefreshTimer = null;
    const isAdmin = (JSON.parse(localStorage.getItem('user_data') || '{}')).role === 'admin';

    // Inicializa mapa
    function initMap() {
        map = L.map('map', {
            zoomControl: false
        }).setView([-3.1190, -60.0217], 13); // Centro do Amazonas

        // Adiciona controle de zoom customizado
        L.control.zoom({
            position: 'topright'
        }).addTo(map);

        // Camada do OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
    }

    // Carrega clientes
    async function loadClients() {
        showLoading('Carregando clientes...');

        try {
            // Busca todos os clientes (limite maior para mapa)
            const response = await API.getClients({ limit: 1000 });
            console.log('[Mapa] Resposta da API:', response);
            
            if (response.success) {
                clients = response.data;
                console.log('[Mapa] Total de clientes recebidos:', clients.length);
                
                // Debug: mostra clientes com lat/lng
                const clientsComLoc = clients.filter(c => c.latitude && c.longitude);
                console.log('[Mapa] Clientes com latitude/longitude no response:', clientsComLoc.map(c => ({
                    nome: c.name,
                    lat: c.latitude,
                    lng: c.longitude
                })));
                
                addMarkers(clients);
                updateStats(clients);
            }
        } catch (error) {
            console.error('[Mapa] Erro:', error);
            showError('Erro ao carregar clientes: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    // Adiciona marcadores
    function addMarkers(clientList) {
        // Remove marcadores existentes
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];

        // Filtra clientes com localização
        const clientsWithLocation = clientList.filter(client => {
            const lat = parseFloat(client.latitude);
            const lng = parseFloat(client.longitude);
            return !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0;
        });

        console.log('[Mapa] Clientes com localização:', clientsWithLocation.length, 'de', clientList.length);

        if (clientsWithLocation.length === 0) {
            showInfo('Nenhum cliente com localização encontrada');
            return;
        }

        // Adiciona marcadores
        clientsWithLocation.forEach(client => {
            const isActive = client.active == 1 || client.status === 'ativo';
            const statusClass = isActive ? 'marker-active' : (client.status === 'pendente' ? 'marker-pending' : 'marker-inactive');
            
            const icon = L.divIcon({
                className: 'custom-marker ' + statusClass,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            const lat = parseFloat(client.latitude);
            const lng = parseFloat(client.longitude);
            
            const popupId = 'sgp-status-' + client.cpf.replace(/\D/g, '');
            const marker = L.marker([lat, lng], { icon })
                .addTo(map)
                .bindPopup(`
                    <div class="p-2 min-w-[220px]">
                        <h3 class="font-bold text-sm mb-1">${escapeHtml(client.name)}</h3>
                        <p class="text-xs text-gray-600 mb-1">CPF: ${Utils.formatCPF(client.cpf)}</p>
                        <p class="text-xs text-gray-600 mb-1">${escapeHtml(client.address || '')}, ${escapeHtml(client.number || '')}</p>
                        <p class="text-xs text-gray-600 mb-2">${escapeHtml(client.city || '')}</p>
                        <div id="${popupId}" class="flex items-center gap-2 mb-2 px-2 py-1.5 rounded-lg bg-gray-50" style="min-height:28px">
                            <div class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></div>
                            <span class="text-xs text-yellow-600">Consultando SGP...</span>
                        </div>
                        <a href="detalher.php?cpf=${client.cpf}"
                           class="block text-center px-3 py-1.5 bg-primary text-white text-xs rounded-lg font-medium">
                            Ver Detalhes
                        </a>
                    </div>
                `, { maxWidth: 260 });

            marker.clientData = client;
            marker.on('popupopen', () => checkSgpStatus(client, popupId));
            markers.push(marker);
        });

        // Ajusta visualização
        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    // Atualiza estatísticas
    function updateStats(clientList) {
        var userData = JSON.parse(localStorage.getItem('user_data') || '{}');
        document.getElementById('stat-total').textContent = (userData.role === 'admin') ? clientList.length : '100+';
        document.getElementById('stat-active').textContent = clientList.filter(c => c.active == 1 || c.status === 'ativo').length;
        document.getElementById('stat-pending').textContent = clientList.filter(c => c.status === 'pendente').length;
        document.getElementById('stat-with-location').textContent = clientList.filter(c => {
            const lat = parseFloat(c.latitude);
            const lng = parseFloat(c.longitude);
            return !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0;
        }).length;
    }

    // Filtros
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove classe ativa de todos
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('active', 'bg-gradient-to-r', 'from-primary', 'to-blue-500', 'text-white', 'shadow-lg', 'shadow-primary/30', 'font-semibold');
                b.classList.add('bg-white/60', 'dark:bg-gray-800/60', 'border', 'border-gray-200/50', 'dark:border-white/10', 'text-gray-600', 'dark:text-gray-400', 'font-medium');
            });

            // Adiciona classe ativa ao clicado
            btn.classList.add('active', 'bg-gradient-to-r', 'from-primary', 'to-blue-500', 'text-white', 'shadow-lg', 'shadow-primary/30', 'font-semibold');
            btn.classList.remove('bg-white/60', 'dark:bg-gray-800/60', 'border', 'border-gray-200/50', 'dark:border-white/10', 'text-gray-600', 'dark:text-gray-400', 'font-medium');

            currentFilter = btn.dataset.filter;
            filterClients();
            
            // Haptic feedback
            if (typeof UIEnhancements !== 'undefined') {
                UIEnhancements.hapticLight();
            }
        });
    });

    // Filtra clientes
    function filterClients() {
        let filtered = clients;

        switch (currentFilter) {
            case 'active':
                filtered = clients.filter(c => c.active == 1 || c.status === 'ativo');
                break;
            case 'pending':
                filtered = clients.filter(c => c.status === 'pendente');
                break;
            case 'inactive':
                filtered = clients.filter(c => c.active == 0 || c.status === 'inativo');
                break;
        }

        addMarkers(filtered);
    }

    // Calcular rota otimizada
    function calculateRoute() {
        const clientsWithLocation = clients.filter(c => c.latitude && c.longitude);
        
        if (clientsWithLocation.length < 2) {
            showWarning('Precisa de pelo menos 2 clientes com localização para calcular rota');
            return;
        }

        showLoading('Calculando rota...');

        // Obtém localização atual
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const currentLat = position.coords.latitude;
                const currentLon = position.coords.longitude;

                // Calcula distâncias de todos os clientes
                const clientsWithDistance = clientsWithLocation.map(client => ({
                    ...client,
                    distance: Utils.calculateDistance(
                        currentLat, currentLon,
                        client.latitude, client.longitude
                    )
                }));

                // Ordena por distância
                clientsWithDistance.sort((a, b) => a.distance - b.distance);

                // Pega os 10 mais próximos
                const nearestClients = clientsWithDistance.slice(0, 10);

                // Desenha rota
                if (routeLayer) {
                    map.removeLayer(routeLayer);
                }

                const routePoints = [[currentLat, currentLon]];
                nearestClients.forEach(client => {
                    routePoints.push([client.latitude, client.longitude]);
                });

                routeLayer = L.polyline(routePoints, {
                    color: '#135bec',
                    weight: 4,
                    opacity: 0.7,
                    dashArray: '10, 10'
                }).addTo(map);

                // Ajusta visualização
                map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

                // Mostra resumo
                const totalDistance = nearestClients.reduce((sum, client) => sum + client.distance, 0);
                showSuccess(`Rota calculada! ${nearestClients.length} clientes, distância total: ${totalDistance.toFixed(1)} km`);

                // Abre Google Maps com rota
                const waypoints = nearestClients.slice(0, 5).map(c => `${c.latitude},${c.longitude}`).join('/');
                Utils.openRouteUrl(currentLat, currentLon, nearestClients[0].latitude, nearestClients[0].longitude);

                hideLoading();
            },
            (error) => {
                showWarning('Não foi possível obter sua localização. Usando centro do mapa como ponto de partida.');
                
                // Fallback: usa primeiro cliente como ponto de partida
                const firstClient = clientsWithLocation[0];
                const remaining = clientsWithLocation.slice(1, 11);
                
                if (routeLayer) {
                    map.removeLayer(routeLayer);
                }

                const routePoints = remaining.map(c => [c.latitude, c.longitude]);
                routePoints.unshift([firstClient.latitude, firstClient.longitude]);

                routeLayer = L.polyline(routePoints, {
                    color: '#135bec',
                    weight: 4,
                    opacity: 0.7
                }).addTo(map);

                map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
                hideLoading();
            },
            { enableHighAccuracy: true }
        );
    }

    // ==========================================
    // BUSCA DE CLIENTES NO MAPA
    // ==========================================
    var searchInput = document.getElementById('map-search');
    var searchResults = document.getElementById('search-results');
    var searchClear = document.getElementById('map-search-clear');
    var searchTimeout = null;

    searchInput.addEventListener('input', function() {
        var query = searchInput.value.trim().toLowerCase();
        clearTimeout(searchTimeout);

        if (query.length === 0) {
            searchResults.classList.add('hidden');
            searchClear.classList.add('hidden');
            return;
        }

        searchClear.classList.remove('hidden');

        searchTimeout = setTimeout(function() {
            var results = clients.filter(function(c) {
                var name = (c.name || '').toLowerCase();
                var cpf = (c.cpf || '').replace(/\D/g, '');
                var pppoe = (c.pppoe_user || '').toLowerCase();
                var queryClean = query.replace(/\D/g, '');

                return name.indexOf(query) !== -1 ||
                       (queryClean.length > 2 && cpf.indexOf(queryClean) !== -1) ||
                       pppoe.indexOf(query) !== -1;
            }).slice(0, 10);

            if (results.length === 0) {
                searchResults.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Nenhum cliente encontrado</div>';
                searchResults.classList.remove('hidden');
                return;
            }

            var html = '';
            results.forEach(function(client) {
                var hasLocation = client.latitude && client.longitude &&
                    parseFloat(client.latitude) !== 0 && parseFloat(client.longitude) !== 0;

                html += '<div class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0" data-search-cpf="' + client.cpf + '">' +
                    '<div class="size-8 rounded-full ' + (hasLocation ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-800') + ' flex items-center justify-center flex-shrink-0">' +
                        '<span class="material-symbols-outlined text-sm ' + (hasLocation ? 'text-green-600' : 'text-gray-400') + '">' + (hasLocation ? 'location_on' : 'location_off') + '</span>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-medium text-gray-900 dark:text-white truncate">' + client.name + '</p>' +
                        '<p class="text-[11px] text-gray-500">' +
                            (client.cpf ? 'CPF: ' + client.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '') +
                            (client.pppoe_user ? ' | PPPoE: ' + client.pppoe_user : '') +
                        '</p>' +
                    '</div>' +
                    (hasLocation ? '<span class="material-symbols-outlined text-primary text-lg">my_location</span>' : '') +
                '</div>';
            });

            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');

            // Click handlers
            searchResults.querySelectorAll('[data-search-cpf]').forEach(function(el) {
                el.addEventListener('click', function() {
                    var cpf = el.dataset.searchCpf;
                    var client = clients.find(function(c) { return c.cpf === cpf; });

                    if (client) {
                        var lat = parseFloat(client.latitude);
                        var lng = parseFloat(client.longitude);

                        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                            // Fly to client on map
                            map.flyTo([lat, lng], 17, { duration: 1.5 });

                            // Find and open marker popup
                            markers.forEach(function(marker) {
                                if (marker.clientData && marker.clientData.cpf === cpf) {
                                    setTimeout(function() {
                                        marker.openPopup();
                                    }, 1600);
                                }
                            });
                        } else {
                            showWarning('Cliente "' + client.name + '" não possui localização GPS registrada');
                        }
                    }

                    searchResults.classList.add('hidden');
                    searchInput.value = client ? client.name : '';
                });
            });
        }, 200);
    });

    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchResults.classList.add('hidden');
        searchClear.classList.add('hidden');
        searchInput.focus();
    });

    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });

    // ══════════════════════════════════════════════
    // TÉCNICOS EM TEMPO REAL — somente admin
    // ══════════════════════════════════════════════

    function buildTechIcon(isActive) {
        return L.divIcon({
            className: 'custom-marker marker-technician' + (isActive ? '' : ' marker-technician-inactive'),
            html: '<span style="font-family:\'Material Symbols Outlined\';font-size:14px;line-height:1;user-select:none">engineering</span>',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -20]
        });
    }

    function timeAgo(minutesAgo) {
        if (minutesAgo < 1)  return 'agora mesmo';
        if (minutesAgo < 60) return `${minutesAgo} min atrás`;
        const h = Math.floor(minutesAgo / 60);
        return `${h}h atrás`;
    }

    async function loadTechnicians() {
        if (!isAdmin || !showTechnicians) return;

        try {
            const res = await API.getActiveTechnicians();
            if (!res.success) return;

            const techs = res.data || [];

            // Remove marcadores anteriores
            techMarkers.forEach(m => map.removeLayer(m));
            techMarkers = [];

            techs.forEach(tech => {
                const lat = parseFloat(tech.latitude);
                const lng = parseFloat(tech.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                const active    = tech.is_active == 1 && tech.minutes_ago < 60;
                const icon      = buildTechIcon(active);
                const minsAgo   = parseInt(tech.minutes_ago) || 0;
                const accuracy  = tech.accuracy ? `${Math.round(tech.accuracy)}m` : '—';
                const lastAct   = (tech.last_activity || 'desconhecido').replace('_', ' ');
                const statusBadge = active
                    ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700"><span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block"></span>Ativo</span>'
                    : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500">Inativo</span>';

                const popup = `
                    <div style="min-width:200px;font-family:'Inter',sans-serif">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                            <div style="width:36px;height:36px;border-radius:50%;background:#7c3aed;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <span style="font-family:'Material Symbols Outlined';color:white;font-size:18px">engineering</span>
                            </div>
                            <div>
                                <p style="font-weight:700;font-size:13px;margin:0;color:#111">${escapeHtml(tech.full_name)}</p>
                                <p style="font-size:11px;color:#6b7280;margin:2px 0 0">@${escapeHtml(tech.username)}</p>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            ${statusBadge}
                            <span style="font-size:11px;color:#9ca3af">${timeAgo(minsAgo)}</span>
                        </div>
                        <div style="background:#f9fafb;border-radius:8px;padding:8px;font-size:11px;color:#6b7280;margin-bottom:8px">
                            <div style="display:flex;justify-content:space-between;margin-bottom:3px">
                                <span>Atividade</span><span style="color:#374151;font-weight:500">${lastAct}</span>
                            </div>
                            <div style="display:flex;justify-content:space-between">
                                <span>Precisão GPS</span><span style="color:#374151;font-weight:500">${accuracy}</span>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                            <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}"
                               target="_blank" rel="noopener"
                               style="display:flex;align-items:center;justify-content:center;gap:4px;padding:7px;background:#f3f4f6;border-radius:8px;text-decoration:none;font-size:11px;font-weight:600;color:#374151">
                                <span style="font-family:'Material Symbols Outlined';font-size:14px">map</span>Maps
                            </a>
                            <a href="ordens.php?new=1&technician_id=${tech.user_id}"
                               style="display:flex;align-items:center;justify-content:center;gap:4px;padding:7px;background:#7c3aed;border-radius:8px;text-decoration:none;font-size:11px;font-weight:600;color:white">
                                <span style="font-family:'Material Symbols Outlined';font-size:14px">add_task</span>Criar OS
                            </a>
                        </div>
                    </div>`;

                const m = L.marker([lat, lng], { icon, zIndexOffset: 1000 })
                    .addTo(map)
                    .bindPopup(popup, { maxWidth: 260 });

                m.techData = tech;
                techMarkers.push(m);
            });

            // Atualiza stats
            document.getElementById('stat-tech-total').textContent  = techs.length;
            document.getElementById('stat-tech-active').textContent = techs.filter(t => t.is_active == 1 && t.minutes_ago < 60).length;

        } catch (e) {
            // silencioso — não interrompe o mapa
        }
    }

    function toggleTechnicians() {
        showTechnicians = !showTechnicians;
        const btn = document.getElementById('btn-technicians');

        if (showTechnicians) {
            btn.classList.add('text-purple-600', 'dark:text-purple-400');
            btn.classList.remove('text-gray-400', 'dark:text-gray-600', 'opacity-50');
            loadTechnicians();
        } else {
            btn.classList.remove('text-purple-600', 'dark:text-purple-400');
            btn.classList.add('text-gray-400', 'dark:text-gray-600', 'opacity-50');
            techMarkers.forEach(m => map.removeLayer(m));
            techMarkers = [];
        }
    }

    function startTechRefresh() {
        if (techRefreshTimer) clearInterval(techRefreshTimer);
        techRefreshTimer = setInterval(() => {
            if (showTechnicians) {
                // pisca o indicador
                const ind = document.getElementById('tech-refresh-indicator');
                if (ind) { ind.style.opacity = '0'; setTimeout(() => ind.style.opacity = '1', 300); }
                loadTechnicians();
            }
        }, 30000); // 30 segundos
    }

    // Inicializa
    initMap();
    loadClients();

    // Admin: exibe controles e carrega técnicos
    if (isAdmin) {
        document.getElementById('btn-technicians').classList.remove('hidden');
        document.getElementById('tech-stats-row').classList.remove('hidden');
        // grid passa de 4 para 4 colunas (mantém) + linha extra de técnicos
        loadTechnicians();
        startTechRefresh();
    }

    // ══════════════════════════════════════════════
    // CONSULTA SGP SOB DEMANDA (ao abrir popup)
    // ══════════════════════════════════════════════
    const sgpCache = {}; // cache por CPF para não reconsultar

    async function checkSgpStatus(client, popupId) {
        const cpf = (client.cpf || '').replace(/\D/g, '');
        if (!cpf) return;

        // Se já consultou, usa cache
        if (sgpCache[cpf]) {
            renderSgpStatus(popupId, sgpCache[cpf]);
            return;
        }

        const el = document.getElementById(popupId);
        if (!el) return;

        const token = API.getToken();
        const headers = { 'Content-Type': 'application/json' };
        if (token) headers['Authorization'] = 'Bearer ' + token;

        try {
            // 1. Se já tem contrato salvo no banco, usa direto
            let contrato = client.contrato || null;
            let servidor = null;

            if (!contrato) {
                // Busca cliente no SGP pelo CPF
                const res = await fetch('/api/sgp-status.php', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({ action: 'buscar_cliente', cpf })
                });
                const data = await res.json();

                if (!data.success || !data.data?.clientes?.length) {
                    sgpCache[cpf] = { status: 'not_found' };
                    renderSgpStatus(popupId, sgpCache[cpf]);
                    return;
                }

                servidor = data.servidor || null;
                const sgpClient = data.data.clientes[0];
                if (sgpClient.contratos?.length) {
                    contrato = sgpClient.contratos[0].id || sgpClient.contratos[0].contrato;
                }
            }

            if (!contrato) {
                sgpCache[cpf] = { status: 'no_contract' };
                renderSgpStatus(popupId, sgpCache[cpf]);
                return;
            }

            // 2. Verifica acesso (online/offline)
            const bodyData = { action: 'verificar_acesso', contrato: String(contrato) };
            if (servidor) bodyData.servidor = servidor;

            const res2 = await fetch('/api/sgp-status.php', {
                method: 'POST',
                headers,
                body: JSON.stringify(bodyData)
            });
            const data2 = await res2.json();

            if (data2.success && data2.data) {
                const msg = data2.data.msg || '';
                const isOnline = msg.toLowerCase().includes('online');
                sgpCache[cpf] = { status: isOnline ? 'online' : 'offline', msg };
            } else {
                sgpCache[cpf] = { status: 'error' };
            }
        } catch (e) {
            console.error('SGP check error:', e);
            sgpCache[cpf] = { status: 'error' };
        }

        renderSgpStatus(popupId, sgpCache[cpf]);
    }

    function renderSgpStatus(popupId, result) {
        const el = document.getElementById(popupId);
        if (!el) return;

        const configs = {
            online:      { dot: 'bg-green-500', bg: 'bg-green-50', text: 'text-green-700', label: 'Online' },
            offline:     { dot: 'bg-red-500',   bg: 'bg-red-50',   text: 'text-red-700',   label: 'Offline' },
            not_found:   { dot: 'bg-gray-400',  bg: 'bg-gray-50',  text: 'text-gray-500',  label: 'Não encontrado no SGP' },
            no_contract: { dot: 'bg-gray-400',  bg: 'bg-gray-50',  text: 'text-gray-500',  label: 'Sem contrato' },
            error:       { dot: 'bg-orange-400', bg: 'bg-orange-50', text: 'text-orange-600', label: 'Erro ao consultar' }
        };

        const c = configs[result.status] || configs.error;
        el.className = 'flex items-center gap-2 mb-2 px-2 py-1.5 rounded-lg ' + c.bg;
        el.innerHTML =
            '<div class="w-2.5 h-2.5 rounded-full ' + c.dot + ' flex-shrink-0"></div>' +
            '<span class="text-xs font-semibold ' + c.text + '">' + escapeHtml(result.msg || c.label) + '</span>';
    }

    // Expõe funções globalmente
    window.initMap     = initMap;
    window.loadClients = loadClients;
    window.toggleTechnicians = toggleTechnicians;
});
</script>
</body></html>