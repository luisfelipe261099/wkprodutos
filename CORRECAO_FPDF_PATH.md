# Correção: FPDF Library Path Issue

## Problema
Ao tentar gerar PDFs (orçamentos, vendas, relatórios), o sistema exibia o erro:
```
FPDF library not found. Please install FPDF.
```

Embora a biblioteca FPDF estivesse instalada em `vendor/setasign/fpdf/fpdf.php`.

## Causa Raiz
O problema era causado pelo uso de **caminhos relativos** nos scripts PHP. Quando os scripts são executados via servidor web, o diretório de trabalho (`getcwd()`) pode ser diferente do diretório do script, causando falha na localização do arquivo FPDF.

### Exemplo do Problema:
```php
// ❌ ERRADO - Usa caminho relativo
if (!file_exists('vendor/setasign/fpdf/fpdf.php')) {
    throw new Exception("FPDF library not found.");
}
require_once 'vendor/setasign/fpdf/fpdf.php';
```

## Solução Implementada
Substituir todos os caminhos relativos por **caminhos absolutos** usando a constante `__DIR__`:

```php
// ✅ CORRETO - Usa caminho absoluto
$fpdf_path = __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    throw new Exception("FPDF library not found at: " . $fpdf_path);
}
require_once $fpdf_path;
```

## Arquivos Corrigidos

1. **gerar_pdf_orcamento.php**
   - Linha 25: Verificação de arquivo com `__DIR__`
   - Linha 115: require_once com `__DIR__`

2. **gerar_pdf_venda.php**
   - Linha 27: Verificação de arquivo com `__DIR__`
   - Linha 113: require_once com `__DIR__`

3. **gerar_pdf_relatorio.php**
   - Linha 21: require_once com `__DIR__`

4. **enviar_orcamento.php**
   - Linha 13: require_once com `__DIR__`

5. **orcamentos.php**
   - Linha 60: require_once com `__DIR__`

6. **orcamentos.php.new**
   - Linha 74: require_once com `__DIR__`

7. **teste_pdf_sem_utf8_decode.php**
   - Linha 20: require_once com `__DIR__`

8. **debug_pdf_relatorio.php**
   - Linha 39-44: Verificação de arquivo com `__DIR__`

## Benefícios da Correção

✅ **Funciona em qualquer contexto**: O script agora funciona independentemente do diretório de trabalho
✅ **Melhor mensagem de erro**: Se o arquivo não for encontrado, mostra o caminho completo tentado
✅ **Mais seguro**: Usa caminhos absolutos em vez de relativos
✅ **Compatível com web servers**: Funciona corretamente quando executado via Apache, Nginx, etc.

## Como Testar

1. Acesse a página de geração de PDF de um orçamento:
   ```
   http://seu-servidor/gerar_pdf_orcamento.php?id=198
   ```

2. O PDF deve ser gerado sem erros

3. Se ainda houver erro, verifique:
   - Se o FPDF está instalado: `ls vendor/setasign/fpdf/fpdf.php`
   - Se não estiver, execute: `composer install`

## Notas Importantes

- A constante `__DIR__` retorna o diretório absoluto do arquivo PHP atual
- Isso garante que o caminho seja sempre correto, independentemente de onde o script é executado
- Todos os arquivos que usavam `require_once 'vendor/...'` foram atualizados

