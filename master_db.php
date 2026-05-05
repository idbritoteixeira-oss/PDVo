<?php
// ============================================================
// PDVo - Master DB: Tenants, Pedidos, SuperAdmin, Config PIX
// Suporta SQLite (dev) e MySQL (produção/cPanel)
// ============================================================

function getMasterPDO(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $configFile = dirname(__DIR__, 2) . '/config.php';
    if (!file_exists($configFile)) $configFile = dirname(__DIR__, 3) . '/config.php';
    if (file_exists($configFile)) require_once $configFile;

    $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';

    try {
        if ($driver === 'mysql') {
            $pdo = new PDO(
                'mysql:host=' . (defined('DB_HOST') ? DB_HOST : 'localhost')
                . ';dbname=' . (defined('DB_NAME') ? DB_NAME : 'pdvo')
                . ';charset=utf8mb4',
                defined('DB_USER') ? DB_USER : 'root',
                defined('DB_PASS') ? DB_PASS : '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );
            _initMasterDB($pdo, 'mysql');
        } else {
            $dataDir = dirname(__DIR__, 2) . '/.data';
            if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
            $path = $dataDir . '/master.sqlite';
            $pdo  = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
            _initMasterDB($pdo, 'sqlite');
        }
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Falha na conexão com o banco master: ' . $e->getMessage()]));
    }

    return $pdo;
}

function masterNowSql(string $driver = ''): string {
    if (!$driver) $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
    return $driver === 'mysql' ? 'NOW()' : "datetime('now','localtime')";
}

function _initMasterDB(PDO $pdo, string $driver): void {
    $ai  = $driver === 'mysql' ? 'INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $now = $driver === 'mysql' ? 'NOW()' : "datetime('now','localtime')";
    $eng = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    // ---- Tabelas MASTER ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tenants (
            id              $ai,
            slug            VARCHAR(60) NOT NULL,
            razao_social    TEXT NOT NULL,
            nome_resp       TEXT NOT NULL,
            email           VARCHAR(191) NOT NULL,
            whatsapp        VARCHAR(30),
            plano_id        INT DEFAULT 1,
            plano_nome      VARCHAR(30) DEFAULT 'BASIC',
            status          VARCHAR(20) DEFAULT 'pendente',
            data_ativacao   VARCHAR(20),
            data_vencimento VARCHAR(20) DEFAULT '2099-12-31',
            db_path         TEXT,
            admin_senha     TEXT,
            observacoes     TEXT,
            created_at      TEXT DEFAULT ({$now})
        )$eng;
    ");
    if ($driver === 'mysql') {
        try { $pdo->exec("ALTER TABLE tenants ADD UNIQUE INDEX idx_tenants_slug (slug)"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE tenants ADD UNIQUE INDEX idx_tenants_email (email)"); } catch (PDOException $e) {}
    } else {
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_tenants_slug  ON tenants(slug)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_tenants_email ON tenants(email)");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pedidos_ativacao (
            id           $ai,
            tenant_slug  VARCHAR(60),
            razao_social TEXT NOT NULL,
            nome         TEXT NOT NULL,
            email        VARCHAR(191) NOT NULL,
            whatsapp     VARCHAR(30) NOT NULL,
            plano_id     INT NOT NULL,
            plano_nome   VARCHAR(30) NOT NULL,
            valor        DECIMAL(10,2) NOT NULL,
            status       VARCHAR(30) DEFAULT 'pendente',
            payload_pix  TEXT,
            ref          VARCHAR(60),
            observacoes  TEXT,
            created_at   TEXT DEFAULT ({$now}),
            updated_at   TEXT DEFAULT ({$now})
        )$eng;
    ");
    if ($driver === 'mysql') {
        try { $pdo->exec("ALTER TABLE pedidos_ativacao ADD UNIQUE INDEX idx_pedidos_ref (ref)"); } catch (PDOException $e) {}
    } else {
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_pedidos_ref ON pedidos_ativacao(ref)");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS superadmin_users (
            id         $ai,
            nome       TEXT NOT NULL,
            email      VARCHAR(191) NOT NULL,
            senha      TEXT NOT NULL,
            created_at TEXT DEFAULT ({$now})
        )$eng;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS planos_sistema (
            id                INT NOT NULL,
            nome              VARCHAR(30) NOT NULL,
            valor             DECIMAL(10,2) NOT NULL,
            limite_produtos   INT DEFAULT 100,
            limite_operadores INT DEFAULT 1,
            limite_gerentes   INT DEFAULT 0,
            descricao         TEXT,
            destaque          INT DEFAULT 0
        )$eng;
    ");
    if ($driver === 'mysql') {
        try { $pdo->exec("ALTER TABLE planos_sistema ADD PRIMARY KEY (id)"); } catch (PDOException $e) {}
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_log (
            id         $ai,
            para_email TEXT NOT NULL,
            para_nome  TEXT,
            assunto    TEXT NOT NULL,
            corpo      MEDIUMTEXT,
            status     VARCHAR(20) DEFAULT 'enviado',
            erro       TEXT,
            enviado_em TEXT DEFAULT ({$now})
        )$eng;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS superadmin_config (
            chave VARCHAR(80) NOT NULL,
            valor TEXT DEFAULT '',
            PRIMARY KEY (chave)
        )$eng;
    ");

    // ---- Tabela: indicacoes ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS indicacoes (
            id              $ai,
            indicador_slug  VARCHAR(60) NOT NULL,
            indicador_email VARCHAR(191),
            indicador_razao TEXT,
            indicado_email  VARCHAR(191) NOT NULL,
            indicado_razao  TEXT,
            pedido_id       INT,
            pedido_ref      VARCHAR(60),
            status          VARCHAR(20) DEFAULT 'pendente',
            observacoes     TEXT,
            validated_at    TEXT,
            created_at      TEXT DEFAULT ({$now})
        )$eng;
    ");
    if ($driver === 'mysql') {
        try { $pdo->exec("ALTER TABLE indicacoes ADD INDEX idx_ind_indicador (indicador_slug)"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE indicacoes ADD INDEX idx_ind_status (status)"); } catch (PDOException $e) {}
        // Adiciona coluna codigo_indicacao em pedidos_ativacao (safe upgrade)
        try { $pdo->exec("ALTER TABLE pedidos_ativacao ADD COLUMN codigo_indicacao VARCHAR(60)"); } catch (PDOException $e) {}
    } else {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ind_indicador ON indicacoes(indicador_slug)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ind_status    ON indicacoes(status)");
        try { $pdo->exec("ALTER TABLE pedidos_ativacao ADD COLUMN codigo_indicacao VARCHAR(60)"); } catch (PDOException $e) {}
    }

    // ---- Tabela: tenant_activity (online user tracking) ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tenant_activity (
            session_id VARCHAR(128) NOT NULL,
            user_id    INT DEFAULT 0,
            last_seen  TEXT,
            PRIMARY KEY (session_id)
        )$eng;
    ");

    // ---- Tabela: email_templates ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_templates (
            id         $ai,
            nome       VARCHAR(120) NOT NULL,
            assunto    TEXT NOT NULL,
            corpo      MEDIUMTEXT,
            created_at TEXT DEFAULT ({$now})
        )$eng;
    ");

    // ---- Tabelas TENANT (somente no banco MySQL unificado) ----
    // Em SQLite, as tabelas de tenant ficam em arquivos separados (init_sqlite.php).
    if ($driver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id           INT NOT NULL AUTO_INCREMENT,
                tenant_id    INT NOT NULL DEFAULT 0,
                nome         VARCHAR(191) NOT NULL,
                email        VARCHAR(191) NOT NULL,
                senha        TEXT NOT NULL,
                perfil       VARCHAR(20) NOT NULL DEFAULT 'operador',
                ativo        TINYINT NOT NULL DEFAULT 1,
                ultimo_login TEXT,
                created_at   DATETIME DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        try { $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX idx_users_email_t (tenant_id,email)"); } catch (PDOException $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categorias (
                id         INT NOT NULL AUTO_INCREMENT,
                tenant_id  INT NOT NULL DEFAULT 0,
                nome       VARCHAR(191) NOT NULL,
                descricao  TEXT,
                created_at DATETIME DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subcategorias (
                id           INT NOT NULL AUTO_INCREMENT,
                tenant_id    INT NOT NULL DEFAULT 0,
                nome         VARCHAR(191) NOT NULL,
                descricao    TEXT,
                categoria_id INT,
                created_at   DATETIME DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id             INT NOT NULL AUTO_INCREMENT,
                tenant_id      INT NOT NULL DEFAULT 0,
                nome           VARCHAR(255) NOT NULL,
                descricao      TEXT,
                codigo_barras  VARCHAR(60),
                preco          DECIMAL(10,2) NOT NULL DEFAULT 0,
                custo          DECIMAL(10,2) DEFAULT 0,
                estoque        DECIMAL(10,3) NOT NULL DEFAULT 0,
                estoque_minimo DECIMAL(10,3) DEFAULT 5,
                unidade        VARCHAR(10) DEFAULT 'un',
                categoria_id   INT,
                ativo          TINYINT NOT NULL DEFAULT 1,
                imagem_url     TEXT,
                created_at     DATETIME DEFAULT NOW(),
                updated_at     DATETIME DEFAULT NOW() ON UPDATE NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS caixas (
                id             INT NOT NULL AUTO_INCREMENT,
                tenant_id      INT NOT NULL DEFAULT 0,
                user_id        INT,
                fundo_caixa    DECIMAL(10,2) DEFAULT 0,
                total_vendas   DECIMAL(10,2) DEFAULT 0,
                total_entradas DECIMAL(10,2) DEFAULT 0,
                total_saidas   DECIMAL(10,2) DEFAULT 0,
                status         VARCHAR(20) NOT NULL DEFAULT 'fechado',
                abertura       DATETIME DEFAULT NOW(),
                fechamento     DATETIME,
                observacoes    TEXT,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS caixa_movimentacoes (
                id         INT NOT NULL AUTO_INCREMENT,
                tenant_id  INT NOT NULL DEFAULT 0,
                caixa_id   INT,
                tipo       VARCHAR(10) NOT NULL,
                valor      DECIMAL(10,2) NOT NULL,
                descricao  TEXT,
                created_at DATETIME DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sales (
                id              INT NOT NULL AUTO_INCREMENT,
                tenant_id       INT NOT NULL DEFAULT 0,
                caixa_id        INT,
                user_id         INT,
                numero_venda    VARCHAR(30),
                subtotal        DECIMAL(10,2) DEFAULT 0,
                desconto        DECIMAL(10,2) DEFAULT 0,
                taxa_servico    DECIMAL(10,2) DEFAULT 0,
                total           DECIMAL(10,2) NOT NULL DEFAULT 0,
                forma_pagamento VARCHAR(30) DEFAULT 'dinheiro',
                valor_pago      DECIMAL(10,2) DEFAULT 0,
                troco           DECIMAL(10,2) DEFAULT 0,
                status          VARCHAR(20) DEFAULT 'concluida',
                observacoes     TEXT,
                created_at      DATETIME DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        try { $pdo->exec("ALTER TABLE sales ADD INDEX idx_sales_tenant_status (tenant_id,status)"); } catch (PDOException $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sale_items (
                id             INT NOT NULL AUTO_INCREMENT,
                tenant_id      INT NOT NULL DEFAULT 0,
                sale_id        INT,
                product_id     INT,
                quantidade     DECIMAL(10,3) NOT NULL,
                preco_unitario DECIMAL(10,2) NOT NULL,
                desconto_item  DECIMAL(10,2) DEFAULT 0,
                subtotal       DECIMAL(10,2) DEFAULT 0,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
                id         INT NOT NULL AUTO_INCREMENT,
                tenant_id  INT NOT NULL DEFAULT 0,
                product_id INT,
                user_id    INT,
                tipo       VARCHAR(20) NOT NULL,
                quantidade DECIMAL(10,3) NOT NULL,
                motivo     TEXT,
                created_at DATETIME DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracoes (
                id        INT NOT NULL AUTO_INCREMENT,
                tenant_id INT NOT NULL DEFAULT 0,
                chave     VARCHAR(80) NOT NULL,
                valor     TEXT,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        try { $pdo->exec("ALTER TABLE configuracoes ADD UNIQUE INDEX idx_cfg_tenant_chave (tenant_id,chave)"); } catch (PDOException $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS config_plano (
                id                INT NOT NULL AUTO_INCREMENT,
                tenant_id         INT NOT NULL DEFAULT 0,
                rank_nome         VARCHAR(30) DEFAULT 'Basic',
                saldo_pago        TINYINT DEFAULT 1,
                data_vencimento   VARCHAR(20) DEFAULT '2099-12-31',
                limite_produtos   INT DEFAULT 100,
                limite_operadores INT DEFAULT 10,
                limite_gerentes   INT DEFAULT 5,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    // ---- Seeds idempotentes ----
    $ignore = $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';

    $hash = password_hash('super@2025', PASSWORD_DEFAULT);
    $pdo->prepare("$ignore superadmin_users (nome,email,senha) VALUES ('Super Admin','superadmin@pdvo.local',?)")->execute([$hash]);

    $pdo->exec("
        $ignore planos_sistema (id,nome,valor,limite_produtos,limite_operadores,limite_gerentes,descricao,destaque)
        VALUES
            (1,'BASIC', 10.00,  100,  1,  0,'Ideal para pequenos negócios',0),
            (2,'PRO',   25.00,   -1, 10,  1,'Para negócios em crescimento', 1),
            (3,'SUPER',109.99,   -1, -1, -1,'Ilimitado para grandes redes', 0)
    ");

    foreach ([
        'pix_chave','pix_nome','pix_cidade','email_notificacao','email_remetente',
        'indicacao_ativo','indicacao_recompensa_desc',
        'app_aviso_ativo','app_aviso_titulo','app_aviso_mensagem',
        'app_aviso_url_android','app_aviso_url_ios',
    ] as $k) {
        $pdo->prepare("$ignore superadmin_config (chave,valor) VALUES (?,'')")->execute([$k]);
    }
    // Valores padrão amigáveis para as novas chaves
    $pdo->prepare("UPDATE superadmin_config SET valor='Baixe nosso App!' WHERE chave='app_aviso_titulo' AND valor=''")->execute();
    $pdo->prepare("UPDATE superadmin_config SET valor='Tenha o sistema na palma da mão. Disponível para Android e iOS.' WHERE chave='app_aviso_mensagem' AND valor=''")->execute();

    // Tenant demo (SQLite: arquivo próprio | MySQL: seeded no banco único com tenant_id=0)
    if ($driver === 'sqlite') {
        $dataDir = dirname(__DIR__, 2) . '/.data';
        $demoDB  = $dataDir . '/pdvo.sqlite';
        $demoHash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("$ignore tenants (slug,razao_social,nome_resp,email,whatsapp,plano_id,plano_nome,status,data_ativacao,data_vencimento,db_path,admin_senha)
            VALUES ('demo','Empresa Demo','Administrador','admin@pdvo.local','',3,'SUPER','ativo',date('now'),'2099-12-31',?,?)")
            ->execute([$demoDB, $demoHash]);
    } else {
        // MySQL: cria tenant demo no banco único com tenant_id dinâmico
        $demoCheck = $pdo->prepare("SELECT id FROM tenants WHERE slug='demo' LIMIT 1");
        $demoCheck->execute();
        if (!$demoCheck->fetch()) {
            $demoHash = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO tenants (slug,razao_social,nome_resp,email,whatsapp,plano_id,plano_nome,status,data_ativacao,data_vencimento,db_path,admin_senha)
                VALUES ('demo','Empresa Demo','Administrador','admin@pdvo.local','',3,'SUPER','ativo',CURDATE(),'2099-12-31','',?)")
                ->execute([$demoHash]);
            $demoId = (int)$pdo->lastInsertId();
            _seedMysqlTenant($pdo, $demoId, 'Empresa Demo', 'admin@pdvo.local', 'Administrador', 'admin123', 'SUPER', -1, -1, -1);
        }
    }
}

/**
 * Semeia dados iniciais de um tenant no banco MySQL compartilhado.
 */
function _seedMysqlTenant(
    PDO    $pdo,
    int    $tenantId,
    string $nomeEmpresa,
    string $adminEmail,
    string $adminNome,
    string $adminSenha,
    string $planoNome,
    int    $limiteProd,
    int    $limiteOp,
    int    $limiteGer,
    string $vencimento = '2099-12-31'
): void {
    $hash = password_hash($adminSenha, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT IGNORE INTO users (tenant_id,nome,email,senha,perfil,ativo) VALUES (?,?,?,?,'admin',1)")
        ->execute([$tenantId, $adminNome, $adminEmail, $hash]);

    $pdo->prepare("INSERT IGNORE INTO config_plano (tenant_id,rank_nome,saldo_pago,data_vencimento,limite_produtos,limite_operadores,limite_gerentes) VALUES (?,?,1,?,?,?,?)")
        ->execute([$tenantId, $planoNome, $vencimento, $limiteProd, $limiteOp, $limiteGer]);

    $configs = [
        'nome_empresa'=>$nomeEmpresa,'cnpj'=>'','telefone'=>'','endereco'=>'',
        'logo_url'=>'','pix_chave'=>'','pix_nome'=>'','pix_cidade'=>'',
        'moeda_simbolo'=>'R$','taxa_servico'=>'0','estoque_minimo_alerta'=>'5',
        'impressora_largura'=>'80','tema'=>'light',
    ];
    $cfgSt = $pdo->prepare("INSERT IGNORE INTO configuracoes (tenant_id,chave,valor) VALUES (?,?,?)");
    foreach ($configs as $k => $v) $cfgSt->execute([$tenantId, $k, $v]);

    foreach (['Alimentos','Bebidas','Higiene','Limpeza'] as $cnome) {
        $pdo->prepare("INSERT IGNORE INTO categorias (tenant_id,nome) VALUES (?,?)")->execute([$tenantId, $cnome]);
    }

    $pdo->prepare("INSERT IGNORE INTO products (tenant_id,nome,codigo_barras,preco,custo,estoque,estoque_minimo,unidade,ativo) VALUES (?,?,?,?,?,?,?,?,1)")
        ->execute([$tenantId,'Produto Exemplo','7891234567890',10.00,6.00,50,5,'un']);
}

function gerarSlug(string $text, PDO $masterPdo): string {
    $from = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç','ñ',
             'Á','À','Ã','Â','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü','Ç','Ñ'];
    $to   = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n',
             'a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
    $text = str_replace($from, $to, $text);
    $slug = trim(substr(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($text))), 0, 20), '_');
    if (!$slug) $slug = 'empresa';

    $base = $slug; $count = 0;
    while (true) {
        $stmtCheck = $masterPdo->prepare("SELECT COUNT(*) FROM tenants WHERE slug=?");
        $stmtCheck->execute([$slug]);
        if (!(int)$stmtCheck->fetchColumn()) break;
        $slug = $base . '_' . (++$count);
    }
    return $slug;
}

function ativarTenant(int $pedidoId, PDO $masterPdo): array {
    $stmt = $masterPdo->prepare("SELECT * FROM pedidos_ativacao WHERE id=?");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch();
    if (!$pedido) return ['ok' => false, 'error' => 'Pedido não encontrado.'];

    $slug   = $pedido['tenant_slug'] ?: gerarSlug($pedido['razao_social'], $masterPdo);
    $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
    $now    = masterNowSql($driver);

    $planoStmt = $masterPdo->prepare("SELECT * FROM planos_sistema WHERE id=?");
    $planoStmt->execute([$pedido['plano_id']]);
    $plano = $planoStmt->fetch();

    $vencimento = date('Y-m-d', strtotime('+30 days'));

    if ($driver === 'mysql') {
        // === MYSQL: banco único ===
        $existStmt = $masterPdo->prepare("SELECT id FROM tenants WHERE email=?");
        $existStmt->execute([$pedido['email']]);
        $existTenant = $existStmt->fetch();

        if ($existTenant) {
            $tenantId = (int)$existTenant['id'];
            $masterPdo->prepare("UPDATE tenants SET slug=?,razao_social=?,nome_resp=?,whatsapp=?,plano_id=?,plano_nome=?,status='ativo',data_ativacao=CURDATE(),data_vencimento=?,db_path='' WHERE id=?")
                ->execute([$slug,$pedido['razao_social'],$pedido['nome'],$pedido['whatsapp'],$pedido['plano_id'],$pedido['plano_nome'],$vencimento,$tenantId]);
        } else {
            $masterPdo->prepare("INSERT INTO tenants (slug,razao_social,nome_resp,email,whatsapp,plano_id,plano_nome,status,data_ativacao,data_vencimento,db_path) VALUES (?,?,?,?,?,?,?,'ativo',CURDATE(),?,'')")
                ->execute([$slug,$pedido['razao_social'],$pedido['nome'],$pedido['email'],$pedido['whatsapp'],$pedido['plano_id'],$pedido['plano_nome'],$vencimento]);
            $tenantId = (int)$masterPdo->lastInsertId();
        }

        _seedMysqlTenant(
            $masterPdo, $tenantId,
            $pedido['razao_social'], $pedido['email'], $pedido['nome'], $pedido['email'],
            $pedido['plano_nome'],
            $plano['limite_produtos']   ?? 100,
            $plano['limite_operadores'] ?? 1,
            $plano['limite_gerentes']   ?? 0,
            $vencimento
        );

        // Atualiza config_plano com a data de vencimento correta
        $masterPdo->prepare("UPDATE config_plano SET data_vencimento=?,limite_produtos=?,limite_operadores=?,limite_gerentes=? WHERE tenant_id=?")
            ->execute([$vencimento,$plano['limite_produtos']??100,$plano['limite_operadores']??1,$plano['limite_gerentes']??0,$tenantId]);

    } else {
        // === SQLITE: arquivo por tenant ===
        $dataDir = dirname(__DIR__, 2) . '/.data';
        $dbPath  = $dataDir . '/tenant_' . $slug . '.sqlite';

        $tenantPdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $tenantPdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        require_once __DIR__ . '/init_sqlite.php';
        pdvoInitSqlite($tenantPdo);

        $adminHash = password_hash($pedido['email'], PASSWORD_DEFAULT);
        $tenantPdo->prepare("INSERT OR REPLACE INTO users (tenant_id,nome,email,senha,perfil,ativo) VALUES (0,?,?,?,'admin',1)")
            ->execute([$pedido['nome'],$pedido['email'],$adminHash]);

        $configs = ['nome_empresa'=>$pedido['razao_social'],'pix_chave'=>'','pix_nome'=>'','pix_cidade'=>''];
        $cfgStmt = $tenantPdo->prepare("INSERT OR REPLACE INTO configuracoes (tenant_id,chave,valor) VALUES (0,?,?)");
        foreach ($configs as $k => $v) $cfgStmt->execute([$k, $v]);

        $tenantPdo->prepare("INSERT OR REPLACE INTO config_plano (id,tenant_id,rank_nome,saldo_pago,data_vencimento,limite_produtos,limite_operadores,limite_gerentes) VALUES (1,0,?,1,date('now','+30 days'),?,?,?)")
            ->execute([$pedido['plano_nome'],$plano['limite_produtos']??100,$plano['limite_operadores']??1,$plano['limite_gerentes']??0]);

        $existStmt = $masterPdo->prepare("SELECT id FROM tenants WHERE email=?");
        $existStmt->execute([$pedido['email']]);
        $existTenant = $existStmt->fetch();

        if ($existTenant) {
            $masterPdo->prepare("UPDATE tenants SET slug=?,razao_social=?,nome_resp=?,whatsapp=?,plano_id=?,plano_nome=?,status='ativo',data_ativacao=date('now'),data_vencimento=?,db_path=? WHERE email=?")
                ->execute([$slug,$pedido['razao_social'],$pedido['nome'],$pedido['whatsapp'],$pedido['plano_id'],$pedido['plano_nome'],$vencimento,$dbPath,$pedido['email']]);
        } else {
            $masterPdo->prepare("INSERT INTO tenants (slug,razao_social,nome_resp,email,whatsapp,plano_id,plano_nome,status,data_ativacao,data_vencimento,db_path) VALUES (?,?,?,?,?,?,?,'ativo',date('now'),?,?)")
                ->execute([$slug,$pedido['razao_social'],$pedido['nome'],$pedido['email'],$pedido['whatsapp'],$pedido['plano_id'],$pedido['plano_nome'],$vencimento,$dbPath]);
        }
    }

    $masterPdo->prepare("UPDATE pedidos_ativacao SET status='confirmado', updated_at=({$now}), tenant_slug=? WHERE id=?")
        ->execute([$slug, $pedidoId]);

    return ['ok' => true, 'slug' => $slug, 'senha_inicial' => $pedido['email']];
}

function getSuperadminConfig(PDO $masterPdo): array {
    try {
        $rows = $masterPdo->query("SELECT chave, valor FROM superadmin_config")->fetchAll();
        $cfg  = [];
        foreach ($rows as $r) $cfg[$r['chave']] = $r['valor'];
        return $cfg;
    } catch (Exception $e) {
        return [];
    }
}
