<?php
// Força o navegador a não usar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redireciona para criar_orcamento.php com timestamp
$timestamp = time();
header("Location: criar_orcamento.php?v=$timestamp");
exit;
?>

