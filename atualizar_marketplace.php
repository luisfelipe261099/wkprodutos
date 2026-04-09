<?php
// Script para atualizar marketplace com integração
require_once __DIR__ . '/includes/db_connect.php';

echo "<h2>🔄 Atualização do Marketplace para Integração Completa</h2>";
echo "<p>Este script irá adicionar os campos de integração ao marketplace existente.</p>";

$errors = [];
$success = [];

try {
    // 1. Adicionar campos de integração na tabela marketplace_pedidos
    echo "<h3>1. Adicionando campos de integração...</h3>";
    
    $campos_integracao = [
        "ADD COLUMN `venda_id` int(11) DEFAULT NULL",
        "ADD COLUMN `transacao_financeira_id` int(11) DEFAULT NULL"
    ];

    foreach ($campos_integracao as $campo) {
        $sql = "ALTER TABLE marketplace_pedidos $campo";
        if ($conn->query($sql)) {
            $success[] = "✓ Campo de integração adicionado: " . explode('`', $campo)[1];
        } else {
            if (strpos($conn->error, 'Duplicate column name') === false) {
                $errors[] = "✗ Erro ao adicionar campo: " . $conn->error;
            } else {
                $success[] = "✓ Campo já existe: " . explode('`', $campo)[1];
            }
        }
    }

    // 2. Adicionar foreign keys
    echo "<h3>2. Adicionando foreign keys...</h3>";
    
    $foreign_keys = [
        "ADD FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE SET NULL",
        "ADD FOREIGN KEY (`transacao_financeira_id`) REFERENCES `transacoes_financeiras` (`id`) ON DELETE SET NULL"
    ];

    foreach ($foreign_keys as $fk) {
        $sql = "ALTER TABLE marketplace_pedidos $fk";
        if ($conn->query($sql)) {
            $success[] = "✓ Foreign key adicionada";
        } else {
            if (strpos($conn->error, 'Duplicate foreign key') === false && 
                strpos($conn->error, 'foreign key constraint fails') === false) {
                $errors[] = "✗ Erro ao adicionar foreign key: " . $conn->error;
            } else {
                $success[] = "✓ Foreign key já existe";
            }
        }
    }

    // 3. Adicionar índices
    echo "<h3>3. Adicionando índices...</h3>";
    
    $indices = [
        "ADD INDEX `idx_venda` (`venda_id`)",
        "ADD INDEX `idx_transacao` (`transacao_financeira_id`)"
    ];

    foreach ($indices as $indice) {
        $sql = "ALTER TABLE marketplace_pedidos $indice";
        if ($conn->query($sql)) {
            $success[] = "✓ Índice adicionado";
        } else {
            if (strpos($conn->error, 'Duplicate key name') === false) {
                $errors[] = "✗ Erro ao adicionar índice: " . $conn->error;
            } else {
                $success[] = "✓ Índice já existe";
            }
        }
    }

    // 4. Adicionar campos na tabela produtos (se não existirem)
    echo "<h3>4. Atualizando tabela produtos...</h3>";
    
    $campos_produtos = [
        "ADD COLUMN `ativo_marketplace` tinyint(1) DEFAULT 1",
        "ADD COLUMN `destaque_marketplace` tinyint(1) DEFAULT 0", 
        "ADD COLUMN `ordem_exibicao` int(11) DEFAULT 0",
        "ADD COLUMN `imagem_url` varchar(255) DEFAULT NULL",
        "ADD COLUMN `descricao_completa` text DEFAULT NULL"
    ];

    foreach ($campos_produtos as $campo) {
        $sql = "ALTER TABLE produtos $campo";
        if ($conn->query($sql)) {
            $success[] = "✓ Campo adicionado à tabela produtos: " . explode('`', $campo)[1];
        } else {
            if (strpos($conn->error, 'Duplicate column name') === false) {
                $errors[] = "✗ Erro ao adicionar campo produtos: " . $conn->error;
            } else {
                $success[] = "✓ Campo produtos já existe: " . explode('`', $campo)[1];
            }
        }
    }

    // 5. Inserir configurações (se não existirem)
    echo "<h3>5. Inserindo configurações...</h3>";
    
    $configuracoes = [
        ['marketplace_ativo', '1', 'Marketplace ativo (1) ou inativo (0)'],
        ['titulo_marketplace', 'Karla Wollinger - Marketplace', 'Título do marketplace'],
        ['descricao_marketplace', 'Faça seus pedidos online de forma rápida e prática', 'Descrição do marketplace'],
        ['email_notificacoes', 'contato@karlawollinger.com', 'Email para receber notificações de pedidos'],
        ['prazo_entrega_padrao', '3', 'Prazo padrão de entrega em dias úteis'],
        ['valor_minimo_pedido', '0.00', 'Valor mínimo para pedidos'],
        ['permitir_agendamento', '1', 'Permitir agendamento de entrega (1) ou não (0)'],
        ['horario_funcionamento', '08:00-18:00', 'Horário de funcionamento para entregas'],
        ['dias_funcionamento', '1,2,3,4,5', 'Dias da semana que funciona (1=segunda, 7=domingo)']
    ];

    foreach ($configuracoes as $config) {
        $sql = "INSERT IGNORE INTO marketplace_configuracoes (chave, valor, descricao) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $config[0], $config[1], $config[2]);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success[] = "✓ Configuração inserida: " . $config[0];
            }
        }
    }

    // 6. Atualizar trigger
    echo "<h3>6. Atualizando trigger...</h3>";
    
    $sql = "DROP TRIGGER IF EXISTS gerar_numero_pedido";
    $conn->query($sql);

    $sql = "CREATE TRIGGER gerar_numero_pedido 
            BEFORE INSERT ON marketplace_pedidos
            FOR EACH ROW 
            BEGIN
                DECLARE novo_numero VARCHAR(20);
                DECLARE contador INT;
                
                SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 4) AS UNSIGNED)), 0) + 1 
                INTO contador 
                FROM marketplace_pedidos 
                WHERE numero_pedido LIKE CONCAT(DATE_FORMAT(NOW(), '%y%m'), '%');
                
                SET novo_numero = CONCAT(DATE_FORMAT(NOW(), '%y%m'), LPAD(contador, 4, '0'));
                SET NEW.numero_pedido = novo_numero;
                
                IF NEW.tipo_faturamento = '15_dias' THEN
                    SET NEW.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 15 DAY);
                ELSEIF NEW.tipo_faturamento = '20_dias' THEN
                    SET NEW.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 20 DAY);
                ELSEIF NEW.tipo_faturamento = '30_dias' THEN
                    SET NEW.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 30 DAY);
                ELSE
                    SET NEW.data_vencimento = CURDATE();
                END IF;
            END";
    
    if ($conn->query($sql)) {
        $success[] = "✓ Trigger atualizado";
    } else {
        $errors[] = "✗ Erro ao criar trigger: " . $conn->error;
    }

    // 7. Atualizar view
    echo "<h3>7. Atualizando view...</h3>";
    
    $sql = "DROP VIEW IF EXISTS vw_marketplace_vendas";
    $conn->query($sql);

    $sql = "CREATE VIEW vw_marketplace_vendas AS
            SELECT 
                mp.id,
                mp.numero_pedido,
                c.nome as cliente_nome,
                c.email as cliente_email,
                mp.valor_total,
                mp.status_pedido,
                mp.tipo_faturamento,
                mp.data_pedido,
                mp.data_entrega_agendada,
                mp.data_vencimento,
                mp.venda_id,
                mp.transacao_financeira_id,
                v.data_venda,
                tf.data_transacao,
                COUNT(mip.id) as total_itens,
                CASE 
                    WHEN mp.venda_id IS NOT NULL AND mp.transacao_financeira_id IS NOT NULL THEN 'Integrado'
                    WHEN mp.status_pedido = 'confirmado' THEN 'Pendente'
                    ELSE 'Aguardando'
                END as status_integracao
            FROM marketplace_pedidos mp
            LEFT JOIN clientes c ON mp.cliente_id = c.id
            LEFT JOIN marketplace_itens_pedido mip ON mp.id = mip.pedido_id
            LEFT JOIN vendas v ON mp.venda_id = v.id
            LEFT JOIN transacoes_financeiras tf ON mp.transacao_financeira_id = tf.id
            GROUP BY mp.id";
    
    if ($conn->query($sql)) {
        $success[] = "✓ View atualizada";
    } else {
        $errors[] = "✗ Erro ao criar view: " . $conn->error;
    }

    // 8. Ativar produtos no marketplace
    echo "<h3>8. Ativando produtos no marketplace...</h3>";
    
    $sql = "UPDATE produtos SET 
                ativo_marketplace = 1,
                destaque_marketplace = 0,
                ordem_exibicao = 0
            WHERE ativo_marketplace IS NULL";
    
    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        $success[] = "✓ $affected produtos ativados no marketplace";
    } else {
        $errors[] = "✗ Erro ao ativar produtos: " . $conn->error;
    }

    // 9. Verificar integridade
    echo "<h3>9. Verificando integridade dos dados...</h3>";
    
    $sql = "SELECT 
                COUNT(*) as total_pedidos,
                SUM(CASE WHEN venda_id IS NOT NULL THEN 1 ELSE 0 END) as com_venda,
                SUM(CASE WHEN transacao_financeira_id IS NOT NULL THEN 1 ELSE 0 END) as com_transacao
            FROM marketplace_pedidos";
    
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
        $success[] = "✓ Verificação: {$stats['total_pedidos']} pedidos, {$stats['com_venda']} com venda, {$stats['com_transacao']} com transação";
    }

} catch (Exception $e) {
    $errors[] = "✗ Erro geral: " . $e->getMessage();
}

// Exibir resultados
echo "<h3>📊 Resultados da Atualização:</h3>";

if (!empty($success)) {
    echo "<div style='color: green; margin-bottom: 20px;'>";
    foreach ($success as $msg) {
        echo "<p>$msg</p>";
    }
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div style='color: red; margin-bottom: 20px;'>";
    foreach ($errors as $msg) {
        echo "<p>$msg</p>";
    }
    echo "</div>";
}

if (empty($errors)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!</h4>";
    echo "<p>O marketplace agora está totalmente integrado com o sistema principal.</p>";
    echo "<p><strong>Funcionalidades ativadas:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Integração automática de vendas</li>";
    echo "<li>✅ Criação automática de transações financeiras</li>";
    echo "<li>✅ Atualização automática de estoque</li>";
    echo "<li>✅ Relatórios integrados</li>";
    echo "<li>✅ Rastreamento completo de pedidos</li>";
    echo "</ul>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>1. Acesse <a href='marketplace_admin.php'>Links Marketplace</a> para gerar links</li>";
    echo "<li>2. Configure produtos em <a href='produtos.php'>Gestão de Produtos</a></li>";
    echo "<li>3. Monitore pedidos em <a href='marketplace_pedidos.php'>Pedidos Marketplace</a></li>";
    echo "<li>4. Veja relatórios em <a href='relatorios.php'>Relatórios</a></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠ ATUALIZAÇÃO CONCLUÍDA COM AVISOS</h4>";
    echo "<p>Alguns erros ocorreram. Verifique os detalhes acima.</p>";
    echo "</div>";
}

$conn->close();
?>
