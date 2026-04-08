<?php
require_once 'includes/session_bootstrap.php';
require_once 'includes/db_connect.php';

// Verificar token de acesso
$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Acesso negado. Token inválido.');
}

// Validar token e buscar dados do cliente
$sql_token = "SELECT ml.*, c.id as cliente_id_db, c.nome as cliente_nome, c.email as cliente_email,
              c.endereco as cliente_endereco, c.cidade as cliente_cidade, c.estado as cliente_estado,
              c.cep as cliente_cep, c.telefone as cliente_telefone, c.tipo_pessoa, c.cpf_cnpj
              FROM marketplace_links ml
              LEFT JOIN clientes c ON ml.cliente_id = c.id
              WHERE ml.token_acesso = ? AND ml.ativo = 1";
$stmt_token = $conn->prepare($sql_token);
if (!$stmt_token) {
    die("Erro ao preparar consulta.");
}
$stmt_token->bind_param("s", $token);
$stmt_token->execute();
$result_token = $stmt_token->get_result();

if ($result_token->num_rows === 0) {
    die('Link inválido, expirado ou desativado.');
}

$link_data = $result_token->fetch_assoc();
$cliente_id = $link_data['cliente_id_db'];
$stmt_token->close();

// Verificar expiração
if ($link_data['data_expiracao'] && strtotime($link_data['data_expiracao']) < time()) {
    die('Link expirado.');
}

// Atualizar último acesso
$sql_update_link = "UPDATE marketplace_links SET ultimo_acesso = NOW(), total_acessos = total_acessos + 1 WHERE token_acesso = ?";
$stmt_ul = $conn->prepare($sql_update_link);
if ($stmt_ul) {
    $stmt_ul->bind_param("s", $token);
    $stmt_ul->execute();
    $stmt_ul->close();
}

// Buscar empresas disponíveis para este cliente
$sql_check_empresas = "SELECT COUNT(*) as total FROM marketplace_cliente_empresas WHERE cliente_id = ? AND ativo = 1";
$stmt_check = $conn->prepare($sql_check_empresas);
$stmt_check->bind_param("i", $cliente_id);
$stmt_check->execute();
$empresas_associadas = $stmt_check->get_result()->fetch_assoc()['total'];
$stmt_check->close();

if ($empresas_associadas > 0) {
    // Cliente tem empresas específicas
    $sql_empresas = "SELECT e.id, e.nome_empresa, e.logo_empresa,
                     (SELECT COUNT(*) FROM produtos p WHERE p.empresa_id = e.id AND p.ativo_marketplace = 1 AND p.quantidade_estoque > 0) as total_produtos
                     FROM empresas_representadas e
                     INNER JOIN marketplace_cliente_empresas mce ON e.id = mce.empresa_id
                     WHERE mce.cliente_id = ? AND mce.ativo = 1 AND e.status = 'ativo'
                     ORDER BY e.nome_empresa ASC";
    $stmt_emp = $conn->prepare($sql_empresas);
    $stmt_emp->bind_param("i", $cliente_id);
    $stmt_emp->execute();
    $result_empresas = $stmt_emp->get_result();
} else {
    // Mostrar todas as empresas ativas
    $sql_empresas = "SELECT e.id, e.nome_empresa, e.logo_empresa,
                     (SELECT COUNT(*) FROM produtos p WHERE p.empresa_id = e.id AND p.ativo_marketplace = 1 AND p.quantidade_estoque > 0) as total_produtos
                     FROM empresas_representadas e
                     WHERE e.status = 'ativo'
                     ORDER BY e.nome_empresa ASC";
    $result_empresas = $conn->query($sql_empresas);
}

$empresas = [];
while ($emp = $result_empresas->fetch_assoc()) {
    if ($emp['total_produtos'] > 0) {
        $empresas[] = $emp;
    }
}

// Buscar empresa selecionada (se houver)
$empresa_selecionada = isset($_GET['empresa']) ? (int)$_GET['empresa'] : 0;
$produtos = [];

if ($empresa_selecionada > 0) {
    // Verificar se o cliente tem acesso a esta empresa
    $acesso_ok = false;
    foreach ($empresas as $emp) {
        if ($emp['id'] == $empresa_selecionada) {
            $acesso_ok = true;
            break;
        }
    }

    if ($acesso_ok) {
        $sql_produtos = "SELECT p.*, e.nome_empresa
                         FROM produtos p
                         LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
                         WHERE p.empresa_id = ? AND p.ativo_marketplace = 1 AND p.quantidade_estoque > 0
                         ORDER BY p.destaque_marketplace DESC, p.ordem_exibicao ASC, p.nome ASC";
        $stmt_prod = $conn->prepare($sql_produtos);
        $stmt_prod->bind_param("i", $empresa_selecionada);
        $stmt_prod->execute();
        $result_prod = $stmt_prod->get_result();
        while ($p = $result_prod->fetch_assoc()) {
            $produtos[] = $p;
        }
        $stmt_prod->close();
    } else {
        $empresa_selecionada = 0;
    }
}

// Buscar histórico de orçamentos do cliente
$sql_orcamentos_hist = "SELECT o.id, o.data_orcamento, o.valor_total, o.status_orcamento, e.nome_empresa,
                        (SELECT COUNT(*) FROM itens_orcamento io WHERE io.orcamento_id = o.id) as qtd_itens
                        FROM orcamentos o
                        LEFT JOIN empresas_representadas e ON o.empresa_id = e.id
                        WHERE o.cliente_id = ?
                        ORDER BY o.data_orcamento DESC
                        LIMIT 20";
$stmt_hist = $conn->prepare($sql_orcamentos_hist);
$stmt_hist->bind_param("i", $cliente_id);
$stmt_hist->execute();
$result_hist = $stmt_hist->get_result();
$historico_orcamentos = [];
while ($orc = $result_hist->fetch_assoc()) {
    $historico_orcamentos[] = $orc;
}
$stmt_hist->close();

// Nome da empresa selecionada
$nome_empresa_selecionada = '';
foreach ($empresas as $emp) {
    if ($emp['id'] == $empresa_selecionada) {
        $nome_empresa_selecionada = $emp['nome_empresa'];
        break;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monte seu Orçamento - Karla Wollinger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #334155;
            --accent: #f97316;
            --success: #10b981;
            --danger: #ef4444;
            --light: #f3f4f6;
            --medium: #d1d5db;
            --dark: #4b5563;
            --text: #1f2937;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background: var(--light);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper { flex-grow: 1; }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2.5rem 0;
            text-align: center;
            border-bottom: 4px solid var(--accent);
        }
        .page-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
        }
        .page-header .welcome-box {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 0.8rem 1.2rem;
            display: inline-block;
            margin-top: 1rem;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Tabs */
        .nav-tabs-custom .nav-link {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--dark);
            border: none;
            border-bottom: 3px solid transparent;
            padding: 1rem 1.5rem;
            transition: all 0.3s;
        }
        .nav-tabs-custom .nav-link.active,
        .nav-tabs-custom .nav-link:hover {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .nav-tabs-custom { border-bottom: 1px solid var(--medium); margin-bottom: 2rem; }

        /* Empresa Cards */
        .empresa-card {
            background: white;
            border: 2px solid var(--medium);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
        }
        .empresa-card:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(37,99,235,0.15);
        }
        .empresa-card.selected {
            border-color: var(--primary);
            background: rgba(37,99,235,0.05);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }
        .empresa-card .logo-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            overflow: hidden;
        }
        .empresa-card .logo-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .empresa-card h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Product Cards */
        .product-card {
            border: 1px solid var(--medium);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: all 0.3s;
            height: 100%;
            background: white;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .product-image-wrapper {
            height: 200px;
            overflow: hidden;
            border-radius: 16px 16px 0 0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        .product-image-wrapper img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .product-card .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .product-card .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--secondary);
        }
        .text-price {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        .btn-add-cart {
            background: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 0.55rem 1.2rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-add-cart:hover {
            background: var(--accent);
            transform: translateY(-2px);
            color: white;
        }

        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            right: -480px;
            top: 0;
            width: 480px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 25px rgba(0,0,0,0.15);
            transition: right 0.4s cubic-bezier(0.25,0.8,0.25,1);
            z-index: 1050;
            display: flex;
            flex-direction: column;
        }
        .cart-sidebar.open { right: 0; }
        .cart-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--medium);
            background: var(--light);
        }
        .cart-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }
        .cart-footer-area {
            padding: 1.5rem;
            border-top: 1px solid var(--medium);
            background: var(--light);
        }
        .cart-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .cart-overlay.show { display: block; opacity: 1; }

        /* Floating Cart Button */
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1030;
        }
        .floating-cart .btn {
            width: 60px;
            height: 60px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
            font-size: 1.2rem;
            background: var(--accent);
            border: none;
            color: white;
        }
        .floating-cart .btn:hover { background: var(--primary); color: white; }
        .badge-cart {
            position: absolute;
            top: -6px; right: -6px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 26px; height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            border: 2px solid white;
        }

        /* Filter bar */
        .filter-bar {
            background: white;
            padding: 1.25rem;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        /* Data cards */
        .data-card {
            background: white;
            padding: 2rem;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .data-card h5 {
            font-family: 'Poppins', sans-serif;
            color: var(--primary);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        /* Status badges */
        .status-pendente { background-color: #facc15; color: #333; }
        .status-aprovado { background-color: var(--success); color: white; }
        .status-rejeitado { background-color: var(--danger); color: white; }
        .status-convertido_venda { background-color: var(--primary); color: white; }
        .status-badge {
            padding: 0.35em 0.8em;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
        }

        /* Footer */
        .footer-page {
            background: var(--secondary);
            color: var(--medium);
            padding: 1.5rem 0;
            margin-top: 3rem;
            font-size: 0.9rem;
        }

        /* Step indicator */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: var(--medium);
        }
        .step.active { color: var(--primary); }
        .step.completed { color: var(--success); }
        .step-number {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            background: var(--medium);
            color: white;
        }
        .step.active .step-number { background: var(--primary); }
        .step.completed .step-number { background: var(--success); }
        .step-line {
            width: 50px;
            height: 2px;
            background: var(--medium);
            align-self: center;
        }

        /* Voltar button */
        .btn-voltar {
            background: white;
            border: 2px solid var(--medium);
            border-radius: 10px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            color: var(--dark);
            transition: all 0.3s;
        }
        .btn-voltar:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.5rem; }
            .cart-sidebar { width: 100%; right: -100%; }
            .empresa-card { padding: 1.2rem; }
            .step span { display: none; }
            .step-line { width: 30px; }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Header -->
        <div class="page-header">
            <div class="container">
                <h1><i class="fas fa-file-invoice-dollar me-2"></i> Monte seu Orçamento</h1>
                <p class="opacity-85 mb-0">Selecione a empresa, escolha os produtos e envie seu orçamento</p>
                <div class="welcome-box">
                    <small class="mb-0">Olá, <strong><?php echo htmlspecialchars($link_data['cliente_nome']); ?>!</strong></small>
                </div>
            </div>
        </div>

        <div class="container mt-4">
            <!-- Tabs -->
            <ul class="nav nav-tabs nav-tabs-custom" id="mainTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="orcamento-tab" data-bs-toggle="tab" data-bs-target="#orcamento-pane" type="button">
                        <i class="fas fa-cart-plus me-2"></i>Novo Orçamento
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico-pane" type="button">
                        <i class="fas fa-history me-2"></i>Meus Orçamentos
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-2" id="mainTabContent">
                <!-- TAB: Novo Orçamento -->
                <div class="tab-pane fade show active" id="orcamento-pane">
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step <?php echo $empresa_selecionada === 0 ? 'active' : 'completed'; ?>">
                            <span class="step-number"><?php echo $empresa_selecionada > 0 ? '<i class="fas fa-check"></i>' : '1'; ?></span>
                            <span>Empresa</span>
                        </div>
                        <div class="step-line"></div>
                        <div class="step <?php echo $empresa_selecionada > 0 ? 'active' : ''; ?>">
                            <span class="step-number">2</span>
                            <span>Produtos</span>
                        </div>
                        <div class="step-line"></div>
                        <div class="step">
                            <span class="step-number">3</span>
                            <span>Enviar</span>
                        </div>
                    </div>

                    <?php if ($empresa_selecionada === 0): ?>
                    <!-- STEP 1: Selecionar Empresa -->
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">Selecione a empresa para montar seu orçamento</h4>
                        <p class="text-muted">Escolha uma das empresas abaixo para ver os produtos disponíveis</p>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($empresas as $emp): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <a href="?token=<?php echo htmlspecialchars($token); ?>&empresa=<?php echo $emp['id']; ?>" class="text-decoration-none">
                                <div class="empresa-card">
                                    <div class="logo-wrapper">
                                        <?php if (!empty($emp['logo_empresa']) && file_exists($emp['logo_empresa'])): ?>
                                            <img src="<?php echo htmlspecialchars($emp['logo_empresa']); ?>" alt="<?php echo htmlspecialchars($emp['nome_empresa']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-building fa-2x" style="color: var(--primary);"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h5><?php echo htmlspecialchars($emp['nome_empresa']); ?></h5>
                                    <span class="badge bg-primary"><?php echo $emp['total_produtos']; ?> produtos</span>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($empresas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-store fa-3x text-muted mb-3"></i>
                            <p class="lead text-muted">Nenhuma empresa disponível no momento.</p>
                        </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <!-- STEP 2: Produtos da Empresa -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <a href="?token=<?php echo htmlspecialchars($token); ?>" class="btn btn-voltar">
                            <i class="fas fa-arrow-left me-1"></i> Trocar Empresa
                        </a>
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-building me-1 text-primary"></i>
                            <?php echo htmlspecialchars($nome_empresa_selecionada); ?>
                        </h5>
                    </div>

                    <!-- Search/Filter -->
                    <div class="filter-bar">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control border-start-0" placeholder="Buscar por nome ou código..." id="searchInput">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-secondary w-100" onclick="document.getElementById('searchInput').value=''; filtrarProdutos();">
                                    <i class="fas fa-times me-1"></i> Limpar Busca
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Grid -->
                    <div class="row gy-4" id="produtos-container">
                        <?php if (!empty($produtos)): ?>
                            <?php foreach ($produtos as $produto): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 produto-item"
                                 data-nome="<?php echo strtolower(htmlspecialchars($produto['nome'])); ?>"
                                 data-sku="<?php echo strtolower(htmlspecialchars($produto['sku'] ?? '')); ?>">
                                <div class="card product-card">
                                    <div class="product-image-wrapper">
                                        <?php
                                        $img = '';
                                        if (!empty($produto['imagem_produto']) && file_exists("uploads/produtos/" . $produto['imagem_produto'])) {
                                            $img = "uploads/produtos/" . $produto['imagem_produto'];
                                        } elseif (!empty($produto['imagem_url']) && filter_var($produto['imagem_url'], FILTER_VALIDATE_URL)) {
                                            $img = $produto['imagem_url'];
                                        }
                                        if ($img): ?>
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-tags fa-3x" style="color: var(--primary); opacity:0.5;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <div>
                                            <?php if ($produto['destaque_marketplace'] == 1): ?>
                                                <span class="badge mb-2" style="background: var(--accent);"><i class="fas fa-star"></i> Destaque</span>
                                            <?php endif; ?>
                                            <h6 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h6>
                                            <p class="small text-muted mb-1">
                                                <i class="fas fa-barcode me-1"></i> <?php echo htmlspecialchars($produto['sku'] ?? 'N/D'); ?>
                                            </p>
                                            <?php if (!empty($produto['descricao'])): ?>
                                                <p class="small text-muted mb-2">
                                                    <?php echo htmlspecialchars(mb_substr($produto['descricao'], 0, 60)) . (mb_strlen($produto['descricao']) > 60 ? '...' : ''); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-price">
                                                    R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?>
                                                </span>
                                                <button class="btn btn-add-cart"
                                                    onclick="adicionarAoCarrinho(<?php echo $produto['id']; ?>, '<?php echo htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>', <?php echo $produto['preco_venda']; ?>, <?php echo $produto['quantidade_estoque']; ?>)">
                                                    <i class="fas fa-plus me-1"></i> Add
                                                </button>
                                            </div>
                                            <small class="text-muted d-block text-end mt-1">
                                                Estoque: <?php echo $produto['quantidade_estoque']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="lead text-muted">Nenhum produto disponível para esta empresa.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- TAB: Histórico -->
                <div class="tab-pane fade" id="historico-pane">
                    <div class="data-card">
                        <h5><i class="fas fa-file-alt me-2"></i>Histórico de Orçamentos</h5>
                        <?php if (empty($historico_orcamentos)): ?>
                            <p class="text-muted lead mt-3">Você ainda não enviou nenhum orçamento.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nº</th>
                                            <th>Data</th>
                                            <th>Empresa</th>
                                            <th class="text-center">Itens</th>
                                            <th class="text-end">Valor Total</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">PDF</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historico_orcamentos as $orc): ?>
                                        <tr>
                                            <td><strong>#<?php echo $orc['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($orc['data_orcamento'])); ?></td>
                                            <td><?php echo htmlspecialchars($orc['nome_empresa'] ?? '-'); ?></td>
                                            <td class="text-center"><?php echo $orc['qtd_itens']; ?></td>
                                            <td class="text-end fw-bold">R$ <?php echo number_format($orc['valor_total'], 2, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <span class="status-badge status-<?php echo htmlspecialchars($orc['status_orcamento']); ?>">
                                                    <?php
                                                    $status_labels = [
                                                        'pendente' => 'Pendente',
                                                        'aprovado' => 'Aprovado',
                                                        'rejeitado' => 'Rejeitado',
                                                        'convertido_venda' => 'Convertido'
                                                    ];
                                                    echo $status_labels[$orc['status_orcamento']] ?? ucfirst($orc['status_orcamento']);
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="gerar_pdf_orcamento_cliente.php?token=<?php echo htmlspecialchars($token); ?>&id=<?php echo $orc['id']; ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Baixar PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Cart Button -->
    <?php if ($empresa_selecionada > 0): ?>
    <div class="floating-cart">
        <button class="btn btn-primary btn-lg rounded-circle" onclick="toggleCarrinho()" id="cart-button">
            <i class="fas fa-shopping-cart"></i>
            <span class="badge-cart" id="cart-count">0</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Cart Overlay -->
    <div class="cart-overlay" id="cart-overlay" onclick="toggleCarrinho()"></div>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="font-family: 'Poppins', sans-serif; font-weight: 600;">
                    <i class="fas fa-file-invoice me-2"></i>Seu Orçamento
                </h5>
                <button type="button" class="btn-close" onclick="toggleCarrinho()"></button>
            </div>
            <?php if ($nome_empresa_selecionada): ?>
                <small class="text-muted"><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($nome_empresa_selecionada); ?></small>
            <?php endif; ?>
        </div>
        <div class="cart-body" id="cart-content">
            <div class="text-center text-muted py-4">
                <i class="fas fa-file-invoice fa-3x mb-3" style="opacity:0.3;"></i>
                <p>Adicione produtos ao orçamento</p>
            </div>
        </div>
        <div class="cart-footer-area" id="cart-footer">
        </div>
    </div>

    <!-- Modal: Observações antes de enviar -->
    <div class="modal fade" id="modalEnviar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--primary); color: white;">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Enviar Orçamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-2 rounded" style="background: #f0f4ff; border: 1px solid #c7d2fe;">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-primary me-2" style="font-size:0.8em">CLI-<?php echo str_pad($cliente_id, 3, '0', STR_PAD_LEFT); ?></span>
                            <strong><?php echo htmlspecialchars($link_data['cliente_nome'] ?? ''); ?></strong>
                        </div>
                        <?php if (!empty($link_data['cpf_cnpj'])): ?>
                            <small class="text-muted"><i class="fas fa-id-card me-1"></i><?php echo ($link_data['tipo_pessoa'] === 'juridica' ? 'CNPJ' : 'CPF'); ?>: <?php echo htmlspecialchars($link_data['cpf_cnpj']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Seu Código ou CPF/CNPJ <small class="text-muted fw-normal">(opcional, para identificação)</small></label>
                        <input type="text" class="form-control" id="codigoIdentificacao" placeholder="Ex: CLI-001 ou 12.345.678/0001-00" value="">
                        <div id="feedbackIdentificacao" class="mt-2" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Forma de Pagamento Preferida</label>
                        <select class="form-select" id="formaPagamento">
                            <option value="">Sem preferência</option>
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                            <option value="faturamento">Faturamento</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="credito">Cartão de Crédito</option>
                            <option value="debito">Cartão de Débito</option>
                        </select>
                    </div>
                    <div class="mb-3" id="faturamentoTipoContainer" style="display:none;">
                        <label class="form-label fw-bold">Tipo de Faturamento</label>
                        <select class="form-select" id="tipoFaturamento">
                            <option value="avista">À vista</option>
                            <option value="7">7 dias</option>
                            <option value="15_dias">15 dias</option>
                            <option value="20_dias">20 dias</option>
                            <option value="30_dias">30 dias</option>
                            <option value="45_dias">45 dias</option>
                            <option value="60_dias">60 dias</option>
                            <option value="90_dias">90 dias</option>
                            <option value="15_30">15/30 dias</option>
                            <option value="20_30">20/30 dias</option>
                            <option value="30_60_90">30/60/90 dias</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Observações</label>
                        <textarea class="form-control" id="observacoes" rows="3" placeholder="Alguma observação sobre o orçamento? (opcional)"></textarea>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Após o envio, a equipe analisará seu orçamento e entrará em contato.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarEnvioOrcamento()">
                        <i class="fas fa-paper-plane me-1"></i> Enviar Orçamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-page">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Karla Wollinger. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const token = '<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>';
        const empresaSelecionada = <?php echo $empresa_selecionada; ?>;
        const clienteId = <?php echo $cliente_id; ?>;
        const clienteCodigo = 'CLI-<?php echo str_pad($cliente_id, 3, '0', STR_PAD_LEFT); ?>';
        const clienteNome = <?php echo json_encode($link_data['cliente_nome'] ?? ''); ?>;
        const clienteCpfCnpj = <?php echo json_encode($link_data['cpf_cnpj'] ?? ''); ?>;

        // Carrinho em localStorage (separado por empresa)
        const CART_KEY = 'orcamento_cart_' + empresaSelecionada;

        function getCarrinho() {
            try {
                return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
            } catch(e) {
                return [];
            }
        }

        function salvarCarrinho(cart) {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
        }

        // Toggle Sidebar
        function toggleCarrinho() {
            document.getElementById('cart-sidebar').classList.toggle('open');
            document.getElementById('cart-overlay').classList.toggle('show');
            atualizarCarrinhoVisual();
        }

        // Adicionar ao carrinho
        function adicionarAoCarrinho(id, nome, preco, estoque) {
            let cart = getCarrinho();
            const existing = cart.find(i => i.id === id);

            if (existing) {
                if (existing.qty < estoque) {
                    existing.qty++;
                } else {
                    mostrarNotificacao('Quantidade máxima em estoque!', 'warning');
                    return;
                }
            } else {
                cart.push({ id, nome, preco, estoque, qty: 1 });
            }

            salvarCarrinho(cart);
            atualizarContador();
            mostrarNotificacao('Produto adicionado!', 'success');
        }

        // Alterar quantidade
        function alterarQtd(id, delta) {
            let cart = getCarrinho();
            const item = cart.find(i => i.id === id);
            if (!item) return;

            item.qty += delta;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.id !== id);
            } else if (item.qty > item.estoque) {
                mostrarNotificacao('Estoque insuficiente!', 'warning');
                return;
            }

            salvarCarrinho(cart);
            atualizarCarrinhoVisual();
        }

        // Remover item
        function removerItem(id) {
            let cart = getCarrinho().filter(i => i.id !== id);
            salvarCarrinho(cart);
            atualizarCarrinhoVisual();
        }

        // Limpar carrinho
        function limparCarrinho() {
            if (confirm('Limpar todos os itens do orçamento?')) {
                salvarCarrinho([]);
                atualizarCarrinhoVisual();
            }
        }

        // Atualizar contador flutuante
        function atualizarContador() {
            const badge = document.getElementById('cart-count');
            if (!badge) return;
            const cart = getCarrinho();
            const total = cart.reduce((s, i) => s + i.qty, 0);
            badge.textContent = total;
            badge.style.display = total > 0 ? 'flex' : 'none';
        }

        // Atualizar visual do carrinho
        function atualizarCarrinhoVisual() {
            atualizarContador();
            const cartContent = document.getElementById('cart-content');
            const cartFooter = document.getElementById('cart-footer');
            if (!cartContent || !cartFooter) return;

            const cart = getCarrinho();

            if (cart.length === 0) {
                cartContent.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-file-invoice fa-3x mb-3" style="opacity:0.3;"></i>
                        <p>Adicione produtos ao orçamento</p>
                    </div>`;
                cartFooter.innerHTML = '';
                return;
            }

            let total = 0;
            let html = '';
            cart.forEach(item => {
                const sub = item.qty * item.preco;
                total += sub;
                html += `
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                    <div class="flex-grow-1">
                        <h6 class="mb-1" style="font-size:0.95rem;">${escapeHtml(item.nome)}</h6>
                        <span class="text-muted small">R$ ${formatMoney(item.preco)} × ${item.qty}</span>
                        <div class="d-flex align-items-center mt-2 gap-1">
                            <button class="btn btn-sm btn-outline-secondary" onclick="alterarQtd(${item.id}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="mx-2 fw-bold">${item.qty}</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="alterarQtd(${item.id}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-end ms-3">
                        <button class="btn btn-sm btn-outline-danger mb-1" onclick="removerItem(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                        <div class="fw-bold" style="color: var(--primary);">R$ ${formatMoney(sub)}</div>
                    </div>
                </div>`;
            });

            cartContent.innerHTML = html;

            cartFooter.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0" style="font-family:'Poppins',sans-serif;">Total:</h5>
                    <h5 class="mb-0" style="color: var(--primary); font-family:'Poppins',sans-serif;">R$ ${formatMoney(total)}</h5>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" onclick="abrirModalEnvio()">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Orçamento
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="limparCarrinho()">
                        <i class="fas fa-trash me-1"></i>Limpar
                    </button>
                </div>`;
        }

        // Abrir modal de envio
        function abrirModalEnvio() {
            const cart = getCarrinho();
            if (cart.length === 0) {
                mostrarNotificacao('Adicione produtos antes de enviar!', 'warning');
                return;
            }
            toggleCarrinho();
            const modal = new bootstrap.Modal(document.getElementById('modalEnviar'));
            modal.show();
        }

        // Mostrar/Ocultar campo de faturamento
        document.getElementById('formaPagamento').addEventListener('change', function() {
            const container = document.getElementById('faturamentoTipoContainer');
            container.style.display = this.value === 'faturamento' ? 'block' : 'none';
        });

        // Validação em tempo real do campo de código/CPF/CNPJ
        document.getElementById('codigoIdentificacao').addEventListener('input', function() {
            const valor = this.value.trim();
            const feedback = document.getElementById('feedbackIdentificacao');
            
            if (!valor) {
                feedback.style.display = 'none';
                this.classList.remove('is-valid', 'is-invalid');
                return;
            }

            const valorLimpo = valor.replace(/[.\-\/\s]/g, '').toLowerCase();
            const cpfCnpjLimpo = clienteCpfCnpj.replace(/[.\-\/\s]/g, '').toLowerCase();
            const codigoLimpo = clienteCodigo.toLowerCase();

            let match = false;

            // Verificar por código (CLI-XXX)
            if (codigoLimpo.includes(valorLimpo) || valorLimpo.includes(codigoLimpo)) {
                match = true;
            }
            // Verificar por CPF/CNPJ
            if (cpfCnpjLimpo && (cpfCnpjLimpo.includes(valorLimpo) || valorLimpo.includes(cpfCnpjLimpo))) {
                match = true;
            }
            // Verificar também se digitou o CPF/CNPJ com pontuação
            if (clienteCpfCnpj && clienteCpfCnpj.toLowerCase().includes(valor.toLowerCase())) {
                match = true;
            }

            feedback.style.display = 'block';
            if (match) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                feedback.innerHTML = '<div class="d-flex align-items-center p-2 rounded" style="background:#d1fae5; border:1px solid #6ee7b7;">' +
                    '<i class="fas fa-check-circle text-success me-2"></i>' +
                    '<div><strong class="text-success">Identificado!</strong><br>' +
                    '<span class="text-dark">' + clienteCodigo + ' — ' + escapeHtml(clienteNome) + '</span></div></div>';
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                feedback.innerHTML = '<div class="d-flex align-items-center p-2 rounded" style="background:#fee2e2; border:1px solid #fca5a5;">' +
                    '<i class="fas fa-exclamation-triangle text-danger me-2"></i>' +
                    '<span class="text-danger">Código ou documento não corresponde ao cadastro.</span></div>';
            }
        });

        // Enviar Orçamento
        function confirmarEnvioOrcamento() {
            const cart = getCarrinho();
            if (cart.length === 0) return;

            const formaPagamento = document.getElementById('formaPagamento').value || 'faturamento';
            const tipoFaturamento = document.getElementById('tipoFaturamento').value || 'avista';
            const observacoes = document.getElementById('observacoes').value.trim();
            const codigoIdentificacao = document.getElementById('codigoIdentificacao').value.trim();

            // Montar observações com código de identificação
            let obsCompleta = observacoes;
            if (codigoIdentificacao) {
                obsCompleta = '[Identificação: ' + codigoIdentificacao + ']\n' + obsCompleta;
            }

            const itens = cart.map(i => ({
                produto_id: i.id,
                quantidade: i.qty,
                preco_unitario: i.preco
            }));

            const btn = document.querySelector('#modalEnviar .btn-primary');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';

            fetch('orcamento_cliente_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'enviar_orcamento',
                    token: token,
                    empresa_id: empresaSelecionada,
                    forma_pagamento: formaPagamento,
                    tipo_faturamento: tipoFaturamento,
                    observacoes: obsCompleta,
                    itens: itens
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Limpar carrinho
                    salvarCarrinho([]);
                    // Redirecionar para sucesso
                    window.location.href = 'orcamento_cliente_sucesso.php?token=' + token + '&orcamento=' + data.orcamento_id;
                } else {
                    mostrarNotificacao(data.message || 'Erro ao enviar orçamento.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar Orçamento';
                }
            })
            .catch(err => {
                console.error(err);
                mostrarNotificacao('Erro de conexão. Tente novamente.', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar Orçamento';
            });
        }

        // Filtrar produtos
        function filtrarProdutos() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.produto-item').forEach(p => {
                const nome = p.getAttribute('data-nome') || '';
                const sku = p.getAttribute('data-sku') || '';
                p.style.display = (nome.includes(term) || sku.includes(term)) ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const si = document.getElementById('searchInput');
            if (si) si.addEventListener('input', filtrarProdutos);
            atualizarContador();
        });

        // Helpers
        function formatMoney(v) {
            return v.toFixed(2).replace('.', ',');
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function mostrarNotificacao(msg, tipo) {
            const n = document.createElement('div');
            n.className = 'alert alert-' + tipo + ' alert-dismissible fade show position-fixed';
            n.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:280px;';
            n.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(n);
            setTimeout(() => { if (n.parentNode) n.remove(); }, 3000);
        }
    </script>
</body>
</html>