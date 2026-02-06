-- =====================================================
-- Script para adicionar coluna 'contrato' na tabela clients
-- Execute este script no phpMyAdmin
-- =====================================================

USE onde2292_erp;

-- Adiciona coluna contrato para armazenar o número do contrato do SGP
ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `contrato` varchar(20) DEFAULT NULL AFTER `serial`;

-- Verifica se a coluna foi adicionada
DESCRIBE `clients`;

SELECT 'Coluna contrato adicionada com sucesso!' as status;
