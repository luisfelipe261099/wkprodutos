<?php
// Script para verificar e instalar o PHPMailer
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

echo "<h1>VerificaÃ§Ã£o e InstalaÃ§Ã£o do PHPMailer</h1>";

// Verificar se o Composer estÃ¡ instalado
echo "<h2>1. Verificando o Composer...</h2>";
$composer_exists = false;

// Verificar se o arquivo composer.phar existe no diretÃ³rio atual
if (file_exists('composer.phar')) {
    echo "<p>âœ… Composer encontrado (composer.phar).</p>";
    $composer_command = 'php composer.phar';
    $composer_exists = true;
} else {
    // Tentar executar o comando composer
    exec('composer --version 2>&1', $output, $return_var);
    if ($return_var === 0) {
        echo "<p>âœ… Composer encontrado (instalaÃ§Ã£o global).</p>";
        $composer_command = 'composer';
        $composer_exists = true;
    } else {
        echo "<p>âŒ Composer nÃ£o encontrado. Baixando...</p>";
        
        // Tentar baixar o Composer
        $composer_setup = file_get_contents('https://getcomposer.org/installer');
        if ($composer_setup === false) {
            echo "<p>âŒ NÃ£o foi possÃ­vel baixar o instalador do Composer. Verifique sua conexÃ£o com a internet.</p>";
            echo "<p>Instale o Composer manualmente: <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></p>";
        } else {
            file_put_contents('composer-setup.php', $composer_setup);
            echo "<p>Executando instalador do Composer...</p>";
            exec('php composer-setup.php 2>&1', $output, $return_var);
            if ($return_var === 0) {
                echo "<p>âœ… Composer instalado com sucesso.</p>";
                $composer_command = 'php composer.phar';
                $composer_exists = true;
                @unlink('composer-setup.php');
            } else {
                echo "<p>âŒ Falha ao instalar o Composer: " . implode("<br>", $output) . "</p>";
                echo "<p>Instale o Composer manualmente: <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></p>";
            }
        }
    }
}

if ($composer_exists) {
    // Verificar composer.json
    echo "<h2>2. Verificando composer.json...</h2>";
    if (file_exists('composer.json')) {
        echo "<p>âœ… Arquivo composer.json encontrado.</p>";
        
        // Verificar se PHPMailer estÃ¡ listado como dependÃªncia
        $composer_json = json_decode(file_get_contents('composer.json'), true);
        if (isset($composer_json['require']['phpmailer/phpmailer'])) {
            echo "<p>âœ… PHPMailer estÃ¡ listado como dependÃªncia no composer.json.</p>";
        } else {
            echo "<p>âŒ PHPMailer nÃ£o estÃ¡ listado como dependÃªncia. Adicionando...</p>";
            
            if (!isset($composer_json['require'])) {
                $composer_json['require'] = [];
            }
            $composer_json['require']['phpmailer/phpmailer'] = "^6.8";
            file_put_contents('composer.json', json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            echo "<p>âœ… PHPMailer adicionado ao composer.json.</p>";
        }
    } else {
        echo "<p>âŒ Arquivo composer.json nÃ£o encontrado. Criando um novo...</p>";
        
        $composer_json = [
            'require' => [
                'phpmailer/phpmailer' => '^6.8'
            ]
        ];
        file_put_contents('composer.json', json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        echo "<p>âœ… Arquivo composer.json criado com PHPMailer como dependÃªncia.</p>";
    }
    
    // Instalar dependÃªncias
    echo "<h2>3. Instalando dependÃªncias...</h2>";
    echo "<p>Executando: {$composer_command} install</p>";
    echo "<pre>";
    passthru("{$composer_command} install 2>&1", $return_var);
    echo "</pre>";
    
    if ($return_var === 0) {
        echo "<p>âœ… DependÃªncias instaladas com sucesso.</p>";
    } else {
        echo "<p>âŒ Erro ao instalar dependÃªncias.</p>";
    }
    
    // Verificar se PHPMailer foi instalado corretamente
    echo "<h2>4. Verificando instalaÃ§Ã£o do PHPMailer...</h2>";
    if (file_exists('vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        echo "<p>âœ… PHPMailer encontrado em vendor/phpmailer/phpmailer/src/PHPMailer.php.</p>";
        
        // Testar se a classe pode ser carregada
        require_once 'vendor/autoload.php';
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "<p>âœ… Classe PHPMailer carregada com sucesso!</p>";
            echo "<h2>âœ… PHPMailer estÃ¡ instalado e funcionando corretamente!</h2>";
            
            echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; border-radius: 5px; color: #155724;'>";
            echo "<h3>VerificaÃ§Ã£o de SMTP</h3>";
            
            // Tentar carregar as configuraÃ§Ãµes de e-mail
            if (file_exists('includes/email_config.php')) {
                echo "<p>âœ… Arquivo de configuraÃ§Ã£o do SMTP encontrado.</p>";
                
                // Testar a criaÃ§Ã£o de uma instÃ¢ncia do PHPMailer
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $email_config = include 'includes/email_config.php';
                    
                    if (!empty($email_config['host']) && !empty($email_config['username']) && !empty($email_config['password'])) {
                        echo "<p>âœ… ConfiguraÃ§Ãµes do SMTP encontradas:</p>";
                        echo "<ul>";
                        echo "<li>Host: " . htmlspecialchars($email_config['host']) . "</li>";
                        echo "<li>Username: " . htmlspecialchars($email_config['username']) . "</li>";
                        echo "<li>Port: " . htmlspecialchars($email_config['port']) . "</li>";
                        echo "</ul>";
                    } else {
                        echo "<p>âš ï¸ Algumas configuraÃ§Ãµes do SMTP estÃ£o faltando. Verifique includes/email_config.php.</p>";
                    }
                    
                    echo "<p>âš ï¸ <strong>Nota:</strong> O teste de conexÃ£o SMTP nÃ£o foi realizado para evitar tentativas incorretas de login. Teste enviando um e-mail real.</p>";
                } catch (Exception $e) {
                    echo "<p>âŒ Erro ao instanciar PHPMailer: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            } else {
                echo "<p>âŒ Arquivo de configuraÃ§Ã£o do SMTP nÃ£o encontrado. Crie o arquivo includes/email_config.php.</p>";
            }
            echo "</div>";
            
            echo "<div style='margin-top: 20px; text-align: center;'>";
            echo "<a href='orcamentos.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voltar para OrÃ§amentos</a>";
            echo "</div>";
        } else {
            echo "<p>âŒ Classe PHPMailer nÃ£o pÃ´de ser carregada. Verifique o autoloader.</p>"; 
        }
    } else {
        echo "<p>âŒ PHPMailer nÃ£o foi encontrado em vendor/phpmailer/phpmailer/src/PHPMailer.php.</p>";
        
        // Tentar instalar especificamente o PHPMailer
        echo "<p>Tentando instalar especificamente o PHPMailer...</p>";
        echo "<pre>";
        passthru("{$composer_command} require phpmailer/phpmailer 2>&1", $return_var);
        echo "</pre>";
        
        if ($return_var === 0) {
            echo "<p>âœ… PHPMailer instalado com sucesso.</p>";
            
            // Verificar novamente
            if (file_exists('vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
                echo "<p>âœ… PHPMailer encontrado em vendor/phpmailer/phpmailer/src/PHPMailer.php.</p>";
                
                // Testar se a classe pode ser carregada
                require_once 'vendor/autoload.php';
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    echo "<p>âœ… Classe PHPMailer carregada com sucesso!</p>";
                    echo "<h2>âœ… PHPMailer estÃ¡ instalado e funcionando corretamente!</h2>";
                } else {
                    echo "<p>âŒ Classe PHPMailer nÃ£o pÃ´de ser carregada. Verifique o autoloader.</p>";
                }
            } else {
                echo "<p>âŒ PHPMailer ainda nÃ£o foi encontrado. Pode haver um problema com o Composer ou com as permissÃµes.</p>";
            }
        } else {
            echo "<p>âŒ Erro ao instalar o PHPMailer.</p>";
        }
    }
} else {
    echo "<h2>âŒ Composer nÃ£o encontrado ou nÃ£o pÃ´de ser instalado. InstalaÃ§Ã£o manual necessÃ¡ria.</h2>";
    echo "<p>Para instalar o PHPMailer manualmente:</p>";
    echo "<ol>";
    echo "<li>Baixe o PHPMailer em <a href='https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip'>https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip</a></li>";
    echo "<li>Extraia o conteÃºdo do arquivo zip</li>";
    echo "<li>Crie um diretÃ³rio <code>vendor/phpmailer/phpmailer/</code> se nÃ£o existir</li>";
    echo "<li>Copie a pasta <code>src/</code> do arquivo extraÃ­do para <code>vendor/phpmailer/phpmailer/</code></li>";
    echo "<li>Certifique-se de que o caminho <code>vendor/phpmailer/phpmailer/src/PHPMailer.php</code> existe</li>";
    echo "</ol>";
}
?>

<div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">    <h3>InstruÃ§Ãµes para o Servidor de ProduÃ§Ã£o</h3>
    <p>Se vocÃª estiver no servidor de produÃ§Ã£o, execute os seguintes comandos:</p>
    <ol>
        <li><code>cd /home/u182607388/domains/wkprodutosdelimpeza.com.br/public_html/2/system</code></li>
        <li><code>composer require phpmailer/phpmailer</code></li>
    </ol>
    <p>Se o Composer nÃ£o estiver instalado no servidor, vocÃª pode instalÃ¡-lo com:</p>
    <pre>curl -sS https://getcomposer.org/installer | php
php composer.phar install</pre>
    <p>Ou entre em contato com seu provedor de hospedagem para instalar o Composer ou o PHPMailer.</p>
    
    <h3>VerificaÃ§Ã£o do Status da InstalaÃ§Ã£o</h3>
    <p>Baseado na verificaÃ§Ã£o realizada:</p>
    <?php
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 15px;">';
        echo '<h4 style="margin-top: 0;">âœ… PHPMailer estÃ¡ instalado corretamente!</h4>';
        echo '<p>VocÃª pode agora enviar e-mails do sistema usando o PHPMailer.</p>';
        echo '<p><a href="orcamentos.php" style="color: #155724; font-weight: bold;">Voltar para orÃ§amentos</a></p>';
        echo '</div>';
    } else {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 15px;">';
        echo '<h4 style="margin-top: 0;">âŒ PHPMailer ainda nÃ£o estÃ¡ instalado corretamente</h4>';
        echo '<p>Siga as instruÃ§Ãµes acima ou entre em contato com o suporte tÃ©cnico.</p>';
        echo '</div>';
    }
    ?>
</div>

