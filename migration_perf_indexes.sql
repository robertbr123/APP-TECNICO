-- =====================================================
-- Migração de Performance — Índices para client_photos
-- Execute este script no banco de dados uma única vez.
-- =====================================================

-- Índice composto (cpf, created_at) para otimizar ORDER BY created_at DESC
-- nas queries de fotos do cliente (get-fotos.php e handleTimeline).
-- Evita full-scan na tabela ao ordenar por data.
ALTER TABLE `client_photos`
    ADD INDEX IF NOT EXISTS `idx_client_photos_cpf_date` (`cpf`, `created_at`);
