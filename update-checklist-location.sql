-- =====================================================
-- Atualização: Adiciona campos de localização ao checklist
-- Execute este SQL no banco de dados
-- =====================================================

-- Adiciona colunas de localização do técnico ao iniciar o checklist
ALTER TABLE `installation_checklists` 
ADD COLUMN `tech_latitude` DECIMAL(10, 8) DEFAULT NULL AFTER `notes`,
ADD COLUMN `tech_longitude` DECIMAL(11, 8) DEFAULT NULL AFTER `tech_latitude`,
ADD COLUMN `tech_location_accuracy` DECIMAL(10, 2) DEFAULT NULL AFTER `tech_longitude`,
ADD COLUMN `location_captured_at` TIMESTAMP NULL AFTER `tech_location_accuracy`;

-- Se alguma coluna já existir, ignore o erro
