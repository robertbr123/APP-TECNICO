-- =====================================================
-- Script de Atualização da Tabela Clients
-- Execute este script no phpMyAdmin do cPanel
-- =====================================================

USE onde2292_cadastro;

-- Adiciona coluna CEP se não existir
ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `cep` varchar(10) DEFAULT NULL AFTER `phone`;

-- Adiciona coluna PPPoE User se não existir
ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `pppoe_user` varchar(100) DEFAULT NULL AFTER `plan`;

-- Adiciona coluna PPPoE Pass se não existir
ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `pppoe_pass` varchar(100) DEFAULT NULL AFTER `pppoe_user`;

-- Se o comando acima der erro (MySQL antigo não suporta IF NOT EXISTS no ALTER TABLE),
-- use os comandos abaixo um por um, ignorando erros de "coluna já existe":

-- ALTER TABLE `clients` ADD COLUMN `cep` varchar(10) DEFAULT NULL AFTER `phone`;
-- ALTER TABLE `clients` ADD COLUMN `pppoe_user` varchar(100) DEFAULT NULL AFTER `plan`;
-- ALTER TABLE `clients` ADD COLUMN `pppoe_pass` varchar(100) DEFAULT NULL AFTER `pppoe_user`;

-- Verifica estrutura da tabela
DESCRIBE `clients`;

SELECT 'Tabela clients atualizada com sucesso!' as status;
