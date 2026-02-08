-- =====================================================
-- Tabela de Checklist de Instalação
-- =====================================================

USE onde2292_erp;

-- Tabela de checklists de instalação
CREATE TABLE IF NOT EXISTS installation_checklists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_cpf VARCHAR(11) NOT NULL,
    client_name VARCHAR(150) NOT NULL,
    technician_id INT NOT NULL,
    technician_name VARCHAR(100) NOT NULL,
    installation_type ENUM('new', 'migration', 'repair', 'maintenance') DEFAULT 'new',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_cpf (client_cpf),
    INDEX idx_technician (technician_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do checklist
CREATE TABLE IF NOT EXISTS checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_category ENUM('pre_installation', 'installation', 'configuration', 'testing', 'documentation') DEFAULT 'installation',
    is_required BOOLEAN DEFAULT TRUE,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    completed_by INT NULL,
    notes TEXT,
    photo_url VARCHAR(255),
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (checklist_id) REFERENCES installation_checklists(id) ON DELETE CASCADE,
    INDEX idx_checklist (checklist_id),
    INDEX idx_category (task_category),
    INDEX idx_completed (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de templates de checklist (tarefas padrão)
CREATE TABLE IF NOT EXISTS checklist_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    task_category ENUM('pre_installation', 'installation', 'configuration', 'testing', 'documentation') DEFAULT 'installation',
    is_required BOOLEAN DEFAULT TRUE,
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (task_category),
    INDEX idx_active (is_active),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insere templates padrão
INSERT INTO checklist_templates (task_name, task_category, is_required, order_index) VALUES
-- Pré-instalação
('Verificar sinal na caixa de atendimento', 'pre_installation', TRUE, 1),
('Conferir materiais e equipamentos', 'pre_installation', TRUE, 2),
('Verificar rota de cabeamento', 'pre_installation', TRUE, 3),
('Identificar ponto de entrada na residência', 'pre_installation', TRUE, 4),

-- Instalação
('Instalar caixa de proteção (se necessário)', 'installation', TRUE, 10),
('Passar cabo de fibra óptica', 'installation', TRUE, 11),
('Conectar fibra na CTO', 'installation', TRUE, 12),
('Instalar roteador/ONU na posição indicada', 'installation', TRUE, 13),
('Organizar cabos e identificar', 'installation', TRUE, 14),
('Fazer fusão da fibra (se necessário)', 'installation', FALSE, 15),

-- Configuração
('Configurar PPPoE no roteador', 'configuration', TRUE, 20),
('Alterar senha WiFi padrão', 'configuration', TRUE, 21),
('Configurar 2.4GHz e 5GHz', 'configuration', TRUE, 22),
('Verificar sincronização ONU', 'configuration', TRUE, 23),

-- Testes
('Testar velocidade de download', 'testing', TRUE, 30),
('Testar velocidade de upload', 'testing', TRUE, 31),
('Verificar latência (ping)', 'testing', TRUE, 32),
('Testar estabilidade da conexão', 'testing', TRUE, 33),
('Verificar alcance do WiFi nos cômodos', 'testing', TRUE, 34),
('Testar streaming de vídeo', 'testing', FALSE, 35),

-- Documentação
('Tirar foto do roteador instalado', 'documentation', TRUE, 40),
('Tirar foto do cabeamento', 'documentation', TRUE, 41),
('Tirar foto do sinal na ONU', 'documentation', TRUE, 42),
('Orientar cliente sobre uso', 'documentation', TRUE, 43),
('Entregar senha anotada ao cliente', 'documentation', TRUE, 44),
('Coletar assinatura do cliente', 'documentation', FALSE, 45)
ON DUPLICATE KEY UPDATE task_name = task_name;

-- Verifica se as tabelas foram criadas
SELECT 'Tabelas de checklist criadas com sucesso!' as status;
