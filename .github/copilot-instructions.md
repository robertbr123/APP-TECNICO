# Copilot Instructions - Ondeline Tech App

## Sobre o Projeto

**Ondeline Tech App** e um Progressive Web App (PWA) para tecnicos de campo da Ondeline Internet. Permite gerenciar clientes, instalacoes, ordens de servico, estoque de equipamentos, ponto eletronico, checklists e auditorias diretamente pelo celular.

## Stack Tecnologico

### Frontend
- **PHP** como template engine (paginas `.php` com HTML embutido)
- **Tailwind CSS** via CDN para estilizacao
- **Vanilla JavaScript** (ES6+) - sem frameworks
- **Google Material Symbols Outlined** para icones
- **Google Fonts (Inter)** para tipografia
- **Service Worker** (`sw.js`) com **Workbox 7** para suporte offline e cache
- **PWA** com `manifest.json` e App Capabilities completas

### Backend
- **PHP 7.4+** com arquitetura RESTful
- **MySQL/MariaDB** via PDO (prepared statements)
- **JWT** para autenticacao (Bearer token)
- **Hospedagem**: cPanel com Apache
- **Push Notifications**: Web Push Protocol VAPID, RFC 8291, aes128gcm (sem biblioteca externa)

### Publicacao
- **PWABuilder** (TWA) para gerar `.aab` para Google Play Store
- **manifest.json** com: `share_target`, `file_handlers`, `protocol_handlers`, `launch_handler`, `shortcuts`

### Banco de Dados
- `onde2292_erp`: banco unico com todas as tabelas (usuarios, clientes, auditoria, ponto, fotos, estoque, checklists, planos, notificacoes, ordens de servico, fila offline)

## Estrutura do Projeto

```
/
├── *.php               # Paginas do app (16 paginas PHP)
├── partials/
│   ├── head.php        # Head compartilhado (meta tags, Tailwind, fontes)
│   └── bottom-nav.php  # Bottom navigation compartilhada
├── js/
│   ├── api.js          # Camada HTTP (fetch) com auth JWT + escapeHtml()
│   ├── app.js          # Inicializacao, auth check, theme, SW registration
│   ├── utils.js        # Validacao (CPF, telefone), formatacao, compressao de imagem
│   ├── feedback.js     # Toast notifications, loading, sync offline
│   ├── components.js   # Bottom nav, header, confirm modal
│   ├── animations.js   # Animacoes e transicoes
│   ├── ui-enhancements.js # Melhorias de UI
│   ├── geolocation.js  # Servicos GPS
│   ├── scanner.js      # Scanner de codigo de barras/QR
│   └── pages/
│       ├── login.js    # Pagina de login
│       ├── dashboard.js # Dashboard com metas e stats
│       ├── cadastro.js # Cadastro de cliente com fotos
│       ├── consulta.js # Busca e listagem de clientes
│       ├── detalhes.js # Perfil completo do cliente
│       ├── vincular.js # Vincular equipamento ao cliente
│       ├── ajustes.js  # Configuracoes do app e perfil
│       ├── historico.js # Metricas de desempenho
│       ├── ordens.js   # Ordens de servico (CRUD + fotos)
│       └── relatorios.js # Relatorios e exportacao
├── css/
│   └── transitions.css # Animacoes de pagina e skeleton loading
├── api/
│   ├── config.php      # Conexao DB, JWT config, CORS, logAudit()
│   ├── Logger.php      # Classe de logging
│   ├── Validator.php   # Validacao de input server-side
│   ├── login.php       # Autenticacao JWT
│   ├── clients.php     # CRUD de clientes
│   ├── search-clients.php # Busca de clientes com autocomplete
│   ├── checklist.php   # Sistema de checklist de instalacao
│   ├── inventory.php   # Gestao de estoque
│   ├── time-clock.php  # Registro de ponto
│   ├── work-orders.php # Ordens de servico
│   ├── notifications.php # Notificacoes in-app
│   ├── reports.php     # Relatorios (admin only)
│   ├── sync.php        # Sincronizacao offline
│   ├── upload.php      # Upload de arquivos
│   ├── push.php        # Push notifications (VAPID)
│   ├── user.php        # Perfil do usuario
│   ├── users.php       # Gestao de usuarios (admin)
│   ├── dashboard.php   # Dados do dashboard + meta mensal
│   └── historico.php   # Dados de desempenho
├── sw.js               # Service Worker (cache + sync + push)
├── manifest.json       # PWA manifest
└── uploads/            # Diretorio de uploads (fotos, OS)
```

## Paginas do App

| Pagina | Arquivo | Funcao |
|--------|---------|--------|
| Login | `login.php` | Autenticacao com JWT |
| Dashboard | `dashboard.php` | Metricas, metas e acoes rapidas |
| Novo Cadastro | `novo-cadastro.php` | Formulario de cadastro de cliente |
| Consultar | `consultar.php` | Busca e listagem de clientes |
| Detalhes | `detalher.php` | Perfil completo do cliente |
| Checklist | `checklist.php` | Checklist de instalacao |
| Estoque | `estoque.php` | Gestao de inventario |
| Ponto | `ponto.php` | Registro de ponto com GPS e foto |
| Mapa | `mapa.php` | Mapa com localizacoes de clientes |
| Historico | `historico.php` | Metricas de desempenho |
| Auditoria | `auditoria.php` | Logs de auditoria |
| Vincular | `vincular-equipamento.php` | Vincular equipamento ao cliente |
| Ordens | `ordens.php` | Ordens de servico (OS) |
| Relatorios | `relatorios.php` | Relatorios e exportacao (admin) |
| Ajustes | `ajustes.php` | Configuracoes do app |
| Admin | `admin.php` | Painel administrativo |

## Convencoes de Codigo

### PHP (Paginas)
- Cada pagina e um arquivo `.php` com HTML + Tailwind embutido
- Usar `partials/head.php` para meta tags e imports compartilhados
- Usar `partials/bottom-nav.php` ou `<nav id="bottom-nav"></nav>` com JS
- Meta tags PWA obrigatorias: `theme-color`, `apple-mobile-web-app-capable`
- NUNCA usar extensao `.html` em links — sempre `.php`

### JavaScript
- Usar `async/await` para chamadas assincronas
- Todas as chamadas API passam pelo `js/api.js`
- Token JWT salvo em `localStorage` como `auth_token`
- Dados do usuario em `localStorage` como `user_data` (JSON)
- Validacoes de CPF, telefone e CEP no `js/utils.js`
- Feedback via `js/feedback.js` (showToast, showSuccess, showError, showLoading)
- Usar `escapeHtml()` de `api.js` ao inserir dados em innerHTML (prevenir XSS)
- Compressao de imagem via `Utils.compressImage()` antes de upload
- Geolocalizacao via `js/geolocation.js`

### PHP (API)
- Toda API deve incluir `config.php` no topo
- Usar PDO com prepared statements (NUNCA concatenar SQL)
- Autenticacao via `requireAuth()` que retorna `$userData` com `user_id`, `username`, `role`
- Funcao `logAudit()` centralizada em `config.php` para auditoria
- Respostas em JSON com `Content-Type: application/json`
- Formato de resposta: `{ "success": true/false, "message": "...", "data": ... }`
- CORS habilitado para todas as origens
- Auto-criacao de tabelas com `CREATE TABLE IF NOT EXISTS`
- Migracao de colunas com `ALTER TABLE ... ADD COLUMN` em try/catch

### CSS
- Tailwind CSS via CDN para layout e componentes
- Classes customizadas em `css/transitions.css` para animacoes
- Suporte a dark mode obrigatorio (classe `dark` no `<html>`)
- Design mobile-first e responsivo
- Safe area insets para dispositivos com notch

## Autenticacao e Permissoes

- Login retorna JWT com expiracao de 7 dias
- Token enviado como `Authorization: Bearer <token>` em todas as requisicoes
- Roles: `admin` (acesso total) e `user` (tecnico, acesso limitado)
- Verificar auth no carregamento de cada pagina via `app.js`
- **Admin only**: criar OS, relatorios, ver total de clientes, painel admin
- **Tecnico**: ver OS atribuidas, executar OS com foto, cadastrar clientes

## Funcionalidades Principais

### Gestao de Clientes
- CPF como chave primaria (11 digitos, sem formatacao no banco)
- Busca por nome, CPF, cidade, serial
- Captura de GPS na localizacao do cliente
- Historico de troca de serial de equipamento
- Autocomplete de clientes na criacao de OS

### Ordens de Servico (OS)
- Admin cria OS e atribui a tecnico
- Tecnico ve somente OS atribuidas a ele
- Fluxo: open → assigned → in_progress → completed/cancelled
- Conclusao com descricao de resolucao + ate 5 fotos do servico
- Fotos comprimidas e salvas em `uploads/os/`
- Notificacao automatica ao atribuir/concluir OS

### Checklist de Instalacao
- Templates por tipo: nova, migracao, reparo, manutencao
- 5 categorias: pre_instalacao, instalacao, configuracao, teste, documentacao
- Fluxo de aprovacao: tecnico submete > admin aprova/rejeita

### Estoque de Equipamentos
- Tipos: router, modem, onu, cpe, antenna, cable, accessory
- Status: available, in_use, maintenance, defective, lost
- Rastreamento de movimentacoes com historico completo

### Ponto Eletronico
- Check-in/out com foto obrigatoria (camera)
- GPS com latitude, longitude e precisao
- Calculo automatico de horas trabalhadas

### Meta Mensal
- Tabela `settings` com `monthly_target`
- Dashboard mostra progresso em barra de porcentagem animada
- Contagem de cadastros do mes vs meta

### Suporte Offline (PWA) — Workbox 7
- Service Worker em `sw.js` usa **Workbox 7 via CDN** (`importScripts`)
- Estratégias: CacheFirst (fontes/imagens), StaleWhileRevalidate (CSS/JS), NetworkFirst (PHP/API) com timeout de 3s
- **Precache** de assets estáticos criticos (JS, CSS, imagens, offline.php)
- **BackgroundSyncPlugin** captura POSTs offline para `/api/clients`, `/api/work-orders`, `/api/checklist`, `/api/inventory`, `/api/time-clock`
- **Sync manual** via tag `'sync-offline-queue'` → notifica clients → `POST /api/sync.php`
- Tabela `offline_queue` no banco para tracking server-side
- Fallback offline: pagina `/offline.php` (precacheada) + SVG placeholder para imagens
- Acoes offline suportadas: cadastro, atualizacao, vinculacao, upload de foto

## Regras de Negocio Importantes

1. CPF deve ser validado (algoritmo) antes de salvar
2. Serial de equipamento e unico no sistema
3. Fotos sao comprimidas no frontend (1200px, 70% quality) antes do upload
4. Registro de ponto e unico por usuario/dia
5. Checklists completados precisam de aprovacao do admin
6. Todas as acoes criticas sao registradas em `audit_logs` via `logAudit()`
7. Equipamentos defeituosos geram alertas automaticos
8. Relatorios sao restritos a admin (frontend + backend)
9. Tecnicos so veem OS atribuidas a eles (filtro no backend)
10. Quantidade de clientes so e visivel para admin (tecnicos veem "100+")

## Seguranca

- Senhas com `password_hash()` (bcrypt)
- JWT com chave secreta configuravel
- Prepared statements em todas as queries SQL
- `escapeHtml()` em todo conteudo dinamico no frontend
- Validacao de input no frontend E backend
- Upload de arquivos restrito por tipo e tamanho
- `.htaccess` protegendo diretorio de uploads
- HTTPS obrigatorio para PWA e geolocalizacao

## Geolocalizacao

Dois contextos distintos:

### 1. Localizacao do cliente
- Capturada no cadastro via `js/geolocation.js` (`enableHighAccuracy: true`)
- Salva em `clients.latitude` / `clients.longitude`
- Exibida no mapa e usada para sugerir tecnico mais proximo

### 2. Localizacao do tecnico (tempo real)
- Atualizada periodicamente via `API.updateTechnicianLocation(lat, lng, accuracy)`
- Endpoint: `POST /api/technician-location.php { action: 'update', ... }`
- Admin ve tecnicos ativos no mapa (ultimas 2 horas)
- Admin pode sugerir tecnico mais proximo de um cliente: `API.getNearestTechnician(cpf)`
- Formula Haversine no MySQL para calculo de distancia
- Marcar inativo ao fechar app: `API.setTechnicianInactive()`
- Periodic Sync (`'sync-location'`) notifica clients para enviar localizacao ao servidor

## Push Notifications

- Backend: `api/push.php` — subscribe, send, unsubscribe, list
- Payload deve incluir: `title`, `body`, `url`, `type`, `tag`, `id`
- Tipos suportados: `work_order`, `checklist`, `sync`, `info`
- Cada tipo exibe botoes de acao diferentes no Android
- Frontend: `API.sendPush(title, body, url, userId, type, tag, id)`
- Para testar: `GET /api/push.php?test=1` (admin)
- Chaves VAPID configuradas em `api/config.php` (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT)

## Share Target

- Configurado em `manifest.json > share_target`
- Quando outro app Android compartilha foto/texto para este app, abre `share-target.php`
- O SW intercepta o POST em `registerRoute(..., 'POST')` e redireciona para upload ou cadastro
- Dados compartilhados ficam em `caches.open('share-target-cache')`

## Atualizacao do Service Worker

- Ao fazer mudancas no codigo, incrementar `APP_VERSION` em `sw.js`
- O Workbox `cleanupOutdatedCaches()` limpa caches de versoes anteriores automaticamente
- Forcar atualizacao no cliente: `navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' })`

## Como Contribuir

1. Sempre testar em dispositivo movel (PWA mobile-first)
2. Manter compatibilidade offline
3. Usar `.php` para todas as paginas e links (NUNCA `.html`)
4. Validar inputs tanto no frontend quanto no backend
5. Registrar acoes na auditoria via `logAudit()`
6. Seguir o padrao de resposta JSON da API
7. Usar Tailwind para estilizacao (evitar CSS inline)
8. Manter dark mode funcional em novos componentes
9. Usar `escapeHtml()` ao inserir dados do usuario em innerHTML
10. Comprimir imagens com `Utils.compressImage()` antes de upload
11. Ao adicionar novos endpoints de POST offline, registrar a rota no sw.js (Background Sync)
12. Ao criar novas paginas PHP, adicionar ao precache em `sw.js` se for critica para offline
13. Geolocalizacao exige HTTPS — nunca testar localizacao em HTTP
