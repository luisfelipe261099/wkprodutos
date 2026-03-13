<?php
// Inicia a sessÃ£o no inÃ­cio do script. Isso Ã© fundamental para gerenciar o estado do usuÃ¡rio.
require_once 'includes/session_bootstrap.php';

// Se o usuÃ¡rio jÃ¡ estÃ¡ logado, redireciona para o dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Inclui o arquivo de conexÃ£o com o banco de dados.
// Usamos 'require_once' para garantir que a conexÃ£o seja estabelecida e para exibir um erro fatal se o arquivo nÃ£o for encontrado.
require_once 'includes/db_connect.php';

// Inicializa a variÃ¡vel para mensagens de erro de login.
$login_err = "";

// Verifica se o formulÃ¡rio de login foi enviado via mÃ©todo POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulÃ¡rio, removendo espaÃ§os em branco extras.
    $email = trim($_POST["email"]);
    $senha = trim($_POST["senha"]);

    // ValidaÃ§Ã£o bÃ¡sica: verifica se os campos nÃ£o estÃ£o vazios.
    if (empty($email) || empty($senha)) {
        $login_err = "Por favor, preencha todos os campos.";
    } else {
        // Prepara a query SQL para buscar o usuÃ¡rio pelo email.
        // Usamos prepared statements para prevenir SQL Injection.
        $sql = "SELECT id, nome, email, senha, nivel_acesso FROM usuarios WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Vincula o parÃ¢metro (email) Ã  declaraÃ§Ã£o preparada. 's' indica que Ã© um string.
            $stmt->bind_param("s", $param_email);

            // Define o valor do parÃ¢metro.
            $param_email = $email;

            // Tenta executar a declaraÃ§Ã£o preparada.
            if ($stmt->execute()) {
                // Armazena o resultado da query.
                $stmt->store_result();

                // Verifica se um usuÃ¡rio com o email fornecido foi encontrado.
                if ($stmt->num_rows == 1) {
                    // Vincula as colunas do resultado Ã s variÃ¡veis PHP.
                    $stmt->bind_result($id, $nome, $email_db, $senha_hash, $nivel_acesso);
                    // Pega o resultado (apenas uma linha, como sabemos pelo num_rows == 1).
                    if ($stmt->fetch()) {
                        // Verifica se a senha fornecida corresponde ao hash armazenado no banco de dados.
                        // password_verify() Ã© a funÃ§Ã£o correta para comparar senhas hashificadas com password_hash().
                        if (password_verify($senha, $senha_hash)) {
                            // Senha correta. Inicia/rege gera a sessÃ£o.
                            // session_regenerate_id(true) aumenta a seguranÃ§a contra ataques de fixaÃ§Ã£o de sessÃ£o.
                            session_regenerate_id(true); 
                            
                            // Armazena as informaÃ§Ãµes do usuÃ¡rio na sessÃ£o.
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["nome"] = $nome;
                            $_SESSION["nivel_acesso"] = $nivel_acesso;
                            kw_issue_auth_cookie((int)$id, (string)$nome, (string)$nivel_acesso);

                            // Redireciona o usuÃ¡rio para a pÃ¡gina do dashboard.
                            header("location: dashboard.php");
                            // Garante que o script pare de executar apÃ³s o redirecionamento.
                            exit; 
                        } else {
                            // Senha invÃ¡lida.
                            $login_err = "Email ou senha invÃ¡lidos.";
                        }
                    }
                } else {
                    // Email nÃ£o encontrado.
                    $login_err = "Email ou senha invÃ¡lidos.";
                }
            } else {
                // Erro na execuÃ§Ã£o da query.
                $login_err = "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
                // Para depuraÃ§Ã£o: echo "Erro na execuÃ§Ã£o: " . $stmt->error;
            }
            // Fecha a declaraÃ§Ã£o preparada.
            $stmt->close();
        } else {
            // Erro na preparaÃ§Ã£o da query.
            $login_err = "Erro interno no servidor ao preparar a autenticaÃ§Ã£o.";
            // Para depuraÃ§Ã£o: echo "Erro na preparaÃ§Ã£o: " . $conn->error;
        }
    }
}

// Fecha a conexÃ£o com o banco de dados.
$conn->close();

// O HTML da pÃ¡gina de login comeÃ§a aqui.
// Esta pÃ¡gina nÃ£o inclui o header.php ou footer.php como as outras pÃ¡ginas,
// pois tem um layout de pÃ¡gina cheia especÃ­fico para o login.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karla Wollinger - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<body>
    <div class="container">
        <div class="row justify-content-center g-4">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="login-aside">
                    <div class="login-kicker">Painel comercial</div>
                    <h1>GestÃ£o com visual mais limpo, rÃ¡pido e pronto para tablet e celular.</h1>
                    <p class="login-copy">Acesse o sistema para acompanhar clientes, vendas, estoque, orÃ§amentos e operaÃ§Ã£o diÃ¡ria em uma interface mais leve e organizada.</p>
                    <div class="login-highlights">
                        <div class="login-highlight">
                            <i class="fas fa-mobile-screen-button"></i>
                            <span>Uso confortÃ¡vel em celular e tablet</span>
                        </div>
                        <div class="login-highlight">
                            <i class="fas fa-table-cells-large"></i>
                            <span>Cards, tabelas e formulÃ¡rios com leitura melhor</span>
                        </div>
                        <div class="login-highlight">
                            <i class="fas fa-bolt"></i>
                            <span>NavegaÃ§Ã£o lateral mais rÃ¡pida para operaÃ§Ã£o diÃ¡ria</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7 col-lg-5 col-xl-4">
                <div class="login-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-cubes fa-3x text-primary mb-3"></i>
                            <h3 class="fw-bold text-primary">Karla Wollinger</h3>
                            <p class="text-muted">Sistema de GestÃ£o Comercial</p>
                        </div>
                        <h4 class="text-center mb-4">Acesse sua conta</h4>

                        <?php
                        // Exibe a mensagem de erro de login, se houver.
                        if (!empty($login_err)) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    ' . $login_err . '
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                  </div>';
                        }
                        ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha:</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="senha" id="senha" class="form-control" placeholder="********" required>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt me-2"></i> Entrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
