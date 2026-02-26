# Ondeline Tech - App do Técnico (PWA)

Sistema de gestão de campo para técnicos da Ondeline Internet. PWA completo com suporte offline, push notifications, geolocalização e publicação via PWABuilder na Google Play Store.

---

## Funcionalidades

### Gestão
- **Login seguro** com autenticação JWT (7 dias)
- **Dashboard** com estatísticas, metas mensais e ações rápidas
- **Cadastro de clientes** com busca de CEP, GPS e fotos
- **Consulta de clientes** com busca por nome, CPF, cidade, serial
- **Detalhes do cliente** com histórico completo
- **Ordens de Serviço** com fluxo `open → assigned → in_progress → completed`
- **Checklist de instalação** com aprovação de admin
- **Estoque de equipamentos** com rastreamento de movimentações
- **Ponto eletrônico** com foto obrigatória e GPS
- **Mapa de técnicos** em tempo real (admin)
- **Relatórios** e exportação (admin)
- **Auditoria** de todas as ações críticas

### PWA / Mobile
- **Instalável** no Android (Chrome) e iOS (Safari)
- **Funciona offline** — cache com Workbox 7
- **Push notifications** com VAPID (ações contextuais por tipo)
- **Background Sync** — fila automática de requests offline
- **Share Target** — recebe fotos e links de outros apps Android
- **File Handlers** — abre imagens/PDFs pelo gerenciador de arquivos
- **Protocol Handlers** — link `web+ondeline://` abre cliente diretamente
- **Shortcuts** — atalhos na tela inicial do Android
- **Dark mode** automático

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Frontend | PHP como template, Tailwind CSS via CDN, Vanilla JS ES6+ |
| Ícones | Google Material Symbols Outlined |
| Fonte | Google Fonts (Inter) |
| Service Worker | Workbox 7 (CDN) |
| Backend | PHP 7.4+, PDO/MySQL, JWT |
| Hospedagem | cPanel / Apache |
| Push | Web Push Protocol (VAPID, RFC 8291, aes128gcm) |
| Play Store | PWABuilder (TWA) |

---

## Estrutura do Projeto

```
/
├── *.php                       # 16 páginas do app
├── sw.js                       # Service Worker (Workbox 7)
├── manifest.json               # PWA manifest com App Capabilities
├── share-target.php            # Receptor de conteúdo compartilhado
├── offline.php                 # Página offline (precacheada)
├── logo.png                    # Logo principal (5000x5000)
├── partials/
│   ├── head.php                # Head compartilhado
│   └── bottom-nav.php          # Bottom navigation
├── js/
│   ├── api.js                  # HTTP client (JWT + todos os endpoints)
│   ├── app.js                  # Init, auth, SW registration, theme
│   ├── utils.js                # Validação, formatação, compressão de imagem
│   ├── feedback.js             # Toast, loading, sync offline
│   ├── components.js           # Nav, header, modals
│   ├── animations.js           # Transições de página
│   ├── ui-enhancements.js      # Melhorias de UI
│   ├── geolocation.js          # GPS: getCurrentPosition, calculateDistance
│   ├── scanner.js              # Scanner de código de barras/QR
│   └── pages/
│       ├── login.js
│       ├── dashboard.js
│       ├── cadastro.js
│       ├── consulta.js
│       ├── detalhes.js
│       ├── vincular.js
│       ├── ajustes.js
│       ├── historico.js
│       ├── ordens.js
│       └── relatorios.js
├── css/
│   └── transitions.css         # Animações e skeleton loading
├── api/
│   ├── config.php              # DB, JWT, helpers, logAudit()
│   ├── Logger.php              # Logging
│   ├── Validator.php           # Validação server-side
│   ├── login.php               # Auth JWT
│   ├── clients.php             # CRUD clientes
│   ├── search-clients.php      # Busca com autocomplete
│   ├── work-orders.php         # Ordens de serviço
│   ├── checklist.php           # Checklist com templates
│   ├── inventory.php           # Estoque
│   ├── time-clock.php          # Ponto eletrônico
│   ├── technician-location.php # Rastreamento GPS em tempo real
│   ├── notifications.php       # Notificações in-app
│   ├── push.php                # Push notifications (VAPID)
│   ├── sync.php                # Sync da offline_queue
│   ├── upload.php              # Upload de fotos (base64 + FormData)
│   ├── upload-foto.php         # Upload via Share Target
│   ├── user.php                # Perfil do usuário
│   ├── users.php               # Gestão de usuários (admin)
│   ├── dashboard.php           # Dados do dashboard
│   ├── historico.php           # Métricas de desempenho
│   └── reports.php             # Relatórios (admin)
├── icons/
│   ├── icon-192.png            # Ícone 192x192 (PWA obrigatório)
│   ├── icon-512.png            # Ícone 512x512 (PWA obrigatório)
│   ├── icon-maskable-512.png   # Ícone maskable (Android adaptativo)
│   ├── icon.svg                # Ícone vetorial
│   └── icon-maskable.svg       # Ícone maskable vetorial
└── uploads/                    # Fotos de clientes e OS
```

---

## Configuração no cPanel

### 1. Upload dos Arquivos
1. Acesse o **Gerenciador de Arquivos** do cPanel
2. Navegue até `public_html` (ou subdomínio desejado)
3. Faça upload de todos os arquivos mantendo a estrutura

### 2. Banco de Dados
1. No cPanel, vá em **Bancos de dados MySQL**
2. Crie o banco `onde2292_erp`
3. Crie um usuário e conceda **TODOS OS PRIVILÉGIOS**
4. Importe `database.sql` pelo phpMyAdmin

### 3. Configurar `api/config.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'onde2292_erp');
define('DB_USER', 'onde2292_user');
define('DB_PASS', 'SUA_SENHA');
define('JWT_SECRET', 'chave_secreta_unica');

// Chaves VAPID para push notifications
// Gerar em: https://vapidkeys.com
define('VAPID_PUBLIC_KEY',  'sua_chave_publica_base64url');
define('VAPID_PRIVATE_KEY', 'sua_chave_privada_base64url');
define('VAPID_SUBJECT',     'mailto:suporte@ondeline.com.br');
```

### 4. HTTPS (obrigatório para PWA e GPS)
1. No cPanel, vá em **SSL/TLS**
2. Instale Let's Encrypt (gratuito)
3. Force HTTPS em **Domínios > Forçar HTTPS**

---

## Publicar na Play Store (PWABuilder)

1. Hospedar o sistema em HTTPS
2. Acessar [pwabuilder.com](https://pwabuilder.com)
3. Colar a URL do sistema e clicar **Start**
4. Clicar em **Package for stores > Google Play**
5. Preencher Package ID (ex: `br.com.ondeline.tecnico`) e versão
6. Baixar o `.aab` gerado
7. Criar conta em [play.google.com/console](https://play.google.com/console) (~$25)
8. Fazer upload do `.aab` e preencher listagem

> **Importante:** Guardar o arquivo `.keystore` com segurança — sem ele não é possível atualizar o app.

---

## Service Worker (Workbox 7)

| Recurso | Estratégia | Cache |
|---------|-----------|-------|
| Fontes Google | CacheFirst | 30 dias |
| Imagens | CacheFirst | 7 dias |
| CSS / JS | StaleWhileRevalidate | 7 dias |
| API (`/api/*`) | NetworkFirst | 24h (fallback) |
| Páginas PHP | NetworkFirst | 24h (fallback) |
| Fallback offline | `/offline.php` | Precache |
| POST offline | BackgroundSyncPlugin | Fila 24h |
| Share Target POST | registerRoute | — |

---

## App Capabilities (manifest.json)

| Capability | Função |
|-----------|--------|
| `share_target` | Recebe fotos/texto de outros apps Android |
| `file_handlers` | Abre imagens/PDFs pelo gerenciador |
| `protocol_handlers` | `web+ondeline://` abre cliente |
| `launch_handler` | Foca janela existente ao abrir link |
| `shortcuts` | Atalhos na tela inicial |

---

## Push Notifications

### Enviar notificação contextual

```json
POST /api/push.php
Authorization: Bearer <token_admin>
{
  "action": "send",
  "title": "Nova OS",
  "body": "OS #42 atribuída a você",
  "url": "/ordens.php?id=42",
  "type": "work_order",
  "tag": "os-42",
  "id": 42,
  "user_id": 5
}
```

**Tipos e botões no Android:**

| `type` | Botões |
|--------|--------|
| `work_order` | Ver OS / Depois |
| `checklist` | Ver Checklist / Depois |
| `sync` | Sincronizar / Ignorar |
| `info` | Abrir / Dispensar |

---

## Geolocalização

O app usa dois tipos de geolocalização:

**1. Localização do cliente** — capturada no cadastro e salva no banco.

**2. Localização do técnico (tempo real)** — atualizada periodicamente, admin vê no mapa, sistema sugere técnico mais próximo ao criar OS.

```js
// Atualizar localização do técnico
const pos = await getLocation();
await API.updateTechnicianLocation(pos.latitude, pos.longitude, pos.accuracy);

// Sugerir técnico mais próximo (admin)
const result = await API.getNearestTechnician(clientCpf);
```

---

## Background Sync

**1. Workbox BackgroundSyncPlugin** — requests HTTP que falharam offline:
- Captura POSTs para `/api/clients`, `/api/work-orders`, `/api/checklist`, `/api/inventory`, `/api/time-clock`
- Reexecuta automaticamente ao reconectar

**2. offline_queue manual** — dados salvos localmente:
- Cliente envia `OFFLINE_DATA_SAVED` → SW registra `sync-offline-queue`
- Ao reconectar: cliente chama `POST /api/sync.php` (ou SW chama diretamente se app fechado)

---

## API Endpoints

| Endpoint | Métodos | Função |
|---------|---------|--------|
| `/api/login.php` | POST | Autenticação JWT |
| `/api/clients.php` | GET POST PUT DELETE | CRUD clientes |
| `/api/work-orders.php` | GET POST PUT DELETE | Ordens de serviço |
| `/api/checklist.php` | GET POST PUT DELETE | Checklists |
| `/api/inventory.php` | GET POST PUT DELETE | Estoque |
| `/api/time-clock.php` | GET POST | Ponto eletrônico |
| `/api/technician-location.php` | GET POST | GPS em tempo real |
| `/api/push.php` | GET POST DELETE | Push notifications |
| `/api/sync.php` | GET POST | Sync offline |
| `/api/notifications.php` | GET POST PUT DELETE | Notificações in-app |
| `/api/reports.php` | GET | Relatórios (admin) |

---

## Segurança

- Senhas com `password_hash()` bcrypt
- JWT com expiração de 7 dias
- PDO prepared statements em todas as queries
- `escapeHtml()` em todo conteúdo dinâmico no frontend
- Validação no frontend e backend
- Upload restrito por tipo e tamanho (10MB)
- HTTPS obrigatório (PWA + GPS + Push)

---

## Solução de Problemas

| Problema | Solução |
|---------|---------|
| PWA não instala | Verificar HTTPS, `manifest.json` acessível, ícones em `/icons/` |
| Push não chega | Verificar VAPID em `config.php`, testar `GET /api/push.php?test=1` |
| Offline não funciona | DevTools > Application > Service Workers |
| GPS não funciona | Exige HTTPS + permissão no browser |
| Erro 500 | Ver logs PHP no cPanel, confirmar PHP 7.4+, permissões 644/755 |
| App não atualiza | Incrementar `APP_VERSION` em `sw.js` |

---

**Ondeline Internet — uso interno**
