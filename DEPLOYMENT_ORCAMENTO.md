# 🚀 Guia de Deployment - criar_orcamento.php

## 📋 Pré-Requisitos

- [ ] Acesso ao servidor
- [ ] Backup do arquivo original
- [ ] Acesso ao banco de dados
- [ ] Permissões de escrita no diretório
- [ ] Navegador para testes

---

## 🔄 Processo de Deployment

### Passo 1: Backup

```bash
# Fazer backup do arquivo original
cp criar_orcamento.php criar_orcamento.php.backup

# Fazer backup do banco de dados
mysqldump -u usuario -p banco_dados > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Passo 2: Verificar Sintaxe

```bash
# Verificar se não há erros de sintaxe PHP
php -l criar_orcamento.php

# Resultado esperado:
# No syntax errors detected in criar_orcamento.php
```

### Passo 3: Fazer Upload

**Opção A: Via FTP**
```
1. Conectar ao servidor via FTP
2. Navegar para /system/
3. Fazer upload de criar_orcamento.php
4. Confirmar permissões (644 ou 755)
```

**Opção B: Via SSH**
```bash
scp criar_orcamento.php usuario@servidor:/caminho/system/
```

**Opção C: Via Git**
```bash
git add criar_orcamento.php
git commit -m "Melhorias na interface de criar_orcamento.php"
git push origin main
```

### Passo 4: Verificar Permissões

```bash
# Verificar permissões
ls -la criar_orcamento.php

# Resultado esperado:
# -rw-r--r-- 1 usuario grupo 50K Nov  5 10:00 criar_orcamento.php

# Se necessário, ajustar permissões
chmod 644 criar_orcamento.php
```

### Passo 5: Testar em Staging

```
1. Acessar https://staging.wkprodutosdelimpeza.com.br/system/criar_orcamento.php
2. Fazer login
3. Testar funcionalidades:
   - [ ] Busca por nome
   - [ ] Busca por SKU
   - [ ] Filtro por empresa
   - [ ] Seleção de produto
   - [ ] Adição de item
   - [ ] Edição de item
   - [ ] Remoção de item
   - [ ] Cálculo de total
   - [ ] Criação de orçamento
4. Verificar console do navegador (F12)
5. Verificar logs do servidor
```

### Passo 6: Deploy em Produção

```bash
# Após testes bem-sucedidos em staging

# Opção 1: Substituir arquivo
cp criar_orcamento.php /var/www/html/system/

# Opção 2: Via Git
git pull origin main

# Opção 3: Via FTP
# Fazer upload do arquivo
```

### Passo 7: Verificar em Produção

```
1. Acessar https://wkprodutosdelimpeza.com.br/system/criar_orcamento.php
2. Fazer login
3. Testar funcionalidades principais
4. Verificar se não há erros
5. Monitorar logs por 24 horas
```

---

## 🧪 Testes Pós-Deployment

### Teste 1: Funcionalidade Básica
```
1. Criar novo orçamento
2. Buscar produto por nome
3. Adicionar 3 produtos diferentes
4. Editar um item
5. Remover um item
6. Salvar orçamento
7. Verificar se foi salvo no banco
```

### Teste 2: Performance
```
1. Medir tempo de busca
2. Testar com 400+ produtos
3. Adicionar 20 itens
4. Verificar se não há lag
5. Monitorar uso de memória
```

### Teste 3: Compatibilidade
```
1. Testar em Chrome
2. Testar em Firefox
3. Testar em Safari
4. Testar em Edge
5. Testar em mobile
```

### Teste 4: Segurança
```
1. Verificar se autenticação funciona
2. Tentar acessar sem login
3. Verificar SQL injection
4. Verificar XSS
5. Verificar CSRF
```

---

## 📊 Monitoramento

### Logs para Verificar

```bash
# Logs do PHP
tail -f /var/log/php-fpm/error.log

# Logs do Apache/Nginx
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log

# Logs da aplicação
tail -f /var/www/html/system/logs/error.log
```

### Métricas para Monitorar

- [ ] Taxa de erro
- [ ] Tempo de resposta
- [ ] Uso de CPU
- [ ] Uso de memória
- [ ] Requisições por segundo
- [ ] Erros de banco de dados

---

## 🔄 Rollback (Se Necessário)

### Se Houver Problemas

```bash
# Restaurar backup
cp criar_orcamento.php.backup criar_orcamento.php

# Restaurar banco de dados (se necessário)
mysql -u usuario -p banco_dados < backup_YYYYMMDD_HHMMSS.sql

# Limpar cache (se aplicável)
rm -rf /var/www/html/system/cache/*
```

### Comunicar Problema

```
1. Notificar equipe
2. Documentar erro
3. Criar ticket
4. Investigar causa
5. Corrigir
6. Testar novamente
7. Fazer novo deploy
```

---

## 📝 Checklist de Deployment

### Pré-Deployment
- [ ] Backup realizado
- [ ] Sintaxe verificada
- [ ] Testes em staging OK
- [ ] Documentação atualizada
- [ ] Equipe notificada

### Deployment
- [ ] Arquivo enviado
- [ ] Permissões corretas
- [ ] Sem erros de upload
- [ ] Arquivo acessível

### Pós-Deployment
- [ ] Funcionalidades testadas
- [ ] Performance OK
- [ ] Sem erros em logs
- [ ] Usuários notificados
- [ ] Monitoramento ativo

---

## 📞 Suporte

### Se Houver Problemas

1. **Verificar Logs**
   ```bash
   tail -f /var/log/php-fpm/error.log
   ```

2. **Verificar Console do Navegador**
   - Abrir F12
   - Ir para "Console"
   - Procurar por erros

3. **Verificar Banco de Dados**
   ```bash
   mysql -u usuario -p banco_dados
   SELECT * FROM orcamentos LIMIT 1;
   ```

4. **Contatar Desenvolvedor**
   - Fornecer logs
   - Descrever problema
   - Fornecer passos para reproduzir

---

## 🎯 Cronograma Recomendado

### Semana 1
- [ ] Deploy em staging
- [ ] Testes funcionais
- [ ] Feedback de usuários

### Semana 2
- [ ] Ajustes conforme feedback
- [ ] Testes finais
- [ ] Preparar produção

### Semana 3
- [ ] Deploy em produção
- [ ] Monitoramento 24h
- [ ] Suporte aos usuários

### Semana 4
- [ ] Análise de resultados
- [ ] Documentação final
- [ ] Próximas melhorias

---

## 📊 Métricas de Sucesso

Após deployment, verificar:

- [ ] Taxa de erro < 1%
- [ ] Tempo de resposta < 500ms
- [ ] Satisfação do usuário > 4/5
- [ ] Sem reclamações críticas
- [ ] Uso de CPU normal
- [ ] Uso de memória normal

---

## 🎉 Conclusão

Após seguir este guia, o deployment deve ser bem-sucedido e a nova interface estará disponível para todos os usuários.

**Tempo estimado**: 2-4 horas
**Risco**: Baixo (com backup)
**Benefício**: Alto (75% mais rápido)

---

**Data**: 2025-11-05
**Versão**: 1.0
**Status**: ✅ Pronto para Deployment

