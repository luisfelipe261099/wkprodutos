<?php
// Habilita a exibiÃ§Ã£o de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/session_bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

// --- FUNÃ‡Ã•ES DE GERAÃ‡ÃƒO DE RELATÃ“RIO ---

function gerarRelatorioVendasGeral($conn, $di, $df, $cid, $status, $eid) {
    if (!$conn || $conn->connect_error) {
        return ['dados' => [], 'resumo' => ['total_vendas' => 0, 'faturamento' => 0, 'lucro_bruto' => 0, 'margem_media' => 0]];
    }
    
    $sql = "SELECT v.id, v.data_venda, v.valor_total, v.status_venda, c.nome as cliente_nome,
                   COALESCE(SUM(iv.quantidade * iv.preco_unitario * (p.percentual_lucro / 100)), 0) as lucro_total
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN itens_venda iv ON v.id = iv.venda_id
            LEFT JOIN produtos p ON iv.produto_id = p.id
            WHERE v.data_venda BETWEEN ? AND ?";
    
    $data_inicio_completa = $di . ' 00:00:00';
    $data_fim_completa = $df . ' 23:59:59';
    
    $params = [$data_inicio_completa, $data_fim_completa];
    $types = "ss";

    if ($cid) { $sql .= " AND v.cliente_id = ?"; $params[] = $cid; $types .= "i"; }
    if ($status) { $sql .= " AND v.status_venda = ?"; $params[] = $status; $types .= "s"; }
    
    if ($eid) {
        $sql .= " AND v.id IN (SELECT DISTINCT venda_id FROM itens_venda iv_e JOIN produtos p_e ON iv_e.produto_id = p_e.id WHERE p_e.empresa_id = ?)";
        $params[] = $eid;
        $types .= "i";
    }

    $sql .= " GROUP BY v.id, c.nome ORDER BY v.data_venda DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $vendas_concluidas = array_filter($dados, function($v) { return $v['status_venda'] == 'concluida'; });
    $total_vendas = count($dados);
    $total_valor = array_sum(array_column($vendas_concluidas, 'valor_total'));
    $total_lucro = array_sum(array_column($vendas_concluidas, 'lucro_total'));

    return ['dados' => $dados, 'resumo' => ['total_vendas' => $total_vendas, 'faturamento' => $total_valor, 'lucro_bruto' => $total_lucro, 'margem_media' => $total_valor > 0 ? ($total_lucro / $total_valor) * 100 : 0]];
}

function gerarRelatorioProdutosVendidos($conn, $di, $df, $eid, $pid) {
    if (!$conn || $conn->connect_error) {
        return ['dados' => [], 'resumo' => ['total_faturamento' => 0, 'total_lucro' => 0, 'margem_geral' => 0, 'total_produtos' => 0]];
    }
    
    $sql = "SELECT p.id, p.nome as produto_nome, e.nome_empresa as empresa_nome,
                   SUM(iv.quantidade) as total_vendido,
                   SUM(iv.quantidade * iv.preco_unitario) as total_faturado,
                   SUM(iv.quantidade * iv.preco_unitario * (p.percentual_lucro / 100)) as total_lucro
            FROM itens_venda iv
            JOIN produtos p ON iv.produto_id = p.id
            LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
            JOIN vendas v ON iv.venda_id = v.id
            WHERE v.data_venda BETWEEN ? AND ? AND v.status_venda = 'concluida'";
    
    $data_inicio_completa = $di . ' 00:00:00';
    $data_fim_completa = $df . ' 23:59:59';

    $params = [$data_inicio_completa, $data_fim_completa];
    $types = "ss";

    if ($eid) { $sql .= " AND p.empresa_id = ?"; $params[] = $eid; $types .= "i"; }
    if ($pid) { $sql .= " AND p.id = ?"; $params[] = $pid; $types .= "i"; }
    
    $sql .= " GROUP BY p.id, p.nome, e.nome_empresa ORDER BY total_faturado DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_faturamento = array_sum(array_column($dados, 'total_faturado'));
    $total_lucro = array_sum(array_column($dados, 'total_lucro'));

    return ['dados' => $dados, 'resumo' => ['total_faturamento' => $total_faturamento, 'total_lucro' => $total_lucro, 'margem_geral' => $total_faturamento > 0 ? ($total_lucro / $total_faturamento) * 100 : 0, 'total_produtos' => count($dados)]];
}

function gerarRelatorioEmpresas($conn, $di, $df, $eid) {
    if (!$conn || $conn->connect_error) {
        return ['dados' => [], 'resumo' => ['faturamento_geral' => 0, 'lucro_geral' => 0, 'empresas_ativas' => 0]];
    }
    
    $sql = "SELECT 
                e.id as empresa_id,
                e.nome_empresa,
                COUNT(DISTINCT v.id) as total_vendas,
                SUM(iv.quantidade) as total_itens,
                COALESCE(SUM(iv.quantidade * iv.preco_unitario), 0) as total_faturado,
                COALESCE(SUM(iv.quantidade * iv.preco_unitario * (p.percentual_lucro / 100)), 0) as total_lucro
            FROM vendas v
            JOIN itens_venda iv ON v.id = iv.venda_id
            JOIN produtos p ON iv.produto_id = p.id
            JOIN empresas_representadas e ON p.empresa_id = e.id
            WHERE v.status_venda = 'concluida' AND v.data_venda BETWEEN ? AND ?";

    $data_inicio_completa = $di . ' 00:00:00';
    $data_fim_completa = $df . ' 23:59:59';
    
    $params = [$data_inicio_completa, $data_fim_completa];
    $types = "ss";

    if ($eid) {
        $sql .= " AND e.id = ?";
        $params[] = $eid;
        $types .= "i";
    }

    $sql .= " GROUP BY e.id, e.nome_empresa ORDER BY total_lucro DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $faturamento_geral = array_sum(array_column($dados, 'total_faturado'));
    $lucro_geral = array_sum(array_column($dados, 'total_lucro'));
    
    return ['dados' => $dados, 'resumo' => ['faturamento_geral' => $faturamento_geral, 'lucro_geral' => $lucro_geral, 'empresas_ativas' => count($dados)]];
}

// Verificar se estÃ¡ em modo PDF
if (isset($_GET['modo_pdf']) && $_GET['modo_pdf'] == '1') {
    // Processar parÃ¢metros GET para modo PDF
    $tipo_relatorio = $_GET["tipo_relatorio"] ?? '';
    $data_inicio = $_GET["data_inicio"] ?? '';
    $data_fim = $_GET["data_fim"] ?? '';
    $cliente_id = $_GET["cliente_id"] ?? null;
    $status_filtro = $_GET["status_filtro"] ?? null;
    $empresa_id = $_GET["empresa_id"] ?? null;
    $produto_id = $_GET["produto_id"] ?? null;
    
    if (!empty($tipo_relatorio) && !empty($data_inicio) && !empty($data_fim)) {
        // Gerar dados do relatÃ³rio
        switch ($tipo_relatorio) {
            case 'vendas_geral':
                $dados_relatorio = gerarRelatorioVendasGeral($conn, $data_inicio, $data_fim, $cliente_id, $status_filtro, $empresa_id);
                $titulo_relatorio = "RelatÃ³rio de Vendas Detalhadas";
                break;
            case 'produtos_vendidos':
                $dados_relatorio = gerarRelatorioProdutosVendidos($conn, $data_inicio, $data_fim, $empresa_id, $produto_id);
                $titulo_relatorio = "RelatÃ³rio de Produtos Vendidos";
                break;
            case 'lucro_por_empresa':
                $dados_relatorio = gerarRelatorioEmpresas($conn, $data_inicio, $data_fim, $empresa_id);
                $titulo_relatorio = "RelatÃ³rio de Lucratividade por Empresa";
                break;
        }
        
        if (!empty($dados_relatorio['dados'])) {
            // Buscar dados do usuÃ¡rio
            $usuario_id = $_SESSION["id"];
            $sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
            $stmt_usuario = $conn->prepare($sql_usuario);
            $stmt_usuario->bind_param("i", $usuario_id);
            $stmt_usuario->execute();
            $result_usuario = $stmt_usuario->get_result();
            $usuario = $result_usuario->fetch_assoc();
            $stmt_usuario->close();
            
            $periodo = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
            
            // Incluir template PDF
            include 'template_pdf_relatorio.php';
            $conn->close();
            exit;
        }
    }
    
    // Se chegou aqui, houve erro
    die('Erro: ParÃ¢metros insuficientes ou dados nÃ£o encontrados para gerar o relatÃ³rio.');
}

// --- Bloco Principal de Processamento ---
$relatorio_gerado = false;
$dados_relatorio = [];
$titulo_relatorio = '';
$tipo_relatorio = $_POST["tipo_relatorio"] ?? 'lucro_por_empresa';
$data_inicio = $_POST["data_inicio"] ?? date('Y-m-01');
$data_fim = $_POST["data_fim"] ?? date('Y-m-d');
$cliente_id = $_POST["cliente_id"] ?? null;
$status_filtro = $_POST["status_filtro"] ?? null;
$empresa_id = $_POST["empresa_id"] ?? null;
$produto_id = $_POST["produto_id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($tipo_relatorio)) {
    switch ($tipo_relatorio) {
        case 'vendas_geral':
            $dados_relatorio = gerarRelatorioVendasGeral($conn, $data_inicio, $data_fim, $cliente_id, $status_filtro, $empresa_id);
            $titulo_relatorio = "RelatÃ³rio de Vendas Detalhadas";
            break;
        case 'produtos_vendidos':
            $dados_relatorio = gerarRelatorioProdutosVendidos($conn, $data_inicio, $data_fim, $empresa_id, $produto_id);
            $titulo_relatorio = "RelatÃ³rio de Produtos Vendidos";
            break;
        case 'lucro_por_empresa':
            $dados_relatorio = gerarRelatorioEmpresas($conn, $data_inicio, $data_fim, $empresa_id);
            $titulo_relatorio = "RelatÃ³rio de Lucratividade por Empresa";
            break;
    }
    $relatorio_gerado = true;
}

// Buscar dados para os filtros
try {
    $clientes_options = $conn->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
    $empresas_options = $conn->query("SELECT id, nome_empresa FROM empresas_representadas ORDER BY nome_empresa ASC");
} catch (Exception $e) {
    error_log("Erro ao buscar dados dos filtros: " . $e->getMessage());
    $clientes_options = false;
    $empresas_options = false;
}

include_once 'includes/header.php';
?>

<!-- CSS especializado para impressÃ£o de relatÃ³rios -->
<style>
@media print {
    /* Ocultar elementos desnecessÃ¡rios na impressÃ£o */
    .page-header, .modern-card:first-of-type, .no-print,
    .btn, button, .navbar, .sidebar, .footer {
        display: none !important;
    }
    
    /* Configurar pÃ¡gina para impressÃ£o */
    body {
        margin: 0;
        padding: 15px;
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #000;
        background: white;
    }
    
    /* CabeÃ§alho do relatÃ³rio para impressÃ£o */
    .relatorio-cabecalho {
        display: block !important;
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    
    .relatorio-cabecalho h1 {
        margin: 0;
        font-size: 20px;
        color: #000;
    }
    
    .relatorio-cabecalho h2 {
        margin: 5px 0;
        font-size: 14px;
        color: #666;
        font-weight: normal;
    }
    
    /* Ajustar cards de resumo para impressÃ£o */
    .stats-card {
        border: 1px solid #ccc !important;
        margin-bottom: 10px;
        page-break-inside: avoid;
        display: inline-block !important;
        width: 23% !important;
        margin-right: 2% !important;
        vertical-align: top !important;
        box-sizing: border-box !important;
    }
    
    .row.g-4 {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 10px !important;
    }
    
    .row.g-4 > div {
        flex: 1 !important;
        min-width: 150px !important;
        max-width: 23% !important;
    }
    
    /* Melhorar tabelas para impressÃ£o */
    table {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 10px;
        page-break-inside: auto;
    }
    
    th, td {
        border: 1px solid #333 !important;
        padding: 4px;
        text-align: left;
    }
    
    th {
        background-color: #f0f0f0 !important;
        font-weight: bold;
        text-align: center;
    }
    
    /* RodapÃ© para impressÃ£o */
    .relatorio-rodape {
        display: block !important;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #333;
        font-size: 10px;
        text-align: center;
    }
    
    /* InformaÃ§Ãµes de comissÃ£o destacadas */
    .comissao-info {
        display: block !important;
        background-color: #f8f8f8 !important;
        border: 1px solid #333;
        padding: 15px;
        margin-top: 20px;
        page-break-inside: avoid;
    }
    
    .comissao-info h3 {
        margin-top: 0;
        font-size: 14px;
        text-align: center;
    }
}

/* Estilos para o modo de visualizaÃ§Ã£o normal */
.relatorio-cabecalho, .relatorio-rodape, .comissao-info {
    display: none;
}

@media print {
    .relatorio-cabecalho, .relatorio-rodape, .comissao-info {
        display: block !important;
    }
}
</style>

<?php
// Buscar dados do usuÃ¡rio logado para o cabeÃ§alho
if ($relatorio_gerado) {
    try {
        $usuario_id = $_SESSION["id"];
        $sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
        $stmt_usuario = $conn->prepare($sql_usuario);
        
        if ($stmt_usuario) {
            $stmt_usuario->bind_param("i", $usuario_id);
            $stmt_usuario->execute();
            $result_usuario = $stmt_usuario->get_result();
            $usuario_dados = $result_usuario->fetch_assoc();
            $stmt_usuario->close();
        } else {
            $usuario_dados = ['nome' => 'UsuÃ¡rio nÃ£o identificado'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar dados do usuÃ¡rio: " . $e->getMessage());
        $usuario_dados = ['nome' => 'Erro ao carregar usuÃ¡rio'];
    }
    
    $periodo_formatado = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
}
?>

<div class="page-header fade-in-up">
    <h1 class="page-title"><i class="fas fa-chart-bar"></i> Central de RelatÃ³rios</h1>
    <p class="page-subtitle">Analise a performance do seu negÃ³cio com relatÃ³rios detalhados.</p>
</div>

<div class="modern-card fade-in-up">
    <div class="card-body-modern">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-3">
                    <label for="tipo_relatorio" class="form-label fw-bold">1. Tipo de RelatÃ³rio</label>
                    <select class="form-select" id="tipo_relatorio" name="tipo_relatorio" required>
                        <option value="lucro_por_empresa" <?php echo ($tipo_relatorio == 'lucro_por_empresa' ? 'selected' : ''); ?>>ðŸ¢ Lucro por Empresa</option>
                        <option value="produtos_vendidos" <?php echo ($tipo_relatorio == 'produtos_vendidos' ? 'selected' : ''); ?>>ðŸ“¦ Produtos Vendidos</option>
                        <option value="vendas_geral" <?php echo ($tipo_relatorio == 'vendas_geral' ? 'selected' : ''); ?>>ðŸ“Š Vendas Detalhadas</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label for="data_inicio" class="form-label fw-bold">2. Data InÃ­cio</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>" required>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label for="data_fim" class="form-label fw-bold">3. Data Fim</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>" required>
                </div>
                
                <div class="col-md-4 col-lg-3" id="filtro_empresa_div">
                    <label for="empresa_id" class="form-label">Filtrar por Empresa</label>
                    <select class="form-select" id="empresa_id" name="empresa_id">
                        <option value="">Todas as Empresas</option>
                        <?php 
                        if($empresas_options) { 
                            while($e = $empresas_options->fetch_assoc()) {
                                echo "<option value='{$e['id']}' ".($empresa_id == $e['id'] ? 'selected' : '').">".htmlspecialchars($e['nome_empresa'])."</option>"; 
                            }
                        } 
                        ?>
                    </select>
                </div>

                <div class="col-md-4 col-lg-3" id="filtro_cliente_div">
                    <label for="cliente_id" class="form-label">Filtrar por Cliente</label>
                    <select class="form-select" id="cliente_id" name="cliente_id">
                        <option value="">Todos os Clientes</option>
                        <?php 
                        if($clientes_options) { 
                            $clientes_options->data_seek(0); 
                            while($c = $clientes_options->fetch_assoc()) {
                                echo "<option value='{$c['id']}' ".($cliente_id == $c['id'] ? 'selected' : '').">".htmlspecialchars($c['nome'])."</option>"; 
                            }
                        } 
                        ?>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2" id="filtro_status_div">
                    <label for="status_filtro" class="form-label">Status da Venda</label>
                    <select class="form-select" name="status_filtro" id="status_filtro">
                        <option value="">Todos os Status</option>
                        <option value="concluida" <?php echo ($status_filtro == 'concluida' ? 'selected' : ''); ?>>ConcluÃ­da</option>
                        <option value="pendente" <?php echo ($status_filtro == 'pendente' ? 'selected' : ''); ?>>Pendente</option>
                        <option value="cancelada" <?php echo ($status_filtro == 'cancelada' ? 'selected' : ''); ?>>Cancelada</option>
                    </select>
                </div>
                
                <div class="col-md-12 col-lg-2 d-grid">
                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-search me-2"></i>Gerar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($relatorio_gerado && !empty($dados_relatorio) && !empty($dados_relatorio['dados'])): ?>

<!-- CabeÃ§alho para impressÃ£o -->
<div class="relatorio-cabecalho">
    <h1><?php echo htmlspecialchars($titulo_relatorio); ?></h1>
    <h2>RelatÃ³rio Comercial para PrestaÃ§Ã£o de Contas</h2>
    <p><strong>PerÃ­odo:</strong> <?php echo $periodo_formatado; ?></p>
    <p><strong>Representante Comercial:</strong> <?php echo htmlspecialchars($usuario_dados['nome'] ?? 'N/A'); ?></p>
    <p><strong>Data de GeraÃ§Ã£o:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
</div>

<div class="modern-card fade-in-up mt-4">
    <div class="card-header-modern">
        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($titulo_relatorio); ?>
        <div class="ms-auto">
            <button onclick="imprimirRelatorio()" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf me-1"></i>Gerar PDF</button>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="row g-4 mb-4">
            <?php if ($tipo_relatorio == 'lucro_por_empresa'): ?>
                <div class="col-6 col-lg-4"><div class="stats-card success"><div class="stats-icon"><i class="fas fa-dollar-sign"></i></div><div class="stats-value">R$ <?php echo number_format($dados_relatorio['resumo']['lucro_geral'], 2, ',', '.'); ?></div><div class="stats-label">Lucro Bruto Total</div></div></div>
                <div class="col-6 col-lg-4"><div class="stats-card primary"><div class="stats-icon"><i class="fas fa-chart-line"></i></div><div class="stats-value">R$ <?php echo number_format($dados_relatorio['resumo']['faturamento_geral'], 2, ',', '.'); ?></div><div class="stats-label">Faturamento Total</div></div></div>
                <div class="col-12 col-lg-4"><div class="stats-card info"><div class="stats-icon"><i class="fas fa-building"></i></div><div class="stats-value"><?php echo $dados_relatorio['resumo']['empresas_ativas']; ?></div><div class="stats-label">Empresas com Vendas</div></div></div>
            <?php elseif ($tipo_relatorio == 'vendas_geral'): ?>
                <div class="col-6 col-lg-3"><div class="stats-card primary"><div class="stats-icon"><i class="fas fa-shopping-cart"></i></div><div class="stats-value"><?php echo $dados_relatorio['resumo']['total_vendas']; ?></div><div class="stats-label">Vendas no PerÃ­odo</div></div></div>
                <div class="col-6 col-lg-3"><div class="stats-card success"><div class="stats-icon"><i class="fas fa-dollar-sign"></i></div><div class="stats-value">R$ <?php echo number_format($dados_relatorio['resumo']['faturamento'], 2, ',', '.'); ?></div><div class="stats-label">Faturamento (ConcluÃ­das)</div></div></div>
                <div class="col-6 col-lg-3"><div class="stats-card info"><div class="stats-icon"><i class="fas fa-chart-pie"></i></div><div class="stats-value">R$ <?php echo number_format($dados_relatorio['resumo']['lucro_bruto'], 2, ',', '.'); ?></div><div class="stats-label">Lucro Bruto (ConcluÃ­das)</div></div></div>
                <div class="col-6 col-lg-3"><div class="stats-card warning"><div class="stats-icon"><i class="fas fa-percentage"></i></div><div class="stats-value"><?php echo number_format($dados_relatorio['resumo']['margem_media'], 1, ',', '.'); ?>%</div><div class="stats-label">Margem MÃ©dia</div></div></div>
            <?php elseif ($tipo_relatorio == 'produtos_vendidos'): ?>
                <div class="col-6 col-lg-3"><div class="stats-card success"><div class="stats-icon"><i class="fas fa-dollar-sign"></i></div><div class="stats-value">R$ <?php echo number_format($dados_relatorio['resumo']['total_faturamento'], 2, ',', '.'); ?></div><div class="stats-label">Faturamento Total</div></div></div>
                <div class="col-6 col-lg-3"><div class="stats-card info"><div class="stats-icon"><i class="fas fa-chart-pie"></i></div><div class="stats-value">R$ <?php echo number_format($dados_relatorio['resumo']['total_lucro'], 2, ',', '.'); ?></div><div class="stats-label">Lucro Total</div></div></div>
                <div class="col-6 col-lg-3"><div class="stats-card warning"><div class="stats-icon"><i class="fas fa-percentage"></i></div><div class="stats-value"><?php echo number_format($dados_relatorio['resumo']['margem_geral'], 1, ',', '.'); ?>%</div><div class="stats-label">Margem Geral</div></div></div>
                <div class="col-6 col-lg-3"><div class="stats-card primary"><div class="stats-icon"><i class="fas fa-boxes"></i></div><div class="stats-value"><?php echo $dados_relatorio['resumo']['total_produtos']; ?></div><div class="stats-label">Produtos Distintos</div></div></div>
            <?php endif; ?>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <?php if($tipo_relatorio == 'lucro_por_empresa'): ?>
                            <th>Empresa Representada</th><th class="text-end">NÂº de Vendas</th><th class="text-end">Itens Vendidos</th><th class="text-end">Faturamento</th><th class="text-end">Lucro Bruto</th><th class="text-end">Margem</th>
                        <?php elseif($tipo_relatorio == 'vendas_geral'): ?>
                            <th>Venda</th><th>Data</th><th>Cliente</th><th class="text-end">Faturamento</th><th class="text-end">Lucro</th><th class="text-end">Margem</th><th>Status</th>
                        <?php elseif($tipo_relatorio == 'produtos_vendidos'): ?>
                            <th>Produto</th><th>Empresa</th><th class="text-end">Qtd.</th><th class="text-end">Faturamento</th><th class="text-end">Lucro</th><th class="text-end">Margem</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados_relatorio['dados'] as $row): ?>
                        <tr>
                        <?php if($tipo_relatorio == 'lucro_por_empresa'): ?>
                            <td><?php echo htmlspecialchars($row['nome_empresa']); ?></td>
                            <td class="text-end"><?php echo $row['total_vendas']; ?></td>
                            <td class="text-end"><?php echo $row['total_itens']; ?></td>
                            <td class="text-end">R$ <?php echo number_format($row['total_faturado'], 2, ',', '.'); ?></td>
                            <td class="text-end fw-bold text-success">R$ <?php echo number_format($row['total_lucro'], 2, ',', '.'); ?></td>
                            <td class="text-end"><?php echo $row['total_faturado'] > 0 ? number_format(($row['total_lucro'] / $row['total_faturado']) * 100, 1) . '%' : 'N/A'; ?></td>
                        <?php elseif($tipo_relatorio == 'vendas_geral'): ?>
                            <td>#<?php echo $row['id']; ?></td><td><?php echo date('d/m/Y', strtotime($row['data_venda'])); ?></td><td><?php echo htmlspecialchars($row['cliente_nome']); ?></td>
                            <td class="text-end">R$ <?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                            <td class="text-end text-success">R$ <?php echo number_format($row['lucro_total'], 2, ',', '.'); ?></td>
                            <td class="text-end"><?php echo $row['valor_total'] > 0 ? number_format(($row['lucro_total'] / $row['valor_total']) * 100, 1) : 0; ?>%</td>
                            <td><span class="badge bg-<?php echo ($row['status_venda'] == 'concluida') ? 'success' : (($row['status_venda'] == 'pendente') ? 'warning text-dark' : 'danger'); ?>"><?php echo ucfirst($row['status_venda']); ?></span></td>
                        <?php elseif($tipo_relatorio == 'produtos_vendidos'): ?>
                            <td><?php echo htmlspecialchars($row['produto_nome']); ?></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($row['empresa_nome']); ?></small></td>
                            <td class="text-end fw-bold"><?php echo $row['total_vendido']; ?></td>
                            <td class="text-end">R$ <?php echo number_format($row['total_faturado'], 2, ',', '.'); ?></td>
                            <td class="text-end text-success">R$ <?php echo number_format($row['total_lucro'], 2, ',', '.'); ?></td>
                            <td class="text-end"><?php echo $row['total_faturado'] > 0 ? number_format(($row['total_lucro'] / $row['total_faturado']) * 100, 1) . '%' : 'N/A'; ?></td>
                        <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- InformaÃ§Ãµes bancÃ¡rias para impressÃ£o -->
<div class="payment-section">
    <h3>ðŸ’³ INFORMAÃ‡Ã•ES BANCÃRIAS PARA PAGAMENTO</h3>
    
    <div class="bank-info">
        <div class="bank-item">
            <span class="bank-label">ðŸ¦ Banco:</span>
            <span class="bank-value">INTER - 077</span>
        </div>
        <div class="bank-item">
            <span class="bank-label">ðŸ‘¤ BeneficiÃ¡rio:</span>
            <span class="bank-value">KARLA WOLLINGER DOS SANTOS</span>
        </div>
        <div class="bank-item">
            <span class="bank-label">ðŸ¢ CNPJ:</span>
            <span class="bank-value">30.459.625/0001-87</span>
        </div>
        <div class="bank-item">
            <span class="bank-label">ðŸª AgÃªncia:</span>
            <span class="bank-value">0001</span>
        </div>
        <div class="bank-item">
            <span class="bank-label">ðŸ’° Conta Corrente:</span>
            <span class="bank-value">44337759-6</span>
        </div>
        <div class="bank-item">
            <span class="bank-label">ðŸ”‘ Chave PIX:</span>
            <span class="bank-value">30.459.625/0001-87</span>
        </div>
    </div>
    
    <div class="commission-note">
        <p><strong>ðŸš€ InstruÃ§Ãµes para Pagamento:</strong> Utilize as informaÃ§Ãµes bancÃ¡rias acima para realizar o pagamento das comissÃµes referentes ao perÃ­odo demonstrado neste relatÃ³rio. Para pagamentos via PIX, utilize o CNPJ como chave. Mantenha este relatÃ³rio como comprovante das transaÃ§Ãµes comerciais realizadas.</p>
    </div>
</div>

<!-- RodapÃ© para impressÃ£o -->
<div class="relatorio-rodape">
    <p><strong>RelatÃ³rio gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?> | <strong>Total de registros:</strong> <?php echo count($dados_relatorio['dados']); ?></p>
</div>
<?php elseif ($relatorio_gerado): ?>
<div class="modern-card fade-in-up mt-4">
    <div class="card-body-modern text-center">
        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
        <h4>Nenhum dado encontrado</h4>
        <p class="text-muted">NÃ£o hÃ¡ registros que correspondam aos filtros selecionados para o perÃ­odo de <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?>.</p>
    </div>
</div>
<?php endif; ?>

<?php 
// Fechar conexÃ£o com o banco de dados
if (isset($conn) && $conn) {
    $conn->close();
}
?>

<?php include_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoRelatorioSelect = document.getElementById('tipo_relatorio');
    const filtroClienteDiv = document.getElementById('filtro_cliente_div');
    const filtroEmpresaDiv = document.getElementById('filtro_empresa_div');
    const filtroStatusDiv = document.getElementById('filtro_status_div');

    function toggleFilters() {
        const tipo = tipoRelatorioSelect.value;
        
        // Esconde todos por padrÃ£o para recomeÃ§ar
        filtroClienteDiv.style.display = 'none';
        filtroEmpresaDiv.style.display = 'none';
        filtroStatusDiv.style.display = 'none';

        if (tipo === 'vendas_geral') {
            filtroClienteDiv.style.display = 'block';
            filtroEmpresaDiv.style.display = 'block';
            filtroStatusDiv.style.display = 'block';
        } else if (tipo === 'produtos_vendidos' || tipo === 'lucro_por_empresa') {
            // Filtro de empresa Ã© relevante para estes dois relatÃ³rios
            filtroEmpresaDiv.style.display = 'block';
        }
    }

    toggleFilters();
    tipoRelatorioSelect.addEventListener('change', toggleFilters);
});

function imprimirRelatorio() {
    // Criar uma nova janela com apenas o conteÃºdo do relatÃ³rio
    const printWindow = window.open('', '_blank');
    const relatorioContent = document.querySelector('.modern-card.fade-in-up.mt-4');
    const cabecalho = document.querySelector('.relatorio-cabecalho');
    const paymentSection = document.querySelector('.payment-section');
    const comissaoInfo = document.querySelector('.comissao-info');
    const rodape = document.querySelector('.relatorio-rodape');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>RelatÃ³rio - PDF</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    font-size: 12px;
                    line-height: 1.5;
                    margin: 20px;
                    color: #2c3e50;
                    background-color: #ffffff;
                }
                
                .cabecalho {
                    text-align: center;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px 20px;
                    margin: -20px -20px 30px -20px;
                    border-radius: 0 0 15px 15px;
                }
                
                .cabecalho h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 300;
                    letter-spacing: 1px;
                }
                
                .cabecalho h2 {
                    margin: 10px 0 0 0;
                    font-size: 16px;
                    font-weight: 300;
                    opacity: 0.9;
                }
                
                .company-info {
                    background-color: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 20px;
                    margin-bottom: 25px;
                    border-radius: 0 8px 8px 0;
                }
                
                .stats-card {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border: 2px solid #e9ecef;
                    border-radius: 12px;
                    padding: 15px;
                    margin-bottom: 15px;
                    text-align: center;
                    display: inline-block;
                    width: 23%;
                    margin-right: 2%;
                    vertical-align: top;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                /* Layout de grid para os cards */
                .row.g-4 {
                    display: grid !important;
                    grid-template-columns: repeat(4, 1fr) !important;
                    gap: 15px !important;
                    margin-bottom: 25px !important;
                }
                
                .row.g-4 > div {
                    width: auto !important;
                    margin: 0 !important;
                }
                
                @media (max-width: 800px) {
                    .row.g-4 {
                        grid-template-columns: repeat(2, 1fr) !important;
                    }
                }
                
                @media (max-width: 500px) {
                    .row.g-4 {
                        grid-template-columns: 1fr !important;
                    }
                }
                
                .stats-value {
                    font-size: 18px;
                    font-weight: 700;
                    color: #2c3e50;
                    margin-bottom: 5px;
                    line-height: 1.1;
                }
                
                .stats-label {
                    font-size: 11px;
                    color: #6c757d;
                    margin-top: 5px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-weight: 600;
                    line-height: 1.2;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    font-size: 11px;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                
                th, td {
                    padding: 12px 8px;
                    text-align: left;
                    border-bottom: 1px solid #e9ecef;
                }
                
                th {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    font-weight: 600;
                    text-align: center;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-size: 10px;
                }
                
                tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                
                .text-end, .text-right {
                    text-align: right;
                }
                
                .text-center {
                    text-align: center;
                }
                
                .payment-section {
                    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                    border: 2px solid #28a745;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 30px 0;
                    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
                }
                
                .payment-section h3 {
                    margin: 0 0 20px 0;
                    color: #155724;
                    font-size: 18px;
                    text-align: center;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                
                .bank-info {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                    margin-bottom: 20px;
                }
                
                .bank-item {
                    background-color: #ffffff;
                    border: 1px solid #b3d4fc;
                    border-radius: 8px;
                    padding: 12px 15px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    min-height: 50px;
                    box-sizing: border-box;
                }
                
                .bank-label {
                    font-weight: 600;
                    color: #495057;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    white-space: nowrap;
                    margin-right: 10px;
                }
                
                .bank-value {
                    font-weight: 700;
                    color: #155724;
                    font-size: 12px;
                    font-family: 'Courier New', monospace;
                    text-align: right;
                    word-break: break-all;
                    flex: 1;
                }
                
                @media (max-width: 600px) {
                    .bank-info {
                        grid-template-columns: 1fr;
                    }
                }
                
                .comissao-info {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 8px;
                    padding: 20px;
                    margin-top: 30px;
                }
                
                .comissao-info h3 {
                    margin-top: 0;
                    color: #856404;
                    text-align: center;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                
                .commission-note {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 8px;
                    padding: 15px;
                    margin-top: 20px;
                    text-align: center;
                }
                
                .commission-note p {
                    margin: 0;
                    color: #856404;
                    font-style: italic;
                    font-size: 13px;
                    line-height: 1.6;
                }
                
                .rodape {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #dee2e6;
                    font-size: 10px;
                    color: #6c757d;
                    text-align: center;
                }
                
                .btn, .card-header-modern, .fas {
                    display: none;
                }
                
                @media print {
                    body { 
                        margin: 0; 
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
    `);
    
    // Adicionar cabeÃ§alho
    if (cabecalho) {
        printWindow.document.write('<div class="cabecalho">' + cabecalho.innerHTML + '</div>');
    }
    
    // Adicionar conteÃºdo do relatÃ³rio (sem os botÃµes)
    if (relatorioContent) {
        let content = relatorioContent.innerHTML;
        // Remover botÃµes
        content = content.replace(/<button[^>]*>.*?<\/button>/gi, '');
        content = content.replace(/<div class="ms-auto">.*?<\/div>/gi, '');
        printWindow.document.write(content);
    }
    
    // Adicionar informaÃ§Ãµes bancÃ¡rias
    if (paymentSection) {
        printWindow.document.write(paymentSection.outerHTML);
    }
    
    // Adicionar informaÃ§Ãµes de comissÃ£o
    if (comissaoInfo) {
        printWindow.document.write(comissaoInfo.outerHTML);
    }
    
    // Adicionar rodapÃ©
    if (rodape) {
        printWindow.document.write('<div class="rodape">' + rodape.innerHTML + '</div>');
    }
    
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    // Aguardar o carregamento e imprimir
    printWindow.onload = function() {
        printWindow.print();
    };
}
</script>
