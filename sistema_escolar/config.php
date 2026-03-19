<?php
// ============================================================
//  config.php — Configuração da conexão com o banco de dados
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // altere para o seu usuário MySQL
define('DB_PASS', '');            // altere para a sua senha MySQL
define('DB_NAME', 'sistema_escolar');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'SumárioPRO');
define('SITE_VERSION', '1.0.0');

// Fuso horário
date_default_timezone_set('Africa/Luanda');   // ajuste conforme necessário

// ----------------------------------------------------------------
// Cria a conexão PDO reutilizável
// ----------------------------------------------------------------
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ----------------------------------------------------------------
// Inicia sessão de forma segura
// ----------------------------------------------------------------
function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ----------------------------------------------------------------
// Verifica se o professor está autenticado; redireciona se não
// ----------------------------------------------------------------
function verificarAutenticacao(): void {
    iniciarSessao();
    if (empty($_SESSION['professor_id'])) {
        header('Location: /sistema_escolar/login.php');
        exit;
    }
}

// ----------------------------------------------------------------
// Retorna o professor logado como array
// ----------------------------------------------------------------
function professorLogado(): array {
    iniciarSessao();
    return [
        'id'    => $_SESSION['professor_id']  ?? 0,
        'nome'  => $_SESSION['professor_nome'] ?? '',
        'email' => $_SESSION['professor_email'] ?? '',
    ];
}

// ----------------------------------------------------------------
// Helper: sanitiza saída HTML
// ----------------------------------------------------------------
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------------
// Helper: resposta JSON para AJAX
// ----------------------------------------------------------------
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
