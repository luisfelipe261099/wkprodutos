<?php
// Inicia a sessÃ£o
require_once 'includes/session_bootstrap.php';

// Desfaz todas as variÃ¡veis de sessÃ£o
$_SESSION = array();

// Destroi a sessÃ£o.
session_destroy();
kw_clear_auth_cookie();

// Redireciona para a pÃ¡gina de login
header("location: index.php");
exit;
?>
