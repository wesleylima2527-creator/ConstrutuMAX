-- =====================================================================
-- Banco único da loja (MySQL / MariaDB)
-- Junta o antigo schema.sql (SQLite) e o pedidos.sql.
-- Rode no phpMyAdmin (aba SQL) dentro do banco já criado na hospedagem.
-- Nomes das tabelas de usuário seguem o que o login.php, register.php
-- e me.php já usam: usuarios / senha_hash.
-- =====================================================================

-- ---------- Usuários (login.php, register.php, me.php) ----------
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    whatsapp   VARCHAR(20)  NULL,
    senha_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Orçamentos (antiga tabela quotes) ----------
CREATE TABLE IF NOT EXISTS quotes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    nome       VARCHAR(120) NOT NULL,
    whatsapp   VARCHAR(20)  NOT NULL,
    mensagem   TEXT NOT NULL,
    status     VARCHAR(30)  NOT NULL DEFAULT 'novo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_quotes_user (user_id),
    CONSTRAINT fk_quotes_usuario
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Catálogo ----------
CREATE TABLE IF NOT EXISTS categories (
    slug VARCHAR(60)  NOT NULL PRIMARY KEY,
    nome VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id          VARCHAR(60)  NOT NULL PRIMARY KEY,
    category    VARCHAR(60)  NOT NULL,
    nome        VARCHAR(160) NOT NULL,
    descricao   TEXT NOT NULL,
    unidade     VARCHAR(20)  NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    imagem      VARCHAR(255) NULL,
    destaque    TINYINT(1)   NOT NULL DEFAULT 0,
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,
    KEY idx_products_category (category),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category) REFERENCES categories(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Pedidos (pedido.php) ----------
CREATE TABLE IF NOT EXISTS pedidos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NULL,
    nome            VARCHAR(120) NOT NULL,
    whatsapp        VARCHAR(20)  NOT NULL,
    mensagem        TEXT NULL,
    forma_pagamento ENUM('pix','cartao','boleto','faturado') NOT NULL,
    status          VARCHAR(30)  NOT NULL DEFAULT 'aguardando_pagamento',
    total_cents     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pedidos_usuario (usuario_id),
    KEY idx_pedidos_status (status),
    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedido_itens (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pedido_id   INT UNSIGNED NOT NULL,
    produto_id  VARCHAR(60)  NOT NULL,
    nome        VARCHAR(160) NOT NULL,
    unidade     VARCHAR(20)  NOT NULL,
    preco_cents INT UNSIGNED NOT NULL,
    quantidade  INT UNSIGNED NOT NULL,
    CONSTRAINT fk_itens_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;