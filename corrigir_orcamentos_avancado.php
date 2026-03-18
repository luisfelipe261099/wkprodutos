<?php
/**
 * Script para corrigir a tabela orcamentos em ambientes com restrições SQL
 * Use este script se o ambiente não permitir ALTER TABLE para AUTO_INCREMENT
 */

require_once 'includes/db_connect.php';

echo "<h1>🔧 Correção da Tabela Orçamentos (Método Avançado)</h1>";
echo "<hr>";

try {
    // Passo 1: Verificar estrutura atual
    echo "<h2>Passo 1: Verificando estrutura atual...</h2>";
    $result = $conn->query("DESCRIBE orcamentos id");
    $row = $result->fetch_assoc();
    echo "<p>Tipo: <code>{$row['Type']}</code></p>";
    echo "<p>Extra: <code>{$row['Extra']}</code></p>";
    
    $tem_auto_increment = strpos($row['Extra'], 'auto_increment') !== false;
    
    if ($tem_auto_increment) {
        echo "<p>✅ AUTO_INCREMENT já está configurado!</p>";
        exit(0);
    }
    
    echo "<p>⚠️ AUTO_INCREMENT não está configurado. Tentando corrigir...</p><br>";
    
    // Passo 2: Verificar se há dados
    echo "<h2>Passo 2: Verificando dados existentes...</h2>";
    $count_result = $conn->query("SELECT COUNT(*) as total FROM orcamentos");
    $count_row = $count_result->fetch_assoc();
    $total_registros = $count_row['total'];
    echo "<p>Total de registros: <code>$total_registros</code></p><br>";
    
    // Passo 3: Criar tabela temporária
    echo "<h2>Passo 3: Criando tabela temporária...</h2>";
    $sql_temp = "CREATE TABLE orcamentos_temp LIKE orcamentos";
    if (!$conn->query($sql_temp)) {
        throw new Exception("Erro ao criar tabela temporária: " . $conn->error);
    }
    echo "<p>✓ Tabela temporária criada</p><br>";
    
    // Passo 4: Copiar dados
    echo "<h2>Passo 4: Copiando dados para tabela temporária...</h2>";
    $sql_copy = "INSERT INTO orcamentos_temp SELECT * FROM orcamentos";
    if (!$conn->query($sql_copy)) {
        // Se houver erro, deletar tabela temporária e abortar
        $conn->query("DROP TABLE orcamentos_temp");
        throw new Exception("Erro ao copiar dados: " . $conn->error);
    }
    echo "<p>✓ $total_registros registros copiados</p><br>";
    
    // Passo 5: Deletar tabela original
    echo "<h2>Passo 5: Removendo tabela original...</h2>";
    if (!$conn->query("DROP TABLE orcamentos")) {
        // Cleanup
        $conn->query("DROP TABLE orcamentos_temp");
        throw new Exception("Erro ao deletar tabela original: " . $conn->error);
    }
    echo "<p>✓ Tabela original removida</p><br>";
    
    // Passo 6: Renomear tabela temporária
    echo "<h2>Passo 6: Renomeando tabela temporária...</h2>";
    if (!$conn->query("ALTER TABLE orcamentos_temp RENAME TO orcamentos")) {
        throw new Exception("Erro ao renomear tabela: " . $conn->error);
    }
    echo "<p>✓ Tabela renomeada</p><br>";
    
    // Passo 7: Adicionar AUTO_INCREMENT
    echo "<h2>Passo 7: Adicionando AUTO_INCREMENT...</h2>";
    $max_result = $conn->query("SELECT MAX(id) as max_id FROM orcamentos");
    $max_row = $max_result->fetch_assoc();
    $proximo_id = intval($max_row['max_id']) + 1;
    
    $sql_auto = "ALTER TABLE orcamentos MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=$proximo_id";
    if (!$conn->query($sql_auto)) {
        throw new Exception("Erro ao adicionar AUTO_INCREMENT: " . $conn->error);
    }
    echo "<p>✓ AUTO_INCREMENT adicionado (próximo ID: $proximo_id)</p><br>";
    
    // Passo 8: Verificar resultado
    echo "<h2>Passo 8: Verificando resultado final...</h2>";
    $result_final = $conn->query("DESCRIBE orcamentos id");
    $row_final = $result_final->fetch_assoc();
    echo "<p>Tipo: <code>{$row_final['Type']}</code></p>";
    echo "<p>Extra: <code>{$row_final['Extra']}</code></p>";
    
    if (strpos($row_final['Extra'], 'auto_increment') !== false) {
        echo "<p>✅ AUTO_INCREMENT está configurado com sucesso!</p>";
    }
    
    echo "<br><hr>";
    echo "<h2>✅ Correção Concluída!</h2>";
    echo "<p><a href='criar_orcamento.php'>← Voltar para Criar Orçamento</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffdddd; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>❌ Erro durante a correção:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    echo "<p><a href='javascript:history.back()'>← Voltar</a></p>";
    exit(1);
}
?>
