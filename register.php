<?php
// POST register.php  { nome, email, whatsapp (opcional), senha }
// Tudo na mesma pasta: index.html, index.css, db.php, register.php
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
    error_log('register.php: db.php não encontrado em ' . $dbFile);
    erro('Configuração do servidor incompleta.', null, 500);
}
require $dbFile;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    erro('Método não permitido.', null, 405);
}

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) erro('Dados inválidos.', null, 400);

$nome     = trim((string)($in['nome'] ?? ''));
$email    = strtolower(trim((string)($in['email'] ?? '')));
$whatsapp = preg_replace('/\D+/', '', (string)($in['whatsapp'] ?? ''));
$senha    = (string)($in['senha'] ?? '');

if ($nome === '' || mb_strlen($nome) > 120)     erro('Informe seu nome.', 'nome');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) erro('Informe um e-mail válido.', 'email');
if ($whatsapp !== '' && (strlen($whatsapp) < 10 || strlen($whatsapp) > 13))
                                                erro('WhatsApp inválido.', 'whatsapp');
if (strlen($senha) < 8)                         erro('A senha deve ter no mínimo 8 caracteres.', 'senha');

try {
    $pdo = db();

    $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) erro('Este e-mail já está cadastrado.', 'email', 409);

    $st = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, whatsapp, senha_hash) VALUES (?, ?, ?, ?)'
    );
    $st->execute([
        $nome,
        $email,
        $whatsapp === '' ? null : $whatsapp,
        password_hash($senha, PASSWORD_DEFAULT),
    ]);
    $id = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') {
        erro('Este e-mail já está cadastrado.', 'email', 409);
    }
    error_log('register.php PDO: ' . $e->getMessage());
    erro('Erro ao salvar no banco de dados.', null, 500);
}

// Deixa o usuário logado após criar a conta
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
session_regenerate_id(true);
$_SESSION['user_id'] = $id;

responder(['ok' => true, 'user' => ['id' => $id, 'nome' => $nome, 'email' => $email]], 201);