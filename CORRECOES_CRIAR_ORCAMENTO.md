# Correções no criar_orcamento.php

## Problemas Corrigidos

### 1. ✅ Exclusão de Itens Apagava Outros Também

**Problema:**
- Ao clicar em "Remover" em um item, outros itens também eram removidos
- Isso ocorria porque a função usava índices que podiam ser ambíguos

**Solução Implementada:**
- Adicionado um identificador único (UUID) para cada item: `_uuid`
- Cada item agora tem um UUID único gerado com timestamp + número aleatório
- A função `removerItem()` agora busca o item pelo UUID em vez do índice
- Isso garante que apenas o item correto seja removido

**Código Alterado:**
```javascript
// Antes (problemático)
function removerItem(index) {
    itensOrcamento.splice(index, 1);
}

// Depois (corrigido)
function removerItem(uuid) {
    const index = itensOrcamento.findIndex(i => i._uuid === uuid);
    if (index !== -1) {
        itensOrcamento.splice(index, 1);
        renderizarTabela();
    }
}
```

---

### 2. ✅ Não Conseguia Alterar Preço Unitário no Modal

**Problema:**
- O campo "Preço Unitário" no modal estava com `readonly`
- Não era possível editar o preço do item
- A função `salvarEdicao()` só atualizava a quantidade

**Solução Implementada:**
- Removido o atributo `readonly` do campo de preço
- Alterado para `type="number"` com `min="0"` e `step="0.01"`
- Adicionada validação para preço negativo
- Atualizada função `salvarEdicao()` para salvar também o preço
- Adicionado event listener para atualizar subtotal quando preço muda

**Mudanças no Modal:**
```html
<!-- Antes -->
<input type="text" class="form-control" id="modal_preco_unitario" readonly>

<!-- Depois -->
<input type="number" class="form-control" id="modal_preco_unitario" 
       min="0" step="0.01" placeholder="0.00">
```

**Função Atualizada:**
```javascript
function salvarEdicao() {
    // ... validações ...
    
    // Agora salva quantidade E preço
    item.quantidade = novaQuantidade;
    item.preco_unitario = novoPreco;
    
    renderizarTabela();
}
```

---

## Arquivos Modificados

- ✅ `criar_orcamento.php` - Todas as correções aplicadas

## Funcionalidades Adicionadas

1. **UUID para cada item** - Identificação única e segura
2. **Edição de preço unitário** - Agora é possível alterar o preço
3. **Validação de preço** - Não permite valores negativos
4. **Atualização de subtotal em tempo real** - Quando preço ou quantidade mudam

## Como Testar

### Teste 1: Exclusão de Itens
1. Criar um orçamento com 3 produtos diferentes
2. Clicar em "Remover" no produto do meio
3. ✅ Esperado: Apenas o produto do meio é removido

### Teste 2: Edição de Preço
1. Criar um orçamento com um produto
2. Clicar em "Editar"
3. Alterar o "Preço Unitário"
4. ✅ Esperado: O preço é alterado e o subtotal é recalculado

### Teste 3: Edição de Quantidade e Preço
1. Criar um orçamento com um produto
2. Clicar em "Editar"
3. Alterar quantidade E preço
4. ✅ Esperado: Ambos são salvos corretamente

## Compatibilidade

- ✅ Funciona com orçamentos novos
- ✅ Funciona com orçamentos existentes (itens carregados do banco)
- ✅ Compatível com navegadores modernos
- ✅ Sem quebra de funcionalidades existentes

