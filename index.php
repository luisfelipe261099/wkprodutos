<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Karla Wollinger Representacoes | Produtos de Limpeza, Higiene e Embalagens em Curitiba';
$metaDescription = 'Solucoes completas em limpeza, higiene, papeis, sacos de lixo, embalagens e descartaveis para empresas em Curitiba e regiao. Solicite seu orcamento.';

kw_render_head($metaTitle, $metaDescription, 'inicio');

$categories = kw_site_categories();
$brands = kw_site_brands();
$benefits = kw_site_benefits();
$products = array_slice(kw_site_products(), 0, 8);
?>

<main>
    <section class="hero">
        <div class="container hero-panel reveal">
            <div>
                <h1>Solucoes completas em higiene, limpeza, embalagens e descartaveis para sua empresa</h1>
                <p>Representacao comercial de marcas reconhecidas, com atendimento agil, mix inteligente e suporte para empresas em Curitiba e regiao metropolitana.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Quero solicitar um orcamento para minha empresa.')); ?>" target="_blank" rel="noopener">Solicitar orcamento</a>
                    <a class="btn btn-secondary" href="/contato.php">Falar com uma especialista</a>
                </div>
                <div class="hero-badges">
                    <span>Atendimento regional</span>
                    <span>Marcas representadas de referencia</span>
                    <span>Resposta comercial rapida</span>
                </div>
            </div>
            <div class="hero-visual">
                <div>
                    <h3>Karla Wollinger Representacoes</h3>
                    <p>Parceira comercial para empresas que exigem qualidade, variedade e agilidade.</p>
                </div>
                <div class="hero-grid">
                    <div>Papeis institucionais</div>
                    <div>Limpeza profissional</div>
                    <div>Descartaveis</div>
                    <div>Embalagens</div>
                </div>
            </div>
        </div>
    </section>

    <section class="section alt" id="categorias">
        <div class="container">
            <div class="section-head reveal">
                <h2>O que trabalhamos</h2>
                <p>Categorias com produtos para operacao empresarial, limpeza profissional e abastecimento recorrente.</p>
            </div>
            <div class="cards-grid">
                <?php foreach ($categories as $category): ?>
                    <article class="card reveal">
                        <div class="card-icon"><i class="fa-solid <?php echo kw_esc($category['icone']); ?>"></i></div>
                        <h3><?php echo kw_esc($category['nome']); ?></h3>
                        <p><?php echo kw_esc($category['descricao']); ?></p>
                        <a class="btn btn-secondary" href="/<?php echo kw_esc($category['slug']); ?>">Quero orcamento</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="marcas">
        <div class="container">
            <div class="section-head reveal">
                <h2>Marcas representadas</h2>
                <p>Trabalhamos com marcas reconhecidas para oferecer qualidade, variedade e excelente custo-beneficio.</p>
            </div>
            <div class="brand-grid">
                <?php foreach ($brands as $brand): ?>
                    <div class="brand-item reveal"><?php echo kw_esc($brand); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section alt" id="diferenciais">
        <div class="container">
            <div class="section-head reveal">
                <h2>Por que comprar com a Karla</h2>
                <p>Mais velocidade comercial e menos atrito no abastecimento da sua empresa.</p>
            </div>
            <div class="cards-grid">
                <?php foreach ($benefits as $benefit): ?>
                    <article class="card reveal">
                        <div class="card-icon"><i class="fa-solid <?php echo kw_esc($benefit['icone']); ?>"></i></div>
                        <h3><?php echo kw_esc($benefit['titulo']); ?></h3>
                        <p><?php echo kw_esc($benefit['texto']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container cta-band reveal">
            <div>
                <h3>Precisa de produtos de limpeza, higiene ou embalagens para sua empresa?</h3>
                <p>Fale agora e receba atendimento rapido para cotacao, recomendacao de linha e prazo comercial.</p>
            </div>
            <div class="cta-actions">
                <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Oi! Preciso de cotacao para limpeza, higiene e embalagens.')); ?>" target="_blank" rel="noopener">WhatsApp</a>
                <a class="btn btn-secondary" href="/contato.php">Formulario</a>
            </div>
        </div>
    </section>

    <section class="section alt">
        <div class="container">
            <div class="section-head reveal">
                <h2>Produtos em destaque</h2>
                <p>Catalogo comercial para consulta rapida e solicitacao de orcamento.</p>
            </div>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card reveal">
                        <div class="product-thumb"><i class="fa-solid fa-box"></i></div>
                        <div class="product-top">
                            <span class="pill"><?php echo kw_esc(kw_category_name($product['categoria'])); ?></span>
                            <span class="pill"><?php echo kw_esc($product['marca']); ?></span>
                        </div>
                        <h3><?php echo kw_esc($product['nome']); ?></h3>
                        <p><?php echo kw_esc($product['descricao']); ?></p>
                        <a class="btn btn-secondary" href="<?php echo kw_esc(kw_whatsapp_link('Quero orcamento para o produto: ' . $product['nome'])); ?>" target="_blank" rel="noopener">Solicitar orcamento</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <h2>Area de atendimento</h2>
                <p>Atendimento comercial para Curitiba e regiao, com foco em empresas, condominios, clinicas, escolas e comercios.</p>
            </div>
            <div class="segment-grid">
                <div class="segment-card reveal"><strong>Escritorios</strong><p>Abastecimento recorrente com previsibilidade.</p></div>
                <div class="segment-card reveal"><strong>Condominios</strong><p>Mix de limpeza e higiene para rotinas intensas.</p></div>
                <div class="segment-card reveal"><strong>Clinicas</strong><p>Padrao elevado de higiene e suporte rapido.</p></div>
                <div class="segment-card reveal"><strong>Escolas</strong><p>Produtos para fluxo intenso e controle de custos.</p></div>
                <div class="segment-card reveal"><strong>Comercios</strong><p>Opcoes para atendimento e limpeza diaria.</p></div>
                <div class="segment-card reveal"><strong>Industrias</strong><p>Solucoes tecnicas para demanda operacional.</p></div>
            </div>
        </div>
    </section>

    <section class="section alt" id="faq">
        <div class="container">
            <div class="section-head reveal">
                <h2>Perguntas frequentes</h2>
                <p>Respostas diretas para acelerar sua decisao de compra.</p>
            </div>
            <div class="faq-grid">
                <article class="faq-item reveal"><h3>Vocês atendem Curitiba e regiao?</h3><p>Sim. Atendemos Curitiba e regiao metropolitana com foco em empresas e revendas.</p></article>
                <article class="faq-item reveal"><h3>Trabalham com orcamento para empresas?</h3><p>Sim. Montamos cotacao conforme volume, categoria e tipo de operacao.</p></article>
                <article class="faq-item reveal"><h3>Quais marcas voces representam?</h3><p>BellPlus, Suave Lar, Troppel, Estilo, AFP, Baston e Ecopel.</p></article>
                <article class="faq-item reveal"><h3>Posso pedir por categoria?</h3><p>Sim. Podemos montar uma sugestao completa por categoria ou por segmento.</p></article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <h2>Solicite atendimento rapido</h2>
                <p>Preencha o formulario para receber retorno comercial.</p>
            </div>
            <div class="form-card reveal">
                <form class="form-grid" action="contato.php" method="get">
                    <div>
                        <label for="nome">Nome</label>
                        <input id="nome" name="nome" type="text" required>
                    </div>
                    <div>
                        <label for="empresa">Empresa</label>
                        <input id="empresa" name="empresa" type="text" required>
                    </div>
                    <div>
                        <label for="whatsapp">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" type="text" required>
                    </div>
                    <div>
                        <label for="cidade">Cidade</label>
                        <input id="cidade" name="cidade" type="text" required>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label for="mensagem">Mensagem</label>
                        <textarea id="mensagem" name="mensagem" placeholder="Conte o que sua empresa precisa."></textarea>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <button class="btn btn-primary" type="submit">Peça sua cotacao</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php kw_render_footer(); ?>

