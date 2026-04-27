<?php
require_once __DIR__ . '/includes/session_bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';

$resultados = [];

// Função auxiliar
function columnExists(mysqli $conn, string $table, string $column): bool {
    $sql = "SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

// --- MIGRAÇÃO 1: Adicionar prazo_pagamento na tabela vendas ---
if (!columnExists($conn, 'vendas', 'prazo_pagamento')) {
    $sql = "ALTER TABLE `vendas` ADD COLUMN `prazo_pagamento` VARCHAR(20) NULL DEFAULT NULL AFTER `forma_pagamento`";
    if ($conn->query($sql)) {
        $resultados[] = ['status' => 'ok', 'msg' => 'Coluna <code>prazo_pagamento</code> adicionada na tabela <strong>vendas</strong>.'];
    } else {
        $resultados[] = ['status' => 'erro', 'msg' => 'Erro ao adicionar <code>prazo_pagamento</code>: ' . htmlspecialchars($conn->error)];
    }
} else {
    $resultados[] = ['status' => 'info', 'msg' => 'Coluna <code>prazo_pagamento</code> já existe na tabela <strong>vendas</strong>. Nada a fazer.'];
}

// --- MIGRAÇÃO 2: Adicionar responsavel_id na tabela vendas (se necessário) ---
if (!columnExists($conn, 'vendas', 'responsavel')) {
    // Verifica se já existe alguma coluna responsavel
    $resultados[] = ['status' => 'info', 'msg' => 'Coluna <code>responsavel</code> não encontrada — pulando (não é necessária para o fluxo atual).'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Migração do Banco de Dados</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            margin: 0;
            padding: 40px 20px;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            padding: 36px 40px;
            max-width: 680px;
            width: 100%;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 8px;
            color: #222;
        }
        p.sub { color: #666; margin-bottom: 28px; font-size: 0.95rem; }
        .item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 7px;
            margin-bottom: 12px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .item.ok    { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .item.info  { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .item.erro  { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 2px; }
        .actions { margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            display: inline-block;
            padding: 10px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: #0d6efd; color: #fff; }
        .btn-success { background: #198754; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn:hover { opacity: 0.88; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔧 Migração do Banco de Dados</h1>
    <p class="sub">Executado automaticamente em <?php echo date('d/m/Y H:i:s'); ?></p>

    <?php foreach ($resultados as $r): ?>
        <div class="item <?php echo htmlspecialchars($r['status']); ?>">
            <span class="icon">
                <?php if ($r['status'] === 'ok') echo '✅';
                      elseif ($r['status'] === 'erro') echo '❌';
                      else echo 'ℹ️'; ?>
            </span>
            <span><?php echo $r['msg']; ?></span>
        </div>
    <?php endforeach; ?>

    <?php
    $temErro = array_filter($resultados, fn($r) => $r['status'] === 'erro');
    if (empty($temErro)): ?>
        <div class="item ok" style="margin-top:20px; font-weight:bold;">
            <span class="icon">🎉</span>
            <span>Banco de dados atualizado com sucesso! Pode voltar a usar o sistema normalmente.</span>
        </div>
    <?php else: ?>
        <div class="item erro" style="margin-top:20px; font-weight:bold;">
            <span class="icon">⚠️</span>
            <span>Uma ou mais migrações falharam. Verifique os erros acima.</span>
        </div>
    <?php endif; ?>

    <div class="actions">
        <a href="vendas.php" class="btn btn-primary">Ir para Vendas</a>
        <a href="registrar_venda.php" class="btn btn-success">Registrar Venda</a>
        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
    </div>
</div>
</body>
</html>
