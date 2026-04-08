<?php

declare(strict_types=1);

const KW_WHATSAPP_NUMBER = '5541998593242';
const KW_WHATSAPP_LABEL = '(41) 99859-3242';
const KW_CONTACT_EMAIL = 'karlawollinger02@gmail.com';

function kw_site_categories(): array
{
    return [
        [
            'slug' => 'papeis',
            'nome' => 'Papeis',
            'descricao' => 'Papel toalha, papel higienico institucional e linhas para alto consumo.',
            'icone' => 'fa-scroll'
        ],
        [
            'slug' => 'equipamentos',
            'nome' => 'Equipamentos e Higiene',
            'descricao' => 'Dispensers, equipamentos de apoio e solucoes para higiene profissional.',
            'icone' => 'fa-pump-soap'
        ],
        [
            'slug' => 'sacos-de-lixo',
            'nome' => 'Sacos de Lixo e Embalagens',
            'descricao' => 'Linhas resistentes para coleta, organizacao e acondicionamento.',
            'icone' => 'fa-bag-shopping'
        ],
        [
            'slug' => 'aerossol',
            'nome' => 'Aerossol',
            'descricao' => 'Odorizadores e produtos de performance para ambientes corporativos.',
            'icone' => 'fa-spray-can-sparkles'
        ],
        [
            'slug' => 'limpeza-profissional',
            'nome' => 'Limpeza Profissional',
            'descricao' => 'Desengraxantes, desinfetantes e linhas tecnicas para empresas.',
            'icone' => 'fa-shield-virus'
        ],
        [
            'slug' => 'lavanderia',
            'nome' => 'Lavanderia',
            'descricao' => 'Produtos para lavanderias industriais e operacoes de alto giro.',
            'icone' => 'fa-soap'
        ],
        [
            'slug' => 'descartaveis',
            'nome' => 'Guardanapos e Descartaveis',
            'descricao' => 'Descartaveis para food service, clinicas, eventos e operacao diaria.',
            'icone' => 'fa-utensils'
        ]
    ];
}

function kw_site_brands(): array
{
    return ['BellPlus', 'Suave Lar', 'Troppel', 'Estilo', 'AFP', 'Baston', 'Ecopel'];
}

function kw_site_benefits(): array
{
    return [
        ['titulo' => 'Direto da industria', 'icone' => 'fa-industry', 'texto' => 'Representamos as maiores industrias do setor. Voce compra direto da origem, sem intermediarios — e paga muito menos.'],
        ['titulo' => 'Descontos de ate 40%', 'icone' => 'fa-percent', 'texto' => 'Sem markup de distribuidor ou atacadista. Preco de industria repassado direto — economia real de ate 40% nos produtos.'],
        ['titulo' => 'Atendimento rapido', 'icone' => 'fa-bolt', 'texto' => 'Resposta agil em ate 2 horas para operacoes que nao podem parar.'],
        ['titulo' => 'Mais de 200 produtos', 'icone' => 'fa-boxes-stacked', 'texto' => 'Mix completo: higiene, limpeza, papeis, embalagens, descartaveis e equipamentos — tudo da industria.'],
        ['titulo' => 'Sem pedido minimo alto', 'icone' => 'fa-hand-holding-dollar', 'texto' => 'Condicoes acessiveis para pequenas e medias empresas aproveitarem precos de industria.'],
        ['titulo' => '7 industrias representadas', 'icone' => 'fa-award', 'texto' => 'BellPlus, Suave Lar, Troppel, Estilo, AFP, Baston e Ecopel — marcas com certificacao e registro ANVISA.'],
        ['titulo' => 'Orcamento gratis', 'icone' => 'fa-file-signature', 'texto' => 'Cotacao gratuita e personalizada. Descubra quanto voce economiza comprando pela representacao.'],
        ['titulo' => 'Entrega em Curitiba e regiao', 'icone' => 'fa-truck-fast', 'texto' => 'Logistica direta da industria para Curitiba, regiao metropolitana e todo o Parana.']
    ];
}

function kw_site_products(): array
{
    return [
        ['nome' => 'Papel Toalha Interfolhado', 'marca' => 'Ecopel', 'categoria' => 'papeis', 'imagem' => 'img/produtos/papel-toalha.svg', 'desconto' => '30', 'descricao' => 'Alta absorcao para banheiros e cozinhas industriais. Direto da industria com desconto.'],
        ['nome' => 'Papel Higienico Rolao', 'marca' => 'BellPlus', 'categoria' => 'papeis', 'imagem' => 'img/produtos/papel-higienico.svg', 'desconto' => '35', 'descricao' => 'Maior rendimento para alto fluxo. Preco de industria sem intermediarios.'],
        ['nome' => 'Saco de Lixo Reforcado 100L', 'marca' => 'Baston', 'categoria' => 'sacos-de-lixo', 'imagem' => 'img/produtos/saco-lixo.svg', 'desconto' => '25', 'descricao' => 'Resistencia superior para operacao pesada. Economia direto da industria.'],
        ['nome' => 'Saco de Lixo 60L', 'marca' => 'AFP', 'categoria' => 'sacos-de-lixo', 'imagem' => 'img/produtos/saco-lixo-60.svg', 'desconto' => '25', 'descricao' => 'Versatil para comercios, escritorios e condominios. Preco imbativel.'],
        ['nome' => 'Detergente Profissional', 'marca' => 'Suave Lar', 'categoria' => 'limpeza-profissional', 'imagem' => 'img/produtos/detergente.svg', 'desconto' => '40', 'descricao' => 'Limpeza eficiente com rendimento elevado. Ate 40% mais barato que distribuidor.'],
        ['nome' => 'Desinfetante Hospitalar', 'marca' => 'Troppel', 'categoria' => 'limpeza-profissional', 'imagem' => 'img/produtos/desinfetante.svg', 'desconto' => '35', 'descricao' => 'Acao antimicrobiana para ambientes criticos. Direto da industria Troppel.'],
        ['nome' => 'Odorizador Aerossol', 'marca' => 'Estilo', 'categoria' => 'aerossol', 'imagem' => 'img/produtos/aerossol.svg', 'desconto' => '30', 'descricao' => 'Fragrancia duradoura para ambientes corporativos. Preco de industria.'],
        ['nome' => 'Dispenser para Papel Toalha', 'marca' => 'BellPlus', 'categoria' => 'equipamentos', 'imagem' => 'img/produtos/dispenser.svg', 'desconto' => '20', 'descricao' => 'Equipamento pratico e resistente. Compre direto da industria BellPlus.'],
        ['nome' => 'Dispenser para Sabonete', 'marca' => 'Troppel', 'categoria' => 'equipamentos', 'imagem' => 'img/produtos/sabonete-dispenser.svg', 'desconto' => '25', 'descricao' => 'Controle de consumo e higiene no ponto de uso. Sem markup de distribuidor.'],
        ['nome' => 'Guardanapo Premium', 'marca' => 'Ecopel', 'categoria' => 'descartaveis', 'imagem' => 'img/produtos/guardanapo.svg', 'desconto' => '30', 'descricao' => 'Acabamento de qualidade para atendimento ao cliente. Preco de industria.'],
        ['nome' => 'Copo Descartavel 200ml', 'marca' => 'AFP', 'categoria' => 'descartaveis', 'imagem' => 'img/produtos/copo-descartavel.svg', 'desconto' => '20', 'descricao' => 'Ideal para escritorios, recepcoes e eventos. Economia garantida.'],
        ['nome' => 'Alvejante para Lavanderia', 'marca' => 'Suave Lar', 'categoria' => 'lavanderia', 'imagem' => 'img/produtos/alvejante.svg', 'desconto' => '40', 'descricao' => 'Performance para lavanderia profissional. Ate 40% de desconto comprando pela representacao.']
    ];
}

function kw_category_name(string $slug): string
{
    foreach (kw_site_categories() as $category) {
        if ($category['slug'] === $slug) {
            return $category['nome'];
        }
    }

    return 'Categoria';
}
