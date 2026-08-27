<?php
/**
 * Opções do filtro de fornecedor (dependem da empresa selecionada).
 *
 * Usado no formulário de filtros e na resposta AJAX, que atualiza a lista
 * quando o usuário troca de empresa.
 */
if (!isset($fornecedores_list)) {
    return;
}

// O filtro é por igualdade exata. Se o valor que veio na URL não estiver na
// lista (empresa trocada, fornecedor renomeado ou link antigo), o select
// mostraria "Todos os fornecedores" enquanto a consulta continua filtrando —
// e o produto procurado simplesmente "some". Então ele sempre aparece aqui.
$fornecedores_visiveis = $fornecedores_list;
$fornecedor_orfao = $filtro_fornecedor !== '' && !in_array($filtro_fornecedor, $fornecedores_list, true);
?>
<option value="">Todos os fornecedores</option>
<?php if ($fornecedor_orfao): ?>
    <option value="<?php echo htmlspecialchars($filtro_fornecedor); ?>" selected>
        <?php echo htmlspecialchars($filtro_fornecedor); ?> (sem produtos aqui)
    </option>
<?php endif; ?>
<?php foreach ($fornecedores_visiveis as $fornecedor): ?>
    <option value="<?php echo htmlspecialchars($fornecedor); ?>" <?php echo ($filtro_fornecedor === $fornecedor) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($fornecedor); ?>
    </option>
<?php endforeach; ?>
