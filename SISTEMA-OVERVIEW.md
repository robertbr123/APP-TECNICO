# 📡 Ondeline Tech — App do Técnico

> Sistema de Gestão de Técnicos de Campo para Provedores de Internet

---

## 🎯 O que é esse sistema?

**Ondeline Tech App** é um **Progressive Web App (PWA)** desenvolvido para gerenciar equipes de técnicos de campo de provedores de internet. Funciona como um aplicativo nativo no celular (instalável no Android/iOS), com suporte completo a operação **offline**, sincronização automática de dados, notificações push e rastreamento por GPS.

O sistema foi construído para substituir processos manuais (papel, planilha, WhatsApp) por um fluxo digital completo: do cadastro do cliente até a conclusão de uma ordem de serviço com foto e geolocalização.

---

## 👥 Quem usa?

| Perfil | Função |
|--------|--------|
| **Técnico (user)** | Cadastra clientes, executa ordens de serviço, registra ponto, vincula equipamentos, preenche checklists |
| **Administrador (admin)** | Gerencia tudo + aprova checklists, cria OS, visualiza todos os técnicos no mapa, acessa relatórios completos, configura o sistema |

---

## 🗂️ Páginas do Sistema (16 páginas)

| Página | Arquivo | O que faz |
|--------|---------|-----------|
| Login | `login.php` | Autenticação JWT com bloqueio por tentativas (5 tentativas / 15 min) |
| Dashboard | `dashboard.php` | Painel principal com métricas, meta mensal e ações rápidas |
| Novo Cadastro | `novo-cadastro.php` | Formulário de cadastro de cliente com auto-preenchimento de CEP, foto e GPS |
| Consultar | `consultar.php` | Busca e listagem de clientes com filtros de status |
| Detalhes | `detalher.php` | Perfil completo do cliente: plano, PPPoE, equipamento, histórico, localização |
| Checklist | `checklist.php` | Checklists de instalação/reparo por categorias, com prazo de 3 dias e aprovação do admin |
| Estoque | `estoque.php` | Gestão de equipamentos: roteadores, modems, ONUs, antenas — com status e histórico |
| Ponto | `ponto.php` | Registro de entrada/saída com foto obrigatória e GPS |
| Mapa | `mapa.php` | Mapa interativo com clientes e técnicos em tempo real |
| Histórico | `historico.php` | Métricas de desempenho por período |
| Auditoria | `auditoria.php` | Log completo de todas as ações do sistema por usuário |
| Vincular Equipamento | `vincular-equipamento.php` | Associa serial de equipamento a um cliente |
| Ordens de Serviço | `ordens.php` | Criação, atribuição e conclusão de OS com fotos |
| Relatórios | `relatorios.php` | Exportação de dados em PDF/CSV (somente admin) |
| Ajustes | `ajustes.php` | Perfil do usuário, troca de senha, tema (dark/light) |
| Admin | `admin.php` | Painel administrativo: usuários, templates, importação, push notifications |

---

## ⚙️ Funcionalidades Principais

### 📋 Gestão de Clientes
- Cadastro completo com CPF (validado), endereço, plano, PPPoE, serial do equipamento
- Auto-preenchimento de endereço via CEP (viacep.com.br)
- Captura de coordenadas GPS da localização do cliente
- Histórico de troca de equipamentos (serial anterior/atual)
- Fotos do cliente, poste, ID, instalação

### 🔧 Ordens de Serviço (OS)
- Numeração automática: `OS-YYYYMMDD-XXXX`
- Tipos: instalação, reparo, manutenção, migração, remoção
- Prioridades: baixa, média, alta, urgente
- Fluxo de status: `aberta → atribuída → em andamento → concluída / cancelada`
- Técnico recebe notificação push ao ser atribuído
- Conclusão com descrição + até 5 fotos (comprimidas automaticamente)
- Fotos salvas em `uploads/os/`

### ✅ Checklist de Instalação
- Numeração automática: `CHK-YYYYMMDD-0001`
- Categorias: pré-instalação, instalação, configuração, teste, documentação
- Prazo de 3 dias para conclusão
- Fluxo de aprovação: técnico conclui → admin aprova ou rejeita
- Status: pendente / concluído / aguardando aprovação / aprovado / rejeitado / expirado

### 🕐 Ponto Eletrônico
- Registro de entrada e saída com timestamp preciso
- Foto obrigatória (câmera) como prova de presença
- GPS no momento do registro
- Cálculo automático de horas trabalhadas por dia

### 📦 Estoque de Equipamentos
- Tipos: roteador, modem, ONU, CPE, antena, cabo, acessório
- Status: disponível, em uso, em manutenção, defeituoso, perdido
- Rastreamento de serial com histórico completo de quem usou
- Alerta de estoque baixo
- Log de movimentações (entrada, saída, reparo, devolução)

### 🗺️ Localização e Mapa
- Técnico envia localização em tempo real ao operar o app
- Admin visualiza todos os técnicos ativos no mapa (últimas 2 horas)
- Admin pode sugerir o técnico mais próximo de um cliente (fórmula Haversine)
- Histórico de localizações dos clientes

### 🔔 Notificações Push (PWA)
- Implementação VAPID (sem Firebase, protocolo nativo)
- Tipos de notificação: `work_order`, `checklist`, `sync`, `info`
- Botões de ação diretamente na notificação (Android)
- Funciona mesmo com o app fechado

### 📊 Relatórios e Exportação
- Formatos: **PDF** (jsPDF) e **CSV**
- Tipos: clientes, checklists, ponto eletrônico, estoque, ordens de serviço
- Relatórios por técnico com ranking de desempenho
- Filtro por período personalizado
- Somente administradores têm acesso

### 🔐 Auditoria Completa
- Toda ação crítica é registrada: login, criação, edição, exclusão, aprovação
- Registro de: usuário, ação, entidade, IP, user agent
- Filtrável por usuário, tipo de ação, período

---

## 📴 Modo Offline (PWA)

O app foi projetado para funcionar **sem internet**:

| Recurso | Como funciona |
|---------|--------------|
| **Cache de páginas** | Workbox v7 pré-cacheia todas as páginas críticas |
| **Fila offline** | Ações feitas sem internet ficam em fila (`offline_queue`) |
| **Sync automático** | Ao reconectar, fila é processada via `POST /api/sync.php` |
| **Background Sync** | Dados sincronizados em segundo plano mesmo com app fechado |
| **Imagens** | Cache local por 7 dias (CacheFirst) |
| **API** | NetworkFirst com timeout de 3s, fallback em cache por 24h |
| **Página offline** | `offline.php` exibida quando a rede está indisponível |

---

## 🔒 Segurança

| Camada | Tecnologia |
|--------|------------|
| Autenticação | JWT (HS256, expira em 7 dias) |
| Senhas | bcrypt (custo 12), migração automática de plaintext |
| Proteção brute-force | Máximo 5 tentativas de login por IP / 15 minutos |
| Queries SQL | Apenas PDO com prepared statements (sem SQL injection) |
| XSS | `escapeHtml()` em todo conteúdo inserido via innerHTML |
| Headers HTTP | CSP, X-Frame-Options, X-Content-Type-Options, HSTS, XSS protection |
| Uploads | Restrição por tipo e tamanho, `.htaccess` no diretório |

---

## 🗄️ Banco de Dados

Banco único: **`onde2292_erp`** — MySQL/MariaDB

| Tabela | O que armazena |
|--------|---------------|
| `users` | Usuários, roles, fotos, metas mensais |
| `clients` | Dados completos dos clientes (CPF como PK) |
| `work_orders` | Ordens de serviço com histórico completo |
| `installation_checklists` | Checklists com status de aprovação |
| `checklist_items` | Itens individuais de cada checklist |
| `time_clock` | Registros de ponto por dia/usuário |
| `equipment_inventory` | Estoque com serial único |
| `inventory_movements` | Log de movimentações de estoque |
| `client_photos` | Fotos vinculadas a clientes |
| `client_locations` | Histórico de localizações de clientes |
| `audit_logs` | Log completo de todas as ações |
| `notifications` | Fila de notificações push |
| `offline_queue` | Fila de sincronização offline |
| `push_subscriptions` | Endpoints de push por usuário |
| `serial_history` | Histórico de trocas de equipamento |
| `plans` | Planos de internet disponíveis |
| `settings` | Configurações do sistema (meta mensal, etc.) |
| `login_attempts` | Tentativas de login para rate limiting |

---

## 🛠️ Stack Tecnológico

### Backend
- **PHP 7.4+** — lógica de negócio e APIs RESTful
- **MySQL/MariaDB** via PDO com prepared statements
- **JWT** — autenticação stateless
- **Hospedagem** — cPanel com Apache

### Frontend
- **Vanilla JavaScript (ES6+)** — sem frameworks
- **Tailwind CSS** via CDN — estilização
- **Google Material Symbols** — ícones
- **Google Fonts (Inter)** — tipografia
- **jsPDF + AutoTable** — geração de PDF no navegador

### PWA / Service Worker
- **Workbox v7** (CDN) — cache, offline, background sync
- **Web Push API + VAPID** — notificações push sem Firebase
- **manifest.json** — instalação, shortcuts, share target, file handlers, protocol handlers

### Integrações Externas
| Serviço | Finalidade |
|---------|-----------|
| **viacep.com.br** | Auto-preenchimento de endereço por CEP |
| **SGP System** | Sincronização de dados de clientes com sistema externo |
| **TR-069** | Gerenciamento remoto de dispositivos (modem/roteador) |

---

## 📲 Capacidades PWA (App Nativo)

- ✅ Instalável no Android e iOS (ícone na tela inicial)
- ✅ Funciona como app nativo (sem barra do navegador)
- ✅ Suporte offline completo com background sync
- ✅ Notificações push nativas (inclusive com app fechado)
- ✅ Atalhos de app (Novo Cadastro, Consultar, Ordens)
- ✅ Share Target — recebe fotos/arquivos de outros apps Android
- ✅ File Handlers — abre imagens/PDFs diretamente no app
- ✅ Protocol Handler — links `web+ondeline://` abrem no app
- ✅ Dark mode (sistema ou manual)
- ✅ Safe area para telas com notch
- ✅ Publicável na Google Play Store via PWABuilder (TWA)

---

## 📁 Estrutura de Arquivos

```
/
├── *.php                   # 16 páginas do app
├── partials/
│   ├── head.php            # Meta tags, imports Tailwind, fontes
│   └── bottom-nav.php      # Navegação inferior compartilhada
├── js/
│   ├── api.js              # Camada HTTP (fetch) com JWT
│   ├── app.js              # Auth check, tema, registro SW
│   ├── utils.js            # Validações, formatações, compressão de imagem
│   ├── feedback.js         # Toast, loading, sync offline
│   ├── components.js       # Header, nav, modais
│   └── pages/              # Scripts específicos por página
├── css/
│   └── transitions.css     # Animações e skeleton loading
├── api/
│   ├── config.php          # DB, JWT, CORS, logAudit()
│   └── *.php               # ~25 endpoints RESTful
├── uploads/                # Fotos dos clientes e OS
├── sw.js                   # Service Worker (Workbox)
└── manifest.json           # Manifesto PWA
```

---

## 🔄 Fluxo Típico de Uso

```
Técnico abre app (offline ou online)
    ↓
Login com JWT (7 dias de validade)
    ↓
Dashboard → visualiza meta, cadastros do dia, ações rápidas
    ↓
Recebe notificação push de nova OS atribuída
    ↓
Abre OS → inicia execução → registra progresso
    ↓
Chega no cliente → preenche checklist de instalação
    ↓
Vincula equipamento (serial) ao cliente
    ↓
Conclui OS com foto + descrição da resolução
    ↓
(Sem internet?) → dados ficam na fila offline
    ↓
Reconecta → sync automático → dados salvos no servidor
    ↓
Admin aprova checklist → técnico recebe push de confirmação
```

---

*Sistema desenvolvido para a **Ondeline Internet** — Gestão completa de campo, do primeiro contato ao suporte técnico.*
