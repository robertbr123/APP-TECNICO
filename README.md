# 📱 Ondeline Tech - App do Técnico (PWA)

Sistema de cadastro e gestão de clientes para técnicos de campo da Ondeline Internet.

## 🚀 Funcionalidades

- ✅ **Login seguro** com autenticação JWT
- ✅ **Dashboard** com estatísticas em tempo real
- ✅ **Cadastro de clientes** com busca de CEP automática
- ✅ **Consulta de clientes** com busca por nome/CPF
- ✅ **Detalhes do cliente** com todas as informações
- ✅ **PWA instalável** no celular (Android e iOS)
- ✅ **Funciona offline** (cache de dados)
- ✅ **Dark mode** automático

---

## 📁 Estrutura do Projeto

```
APP Tecnico/
├── api/                    # Backend PHP
│   ├── config.php          # Configurações do banco
│   ├── login.php           # Autenticação
│   ├── clients.php         # CRUD de clientes
│   ├── plans.php           # Lista de planos
│   ├── installers.php      # Lista de instaladores
│   └── dashboard.php       # Estatísticas
├── js/                     # Frontend JavaScript
│   ├── api.js              # Comunicação com a API
│   └── app.js              # Lógica do app
├── icons/                  # Ícones do PWA
│   ├── icon.svg            # Ícone vetorial
│   └── generate-icons.html # Gerador de ícones PNG
├── login.html              # Página de login
├── dashboard.html          # Painel principal
├── novo-cadastro.html      # Formulário de cadastro
├── consultar.html          # Lista de clientes
├── detalher.html           # Detalhes do cliente
├── manifest.json           # Configuração PWA
├── sw.js                   # Service Worker
├── .htaccess               # Configuração Apache
└── README.md               # Este arquivo
```

---

## ⚙️ Configuração no cPanel

### 1️⃣ Upload dos Arquivos

1. Acesse o **Gerenciador de Arquivos** do cPanel
2. Navegue até `public_html` (ou o subdomínio desejado)
3. Faça upload de todos os arquivos mantendo a estrutura de pastas

### 2️⃣ Configurar o Banco de Dados

1. No cPanel, vá em **Bancos de dados MySQL**
2. Verifique se o banco `onde2292_cadastro` existe
3. Crie um usuário para o banco (se não existir):
   - Nome de usuário: `onde2292_user`
   - Crie uma senha segura
4. Adicione o usuário ao banco com **TODOS OS PRIVILÉGIOS**

### 3️⃣ Editar Configurações

Edite o arquivo `api/config.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'onde2292_cadastro');
define('DB_USER', 'onde2292_user');       // Seu usuário
define('DB_PASS', 'SUA_SENHA_AQUI');      // Sua senha
```

Também altere a chave secreta JWT:

```php
define('JWT_SECRET', 'sua_chave_secreta_unica_aqui');
```

### 4️⃣ Gerar Ícones do PWA

1. Abra no navegador: `https://seudominio.com/icons/generate-icons.html`
2. Clique em "Baixar Todos os Ícones"
3. Faça upload dos ícones PNG para a pasta `/icons/`

### 5️⃣ Configurar HTTPS (Obrigatório para PWA)

1. No cPanel, vá em **SSL/TLS**
2. Instale um certificado SSL (Let's Encrypt é gratuito)
3. Force HTTPS em **Domínios** > **Forçar HTTPS**

---

## 📱 Instalar no Celular

### Android (Chrome):
1. Acesse o app pelo Chrome
2. Toque nos 3 pontos (menu)
3. Toque em "Adicionar à tela inicial"
4. Confirme a instalação

### iPhone (Safari):
1. Acesse o app pelo Safari
2. Toque no botão de compartilhar ↗️
3. Toque em "Adicionar à Tela de Início"
4. Confirme com "Adicionar"

---

## 🔑 Login Padrão

Usuários cadastrados no banco:

| Usuário | Senha | 
|---------|-------|
| admin | (hash bcrypt) |
| robert | admin |

⚠️ **Importante:** Altere as senhas após o primeiro acesso!

---

## 🛠️ API Endpoints

### Autenticação
- `POST /api/login.php` - Login

### Clientes
- `GET /api/clients.php` - Listar clientes
- `GET /api/clients.php?cpf=123` - Buscar por CPF
- `GET /api/clients.php?search=termo` - Buscar por nome/CPF
- `POST /api/clients.php` - Criar cliente
- `PUT /api/clients.php` - Atualizar cliente
- `DELETE /api/clients.php?cpf=123` - Excluir cliente

### Outros
- `GET /api/plans.php` - Listar planos
- `GET /api/installers.php` - Listar instaladores
- `GET /api/dashboard.php` - Estatísticas

---

## 🐛 Solução de Problemas

### Erro de conexão com banco
- Verifique as credenciais em `api/config.php`
- Confirme que o usuário tem permissão no banco
- Teste a conexão no phpMyAdmin

### PWA não instala
- Verifique se está usando HTTPS
- Confirme que `manifest.json` está acessível
- Verifique os ícones na pasta `/icons/`

### Erro 500 na API
- Verifique os logs de erro do PHP no cPanel
- Confirme que a versão do PHP é 7.4+
- Verifique permissões dos arquivos (644 para arquivos, 755 para pastas)

---

## 📄 Licença

Projeto desenvolvido para uso interno da Ondeline Internet.

---

**Desenvolvido com ❤️ para Ondeline**
