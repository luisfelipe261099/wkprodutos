<?php
putenv('APP_ENV=development');
require_once 'includes/db_connect.php';

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Iniciando verificação de codificação de nomes...\n\n";

$tables_columns = [
    'clientes' => ['nome', 'nome_fantasia', 'cidade', 'endereco'],
    'produtos' => ['nome', 'descricao'],
    'empresas_representadas' => ['nome_empresa', 'razao_social']
];

foreach ($tables_columns as $table => $columns) {
    echo "Processando tabela: $table\n";
    $result = $conn->query("SELECT id, " . implode(', ', $columns) . " FROM $table");
    
    if (!$result) {
        echo "Erro ao selecionar da tabela $table: " . $conn->error . "\n";
        continue;
    }

    while ($row = $result->fetch_assoc()) {
        $updates = [];
        $id = $row['id'];
        
        foreach ($columns as $col) {
            $val = $row[$col];
            if (empty($val)) continue;

            // Detectar codificação dupla (UTF-8 interpretado como Latin1 e codificado novamente como UTF-8)
            // Um sinal claro é "Ã" seguido de caracteres específicos
            if (preg_match('/Ã[Â-ÿ]/', $val) || preg_match('/Ã /', $val) || strpos($val, 'Ã­') !== false) {
                // Tentar converter de volta
                // Se o texto é "EmÃlia", utf8_decode ("EmÃlia") resulta em "Emília"
                $new_val = mb_convert_encoding($val, 'ISO-8859-1', 'UTF-8');
                
                echo "Fixing $table [$id] -> $col: '$val' para '$new_val'\n";
                $updates[$col] = $new_val;
            }
        }

        if (!empty($updates)) {
            $update_parts = [];
            foreach ($updates as $col => $new_val) {
                $update_parts[] = "$col = '" . $conn->real_escape_string($new_val) . "'";
            }
            $sql_update = "UPDATE $table SET " . implode(', ', $update_parts) . " WHERE id = $id";
            if ($conn->query($sql_update)) {
                echo "✅ Sucesso ao atualizar ID $id\n";
            } else {
                echo "❌ Erro ao atualizar ID $id: " . $conn->error . "\n";
            }
        }
    }
}

echo "\nFim do processamento.\n";
?>
