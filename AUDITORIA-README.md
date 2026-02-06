# Sistema de Auditoria - App do Técnico

## Visão Geral

O sistema de auditoria permite acompanhar todas as ações realizadas pelos técnicos no aplicativo, incluindo:
- ✅ Login no sistema
- ✅ Cadastro de novos clientes
- ✅ Visualização de detalhes de clientes
- ✅ Vinculação de equipamentos

## Instalação

### 1. Criar a tabela no banco de dados

Execute o arquivo `create-audit-table.sql` no seu phpMyAdmin ou terminal:

```bash
# Via terminal (substitua usuário e senha)
mysql -u seu_usuario -p < create-audit-table.sql
```

Ou copie e cole o conteúdo do arquivo `create-audit-table.sql` no phpMyAdmin.

### 2. Verificar arquivos criados

Os seguintes arquivos foram criados/atualizados:

**Arquivos novos:**
- `create-audit-table.sql` - Script para criar a tabela de auditoria
- `api/audit-log.php` - API para registrar logs de auditoria
- `api/get-audit-logs.php` - API para buscar logs de auditoria
- `auditoria.html` - Página de visualização dos logs

**Arquivos modificados:**
- `api/login.php` - Adicionado registro de login
- `api/cadastro.php` - Adicionado registro de cadastro de cliente
- `api/vincular.php` - Adicionado registro de vinculação de equipamento
- `detalher.html` - Adicionado registro de visualização de cliente

## Como Usar

### Acessar a Página de Auditoria

Abra o arquivo `auditoria.html` no navegador:
```
http://seu-dominio.com/auditoria.html
```

### Funcionalidades da Página

1. **Filtro por Técnico**
   - Digite o nome do técnico na busca
   - Filtra automaticamente após 500ms

2. **Filtro por Tipo de Ação**
   - **Todos**: Mostra todos os registros
   - **Logins**: Apenas acessos ao sistema
   - **Cadastros**: Apenas novos clientes cadastrados
   - **Visualizações**: Apenas visualizações de detalhes de clientes
   - **Vinculações**: Apenas vinculações de equipamentos

3. **Filtro por Período**
   - **De**: Data inicial do período
   - **Até**: Data final do período

4. **Limpar Filtros**
   - Reseta todos os filtros para mostrar todos os registros

5. **Atualizar**
   - Recarrega os logs mais recentes

### Visualização dos Logs

Cada log mostra:
- **Ícone colorido** indicando o tipo de ação
- **Título da ação** (Login, Novo Cadastro, etc.)
- **Descrição** detalhada
- **Nome do cliente** (quando aplicável)
- **Detalhes adicionais** (plano, cidade, serial, etc.)
- **Nome do técnico** que realizou a ação
- **Data/hora** relativa (ex: "há 5 minutos") e completa ao passar o mouse

## Tipos de Ações Registradas

| Tipo | Descrição | Cor |
|------|-----------|-----|
| `login` | Técnico acessou o sistema | Verde |
| `client_created` | Novo cliente cadastrado | Azul |
| `client_viewed` | Detalhes do cliente visualizados | Roxo |
| `equipment_linked` | Equipamento vinculado ao cliente | Laranja |

## Estrutura da Tabela audit_logs

```sql
- id: Identificador único
- user_id: ID do usuário (tabela users)
- username: Nome de usuário
- action_type: Tipo de ação (login, client_created, etc.)
- action_description: Descrição da ação
- entity_type: Tipo de entidade (client, user, etc.)
- entity_id: ID da entidade (CPF do cliente, etc.)
- entity_name: Nome da entidade
- details: Detalhes adicionais em JSON
- ip_address: Endereço IP
- user_agent: Informações do navegador/dispositivo
- created_at: Data/hora do registro
```

## APIs Disponíveis

### 1. Registrar Log de Auditoria

**Endpoint:** `POST /api/audit-log.php`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
```

**Body:**
```json
{
  "action_type": "client_created",
  "action_description": "Novo cliente cadastrado",
  "entity_type": "client",
  "entity_id": "12345678900",
  "entity_name": "João Silva",
  "details": {
    "plan": "Fibra 100MB",
    "city": "Manaus",
    "phone": "92999999999"
  }
}
```

### 2. Buscar Logs de Auditoria

**Endpoint:** `GET /api/get-audit-logs.php`

**Parâmetros Query:**
- `limit`: Quantidade de registros (padrão: 100)
- `offset`: Paginação (padrão: 0)
- `action_type`: Filtro por tipo de ação
- `username`: Filtro por nome de usuário
- `start_date`: Data inicial (formato: YYYY-MM-DD)
- `end_date`: Data final (formato: YYYY-MM-DD)

**Exemplo:**
```
GET /api/get-audit-logs.php?limit=50&action_type=client_created&start_date=2024-01-01
```

## Ações que São Automaticamente Registradas

O sistema registra automaticamente as seguintes ações:

1. **Login**
   - Quando um técnico faz login
   - Arquivo: `api/login.php`

2. **Cadastro de Cliente**
   - Quando um novo cliente é cadastrado
   - Arquivo: `api/cadastro.php`

3. **Visualização de Cliente**
   - Quando os detalhes de um cliente são visualizados
   - Arquivo: `detalher.html`

4. **Vinculação de Equipamento**
   - Quando um equipamento é vinculado a um cliente
   - Arquivo: `api/vincular.php`

## Adicionar Novos Tipos de Auditoria

Para adicionar registro de auditoria em outras funcionalidades:

### 1. No PHP (APIs)

```php
// Após a ação principal ser concluída com sucesso
try {
    $auditStmt = $db->prepare("
        INSERT INTO audit_logs 
        (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $auditStmt->execute([
        $userId,
        $username,
        'nova_acao',
        'Descrição da nova ação',
        'tipo_entidade',
        $entityId,
        $entityName,
        json_encode($detalhes),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
} catch (Exception $e) {
    // Não falha a ação principal se a auditoria falhar
    error_log('Erro ao registrar auditoria: ' . $e->getMessage());
}
```

### 2. No JavaScript (Frontend)

```javascript
async function logAction(actionData) {
    try {
        var token = localStorage.getItem('authToken');
        if (!token) return;
        
        await fetch('/api/audit-log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(actionData)
        });
    } catch (error) {
        console.error('Erro ao registrar ação:', error);
    }
}

// Exemplo de uso
logAction({
    action_type: 'client_updated',
    action_description: 'Cliente atualizado',
    entity_type: 'client',
    entity_id: '12345678900',
    entity_name: 'João Silva',
    details: {
        'campo_alterado': 'valor_antigo -> valor_novo'
    }
});
```

## Personalização

### Adicionar Novo Tipo de Ação na Página de Auditoria

Edite `auditoria.html` e adicione:

1. **Novo botão de filtro:**
```html
<button class="filter-btn flex-shrink-0 h-9 px-4 rounded-full bg-gray-100 dark:bg-gray-800 text-[#111318] dark:text-white text-sm font-medium" data-action="sua_nova_acao">
    Sua Ação
</button>
```

2. **Ícone e cor:**
```javascript
function getActionIcon(actionType) {
    var icons = {
        // ... ícones existentes ...
        'sua_nova_acao': 'nome_do_icone'
    };
    return icons[actionType] || 'history';
}

function getActionColor(actionType) {
    var colors = {
        // ... cores existentes ...
        'sua_nova_acao': 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
    };
    return colors[actionType] || 'bg-gray-100 text-gray-600';
}

function getActionLabel(actionType) {
    var labels = {
        // ... labels existentes ...
        'sua_nova_acao': 'Sua Nova Ação'
    };
    return labels[actionType] || actionType;
}
```

## Dicas de Uso

1. **Monitoramento Diário**
   - Acesse a página regularmente para verificar atividades
   - Use filtros de data para ver atividades do dia

2. **Investigação de Problemas**
   - Filtre por técnico específico para verificar atividades
   - Use filtros de tipo de ação para rastrear eventos específicos

3. **Relatórios**
   - Exporte os dados via API para criar relatórios personalizados
   - Os logs podem ser filtrados por período para análises mensais

4. **Segurança**
   - Monitore logins de usuários
   - Verifique atividades incomuns ou fora do horário normal

## Troubleshooting

### Logs não aparecem

1. Verifique se a tabela `audit_logs` foi criada:
```sql
SHOW TABLES LIKE 'audit_logs';
```

2. Verifique se há erros no console do navegador (F12)

3. Verifique os logs de erro do PHP no servidor

### Erro ao registrar auditoria

O sistema está configurado para **não falhar** a ação principal se a auditoria falhar. Verifique os logs de erro do PHP:
```bash
tail -f /path/to/php/error.log
```

## Suporte

Para dúvidas ou problemas, verifique:
- A estrutura da tabela `audit_logs`
- As configurações do banco de dados em `api/config.php`
- Os logs de erro do navegador (F12 > Console)
- Os logs de erro do servidor PHP

---

**Desenvolvido para Ondeline Tech - App do Técnico**