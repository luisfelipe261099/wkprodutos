-- ============================================================================
--  ÍNDICES DE PERFORMANCE - Karla Wollinger / wkprodutos
-- ----------------------------------------------------------------------------
--  Por que: as telas (dashboard, vendas, financeiro, orçamentos, agendamentos)
--  filtram/juntam por estas colunas. Sem índice, o banco varre a tabela inteira
--  a cada consulta -- o que fica caro conforme os dados crescem e PIOR ainda
--  com banco remoto (cada query é uma ida-e-volta de rede).
--
--  Como rodar:
--    - TiDB Cloud / MySQL 8: cole e execute este arquivo no console SQL.
--    - "CREATE INDEX IF NOT EXISTS" é suportado pelo TiDB. Se o seu MySQL
--      reclamar do "IF NOT EXISTS", remova esse trecho ou ignore o erro de
--      índice duplicado (significa que já existe).
--
--  Seguro: criar índice NÃO altera dados. Em tabelas grandes pode levar alguns
--  segundos. Rode fora do horário de pico se a base for muito grande.
-- ============================================================================

-- VENDAS ---------------------------------------------------------------------
-- Filtro status + intervalo de data (dashboard, financeiro):
CREATE INDEX IF NOT EXISTS idx_vendas_status_data ON vendas (status_venda, data_venda);
-- Listagem "últimas vendas" / ordenação por data:
CREATE INDEX IF NOT EXISTS idx_vendas_data        ON vendas (data_venda);
-- JOIN/relacionamento com clientes:
CREATE INDEX IF NOT EXISTS idx_vendas_cliente     ON vendas (cliente_id);

-- ITENS_VENDA (usado nos cálculos de lucro com JOIN) ------------------------
CREATE INDEX IF NOT EXISTS idx_itens_venda_venda   ON itens_venda (venda_id);
CREATE INDEX IF NOT EXISTS idx_itens_venda_produto ON itens_venda (produto_id);

-- ORÇAMENTOS -----------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_orcamentos_status  ON orcamentos (status_orcamento);
CREATE INDEX IF NOT EXISTS idx_orcamentos_cliente ON orcamentos (cliente_id);

-- ITENS_ORCAMENTO ------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_itens_orc_orcamento ON itens_orcamento (orcamento_id);
CREATE INDEX IF NOT EXISTS idx_itens_orc_produto   ON itens_orcamento (produto_id);

-- AGENDAMENTOS DE ENTREGA (dashboard: próximos 7 dias) ----------------------
CREATE INDEX IF NOT EXISTS idx_agend_data_status ON agendamentos_entrega (data_hora_entrega, status_entrega);

-- TRANSAÇÕES FINANCEIRAS (listagem ordenada por data) -----------------------
CREATE INDEX IF NOT EXISTS idx_transacoes_data ON transacoes_financeiras (data_transacao);

-- ============================================================================
--  Observação sobre "produtos com estoque crítico":
--  A condição é  quantidade_estoque <= estoque_minimo  (coluna vs coluna),
--  que NENHUM índice comum consegue acelerar. É barato porque a tabela de
--  produtos costuma ser pequena. Se um dia ficar grande, a saída é manter uma
--  coluna/flag "estoque_critico" atualizada por trigger e indexá-la.
-- ============================================================================
