<?php
// Incluir o arquivo de envio de email
require_once 'send_email.php';

// Limpar o arquivo de log para este teste
file_put_contents('email_log.txt', '');

// Registrar início do teste
logMessage("=== INÍCIO DO TESTE DE EMAIL ===");
logMessage("Data e hora: " . date('Y-m-d H:i:s'));
logMessage("Servidor: " . $_SERVER['SERVER_NAME']);

// Testar conexão com o servidor SMTP
logMessage("Testando conexão com o servidor SMTP...");
$host = 'smtp.hostinger.com';
$port = 587;
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if (!$socket) {
    logMessage("ERRO: Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
} else {
    logMessage("Conexão com servidor SMTP bem-sucedida");
    fclose($socket);
}

// Testar envio de email
logMessage("Testando envio de email...");
$test_subject = "Teste de Email - " . date('Y-m-d H:i:s');
$test_body = "
<html>
<body>
    <h2>Teste de Email</h2>
    <p>Este é um email de teste enviado em: " . date('Y-m-d H:i:s') . "</p>
    <p>Se você está vendo esta mensagem, o sistema de email está funcionando.</p>
</body>
</html>";

$result = sendEmail(
    $test_subject,
    $test_body,
    'teste@exemplo.com',
    'Teste Automático',
    true
);

// Exibir resultado
echo "<h1>Teste de Envio de Email</h1>";
echo "<p>Resultado: " . ($result ? "<span style='color:green'>Email enviado com sucesso</span>" : "<span style='color:red'>Falha ao enviar email</span>") . "</p>";
echo "<h2>Log de Envio</h2>";
echo "<pre style='background-color:#f5f5f5; padding:15px; border:1px solid #ddd; max-height:400px; overflow:auto;'>";
echo htmlspecialchars(file_get_contents('email_log.txt'));
echo "</pre>";

// Testar envio alternativo
if (!$result) {
    logMessage("Tentando método alternativo de envio...");
    $fallback_result = sendEmailFallback(
        $test_subject,
        $test_body,
        'teste@exemplo.com',
        'Teste Automático'
    );
    
    echo "<h2>Teste de Método Alternativo</h2>";
    echo "<p>Resultado: " . ($fallback_result ? "<span style='color:green'>Email enviado com sucesso via método alternativo</span>" : "<span style='color:red'>Falha ao enviar email via método alternativo</span>") . "</p>";
}

// Exibir informações do servidor
echo "<h2>Informações do Servidor</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>sendmail_path:</strong> " . ini_get('sendmail_path') . "</li>";
echo "<li><strong>SMTP:</strong> " . ini_get('SMTP') . "</li>";
echo "<li><strong>smtp_port:</strong> " . ini_get('smtp_port') . "</li>";
echo "</ul>";

// Exibir link para voltar
echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?>
