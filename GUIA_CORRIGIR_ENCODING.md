# 🔧 Guia: Corrigir Encoding de Caracteres em Produtos

## ❌ Problema Identificado
Os nomes dos produtos estão com caracteres especiais incorretos:
- `Ã¡` deveria ser `á`
- `Ã•` deveria ser `Ó`
- `Â°` deveria ser `°`
- `Ã§` deveria ser `ç`

Isso acontece quando há **conflito de encoding UTF-8** entre o banco de dados e a aplicação.

---

## ✅ Solução Implementada

### 1. **Melhorias no Banco de Dados** (db_connect.php)
✅ Adicionado:
- `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci`
- `SET CHARACTER SET utf8mb4`
- `SET COLLATION_CONNECTION = utf8mb4_unicode_ci`
- Header HTTP: `Content-Type: text/html; charset=utf-8`

Isso garante que **todas as novas conexões e dados salvos sejam em UTF-8 puro**.

### 2. **Script de Correção** (corrigir_encoding_produtos.php)
Criei um script que:
- ✅ Detecta produtos com encoding incorreto
- ✅ Converte caracteres truncados para o formato correto
- ✅ Atualiza todos os produtos automaticamente

---

## 🚀 Como Usar a Correção

### **Passo 1: Acessar o Script**
1. Faça login no sistema
2. **Acesse esta URL diretamente no navegador:**
   ```
   http://localhost/WK/wkprodutos/corrigir_encoding_produtos.php
   ```
   (Ou substitua `localhost` pelo seu domínio: `https://seusite.com/corrigir_encoding_produtos.php`)

### **Passo 2: Visualizar Produtos com Problema**
- A página exibirá uma **lista de todos os produtos com caracteres incorretos**
- Exemplo: `Ã¡cool em Gel 70Â° - BB 5 Litros`

### **Passo 3: Corrigir Automaticamente**
1. Clique no botão **"✅ Corrigir Todos os Produtos"**
2. Aguarde o processamento (pode levar alguns segundos)
3. O sistema exibirá cada produto **antes e depois**:
   ```
   ✅ Produto ID 123:
   ❌ Ã¡lcool em Gel 70Â° - BB 5 Litros
   ➜ ✅ Álcool em Gel 70° - BB 5 Litros
   ```

### **Passo 4: Verificar Resultado**
- Ao finalizar, uma mensagem dirá **quantos produtos foram corrigidos**
- Visite a página de **Produtos** para confirmar que os nomes estão corretos

---

## 📋 O Que Será Corrigido

| Incorreto | Correto |
|-----------|---------|
| `Ã¡` | `á` |
| `Ã©` | `é` |
| `Ã­` | `í` |
| `Ã³` | `ó` |
| `Ã¡` | `ú` |
| `Ã†` | `Á` |
| `Ã‰` | `É` |
| `Ã› ` | `Í` |
| `Ã³` | `Ó` |
| `Ã"` | `Ú` |
| `Ã§` | `ç` |
| `Â°` | `°` |
| `Â` | (removido) |

---

## 🔐 Segurança

- **✅ Script protegido**: Requer login para acessar
- **✅ Somente leitura antes de confirmar**: Mostra o que será mudado
- **✅ Sem perda de dados**: Apenas converte a codificação

---

## ✨ Após a Correção

Daqui em diante:
1. **Todos os novos produtos** serão salvos com encoding UTF-8 correto
2. **PDF e orçamentos** exibirão nomes corretamente
3. **Caracteres especiais** funcionarão perfeitamente

---

## 🆘 Se Ainda Tiver Problemas

**Após executar a correção:**
1. **Limpe o cache do navegador** (Ctrl+Shift+Delete)
2. **Feche e abra o navegador novamente**
3. **Verifique se o nome do produto está correto** em:
   - Lista de produtos
   - Formulário de criação de orçamento
   - PDFs gerados

Se o problema persistir:
- Verifique se o banco de dados está com charset `utf8mb4`
- Confirme que o arquivo `includes/db_connect.php` foi atualizado
- Teste salvando um novo produto com acentos

---

## 📌 Resumo Rápido

```
🔍 Detectar → 🔧 Corrigir → ✅ Verificar → 🎯 Concluído!
```

1. Acesse: `corrigir_encoding_produtos.php`
2. Clique: **Corrigir Todos os Produtos**
3. Aguarde: Confirmação de sucesso
4. Pronto! Seus produtos estão corrigidos! 🎉
