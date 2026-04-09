<?php
// Incluir a biblioteca PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // Certifique-se de ter o PHPMailer instalado via Composer

// Adicionar log para depuração
function logMessage($message) {
    file_put_contents('email_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Função para sanitizar dados de entrada
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Função para enviar e-mail
function sendEmail($subject, $body, $from_email, $from_name, $isHTML = true) {
    logMessage("Iniciando tentativa de envio de email: " . $subject);

    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'desenvolvimento@lfmtecnologia.com';
        $mail->Password   = 'T3cn0l0g1a@';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Ativar debug SMTP
        $mail->SMTPDebug  = 3; // Nível 3 = debug máximo
        $mail->Debugoutput = function($str, $level) {
            logMessage("DEBUG SMTP ($level): $str");
        };

        // Verificar se o servidor está acessível
        logMessage("Verificando conexão com servidor SMTP...");
        $socket = @fsockopen($mail->Host, $mail->Port, $errno, $errstr, 5);
        if (!$socket) {
            logMessage("ERRO: Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
            return false;
        }
        fclose($socket);
        logMessage("Conexão com servidor SMTP bem-sucedida");

        // Destinatários
        $mail->setFrom('desenvolvimento@lfmtecnologia.com', 'Site WK Produtos');

        // Adicionar múltiplos destinatários para aumentar chances de recebimento
        $mail->addAddress('wk.karla@hotmail.com', 'WK Produtos de Limpeza');
        // Adicione seu email pessoal como cópia para teste
        // $mail->addCC('seu-email-pessoal@gmail.com'); // Comentado para evitar erros
        $mail->addReplyTo($from_email, $from_name);

        // Conteúdo
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<p>', '</p>'], ["\n", "\n", "\n"], $body));

        logMessage("Configuração concluída, tentando enviar email...");
        $result = $mail->send();
        logMessage("Resultado do envio: " . ($result ? "SUCESSO" : "FALHA"));

        if ($result) {
            logMessage("Email enviado com sucesso!");
            return true;
        } else {
            logMessage("FALHA NO ENVIO: Nenhum erro específico");
            return false;
        }
    } catch (Exception $e) {
        logMessage("EXCEÇÃO AO ENVIAR EMAIL: " . $mail->ErrorInfo);
        logMessage("Detalhes da exceção: " . $e->getMessage());
        return false;
    }
}

function sendEmailFallback($subject, $body, $from_email, $from_name) {
    logMessage("Tentando enviar email via função mail() nativa: " . $subject);

    // Cabeçalhos
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Enviar email
    $result = mail('wk.karla@hotmail.com', $subject, $body, $headers);

    logMessage("Resultado do envio via mail(): " . ($result ? "SUCESSO" : "FALHA"));
    return $result;
}

// Função para gerar o HTML do email de cotação
function generateQuoteEmailHTML($name, $email, $phone, $type, $products, $message) {
    // Garantir que produtos é um array
    if (!is_array($products)) {
        $products = [];
    }

    // Gerar lista de produtos HTML
    $products_list_html = '';
    if (count($products) > 0) {
        foreach ($products as $product) {
            $products_list_html .= '<li class="product-item">' . $product . '</li>';
        }
    } else {
        $products_list_html = '<li class="product-item">Nenhum produto específico selecionado</li>';
    }

    // Se não houver mensagem, exibir texto padrão
    if (empty($message)) {
        $message = "Nenhuma mensagem adicional informada.";
    }

    // Carregar o template de cotação embutido no código
    $template = getQuoteEmailTemplate();

    // Substituir variáveis no template
    $template = str_replace('{$name}', $name, $template);
    $template = str_replace('{$email}', $email, $template);
    $template = str_replace('{$phone}', $phone, $template);
    $template = str_replace('{$type}', $type, $template);
    $template = str_replace('{$products_list}', $products_list_html, $template);
    $template = str_replace('{$message}', nl2br($message), $template);

    return $template;
}

// Função para gerar o HTML do email de contato
function generateContactEmailHTML($name, $email, $phone, $subject, $message) {
    // Se não houver mensagem, exibir texto padrão
    if (empty($message)) {
        $message = "Nenhuma mensagem informada.";
    }

    // Carregar o template de contato embutido no código
    $template = getContactEmailTemplate();

    // Substituir variáveis no template
    $template = str_replace('{$name}', $name, $template);
    $template = str_replace('{$email}', $email, $template);
    $template = str_replace('{$phone}', $phone, $template);
    $template = str_replace('{$subject}', $subject, $template);
    $template = str_replace('{$message}', nl2br($message), $template);

    return $template;
}

// Função para gerar o HTML do email de newsletter
function generateNewsletterEmailHTML($email) {
    // Um template simples para a newsletter
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #005294; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Nova Inscrição na Newsletter</h2>
            </div>
            <div class="content">
                <p>Olá Karla,</p>
                <p>Uma nova pessoa se inscreveu para receber atualizações e promoções da WK Produtos de Limpeza.</p>
                <p><strong>Email inscrito:</strong> ' . $email . '</p>
                <p>É recomendável adicionar este email à sua lista de contatos de newsletter.</p>
            </div>
            <div class="footer">
                <p>WK Produtos de Limpeza - Rua Tiradentes, 406 - São José dos Pinhais - PR</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

// Template de email de cotação embutido
function getQuoteEmailTemplate() {
    return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Solicitação de Cotação - WK Produtos</title>
    <style>
        /* Reset de estilos para compatibilidade com clientes de email */
        body, html {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #343a40;
            background-color: #f8f9fa;
        }

        /* Container principal */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Cabeçalho */
        .header {
            background: linear-gradient(90deg, #005294, #0078d4);
            padding: 20px;
            text-align: center;
            color: white;
        }

        .logo {
            margin-bottom: 15px;
        }

        .logo-icon {
            font-size: 38px;
            color: white;
        }

        .logo-text {
            font-size: 26px;
            font-weight: 700;
        }

        /* Saudação */
        .greeting {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-left: 4px solid #005294;
            margin: 20px;
            font-size: 16px;
        }

        /* Conteúdo */
        .content {
            padding: 20px;
        }

        .section-title {
            font-size: 20px;
            color: #005294;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th {
            background-color: #e9ecef;
            text-align: left;
            padding: 10px;
            font-weight: 600;
            border: 1px solid #dee2e6;
        }

        .data-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }

        .highlight {
            background-color: #f8f9fa;
        }

        /* Lista de produtos */
        .product-list {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }

        .product-item {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            border-left: 3px solid #005294;
            border-radius: 0 4px 4px 0;
        }

        /* Mensagem */
        .message-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-style: italic;
            border-left: 3px solid #6c757d;
        }

        /* Rodapé */
        .footer {
            background-color: #003b6f;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }

        .footer a {
            color: white;
            text-decoration: none;
        }

        .social-links {
            margin: 15px 0;
        }

        .social-link {
            display: inline-block;
            margin: 0 10px;
            width: 32px;
            height: 32px;
            line-height: 32px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #005294;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            font-weight: 500;
        }

        .cta {
            text-align: center;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Cabeçalho com logo -->
        <div class="header">
            <div class="logo">
                <span class="logo-icon">⚗️</span>
                <span class="logo-text">WK Produtos de Limpeza</span>
            </div>
            <h1>Nova Solicitação de Cotação</h1>
        </div>

        <!-- Saudação -->
        <div class="greeting">
            <p>Olá <strong>Karla</strong>,</p>
            <p>Você recebeu uma nova solicitação de cotação através do seu site. Abaixo estão os detalhes para você responder ao cliente.</p>
        </div>

        <!-- Conteúdo principal -->
        <div class="content">
            <!-- Dados do cliente -->
            <h2 class="section-title">Dados do Cliente</h2>
            <table class="data-table">
                <tr>
                    <th>Nome/Empresa</th>
                    <td data-label="Nome/Empresa">{$name}</td>
                </tr>
                <tr class="highlight">
                    <th>E-mail</th>
                    <td data-label="E-mail">{$email}</td>
                </tr>
                <tr>
                    <th>Telefone</th>
                    <td data-label="Telefone">{$phone}</td>
                </tr>
                <tr class="highlight">
                    <th>Tipo de Compra</th>
                    <td data-label="Tipo de Compra">{$type}</td>
                </tr>
            </table>

            <!-- Produtos selecionados -->
            <h2 class="section-title">Produtos de Interesse</h2>
            <ul class="product-list">
                {$products_list}
            </ul>

            <!-- Mensagem do cliente -->
            <h2 class="section-title">Mensagem Adicional</h2>
            <div class="message-box">
                {$message}
            </div>

            <!-- Call to Action -->
            <div class="cta">
                <p>Recomendamos responder a esta solicitação em até 24 horas para maximizar suas chances de conversão.</p>
                <a href="mailto:{$email}" class="button">Responder ao Cliente</a>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="footer">
            <p><strong>WK Produtos de Limpeza</strong></p>
            <p>Rua Tiradentes, 406 - São José dos Pinhais - PR</p>
            <p>Tel: (41) 3283-7121 | (41) 9 9859-3242</p>

            <div class="social-links">
                <a href="#" class="social-link">F</a>
                <a href="#" class="social-link">I</a>
                <a href="#" class="social-link">W</a>
            </div>

            <p>&copy; 2025 WK Produtos de Limpeza. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
}

// Template de email de contato embutido
function getContactEmailTemplate() {
    return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Mensagem de Contato - WK Produtos</title>
    <style>
        /* Reset de estilos para compatibilidade com clientes de email */
        body, html {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #343a40;
            background-color: #f8f9fa;
        }

        /* Container principal */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Cabeçalho */
        .header {
            background: linear-gradient(135deg, #005294, #00a1ff);
            padding: 20px;
            text-align: center;
            color: white;
        }

        .logo {
            margin-bottom: 15px;
        }

        .logo-icon {
            font-size: 38px;
            color: white;
        }

        .logo-text {
            font-size: 26px;
            font-weight: 700;
        }

        /* Saudação */
        .greeting {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-left: 4px solid #00a1ff;
            margin: 20px;
            font-size: 16px;
        }

        /* Conteúdo */
        .content {
            padding: 20px;
        }

        .section-title {
            font-size: 20px;
            color: #005294;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }

        .data-group {
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .label {
            font-weight: 600;
            color: #005294;
            margin-bottom: 5px;
            display: block;
        }

        .value {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        /* Mensagem */
        .message-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
            font-style: italic;
            border-left: 3px solid #00a1ff;
            line-height: 1.8;
        }

        /* Ícone de assunto */
        .subject-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            background-color: #005294;
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }

        /* Call to Action */
        .cta {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #005294;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            font-weight: 500;
        }

        /* Destaque */
        .highlight-box {
            background-color: #e9ecef;
            border-left: 4px solid #00a1ff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }

        /* Rodapé */
        .footer {
            background-color: #003b6f;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }

        .footer a {
            color: white;
            text-decoration: none;
        }

        .social-links {
            margin: 15px 0;
        }

        .social-link {
            display: inline-block;
            margin: 0 10px;
            width: 32px;
            height: 32px;
            line-height: 32px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Cabeçalho com logo -->
        <div class="header">
            <div class="logo">
                <span class="logo-icon">⚗️</span>
                <span class="logo-text">WK Produtos de Limpeza</span>
            </div>
            <h1>Nova Mensagem de Contato</h1>
        </div>

        <!-- Saudação -->
        <div class="greeting">
            <p>Olá <strong>Karla</strong>,</p>
            <p>Você recebeu uma nova mensagem de contato através do site da WK Produtos de Limpeza.</p>
        </div>

        <!-- Conteúdo principal -->
        <div class="content">
            <!-- Dados do contato -->
            <h2 class="section-title">
                <span class="subject-icon">!</span>
                Assunto: {$subject}
            </h2>

            <div class="data-group">
                <div class="label">Nome:</div>
                <div class="value">{$name}</div>

                <div class="label">E-mail:</div>
                <div class="value">{$email}</div>

                <div class="label">Telefone:</div>
                <div class="value">{$phone}</div>
            </div>

            <!-- Mensagem do contato -->
            <h2 class="section-title">Mensagem</h2>
            <div class="message-box">
                {$message}
            </div>

            <!-- Observação -->
            <div class="highlight-box">
                <p><strong>Importante:</strong> Este contato foi recebido através do formulário do site. Recomendamos responder no prazo de 24 horas para um melhor atendimento.</p>
            </div>

            <!-- Call to Action -->
            <div class="cta">
                <a href="mailto:{$email}" class="button">Responder ao Cliente</a>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="footer">
            <p><strong>WK Produtos de Limpeza</strong></p>
            <p>Rua Tiradentes, 406 - São José dos Pinhais - PR</p>
            <p>Tel: (41) 3283-7121 | (41) 9 9859-3242</p>

            <div class="social-links">
                <a href="#" class="social-link">F</a>
                <a href="#" class="social-link">I</a>
                <a href="#" class="social-link">W</a>
            </div>

            <p>&copy; 2025 WK Produtos de Limpeza. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
}

// Verifica qual formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    logMessage("Recebido um formulário via POST");

    // Formulário de Cotação
    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['type'])) {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $type = sanitizeInput($_POST['type']);
        $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';

        logMessage("Processando formulário de cotação de: " . $name);

        // Produtos selecionados
        $products = [];
        if (isset($_POST['products']) && is_array($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                $products[] = sanitizeInput($product);
            }
        }

        // Gerar o HTML do email usando o template
        $email_body = generateQuoteEmailHTML($name, $email, $phone, $type, $products, $message);

        // Enviar e-mail
        $subject = "Nova Solicitação de Cotação - WK Produtos";
        $result = sendEmail($subject, $email_body, $email, $name, true);

        // Redirecionamento com status
        if ($result) {
            logMessage("Cotação enviada com sucesso, redirecionando");
            header("Location: index.php?form=quote&status=success");
        } else {
            logMessage("ERRO ao enviar cotação, redirecionando com erro");
            header("Location: index.php?form=quote&status=error");
        }
        exit;
    }

    // Formulário de Contato
    elseif (isset($_POST['contact-name']) && isset($_POST['contact-email']) && isset($_POST['contact-subject'])) {
        $name = sanitizeInput($_POST['contact-name']);
        $email = sanitizeInput($_POST['contact-email']);
        $phone = isset($_POST['contact-phone']) ? sanitizeInput($_POST['contact-phone']) : 'Não informado';
        $subject = sanitizeInput($_POST['contact-subject']);
        $message = isset($_POST['contact-message']) ? sanitizeInput($_POST['contact-message']) : '';

        logMessage("Processando formulário de contato de: " . $name);

        // Gerar o HTML do email usando o template
        $email_body = generateContactEmailHTML($name, $email, $phone, $subject, $message);

        // Enviar e-mail
        $email_subject = "Nova Mensagem de Contato - {$subject}";
        $result = sendEmail($email_subject, $email_body, $email, $name, true);

        // Redirecionamento com status
        if ($result) {
            logMessage("Mensagem de contato enviada com sucesso, redirecionando");
            header("Location: index.php?form=contact&status=success");
        } else {
            logMessage("ERRO ao enviar mensagem de contato, redirecionando com erro");
            header("Location: index.php?form=contact&status=error");
        }
        exit;
    }

    // Formulário de Newsletter
    elseif (isset($_POST['newsletter-email'])) {
        $email = sanitizeInput($_POST['newsletter-email']);

        logMessage("Processando inscrição newsletter: " . $email);

        // Gerar o HTML do email
        $email_body = generateNewsletterEmailHTML($email);

        // Enviar e-mail
        $subject = "Nova Inscrição na Newsletter - WK Produtos";
        $result = sendEmail($subject, $email_body, $email, 'Assinante Newsletter', true);

        // Redirecionamento com status
        if ($result) {
            logMessage("Inscrição newsletter enviada com sucesso, redirecionando");
            header("Location: index.php?form=newsletter&status=success");
        } else {
            logMessage("ERRO ao processar newsletter, redirecionando com erro");
            header("Location: index.php?form=newsletter&status=error");
        }
        exit;
    }

    // Se nenhum formulário válido for identificado
    logMessage("Nenhum formulário válido identificado");
    header("Location: index.php?status=error");
    exit;
}

// No final do arquivo, após processar o formulário
// Adicione este código para testar o envio diretamente

if (isset($_GET['test']) && $_GET['test'] == '1') {
    logMessage("Iniciando teste de email direto");

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

    echo "<h1>Teste de Email</h1>";
    echo "<p>Resultado: " . ($result ? "Email enviado com sucesso" : "Falha ao enviar email") . "</p>";
    echo "<p>Verifique o arquivo de log para mais detalhes.</p>";
    exit;
}
