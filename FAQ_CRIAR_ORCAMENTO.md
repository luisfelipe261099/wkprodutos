# ❓ FAQ - Criar Orçamento (Nova Interface)

## 🔍 Perguntas Frequentes

### 1. Como buscar um produto?

**P:** Qual é a forma correta de buscar um produto?

**R:** 
```
1. Clique no campo "Buscar Produto"
2. Digite o nome, SKU ou empresa
3. Aguarde 300ms
4. Veja os resultados
5. Clique no desejado
```

**Exemplos:**
- Digitar "sabão" → mostra todos com "sabão"
- Digitar "SAB001" → mostra produto com SKU "SAB001"
- Digitar "Empresa A" → mostra produtos dessa empresa

---

### 2. Por que não vejo todos os produtos?

**P:** Por que a busca mostra apenas 10 produtos?

**R:** 
Para melhorar a performance e evitar sobrecarga, a busca mostra no máximo 10 resultados. Se não encontrar o produto:

1. Digite mais caracteres para refinar
2. Use o filtro de empresa
3. Verifique o SKU do produto
4. Certifique-se que o produto está ativo

---

### 3. Como filtrar por empresa?

**P:** Como usar o filtro de empresa?

**R:**
```
1. Selecione a empresa no dropdown
2. Digite o nome do produto
3. Vê apenas produtos dessa empresa
4. Para ver todas, selecione "Todas as empresas"
```

---

### 4. Posso mudar o preço?

**P:** Posso usar um preço diferente do preço de tabela?

**R:** 
Sim! O preço é preenchido automaticamente, mas você pode:

1. Clicar no campo "Preço Unit."
2. Digitar um novo valor
3. Usar formato: 5,50 (com vírgula)
4. Clicar "Adicionar"

---

### 5. Como editar um item?

**P:** Como mudar a quantidade ou preço de um item já adicionado?

**R:**
```
1. Clique no botão ✎ (editar) na linha do produto
2. Painel aparece com dados
3. Ajuste quantidade ou preço
4. Clique "Atualizar Item"
5. Pronto!
```

---

### 6. Como remover um item?

**P:** Como remover um produto do orçamento?

**R:**
```
1. Clique no botão ✕ (remover) na linha
2. Item é removido imediatamente
3. Total é recalculado
4. Sem confirmação (rápido)
```

---

### 7. O que significa cada botão?

**P:** Qual é a função de cada botão?

**R:**
| Botão | Função |
|-------|--------|
| ✎ | Editar item |
| ✕ | Remover item |
| ✕ (busca) | Limpar campo de busca |
| Adicionar | Adicionar produto ao orçamento |
| Atualizar | Salvar alterações do item |
| Cancelar | Cancelar edição |

---

### 8. Como adicionar o mesmo produto 2x?

**P:** Se adicionar o mesmo produto duas vezes, o que acontece?

**R:**
```
Primeira adição: 1x Sabão Neutro
Segunda adição: 1x Sabão Neutro

Resultado: 2x Sabão Neutro (quantidade somada)
Não cria linha duplicada
```

---

### 9. Qual é o tempo de busca?

**P:** Por que a busca demora um pouco?

**R:**
A busca tem um debounce de 300ms para:
- Evitar requisições desnecessárias
- Melhorar performance
- Permitir que você termine de digitar

Isso é normal e esperado.

---

### 10. Posso usar atalhos de teclado?

**P:** Existem atalhos de teclado?

**R:**
Sim! Atalhos disponíveis:

| Tecla | Ação |
|-------|------|
| `Escape` | Fecha dropdown/painel |
| `Tab` | Próximo campo |
| `Enter` | Confirma (em alguns campos) |
| `Ctrl+A` | Seleciona tudo |

---

## ⚠️ Problemas Comuns

### 11. "Por favor, selecione um produto"

**P:** Por que recebo este erro?

**R:**
Você clicou em "Adicionar" sem selecionar um produto.

**Solução:**
1. Digite o nome do produto
2. Clique em um resultado
3. Painel aparece
4. Clique "Adicionar"

---

### 12. "Quantidade inválida"

**P:** Por que recebo este erro?

**R:**
A quantidade está vazia, é zero ou não é um número.

**Solução:**
1. Clique no campo de quantidade
2. Digite um número > 0
3. Tente novamente

---

### 13. "Preço inválido"

**P:** Por que recebo este erro?

**R:**
O preço tem caracteres inválidos ou está vazio.

**Solução:**
1. Use apenas números e vírgula
2. Formato correto: 5,50
3. Não use R$ ou símbolos
4. Tente novamente

---

### 14. Produto não aparece na busca

**P:** Por que não encontro um produto?

**R:**
Possíveis razões:

1. **Produto inativo** → Ative no cadastro
2. **Nome diferente** → Procure pelo SKU
3. **Empresa diferente** → Verifique filtro
4. **Digitação errada** → Verifique ortografia
5. **Produto não existe** → Cadastre novo

---

### 15. Busca muito lenta

**P:** Por que a busca está lenta?

**R:**
Possíveis causas:

1. **Muitos produtos** → Normal com 400+
2. **Conexão lenta** → Verifique internet
3. **Servidor sobrecarregado** → Tente depois
4. **Navegador antigo** → Atualize

**Solução:**
- Use filtro de empresa
- Digite mais caracteres
- Tente em outro navegador

---

## 💡 Dicas e Truques

### 16. Como buscar mais rápido?

**P:** Como encontrar produtos mais rapidamente?

**R:**
1. Use o filtro de empresa primeiro
2. Digite o SKU (mais específico)
3. Use abreviações: "sab" para "sabão"
4. Memorize SKUs dos produtos frequentes

---

### 17. Como adicionar muitos produtos?

**P:** Qual é a forma mais rápida de adicionar 20 produtos?

**R:**
```
1. Busque o primeiro produto
2. Clique para selecionar
3. Ajuste quantidade
4. Clique "Adicionar"
5. Repita para próximo
6. Fluxo fica rápido com prática
```

Tempo estimado: 5-10 segundos por produto

---

### 18. Como editar vários itens?

**P:** Preciso editar vários itens. Como fazer?

**R:**
```
1. Clique em ✎ do primeiro item
2. Ajuste e clique "Atualizar"
3. Clique em ✎ do próximo
4. Repita até terminar
```

---

### 19. Como verificar o total?

**P:** Onde vejo o total do orçamento?

**R:**
O total aparece:
1. Na tabela (rodapé)
2. Atualiza automaticamente
3. Mostra em R$ (formato correto)
4. Recalcula ao adicionar/editar/remover

---

### 20. Como salvar o orçamento?

**P:** Como finalizar e salvar o orçamento?

**R:**
```
1. Preencha dados do cliente
2. Adicione todos os produtos
3. Revise o total
4. Clique "Criar Orçamento"
5. Aguarde confirmação
6. Será redirecionado para lista
```

---

## 🔧 Problemas Técnicos

### 21. Página não carrega

**P:** A página não carrega ou fica em branco.

**R:**
1. Verifique se está logado
2. Limpe cache do navegador (Ctrl+Shift+Del)
3. Tente em outro navegador
4. Verifique console (F12)
5. Contate suporte

---

### 22. Erros no console

**P:** Vejo erros no console do navegador (F12).

**R:**
1. Abra F12
2. Vá para "Console"
3. Procure por erros em vermelho
4. Anote a mensagem
5. Contate suporte com a mensagem

---

### 23. Dados não salvam

**P:** Criei um orçamento mas não aparece na lista.

**R:**
1. Verifique se clicou "Criar Orçamento"
2. Aguarde a confirmação
3. Verifique se foi redirecionado
4. Procure na lista de orçamentos
5. Se não encontrar, contate suporte

---

### 24. Navegador não suportado

**P:** A interface não funciona no meu navegador.

**R:**
Navegadores suportados:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

Se usar navegador antigo, atualize.

---

### 25. Mobile não funciona bem

**P:** A interface está ruim no celular.

**R:**
1. Verifique se está em modo retrato
2. Tente modo paisagem
3. Atualize o navegador
4. Limpe cache
5. Tente em outro celular

---

## 📞 Suporte

### Não encontrou resposta?

Se sua pergunta não está aqui:

1. **Consulte a documentação**
   - GUIA_USO_CRIAR_ORCAMENTO.md
   - MELHORIAS_CRIAR_ORCAMENTO.md

2. **Contate suporte**
   - Email: suporte@wkprodutosdelimpeza.com.br
   - Telefone: (XX) XXXX-XXXX
   - Chat: Disponível no sistema

3. **Forneça informações**
   - Descrição do problema
   - Passos para reproduzir
   - Navegador e versão
   - Screenshots se possível

---

**Versão**: 1.0
**Data**: 2025-11-05
**Status**: ✅ Completo

