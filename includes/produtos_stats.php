<?php
/**
 * Cartões de estatísticas da listagem de produtos.
 *
 * Renderizado tanto na carga completa da página quanto nas atualizações
 * via AJAX (produtos.php?ajax=1). Usa as variáveis já calculadas em
 * produtos.php: $total_produtos_db, $criticos, $valor_total_estoque,
 * $empresas_no_resultado, $filtro_empresa e $possui_filtros.
 */
if (!isset($total_produtos_db)) {
    return;
}
?>
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stats-card primary">
            <div class="stats-icon primary"><i class="fas fa-boxes"></i></div>
            <div class="stats-value"><?php echo $total_produtos_db; ?></div>
            <div class="stats-label"><?php echo $possui_filtros ? 'Produtos no filtro' : 'Total de Produtos'; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card danger">
            <div class="stats-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stats-value"><?php echo $criticos; ?></div>
            <div class="stats-label">Estoque Crítico</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card success">
            <div class="stats-icon success"><i class="fas fa-dollar-sign"></i></div>
            <div class="stats-value"><?php echo fmt_brl($valor_total_estoque); ?></div>
            <div class="stats-label">Valor em Estoque</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card info">
            <div class="stats-icon info"><i class="fas fa-building"></i></div>
            <div class="stats-value"><?php echo $filtro_empresa !== '' ? 1 : $empresas_no_resultado; ?></div>
            <div class="stats-label">Empresas no resultado</div>
        </div>
    </div>
</div>
