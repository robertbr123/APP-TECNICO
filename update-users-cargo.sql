-- =====================================================
-- Adiciona colunas na tabela users
-- Execute cada comando separadamente no phpMyAdmin
-- Se der "Duplicate column name", a coluna já existe (OK)
-- =====================================================

-- Adiciona coluna cargo
ALTER TABLE users ADD COLUMN cargo VARCHAR(100) DEFAULT NULL;

-- Adiciona coluna city
ALTER TABLE users ADD COLUMN city VARCHAR(100) DEFAULT NULL;

-- Adiciona coluna active (1=ativo, 0=inativo)
ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1;

-- Verifica as colunas existentes
SHOW COLUMNS FROM users;
