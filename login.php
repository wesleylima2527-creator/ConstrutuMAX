<?php
// POST login.php  { email, senha }
// Mesma pasta de: index.html, index.css, db.php, register.php
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
    error_log('login.php: db.php não encontrado em ' . $dbFile);
    erro('Configuração do servidor incompleta.', null, 500);
}
require $dbFile;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    erro('Método não permitido.', null, 405);
}

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) erro('Dados inválidos.', null, 400);

$email = strtolower(trim((string)($in['email'] ?? '')));
$senha = (string)($in['senha'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) erro('Informe um e-mail válido.', 'email');
if ($senha === '')                              erro('Informe sua senha.', 'senha');

try {
    $st = db()->prepare('SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();
} catch (PDOException $e) {
    error_log('login.php PDO: ' . $e->getMessage());
    erro('Erro ao consultar o banco de dados.', null, 500);
}

// Mensagem igual para e-mail inexistente e senha errada (não revela quais e-mails existem)
if (!$u || !password_verify($senha, $u['senha_hash'])) {
    erro('E-mail ou senha incorretos.', null, 401);
}

// Atualiza o hash se o algoritmo padrão do PHP mudou
if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) {
    try {
        $up = db()->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
        $up->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
    } catch (PDOException $e) {
        error_log('login.php rehash: ' . $e->getMessage());
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$u['id'];

responder(['ok' => true, 'user' => [
    'id'    => (int)$u['id'],
    'nome'  => $u['nome'],
    'email' => $u['email'],
]]);