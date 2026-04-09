<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/db_connect.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$message = '';
$message_type = '';

// Lógica de processamento do formulário (permanece a mesma)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $venda_id = trim($_POST["venda_id"]);
    $cliente_id = trim($_POST["cliente_id"]);
    $data_lembrete = trim($_POST["data_lembrete"]);
    $tipo_lembrete = trim($_POST["tipo_lembrete"]);
    $descricao = trim($_POST["descricao"]);
    $usuario_id = $_SESSION['id'];

    if (empty($venda_id) || empty($cliente_id) || empty($data_lembrete) || empty($descricao) || empty($tipo_lembrete)) {
        $message = "Erro: Todos os campos são obrigatórios.";
        $message_type = "danger";
    } else {
        $sql_insert = "INSERT INTO lembretes_acompanhamento (cliente_id, venda_id, data_lembrete, tipo_lembrete, descricao, usuario_id, status_lembrete) VALUES (?, ?, ?, ?, ?, ?, 'Pendente')";
        if ($stmt = $conn->prepare($sql_insert)) {
            $stmt->bind_param("iisssi", $cliente_id, $venda_id, $data_lembrete, $tipo_lembrete, $descricao, $usuario_id);
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = "Acompanhamento preditivo para a venda #" . htmlspecialchars($venda_id) . " criado com sucesso!";
                $_SESSION['flash_message_type'] = "success";
                header("location: acompanhamento_clientes.php");
                exit;
            } else {
                $message = "Erro ao registrar o acompanhamento: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }
}

// Buscar vendas concluídas que AINDA NÃO têm um acompanhamento do tipo 'Follow-up' ou 'Pós-venda'
$sql_vendas = "SELECT v.id, v.data_venda, c.id as cliente_id, c.nome as nome_cliente
               FROM vendas v
               JOIN clientes c ON v.cliente_id = c.id
               WHERE v.status_venda = 'concluida'
               AND NOT EXISTS (
                   SELECT 1 
                   FROM lembretes_acompanhamento la
                   WHERE la.venda_id = v.id AND la.tipo_lembrete IN ('Follow-up', 'Pós-venda')
               )
               ORDER BY v.data_venda DESC";
$vendas_para_acompanhar = $conn->query($sql_vendas);

include_once __DIR__ . '/includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title"><i class="fas fa-handshake"></i> Criar Acompanhamento Preditivo</h1>
    <p class="page-subtitle">Crie um lembrete com data calculada com base nos produtos e quantidades da venda.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="modern-card fade-in-up">
            <div class="card-body-modern">
                <form id="formAcompanhamento" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    
                    <div class="mb-4">
                        <h5 class="text-primary"><i class="fas fa-check-circle me-2"></i>Passo 1: Selecione a Venda</h5>
                        <select class="form-select form-select-lg" id="venda_select" required>
                            <option value="" selected>-- Clique aqui para escolher uma venda --</option>
                            <?php if ($vendas_para_acompanhar && $vendas_para_acompanhar->num_rows > 0): ?>
                                <?php while($venda = $vendas_para_acompanhar->fetch_assoc()): ?>
                                    <option 
                                        value="<?php echo $venda['id']; ?>"
                                        data-cliente-id="<?php echo $venda['cliente_id']; ?>"
                                        data-cliente-nome="<?php echo htmlspecialchars($venda['nome_cliente']); ?>"
                                        data-venda-date="<?php echo date('Y-m-d', strtotime($venda['data_venda'])); ?>">
                                        Venda #<?php echo $venda['id']; ?> - 
                                        <?php echo htmlspecialchars($venda['nome_cliente']); ?> 
                                        (<?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>Nenhuma nova venda para acompanhar.</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div id="detalhes_acompanhamento" class="mt-4" style="display: none;">
                        <div id="loading" class="text-center my-3" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Calculando data ideal...</p>
                        </div>

                        <div id="form-fields">
                            <h5 class="text-primary"><i class="fas fa-edit me-2"></i>Passo 2: Detalhes do Lembrete</h5>
                            <input type="hidden" id="venda_id" name="venda_id">
                            <input type="hidden" id="cliente_id" name="cliente_id">
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Cliente</label>
                                    <input type="text" id="display_cliente_nome" class="form-control" readonly style="font-weight: bold; background-color: #e9ecef;">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="data_lembrete" class="form-label">Data Sugerida para Lembrete *</label>
                                    <input type="date" class="form-control border-primary" id="data_lembrete" name="data_lembrete" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="tipo_lembrete" class="form-label">Tipo de Lembrete *</label>
                                    <select class="form-select" id="tipo_lembrete" name="tipo_lembrete" required>
                                        <option value="Follow-up" selected>Follow-up</option>
                                        <option value="Pós-venda">Pós-venda</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="descricao" class="form-label">Descrição / Notas *</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="5" required></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a href="acompanhamento_clientes.php" class="btn btn-outline-secondary me-2"><i class="fas fa-times me-2"></i>Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Criar Acompanhamento</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include_once __DIR__ . '/includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vendaSelect = document.getElementById('venda_select');
    const detalhesDiv = document.getElementById('detalhes_acompanhamento');
    const loadingDiv = document.getElementById('loading');
    const formFieldsDiv = document.getElementById('form-fields');

    vendaSelect.addEventListener('change', async function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (!selectedOption.value) {
            detalhesDiv.style.display = 'none';
            return;
        }
        
        detalhesDiv.style.display = 'block';
        loadingDiv.style.display = 'block';
        formFieldsDiv.style.display = 'none';

        const vendaId = selectedOption.value;
        const clienteId = selectedOption.dataset.clienteId;
        const clienteNome = selectedOption.dataset.clienteNome;
        const dataVendaStr = selectedOption.dataset.vendaDate; // Formato YYYY-MM-DD
        const dataVenda = new Date(dataVendaStr + 'T00:00:00'); // Evita problemas com timezone

        document.getElementById('venda_id').value = vendaId;
        document.getElementById('cliente_id').value = clienteId;
        document.getElementById('display_cliente_nome').value = clienteNome;

        try {
            const response = await fetch(`api_get_venda_detalhes.php?venda_id=${vendaId}`);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({error: 'Resposta inválida do servidor.'}));
                throw new Error(`Erro ${response.status}: ${errorData.error || response.statusText}`);
            }

            const itens = await response.json();
            
            let menorDuracao = Infinity;
            let itemPrincipal = null;
            let temPrevisao = false;

            itens.forEach(item => {
                if (item.dias_recompra_sugerido && item.dias_recompra_sugerido > 0) {
                    temPrevisao = true;
                    const duracaoTotalItem = parseInt(item.dias_recompra_sugerido, 10) * parseInt(item.quantidade, 10);
                    if (duracaoTotalItem < menorDuracao) {
                        menorDuracao = duracaoTotalItem;
                        itemPrincipal = item;
                    }
                }
            });

            const diasDeAntecedencia = 5; 
            let dataSugerida = new Date(dataVenda);
            let descricaoFinal = `Realizar acompanhamento (follow-up) com o cliente ${clienteNome} referente à venda #${vendaId}.`;
            
            if (temPrevisao) {
                dataSugerida.setDate(dataVenda.getDate() + menorDuracao - diasDeAntecedencia);
                const dataPrevistaRecompra = new Date(dataVenda);
                dataPrevistaRecompra.setDate(dataVenda.getDate() + menorDuracao);
                const dataRecompraFormatada = dataPrevistaRecompra.toLocaleDateString('pt-BR');
                descricaoFinal += `\n\nPrevisão inteligente: O produto "${itemPrincipal.nome}" (Qtd: ${itemPrincipal.quantidade}) deve acabar por volta de ${dataRecompraFormatada}. Entrar em contato para oferecer nova remessa.`;
            } else {
                dataSugerida.setDate(dataVenda.getDate() + 15);
                descricaoFinal += `\n\nNenhum produto nesta venda possui um ciclo de recompra definido. Verifique a necessidade do cliente.`;
            }
            
            const dataFinalFormatada = dataSugerida.toISOString().split('T')[0];
            document.getElementById('data_lembrete').value = dataFinalFormatada;
            document.getElementById('descricao').value = descricaoFinal;

        } catch (error) {
            console.error('Falha na operação:', error);
            alert(`Ocorreu um erro ao buscar os detalhes da venda:\n${error.message}\n\nVerifique o console do navegador (F12) para mais detalhes.`);
            
            let dataFallback = new Date(dataVenda);
            dataFallback.setDate(dataVenda.getDate() + 7);
            document.getElementById('data_lembrete').value = dataFallback.toISOString().split('T')[0];
            document.getElementById('descricao').value = `Não foi possível calcular a data preditiva devido a um erro. Por favor, preencha os detalhes manualmente para a venda #${vendaId}.`;
        } finally {
            loadingDiv.style.display = 'none';
            formFieldsDiv.style.display = 'block';
        }
    });
});
</script>
