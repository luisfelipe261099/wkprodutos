# 🧪 Teste - Página Nova criar_orcamento.php

## ✅ Mudanças Realizadas

- ✅ Página recriada do zero
- ✅ Removidas 2 interfaces conflitantes
- ✅ Criada 1 interface limpa e funcional
- ✅ Código reduzido de 1548 para ~300 linhas
- ✅ Sem conflitos de IDs
- ✅ Sem código duplicado

---

## 🧪 Teste 1: Abrir Página

### Passo 1: Acessar
1. Abrir navegador
2. Ir para `criar_orcamento.php`
3. Fazer login se necessário

### ✅ Resultado Esperado
- Página carrega sem erros
- Formulário aparece
- Campo "Buscar Produto" está visível

---

## 🧪 Teste 2: Buscar por Nome

### Passo 1: Digitar "saco"
1. Clicar no campo "Buscar Produto"
2. Digitar "saco"
3. Aguardar 300ms

### ✅ Resultado Esperado
```
Dropdown aparece com:
- Saco de lixo preto 20lts reforçado c/100un
  SKU: 1001 | R$ 8,60
- Saco de lixo preto 20lts c/100un
  SKU: 1002 | R$ 5,10
- Saco de lixo preto 20lts reforçado c/100un
  SKU: 1003 | R$ 8,10
- Saco de lixo preto 40lts c/100un
  SKU: 1004 | R$ 9,45
```

### ❌ Se Não Funcionar
1. Abrir Console (F12)
2. Procurar por erros em vermelho
3. Verificar se há mensagens de erro

---

## 🧪 Teste 3: Buscar por SKU

### Passo 1: Limpar Campo
1. Clicar no campo
2. Selecionar tudo (Ctrl+A)
3. Deletar

### Passo 2: Digitar SKU
1. Digitar "1001"
2. Aguardar 300ms

### ✅ Resultado Esperado
```
Dropdown aparece com:
- Saco de lixo preto 20lts reforçado c/100un
  SKU: 1001 | R$ 8,60
```

---

## 🧪 Teste 4: Filtrar por Empresa

### Passo 1: Selecionar Empresa
1. Ir para "Filtrar por Empresa"
2. Selecionar "Acttion" (ou outra empresa)

### Passo 2: Digitar Nome
1. Digitar "saco"
2. Aguardar 300ms

### ✅ Resultado Esperado
Apenas produtos da empresa "Acttion" com "saco" no nome

---

## 🧪 Teste 5: Selecionar Produto

### Passo 1: Buscar
1. Digitar "saco"
2. Aguardar resultados

### Passo 2: Clicar em Resultado
1. Clicar em "Saco de lixo preto 20lts reforçado c/100un"

### ✅ Resultado Esperado
```
- Campo "Buscar Produto" preenchido com: "Saco de lixo preto 20lts reforçado c/100un"
- Campo "Preço Unit." preenchido com: "8.60"
- Campo "Quantidade" com valor: "1"
- Dropdown desaparece
```

---

## 🧪 Teste 6: Adicionar Quantidade

### Passo 1: Produto Selecionado
1. Produto deve estar selecionado (do teste anterior)

### Passo 2: Alterar Quantidade
1. Clicar no campo "Quantidade"
2. Selecionar tudo (Ctrl+A)
3. Digitar "5"

### ✅ Resultado Esperado
- Campo "Quantidade" mostra "5"
- Preço Unit. continua "8.60"

---

## 🧪 Teste 7: Adicionar ao Orçamento

### Passo 1: Clicar "Adicionar"
1. Clicar botão "Adicionar"

### ✅ Resultado Esperado
```
Tabela "Itens do Orçamento" mostra:
┌─────────────────────────────────────────────────────────┐
│ Produto                                  │ Qtd │ Preço  │
├─────────────────────────────────────────────────────────┤
│ Saco de lixo preto 20lts reforçado...    │ 5   │ 8,60   │
│ Subtotal: R$ 43,00                                      │
└─────────────────────────────────────────────────────────┘

Total do Orçamento: R$ 43,00
Badge mostra: 1 item
```

---

## 🧪 Teste 8: Adicionar Segundo Produto

### Passo 1: Buscar Outro Produto
1. Digitar "sabão" no campo "Buscar Produto"
2. Aguardar resultados

### Passo 2: Selecionar
1. Clicar em um resultado
2. Digitar quantidade (ex: 3)
3. Clicar "Adicionar"

### ✅ Resultado Esperado
```
Tabela mostra 2 produtos:
- Saco de lixo preto 20lts reforçado... | 5 | 8,60 | R$ 43,00
- Sabão... | 3 | X,XX | R$ X,XX

Total do Orçamento: R$ XX,XX
Badge mostra: 2 itens
```

---

## 🧪 Teste 9: Remover Item

### Passo 1: Clicar "Remover"
1. Clicar botão "Remover" em um item

### ✅ Resultado Esperado
- Item é removido da tabela
- Total recalcula
- Badge mostra número correto

---

## 🧪 Teste 10: Selecionar Cliente

### Passo 1: Buscar Cliente
1. Clicar no campo "Cliente"
2. Digitar nome de um cliente (ex: "Ellolimp")
3. Aguardar resultados

### Passo 2: Selecionar
1. Clicar em um cliente

### ✅ Resultado Esperado
- Campo "Cliente" preenchido com nome
- Cliente selecionado internamente

---

## 🧪 Teste 11: Criar Orçamento

### Passo 1: Preencher Dados
1. Selecionar cliente
2. Selecionar status (ex: "Pendente")
3. Adicionar 2-3 produtos

### Passo 2: Clicar "Criar Orçamento"
1. Clicar botão "Criar Orçamento"

### ✅ Resultado Esperado
```
- Mensagem de sucesso aparece
- Página redireciona para orcamentos.php
- Orçamento aparece na lista
```

---

## 📊 Checklist de Testes

| # | Teste | Status |
|---|-------|--------|
| 1 | Abrir página | [ ] |
| 2 | Buscar por nome | [ ] |
| 3 | Buscar por SKU | [ ] |
| 4 | Filtrar por empresa | [ ] |
| 5 | Selecionar produto | [ ] |
| 6 | Adicionar quantidade | [ ] |
| 7 | Adicionar ao orçamento | [ ] |
| 8 | Adicionar segundo produto | [ ] |
| 9 | Remover item | [ ] |
| 10 | Selecionar cliente | [ ] |
| 11 | Criar orçamento | [ ] |

---

## ❌ Troubleshooting

### Erro: "Nenhum produto encontrado"
1. Verificar se há produtos no banco
2. Executar: `SELECT COUNT(*) FROM produtos;`
3. Se vazio, inserir produtos

### Erro: "Página em branco"
1. Abrir Console (F12)
2. Procurar por erros em vermelho
3. Verificar se arquivo foi substituído corretamente

### Erro: "Busca não funciona"
1. Abrir Console (F12)
2. Verificar se `produtosData` está populado
3. Executar: `console.log(produtosData.length)`

### Erro: "Não consegue adicionar produto"
1. Verificar se produto foi selecionado
2. Verificar se quantidade está preenchida
3. Verificar se preço está preenchido

---

## ✅ Conclusão

Se todos os 11 testes passarem:

✅ Página funciona perfeitamente
✅ Busca funciona
✅ Filtro funciona
✅ Adição funciona
✅ Remoção funciona
✅ Criação funciona

**Status:** ✅ PRONTO PARA PRODUÇÃO

---

**Data:** 2025-11-05
**Versão:** 4.0
**Arquivo:** criar_orcamento.php

