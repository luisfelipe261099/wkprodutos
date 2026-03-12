# 📖 Guia de Uso - Página Nova criar_orcamento.php

## 🎯 Objetivo

Criar orçamentos de forma rápida e fácil, com busca em tempo real de produtos.

---

## 📋 Passo a Passo

### Passo 1: Acessar a Página

1. Abrir navegador
2. Ir para `criar_orcamento.php`
3. Fazer login se necessário

### Passo 2: Selecionar Cliente

1. Clicar no campo "Cliente"
2. Digitar nome do cliente (ex: "Ellolimp")
3. Aguardar resultados
4. Clicar em um cliente

**Resultado:** Cliente selecionado

---

### Passo 3: Selecionar Status

1. Clicar no campo "Status"
2. Selecionar uma opção:
   - Pendente
   - Aprovado
   - Rejeitado

**Resultado:** Status selecionado

---

### Passo 4: Adicionar Observações (Opcional)

1. Clicar no campo "Observações"
2. Digitar observações (ex: "Entrega urgente")

**Resultado:** Observações adicionadas

---

### Passo 5: Buscar Primeiro Produto

1. Clicar no campo "Buscar Produto"
2. Digitar nome ou SKU (ex: "saco")
3. Aguardar 300ms

**Resultado:** Dropdown com resultados

---

### Passo 6: Selecionar Produto

1. Clicar em um produto no dropdown

**Resultado:**
- Campo "Buscar Produto" preenchido
- Campo "Preço Unit." preenchido automaticamente
- Campo "Quantidade" com valor 1

---

### Passo 7: Alterar Quantidade (Opcional)

1. Clicar no campo "Quantidade"
2. Selecionar tudo (Ctrl+A)
3. Digitar nova quantidade (ex: 5)

**Resultado:** Quantidade alterada

---

### Passo 8: Adicionar ao Orçamento

1. Clicar botão "Adicionar"

**Resultado:**
- Produto adicionado à tabela
- Campos limpos
- Badge mostra número de itens

---

### Passo 9: Adicionar Mais Produtos (Opcional)

1. Repetir passos 5-8 para cada produto

**Resultado:** Múltiplos produtos na tabela

---

### Passo 10: Remover Produto (Se Necessário)

1. Clicar botão "Remover" em um item

**Resultado:** Produto removido da tabela

---

### Passo 11: Criar Orçamento

1. Verificar se:
   - Cliente está selecionado
   - Status está selecionado
   - Há pelo menos 1 produto

2. Clicar botão "Criar Orçamento"

**Resultado:** Orçamento criado com sucesso

---

## 🔍 Dicas de Busca

### Buscar por Nome
```
Digitar: "saco"
Resultado: Todos os produtos com "saco" no nome
```

### Buscar por SKU
```
Digitar: "1001"
Resultado: Produto com SKU "1001"
```

### Buscar por Empresa
```
1. Selecionar empresa em "Filtrar por Empresa"
2. Digitar nome de produto
Resultado: Apenas produtos dessa empresa
```

---

## 💡 Atalhos

| Atalho | Função |
|--------|--------|
| Ctrl+A | Selecionar tudo |
| Tab | Próximo campo |
| Enter | Adicionar produto |
| Esc | Fechar dropdown |

---

## ⚠️ Validações

### Antes de Criar Orçamento

✅ Cliente deve estar selecionado
✅ Status deve estar selecionado
✅ Deve haver pelo menos 1 produto

Se alguma validação falhar, você verá uma mensagem de erro.

---

## 📊 Exemplo Prático

### Cenário: Criar Orçamento para Ellolimp

**Passo 1:** Selecionar cliente
- Digitar "Ellolimp"
- Clicar em "Ellolimp"

**Passo 2:** Selecionar status
- Selecionar "Pendente"

**Passo 3:** Adicionar produtos
- Buscar "saco"
- Selecionar "Saco de lixo preto 20lts reforçado c/100un"
- Quantidade: 10
- Clicar "Adicionar"

- Buscar "sabão"
- Selecionar "Sabão Neutro 5L"
- Quantidade: 5
- Clicar "Adicionar"

**Passo 4:** Criar orçamento
- Clicar "Criar Orçamento"

**Resultado:** Orçamento criado com 2 produtos

---

## 🎯 Fluxo Visual

```
┌─────────────────────────────────────┐
│ 1. Selecionar Cliente               │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 2. Selecionar Status                │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 3. Buscar Produto                   │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 4. Selecionar Produto               │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 5. Adicionar Quantidade             │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 6. Clicar "Adicionar"               │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 7. Repetir para mais produtos       │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ 8. Clicar "Criar Orçamento"         │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ ✅ Orçamento Criado!                │
└─────────────────────────────────────┘
```

---

## ❓ Perguntas Frequentes

### P: Como buscar um produto?
**R:** Digitar nome ou SKU no campo "Buscar Produto"

### P: Como filtrar por empresa?
**R:** Selecionar empresa em "Filtrar por Empresa"

### P: Como remover um produto?
**R:** Clicar botão "Remover" na linha do produto

### P: Como editar um orçamento?
**R:** Ir para a lista de orçamentos e clicar "Editar"

### P: Qual é o total do orçamento?
**R:** Mostrado na tabela, linha "Total"

---

## 🆘 Troubleshooting

### Problema: Nenhum produto aparece
**Solução:**
1. Verificar se digitou corretamente
2. Tentar buscar por SKU
3. Tentar filtrar por empresa

### Problema: Não consegue selecionar cliente
**Solução:**
1. Verificar se cliente existe no banco
2. Tentar digitar nome completo
3. Tentar buscar por parte do nome

### Problema: Não consegue criar orçamento
**Solução:**
1. Verificar se cliente está selecionado
2. Verificar se status está selecionado
3. Verificar se há pelo menos 1 produto

---

## 📞 Suporte

Se tiver dúvidas ou problemas:
1. Abrir Console (F12)
2. Procurar por mensagens de erro
3. Contatar administrador

---

**Data:** 2025-11-05
**Versão:** 4.0
**Arquivo:** criar_orcamento.php

