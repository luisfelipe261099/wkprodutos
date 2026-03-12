# 🔍 Debug - Busca de Produtos

## 📋 Mudanças Realizadas

### 1. Adicionado Select Hidden com Debug

**Arquivo:** `criar_orcamento.php` (Linhas 758-784)

```php
<!-- Select Hidden com Produtos para Busca -->
<select id="produto_select" style="display: none;">
    <?php
    // Debug: Verificar se a query foi executada
    if (!$produtos_options) {
        echo '<!-- ERRO: Query de produtos falhou: ' . htmlspecialchars($conn->error) . ' -->';
    } elseif ($produtos_options->num_rows == 0) {
        echo '<!-- AVISO: Nenhum produto encontrado -->';
    } else {
        // Renderizar todos os produtos
        while($produto = $produtos_options->fetch_assoc()) {
            $id = isset($produto['id']) ? $produto['id'] : '';
            $nome = isset($produto['nome']) ? $produto['nome'] : '';
            $sku = isset($produto['sku']) ? $produto['sku'] : '';
            $preco = isset($produto['preco_venda']) ? $produto['preco_venda'] : '0';
            $empresa_id = isset($produto['empresa_id']) ? $produto['empresa_id'] : '';
            
            echo '<option value="' . htmlspecialchars($id) . '" 
                    data-nome="' . htmlspecialchars($nome) . '"
                    data-sku="' . htmlspecialchars($sku) . '"
                    data-preco="' . htmlspecialchars($preco) . '"
                    data-empresa="' . htmlspecialchars($empresa_id) . '">' 
                    . htmlspecialchars($nome) . ' (SKU: ' . htmlspecialchars($sku) . ')</option>';
        }
    }
    ?>
</select>
```

### 2. Adicionado Debug no JavaScript

**Arquivo:** `criar_orcamento.php` (Linhas 936-964)

```javascript
// Carregar todos os produtos
const produtoSelectElement = document.getElementById('produto_select');

if (!produtoSelectElement) {
    console.error('❌ ERRO: Elemento produto_select não encontrado!');
} else {
    console.log('✅ Elemento produto_select encontrado');
    console.log('Total de options:', produtoSelectElement.options.length);
    
    Array.from(produtoSelectElement.options).forEach((option, index) => {
        if (option.value) {
            const produto = {
                id: option.value,
                nome: option.dataset.nome || option.textContent,
                sku: option.dataset.sku || '',
                preco: parseFloat(option.dataset.preco),
                empresa_id: option.dataset.empresa || '',
                texto_completo: option.textContent
            };
            todosProdutosNovo.push(produto);
            
            if (index < 3) {
                console.log('Produto ' + (index + 1) + ':', produto);
            }
        }
    });
    
    console.log('✅ Total de produtos carregados:', todosProdutosNovo.length);
}
```

---

## 🧪 Como Debugar

### Passo 1: Abrir a Página

1. Abrir `criar_orcamento.php`
2. Fazer login se necessário

### Passo 2: Abrir Developer Tools

1. Pressionar `F12`
2. Ir para a aba "Console"

### Passo 3: Verificar Mensagens

Você deve ver mensagens como:

```
✅ Elemento produto_select encontrado
Total de options: 400
Produto 1: {id: "7", nome: "Saco de lixo preto 20lts reforçado c/100un", sku: "1001", preco: 8.6, empresa_id: "5"}
Produto 2: {id: "8", nome: "Saco de lixo preto 20lts c/100un", sku: "1002", preco: 5.1, empresa_id: "5"}
Produto 3: {id: "9", nome: "Saco de lixo preto 20lts reforçado c/100un", sku: "1003", preco: 8.1, empresa_id: "5"}
✅ Total de produtos carregados: 400
```

---

## ❌ Se Houver Erro

### Erro 1: "Elemento produto_select não encontrado!"

**Causa:** O elemento não foi renderizado

**Solução:**
1. Verificar se o arquivo foi atualizado
2. Fazer F5 para recarregar
3. Limpar cache (Ctrl+Shift+Del)

### Erro 2: "Total de options: 0"

**Causa:** Nenhum produto no banco de dados

**Solução:**
1. Verificar se há produtos na tabela `produtos`
2. Executar: `SELECT COUNT(*) FROM produtos;`
3. Se vazio, inserir produtos

### Erro 3: "ERRO: Query de produtos falhou"

**Causa:** Erro na query SQL

**Solução:**
1. Verificar se a tabela `produtos` existe
2. Verificar se a tabela `empresas_representadas` existe
3. Verificar se há permissão de leitura

### Erro 4: "Nenhum produto encontrado"

**Causa:** Query retornou 0 linhas

**Solução:**
1. Verificar se há produtos cadastrados
2. Verificar se produtos estão ativos
3. Verificar se empresa_id está preenchido

---

## 🔍 Verificar HTML

### Passo 1: Abrir Developer Tools

1. Pressionar `F12`
2. Ir para a aba "Elements" ou "Inspector"

### Passo 2: Procurar pelo Select

1. Pressionar `Ctrl+F`
2. Digitar `produto_select`
3. Verificar se elemento existe

### Passo 3: Verificar Options

1. Expandir o elemento `<select id="produto_select">`
2. Verificar se há `<option>` dentro
3. Verificar se data-attributes estão preenchidos

---

## 📊 Estrutura Esperada

```html
<select id="produto_select" style="display: none;">
    <option value="7" 
            data-nome="Saco de lixo preto 20lts reforçado c/100un"
            data-sku="1001"
            data-preco="8.60"
            data-empresa="5">
        Saco de lixo preto 20lts reforçado c/100un (SKU: 1001)
    </option>
    <option value="8" 
            data-nome="Saco de lixo preto 20lts c/100un"
            data-sku="1002"
            data-preco="5.10"
            data-empresa="5">
        Saco de lixo preto 20lts c/100un (SKU: 1002)
    </option>
    ...
</select>
```

---

## 🧪 Teste Rápido

### Teste 1: Verificar Elemento

```javascript
// No Console (F12), executar:
document.getElementById('produto_select')

// Resultado esperado:
<select id="produto_select" style="display: none;">...</select>
```

### Teste 2: Verificar Options

```javascript
// No Console (F12), executar:
document.getElementById('produto_select').options.length

// Resultado esperado:
400 (ou número de produtos)
```

### Teste 3: Verificar Primeiro Produto

```javascript
// No Console (F12), executar:
const opt = document.getElementById('produto_select').options[0];
console.log({
    value: opt.value,
    nome: opt.dataset.nome,
    sku: opt.dataset.sku,
    preco: opt.dataset.preco,
    empresa: opt.dataset.empresa
});

// Resultado esperado:
{
    value: "7",
    nome: "Saco de lixo preto 20lts reforçado c/100un",
    sku: "1001",
    preco: "8.60",
    empresa: "5"
}
```

### Teste 4: Verificar Array Carregado

```javascript
// No Console (F12), executar:
console.log('Total:', todosProdutosNovo.length);
console.log('Primeiros 3:', todosProdutosNovo.slice(0, 3));

// Resultado esperado:
Total: 400
Primeiros 3: [
    {id: "7", nome: "Saco de lixo preto 20lts reforçado c/100un", sku: "1001", preco: 8.6, empresa_id: "5"},
    {id: "8", nome: "Saco de lixo preto 20lts c/100un", sku: "1002", preco: 5.1, empresa_id: "5"},
    {id: "9", nome: "Saco de lixo preto 20lts reforçado c/100un", sku: "1003", preco: 8.1, empresa_id: "5"}
]
```

---

## 📝 Próximos Passos

1. Abrir a página
2. Abrir Console (F12)
3. Verificar mensagens de debug
4. Se houver erro, seguir troubleshooting
5. Se tudo OK, testar busca

---

**Data:** 2025-11-05
**Versão:** 2.2
**Status:** ✅ Com Debug

