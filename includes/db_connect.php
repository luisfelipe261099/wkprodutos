<?php

// Le variaveis de ambiente com fallback para desenvolvimento local.
function env_or_default(string $key, string $default): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

$dbHost = env_or_default('DB_HOST', 'localhost');
$dbUser = env_or_default('DB_USER', 'u182607388_karla_wollinge');
$dbPass = env_or_default('DB_PASSWORD', 'T3cn0l0g1a@');
$dbName = env_or_default('DB_NAME', 'u182607388_karla_wollinge');
$dbPort = (int) env_or_default('DB_PORT', '3306');

// Suporta DATABASE_URL no formato: mysql://user:pass@host:3306/dbname
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $scheme = $parts['scheme'] ?? '';
        if ($scheme === 'mysql' || $scheme === 'mariadb') {
            $dbHost = $parts['host'] ?? $dbHost;
            $dbUser = $parts['user'] ?? $dbUser;
            $dbPass = $parts['pass'] ?? $dbPass;
            $dbPort = isset($parts['port']) ? (int)$parts['port'] : $dbPort;
            if (!empty($parts['path'])) {
                $dbName = ltrim($parts['path'], '/');
            }
        }
    }
}

// Tenta estabelecer a conexao com o banco de dados MySQL.
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if ($conn->connect_error) {
    // Em producao, evita expor detalhes sensiveis da conexao.
    if (env_or_default('APP_ENV', 'production') === 'production') {
        http_response_code(500);
        die('ERRO: Nao foi possivel conectar ao banco de dados.');
    }
    die('ERRO: Nao foi possivel conectar ao banco de dados. ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

?>