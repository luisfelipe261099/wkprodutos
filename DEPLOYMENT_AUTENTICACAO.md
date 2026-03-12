# Guia de Deployment - Sistema de Autenticação

## 📋 Pré-requisitos

Antes de fazer o deployment, certifique-se de que:

- [x] PHP 7.4 ou superior instalado
- [x] MySQL 5.7 ou superior instalado
- [x] Apache com mod_rewrite habilitado
- [x] .htaccess ativado no servidor
- [x] Cookies habilitados no navegador
- [x] HTTPS configurado (recomendado)

---

## 🚀 Passos de Deployment

### 1. Fazer Upload dos Arquivos

Faça upload de todos os arquivos para o servidor, mantendo a estrutura de diretórios:

```
/system/
├── index.php (MODIFICADO)
├── login.php (MODIFICADO)
├── .htaccess (MODIFICADO)
├── dashboard.php
├── vendas.php
├── produtos.php
├── clientes.php
├── orcamentos.php
├── usuarios.php
├── logout.php
├── includes/
│   ├── db_connect.php
│   ├── header.php
│   ├── footer.php
│   └── email_config.php
├── css/
│   └── style.css
├── js/
│   └── script.js
├── uploads/
│   ├── produtos/
│   └── logos_empresas/
└── vendor/
    ├── autoload.php
    └── phpmailer/
```

### 2. Verificar Permissões

Certifique-se de que as permissões estão corretas:

```bash
# Diretórios: 755
chmod 755 /path/to/system
chmod 755 /path/to/system/includes
chmod 755 /path/to/system/css
chmod 755 /path/to/system/js
chmod 755 /path/to/system/uploads

# Arquivos: 644
chmod 644 /path/to/system/*.php
chmod 644 /path/to/system/.htaccess
chmod 644 /path/to/system/includes/*.php
chmod 644 /path/to/system/css/*.css
chmod 644 /path/to/system/js/*.js

# Diretório de uploads: 777 (para escrita)
chmod 777 /path/to/system/uploads
chmod 777 /path/to/system/uploads/produtos
chmod 777 /path/to/system/uploads/logos_empresas
```

### 3. Verificar Configuração do Apache

Certifique-se de que mod_rewrite está habilitado:

```bash
# No servidor Linux/Unix
a2enmod rewrite

# Reiniciar Apache
systemctl restart apache2
# ou
service apache2 restart
```

### 4. Verificar .htaccess

Certifique-se de que .htaccess está ativado no VirtualHost:

```apache
<Directory /path/to/system>
    AllowOverride All
    Require all granted
</Directory>
```

### 5. Testar Conexão com Banco de Dados

Acesse: `https://wkprodutosdelimpeza.com.br/system/test_db.php`

**Resultado esperado**:
- ✅ Conexão com banco de dados estabelecida
- ✅ Tabelas verificadas
- ✅ Sem erros

### 6. Testar Sistema de Autenticação

#### Teste 1: Acesso sem autenticação
```
1. Abra aba anônima
2. Acesse: https://wkprodutosdelimpeza.com.br/system
3. Resultado: Redireciona para login.php
```

#### Teste 2: Login bem-sucedido
```
1. Insira email e senha válidos
2. Clique em "Entrar"
3. Resultado: Redireciona para dashboard.php
```

#### Teste 3: Logout
```
1. Clique em "Logout"
2. Resultado: Redireciona para login.php
3. Tente acessar dashboard.php
4. Resultado: Redireciona para login.php
```

---

## 🔍 Verificações Pós-Deployment

### Verificação 1: Redirecionamento Funciona

```bash
# Teste com curl
curl -I https://wkprodutosdelimpeza.com.br/system

# Resultado esperado:
# HTTP/1.1 302 Found
# Location: https://wkprodutosdelimpeza.com.br/system/login.php
```

### Verificação 2: Sessão Funciona

```bash
# Teste com curl (com cookies)
curl -c cookies.txt -b cookies.txt \
  -d "email=admin@example.com&senha=senha123" \
  https://wkprodutosdelimpeza.com.br/system/login.php

# Resultado esperado:
# Sessão criada em cookies.txt
```

### Verificação 3: Diretórios Protegidos

```bash
# Teste acesso a includes
curl -I https://wkprodutosdelimpeza.com.br/system/includes/db_connect.php

# Resultado esperado:
# HTTP/1.1 403 Forbidden
# ou
# HTTP/1.1 404 Not Found
```

### Verificação 4: Recursos Estáticos Acessíveis

```bash
# Teste acesso a CSS
curl -I https://wkprodutosdelimpeza.com.br/system/css/style.css

# Resultado esperado:
# HTTP/1.1 200 OK
```

---

## ⚠️ Problemas Comuns e Soluções

### Problema 1: "404 Not Found" ao acessar /system

**Causa**: .htaccess não está ativado ou mod_rewrite não está habilitado

**Solução**:
1. Verifique se mod_rewrite está habilitado: `a2enmod rewrite`
2. Verifique se AllowOverride está configurado: `AllowOverride All`
3. Reinicie Apache: `systemctl restart apache2`

### Problema 2: Fica em loop de redirecionamento

**Causa**: login.php não tem a verificação de usuário logado

**Solução**:
1. Verifique se login.php tem a verificação no início
2. Verifique se não há redirecionamento circular

### Problema 3: Sessão não persiste

**Causa**: Cookies não estão habilitados ou session.save_path não está configurado

**Solução**:
1. Verifique se cookies estão habilitados no navegador
2. Verifique se session.save_path está configurado em php.ini
3. Verifique permissões de escrita em /tmp

### Problema 4: Arquivo de teste acessível sem admin

**Causa**: Arquivo não tem a verificação de admin

**Solução**:
1. Verifique se o arquivo tem session_start()
2. Verifique se tem a verificação de $_SESSION["nivel_acesso"]

---

## 📊 Monitoramento Pós-Deployment

### Logs a Monitorar

```bash
# Logs do Apache
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log

# Logs do PHP
tail -f /var/log/php-fpm.log
```

### Métricas a Acompanhar

- [ ] Taxa de redirecionamento para login
- [ ] Taxa de sucesso de login
- [ ] Taxa de erro de autenticação
- [ ] Tempo de resposta do sistema
- [ ] Uso de memória
- [ ] Uso de CPU

---

## 🔐 Segurança Pós-Deployment

### Checklist de Segurança

- [x] HTTPS configurado
- [x] Senhas hashificadas com password_hash()
- [x] Prepared statements para SQL
- [x] Sessões regeneradas após login
- [x] Diretórios sensíveis protegidos
- [x] Headers de segurança configurados
- [x] Cookies com flag HttpOnly
- [x] Cookies com flag Secure (HTTPS)

### Configurações Recomendadas em php.ini

```ini
# Segurança de Sessão
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Strict
session.use_strict_mode = On

# Segurança Geral
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
```

---

## 📞 Suporte

Para dúvidas ou problemas:

1. Consulte `AUTENTICACAO_FLUXO.md`
2. Consulte `TESTE_AUTENTICACAO.md`
3. Consulte `DIAGRAMA_FLUXO_AUTENTICACAO.txt`
4. Verifique os logs do servidor

---

**Data**: 2025-11-05
**Versão**: 1.0
**Status**: ✅ Pronto para Deployment

