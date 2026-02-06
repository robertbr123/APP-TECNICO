-- =====================================================
-- Tabela de Auditoria - App do Técnico
-- Registra todas as ações realizadas pelos técnicos
-- =====================================================

USE onde2292_cadastro;

-- Cria tabela de auditoria se não existir
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Índices para melhorar performance nas consultas
CREATE INDEX IF NOT EXISTS `idx_audit_user_action` ON `audit_logs` (`user_id`, `action_type`);
CREATE INDEX IF NOT EXISTS `idx_audit_date` ON `audit_logs` (`created_at`);

SELECT 'Tabela de auditoria criada com sucesso!' as status;