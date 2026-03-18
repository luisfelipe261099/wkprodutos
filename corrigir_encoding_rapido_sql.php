<?php
require_once 'includes/session_bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

// Aumentar timeout
set_time_limit(600);

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção RÁPIDA de Encoding (SQL Puro)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
            border-bottom: 3px solid #28a745;
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
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #218838;
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
        <h1>⚡ Correção RÁPIDA com SQL Puro</h1>
        
        <p><strong>Este método usa SQL REPLACE para corrigir em 1 comando!</strong></p>';

// Processar correção via SQL puro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['corrigir_sql'])) {
    if (ob_get_level() == 0) ob_start();
    
    echo '<div class="info">⏳ Executando correção SQL... Por favor aguarde.</div>';
    flush();
    ob_flush();
    
    // Tabela de conversões
    $conversoes = [
        'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ã º' => 'ú',
        'Ã†' => 'Á', 'Ã‰' => 'É', 'Ã›' => 'Í', 'Ã"' => 'Ó', 'Ã™' => 'Ú',
        'Ã§' => 'ç', 'Ã‡' => 'Ç',
        'Â°' => '°', 'Â ' => '', 'Â' => '',
        'Ã£' => 'ã', 'Ã•' => 'Õ',
        'Ã¤' => 'ä', 'Ä ' => 'Ä',
        'Ãº' => 'ú', 'Ã‰' => 'É',
    ];
    
    $processed = 0;
    $campos = ['nome', 'descricao', 'sku', 'fornecedor'];
    
    foreach ($campos as $campo) {
        foreach ($conversoes as $errado => $correto) {
            // Usar REPLACE para substituir na coluna
            $sql = "UPDATE produtos SET `$campo` = REPLACE(`$campo`, '" . $conn->real_escape_string($errado) . "', '" . $conn->real_escape_string($correto) . "') WHERE `$campo` LIKE '%" . $conn->real_escape_string($errado) . "%'";
            
            if ($conn->query($sql)) {
                $affected = $conn->affected_rows;
                if ($affected > 0) {
                    $processed += $affected;
                    echo '<div class="success">✅ Campo <strong>' . $campo . '</strong>: Substituído "' . htmlspecialchars($errado) . '" → "' . htmlspecialchars($correto) . '" (' . $affected . ' linhas)</div>';
                    flush();
                    ob_flush();
                }
            } else {
                echo '<div class="error">❌ Erro: ' . $conn->error . '</div>';
                flush();
                ob_flush();
            }
        }
    }
    
    echo '<div class="success"><strong>✅ Correção SQL Concluída!</strong><br>
        Total de substituições realizadas: ' . $processed . '<br>
        <br>
        <strong>Próximo passo:</strong> Verifique a lista de produtos para confirmar!
    </div>';
    flush();
    ob_flush();
    
    echo '<br><a href="corrigir_encoding_produtos.php" class="btn">Voltar para Verificação</a>';
    
} else {
    echo '<div class="info">
        <strong>ℹ️ Este método é MUITO RÁPIDO:</strong><br>
        • Usa SQL REPLACE (não faz loop em PHP)<br>
        • Processa TODAS as conversões no banco<br>
        • Ta muitoacelerado (< 5 segundos)<br>
        • Sem travamentos!
    </div>';
    
    // Mostrar quantos chars errados ainda existem
    $check = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM produtos WHERE nome LIKE '%Ã%' OR nome LIKE '%Â%') +
            (SELECT COUNT(*) FROM produtos WHERE descricao LIKE '%Ã%' OR descricao LIKE '%Â%') +
            (SELECT COUNT(*) FROM produtos WHERE sku LIKE '%Ã%' OR sku LIKE '%Â%') +
            (SELECT COUNT(*) FROM produtos WHERE fornecedor LIKE '%Ã%' OR fornecedor LIKE '%Â%') as total_problemas
    ");
    
    if ($check) {
        $row = $check->fetch_assoc();
        echo '<div class="info">
            Campos com caracteres errados detectados: <strong>' . $row['total_problemas'] . '</strong>
        </div>';
    }
    
    echo '<form method="POST" style="margin-top: 20px;">
        <button type="submit" name="corrigir_sql" value="1" class="btn" style="background-color: #28a745; padding: 15px 30px; font-size: 16px;">
            ⚡ Corrigir COM SQL PURO (RÁPIDO!)
        </button>
    </form>';
    
    echo '<hr style="margin: 30px 0;">';
    echo '<div class="info">
        <strong>Se este método não funcionar ou for lento:</strong><br>
        <a href="corrigir_encoding_produtos.php">← Volta para Correção Normal (com Progress)</a><br>
        <a href="corrigir_encoding_avancado.php">← Vai para Correção Avançada (SQL CONVERT)</a>
    </div>';
}

echo '</div>
</body>
</html>';

$conn->close();
?>
