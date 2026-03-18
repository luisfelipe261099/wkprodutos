<?php
require_once 'includes/db_connect.php';

echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin-bottom: 20px;'>";
echo "<h2>⚠️ AVISO</h2>";
echo "<p>Este script tenta usar <code>ALTER TABLE</code> para adicionar AUTO_INCREMENT.</p>";
echo "<p><strong>Pode não funcionar em ambientes como Vercel, PlanetScale ou outros serverless.</strong></p>";
echo "<p><a href='corrigir_orcamentos_avancado.php'><strong>👉 Clique aqui para usar o método avançado (recomendado)</strong></a></p>";
echo "</div>";

echo "<h2>🔧 Correção do Banco de Dados - Orçamentos</h2>";

function fixTable($conn, $tableName) {
    echo "<h3>Verificando tabela '$tableName'...</h3>";
    
    $result = $conn->query("DESCRIBE `$tableName` `id`");
    if ($result && $row = $result->fetch_assoc()) {
        echo "Coluna 'id' encontrada. Extra: " . $row['Extra'] . "<br>";
        
        if (strpos($row['Extra'], 'auto_increment') === false) {
            echo "⚠️ Coluna 'id' NÃO é auto_increment. Tentando corrigir...<br>";
            
            // Tenta adicionar AUTO_INCREMENT
            // Nota: Não precisamos ADD PRIMARY KEY se ela já existe, apenas MODIFY o tipo e adicionar o atributo
            $sql = "ALTER TABLE `$tableName` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT";
            if ($conn->query($sql)) {
                echo "✅ Sucesso! AUTO_INCREMENT adicionado à tabela '$tableName'.<br>";
            } else {
                echo "❌ Erro ao corrigir: " . $conn->error . "<br>";
            }
        } else {
            echo "✅ Coluna 'id' já é auto_increment.<br>";
        }
    } else {
        echo "❌ Coluna 'id' não encontrada na tabela '$tableName'.<br>";
    }
}

fixTable($conn, 'orcamentos');
fixTable($conn, 'itens_orcamento');

echo "<br><a href='criar_orcamento.php'>Voltar para Criar Orçamento</a>";
?>
