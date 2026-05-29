# Performance — diagnóstico e próximos passos

## Resumo do problema
O sistema roda em **Vercel serverless** (`vercel-php@0.9.0`, ver `vercel.json`) com **banco de dados remoto** acessado por **TLS** (`includes/db_connect.php`, `DB_SSL` ligado por padrão; dumps `..._tidb.sql` indicam TiDB Cloud).

Nessa arquitetura, o maior custo **não são as queries em si**, e sim:
1. Cada requisição abre uma **conexão nova + handshake TLS** ao banco remoto.
2. **Latência de rede** multiplicada pelo número de queries por página.
3. **Cold start** do runtime serverless.

## O que já foi feito no código
- **Dashboard:** de ~12 para 6 queries (consultas agrupadas) e filtros de data reescritos para usarem índice (`data_venda >= ... AND < ...` em vez de `DATE()/MONTH()/YEAR()`).
- **CSS:** deixou de ser embutido inline (41 KB por página) e passou a ser servido como arquivo estático cacheável (`/css/main.css?v=mtime`).
- **Chart.js:** carregado só no dashboard e com `defer` (não bloqueia a renderização).
- **Índices:** script em `db_indices.sql` (rodar no banco).

## Próximos passos de infra (maior impacto primeiro)

### 1. Região da função × região do banco  ⭐ (geralmente o maior ganho)
Se a função na Vercel e o banco (TiDB) estão em **regiões diferentes**, cada ida-e-volta custa 100–200 ms — e há várias por página.
- Confira a região do projeto na Vercel (Project → Settings → Functions → Region) e a região do cluster TiDB.
- **Deixe as duas na mesma região** (ou o mais próximo possível). Isso sozinho costuma cortar boa parte da lentidão.

### 2. Reduzir o custo de conexão por requisição
- **TiDB Cloud Serverless** já oferece um endpoint com pooling — confirme que a string de conexão usa o endpoint recomendado para serverless.
- Conexão **persistente** do mysqli (prefixo `p:` no host) ajuda **apenas** se o processo for reaproveitado entre requisições. No runtime atual o ganho é limitado e pode estourar o limite de conexões do banco — teste com cautela, não aplique às cegas.

### 3. Avaliar sair do serverless para o painel administrativo
Este painel é um CRUD clássico com muitas queries por página — o modelo serverless + banco remoto é o pior cenário para esse padrão. Os dumps indicam origem em **hospedagem PHP tradicional (Hostinger)**.
- Um host PHP comum (always-on), com o banco próximo/no mesmo provedor, **elimina o cold start e o reconnect por request** e tende a ser muito mais rápido para este tipo de app.
- Alternativa na Vercel: migrar para um runtime mais novo / **Fluid Compute** (reaproveita instâncias e reduz cold start). Requer validar compatibilidade do PHP nesse modelo.

### 4. Cache de agregados do dashboard (opcional)
As estatísticas do dashboard não precisam ser exatas ao segundo. Dá para cachear o resultado por alguns minutos (sessão, arquivo temporário ou um KV) e evitar recalcular a cada carregamento.

## Como medir o ganho
No painel da Vercel (Observability/Logs) acompanhe a **duração das funções** antes/depois. Para o banco, o console do TiDB mostra as queries mais lentas — confirme que, após criar os índices, as consultas de `vendas`/`itens_venda` deixaram de fazer *full table scan* (use `EXPLAIN`).
