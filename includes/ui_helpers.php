<?php

if (!function_exists('fmt_brl')) {
    /**
     * Abbreviates large BRL currency values for stats cards.
     * e.g. 13869322 → R$ 13,9M  | 85000 → R$ 85K  | 950 → R$ 950,00
     */
    function fmt_brl(float $value, int $precision = 1): string {
        $abs = abs($value);
        if ($abs >= 1_000_000) {
            return 'R$ ' . number_format($value / 1_000_000, $precision, ',', '.') . 'M';
        }
        if ($abs >= 10_000) {
            return 'R$ ' . number_format($value / 1_000, $precision, ',', '.') . 'K';
        }
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

if (!function_exists('fmt_num_short')) {
    /**
     * Abbreviates large numbers. e.g. 1350000 → 1,4M  | 85000 → 85K
     */
    function fmt_num_short(float $value, int $precision = 1): string {
        $abs = abs($value);
        if ($abs >= 1_000_000) {
            return number_format($value / 1_000_000, $precision, ',', '.') . 'M';
        }
        if ($abs >= 10_000) {
            return number_format($value / 1_000, $precision, ',', '.') . 'K';
        }
        return number_format($value, 0, ',', '.');
    }
}

if (!function_exists('build_pagination_url')) {
    function build_pagination_url(array $params, string $pageParam, int $page): string
    {
        $params[$pageParam] = $page;
        $query = http_build_query($params);
        return $query === '' ? '?' : '?' . $query;
    }
}

if (!function_exists('render_pagination')) {
    function render_pagination(array $options): string
    {
        $currentPage = max(1, (int) ($options['current_page'] ?? 1));
        $totalPages = max(1, (int) ($options['total_pages'] ?? 1));

        if ($totalPages <= 1) {
            return '';
        }

        $pageParam = (string) ($options['page_param'] ?? 'page');
        $baseParams = $options['base_params'] ?? $_GET;
        $labels = $options['labels'] ?? [];
        $window = max(3, (int) ($options['window'] ?? 5));
        $summary = $options['summary'] ?? null;
        $ariaLabel = (string) ($options['aria_label'] ?? 'Paginacao');

        unset($baseParams[$pageParam], $baseParams['action'], $baseParams['id'], $baseParams['success'], $baseParams['venda_id']);

        $halfWindow = (int) floor($window / 2);
        $startPage = max(1, $currentPage - $halfWindow);
        $endPage = min($totalPages, $startPage + $window - 1);

        if (($endPage - $startPage + 1) < $window) {
            $startPage = max(1, $endPage - $window + 1);
        }

        $firstLabel = $labels['first'] ?? '&laquo;&laquo;';
        $previousLabel = $labels['previous'] ?? '&laquo;';
        $nextLabel = $labels['next'] ?? '&raquo;';
        $lastLabel = $labels['last'] ?? '&raquo;&raquo;';

        ob_start();
        ?>
        <nav aria-label="<?php echo htmlspecialchars($ariaLabel); ?>" class="pagination-shell mt-4">
            <div class="pagination-meta">
                <?php if (!empty($summary)): ?>
                    <span class="pagination-summary"><?php echo htmlspecialchars((string) $summary); ?></span>
                <?php else: ?>
                    <span class="pagination-summary">Pagina <?php echo $currentPage; ?> de <?php echo $totalPages; ?></span>
                <?php endif; ?>
            </div>
            <ul class="pagination pagination-modern justify-content-center mb-0">
                <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <?php if ($currentPage <= 1): ?>
                        <span class="page-link" aria-hidden="true"><?php echo $firstLabel; ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?php echo htmlspecialchars(build_pagination_url($baseParams, $pageParam, 1)); ?>" aria-label="Primeira pagina"><?php echo $firstLabel; ?></a>
                    <?php endif; ?>
                </li>
                <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <?php if ($currentPage <= 1): ?>
                        <span class="page-link" aria-hidden="true"><?php echo $previousLabel; ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?php echo htmlspecialchars(build_pagination_url($baseParams, $pageParam, $currentPage - 1)); ?>" aria-label="Pagina anterior"><?php echo $previousLabel; ?></a>
                    <?php endif; ?>
                </li>

                <?php if ($startPage > 1): ?>
                    <li class="page-item disabled d-none d-sm-inline-flex"><span class="page-link">...</span></li>
                <?php endif; ?>

                <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
                    <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
                        <?php if ($page === $currentPage): ?>
                            <span class="page-link"><?php echo $page; ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?php echo htmlspecialchars(build_pagination_url($baseParams, $pageParam, $page)); ?>"><?php echo $page; ?></a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <li class="page-item disabled d-none d-sm-inline-flex"><span class="page-link">...</span></li>
                <?php endif; ?>

                <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <?php if ($currentPage >= $totalPages): ?>
                        <span class="page-link" aria-hidden="true"><?php echo $nextLabel; ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?php echo htmlspecialchars(build_pagination_url($baseParams, $pageParam, $currentPage + 1)); ?>" aria-label="Proxima pagina"><?php echo $nextLabel; ?></a>
                    <?php endif; ?>
                </li>
                <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <?php if ($currentPage >= $totalPages): ?>
                        <span class="page-link" aria-hidden="true"><?php echo $lastLabel; ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?php echo htmlspecialchars(build_pagination_url($baseParams, $pageParam, $totalPages)); ?>" aria-label="Ultima pagina"><?php echo $lastLabel; ?></a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
        <?php

        return trim(ob_get_clean());
    }
}
