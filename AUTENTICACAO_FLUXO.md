# Fluxo de Autenticação do Sistema

## Resumo
Quando um usuário não autenticado acessa `https://wkprodutosdelimpeza.com.br/system`, ele é automaticamente redirecionado para a página de login (`login.php`).

## Fluxo Detalhado

### 1. Acesso à Raiz do Sistema
- **URL**: `https://wkprodutosdelimpeza.com.br/system` ou `https://wkprodutosdelimpeza.com.br/system/`
- **Arquivo**: `.htaccess` redireciona para `index.php`

### 2. Verificação em index.php
O arquivo `index.php` verifica o status de autenticação:

```php
session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Usuário logado → Redireciona para dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Usuário não logado → Redireciona para login
    header("Location: login.php");
    exit();
}
```

### 3. Página de Login (login.php)
- Se o usuário já está logado, redireciona para `dashboard.php`
- Se não está logado, exibe o formulário de login
- Após autenticação bem-sucedida, cria a sessão e redireciona para `dashboard.php`

### 4. Páginas Protegidas
Todas as páginas protegidas (dashboard.php, vendas.php, etc.) verificam:

```php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
```

## Configuração do .htaccess

O arquivo `.htaccess` foi configurado para:
1. Redirecionar todas as requisições para `index.php` (exceto arquivos e diretórios existentes)
2. Permitir acesso a recursos estáticos (CSS, JS, imagens)
3. Proteger diretórios sensíveis (includes, uploads, vendor)

### Regras de Reescrita
```
RewriteCond %{REQUEST_FILENAME} !-f          # Não é um arquivo
RewriteCond %{REQUEST_FILENAME} !-d          # Não é um diretório
RewriteCond %{REQUEST_URI} !^/login\.php$    # Não é login.php
RewriteCond %{REQUEST_URI} !^/css/           # Não é CSS
RewriteCond %{REQUEST_URI} !^/js/            # Não é JS
RewriteCond %{REQUEST_URI} !^/images/        # Não é imagens
RewriteCond %{REQUEST_URI} !^/includes/      # Não é includes
RewriteCond %{REQUEST_URI} !^/uploads/       # Não é uploads
RewriteCond %{REQUEST_URI} !^/vendor/        # Não é vendor
RewriteRule ^(.*)$ index.php [L]             # Redireciona para index.php
```

## Fluxo Visual

```
┌─────────────────────────────────────────┐
│ Acesso: /system ou /system/             │
└──────────────┬──────────────────────────┘
               │
               ▼
        ┌──────────────┐
        │ .htaccess    │
        │ Redireciona  │
        │ para         │
        │ index.php    │
        └──────┬───────┘
               │
               ▼
        ┌──────────────────────┐
        │ index.php            │
        │ Verifica sessão      │
        └──────┬───────────────┘
               │
        ┌──────┴──────────┐
        │                 │
        ▼                 ▼
   ┌─────────┐      ┌──────────┐
   │ Logado? │      │ Não      │
   │ SIM     │      │ Logado   │
   └────┬────┘      └────┬─────┘
        │                │
        ▼                ▼
   ┌─────────────┐  ┌──────────────┐
   │ Dashboard   │  │ login.php    │
   │ (protegida) │  │ (formulário) │
   └─────────────┘  └──────┬───────┘
                           │
                    ┌──────┴──────┐
                    │             │
                    ▼             ▼
              ┌──────────┐  ┌──────────┐
              │ Autentica│  │ Erro de  │
              │ com      │  │ Login    │
              │ sucesso  │  │ (exibe   │
              └────┬─────┘  │ mensagem)│
                   │        └──────────┘
                   ▼
            ┌─────────────┐
            │ Dashboard   │
            │ (protegida) │
            └─────────────┘
```

## Testes Recomendados

1. **Acesso sem autenticação**
   - Abra `https://wkprodutosdelimpeza.com.br/system`
   - Deve redirecionar para `login.php`

2. **Login bem-sucedido**
   - Faça login com credenciais válidas
   - Deve redirecionar para `dashboard.php`

3. **Acesso direto a página protegida sem autenticação**
   - Tente acessar `https://wkprodutosdelimpeza.com.br/system/vendas.php`
   - Deve redirecionar para `login.php`

4. **Logout**
   - Clique em logout
   - Deve redirecionar para `login.php`

## Segurança

- ✅ Sessões regeneradas após login (previne fixação de sessão)
- ✅ Senhas verificadas com `password_verify()` (hashing seguro)
- ✅ Prepared statements (previne SQL injection)
- ✅ Diretórios sensíveis protegidos no `.htaccess`
- ✅ Headers de segurança configurados (X-Frame-Options, CSP, etc.)
- ✅ Arquivos de teste/debug protegidos (apenas admin)
- ✅ Redirecionamento automático para login quando não autenticado

## Arquivos Modificados

### 1. **index.php** (Principal)
- Agora verifica se o usuário está logado
- Se logado → redireciona para `dashboard.php`
- Se não logado → redireciona para `login.php`

### 2. **login.php**
- Adicionada verificação: se já está logado, redireciona para `dashboard.php`
- Evita que usuários logados vejam a página de login

### 3. **.htaccess**
- Modificado para redirecionar para `index.php` (em vez de `login.php`)
- Adicionadas exceções para `/uploads/` e `/vendor/`
- `index.php` faz a verificação de autenticação

### 4. **Arquivos de Teste/Debug Protegidos**
Os seguintes arquivos agora requerem autenticação como **admin**:
- `instalar_phpmailer.php`
- `instalar_tcpdf.php`
- `instalar_marketplace.php`
- `debug_erro.php`
- `debug_pdf_relatorio.php`
- `testeemail.php`
- `teste_email_simples.php`
- `teste_envio_completo.php`
- `verificar_email.php`
- `test_db.php`
- `test_relatorios_connection.php`

## Como Testar

### Teste 1: Acesso sem autenticação
```
1. Abra uma aba anônima/privada do navegador
2. Acesse: https://wkprodutosdelimpeza.com.br/system
3. Resultado esperado: Redireciona para login.php
```

### Teste 2: Login bem-sucedido
```
1. Faça login com credenciais válidas
2. Resultado esperado: Redireciona para dashboard.php
3. Você pode acessar todas as páginas protegidas
```

### Teste 3: Acesso direto a página protegida sem autenticação
```
1. Em aba anônima, tente acessar: https://wkprodutosdelimpeza.com.br/system/vendas.php
2. Resultado esperado: Redireciona para login.php
```

### Teste 4: Acesso a arquivo de teste sem ser admin
```
1. Faça login com usuário comum (não admin)
2. Tente acessar: https://wkprodutosdelimpeza.com.br/system/test_db.php
3. Resultado esperado: Mensagem "Acesso negado. Apenas administradores..."
```

### Teste 5: Logout
```
1. Clique em logout
2. Resultado esperado: Redireciona para login.php
3. Tente acessar dashboard.php
4. Resultado esperado: Redireciona para login.php
```

