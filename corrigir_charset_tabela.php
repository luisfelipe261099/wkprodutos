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
    <title>Alteração de Charset da Tabela (NUCLEAR)</title>
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
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 10px 0;
        }
        .danger {
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
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔴 ALTER TABLE - Mudar Charset (OPÇÃO NUCLEAR)</h1>
        
        <div class="danger">
            <strong>⚠️ ATENÇÃO - OPERAÇÃO AVANÇADA!</strong><br>
            Este método altera todo o charset da tabela de produtos.<br>
            Use apenas se os métodos anteriores falharem completamente.<br>
            <strong>Recomendação: Faça backup do banco de dados antes!</strong>
        </div>';

// Verificar charset atual
$charset_check = $conn->query("SHOW CREATE TABLE produtos");
if ($charset_check) {
    $table_info = $charset_check->fetch_assoc();
    $create_table = $table_info['Create Table'];
    
    echo '<h2>📊 Informação Atual da Tabela</h2>';
    echo '<pre style="background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto;">';
    echo htmlspecialchars($create_table);
    echo '</pre>';
}

// Processar mudança de charset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['alterar_charset'])) {
    echo '<div class="warning">⏳ Alterando charset da tabela... Por favor aguarde.</div>';
    
    // Passo 1: Mudar charset da tabela
    $sql1 = "ALTER TABLE produtos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if ($conn->query($sql1)) {
        echo '<div class="success">✅ Charset da tabela alterado para utf8mb4</div>';
        
        // Passo 2: Mudar charset de cada coluna
        $colunas = ['nome', 'descricao', 'sku', 'fornecedor'];
        
        foreach ($colunas as $coluna) {
            $sql = "ALTER TABLE produtos MODIFY $coluna VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            if (@$conn->query($sql)) {
                echo '<div class="success">✅ Coluna <strong>' . $coluna . '</strong> alterada para utf8mb4</div>';
            } else {
                echo '<div class="error">❌ Erro ao alterar coluna ' . $coluna . ': ' . $conn->error . '</div>';
            }
        }
        
        echo '<div class="success" style="margin-top: 20px;">
            <strong>✅ Operação Concluída!</strong><br>
            A tabela produto agora usa utf8mb4 (máxima compatibilidade Unicode).<br>
            Agora você pode rodar a correção normal novamente se necessário.
        </div>';
    } else {
        echo '<div class="error">❌ Erro ao alterar charset: ' . $conn->error . '</div>';
    }
    
    echo '<br><a href="corrigir_encoding_produtos.php" class="btn">Ir para Correção Normal</a>';
    
} else {
    // Mostrar avisos e opção
    echo '<h2>ℹ️ O que este método faz?</h2>
    <ul>
        <li>Converte toda a tabela para <code>utf8mb4</code></li>
        <li>Converte todas as colunas de texto para <code>utf8mb4</code></li>
        <li>Define a collação para <code>utf8mb4_unicode_ci</code></li>
        <li>Garante suporte total a caracteres especiais</li>
    </ul>';
    
    echo '<h2>⚠️ Riscos</h2>
    <ul>
        <li>⚠️ Pode levar alguns segundos (dependendo do tamanho)</li>
        <li>⚠️ Bloqueia a tabela durante a operação</li>
        <li>❌ NÃO remove dados (apenas altera charset)</li>
        <li>✅ Pode ser desfeito com BACKUP</li>
    </ul>';
    
    echo '<h2>✅ Quando usar?</h2>
    <ul>
        <li>Se a correção normal não funcionou</li>
        <li>Se a correção avançada não funcionou</li>
        <li>Se você vai ter muitos produtos com acentos</li>
        <li>Como solução definitiva para o problema</li>
    </ul>';
    
    echo '<form method="POST" style="margin-top: 20px;">
        <button type="submit" name="alterar_charset" value="1" class="btn btn-danger" onclick="return confirm(\'Tem certeza? Isso vai alterar toda a tabela produtos!\');">
            🔴 EXECUTAR ALTERAÇÃO DE CHARSET
        </button>
    </form>';
    
    echo '<br><a href="corrigir_encoding_avancado.php" class="btn">← Voltar para Método Avançado</a>';
}

echo '</div>
</body>
</html>';

$conn->close();
?>
