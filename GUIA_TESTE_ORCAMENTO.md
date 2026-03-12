# 🧪 Guia de Teste - Correções do Orçamento

## 📌 Pré-requisitos
- Estar logado no sistema
- Ter pelo menos 3 produtos cadastrados
- Ter pelo menos 1 cliente cadastrado

---

## ✅ Teste 1: Exclusão Correta de Itens

### Cenário: Remover apenas o item selecionado

**Passos:**
1. Acesse: `criar_orcamento.php`
2. Selecione um cliente
3. Adicione 3 produtos diferentes:
   - Produto A (Qtd: 1)
   - Produto B (Qtd: 2)
   - Produto C (Qtd: 3)
4. Clique em "Remover" do **Produto B** (do meio)

**Resultado Esperado:**
- ✅ Apenas Produto B é removido
- ✅ Produtos A e C permanecem
- ✅ Total é recalculado corretamente
- ✅ Badge de contagem atualiza para 2 itens

**Resultado Incorreto (BUG):**
- ❌ Mais de um produto foi removido
- ❌ Produtos A ou C também desapareceram

---

## ✅ Teste 2: Editar Preço Unitário

### Cenário: Alterar o preço de um item

**Passos:**
1. Acesse: `criar_orcamento.php`
2. Selecione um cliente
3. Adicione 1 produto:
   - Produto: "Notebook"
   - Quantidade: 2
   - Preço: R$ 1.000,00 (preço padrão)
4. Clique em "Editar"
5. No modal, altere:
   - Preço Unitário: 1500.00
6. Observe o "Novo Subtotal"
7. Clique em "Salvar Alterações"

**Resultado Esperado:**
- ✅ Campo de preço é editável (não readonly)
- ✅ Ao digitar novo preço, subtotal atualiza em tempo real
- ✅ Novo Subtotal mostra: R$ 3.000,00 (2 × 1500)
- ✅ Após salvar, tabela mostra novo preço
- ✅ Total do orçamento atualiza

**Resultado Incorreto (BUG):**
- ❌ Campo de preço está readonly
- ❌ Subtotal não atualiza ao mudar preço
- ❌ Preço não é salvo após edição

---

## ✅ Teste 3: Editar Quantidade E Preço

### Cenário: Alterar quantidade e preço simultaneamente

**Passos:**
1. Acesse: `criar_orcamento.php`
2. Selecione um cliente
3. Adicione 1 produto:
   - Produto: "Mouse"
   - Quantidade: 5
   - Preço: R$ 50,00
4. Clique em "Editar"
5. No modal, altere:
   - Nova Quantidade: 10
   - Preço Unitário: 75.00
6. Observe o "Novo Subtotal"
7. Clique em "Salvar Alterações"

**Resultado Esperado:**
- ✅ Quantidade Atual mostra: 5
- ✅ Nova Quantidade: 10
- ✅ Preço Unitário: 75.00
- ✅ Novo Subtotal: R$ 750,00 (10 × 75)
- ✅ Após salvar, tabela mostra: Qtd 10, Preço R$ 75,00
- ✅ Total do orçamento: R$ 750,00

---

## ✅ Teste 4: Validação de Preço Negativo

### Cenário: Tentar salvar preço negativo

**Passos:**
1. Acesse: `criar_orcamento.php`
2. Adicione 1 produto
3. Clique em "Editar"
4. Tente alterar Preço Unitário para: -100
5. Clique em "Salvar Alterações"

**Resultado Esperado:**
- ✅ Alerta: "Preço não pode ser negativo"
- ✅ Modal permanece aberto
- ✅ Dados não são salvos

---

## ✅ Teste 5: Múltiplas Edições

### Cenário: Editar o mesmo item várias vezes

**Passos:**
1. Acesse: `criar_orcamento.php`
2. Adicione 1 produto (Qtd: 1, Preço: R$ 100)
3. Clique em "Editar" → Altere para Qtd: 5 → Salvar
4. Clique em "Editar" novamente → Altere Preço para R$ 150 → Salvar
5. Clique em "Editar" novamente → Altere Qtd: 10 → Salvar

**Resultado Esperado:**
- ✅ Cada edição é salva corretamente
- ✅ Valores finais: Qtd 10, Preço R$ 150
- ✅ Subtotal final: R$ 1.500,00
- ✅ Nenhum erro ou comportamento estranho

---

## 🔍 Verificação Técnica

### Abrir Console do Navegador (F12)

**Verificar se não há erros:**
```javascript
// Não deve haver erros de JavaScript
// Verificar aba "Console" - não deve haver mensagens vermelhas
```

**Verificar dados:**
```javascript
// No console, digitar:
console.log(itensOrcamento);

// Deve mostrar array com itens contendo _uuid:
// [
//   {id: 1, nome: "Produto A", quantidade: 5, preco_unitario: 100, _uuid: "item_..."},
//   ...
// ]
```

---

## 📊 Checklist de Testes

- [ ] Teste 1: Exclusão de itens funciona corretamente
- [ ] Teste 2: Edição de preço funciona
- [ ] Teste 3: Edição de quantidade e preço juntos funciona
- [ ] Teste 4: Validação de preço negativo funciona
- [ ] Teste 5: Múltiplas edições funcionam
- [ ] Console não mostra erros
- [ ] Total do orçamento é calculado corretamente
- [ ] Dados são salvos ao submeter o formulário

---

## 🚀 Próximos Passos

Se todos os testes passarem:
1. ✅ Testar em diferentes navegadores
2. ✅ Testar em dispositivos móveis
3. ✅ Testar edição de orçamentos existentes
4. ✅ Testar envio de orçamento por email

