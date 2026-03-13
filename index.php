<?php
// Inicia a sessÃ£o para verificar se o usuÃ¡rio estÃ¡ logado
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Se estiver logado, redireciona para o dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Se nÃ£o estiver logado, redireciona para a pÃ¡gina de login
    header("Location: login.php");
    exit();
}
?>

