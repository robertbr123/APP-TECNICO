-- =====================================================
-- Atualização dos Templates de Checklist com Filtros por Tipo
-- =====================================================

USE onde2292_erp;

-- Adiciona coluna para tipos de instalação aplicáveis
ALTER TABLE checklist_templates 
ADD COLUMN IF NOT EXISTS applicable_types VARCHAR(100) DEFAULT 'new,migration,repair,maintenance';

-- Atualiza templates com filtros específicos
-- Tarefas de Pré-instalação (aplicáveis a todos)
UPDATE checklist_templates SET applicable_types = 'new,migration,repair,maintenance' 
WHERE task_category = 'pre_installation';

-- Instalação física (apenas nova instalação e migração)
UPDATE checklist_templates SET applicable_types = 'new,migration' 
WHERE task_category = 'installation';

-- Configuração (aplicável a todos, mas menos tarefas para reparo/manutenção)
UPDATE checklist_templates 
SET applicable_types = 'new,migration,repair,maintenance',
    is_required = FALSE
WHERE task_category = 'configuration' 
AND task_name LIKE '%senha%';

-- Testes (aplicável a todos)
UPDATE checklist_templates SET applicable_types = 'new,migration,repair,maintenance' 
WHERE task_category = 'testing';

-- Documentação (aplicável a todos)
UPDATE checklist_templates SET applicable_types = 'new,migration,repair,maintenance' 
WHERE task_category = 'documentation';

-- Tarefas específicas para Reparo/Manutenção
INSERT INTO checklist_templates (task_name, task_category, is_required, order_index, applicable_types) VALUES
('Diagnosticar problema reportado', 'pre_installation', TRUE, 0, 'repair,maintenance'),
('Verificar cabos e conexões', 'pre_installation', TRUE, 1, 'repair,maintenance'),
('Testar sinal na CTO', 'pre_installation', TRUE, 2, 'repair,maintenance'),
('Substituir equipamento defeituoso', 'installation', TRUE, 10, 'repair,maintenance'),
('Atualizar firmware do roteador', 'configuration', FALSE, 20, 'maintenance'),
('Verificar atualizações de segurança', 'configuration', FALSE, 21, 'maintenance'),
('Otimizar canais WiFi', 'configuration', FALSE, 22, 'maintenance'),
('Verificar consumo de banda', 'testing', FALSE, 30, 'maintenance'),
('Limpeza do equipamento', 'documentation', FALSE, 40, 'maintenance'),
('Relatório de serviço', 'documentation', TRUE, 45, 'repair,maintenance')
ON DUPLICATE KEY UPDATE task_name = task_name;

-- Tarefas específicas para Nova Instalação
INSERT INTO checklist_templates (task_name, task_category, is_required, order_index, applicable_types) VALUES
('Cadastrar cliente no sistema', 'pre_installation', TRUE, 0, 'new'),
('Verificar viabilidade técnica', 'pre_installation', TRUE, 1, 'new'),
('Instalar poste/caixa externa', 'installation', TRUE, 9, 'new'),
('Configurar PPPoE inicial', 'configuration', TRUE, 20, 'new'),
('Tutorial inicial com cliente', 'documentation', TRUE, 43, 'new')
ON DUPLICATE KEY UPDATE task_name = task_name;

-- Tarefas específicas para Migração
INSERT INTO checklist_templates (task_name, task_category, is_required, order_index, applicable_types) VALUES
('Verificar compatibilidade', 'pre_installation', TRUE, 0, 'migration'),
('Backup de configurações', 'pre_installation', TRUE, 1, 'migration'),
('Migrar configurações', 'configuration', TRUE, 20, 'migration'),
('Verificar funcionamento após migração', 'testing', TRUE, 30, 'migration')
ON DUPLICATE KEY UPDATE task_name = task_name;

SELECT 'Templates atualizados com sucesso!' as status;
