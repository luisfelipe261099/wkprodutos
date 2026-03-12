# 📖 Instruções de Uso - Orçamento Corrigido

## 🎯 Visão Geral

O sistema de criação de orçamentos foi corrigido para:
1. ✅ Remover apenas o item selecionado (não mais outros itens)
2. ✅ Permitir edição do preço unitário no modal

---

## 🚀 Como Usar

### 1️⃣ Criar um Novo Orçamento

**Passo 1: Acessar a página**
```
URL: criar_orcamento.php
```

**Passo 2: Preencher dados básicos**
- Selecione um cliente
- Escolha o status (Pendente, Aprovado, Rejeitado)
- Adicione observações (opcional)

**Passo 3: Adicionar produtos**
- Digite o nome ou SKU do produto
- Selecione na lista
- Defina a quantidade
- Clique em "Adicionar"

**Passo 4: Revisar itens**
- Verifique a tabela de itens
- Veja o total do orçamento
- Faça ajustes se necessário

**Passo 5: Salvar**
- Clique em "Criar Orçamento"
- Aguarde a confirmação

---

### 2️⃣ Editar um Orçamento Existente

**Passo 1: Acessar a página**
```
URL: criar_orcamento.php?id=198
```
(Substitua 198 pelo ID do orçamento)

**Passo 2: Modificar dados**
- Altere cliente, status ou observações
- Adicione ou remova produtos
- Edite quantidades e preços

**Passo 3: Salvar**
- Clique em "Atualizar Orçamento"

---

### 3️⃣ Remover um Item (NOVO - CORRIGIDO)

**Antes (BUG):**
- ❌ Removia múltiplos itens
- ❌ Perdia dados

**Agora (CORRIGIDO):**
- ✅ Remove apenas o item selecionado
- ✅ Outros itens permanecem intactos

**Como fazer:**
1. Localize o item na tabela
2. Clique no botão "Remover" (ícone de lixeira)
3. ✅ Apenas esse item é removido
4. Total é recalculado automaticamente

---

### 4️⃣ Editar Quantidade e Preço (NOVO - CORRIGIDO)

**Antes (BUG):**
- ❌ Não conseguia editar preço
- ❌ Campo era readonly

**Agora (CORRIGIDO):**
- ✅ Pode editar quantidade
- ✅ Pode editar preço unitário
- ✅ Subtotal atualiza em tempo real

**Como fazer:**

1. **Clique em "Editar"** no item desejado
   - Abre um modal com os dados do item

2. **No modal, você pode:**
   - Ver o produto (não editável)
   - Ver quantidade atual (não editável)
   - **Alterar "Nova Quantidade"** ← NOVO
   - **Alterar "Preço Unitário"** ← NOVO (antes era readonly)

3. **Observe o "Novo Subtotal"**
   - Atualiza automaticamente enquanto você digita
   - Mostra: Nova Quantidade × Preço Unitário

4. **Clique em "Salvar Alterações"**
   - Valida os dados
   - Rejeita preços negativos
   - Salva as alterações

5. **Tabela é atualizada**
   - Mostra novos valores
   - Total do orçamento recalculado

---

## 📝 Exemplos Práticos

### Exemplo 1: Remover Item do Meio

```
Orçamento com 3 itens:
1. Notebook (Qtd: 1, Preço: R$ 1.000)
2. Mouse (Qtd: 5, Preço: R$ 50)      ← Remover este
3. Teclado (Qtd: 2, Preço: R$ 150)

Resultado após remover:
1. Notebook (Qtd: 1, Preço: R$ 1.000)
3. Teclado (Qtd: 2, Preço: R$ 150)

✅ Apenas Mouse foi removido!
```

### Exemplo 2: Editar Preço

```
Item: Mouse (Qtd: 5, Preço: R$ 50)

Ações:
1. Clique em "Editar"
2. Altere Preço Unitário de 50 para 75
3. Observe Novo Subtotal: 5 × 75 = R$ 375
4. Clique em "Salvar Alterações"

Resultado:
Mouse (Qtd: 5, Preço: R$ 75) ✅
```

### Exemplo 3: Editar Quantidade e Preço

```
Item: Notebook (Qtd: 1, Preço: R$ 1.000)

Ações:
1. Clique em "Editar"
2. Altere Nova Quantidade de 1 para 3
3. Altere Preço Unitário de 1000 para 900
4. Observe Novo Subtotal: 3 × 900 = R$ 2.700
5. Clique em "Salvar Alterações"

Resultado:
Notebook (Qtd: 3, Preço: R$ 900) ✅
```

---

## ⚠️ Validações

### Quantidade
- ✅ Deve ser maior que 0
- ✅ Deve ser um número inteiro
- ❌ Não aceita valores negativos

### Preço Unitário
- ✅ Deve ser maior ou igual a 0
- ✅ Aceita decimais (ex: 99.99)
- ❌ Não aceita valores negativos
- ⚠️ Se tentar salvar preço negativo, mostra alerta

---

## 🆘 Troubleshooting

### Problema: Não consigo editar o preço
**Solução:**
- Verifique se o campo está vazio
- Clique no campo e digite o novo valor
- O campo agora é editável (não é mais readonly)

### Problema: Removi o item errado
**Solução:**
- Recarregue a página (Ctrl+R)
- Adicione o item novamente
- Agora o sistema remove apenas o item correto

### Problema: Subtotal não atualiza
**Solução:**
- Verifique se digitou um número válido
- Clique em outro campo para disparar atualização
- Recarregue a página se persistir

---

## 💡 Dicas

1. **Sempre revise antes de salvar**
   - Verifique quantidade e preço
   - Confirme o total

2. **Use a busca de produtos**
   - Digite nome ou SKU
   - Selecione na lista

3. **Edite múltiplas vezes se necessário**
   - Pode editar o mesmo item várias vezes
   - Cada edição é salva corretamente

4. **Observe o total em tempo real**
   - Atualiza conforme você adiciona/remove itens
   - Ajuda a controlar o orçamento

---

## ✅ Checklist de Uso

- [ ] Consegui criar um novo orçamento
- [ ] Consegui adicionar múltiplos produtos
- [ ] Consegui remover um item específico
- [ ] Consegui editar quantidade
- [ ] Consegui editar preço unitário
- [ ] O total foi recalculado corretamente
- [ ] Consegui salvar o orçamento

---

**Versão:** 1.0  
**Data:** 2025-11-06  
**Status:** ✅ Pronto para uso

