<?php
// GET  quotes.php -> lista os orçamentos da conta logada   => { ok, quotes: [ {id, status, mensagem, created_at} ] }
// POST quotes.php { nome, whatsapp, mensagem } -> grava um orçamento => { ok, id }
// Tabela: quotes (banco.sql). Mesma pasta de: index.html, db.php, login.php...
header('Content-Type: application/json; charset=utf-8');

function responder(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function erro(string $msg, ?string $campo = null, int $status = 422): void {
    $r = ['ok' => false, 'error' => $msg];
    if ($campo) $r['field'] = $campo;
    responder($r, $status);
}

$dbFile = __DIR__ . '/db.php';
if (!is_file($dbFile)) {
    error_log('quotes.php: db.php não encontrado em ' . $dbFile);
    erro('Configuração do servidor incompleta.', null, 500);
}
require $dbFile;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$userId = (int)($_SESSION['user_id'] ?? 0);

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET' && $method !== 'POST') {
    erro('Método não permitido.', null, 405);
}

try {
    $pdo = db();
    // O index.html lê created_at como UTC
    $pdo->exec("SET time_zone = '+00:00'");

    if ($method === 'GET') {
        if ($userId <= 0) erro('Faça login para ver seus orçamentos.', null, 401);

        $st = $pdo->prepare(
            'SELECT id, status, mensagem, created_at
               FROM quotes
              WHERE user_id = ?
              ORDER BY id DESC
              LIMIT 50'
        );
        $st->execute([$userId]);
        responder(['ok' => true, 'quotes' => $st->fetchAll()]);
    }

    // POST
    $in = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($in)) erro('Dados inválidos.', null, 400);

    $nome     = trim((string)($in['nome'] ?? ''));
    $whatsapp = preg_replace('/\D+/', '', (string)($in['whatsapp'] ?? ''));
    $mensagem = trim((string)($in['mensagem'] ?? ''));

    if ($nome === '' || mb_strlen($nome) > 120)           erro('Informe seu nome.', 'nome');
    if (strlen($whatsapp) < 10 || strlen($whatsapp) > 13) erro('WhatsApp inválido.', 'whatsapp');
    if ($mensagem === '' || mb_strlen($mensagem) > 2000)  erro('Descreva o que você precisa.', 'mensagem');

    $st = $pdo->prepare(
        'INSERT INTO quotes (user_id, nome, whatsapp, mensagem) VALUES (?, ?, ?, ?)'
    );
    $st->execute([$userId > 0 ? $userId : null, $nome, $whatsapp, $mensagem]);

    responder(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
} catch (PDOException $e) {
    error_log('quotes.php PDO: ' . $e->getMessage());
    erro('Erro ao acessar o banco de dados.', null, 500);
}