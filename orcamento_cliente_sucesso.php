<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/db_connect.php';

$token = $_GET['token'] ?? '';
$orcamento_id = isset($_GET['orcamento']) ? (int)$_GET['orcamento'] : 0;

if (empty($token) || $orcamento_id <= 0) {
    die('Dados inválidos.');
}

// Validar token
$sql_token = "SELECT ml.cliente_id, c.nome as cliente_nome
              FROM marketplace_links ml
              LEFT JOIN clientes c ON ml.cliente_id = c.id
              WHERE ml.token_acesso = ? AND ml.ativo = 1";
$stmt = $conn->prepare($sql_token);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Token inválido.');
}

$link_data = $result->fetch_assoc();
$cliente_id = (int)$link_data['cliente_id'];
$stmt->close();

// Buscar orçamento (garantir que pertence ao cliente)
$sql_orc = "SELECT o.*, e.nome_empresa
            FROM orcamentos o
            LEFT JOIN empresas_representadas e ON o.empresa_id = e.id
            WHERE o.id = ? AND o.cliente_id = ?";
$stmt_orc = $conn->prepare($sql_orc);
$stmt_orc->bind_param("ii", $orcamento_id, $cliente_id);
$stmt_orc->execute();
$result_orc = $stmt_orc->get_result();

if ($result_orc->num_rows === 0) {
    die('Orçamento não encontrado.');
}

$orcamento = $result_orc->fetch_assoc();
$stmt_orc->close();

// Buscar itens
$sql_itens = "SELECT io.*, p.nome as produto_nome, p.sku
              FROM itens_orcamento io
              LEFT JOIN produtos p ON io.produto_id = p.id
              WHERE io.orcamento_id = ?";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $orcamento_id);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();

$itens = [];
while ($item = $result_itens->fetch_assoc()) {
    $itens[] = $item;
}
$stmt_itens->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento Enviado - Karla Wollinger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #334155;
            --accent: #f97316;
            --success: #10b981;
            --light: #f3f4f6;
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
        .main-wrapper { flex-grow: 1; display: flex; align-items: center; }
        .success-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 2rem;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success), #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s ease;
        }
        .success-icon i { font-size: 2.5rem; color: white; }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .success-card h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--secondary);
        }
        .detail-box {
            background: var(--light);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: left;
            margin-top: 1.5rem;
        }
        .detail-row { display: flex; justify-content: space-between; padding: 0.4rem 0; }
        .detail-row .label { color: #64748b; font-weight: 500; }
        .detail-row .value { font-weight: 600; }
        .item-list {
            text-align: left;
            margin-top: 1rem;
        }
        .item-list table { width: 100%; }
        .item-list th { font-size: 0.8rem; color: #64748b; text-transform: uppercase; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; }
        .item-list td { padding: 0.5rem; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }
        .total-row td { font-weight: 700; border-top: 2px solid var(--primary); color: var(--primary); }
        .btn-primary-custom {
            background: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 0.7rem 2rem;
            font-weight: 600;
            color: white;
        }
        .btn-primary-custom:hover { background: #1d4ed8; color: white; }
        .footer-page {
            background: var(--secondary);
            color: #94a3b8;
            padding: 1.5rem 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="success-container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Orçamento Enviado!</h2>
                <p class="text-muted mb-0">
                    Obrigado, <strong><?php echo htmlspecialchars($link_data['cliente_nome']); ?></strong>!
                    Seu orçamento foi recebido com sucesso.
                </p>
                <p class="text-muted">Nossa equipe irá analisar e entrar em contato em breve.</p>

                <div class="detail-box">
                    <div class="detail-row">
                        <span class="label">Nº do Orçamento</span>
                        <span class="value">#<?php echo $orcamento_id; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Empresa</span>
                        <span class="value"><?php echo htmlspecialchars($orcamento['nome_empresa'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Data</span>
                        <span class="value"><?php echo date('d/m/Y H:i', strtotime($orcamento['data_orcamento'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Forma de Pgto.</span>
                        <span class="value"><?php echo ucfirst(htmlspecialchars($orcamento['forma_pagamento'] ?? '-')); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status</span>
                        <span class="badge" style="background: #facc15; color: #333;">Pendente</span>
                    </div>
                </div>

                <div class="item-list">
                    <table>
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item['produto_nome']); ?>
                                    <?php if (!empty($item['sku'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['sku']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $item['quantidade']; ?></td>
                                <td class="text-end">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                <td class="text-end">R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="3">Total</td>
                                <td class="text-end">R$ <?php echo number_format($orcamento['valor_total'], 2, ',', '.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-center gap-3 flex-wrap">
                    <a href="orcamento_cliente.php?token=<?php echo htmlspecialchars($token); ?>" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-1"></i> Novo Orçamento
                    </a>
                    <a href="gerar_pdf_orcamento_cliente.php?token=<?php echo htmlspecialchars($token); ?>&id=<?php echo $orcamento_id; ?>" class="btn btn-primary-custom" style="background: #dc2626;" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> Baixar PDF
                    </a>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimir
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
</body>
</html>