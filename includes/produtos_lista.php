<?php
/**
 * Resultado da listagem de produtos (tabela + cards mobile + paginação).
 *
 * Renderizado na carga completa da página e nas atualizações via AJAX
 * (produtos.php?ajax=1), por isso vive em um arquivo separado.
 */
if (!isset($produtos_data)) {
    return;
}
?>
<?php if ($total_ilimitados > 0): ?>
    <p class="text-muted small mb-3">
        <i class="fas fa-infinity me-1"></i>
        <?php echo $total_ilimitados; ?> produto<?php echo $total_ilimitados === 1 ? '' : 's'; ?> com estoque ilimitado
        <?php echo $total_ilimitados === 1 ? 'não é considerado' : 'não são considerados'; ?> no valor em estoque.
    </p>
<?php endif; ?>

<?php if (!empty($produtos_data)): ?>
    <div class="table-responsive table-responsive-custom">
        <table class="table table-hover mb-0" id="produtosTable">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Empresa</th>
                    <th>Preço / Lucro</th>
                    <th class="text-center">Estoque</th>
                    <th>Fornecedor</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos_data as $row): ?>
                    <?php
                    $estoque = (int)$row['quantidade_estoque'];
                    $ilimitado = $estoque < 0;
                    $estoque_baixo = !$ilimitado && $estoque <= (int)$row['estoque_minimo'];
                    ?>
                    <tr>
                        <td class="col-produto">
                            <div class="fw-semibold cell-ellipsis" title="<?php echo htmlspecialchars($row['nome']); ?>"><?php echo htmlspecialchars($row['nome']); ?></div>
                            <small class="text-muted">
                                #<?php echo $row['id']; ?>
                                <?php if (!empty($row['sku'])): ?>
                                    &middot; SKU <code class="bg-light px-1 rounded"><?php echo htmlspecialchars($row['sku']); ?></code>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td class="col-empresa">
                            <?php if (!empty($row['nome_empresa'])): ?>
                                <a class="text-decoration-none cell-ellipsis d-block"
                                   href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['empresa_id' => (int)$row['empresa_id'], 'fornecedor' => '', 'page' => ''])); ?>"
                                   title="Filtrar por: <?php echo htmlspecialchars($row['nome_empresa']); ?>">
                                    <i class="fas fa-building me-1 text-muted"></i><?php echo htmlspecialchars($row['nome_empresa']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-minus me-1"></i>Sem empresa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-success">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></div>
                            <small class="text-muted">Lucro: <?php echo number_format($row['percentual_lucro'], 2, ',', '.'); ?>%</small>
                        </td>
                        <td class="text-center">
                            <?php if ($ilimitado): ?>
                                <span class="status-badge status-info" title="Estoque ilimitado">
                                    <i class="fas fa-infinity me-1"></i>Ilimitado
                                </span>
                            <?php else: ?>
                                <span class="status-badge <?php echo $estoque_baixo ? 'status-danger' : 'status-success'; ?>" title="Mínimo: <?php echo (int)$row['estoque_minimo']; ?>">
                                    <?php echo $estoque; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="col-fornecedor">
                            <span class="cell-ellipsis d-block" title="<?php echo htmlspecialchars($row['fornecedor'] ?: 'N/A'); ?>"><?php echo htmlspecialchars($row['fornecedor'] ?: 'N/A'); ?></span>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="cadastro_produto.php?id=<?php echo $row['id']; ?>"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger"
                                           href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['page' => $pagina_atual > 1 ? $pagina_atual : '', 'action' => 'delete', 'id' => $row['id']])); ?>"
                                           onclick="return confirm('Tem certeza que deseja excluir este produto? A exclusão só será permitida se ele não estiver em nenhuma venda ou orçamento.');"><i class="fas fa-trash-alt me-2"></i>Excluir</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cards para Mobile -->
    <div class="mobile-items-container">
        <?php foreach ($produtos_data as $row): ?>
            <?php
            $estoque = (int)$row['quantidade_estoque'];
            $ilimitado = $estoque < 0;
            $estoque_baixo = !$ilimitado && $estoque <= (int)$row['estoque_minimo'];
            ?>
            <div class="mobile-item-card">
                <div class="mobile-item-title">
                    <?php echo htmlspecialchars($row['nome']); ?>
                    <small class="text-muted d-block fw-normal">
                        #<?php echo $row['id']; ?>
                        <?php if (!empty($row['sku'])): ?> &middot; SKU: <?php echo htmlspecialchars($row['sku']); ?><?php endif; ?>
                    </small>
                </div>

                <div class="mobile-item-meta">
                    <div>
                        <span class="mobile-item-meta-label">Empresa</span>
                        <span class="mobile-item-meta-value"><?php echo htmlspecialchars($row['nome_empresa'] ?: 'Sem empresa'); ?></span>
                    </div>
                    <div>
                        <span class="mobile-item-meta-label">Preço de Venda</span>
                        <span class="mobile-item-meta-value text-success">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></span>
                    </div>
                    <div>
                        <span class="mobile-item-meta-label">Margem de Lucro</span>
                        <span class="mobile-item-meta-value"><?php echo number_format($row['percentual_lucro'], 2, ',', '.'); ?>%</span>
                    </div>
                    <div>
                        <span class="mobile-item-meta-label">Estoque</span>
                        <span class="mobile-item-meta-value">
                            <?php if ($ilimitado): ?>
                                <span class="status-badge status-info"><i class="fas fa-infinity me-1"></i> Ilimitado</span>
                            <?php else: ?>
                                <span class="status-badge <?php echo $estoque_baixo ? 'status-danger' : 'status-success'; ?>">
                                    <?php echo $estoque; ?> un.
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!$ilimitado): ?>
                    <div>
                        <span class="mobile-item-meta-label">Estoque Mínimo</span>
                        <span class="mobile-item-meta-value"><?php echo (int)$row['estoque_minimo']; ?> un.</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($row['fornecedor'])): ?>
                    <div>
                        <span class="mobile-item-meta-label">Fornecedor</span>
                        <span class="mobile-item-meta-value"><?php echo htmlspecialchars($row['fornecedor']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mobile-item-actions">
                    <a href="cadastro_produto.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Produto">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <a href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['page' => $pagina_atual > 1 ? $pagina_atual : '', 'action' => 'delete', 'id' => $row['id']])); ?>"
                       class="btn btn-sm btn-outline-danger" title="Excluir Produto"
                       onclick="return confirm('Tem certeza que deseja excluir este produto? A exclusão só será permitida se ele não estiver em nenhuma venda ou orçamento.');">
                        <i class="fas fa-trash-alt me-1"></i> Excluir
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($total_produtos_geral === 0): ?>
    <div class="text-center py-5">
        <div class="stats-icon primary mx-auto mb-3"><i class="fas fa-box-open"></i></div>
        <h5 class="text-muted mb-2">Nenhum produto cadastrado</h5>
        <p class="text-muted">Comece adicionando seu primeiro produto ao sistema.</p>
        <a href="cadastro_produto.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Adicionar Primeiro Produto</a>
    </div>
<?php else: ?>
    <div class="text-center py-5">
        <div class="stats-icon info mx-auto mb-3"><i class="fas fa-filter"></i></div>
        <h5 class="text-muted mb-2">
            <?php if ($filtro_busca !== ''): ?>
                Nenhum produto encontrado para &ldquo;<?php echo htmlspecialchars($filtro_busca); ?>&rdquo;
            <?php else: ?>
                Nenhum produto encontrado com os filtros aplicados
            <?php endif; ?>
        </h5>
        <p class="text-muted">Ajuste a busca, a empresa ou o fornecedor para ver mais resultados.</p>
        <a href="produtos.php" class="btn btn-outline-secondary"><i class="fas fa-eraser me-2"></i> Limpar filtros</a>
    </div>
<?php endif; ?>

<?php
echo render_pagination([
    'current_page' => $pagina_atual,
    'total_pages' => $total_paginas,
    'page_param' => 'page',
    'base_params' => $filtros_ativos_query,
    'aria_label' => 'Paginacao de produtos',
    'summary' => $total_produtos_db . ' produtos encontrados',
    'window' => 7,
    'labels' => [
        'first' => '&laquo;&laquo;',
        'previous' => '&laquo;',
        'next' => '&raquo;',
        'last' => '&raquo;&raquo;'
    ]
]);
?>
