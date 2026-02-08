-- =====================================================
-- Atualização do Sistema de Aprovação de Checklists
-- =====================================================

USE onde2292_erp;

-- Adiciona novos campos para controle de aprovação
ALTER TABLE installation_checklists 
ADD COLUMN IF NOT EXISTS approval_status ENUM('pending', 'pending_approval', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS approved_by INT NULL,
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS rejection_notes TEXT NULL,
ADD INDEX idx_approval_status (approval_status),
ADD INDEX idx_approved_by (approved_by);

-- Atualiza registros existentes
UPDATE installation_checklists 
SET approval_status = 'approved' 
WHERE status = 'completed' AND approval_status IS NULL;

UPDATE installation_checklists 
SET approval_status = 'pending' 
WHERE status != 'completed' AND approval_status IS NULL;

-- Tabela de histórico de aprovações
CREATE TABLE IF NOT EXISTS checklist_approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_id INT NOT NULL,
    action ENUM('submitted', 'approved', 'rejected', 'deleted') NOT NULL,
    action_by INT NOT NULL,
    action_by_name VARCHAR(100) NOT NULL,
    action_by_role ENUM('admin', 'tecnico') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (checklist_id) REFERENCES installation_checklists(id) ON DELETE CASCADE,
    INDEX idx_checklist (checklist_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Sistema de aprovação configurado com sucesso!' as status;
