# Guia de Testes - Página de Produtos

## 🧪 Testes Funcionais

### Teste 1: Busca por Nome
**Objetivo:** Verificar se a busca por nome funciona corretamente

**Passos:**
1. Acesse: `https://wkprodutosdelimpeza.com.br/system/produtos.php`
2. No campo de busca, digite: `água`
3. Aguarde 500ms ou pressione Enter
4. Observe a página

**Resultado Esperado:**
- ✅ Apenas produtos com "água" no nome aparecem
- ✅ Paginação se atualiza (ex: mostra 3 páginas em vez de 28)
- ✅ Contador mostra número correto (ex: "45 resultados")
- ✅ Sem erros na página

---

### Teste 2: Busca por SKU
**Objetivo:** Verificar se a busca por SKU funciona

**Passos:**
1. Limpe o campo de busca
2. Digite um SKU válido (ex: `SKU001`)
3. Pressione Enter

**Resultado Esperado:**
- ✅ Produtos com esse SKU aparecem
- ✅ Paginação se atualiza
- ✅ Contador mostra número correto

---

### Teste 3: Busca por Fornecedor
**Objetivo:** Verificar se a busca por fornecedor funciona

**Passos:**
1. Limpe o campo de busca
2. Digite um nome de fornecedor (ex: `Distribuidor`)
3. Pressione Enter

**Resultado Esperado:**
- ✅ Produtos desse fornecedor aparecem
- ✅ Paginação se atualiza
- ✅ Contador mostra número correto

---

### Teste 4: Paginação Inteligente
**Objetivo:** Verificar se a paginação mostra apenas 7 páginas

**Passos:**
1. Acesse a página de produtos (sem filtros)
2. Observe os números de página

**Resultado Esperado:**
- ✅ Mostra apenas 7 páginas (ex: 1 2 3 4 5 6 7)
- ✅ Botão "<<" para primeira página
- ✅ Botão ">>" para última página
- ✅ Página atual está destacada

---

### Teste 5: Navegação de Páginas
**Objetivo:** Verificar se a navegação entre páginas funciona

**Passos:**
1. Clique no botão "<<" (primeira página)
2. Verifique se vai para página 1
3. Clique no botão ">>" (última página)
4. Verifique se vai para a última página
5. Clique em um número de página
6. Verifique se vai para essa página

**Resultado Esperado:**
- ✅ Todos os botões funcionam
- ✅ URL se atualiza com o número da página
- ✅ Filtros são mantidos ao navegar

---

### Teste 6: Limpar Busca
**Objetivo:** Verificar se o botão "X" limpa a busca

**Passos:**
1. Digite um termo de busca (ex: `água`)
2. Clique no botão "X"
3. Observe a página

**Resultado Esperado:**
- ✅ Campo de busca fica vazio
- ✅ Volta para página 1
- ✅ Mostra todos os produtos novamente
- ✅ Paginação volta a mostrar 28 páginas

---

### Teste 7: Filtro de Fornecedor + Busca
**Objetivo:** Verificar se os filtros funcionam combinados

**Passos:**
1. Selecione um fornecedor no dropdown
2. Digite um termo de busca
3. Observe a página

**Resultado Esperado:**
- ✅ Filtra por ambos os critérios
- ✅ Paginação se atualiza
- ✅ Contador mostra número correto
- ✅ URL contém ambos os parâmetros

---

### Teste 8: Atalhos de Teclado
**Objetivo:** Verificar se os atalhos funcionam

**Passos:**
1. Pressione Ctrl+F
2. Verifique se o campo de busca recebe foco
3. Pressione Ctrl+K
4. Verifique se o campo de busca recebe foco

**Resultado Esperado:**
- ✅ Campo de busca recebe foco
- ✅ Texto anterior é selecionado
- ✅ Pronto para digitar

---

### Teste 9: Busca com Enter
**Objetivo:** Verificar se Enter executa a busca imediatamente

**Passos:**
1. Digite um termo de busca
2. Pressione Enter
3. Observe a página

**Resultado Esperado:**
- ✅ Busca é executada imediatamente
- ✅ Não precisa aguardar 500ms
- ✅ Resultados aparecem

---

### Teste 10: Busca com Escape
**Objetivo:** Verificar se Escape limpa a busca

**Passos:**
1. Digite um termo de busca
2. Pressione Escape
3. Observe o campo de busca

**Resultado Esperado:**
- ✅ Campo de busca fica vazio
- ✅ Página não recarrega (apenas limpa o campo)

---

## 🔍 Testes de Validação

### Validação 1: Sem Erros de Sintaxe
```bash
php -l produtos.php
```
**Resultado Esperado:** `No syntax errors detected`

---

### Validação 2: Variável Definida
**Objetivo:** Verificar se $total_produtos_db é usada corretamente

**Passos:**
1. Abra o navegador (F12)
2. Vá para a aba Console
3. Procure por erros

**Resultado Esperado:**
- ✅ Nenhum erro de variável indefinida
- ✅ Nenhum erro de JavaScript

---

### Validação 3: Contador Correto
**Objetivo:** Verificar se o contador mostra o número correto

**Passos:**
1. Faça uma busca
2. Conte manualmente os produtos na página
3. Compare com o contador

**Resultado Esperado:**
- ✅ Contador mostra o número total de resultados
- ✅ Não apenas os produtos da página atual

---

## 📊 Testes de Performance

### Performance 1: Debounce
**Objetivo:** Verificar se o debounce funciona

**Passos:**
1. Digite rapidamente: `a`, `ag`, `agu`, `água`
2. Observe quantas requisições são feitas

**Resultado Esperado:**
- ✅ Apenas 1 requisição (após 500ms de inatividade)
- ✅ Não faz requisição a cada letra digitada

---

### Performance 2: Tempo de Resposta
**Objetivo:** Verificar se a busca é rápida

**Passos:**
1. Abra o DevTools (F12)
2. Vá para a aba Network
3. Faça uma busca
4. Observe o tempo de resposta

**Resultado Esperado:**
- ✅ Tempo de resposta < 1 segundo
- ✅ Página carrega suavemente

---

## ✅ Checklist Final

- [ ] Busca por nome funciona
- [ ] Busca por SKU funciona
- [ ] Busca por fornecedor funciona
- [ ] Paginação mostra apenas 7 páginas
- [ ] Botões primeira/última página funcionam
- [ ] Limpar busca funciona
- [ ] Filtro de fornecedor + busca funcionam
- [ ] Atalhos de teclado funcionam
- [ ] Enter executa busca imediatamente
- [ ] Escape limpa o campo
- [ ] Sem erros de sintaxe PHP
- [ ] Sem erros de variável indefinida
- [ ] Contador mostra número correto
- [ ] Debounce funciona
- [ ] Tempo de resposta é rápido

---

## 🐛 Troubleshooting

### Problema: Busca não funciona
**Solução:**
1. Verifique se o arquivo `produtos.php` foi salvo corretamente
2. Limpe o cache do navegador (Ctrl+Shift+Delete)
3. Recarregue a página (Ctrl+F5)

### Problema: Paginação mostra muitas páginas
**Solução:**
1. Verifique se a paginação inteligente foi implementada (linhas 338-413)
2. Verifique se `$intervalo_paginas = 7` está definido

### Problema: Contador mostra número errado
**Solução:**
1. Verifique se o contador usa `$total_produtos_db` (não `$total_produtos`)
2. Verifique se o filtro de busca está sendo aplicado corretamente

### Problema: Erro de variável indefinida
**Solução:**
1. Verifique se a linha 168 usa `$total_produtos_db` (não `$total_produtos`)
2. Recarregue a página

---

**Data**: 2025-11-05
**Status**: ✅ Pronto para Testes

