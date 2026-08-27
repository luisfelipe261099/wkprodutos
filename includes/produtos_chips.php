<?php
/**
 * Chips de filtros ativos da listagem de produtos.
 *
 * Renderizado na carga completa da página e nas atualizações via AJAX
 * (produtos.php?ajax=1), por isso vive em um arquivo separado.
 */
if (!isset($possui_filtros)) {
    return;
}

$rotulos_estoque = [
    'critico'   => 'Crítico',
    'zerado'    => 'Zerado',
    'ok'        => 'Normal',
    'ilimitado' => 'Ilimitado',
];

$chips = [];

if ($filtro_busca !== '') {
    $chips[] = [
        'rotulo' => 'Busca',
        'valor'  => '"' . $filtro_busca . '"',
        'url'    => produtos_url($filtros_atuais, ['search' => '']),
    ];
}
if ($filtro_empresa !== '') {
    $chips[] = [
        'rotulo' => 'Empresa',
        'valor'  => $empresa_selecionada_nome ?: $filtro_empresa,
        'url'    => produtos_url($filtros_atuais, ['empresa_id' => '', 'fornecedor' => '']),
    ];
}
if ($filtro_fornecedor !== '') {
    $chips[] = [
        'rotulo' => 'Fornecedor',
        'valor'  => $filtro_fornecedor,
        'url'    => produtos_url($filtros_atuais, ['fornecedor' => '']),
    ];
}
if ($filtro_estoque !== '') {
    $chips[] = [
        'rotulo' => 'Estoque',
        'valor'  => $rotulos_estoque[$filtro_estoque] ?? ucfirst($filtro_estoque),
        'url'    => produtos_url($filtros_atuais, ['estoque' => '']),
    ];
}
if ($ordenacao !== 'nome_asc') {
    $chips[] = [
        'rotulo' => 'Ordem',
        'valor'  => $ordenacoes[$ordenacao]['label'],
        'url'    => produtos_url($filtros_atuais, ['ordem' => '']),
    ];
}
?>
<?php if (!empty($chips)): ?>
    <div class="filter-chips">
        <span class="filter-chips-label"><i class="fas fa-sliders-h me-1"></i> Filtros ativos:</span>
        <?php foreach ($chips as $chip): ?>
            <a class="filter-chip" href="<?php echo htmlspecialchars($chip['url']); ?>"
               title="Remover filtro — <?php echo htmlspecialchars($chip['rotulo'] . ': ' . $chip['valor']); ?>">
                <span class="filter-chip-label"><?php echo htmlspecialchars($chip['rotulo']); ?>:</span>
                <span class="filter-chip-value"><?php echo htmlspecialchars($chip['valor']); ?></span>
                <i class="fas fa-times"></i>
            </a>
        <?php endforeach; ?>
        <a class="filter-chip filter-chip-reset" href="produtos.php" title="Remover todos os filtros">
            <i class="fas fa-eraser"></i> Limpar tudo
        </a>
    </div>
<?php endif; ?>
