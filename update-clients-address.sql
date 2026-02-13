-- Atualização da tabela clients para adicionar campos de bairro e estado
-- Execute este script no banco de dados ondeline

-- Adiciona coluna de estado (UF)
ALTER TABLE clients 
ADD COLUMN IF NOT EXISTS state VARCHAR(2) DEFAULT NULL AFTER city;

-- Adiciona coluna de bairro
ALTER TABLE clients 
ADD COLUMN IF NOT EXISTS neighborhood VARCHAR(100) DEFAULT NULL AFTER state;

-- Caso a sintaxe IF NOT EXISTS não funcione no seu MySQL, use:
-- ALTER TABLE clients ADD COLUMN state VARCHAR(2) DEFAULT NULL AFTER city;
-- ALTER TABLE clients ADD COLUMN neighborhood VARCHAR(100) DEFAULT NULL AFTER state;

-- Índice para melhorar buscas por cidade e estado
CREATE INDEX IF NOT EXISTS idx_clients_city_state ON clients(city, state);
