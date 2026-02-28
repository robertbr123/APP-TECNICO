<!DOCTYPE html>
<html class="light" lang="pt-br"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport"/>
<title>Login Ondeline</title>
<!-- PWA Meta Tags -->
<meta name="theme-color" content="#135bec"/>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
<meta name="apple-mobile-web-app-title" content="Ondeline"/>
<link rel="manifest" href="manifest.json"/>
<link rel="icon" type="image/png" href="logo.png"/>
<link rel="apple-touch-icon" href="logo.png"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/transitions.css"/>

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
                fontFamily: {
                    "display": ["Inter", "sans-serif"]
                },
                borderRadius: {
                    "ios": "12px",
                    "ios-lg": "16px",
                    "ios-xl": "20px"
                }
            },
        },
    }
</script>
<style type="text/tailwindcss">
    @layer base {
        body {
            @apply bg-background-light dark:bg-background-dark font-display text-[#000000] dark:text-white antialiased;
            -webkit-tap-highlight-color: transparent;
        }
    }
</style>
<style>
    /* Splash Screen */
    .splash-screen {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
        transition: opacity 0.6s ease, visibility 0.6s ease;
    }
    .splash-screen.fade-out {
        opacity: 0;
        visibility: hidden;
    }
    .splash-logo {
        width: 100px;
        height: 100px;
        animation: splash-pulse 2s ease-in-out infinite, splash-float 3s ease-in-out infinite;
    }
    .splash-text {
        margin-top: 24px;
        font-size: 28px;
        font-weight: 700;
        color: white;
        letter-spacing: 4px;
        animation: splash-fade-in 1s ease forwards;
    }
    .splash-loader {
        margin-top: 40px;
        width: 200px;
        height: 3px;
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .splash-loader-bar {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #60a5fa, #3b82f6, #2563eb);
        border-radius: 10px;
        animation: splash-load 2s ease forwards;
    }
    @keyframes splash-pulse {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.5)); }
        50% { transform: scale(1.05); filter: drop-shadow(0 0 40px rgba(59, 130, 246, 0.8)); }
    }
    @keyframes splash-float {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-10px) scale(1.05); }
    }
    @keyframes splash-fade-in {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes splash-load {
        0% { width: 0%; }
        50% { width: 70%; }
        100% { width: 100%; }
    }

    /* Login Background */
    .login-bg {
        min-height: 100dvh;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
        position: relative;
        overflow: hidden;
    }
    .login-bg::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                    radial-gradient(circle at 70% 70%, rgba(14, 165, 233, 0.1) 0%, transparent 50%);
        animation: bg-rotate 20s linear infinite;
    }
    @keyframes bg-rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Floating orbs */
    .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        animation: orb-float 8s ease-in-out infinite;
    }
    .orb-1 {
        width: 300px;
        height: 300px;
        background: rgba(59, 130, 246, 0.3);
        top: -100px;
        right: -100px;
        animation-delay: 0s;
    }
    .orb-2 {
        width: 200px;
        height: 200px;
        background: rgba(14, 165, 233, 0.2);
        bottom: -50px;
        left: -50px;
        animation-delay: -4s;
    }
    .orb-3 {
        width: 150px;
        height: 150px;
        background: rgba(139, 92, 246, 0.2);
        top: 50%;
        left: 50%;
        animation-delay: -2s;
    }
    @keyframes orb-float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -30px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }

    /* Glass card */
    .login-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: card-enter 0.8s ease forwards;
        animation-delay: 0.3s;
        opacity: 0;
        transform: translateY(30px);
    }
    @keyframes card-enter {
        to { opacity: 1; transform: translateY(0); }
    }

    /* Glass input */
    .glass-input {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        transition: all 0.3s ease;
        caret-color: #60a5fa;
    }
    .glass-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
        -webkit-text-fill-color: rgba(255, 255, 255, 0.5);
    }
    .glass-input:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        outline: none;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }
    /* Fix autofill background */
    .glass-input:-webkit-autofill,
    .glass-input:-webkit-autofill:hover,
    .glass-input:-webkit-autofill:focus,
    .glass-input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px rgba(30, 58, 95, 0.9) inset !important;
        -webkit-text-fill-color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.1) !important;
        caret-color: #60a5fa !important;
    }

    /* Animated button */
    .login-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .login-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s ease;
    }
    .login-btn:hover::before {
        left: 100%;
    }
    .login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.5);
    }
    .login-btn:active {
        transform: scale(0.98);
    }

    /* Form animations */
    .form-group {
        opacity: 0;
        transform: translateY(20px);
        animation: form-stagger 0.5s ease forwards;
    }
    .form-group:nth-child(1) { animation-delay: 0.5s; }
    .form-group:nth-child(2) { animation-delay: 0.6s; }
    .form-group:nth-child(3) { animation-delay: 0.7s; }
    .form-group:nth-child(4) { animation-delay: 0.8s; }
    @keyframes form-stagger {
        to { opacity: 1; transform: translateY(0); }
    }

    /* Loading spinner */
    .btn-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Toast notification */
    .toast {
        position: fixed;
        bottom: 100px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: rgba(239, 68, 68, 0.9);
        backdrop-filter: blur(10px);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        font-weight: 500;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 1000;
    }
    .toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
    .toast.success {
        background: rgba(34, 197, 94, 0.9);
    }
</style>
</head>
<body>
<!-- Splash Screen -->
<div class="splash-screen" id="splashScreen">
    <img src="logo.png" alt="Ondeline" class="splash-logo">
    <span class="splash-text">ONDELINE</span>
    <div class="splash-loader">
        <div class="splash-loader-bar"></div>
    </div>
</div>

<!-- Login Page -->
<div class="login-bg">
    <!-- Floating orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="relative z-10 flex items-center justify-center min-h-dvh p-4 safe-top safe-bottom">
        <div class="w-full max-w-[400px]">
            <!-- Logo section -->
            <div class="text-center mb-8 opacity-0 animate-[form-stagger_0.5s_ease_forwards]">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 mb-4">
                    <img src="logo.png" alt="Ondeline" class="w-12 h-12 object-contain">
                </div>
                <h1 class="text-white text-3xl font-bold tracking-tight">Bem-vindo</h1>
                <p class="text-white/60 mt-2">Portal do Técnico</p>
            </div>

            <!-- Login Card -->
            <div class="login-card p-8">
                <form id="loginForm" class="space-y-5">
                    <div class="form-group">
                        <label class="block text-white/80 text-sm font-medium mb-2">
                            <span class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">person</span>
                                Usuário ou CPF
                            </span>
                        </label>
                        <input 
                            type="text" 
                            id="username"
                            class="glass-input w-full h-14 px-4 rounded-xl text-base"
                            placeholder="Digite seu usuário ou CPF"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="block text-white/80 text-sm font-medium mb-2">
                            <span class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">lock</span>
                                Senha
                            </span>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password"
                                class="glass-input w-full h-14 px-4 pr-12 rounded-xl text-base"
                                placeholder="Sua senha"
                                autocomplete="current-password"
                                required
                            >
                            <button 
                                type="button" 
                                id="togglePassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors"
                            >
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group flex justify-end">
                        <a href="#" class="text-blue-400 text-sm font-medium hover:text-blue-300 transition-colors">
                            Esqueci minha senha
                        </a>
                    </div>

                    <div class="form-group pt-2">
                        <button 
                            type="submit" 
                            id="loginBtn"
                            class="login-btn w-full h-14 rounded-xl text-white font-bold text-base flex items-center justify-center gap-2"
                        >
                            <span id="btnText">Entrar</span>
                            <span class="material-symbols-outlined" id="btnIcon">arrow_forward</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 opacity-0 animate-[form-stagger_0.5s_ease_0.9s_forwards]">
                <p class="text-white/30 text-xs">Ondeline © 2026</p>
                <p class="text-white/20 text-[10px] mt-1 uppercase tracking-widest">v2.0</p>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Scripts -->
<script src="js/api.js"></script>
<script src="js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const splash = document.getElementById('splashScreen');
    const form = document.getElementById('loginForm');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');
    const toast = document.getElementById('toast');

    // Oculta o splash assim que o DOM estiver pronto, mas garante pelo menos 800ms
    // para a animação inicial completar (era 2200ms fixos antes)
    const splashMinTime = 800;
    const splashStart = Date.now();
    function hideSplash() {
        const elapsed = Date.now() - splashStart;
        const remaining = Math.max(0, splashMinTime - elapsed);
        setTimeout(() => {
            splash.classList.add('fade-out');
            setTimeout(() => splash.remove(), 600);
        }, remaining);
    }
    if (document.readyState === 'complete') {
        hideSplash();
    } else {
        window.addEventListener('load', hideSplash, { once: true });
    }

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = password.type === 'password' ? 'text' : 'password';
        password.type = type;
        this.querySelector('span').textContent = type === 'password' ? 'visibility' : 'visibility_off';
    });

    // Show toast
    function showToast(message, isSuccess = false) {
        toast.textContent = message;
        toast.className = 'toast' + (isSuccess ? ' success' : '');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Set loading state
    function setLoading(loading) {
        if (loading) {
            btnText.textContent = 'Entrando...';
            btnIcon.style.display = 'none';
            loginBtn.insertAdjacentHTML('beforeend', '<div class="btn-spinner"></div>');
            loginBtn.disabled = true;
        } else {
            btnText.textContent = 'Entrar';
            btnIcon.style.display = '';
            const spinner = loginBtn.querySelector('.btn-spinner');
            if (spinner) spinner.remove();
            loginBtn.disabled = false;
        }
    }

    // Handle form submit
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const user = username.value.trim();
        const pass = password.value;

        if (!user || !pass) {
            showToast('Preencha todos os campos');
            return;
        }

        setLoading(true);

        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    username: user, 
                    password: pass 
                })
            });

            const data = await response.json();

            if (data.success && data.token) {
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('user_data', JSON.stringify(data.user));
                showToast('Login realizado com sucesso!', true);

                // Haptic feedback
                if (navigator.vibrate) navigator.vibrate(50);

                // Garante que a senha não apareça em texto após o toggle do olhinho
                password.type = 'password';
                password.value = '';

                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 500);
            } else {
                showToast(data.message || 'Credenciais inválidas');
                setLoading(false);
            }
        } catch (error) {
            console.error('Login error:', error);
            showToast('Erro de conexão. Tente novamente.');
            setLoading(false);
        }
    });

    // Check if already logged in
    const token = localStorage.getItem('auth_token');
    if (token) {
        window.location.href = 'dashboard.php';
    }
});
</script>
</body></html>