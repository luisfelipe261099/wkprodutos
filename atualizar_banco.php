<?php
/**
 * Script para atualizar estrutura do banco de dados
 * Adiciona 'boleto' e novos tipos de faturamento
 * 
 * IMPORTANTE: Execute este arquivo apenas UMA VEZ
 * Acesse: http://seusite.com/atualizar_banco.php
 */

require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado (segurança)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die('Acesso negado. Faça login primeiro.');
}

require_once 'includes/db_connect.php';

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização do Banco de Dados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
            margin: 10px 0;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Atualização do Banco de Dados</h1>
        <p>Este script irá atualizar a estrutura da tabela <code>orcamentos</code> para suportar:</p>
        <ul>
            <li>✅ Forma de pagamento: <strong>Boleto</strong></li>
            <li>✅ Novos tipos de faturamento: 7, 15, 20, 21, 30 dias e parcelamentos</li>
        </ul>
';

$errors = [];
$success = [];

try {
    // 1. Atualizar forma_pagamento
    echo '<div class="step"><h3>Passo 1: Atualizando formas de pagamento...</h3>';
    
    $sql1 = "ALTER TABLE `orcamentos` 
             MODIFY COLUMN `forma_pagamento` 
             ENUM('pix','debito','credito','dinheiro','faturamento','boleto') 
             DEFAULT 'faturamento'";
    
    if ($conn->query($sql1) === TRUE) {
        echo '<div class="success">✅ Forma de pagamento atualizada com sucesso! Adicionado: <strong>boleto</strong></div>';
        $success[] = 'forma_pagamento';
    } else {
        throw new Exception("Erro ao atualizar forma_pagamento: " . $conn->error);
    }
    echo '</div>';
    
    // 2. Atualizar tipo_faturamento
    echo '<div class="step"><h3>Passo 2: Atualizando tipos de faturamento...</h3>';
    
    $sql2 = "ALTER TABLE `orcamentos` 
             MODIFY COLUMN `tipo_faturamento` 
             ENUM(
                 'avista',
                 '7',
                 '15',
                 '15_dias',
                 '20',
                 '20_dias',
                 '21',
                 '30',
                 '30_dias',
                 '45_dias',
                 '60_dias',
                 '90_dias',
                 '15_30',
                 '20_30',
                 '21_30',
                 '20_30_45',
                 '28_35_42',
                 '28_35_42_59',
                 '28_35_45',
                 '30_45_60'
             ) 
             DEFAULT 'avista'";
    
    if ($conn->query($sql2) === TRUE) {
        echo '<div class="success">✅ Tipos de faturamento atualizados com sucesso!</div>';
        echo '<div class="info"><strong>Novos tipos adicionados:</strong><br>';
        echo '• 7, 15, 20, 21, 30 dias<br>';
        echo '• 15/30, 20/30, 21/30 dias<br>';
        echo '• 20/30/45 dias<br>';
        echo '• 28/35/42, 28/35/42/59, 28/35/45 dias<br>';
        echo '• 30/45/60 dias</div>';
        $success[] = 'tipo_faturamento';
    } else {
        throw new Exception("Erro ao atualizar tipo_faturamento: " . $conn->error);
    }
    echo '</div>';
    
    // 3. Verificar as alterações
    echo '<div class="step"><h3>Passo 3: Verificando alterações...</h3>';
    
    $result1 = $conn->query("SHOW COLUMNS FROM `orcamentos` LIKE 'forma_pagamento'");
    if ($result1 && $row1 = $result1->fetch_assoc()) {
        echo '<div class="info"><strong>forma_pagamento:</strong><br><code>' . htmlspecialchars($row1['Type']) . '</code></div>';
    }
    
    $result2 = $conn->query("SHOW COLUMNS FROM `orcamentos` LIKE 'tipo_faturamento'");
    if ($result2 && $row2 = $result2->fetch_assoc()) {
        echo '<div class="info"><strong>tipo_faturamento:</strong><br><code>' . htmlspecialchars($row2['Type']) . '</code></div>';
    }
    echo '</div>';

    // 4. Corrigir AUTO_INCREMENT na tabela vendas
    echo '<div class="step"><h3>Passo 4: Corrigindo AUTO_INCREMENT da tabela <code>vendas</code>...</h3>';

    $result_id = $conn->query("SHOW COLUMNS FROM `vendas` LIKE 'id'");
    $col_info = $result_id ? $result_id->fetch_assoc() : null;
    if ($col_info && strpos(strtolower($col_info['Extra']), 'auto_increment') === false) {
        // Busca o maior id existente para garantir que o AUTO_INCREMENT começa coreto
        $max_id_row = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM vendas")->fetch_assoc();
        $next_id = (int)$max_id_row['next_id'];

        $sql_fix_vendas = "ALTER TABLE `vendas` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT";
        if ($conn->query($sql_fix_vendas) === TRUE) {
            // Ajusta o AUTO_INCREMENT para não colidir com registros existentes
            $conn->query("ALTER TABLE `vendas` AUTO_INCREMENT = {$next_id}");
            echo '<div class="success">✅ AUTO_INCREMENT adicionado ao campo <code>id</code> da tabela <strong>vendas</strong> com sucesso! (próximo ID: ' . $next_id . ')</div>';
            $success[] = 'vendas_auto_increment';
        } else {
            throw new Exception("Erro ao corrigir AUTO_INCREMENT da tabela vendas: " . $conn->error);
        }
    } else {
        echo '<div class="info">ℹ️ Tabela <code>vendas</code> já possui AUTO_INCREMENT no campo <code>id</code>. Nenhuma alteração necessária.</div>';
        $success[] = 'vendas_auto_increment';
    }
    echo '</div>';
    
    // Sucesso final
    echo '<div class="success" style="font-size: 18px; font-weight: bold;">
            🎉 ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!
          </div>';
    
    echo '<div class="info">
            <strong>Próximos passos:</strong><br>
            1. ✅ Banco de dados atualizado<br>
            2. ✅ Agora você pode usar "Boleto" nos orçamentos<br>
            3. ✅ Todos os novos prazos de pagamento estão disponíveis<br>
            4. ⚠️ <strong>IMPORTANTE:</strong> Delete este arquivo (atualizar_banco.php) por segurança
          </div>';
    
} catch (Exception $e) {
    echo '<div class="error">❌ <strong>ERRO:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    $errors[] = $e->getMessage();
}

$conn->close();

echo '
        <a href="criar_orcamento.php" class="btn">Ir para Criar Orçamento</a>
        <a href="orcamentos.php" class="btn" style="background-color: #28a745;">Ver Orçamentos</a>
    </div>
</body>
</html>';

// Se tudo deu certo, sugerir deletar o arquivo
if (count($errors) == 0) {
    echo '<script>
        console.log("✅ Atualização concluída com sucesso!");
        console.log("⚠️ LEMBRE-SE: Delete o arquivo atualizar_banco.php por segurança!");
    </script>';
}
?>


