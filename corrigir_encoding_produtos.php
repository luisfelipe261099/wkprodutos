<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
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
    <title>Corrigir Encoding de Produtos</title>
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
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .old-name {
            color: #dc3545;
            text-decoration: line-through;
        }
        .new-name {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Corrigir Encoding de Caracteres em Produtos</h1>
        
        <p>Este script corrige caracteres com encoding incorreto como <code>Ã¡</code> (deveria ser <code>á</code>), <code>Ã•</code> (deveria ser <code>Ó</code>), etc.</p>';

// Processar correção se POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['corrigir'])) {
    echo '<div class="info">⏳ Processando... Por favor aguarde.</div>';
    
    // Buscar todos os produtos
    $result = $conn->query("SELECT id, nome, descricao, sku, fornecedor FROM produtos");
    
    if ($result) {
        $corrected_count = 0;
        $total_count = 0;
        
        // Função para corrigir encoding UTF-8 duplicado (mojibake)
        function fix_utf8($text) {
            if ($text === null || $text === '') return $text;
            
            // Se a string contém padrões típicos de mojibake (Ã¡, Ã•, Â°, etc)
            if (preg_match('/[À-ÿ]{2,}/u', $text) === 0 && preg_match('/[ÃÂ]/u', $text)) {
                // Tenta decodificar a string como se fosse UTF-8 lida como Latin-1
                return @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);
            }
            
            return $text;
        }
        
        while ($row = $result->fetch_assoc()) {
            $total_count++;
            $original_nome = $row['nome'];
            $original_descricao = $row['descricao'];
            $original_sku = $row['sku'];
            $original_fornecedor = $row['fornecedor'];
            
            $novo_nome = fix_utf8($original_nome);
            $nova_descricao = fix_utf8($original_descricao);
            $novo_sku = fix_utf8($original_sku);
            $novo_fornecedor = fix_utf8($original_fornecedor);
            
            // Se houve mudança em qualquer campo, atualizar
            if ($novo_nome !== $original_nome || $nova_descricao !== $original_descricao || 
                $novo_sku !== $original_sku || $novo_fornecedor !== $original_fornecedor) {
                
                $sql = "UPDATE produtos SET nome = ?, descricao = ?, sku = ?, fornecedor = ? WHERE id = ?";
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ssssi", $novo_nome, $nova_descricao, $novo_sku, $novo_fornecedor, $row['id']);
                    if ($stmt->execute()) {
                        $corrected_count++;
                        echo '<div class="success">
                            ✅ <strong>Produto ID ' . $row['id'] . ':</strong><br>
                            <span class="old-name">' . htmlspecialchars($original_nome) . '</span><br>
                            ➜ <span class="new-name">' . htmlspecialchars($novo_nome) . '</span>
                        </div>';
                    } else {
                        echo '<div class="error">❌ Erro ao atualizar produto ' . $row['id'] . ': ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                }
            }
        }
        
        echo '<div class="success"><strong>✅ Processamento Concluído!</strong><br>
            Total de produtos verificados: ' . $total_count . '<br>
            Produtos corrigidos: ' . $corrected_count . '
        </div>';
    } else {
        echo '<div class="error">❌ Erro ao buscar produtos: ' . $conn->error . '</div>';
    }
    
    echo '<br><a href="corrigir_encoding_produtos.php" class="btn">Voltar</a>';
    
} else {
    // Exibir produtos com problema de encoding
    echo '<h2>📋 Produtos com Possível Encoding Incorreto</h2>';
    
    $problematicos = $conn->query("
        SELECT id, nome, descricao 
        FROM produtos 
        WHERE nome LIKE '%Ã%' 
           OR nome LIKE '%Â%' 
           OR descricao LIKE '%Ã%' 
           OR descricao LIKE '%Â%'
        ORDER BY id
    ");
    
    if ($problematicos && $problematicos->num_rows > 0) {
        echo '<div class="warning">⚠️ Encontrados ' . $problematicos->num_rows . ' produtos com caracteres potencialmente incorretos.</div>';
        
        echo '<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Produto</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>';
        
        while ($row = $problematicos->fetch_assoc()) {
            echo '<tr>
                <td>' . $row['id'] . '</td>
                <td><code>' . htmlspecialchars($row['nome']) . '</code></td>
                <td><code>' . htmlspecialchars(substr($row['descricao'], 0, 100)) . (strlen($row['descricao']) > 100 ? '...' : '') . '</code></td>
            </tr>';
        }
        
        echo '</tbody></table>';
        
        echo '<div class="info">
            <strong>ℹ️ Como funcionará a correção:</strong><br>
            O sistema irá converter caracteres como:<br>
            • <code>Ã¡</code> → <code>á</code><br>
            • <code>Ã•</code> → <code>Ó</code><br>
            • <code>Â°</code> → <code>°</code><br>
            • <code>Ã§</code> → <code>ç</code><br>
            E todos os acentos e caracteres especiais incorretos.
        </div>';
        
        echo '<form method="POST" style="margin-top: 20px;">
            <button type="submit" name="corrigir" value="1" class="btn btn-success">✅ Corrigir Todos os Produtos</button>
        </form>';
    } else {
        echo '<div class="success">✅ Nenhum produto com problema de encoding encontrado!</div>';
    }
    
    // Também verificar nos orçamentos
    echo '<hr style="margin: 40px 0;"><h2>📋 Itens de Orçamento com Possível Encoding Incorreto</h2>';
    
    $orcamentos_problematicos = $conn->query("
        SELECT io.id, io.orcamento_id, p.nome
        FROM itens_orcamento io
        LEFT JOIN produtos p ON io.produto_id = p.id
        WHERE p.nome LIKE '%Ã%' 
           OR p.nome LIKE '%Â%'
        LIMIT 20
    ");
    
    if ($orcamentos_problematicos && $orcamentos_problematicos->num_rows > 0) {
        echo '<div class="info">ℹ️ ' . $orcamentos_problematicos->num_rows . ' itens de orçamento são afetados.</div>';
        echo '<p>Esses itens serão corrigidos automaticamente quando os produtos forem atualizados acima.</p>';
    } else {
        echo '<div class="success">✅ Nenhum item de orçamento afetado.</div>';
    }
    
    echo '<br><a href="cadastro_produto.php" class="btn">Ir para Cadastro de Produtos</a>';
}

echo '</div>
</body>
</html>';

$conn->close();
?>
