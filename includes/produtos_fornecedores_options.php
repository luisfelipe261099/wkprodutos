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
?>
<option value="">Todos os fornecedores</option>
<?php foreach ($fornecedores_list as $fornecedor): ?>
    <option value="<?php echo htmlspecialchars($fornecedor); ?>" <?php echo ($filtro_fornecedor === $fornecedor) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($fornecedor); ?>
    </option>
<?php endforeach; ?>
