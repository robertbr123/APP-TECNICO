-- =====================================================
-- Atualização da tabela clients - adicionar colunas faltantes
-- Execute cada comando separadamente no phpMyAdmin
-- Se der "Duplicate column name", a coluna já existe (OK)
-- =====================================================

-- Estado (UF)
ALTER TABLE clients ADD COLUMN state VARCHAR(2) DEFAULT NULL;

-- Bairro
ALTER TABLE clients ADD COLUMN neighborhood VARCHAR(100) DEFAULT NULL;

-- CEP
ALTER TABLE clients ADD COLUMN cep VARCHAR(9) DEFAULT NULL;

-- Latitude
ALTER TABLE clients ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL;

-- Longitude
ALTER TABLE clients ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL;

-- Precisão da localização
ALTER TABLE clients ADD COLUMN location_accuracy FLOAT DEFAULT NULL;

-- Verifica as colunas
SHOW COLUMNS FROM clients;
