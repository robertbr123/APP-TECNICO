-- =====================================================
-- Adiciona coluna cargo na tabela users
-- Execute este script se o campo cargo não estiver funcionando
-- =====================================================

-- Adiciona coluna cargo se não existir
ALTER TABLE users ADD COLUMN IF NOT EXISTS cargo VARCHAR(100) DEFAULT NULL;

-- Alternativa para MySQL < 8.0 (sem IF NOT EXISTS)
-- Execute manualmente se a coluna não existir:
-- ALTER TABLE users ADD COLUMN cargo VARCHAR(100) DEFAULT NULL;

-- Verifica se a coluna foi adicionada
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
AND COLUMN_NAME IN ('city', 'photo', 'cargo');
