# Guia de Uso - Criar Orçamento (Nova Interface)

## 🎯 Objetivo

Criar orçamentos de forma rápida e intuitiva com busca em tempo real.

## 📱 Interface

```
┌─────────────────────────────────────────────────────────────┐
│  Criar Novo Orçamento                                       │
└─────────────────────────────────────────────────────────────┘

┌─ Dados do Orçamento ────────────────────────────────────────┐
│                                                              │
│  Cliente *                          Status do Orçamento *   │
│  [Buscar cliente...]                [Pendente ▼]            │
│                                                              │
│  Observações                                                │
│  [Informações adicionais...]                                │
│                                                              │
└──────────────────────────────────────────────────────────────┘

┌─ Adicionar Produtos ───────────────────────────────────────┐
│                                                             │
│  🔍 Buscar Produto                  🏢 Filtrar por Empresa │
│  [Digite o nome, SKU ou empresa...] [Todas as empresas ▼]  │
│  ✕                                                          │
│                                                             │
│  Produtos encontrados: 5                                    │
│                                                             │
│  ┌─ Dropdown de Resultados ──────────────────────────────┐ │
│  │ Sabão Neutro [SKU: SAB001]          R$ 5,50           │ │
│  │ Sabão Líquido [SKU: SAB002]         R$ 8,90           │ │
│  │ Sabão em Pó [SKU: SAB003]           R$ 12,50          │ │
│  │ Sabão Desinfetante [SKU: SAB004]    R$ 15,00          │ │
│  │ Sabão Neutro Galão [SKU: SAB005]    R$ 45,00          │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                             │
│  ┌─ Painel de Seleção (após clicar) ─────────────────────┐ │
│  │                                                        │ │
│  │  Produto Selecionado                                  │ │
│  │  ┌──────────────────────────────────────────────────┐ │ │
│  │  │ Sabão Neutro                                     │ │ │
│  │  │ SKU: SAB001                                      │ │ │
│  │  │ R$ 5,50                                          │ │ │
│  │  └──────────────────────────────────────────────────┘ │ │
│  │                                                        │ │
│  │  Quantidade    Preço Unit.    [Adicionar] [Cancelar]  │ │
│  │  [1        ]   [5,50      ]                           │ │
│  │                                                        │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─ Itens do Orçamento ───────────────────────────────────────┐
│                                                             │
│  Produto          Quantidade  Preço Unit.  Subtotal  Ações │
│  ─────────────────────────────────────────────────────────  │
│  Sabão Neutro     2            R$ 5,50     R$ 11,00  ✎ ✕   │
│  Sabão Líquido    1            R$ 8,90     R$ 8,90   ✎ ✕   │
│  ─────────────────────────────────────────────────────────  │
│  Total do Orçamento:                       R$ 19,90        │
│                                                             │
└─────────────────────────────────────────────────────────────┘

[Cancelar]  [Criar Orçamento]
```

## 🚀 Passo a Passo

### 1️⃣ Preencher Dados Básicos

```
1. Clique no campo "Cliente"
2. Digite o nome do cliente
3. Selecione na lista que aparece
4. Escolha o Status (Pendente, Aprovado, etc)
5. Adicione observações se necessário
```

### 2️⃣ Adicionar Produtos

#### Opção A: Buscar por Nome
```
1. Clique no campo "Buscar Produto"
2. Digite "sabão"
3. Veja a lista de produtos com "sabão"
4. Clique no produto desejado
5. Painel aparece com dados preenchidos
```

#### Opção B: Buscar por SKU
```
1. Clique no campo "Buscar Produto"
2. Digite "SAB001" (código do produto)
3. Veja o produto específico
4. Clique para selecionar
```

#### Opção C: Filtrar por Empresa
```
1. Selecione a empresa no dropdown
2. Digite o nome do produto
3. Vê apenas produtos dessa empresa
4. Clique para selecionar
```

### 3️⃣ Confirmar Seleção

```
Após clicar em um produto:

┌─ Painel de Seleção ────────────────┐
│ Sabão Neutro                       │
│ SKU: SAB001                        │
│ R$ 5,50                            │
│                                    │
│ Quantidade: [1]                    │
│ Preço Unit.: [5,50]                │
│                                    │
│ [Adicionar] [Cancelar]             │
└────────────────────────────────────┘

1. Ajuste a quantidade se necessário
2. Ajuste o preço se necessário
3. Clique "Adicionar"
4. Produto aparece na tabela
```

### 4️⃣ Gerenciar Itens

#### Adicionar Mais Produtos
```
1. Repita os passos 2-3
2. Cada produto é adicionado à tabela
3. Se adicionar o mesmo produto 2x, soma as quantidades
```

#### Editar um Item
```
1. Clique no botão ✎ (editar) na linha do produto
2. Painel aparece com dados do item
3. Ajuste quantidade ou preço
4. Clique "Atualizar Item"
```

#### Remover um Item
```
1. Clique no botão ✕ (remover) na linha do produto
2. Item é removido imediatamente
3. Total é recalculado
```

### 5️⃣ Finalizar Orçamento

```
1. Revise todos os itens
2. Verifique o total
3. Clique "Criar Orçamento"
4. Orçamento é salvo no banco de dados
5. Você é redirecionado para a lista de orçamentos
```

## ⌨️ Atalhos de Teclado

| Tecla | Ação |
|-------|------|
| `Escape` | Fecha dropdown e painel |
| `Enter` | Confirma seleção (em alguns campos) |
| `Tab` | Move para próximo campo |
| `Ctrl+A` | Seleciona todo o texto |

## 💡 Dicas Úteis

### Busca Rápida
```
✓ Digite apenas parte do nome: "sab" encontra "sabão"
✓ Busca é case-insensitive: "SABÃO" = "sabão"
✓ Busca em SKU também: "SAB001" encontra o produto
```

### Quantidade
```
✓ Mínimo: 1 unidade
✓ Máximo: sem limite
✓ Decimais: não permitidos (apenas números inteiros)
```

### Preço
```
✓ Formato: R$ 0,00
✓ Pode ser diferente do preço de tabela
✓ Usa vírgula como separador decimal
```

### Filtro por Empresa
```
✓ Filtra a busca em tempo real
✓ Combina com o campo de busca
✓ "Todas as empresas" mostra tudo
```

## ❌ Erros Comuns

### "Por favor, selecione um produto"
```
❌ Clicou em "Adicionar" sem selecionar produto
✓ Solução: Digite o nome e clique em um resultado
```

### "Quantidade inválida"
```
❌ Deixou quantidade em branco ou com 0
✓ Solução: Digite um número maior que 0
```

### "Preço inválido"
```
❌ Preço com caracteres inválidos
✓ Solução: Use apenas números e vírgula (ex: 5,50)
```

### Produto não aparece
```
❌ Produto pode estar inativo no banco
✓ Solução: Verifique se o produto está ativo
```

## 🎨 Cores e Significados

| Cor | Significado |
|-----|-------------|
| 🔵 Azul | Ação principal (Adicionar) |
| 🟢 Verde | Preço, sucesso |
| 🟡 Amarelo | Editar |
| 🔴 Vermelho | Remover, perigo |
| ⚪ Cinza | Ação secundária (Cancelar) |

## 📊 Exemplo Completo

```
1. Criar novo orçamento
2. Cliente: "João Silva"
3. Status: "Pendente"
4. Adicionar:
   - 2x Sabão Neutro @ R$ 5,50 = R$ 11,00
   - 1x Sabão Líquido @ R$ 8,90 = R$ 8,90
   - 3x Desinfetante @ R$ 12,00 = R$ 36,00
5. Total: R$ 55,90
6. Clicar "Criar Orçamento"
7. ✅ Orçamento criado com sucesso!
```

---

**Versão**: 2.0
**Data**: 2025-11-05
**Status**: ✅ Pronto para Uso

