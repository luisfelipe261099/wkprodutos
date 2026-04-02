<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Blog | Dicas de higiene e limpeza profissional em Curitiba';
$metaDescription = 'Conteudos sobre produtos de limpeza profissional, higiene empresarial, sacos de lixo e papeis para empresas em Curitiba.';

kw_render_head($metaTitle, $metaDescription, 'inicio');
?>

<main class="section">
    <div class="container">
        <div class="section-head reveal">
            <h1>Blog e dicas comerciais</h1>
            <p>Conteudos para ajudar empresas a comprar melhor, reduzir custo operacional e manter padrao de higiene.</p>
        </div>

        <div class="cards-grid">
            <article class="card reveal">
                <h3>Como escolher produtos de limpeza profissional</h3>
                <p>Guia pratico para selecionar linha tecnica por tipo de operacao e frequencia de uso.</p>
            </article>
            <article class="card reveal">
                <h3>Qual saco de lixo usar em cada tipo de empresa</h3>
                <p>Entenda capacidade, espessura e aplicacao ideal para evitar desperdicio.</p>
            </article>
            <article class="card reveal">
                <h3>Diferenca entre papel toalha e papel bobina</h3>
                <p>Comparativo para apoiar decisao de compra conforme fluxo e custo por uso.</p>
            </article>
            <article class="card reveal">
                <h3>Produtos essenciais para limpeza empresarial</h3>
                <p>Checklist para empresas que querem padronizar operacao e melhorar produtividade.</p>
            </article>
            <article class="card reveal">
                <h3>Como reduzir custos com produtos de higiene</h3>
                <p>Recomendacoes de controle de consumo sem comprometer qualidade.</p>
            </article>
        </div>
    </div>
</main>

<?php kw_render_footer(); ?>
