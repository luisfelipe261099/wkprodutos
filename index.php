<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Produtos de Limpeza Direto da Fabrica | Menor Preco em Curitiba - Karla Wollinger';
$metaDescription = 'Produtos de limpeza, higiene, papeis, embalagens e descartaveis direto da fabrica com menor preco para empresas em Curitiba e regiao. Sem intermediarios. Solicite orcamento gratis.';

kw_render_head($metaTitle, $metaDescription, 'inicio');

$categories = kw_site_categories();
$brands = kw_site_brands();
$benefits = kw_site_benefits();
$products = array_slice(kw_site_products(), 0, 8);
?>

<main itemscope itemtype="https://schema.org/WebPage">
    <section class="hero">
        <div class="container hero-panel reveal">
            <div>
                <h1>Produtos de limpeza, higiene e embalagens direto da fabrica com o menor preco para sua empresa</h1>
                <p>Representacao comercial de fabricas reconhecidas — <strong>sem intermediarios</strong>, com atendimento agil, mix completo e precos competitivos para empresas em Curitiba e regiao metropolitana.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Quero solicitar um orcamento para minha empresa.')); ?>" target="_blank" rel="noopener">Solicitar orcamento gratis</a>
                    <a class="btn btn-secondary" href="<?php echo kw_esc(kw_site_url('contato.php')); ?>">Falar com uma especialista</a>
                </div>
                <div class="hero-badges">
                    <span><i class="fa-solid fa-industry"></i> Direto da fabrica</span>
                    <span><i class="fa-solid fa-tag"></i> Menor preco garantido</span>
                    <span><i class="fa-solid fa-bolt"></i> Resposta em ate 2h</span>
                </div>
            </div>
            <div class="hero-visual">
                <div>
                    <h3>Karla Wollinger Representacoes</h3>
                    <p>Parceira comercial das principais fabricas — qualidade, variedade e preco justo sem intermediarios.</p>
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
                        <a class="btn btn-secondary" href="<?php echo kw_esc(kw_site_url($category['slug'])); ?>">Quero orcamento</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="marcas">
        <div class="container">
            <div class="section-head reveal">
                <h2>Fabricas e marcas que representamos</h2>
                <p>Trabalhamos diretamente com fabricas, garantindo <strong>preco de fabrica</strong>, qualidade certificada e sem intermediarios.</p>
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
                <h2>Por que comprar direto com a fabrica pela Karla</h2>
                <p>Mais economia, velocidade comercial e menos atrito no abastecimento da sua empresa.</p>
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
                <h3>Compre direto da fabrica — produtos de limpeza e higiene com o menor preco</h3>
                <p>Sem intermediarios, sem markup de distribuidor. Fale agora e receba cotacao com preco de fabrica, linha recomendada e prazo comercial.</p>
            </div>
            <div class="cta-actions">
                <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Oi! Quero cotacao direto de fabrica para limpeza, higiene e embalagens.')); ?>" target="_blank" rel="noopener">WhatsApp</a>
                <a class="btn btn-secondary" href="<?php echo kw_esc(kw_site_url('contato.php')); ?>">Formulario</a>
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

    <section class="section alt" id="faq" itemscope itemtype="https://schema.org/FAQPage">
        <div class="container">
            <div class="section-head reveal">
                <h2>Perguntas frequentes</h2>
                <p>Respostas diretas para acelerar sua decisao de compra.</p>
            </div>
            <div class="faq-grid">
                <article class="faq-item reveal" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Os produtos sao direto da fabrica?</h3>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer"><p itemprop="text">Sim. Somos representantes comerciais das fabricas, o que significa que voce compra direto da origem, sem intermediarios e com o melhor preco do mercado.</p></div>
                </article>
                <article class="faq-item reveal" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">O preco e realmente menor que de distribuidores?</h3>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer"><p itemprop="text">Sim. Como representamos as fabricas diretamente, eliminamos as margens de distribuidores e atacadistas, repassando economia real para sua empresa.</p></div>
                </article>
                <article class="faq-item reveal" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Voces atendem Curitiba e regiao?</h3>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer"><p itemprop="text">Sim. Atendemos Curitiba, regiao metropolitana e todo o Parana, com foco em empresas, condominios, comercios e industrias.</p></div>
                </article>
                <article class="faq-item reveal" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Trabalham com orcamento para empresas?</h3>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer"><p itemprop="text">Sim. Montamos cotacao personalizada conforme volume, categoria e tipo de operacao. O orcamento e gratis e sem compromisso.</p></div>
                </article>
                <article class="faq-item reveal" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Quais marcas voces representam?</h3>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer"><p itemprop="text">Representamos BellPlus, Suave Lar, Troppel, Estilo, AFP, Baston e Ecopel — fabricas reconhecidas em produtos de higiene, limpeza e descartaveis.</p></div>
                </article>
                <article class="faq-item reveal" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Qual o pedido minimo?</h3>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer"><p itemprop="text">Trabalhamos com pedidos a partir de volumes acessiveis para pequenas e medias empresas. Entre em contato para consultar condicoes especiais para sua demanda.</p></div>
                </article>
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

