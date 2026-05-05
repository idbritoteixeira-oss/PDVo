<?php
// ============================================================
// PDVo - Inicialização e Migração do Banco SQLite por Tenant
// Cria as tabelas na 1ª execução e migra bancos antigos.
// ============================================================

function pdvoInitSqlite(PDO $pdo): void {
    $check = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();

    if ($check === 0) {
        // Banco novo — cria tudo do zero
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id     INT NOT NULL DEFAULT 0,
                nome          TEXT NOT NULL,
                email         TEXT NOT NULL,
                senha         TEXT NOT NULL,
                perfil        TEXT NOT NULL DEFAULT 'operador',
                ativo         INTEGER NOT NULL DEFAULT 1,
                ultimo_login  TEXT,
                created_at    TEXT DEFAULT (datetime('now','localtime'))
            );
            CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email_t ON users(tenant_id, email);

            CREATE TABLE IF NOT EXISTS categorias (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id  INT NOT NULL DEFAULT 0,
                nome       TEXT NOT NULL,
                descricao  TEXT,
                created_at TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS subcategorias (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id    INT NOT NULL DEFAULT 0,
                nome         TEXT NOT NULL,
                descricao    TEXT,
                categoria_id INTEGER REFERENCES categorias(id) ON DELETE SET NULL,
                created_at   TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS products (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id      INT NOT NULL DEFAULT 0,
                nome           TEXT NOT NULL,
                descricao      TEXT,
                codigo_barras  TEXT,
                preco          REAL NOT NULL DEFAULT 0,
                custo          REAL DEFAULT 0,
                estoque        REAL NOT NULL DEFAULT 0,
                estoque_minimo REAL DEFAULT 5,
                unidade        TEXT DEFAULT 'un',
                categoria_id   INTEGER REFERENCES categorias(id) ON DELETE SET NULL,
                ativo          INTEGER NOT NULL DEFAULT 1,
                imagem_url     TEXT,
                created_at     TEXT DEFAULT (datetime('now','localtime')),
                updated_at     TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS caixas (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id      INT NOT NULL DEFAULT 0,
                user_id        INTEGER REFERENCES users(id),
                fundo_caixa    REAL DEFAULT 0,
                total_vendas   REAL DEFAULT 0,
                total_entradas REAL DEFAULT 0,
                total_saidas   REAL DEFAULT 0,
                status         TEXT NOT NULL DEFAULT 'fechado',
                abertura       TEXT DEFAULT (datetime('now','localtime')),
                fechamento     TEXT,
                observacoes    TEXT
            );

            CREATE TABLE IF NOT EXISTS caixa_movimentacoes (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id   INT NOT NULL DEFAULT 0,
                caixa_id    INTEGER REFERENCES caixas(id),
                tipo        TEXT NOT NULL,
                valor       REAL NOT NULL,
                descricao   TEXT,
                created_at  TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS sales (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id        INT NOT NULL DEFAULT 0,
                caixa_id         INTEGER REFERENCES caixas(id),
                user_id          INTEGER REFERENCES users(id),
                numero_venda     TEXT,
                subtotal         REAL DEFAULT 0,
                desconto         REAL DEFAULT 0,
                taxa_servico     REAL DEFAULT 0,
                total            REAL NOT NULL DEFAULT 0,
                forma_pagamento  TEXT DEFAULT 'dinheiro',
                valor_pago       REAL DEFAULT 0,
                troco            REAL DEFAULT 0,
                status           TEXT DEFAULT 'concluida',
                observacoes      TEXT,
                created_at       TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS sale_items (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id      INT NOT NULL DEFAULT 0,
                sale_id        INTEGER REFERENCES sales(id),
                product_id     INTEGER REFERENCES products(id),
                quantidade     REAL NOT NULL,
                preco_unitario REAL NOT NULL,
                desconto_item  REAL DEFAULT 0,
                subtotal       REAL DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id   INT NOT NULL DEFAULT 0,
                product_id  INTEGER REFERENCES products(id),
                tipo        TEXT NOT NULL,
                quantidade  REAL NOT NULL,
                motivo      TEXT,
                user_id     INTEGER REFERENCES users(id),
                created_at  TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS configuracoes (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INT NOT NULL DEFAULT 0,
                chave     TEXT NOT NULL,
                valor     TEXT,
                UNIQUE(tenant_id, chave)
            );

            CREATE TABLE IF NOT EXISTS planos (
                id                INTEGER PRIMARY KEY AUTOINCREMENT,
                nome              TEXT NOT NULL,
                valor             REAL NOT NULL,
                limite_produtos   INTEGER DEFAULT 5,
                limite_operadores INTEGER DEFAULT 1,
                limite_gerentes   INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS config_plano (
                id                INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id         INT NOT NULL DEFAULT 0,
                rank_nome         TEXT DEFAULT 'Basic',
                saldo_pago        INTEGER DEFAULT 1,
                data_vencimento   TEXT DEFAULT '2099-12-31',
                limite_produtos   INTEGER DEFAULT 100,
                limite_operadores INTEGER DEFAULT 10,
                limite_gerentes   INTEGER DEFAULT 5
            );

            CREATE TRIGGER IF NOT EXISTS trg_decrement_stock
            AFTER INSERT ON sale_items
            BEGIN
                UPDATE products SET estoque = estoque - NEW.quantidade
                WHERE id = NEW.product_id AND tenant_id = NEW.tenant_id;
            END;

            CREATE TRIGGER IF NOT EXISTS trg_restore_stock_on_cancel
            AFTER UPDATE OF status ON sales
            WHEN NEW.status = 'cancelada' AND OLD.status != 'cancelada'
            BEGIN
                UPDATE products SET estoque = estoque + si.quantidade
                FROM sale_items si WHERE si.sale_id = NEW.id AND si.product_id = products.id;
            END;
        ");

        _seedSqliteTenant($pdo, 0);
    } else {
        // Banco existente — migração para adicionar tenant_id se faltar
        _migrateTenantId($pdo);
    }
}

/**
 * Semeia dados padrão para um tenant (SQLite).
 */
function _seedSqliteTenant(PDO $pdo, int $tid): void {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT OR IGNORE INTO users (tenant_id,nome,email,senha,perfil,ativo) VALUES (?,?,?,?,'admin',1)")
        ->execute([$tid, 'Administrador', 'admin@pdvo.local', $hash]);

    $pdo->exec("
        INSERT OR IGNORE INTO planos (id,nome,valor,limite_produtos,limite_operadores,limite_gerentes)
        VALUES (1,'BASIC',10.00,100,1,0),(2,'PRO',25.00,-1,10,1),(3,'SUPER',109.99,-1,-1,-1)
    ");

    $pdo->prepare("INSERT OR IGNORE INTO config_plano (id,tenant_id,rank_nome,saldo_pago,data_vencimento,limite_produtos,limite_operadores,limite_gerentes) VALUES (1,?,'Super',1,'2099-12-31',-1,-1,-1)")
        ->execute([$tid]);

    foreach ([[1,'Alimentos'],[2,'Bebidas'],[3,'Higiene'],[4,'Limpeza']] as [$cid,$cnome]) {
        $pdo->prepare("INSERT OR IGNORE INTO categorias (id,tenant_id,nome) VALUES (?,?,?)")->execute([$cid,$tid,$cnome]);
    }

    $configs = [
        'nome_empresa'=>'Minha Empresa','cnpj'=>'','telefone'=>'','endereco'=>'',
        'logo_url'=>'','pix_chave'=>'','pix_nome'=>'','pix_cidade'=>'',
        'moeda_simbolo'=>'R$','taxa_servico'=>'0','estoque_minimo_alerta'=>'5',
        'impressora_largura'=>'80','tema'=>'light',
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO configuracoes (tenant_id,chave,valor) VALUES (?,?,?)");
    foreach ($configs as $k => $v) $stmt->execute([$tid, $k, $v]);

    $pdo->prepare("INSERT OR IGNORE INTO products (id,tenant_id,nome,codigo_barras,preco,custo,estoque,estoque_minimo,unidade,categoria_id,ativo)
        VALUES (1,?,?,?,?,?,?,?,?,?,1)")->execute([$tid,'Produto Exemplo','7891234567890',10.00,6.00,50,5,'un',1]);
}

/**
 * Adiciona coluna tenant_id a tabelas existentes que ainda não possuem.
 */
function _migrateTenantId(PDO $pdo): void {
    $tables = [
        'users','categorias','subcategorias','products','caixas','caixa_movimentacoes',
        'sales','sale_items','estoque_movimentacoes','configuracoes','config_plano'
    ];
    foreach ($tables as $t) {
        try {
            $rows = $pdo->query("PRAGMA table_info({$t})")->fetchAll(PDO::FETCH_ASSOC);
            $has  = array_filter($rows, fn($c) => $c['name'] === 'tenant_id');
            if (!$has) {
                $pdo->exec("ALTER TABLE {$t} ADD COLUMN tenant_id INT NOT NULL DEFAULT 0");
            }
        } catch (PDOException $e) { /* tabela pode não existir ainda */ }
    }
    // Garante tabela caixa com coluna observacoes
    try {
        $rows = $pdo->query("PRAGMA table_info(caixas)")->fetchAll(PDO::FETCH_ASSOC);
        $has  = array_filter($rows, fn($c) => $c['name'] === 'observacoes');
        if (!$has) $pdo->exec("ALTER TABLE caixas ADD COLUMN observacoes TEXT");
    } catch (PDOException $e) {}
}
