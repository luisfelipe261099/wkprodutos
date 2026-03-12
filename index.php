<?php
// Inicia a sessão para verificar se o usuário está logado
session_start();

// Verifica se o usuário está logado
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Se estiver logado, redireciona para o dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login.php");
    exit();
}
?>
