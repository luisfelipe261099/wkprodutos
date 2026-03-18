<?php
/**
 * Script de verificação e correção automática da estrutura do banco de dados
 * Execute este arquivo uma vez quando o banco estiver acessível
 * Ele corrigirá automaticamente a tabela orcamentos adicionando AUTO_INCREMENT se necessário
 */

require_once 'includes/db_connect.php';

echo "<h1>🔧 Verificação e Correção da Estrutura do Banco de Dados</h1>";
echo "<hr>";

$tabelas_verificar = [
    'orcamentos' => [
        'id' => ['tipo' => 'int(11)', 'auto_increment' => true]
    ],
    'itens_orcamento' => [
        'id' => ['tipo' => 'int(11)', 'auto_increment' => true]
    ]
];

foreach ($tabelas_verificar as $tabela => $campos) {
    echo "<h2>Tabela: <code>$tabela</code></h2>";
    
    foreach ($campos as $campo => $config) {
        $result = $conn->query("DESCRIBE `$tabela` `$campo`");
        
        if (!$result) {
            echo "❌ Erro ao descrever campo '$campo': " . $conn->error . "<br>";
            continue;
        }
        
        $row = $result->fetch_assoc();
        if (!$row) {
            echo "❌ Campo '$campo' não encontrado<br>";
            continue;
        }
        
        $tem_auto_increment = strpos($row['Extra'], 'auto_increment') !== false;
        $eh_chave_primaria = $row['Key'] === 'PRI';
        
        echo "Campo: <code>$campo</code><br>";
        echo "├─ Tipo: <code>{$row['Type']}</code> (Esperado: <code>{$config['tipo']}</code>)<br>";
        echo "├─ Chave: <code>{$row['Key']}</code> (PRI = Primária)<br>";
        echo "├─ AutoIncrement: " . ($tem_auto_increment ? "✅ Sim" : "❌ Não") . "<br>";
        echo "└─ Extra: <code>{$row['Extra']}</code><br><br>";
        
        // Corrigir se necessário
        if ($config['auto_increment'] && !$tem_auto_increment) {
            echo "⚠️ Corrigindo campo '$campo' para adicionar AUTO_INCREMENT...<br>";
            $sql_fix = "ALTER TABLE `$tabela` MODIFY COLUMN `$campo` {$config['tipo']} NOT NULL AUTO_INCREMENT";
            
            if ($conn->query($sql_fix)) {
                echo "✅ Campo '$campo' corrigido com sucesso!<br>";
            } else {
                echo "❌ Erro ao corrigir: " . $conn->error . "<br>";
            }
            
            // Adicionar PRIMARY KEY se não existir
            if (!$eh_chave_primaria) {
                echo "⚠️ Adicionando PRIMARY KEY ao campo '$campo'...<br>";
                $sql_pk = "ALTER TABLE `$tabela` ADD PRIMARY KEY (`$campo`)";
                
                if ($conn->query($sql_pk)) {
                    echo "✅ PRIMARY KEY adicionada com sucesso!<br>";
                } else {
                    // Ignorar erro se a chave já existe
                    if (strpos($conn->error, 'already exists') !== false || strpos($conn->error, 'Duplicate key') !== false) {
                        echo "ℹ️ PRIMARY KEY já existe (ignorado)<br>";
                    } else {
                        echo "❌ Erro ao adicionar PRIMARY KEY: " . $conn->error . "<br>";
                    }
                }
            }
            echo "<br>";
        }
    }
}

echo "<hr>";
echo "<h2>✅ Verificação concluída!</h2>";
echo "<p><a href='criar_orcamento.php'>← Voltar para Criar Orçamento</a></p>";
?>
