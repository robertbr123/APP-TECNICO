<!DOCTYPE html>
<html class="light" lang="pt-br">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport"/>
<title>Sem Conexão — Ondeline</title>
<meta name="theme-color" content="#135bec"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
<link rel="manifest" href="manifest.json"/>
<link rel="icon" type="image/svg+xml" href="icons/icon.svg"/>
<link rel="apple-touch-icon" href="logo.png"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#135bec",
                    "background-light": "#F2F2F7",
                    "background-dark": "#000000",
                    "card-light": "#FFFFFF",
                    "card-dark": "#1C1C1E"
                },
                fontFamily: { "display": ["Inter", "sans-serif"] },
                borderRadius: {
                    "ios": "12px",
                    "ios-lg": "16px",
                    "ios-xl": "20px"
                }
            }
        }
    }

    // Aplica tema salvo imediatamente (evita flash)
    (function() {
        const saved = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (saved === 'dark' || (!saved && prefersDark)) {
            document.documentElement.classList.replace('light', 'dark');
        }
    })();
</script>
<style type="text/tailwindcss">
    @layer base {
        body {
            @apply bg-background-light dark:bg-background-dark font-display text-[#000000] dark:text-white antialiased;
            -webkit-tap-highlight-color: transparent;
        }
    }
    .wifi-animation {
        animation: wifi-pulse 2s ease-in-out infinite;
    }
    @keyframes wifi-pulse {
        0%, 100% { opacity: 0.3; transform: scale(0.95); }
        50% { opacity: 1; transform: scale(1); }
    }
</style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center px-6">

    <div class="w-full max-w-sm flex flex-col items-center text-center gap-6">

        <!-- Ícone animado -->
        <div class="wifi-animation">
            <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-5xl text-gray-400 dark:text-gray-500">
                    wifi_off
                </span>
            </div>
        </div>

        <!-- Título e descrição -->
        <div class="space-y-2">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sem conexão</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                Você está offline. Verifique sua conexão com a internet e tente novamente.
            </p>
        </div>

        <!-- Card de status -->
        <div id="status-card" class="w-full bg-white dark:bg-card-dark rounded-ios-xl p-4 shadow-sm border border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div id="status-dot" class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></div>
                <div class="text-left">
                    <p id="status-text" class="text-sm font-medium text-gray-800 dark:text-gray-200">Sem acesso à internet</p>
                    <p id="status-sub" class="text-xs text-gray-400 mt-0.5">Verificando conectividade...</p>
                </div>
            </div>
        </div>

        <!-- Botão de retry -->
        <button id="btn-retry"
            onclick="retryConnection()"
            class="w-full py-4 bg-primary text-white font-semibold rounded-ios-xl flex items-center justify-center gap-2 active:opacity-80 transition-opacity">
            <span id="retry-icon" class="material-symbols-outlined text-[20px]">refresh</span>
            <span id="retry-label">Tentar novamente</span>
        </button>

        <!-- Link para dashboard (via cache) -->
        <button onclick="window.location.href='/dashboard.php'"
            class="text-primary text-sm font-medium underline-offset-2 hover:underline">
            Ir para o painel
        </button>

        <!-- Logo -->
        <div class="mt-4 flex flex-col items-center gap-1 opacity-40">
            <img src="logo.png" alt="Ondeline" class="w-8 h-8 rounded-lg"/>
            <span class="text-xs text-gray-400">Ondeline Tech</span>
        </div>
    </div>

    <script>
        let isChecking = false;

        async function retryConnection() {
            if (isChecking) return;
            isChecking = true;

            const btn = document.getElementById('btn-retry');
            const icon = document.getElementById('retry-icon');
            const label = document.getElementById('retry-label');

            // Estado de carregamento
            icon.classList.add('animate-spin');
            label.textContent = 'Verificando...';
            btn.disabled = true;

            try {
                // Tenta buscar o manifest (pequeno, sem auth)
                const response = await fetch('/manifest.json?_=' + Date.now(), {
                    method: 'HEAD',
                    cache: 'no-store'
                });

                if (response.ok) {
                    // Online! Redireciona
                    setStatus('online');
                    label.textContent = 'Conectado!';
                    icon.classList.remove('animate-spin');
                    icon.textContent = 'check_circle';

                    setTimeout(() => {
                        window.location.href = '/dashboard.php';
                    }, 800);
                    return;
                }
            } catch (e) {
                // Ainda offline
            }

            // Ainda offline
            setStatus('offline');
            icon.classList.remove('animate-spin');
            icon.textContent = 'refresh';
            label.textContent = 'Tentar novamente';
            btn.disabled = false;
            isChecking = false;
        }

        function setStatus(state) {
            const dot = document.getElementById('status-dot');
            const text = document.getElementById('status-text');
            const sub = document.getElementById('status-sub');

            if (state === 'online') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0';
                text.textContent = 'Conexão restaurada';
                sub.textContent = 'Redirecionando...';
            } else {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0';
                text.textContent = 'Sem acesso à internet';
                sub.textContent = 'Última tentativa: ' + new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            }
        }

        // Detecta reconexão automática
        window.addEventListener('online', () => {
            setStatus('online');
            setTimeout(() => { window.location.href = '/dashboard.php'; }, 1000);
        });

        window.addEventListener('offline', () => {
            setStatus('offline');
        });

        // Atualiza sub-texto com hora atual
        setStatus('offline');
    </script>
</body>
</html>
