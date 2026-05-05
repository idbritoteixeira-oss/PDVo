-- ============================================================
-- PDVo v11 — SQL Patch
-- Execute via phpMyAdmin ou linha de comando MySQL
-- SEGURO: usa IF NOT EXISTS / INSERT IGNORE / DROP duplicatas
-- ============================================================

-- ============================================================
-- 1. LIMPAR DUPLICATAS de superadmin_users (mantém o mais antigo)
-- ============================================================
DELETE su1
FROM superadmin_users su1
INNER JOIN superadmin_users su2
  ON su1.email = su2.email AND su1.id > su2.id;

-- ============================================================
-- 2. TABELA: suporte_tickets
-- ============================================================
CREATE TABLE IF NOT EXISTS suporte_tickets (
  id             INT NOT NULL AUTO_INCREMENT,
  tenant_id      INT NOT NULL,
  assunto        VARCHAR(255) NOT NULL,
  mensagem       TEXT NOT NULL,
  resposta_sa    TEXT DEFAULT NULL,
  status         VARCHAR(40) DEFAULT 'aguardando_sa',
  respondido_em  DATETIME DEFAULT NULL,
  created_at     DATETIME DEFAULT NOW(),
  updated_at     DATETIME DEFAULT NOW() ON UPDATE NOW(),
  PRIMARY KEY (id),
  KEY idx_tenant_id (tenant_id),
  KEY idx_status    (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABELA: superadmin_config — chaves novas
-- ============================================================
CREATE TABLE IF NOT EXISTS superadmin_config (
  chave VARCHAR(80) NOT NULL,
  valor TEXT DEFAULT '',
  PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chaves novas de indicações
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('indicacao_ativo',             '0');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('indicacao_recompensa_desc',   '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('indicacao_valor_recompensa',  '0.00');

-- Chaves de PIX
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('pix_chave',          '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('pix_nome',           '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('pix_cidade',         '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('email_notificacao',  '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('email_remetente',    '');

-- Chaves de App
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('app_aviso_ativo',       '0');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('app_aviso_titulo',      '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('app_aviso_mensagem',    '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('app_aviso_url_android', '');
INSERT IGNORE INTO superadmin_config (chave, valor) VALUES ('app_aviso_url_ios',     '');

-- ============================================================
-- 4. TABELA: email_log — garante existência
-- ============================================================
CREATE TABLE IF NOT EXISTS email_log (
  id         INT NOT NULL AUTO_INCREMENT,
  para_email VARCHAR(191) NOT NULL,
  para_nome  VARCHAR(120) DEFAULT NULL,
  assunto    TEXT NOT NULL,
  corpo      MEDIUMTEXT,
  status     VARCHAR(20) DEFAULT 'enviado',
  erro       TEXT,
  enviado_em DATETIME DEFAULT NOW(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABELA: tenants — colunas opcionais caso não existam
--    (ALTER TABLE IF NOT EXISTS não existe no MySQL antigo;
--     use o bloco abaixo apenas se necessário)
-- ============================================================
-- ALTER TABLE tenants ADD COLUMN IF NOT EXISTS data_vencimento DATE DEFAULT NULL;
-- ALTER TABLE tenants ADD COLUMN IF NOT EXISTS plano_nome VARCHAR(50) DEFAULT 'Basic';

-- ============================================================
-- 6. ÍNDICES auxiliares (ignoram se já existem via IF NOT EXISTS)
-- ============================================================
-- CREATE INDEX IF NOT EXISTS idx_ped_status ON pedidos_ativacao (status);
-- CREATE INDEX IF NOT EXISTS idx_ped_created ON pedidos_ativacao (created_at);

-- ============================================================
-- FIM DO PATCH v11
-- ============================================================
SELECT 'PDVo v11 patch aplicado com sucesso!' AS resultado;
