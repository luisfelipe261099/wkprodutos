# Testes - Criar Orçamento (Nova Interface)

## ✅ Testes Funcionais

### Teste 1: Busca por Nome
```
Pré-requisito: Página carregada
Passos:
1. Clique no campo "Buscar Produto"
2. Digite "sabão"
3. Aguarde 300ms

Resultado Esperado:
✓ Dropdown aparece
✓ Mostra produtos com "sabão" no nome
✓ Máximo 10 resultados
✓ Cada item mostra: Nome, SKU, Preço
✓ Contador mostra número de resultados

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 2: Busca por SKU
```
Pré-requisito: Página carregada
Passos:
1. Clique no campo "Buscar Produto"
2. Digite "SAB001"
3. Aguarde 300ms

Resultado Esperado:
✓ Dropdown aparece
✓ Mostra produto com SKU "SAB001"
✓ Preço correto exibido

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 3: Seleção de Produto
```
Pré-requisito: Dropdown com resultados visível
Passos:
1. Clique em um produto da lista

Resultado Esperado:
✓ Painel de seleção aparece com animação
✓ Nome do produto preenchido
✓ SKU exibido
✓ Preço em verde
✓ Quantidade = 1
✓ Preço unitário preenchido
✓ Dropdown fecha

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 4: Adicionar Produto
```
Pré-requisito: Painel de seleção visível
Passos:
1. Ajuste quantidade se necessário
2. Clique "Adicionar"

Resultado Esperado:
✓ Produto adicionado à tabela
✓ Linha mostra: Nome, Quantidade, Preço, Subtotal
✓ Total recalculado
✓ Painel fecha
✓ Campo de busca limpo
✓ Contador de itens atualizado

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 5: Adicionar Mesmo Produto 2x
```
Pré-requisito: Um produto já adicionado
Passos:
1. Busque o mesmo produto novamente
2. Clique para selecionar
3. Clique "Adicionar"

Resultado Esperado:
✓ Quantidade é somada (não cria linha duplicada)
✓ Tabela mostra quantidade total
✓ Total recalculado corretamente

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 6: Editar Item
```
Pré-requisito: Produto adicionado à tabela
Passos:
1. Clique no botão ✎ (editar) na linha
2. Ajuste quantidade
3. Clique "Atualizar Item"

Resultado Esperado:
✓ Painel aparece com dados do item
✓ Botão muda para "Atualizar Item"
✓ Alterações são salvas
✓ Tabela atualizada
✓ Total recalculado

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 7: Remover Item
```
Pré-requisito: Produto adicionado à tabela
Passos:
1. Clique no botão ✕ (remover) na linha

Resultado Esperado:
✓ Item removido imediatamente
✓ Tabela atualizada
✓ Total recalculado
✓ Contador de itens atualizado

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 8: Filtro por Empresa
```
Pré-requisito: Página carregada
Passos:
1. Selecione uma empresa no dropdown
2. Digite um termo de busca

Resultado Esperado:
✓ Mostra apenas produtos dessa empresa
✓ Busca funciona dentro da empresa
✓ Contador atualizado

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 9: Limpar Busca
```
Pré-requisito: Busca com resultados
Passos:
1. Clique no botão ✕ no campo de busca

Resultado Esperado:
✓ Campo de busca limpo
✓ Dropdown fecha
✓ Painel fecha
✓ Contador = 0
✓ Foco no campo de busca

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 10: Escape para Fechar
```
Pré-requisito: Dropdown ou painel visível
Passos:
1. Pressione a tecla Escape

Resultado Esperado:
✓ Dropdown fecha
✓ Painel fecha
✓ Campo de busca mantém valor

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

## 🔍 Testes de Validação

### Teste 11: Validação de Quantidade
```
Pré-requisito: Painel de seleção visível
Passos:
1. Limpe o campo de quantidade
2. Clique "Adicionar"

Resultado Esperado:
✓ Alerta: "Por favor, insira uma quantidade válida"
✓ Foco no campo de quantidade

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 12: Validação de Preço
```
Pré-requisito: Painel de seleção visível
Passos:
1. Digite "abc" no campo de preço
2. Clique "Adicionar"

Resultado Esperado:
✓ Alerta: "Por favor, insira um preço válido"
✓ Foco no campo de preço

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 13: Validação de Produto
```
Pré-requisito: Painel fechado
Passos:
1. Clique "Adicionar" sem selecionar produto

Resultado Esperado:
✓ Alerta: "Por favor, selecione um produto"
✓ Foco no campo de busca

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

## ⚡ Testes de Performance

### Teste 14: Busca Rápida
```
Pré-requisito: Página carregada
Passos:
1. Digite rapidamente: "s-a-b-ã-o"
2. Observe o tempo de resposta

Resultado Esperado:
✓ Sem lag ou travamento
✓ Debounce funciona (aguarda 300ms)
✓ Resultados aparecem em < 1 segundo

Status: [ ] Passou [ ] Falhou
Tempo: _____ ms
```

### Teste 15: Múltiplos Itens
```
Pré-requisito: Página carregada
Passos:
1. Adicione 20 produtos diferentes
2. Observe performance

Resultado Esperado:
✓ Tabela rápida
✓ Sem lag ao adicionar
✓ Total recalculado rapidamente
✓ Editar/remover rápido

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

## 🌐 Testes de Compatibilidade

### Teste 16: Desktop (Chrome)
```
Navegador: Google Chrome (versão _____)
Resolução: 1920x1080

Resultado Esperado:
✓ Interface completa visível
✓ Sem elementos sobrepostos
✓ Botões clicáveis
✓ Dropdown posicionado corretamente

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 17: Tablet (iPad)
```
Dispositivo: iPad (versão _____)
Resolução: 768x1024

Resultado Esperado:
✓ Interface responsiva
✓ Campos touch-friendly
✓ Dropdown visível
✓ Sem scroll horizontal desnecessário

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

### Teste 18: Mobile (iPhone)
```
Dispositivo: iPhone (versão _____)
Resolução: 375x667

Resultado Esperado:
✓ Interface adaptada
✓ Campos grandes o suficiente
✓ Dropdown não sai da tela
✓ Botões clicáveis

Status: [ ] Passou [ ] Falhou
Observações: ___________________________
```

## 📋 Checklist Final

- [ ] Todos os testes funcionais passaram
- [ ] Todas as validações funcionam
- [ ] Performance aceitável
- [ ] Compatibilidade confirmada
- [ ] Sem erros no console
- [ ] Sem erros no servidor
- [ ] Dados salvos corretamente
- [ ] Interface intuitiva
- [ ] Documentação completa
- [ ] Pronto para produção

## 📝 Notas

```
Data do Teste: _______________
Testador: _______________
Ambiente: [ ] Desenvolvimento [ ] Staging [ ] Produção
Navegador: _______________
Dispositivo: _______________

Problemas Encontrados:
_________________________________
_________________________________
_________________________________

Recomendações:
_________________________________
_________________________________
_________________________________
```

---

**Versão**: 1.0
**Data**: 2025-11-05
**Status**: ✅ Pronto para Testes

