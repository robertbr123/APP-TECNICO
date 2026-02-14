-- Atualiza a coluna type da tabela client_photos para aceitar tipo 'checklist'
-- A tabela pode ter sido criada com ENUM, então alteramos para VARCHAR

ALTER TABLE `client_photos` 
MODIFY COLUMN `type` VARCHAR(50) DEFAULT 'other';

-- Se precisar adicionar coluna de task_name para identificar de que tarefa é a foto:
-- ALTER TABLE `client_photos` ADD COLUMN `task_name` VARCHAR(255) DEFAULT NULL AFTER `type`;
