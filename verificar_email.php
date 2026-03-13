<?php
// VerificaÃ§Ã£o rÃ¡pida de email
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado e Ã© admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_SESSION["nivel_acesso"]) || $_SESSION["nivel_acesso"] !== "admin") {
    echo "Acesso negado. Apenas administradores podem acessar esta pÃ¡gina.";
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

echo "<h1>ðŸ” VerificaÃ§Ã£o de Email - LFM Tecnologia</h1>";

try {
    echo "<h2>1. Testando conexÃ£o SMTP...</h2>";
    
    $mail = new PHPMailer(true);
    
    // ConfiguraÃ§Ãµes exatas do Hostinger
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'desenvolvimento@lfmtecnologia.com';
    $mail->Password = 'T3cn0l0g1a@';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    
    echo "âœ… ConfiguraÃ§Ãµes SMTP definidas<br>";
    
    // Teste de conexÃ£o simples
    echo "<h2>2. Testando autenticaÃ§Ã£o...</h2>";
    
    $mail->setFrom('desenvolvimento@lfmtecnologia.com', 'LFM Tecnologia - Teste');
    $mail->addAddress('desenvolvimento@lfmtecnologia.com', 'Teste Interno');
    
    $mail->isHTML(true);
    $mail->Subject = 'Teste de ConfiguraÃ§Ã£o - ' . date('d/m/Y H:i:s');
    $mail->Body = '<h2>Teste de Email</h2><p>Este Ã© um teste de configuraÃ§Ã£o do sistema de email.</p><p>Data/Hora: ' . date('d/m/Y H:i:s') . '</p>';
    $mail->AltBody = 'Teste de Email - Data/Hora: ' . date('d/m/Y H:i:s');
    
    echo "âœ… Email configurado<br>";
    
    echo "<h2>3. Enviando email de teste...</h2>";
    
    if ($mail->send()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>ðŸŽ‰ SUCESSO!</h3>";
        echo "<p>Email enviado com sucesso!</p>";
        echo "<p><strong>ConfiguraÃ§Ãµes funcionando corretamente:</strong></p>";
        echo "<ul>";
        echo "<li>Host: smtp.hostinger.com</li>";
        echo "<li>Porta: 465 (SSL)</li>";
        echo "<li>UsuÃ¡rio: desenvolvimento@lfmtecnologia.com</li>";
        echo "<li>AutenticaÃ§Ã£o: OK</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h2>âœ… Sistema de email estÃ¡ funcionando!</h2>";
        echo "<p>Agora vocÃª pode usar o botÃ£o de enviar email nos orÃ§amentos.</p>";
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>âŒ FALHA NO ENVIO</h3>";
        echo "<p>O email nÃ£o foi enviado, mas nÃ£o houve exceÃ§Ã£o.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>âŒ ERRO</h3>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // DiagnÃ³sticos especÃ­ficos
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<p><strong>PossÃ­vel causa:</strong> Firewall bloqueando a porta 465</p>";
        echo "<p><strong>SoluÃ§Ã£o:</strong> Verifique se a porta 465 estÃ¡ aberta no servidor</p>";
    } elseif (strpos($e->getMessage(), 'Authentication failed') !== false) {
        echo "<p><strong>PossÃ­vel causa:</strong> Credenciais incorretas</p>";
        echo "<p><strong>SoluÃ§Ã£o:</strong> Verifique o email e senha</p>";
    } elseif (strpos($e->getMessage(), 'Could not connect to SMTP host') !== false) {
        echo "<p><strong>PossÃ­vel causa:</strong> Problema de conectividade</p>";
        echo "<p><strong>SoluÃ§Ã£o:</strong> Verifique a conexÃ£o com a internet</p>";
    }
    
    echo "</div>";
    
    echo "<h3>Tentando com porta 587 (TLS)...</h3>";
    
    try {
        $mail2 = new PHPMailer(true);
        $mail2->isSMTP();
        $mail2->Host = 'smtp.hostinger.com';
        $mail2->SMTPAuth = true;
        $mail2->Username = 'desenvolvimento@lfmtecnologia.com';
        $mail2->Password = 'T3cn0l0g1a@';
        $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail2->Port = 587;
        $mail2->CharSet = 'UTF-8';
        
        $mail2->setFrom('desenvolvimento@lfmtecnologia.com', 'LFM Tecnologia - Teste TLS');
        $mail2->addAddress('desenvolvimento@lfmtecnologia.com', 'Teste TLS');
        
        $mail2->isHTML(true);
        $mail2->Subject = 'Teste TLS - ' . date('d/m/Y H:i:s');
        $mail2->Body = '<h2>Teste com TLS</h2><p>Teste usando porta 587 com TLS.</p>';
        
        if ($mail2->send()) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>ðŸŽ‰ SUCESSO COM TLS!</h3>";
            echo "<p>Email enviado com sucesso usando porta 587 (TLS)!</p>";
            echo "<p><strong>Use estas configuraÃ§Ãµes:</strong></p>";
            echo "<ul>";
            echo "<li>Porta: 587</li>";
            echo "<li>Encryption: STARTTLS</li>";
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (Exception $e2) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>âŒ ERRO TAMBÃ‰M COM TLS</h3>";
        echo "<p>" . htmlspecialchars($e2->getMessage()) . "</p>";
        echo "</div>";
    }
}

echo "<br><br>";
echo "<a href='orcamentos.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voltar para OrÃ§amentos</a>";
?>

