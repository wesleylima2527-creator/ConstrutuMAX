<?php
// GET me.php -> devolve o usuário logado (sessão), usado pelo index.html ao carregar a página
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$id = (int)($_SESSION['user_id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'user' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $st = db()->prepare('SELECT id, nome, email FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $u = $st->fetch();
} catch (PDOException $e) {
    error_log('me.php PDO: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro ao consultar o banco de dados.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$u) {
    // usuário da sessão não existe mais
    $_SESSION = [];
    session_destroy();
    echo json_encode(['ok' => false, 'user' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'user' => [
    'id'    => (int)$u['id'],
    'nome'  => $u['nome'],
    'email' => $u['email'],
]], JSON_UNESCAPED_UNICODE);