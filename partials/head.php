    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport"/>
    <meta name="theme-color" content="#135bec"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
    <meta name="apple-mobile-web-app-title" content="Ondeline Tech"/>
    <link rel="manifest" href="manifest.json"/>
    <link rel="icon" type="image/png" href="logo.png"/>
    <link rel="apple-touch-icon" href="logo.png"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
        .ios-blur {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        .safe-top {
            padding-top: env(safe-area-inset-top);
        }
    </style>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { min-height: max(884px, 100dvh); }
    </style>
