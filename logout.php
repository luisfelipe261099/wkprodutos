<?php
// Inicia a sessão
require_once __DIR__ . '/includes/session_bootstrap.php';

// Desfaz todas as variáveis de sessão
$_SESSION = array();

// Destroi a sessão.
session_destroy();
kw_clear_auth_cookie();

// Redireciona para a página de login
header("location: index.php");
exit;
?>
