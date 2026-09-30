<?php
// Conexão com o banco (MySQL/MariaDB). Preencha com os dados do seu painel de hospedagem.
const DB_HOST = 'sql211.iceiy.com';
const DB_NAME = 'icei_43046926_costrutoriaMAX';
const DB_USER = 'icei_43046926';
const DB_PASS = 'Wesley998877';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}