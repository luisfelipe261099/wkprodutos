# 🔧 Solução: Erro "Field 'id' doesn't have a default value"

## Problema
Ao tentar criar um novo orçamento, você recebeu o erro:
```
Field 'id' doesn't have a default value
```

Isso significa que o campo `id` da tabela `orcamentos` não estava configurado com `AUTO_INCREMENT`.

## Solução Aplicada ✅

Foram realizadas as seguintes correções:

### 1. **Correção Automática no Código**
Um verificador automático foi adicionado ao arquivo `criar_orcamento.php` que:
- ✓ Verifica se a tabela tem `AUTO_INCREMENT` configurado
- ✓ Corrige automaticamente se necessário
- ✓ Funciona na primeira vez que a página é acessada

### 2. **Arquivos de Banco de Dados Atualizados**
Os seguintes arquivos foram corrigidos para futuras restaurações:
- ✓ `u182607388_karla_wollinge.sql`
- ✓ `u182607388_karla_wollinge_tidb.sql`

### 3. **Scripts de Verificação Disponíveis**
Se precisar verificar ou corrigir manualmente, use:

#### Opção A: Verificar e Corrigir Automaticamente
1. Acesse http://seu-dominio/criar_orcamento.php
2. O sistema verificará e corrigirá automaticamente se necessário

#### Opção B: Usar Script Dedicado
Se o MySQL estiver acessível via CLI:
```bash
php verificar_banco.php
```

#### Opção C: Usar Ferramenta de Correção do Sistema
```bash
php fix_db_orcamentos.php
```

## O que foi mudado na estrutura do banco?

**Antes:**
```sql
CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL,
  ...
)
```

**Depois:**
```sql
CREATE TABLE `orcamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  ...
  PRIMARY KEY (`id`)
)
```

## Como Usar Agora

Agora você pode:
1. ✅ Criar novos orçamentos normalmente
2. ✅ Editar orçamentos existentes
3. ✅ Adicionar itens ao orçamento

## Próximos Passos

1. **Acesse a página:** http://seu-dominio/criar_orcamento.php
2. **Preecha os dados** do novo orçamento
3. **Clique em "Criar Orçamento"** - agora deve funcionar!

Se ainda tiver problemas, verifique:
- Se o banco de dados está acessível
- Se o usuário MySQL tem permissões de ALTER TABLE
- Os logs de erro do servidor

## Arquivos Modificados
- ✏️ `criar_orcamento.php` - Adicionado verificador automático
- 📝 `u182607388_karla_wollinge.sql` - Adicionado AUTO_INCREMENT
- 📝 `u182607388_karla_wollinge_tidb.sql` - Adicionado AUTO_INCREMENT
- ✨ `verificar_banco.php` - Novo script de verificação
- ✨ `fix_orcamentos_id.php` - Novo script de correção

---
**Data da correção:** 18 de março de 2026
