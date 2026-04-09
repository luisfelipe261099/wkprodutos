<?php
// JÁ PODEMOS REMOVER AS LINHAS DE DEPURAÇÃO
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__ . '/includes/session_bootstrap.php';

// 1. VERIFICAÇÃO DE LOGIN
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// 2. CONEXÃO E DECLARAÇÃO DE VARIÁVEIS
require_once __DIR__ . '/includes/db_connect.php';

$id = $nome = $nome_fantasia = $tipo_pessoa = $cpf_cnpj = $inscricao_estadual = $email = $telefone = $endereco = $numero = $ponto_referencia = $cidade = $estado = $cep = "";
$title = "Cadastrar Novo Cliente";
$submit_button_text = "Cadastrar Cliente";
$message = '';
$message_type = '';

// 3. PROCESSAMENTO DO FORMULÁRIO (MÉTODO POST) - VERSÃO FINAL COM TRY/CATCH
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = trim($_POST["id"] ?? '');
    $nome = trim($_POST["nome"]);
    $tipo_pessoa = trim($_POST["tipo_pessoa"]);
    $nome_fantasia = ($tipo_pessoa == 'juridica') ? trim($_POST["nome_fantasia"]) : '';
    $inscricao_estadual = ($tipo_pessoa == 'juridica') ? trim($_POST["inscricao_estadual"]) : '';
    $cpf_cnpj = preg_replace('/[^0-9]/', '', trim($_POST["cpf_cnpj"]));
    $email = trim($_POST["email"]);
    $telefone = preg_replace('/[^0-9]/', '', trim($_POST["telefone"]));
    $endereco = trim($_POST["endereco"]);
    $numero = trim($_POST["numero"]);
    $ponto_referencia = trim($_POST["ponto_referencia"]);
    $cidade = trim($_POST["cidade"]);
    $estado = trim($_POST["estado"]);
    $cep = preg_replace('/[^0-9]/', '', trim($_POST["cep"]));

    if (empty($nome) || empty($tipo_pessoa)) {
        $message = "Nome e Tipo de Pessoa são obrigatórios.";
        $message_type = "danger";
    } else {
        if (empty($id)) { // Lógica para INSERIR novo cliente
            $sql = "INSERT INTO clientes (nome, nome_fantasia, tipo_pessoa, cpf_cnpj, inscricao_estadual, email, telefone, endereco, numero, ponto_referencia, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssssssssss", $nome, $nome_fantasia, $tipo_pessoa, $cpf_cnpj, $inscricao_estadual, $email, $telefone, $endereco, $numero, $ponto_referencia, $cidade, $estado, $cep);
                
                // INÍCIO DO BLOCO TRY...CATCH PARA TRATAMENTO DE ERROS
                try {
                    if ($stmt->execute()) {
                        $message = "Cliente cadastrado com sucesso!";
                        $message_type = "success";
                        $id = $nome = $nome_fantasia = $tipo_pessoa = $cpf_cnpj = $inscricao_estadual = $email = $telefone = $endereco = $numero = $ponto_referencia = $cidade = $estado = $cep = "";
                    }
                } catch (mysqli_sql_exception $e) {
                    // O código 1062 é específico para erro de "Entrada Duplicada"
                    if ($e->getCode() == 1062) {
                        $message = "Este CPF/CNPJ já está cadastrado. Por favor, verifique os dados.";
                    } else {
                        // Para qualquer outro erro de banco de dados
                        $message = "Erro de banco de dados: " . $e->getMessage();
                    }
                    $message_type = "danger";
                } finally {
                    $stmt->close();
                }
                // FIM DO BLOCO TRY...CATCH
            } else {
                 $message = "Erro na preparação da query SQL: " . $conn->error;
                 $message_type = "danger";
            }
        } else { // Lógica para ATUALIZAR cliente existente
            $sql = "UPDATE clientes SET nome = ?, nome_fantasia = ?, tipo_pessoa = ?, cpf_cnpj = ?, inscricao_estadual = ?, email = ?, telefone = ?, endereco = ?, numero = ?, ponto_referencia = ?, cidade = ?, estado = ?, cep = ? WHERE id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssssssssssi", $nome, $nome_fantasia, $tipo_pessoa, $cpf_cnpj, $inscricao_estadual, $email, $telefone, $endereco, $numero, $ponto_referencia, $cidade, $estado, $cep, $id);
                
                // INÍCIO DO BLOCO TRY...CATCH PARA TRATAMENTO DE ERROS
                try {
                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = "Cliente atualizado com sucesso!";
                        $_SESSION['flash_message_type'] = "success";
                        header("location: clientes.php");
                        exit();
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $message = "Não foi possível atualizar. O CPF/CNPJ informado já pertence a outro cliente.";
                    } else {
                        $message = "Erro de banco de dados ao atualizar: " . $e->getMessage();
                    }
                    $message_type = "danger";
                } finally {
                    $stmt->close();
                }
                // FIM DO BLOCO TRY...CATCH
            } else {
                 $message = "Erro na preparação da query SQL de atualização: " . $conn->error;
                 $message_type = "danger";
            }
        }
    }
}

// 4. PREENCHER FORMULÁRIO PARA EDIÇÃO (MÉTODO GET)
if (isset($_GET["id"]) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $id = trim($_GET["id"]);
    $sql_edit = "SELECT id, nome, nome_fantasia, tipo_pessoa, cpf_cnpj, inscricao_estadual, email, telefone, endereco, numero, ponto_referencia, cidade, estado, cep FROM clientes WHERE id = ?";
    
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $id);
        if ($stmt_edit->execute()) {
            $result_edit = $stmt_edit->get_result();
            if ($result_edit->num_rows == 1) {
                $row = $result_edit->fetch_assoc();
                extract($row);
                $title = "Editar Cliente: " . htmlspecialchars($nome);
                $submit_button_text = "Atualizar Cliente";
            } else {
                $_SESSION['flash_message'] = "Cliente não encontrado.";
                $_SESSION['flash_message_type'] = "danger";
                header("location: clientes.php");
                exit();
            }
        }
        $stmt_edit->close();
    }
}

$conn->close();

include_once __DIR__ . '/includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title">
        <i class="fas fa-<?php echo ($id ? 'user-edit' : 'user-plus'); ?> me-2"></i> <?php echo $title; ?>
    </h1>
    <p class="page-subtitle">Preencha os dados do cliente abaixo.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="modern-card fade-in-up">
    <div class="card-body-modern">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php echo !empty($id) ? '?id='.$id : ''; ?>" method="post" id="clientForm" novalidate>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

            <h5 class="form-section-title"><i class="fas fa-id-card me-2"></i> Identificação</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome / Razão Social <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
                <div class="col-md-6 mb-3" id="nome_fantasia_container" style="display: none;">
                    <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                    <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia" value="<?php echo htmlspecialchars($nome_fantasia); ?>">
                </div>
            </div>
            <div class="row">
                 <div class="col-md-4 mb-3">
                    <label for="tipo_pessoa" class="form-label">Tipo de Pessoa <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipo_pessoa" name="tipo_pessoa" required>
                        <option value="" disabled <?php echo empty($tipo_pessoa) ? 'selected' : ''; ?>>Selecione...</option>
                        <option value="fisica" <?php echo ($tipo_pessoa == 'fisica' ? 'selected' : ''); ?>>Física</option>
                        <option value="juridica" <?php echo ($tipo_pessoa == 'juridica' ? 'selected' : ''); ?>>Jurídica</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cpf_cnpj" class="form-label">CPF / CNPJ</label>
                    <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo htmlspecialchars($cpf_cnpj); ?>">
                </div>
                <div class="col-md-4 mb-3" id="inscricao_estadual_container" style="display: none;">
                    <label for="inscricao_estadual" class="form-label">Inscrição Estadual</label>
                    <input type="text" class="form-control" id="inscricao_estadual" name="inscricao_estadual" value="<?php echo htmlspecialchars($inscricao_estadual); ?>">
                </div>
            </div>
            
            <hr class="my-4">
            <h5 class="form-section-title"><i class="fas fa-address-book me-2"></i> Contato</h5>
            <div class="row">
                <div class="col-md-7 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="col-md-5 mb-3">
                    <label for="telefone" class="form-label">Telefone / Celular</label>
                    <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">
                </div>
            </div>

            <hr class="my-4">
            <h5 class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i> Endereço</h5>
            <div class="row align-items-center">
                <div class="col-md-4 mb-3">
                    <label for="cep" class="form-label">CEP</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="cep" name="cep" value="<?php echo htmlspecialchars($cep); ?>" aria-describedby="cep-helper" placeholder="00000-000">
                        <button type="button" class="btn btn-outline-secondary" id="cep-search-btn" title="Buscar CEP">
                            <i class="fas fa-search"></i>
                        </button>
                        <span class="input-group-text" id="cep-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                    </div>
                    <div id="cep-helper" class="form-text mt-1"></div>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="endereco" class="form-label">Endereço (Rua, Bairro)</label>
                    <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo htmlspecialchars($endereco); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($numero); ?>">
                </div>
                <div class="col-md-9 mb-3">
                    <label for="ponto_referencia" class="form-label">Ponto de Referência</label>
                    <input type="text" class="form-control" id="ponto_referencia" name="ponto_referencia" value="<?php echo htmlspecialchars($ponto_referencia); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cidade); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="estado" class="form-label">Estado (UF)</label>
                    <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($estado); ?>" maxlength="2">
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-4">
                <a href="clientes.php" class="btn btn-secondary w-100 w-sm-auto order-2 order-sm-1">
                    <i class="fas fa-arrow-left me-2"></i> Voltar para Lista
                </a>
                <button type="submit" class="btn btn-success btn-lg w-100 w-sm-auto order-1 order-sm-2">
                    <i class="fas fa-<?php echo ($id ? 'save' : 'check'); ?> me-2"></i> <?php echo $submit_button_text; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Nenhuma alteração é necessária no Javascript.
document.addEventListener('DOMContentLoaded', function() {
    const tipoPessoaSelect = document.getElementById('tipo_pessoa');
    const nomeFantasiaContainer = document.getElementById('nome_fantasia_container'); 
    const ieContainer = document.getElementById('inscricao_estadual_container');
    const cpfCnpjInput = document.getElementById('cpf_cnpj');
    const telefoneInput = document.getElementById('telefone');
    const cepInput = document.getElementById('cep');
    const cepHelper = document.getElementById('cep-helper');
    const cepLoading = document.getElementById('cep-loading');
    const cepSearchBtn = document.getElementById('cep-search-btn');
    const enderecoInput = document.getElementById('endereco');
    const cidadeInput = document.getElementById('cidade');
    const estadoInput = document.getElementById('estado');

    function toggleJuridicaFields() { 
        const isJuridica = tipoPessoaSelect.value === 'juridica';
        nomeFantasiaContainer.style.display = isJuridica ? 'block' : 'none';
        ieContainer.style.display = isJuridica ? 'block' : 'none';
        applyCpfCnpjMask();
    }

    function applyCpfCnpjMask() {
        let value = cpfCnpjInput.value.replace(/\D/g, '');
        if (tipoPessoaSelect.value === 'juridica') {
            cpfCnpjInput.maxLength = 18;
            value = value.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            cpfCnpjInput.maxLength = 14;
            value = value.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        cpfCnpjInput.value = value;
    }
    
    function applyTelefoneMask() {
        let value = telefoneInput.value.replace(/\D/g, '');
        telefoneInput.maxLength = 15;
        if (value.length > 10) {
            value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
        } else {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        }
        telefoneInput.value = value;
    }
    
    function applyCepMask() {
        let value = cepInput.value.replace(/\D/g, '');
        cepInput.maxLength = 9;
        value = value.replace(/^(\d{5})(\d)/, '$1-$2');
        cepInput.value = value;
    }

    let cepAbortController;
    async function handleCepSearch() {
        if (cepAbortController) cepAbortController.abort();
        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            cepHelper.textContent = 'Digite 8 números para o CEP.';
            cepHelper.className = 'form-text mt-1 text-muted';
            return;
        }
        cepLoading.style.display = 'inline-block';
        cepHelper.textContent = 'Buscando...';
        cepHelper.className = 'form-text mt-1 text-primary';
        cepAbortController = new AbortController();
        const signal = cepAbortController.signal;
        try {
            const response = await Promise.race([
                fetch(`https://viacep.com.br/ws/${cep}/json/`, { signal, referrerPolicy: 'no-referrer' }),
                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 7000))
            ]);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            if (data.erro) {
                cepHelper.textContent = 'CEP não encontrado.';
                cepHelper.className = 'form-text mt-1 text-danger';
            } else {
                enderecoInput.value = `${data.logradouro || ''}, ${data.bairro || ''}`.trim();
                cidadeInput.value = data.localidade || '';
                estadoInput.value = data.uf || '';
                cepHelper.textContent = 'Endereço preenchido!';
                cepHelper.className = 'form-text mt-1 text-success';
            }
        } catch (error) {
            console.error("Erro ao buscar CEP:", error);
            if (error.name === 'AbortError' || error.message === 'timeout') {
                cepHelper.textContent = 'Busca cancelada (tempo esgotado).';
            } else {
                cepHelper.textContent = 'Falha na comunicação com o serviço de CEP.';
            }
            cepHelper.className = 'form-text mt-1 text-danger';
        } finally {
            cepLoading.style.display = 'none';
        }
    }

    tipoPessoaSelect.addEventListener('change', toggleJuridicaFields);
    cpfCnpjInput.addEventListener('input', applyCpfCnpjMask);
    telefoneInput.addEventListener('input', applyTelefoneMask);
    cepInput.addEventListener('input', applyCepMask);
    cepSearchBtn.addEventListener('click', handleCepSearch);

    function initializeForm() {
        toggleJuridicaFields();
        applyCpfCnpjMask();
        applyTelefoneMask();
        applyCepMask();
    }
    initializeForm();
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
