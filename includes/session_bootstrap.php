<?php

if (!function_exists('kw_auth_secret')) {
    function kw_auth_secret(): string
    {
        $secret = getenv('AUTH_COOKIE_SECRET');
        if ($secret === false || $secret === '') {
            $secret = 'kw-default-secret-change-in-vercel';
        }
        return $secret;
    }
}

if (!function_exists('kw_is_https')) {
    function kw_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        return false;
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = kw_is_https();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('KWSESSID');
    session_start();
}

if (!function_exists('kw_base64url_encode')) {
    function kw_base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('kw_base64url_decode')) {
    function kw_base64url_decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}

if (!function_exists('kw_issue_auth_cookie')) {
    function kw_issue_auth_cookie(int $id, string $nome, string $nivel, int $ttlSeconds = 86400 * 7): void
    {
        $payload = [
            'id' => $id,
            'nome' => $nome,
            'nivel' => $nivel,
            'exp' => time() + $ttlSeconds,
        ];

        $json = json_encode($payload);
        if ($json === false) {
            return;
        }

        $data = kw_base64url_encode($json);
        $sig = hash_hmac('sha256', $data, kw_auth_secret());
        $cookieValue = $data . '.' . $sig;

        setcookie('kw_auth', $cookieValue, [
            'expires' => $payload['exp'],
            'path' => '/',
            'domain' => '',
            'secure' => kw_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('kw_clear_auth_cookie')) {
    function kw_clear_auth_cookie(): void
    {
        setcookie('kw_auth', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => kw_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('kw_restore_session_from_cookie')) {
    function kw_restore_session_from_cookie(): void
    {
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            return;
        }

        if (empty($_COOKIE['kw_auth']) || !is_string($_COOKIE['kw_auth'])) {
            return;
        }

        $parts = explode('.', $_COOKIE['kw_auth'], 2);
        if (count($parts) !== 2) {
            return;
        }

        [$data, $sig] = $parts;
        $expectedSig = hash_hmac('sha256', $data, kw_auth_secret());
        if (!hash_equals($expectedSig, $sig)) {
            return;
        }

        $json = kw_base64url_decode($data);
        if ($json === '') {
            return;
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return;
        }

        $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
        if ($exp <= time()) {
            kw_clear_auth_cookie();
            return;
        }

        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = (int)($payload['id'] ?? 0);
        $_SESSION['nome'] = (string)($payload['nome'] ?? '');
        $_SESSION['nivel_acesso'] = (string)($payload['nivel'] ?? 'usuario');
    }
}

kw_restore_session_from_cookie();
