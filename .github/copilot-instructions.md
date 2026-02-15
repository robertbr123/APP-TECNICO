# Copilot Instructions - Ondeline Tech App

## Sobre o Projeto

**Ondeline Tech App** e um Progressive Web App (PWA) para tecnicos de campo da Ondeline Internet. Permite gerenciar clientes, instalacoes, estoque de equipamentos, ponto eletronico e auditorias diretamente pelo celular.

## Stack Tecnologico

### Frontend
- **HTML5** com paginas independentes (SPA-like com navegacao por paginas)
- **Tailwind CSS** via CDN para estilizacao
- **Vanilla JavaScript** (ES6+) - sem frameworks
- **Google Material Symbols Outlined** para icones
- **Google Fonts (Inter)** para tipografia
- **Service Worker** (`sw.js`) para suporte offline e cache
- **PWA** com `manifest.json` para instalacao no dispositivo

### Backend
- **PHP 7.4+** com arquitetura RESTful
- **MySQL/MariaDB** via PDO (prepared statements)
- **JWT** para autenticacao (Bearer token)
- **Hospedagem**: cPanel com Apache

### Bancos de Dados
- `onde2292_cadastro`: usuarios, auditoria, ponto, fotos, estoque, notificacoes
- `onde2292_erp`: clientes, checklists de instalacao, planos

## Estrutura do Projeto

```
/
├── *.html              # Paginas do app (14 paginas)
├── js/
│   ├── api.js          # Camada HTTP (fetch) com auth JWT
│   ├── app.js          # Inicializacao, auth check, theme, SW registration
│   ├── utils.js        # Validacao (CPF, telefone), formatacao, compressao de imagem
│   ├── feedback.js     # Toast notifications
│   └── geolocation.js  # Servicos GPS
├── css/
│   └── transitions.css # Animacoes de pagina e skeleton loading
├── api/
│   ├── config.php      # Conexao DB, JWT config, CORS
│   ├── Logger.php      # Classe de logging
│   ├── Validator.php   # Validacao de input server-side
│   ├── login.php       # Autenticacao JWT
│   ├── clients.php     # CRUD de clientes
│   ├── checklist.php   # Sistema de checklist de instalacao
│   ├── inventory.php   # Gestao de estoque
│   ├── time-clock.php  # Registro de ponto
│   ├── upload.php      # Upload de arquivos
│   └── ...             # Outros endpoints
├── database.sql        # Script consolidado do banco de dados
├── sw.js               # Service Worker
├── manifest.json       # PWA manifest
└── uploads/            # Diretorio de uploads
```

## Paginas do App

| Pagina | Arquivo | Funcao |
|--------|---------|--------|
| Login | `login.html` | Autenticacao com JWT |
| Dashboard | `dashboard.html` | Metricas e acoes rapidas |
| Novo Cadastro | `novo-cadastro.html` | Formulario de cadastro de cliente |
| Consultar | `consultar.html` | Busca e listagem de clientes |
| Detalhes | `detalher.html` | Perfil completo do cliente |
| Checklist | `checklist.html` | Checklist de instalacao |
| Estoque | `estoque.html` | Gestao de inventario |
| Ponto | `ponto.html` | Registro de ponto com GPS e foto |
| Mapa | `mapa.html` | Mapa com localizacoes de clientes |
| Historico | `historico.html` | Metricas de desempenho |
| Auditoria | `auditoria.html` | Logs de auditoria |
| Vincular | `vincular-equipamento.html` | Vincular equipamento ao cliente |
| Ajustes | `ajustes.html` | Configuracoes do app |
| Admin | `admin.html` | Painel administrativo |

## Convencoes de Codigo

### HTML
- Cada pagina e um arquivo HTML independente com Tailwind embutido
- Meta tags PWA obrigatorias: `theme-color`, `apple-mobile-web-app-capable`
- IDs descritivos em portugues ou ingles consistente
- Dark mode via classe CSS no `<html>` ou `<body>`

### JavaScript
- Usar `async/await` para chamadas assincronas
- Todas as chamadas API passam pelo `js/api.js`
- Token JWT salvo em `localStorage` como `token`
- Dados do usuario em `localStorage` como `user` (JSON)
- Validacoes de CPF, telefone e CEP no `js/utils.js`
- Feedback via `js/feedback.js` (toasts)
- Geolocalizacao via `js/geolocation.js`

### PHP (API)
- Toda API deve incluir `config.php` no topo
- Usar PDO com prepared statements (NUNCA concatenar SQL)
- Validar JWT em rotas protegidas via funcao `validateToken()`
- Respostas em JSON com `Content-Type: application/json`
- Formato de resposta: `{ "success": true/false, "data": ..., "message": "..." }`
- CORS habilitado para todas as origens
- Registrar acoes importantes na tabela `audit_logs`

### CSS
- Tailwind CSS para layout e componentes
- Classes customizadas em `css/transitions.css` para animacoes
- Suporte a dark mode obrigatorio
- Design mobile-first e responsivo
- Safe area insets para dispositivos com notch

## Autenticacao

- Login retorna JWT com expiracao de 7 dias
- Token enviado como `Authorization: Bearer <token>` em todas as requisicoes
- Roles: `admin` (acesso total) e `user` (tecnico, acesso limitado)
- Verificar auth no carregamento de cada pagina via `app.js`

## Funcionalidades Principais

### Gestao de Clientes
- CPF como chave primaria (11 digitos, sem formatacao no banco)
- Busca por nome, CPF, cidade, serial
- Captura de GPS na localizacao do cliente
- Historico de troca de serial de equipamento

### Checklist de Instalacao
- Templates por tipo: nova, migracao, reparo, manutencao
- 5 categorias: pre_instalacao, instalacao, configuracao, teste, documentacao
- Fluxo de aprovacao: tecnico submete > admin aprova/rejeita
- Captura de GPS do tecnico ao iniciar

### Estoque de Equipamentos
- Tipos: router, modem, onu, cpe, antenna, cable, accessory
- Status: available, in_use, maintenance, defective, lost
- Rastreamento de movimentacoes com historico completo
- Alertas automaticos

### Ponto Eletronico
- Check-in/out com foto obrigatoria (camera)
- GPS com latitude, longitude e precisao
- Calculo automatico de horas trabalhadas
- Um registro por dia por usuario

### Suporte Offline
- Service Worker com cache de assets
- Fila de acoes offline sincronizadas quando online
- localStorage para dados locais

## Regras de Negocio Importantes

1. CPF deve ser validado (algoritmo) antes de salvar
2. Serial de equipamento e unico no sistema
3. Fotos sao comprimidas no frontend antes do upload
4. Registro de ponto e unico por usuario/dia
5. Checklists completados precisam de aprovacao do admin
6. Todas as acoes criticas sao registradas em audit_logs
7. Equipamentos defeituosos geram alertas automaticos

## Seguranca

- Senhas com `password_hash()` (bcrypt)
- JWT com chave secreta configuravel
- Prepared statements em todas as queries SQL
- Validacao de input no frontend E backend
- Upload de arquivos restrito por tipo e tamanho
- `.htaccess` protegendo diretorio de uploads
- HTTPS obrigatorio para PWA e geolocalização

## Como Contribuir

1. Sempre testar em dispositivo movel (PWA mobile-first)
2. Manter compatibilidade offline
3. Validar inputs tanto no frontend quanto no backend
4. Registrar acoes na auditoria
5. Seguir o padrao de resposta JSON da API
6. Usar Tailwind para estilizacao (evitar CSS inline)
7. Manter dark mode funcional em novos componentes
