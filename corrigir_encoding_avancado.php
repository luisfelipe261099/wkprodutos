<?php
require_once 'includes/session_bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção Avançada de Encoding</title>
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
            border-bottom: 3px solid #dc3545;
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
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
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
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #c82333;
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
        <h1>🔧 Correção AVANÇADA de Encoding de Caracteres</h1>
        
        <div class="warning">
            <strong>⚠️ ATENÇÃO:</strong> Este é um método alternativo mais agressivo.<br>
            Use apenas se o método anterior não funcionou completamente.
        </div>';

// Processar correção alternativa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['corrigir_avancado'])) {
    echo '<div class="info">⏳ Executando correção avançada... Por favor aguarde.</div>';
    
    // Usar múltiplas conversões em cadeia
    $count = 0;
    
    // Estratégia 1: Usar SQL CONVERT (mais eficiente para banco de dados)
    echo '<p><strong>Estratégia 1: Conversão SQL CONVERT...</strong></p>';
    
    $campos_corrigir = [
        'nome',
        'descricao', 
        'sku',
        'fornecedor'
    ];
    
    foreach ($campos_corrigir as $campo) {
        // Este SQL converte a coluna para BINARY e depois de volta para UTF-8
        $sql = "UPDATE produtos SET `$campo` = CONVERT(CAST(CONVERT(`$campo` USING utf8) AS BINARY) USING utf8) WHERE `$campo` IS NOT NULL";
        
        if (@$conn->query($sql)) {
            echo '<div class="success">✅ Campo <strong>' . $campo . '</strong> atualizado com SQL CONVERT</div>';
            $count++;
        } else {
            echo '<div class="error">❌ Erro ao atualizar campo ' . $campo . ': ' . $conn->error . '</div>';
        }
    }
    
    // Estratégia 2: Atualização por PHP com múltiplas tentativas
    echo '<p><strong>Estratégia 2: Conversão PHP Avançada...</strong></p>';
    
    $result = $conn->query("SELECT id, nome, descricao, sku, fornecedor FROM produtos");
    
    if ($result) {
        $updated = 0;
        
        function try_multiple_conversions($text) {
            if (!$text) return $text;
            
            // Tentativa 1: iconv com sequência específica
            $t1 = @iconv('ISO-8859-1', 'UTF-8', $text);
            
            // Tentativa 2: mb_convert_encoding
            $t2 = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
            
            // Tentativa 3: htmlspecialchars/htmlentity
            $t3 = @html_entity_decode(htmlentities($text, ENT_QUOTES, 'ISO-8859-1'), ENT_QUOTES, 'UTF-8');
            
            // Tentativa 4: Decodificação UTF-8 dupla
            $t4 = @utf8_decode(utf8_encode($text));
            
            // Tentativa 5: Array de conversões em cadeia
            $t5 = $text;
            $t5 = @iconv('UTF-8', 'ISO-8859-1', @iconv('ISO-8859-1', 'UTF-8', $t5));
            
            // Escolher a melhor (a que tem menos caracteres estranhos)
            $candidates = array_filter([$t1, $t2, $t3, $t4, $t5]);
            
            $best = $text;
            $best_score = substr_count($text, 'Ã') + substr_count($text, 'Â') + substr_count($text, 'Ä') + substr_count($text, 'â€');
            
            foreach ($candidates as $candidate) {
                $score = substr_count($candidate, 'Ã') + substr_count($candidate, 'Â') + substr_count($candidate, 'Ä') + substr_count($candidate, 'â€');
                if ($score < $best_score) {
                    $best = $candidate;
                    $best_score = $score;
                }
            }
            
            return $best;
        }
        
        while ($row = $result->fetch_assoc()) {
            $novo_nome = try_multiple_conversions($row['nome']);
            $nova_descricao = try_multiple_conversions($row['descricao']);
            $novo_sku = try_multiple_conversions($row['sku']);
            $novo_fornecedor = try_multiple_conversions($row['fornecedor']);
            
            if ($novo_nome !== $row['nome'] || $nova_descricao !== $row['descricao'] || 
                $novo_sku !== $row['sku'] || $novo_fornecedor !== $row['fornecedor']) {
                
                $sql = "UPDATE produtos SET nome=?, descricao=?, sku=?, fornecedor=? WHERE id=?";
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ssssi", $novo_nome, $nova_descricao, $novo_sku, $novo_fornecedor, $row['id']);
                    if ($stmt->execute()) {
                        $updated++;
                    }
                    $stmt->close();
                }
            }
        }
        
        echo '<div class="success">✅ Conversão PHP avançada processou ' . $updated . ' produtos</div>';
    }
    
    echo '<div class="success" style="margin-top: 20px;">
        <strong>✅ Correção Avançada Concluída!</strong><br>
        Por favor, verifique os produtos para confirmar se os caracteres foram corrigidos.
    </div>';
    
    echo '<br><a href="corrigir_encoding_produtos.php" class="btn">Voltar para Correção Normal</a>';
    
} else {
    // Exibir informações e botão
    echo '<div class="warning">
        <strong>ℹ️ Quando usar este método?</strong><br>
        • Se a correção normal não funcionou 100%<br>
        • Se ainda houver caracteres como Ã¡, Ã©, Â°<br>
        • Como último recurso antes de editar manualmente
    </div>';
    
    // Mostrar produtos ainda com problema
    $problematicos = $conn->query("
        SELECT id, nome FROM produtos 
        WHERE nome LIKE '%Ã%' 
           OR nome LIKE '%Â%'
        LIMIT 10
    ");
    
    if ($problematicos && $problematicos->num_rows > 0) {
        echo '<h2>📋 Ainda com Problema:</h2>';
        echo '<ul>';
        while ($row = $problematicos->fetch_assoc()) {
            echo '<li><code>' . htmlspecialchars($row['nome']) . '</code></li>';
        }
        echo '</ul>';
        
        echo '<form method="POST" style="margin-top: 20px;">
            <p><strong>Clique abaixo para aplicar a correção avançada:</strong></p>
            <button type="submit" name="corrigir_avancado" value="1" class="btn" style="background-color: #dc3545;">
                ⚡ Executar Correção AVANÇADA
            </button>
        </form>';
    } else {
        echo '<div class="success">
            ✅ Não há mais produtos com problema de encoding!<br>
            A correção normal foi bem-sucedida.
        </div>';
        echo '<a href="corrigir_encoding_produtos.php" class="btn" style="background-color: #28a745;">
            ✅ Voltar para Verificação Normal
        </a>';
    }
}

echo '</div>
</body>
</html>';

$conn->close();
?>
