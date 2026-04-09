<?php

// GARANTIR UTF-8 EM TODOS OS HEADERS HTTP
if (headers_sent() === false) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

// Le variaveis de ambiente com fallback para desenvolvimento local.
function env_or_default(string $key, string $default): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function env_to_bool(string $key, bool $default = false): bool {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    $normalized = strtolower(trim($value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

$dbHost = env_or_default('DB_HOST', 'localhost');
$dbUser = env_or_default('DB_USER', 'u182607388_karla_wollinge');
$dbPass = env_or_default('DB_PASSWORD', 'T3cn0l0g1a@');
$dbName = env_or_default('DB_NAME', 'u182607388_karla_wollinge');
$dbPort = (int) env_or_default('DB_PORT', '3306');

$appEnv = env_or_default('APP_ENV', 'production');
$isProduction = $appEnv === 'production';

// Suporta DATABASE_URL no formato: mysql://user:pass@host:3306/dbname
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $scheme = $parts['scheme'] ?? '';
        if ($scheme === 'mysql' || $scheme === 'mariadb') {
            $dbHost = $parts['host'] ?? $dbHost;
            $dbUser = isset($parts['user']) ? rawurldecode($parts['user']) : $dbUser;
            $dbPass = isset($parts['pass']) ? rawurldecode($parts['pass']) : $dbPass;
            $dbPort = isset($parts['port']) ? (int)$parts['port'] : $dbPort;
            if (!empty($parts['path'])) {
                $dbName = ltrim($parts['path'], '/');
            }
        }
    }
}

// Em producao, nunca usa localhost para evitar erro de socket em serverless.
if ($isProduction && ($dbHost === 'localhost' || $dbHost === '127.0.0.1')) {
    http_response_code(500);
    die('ERRO: Configure DB_HOST/DB_PORT/DB_USER/DB_PASSWORD/DB_NAME na Vercel.');
}

// Conexao com suporte a TLS (necessario para TiDB Cloud com endpoint publico).
$conn = mysqli_init();
if ($conn === false) {
    http_response_code(500);
    die('ERRO: Falha ao inicializar cliente MySQL.');
}

$useTls = env_to_bool('DB_SSL', true);
$verifyTls = env_to_bool('DB_SSL_VERIFY', false);
$sslCaPath = getenv('DB_SSL_CA') ?: null;

if ($useTls) {
    if (!$verifyTls) {
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    }
    mysqli_ssl_set($conn, null, null, $sslCaPath, null, null);
}

$connectFlags = 0;
if ($useTls) {
    $connectFlags |= MYSQLI_CLIENT_SSL;
}

$connected = @mysqli_real_connect($conn, $dbHost, $dbUser, $dbPass, $dbName, $dbPort, null, $connectFlags);

if (!$connected) {
    $connectError = mysqli_connect_error();
    $connectErrno = mysqli_connect_errno();
    if ($isProduction) {
        http_response_code(500);
        die('ERRO: Nao foi possivel conectar ao banco de dados.');
    }
    die('ERRO: Nao foi possivel conectar ao banco de dados. [' . $connectErrno . '] ' . $connectError);
}

// CHARSET UTF-8MB4 - Garantir que todos os dados sejam salvos corretamente com acentos e caracteres especiais
$conn->set_charset('utf8mb4');
mysqli_query($conn, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

?>
