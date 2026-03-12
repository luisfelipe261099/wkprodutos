# Guia de Testes - Sistema de Autenticação

## Resumo das Mudanças

O sistema foi configurado para redirecionar automaticamente usuários não autenticados para a página de login quando acessam `https://wkprodutosdelimpeza.com.br/system`.

## Testes Recomendados

### ✅ Teste 1: Acesso à Raiz sem Autenticação

**Objetivo**: Verificar se usuário não autenticado é redirecionado para login

**Passos**:
1. Abra uma aba anônima/privada do navegador
2. Acesse: `https://wkprodutosdelimpeza.com.br/system`
3. Acesse também: `https://wkprodutosdelimpeza.com.br/system/`

**Resultado Esperado**:
- ✅ Redireciona para `login.php`
- ✅ Exibe formulário de login
- ✅ URL muda para `https://wkprodutosdelimpeza.com.br/system/login.php`

---

### ✅ Teste 2: Login com Credenciais Válidas

**Objetivo**: Verificar se login funciona e redireciona para dashboard

**Passos**:
1. Na página de login, insira email e senha válidos
2. Clique em "Entrar"

**Resultado Esperado**:
- ✅ Login bem-sucedido
- ✅ Redireciona para `dashboard.php`
- ✅ Sessão criada com dados do usuário
- ✅ Pode acessar todas as páginas protegidas

---

### ✅ Teste 3: Acesso Direto a Página Protegida sem Autenticação

**Objetivo**: Verificar se páginas protegidas redirecionam para login

**Passos**:
1. Em aba anônima, tente acessar: `https://wkprodutosdelimpeza.com.br/system/vendas.php`
2. Tente também: `https://wkprodutosdelimpeza.com.br/system/produtos.php`
3. Tente também: `https://wkprodutosdelimpeza.com.br/system/dashboard.php`

**Resultado Esperado**:
- ✅ Redireciona para `login.php`
- ✅ Não exibe conteúdo da página protegida

---

### ✅ Teste 4: Usuário Logado Acessa login.php

**Objetivo**: Verificar se usuário logado não vê página de login

**Passos**:
1. Faça login com credenciais válidas
2. Tente acessar: `https://wkprodutosdelimpeza.com.br/system/login.php`

**Resultado Esperado**:
- ✅ Redireciona para `dashboard.php`
- ✅ Não exibe formulário de login

---

### ✅ Teste 5: Acesso a Arquivo de Teste sem ser Admin

**Objetivo**: Verificar se arquivos de teste requerem permissão admin

**Passos**:
1. Faça login com usuário comum (não admin)
2. Tente acessar: `https://wkprodutosdelimpeza.com.br/system/test_db.php`
3. Tente também: `https://wkprodutosdelimpeza.com.br/system/debug_erro.php`

**Resultado Esperado**:
- ✅ Exibe mensagem: "Acesso negado. Apenas administradores podem acessar esta página."
- ✅ Não exibe conteúdo do arquivo

---

### ✅ Teste 6: Acesso a Arquivo de Teste como Admin

**Objetivo**: Verificar se admin consegue acessar arquivos de teste

**Passos**:
1. Faça login com usuário admin
2. Acesse: `https://wkprodutosdelimpeza.com.br/system/test_db.php`
3. Acesse também: `https://wkprodutosdelimpeza.com.br/system/debug_erro.php`

**Resultado Esperado**:
- ✅ Exibe conteúdo do arquivo
- ✅ Mostra informações de diagnóstico

---

### ✅ Teste 7: Logout

**Objetivo**: Verificar se logout destrói sessão

**Passos**:
1. Faça login
2. Clique em "Logout" (no menu superior)
3. Tente acessar: `https://wkprodutosdelimpeza.com.br/system/dashboard.php`

**Resultado Esperado**:
- ✅ Sessão destruída
- ✅ Redireciona para `login.php`
- ✅ Não consegue acessar páginas protegidas

---

### ✅ Teste 8: Recursos Estáticos Acessíveis

**Objetivo**: Verificar se CSS, JS e imagens funcionam sem autenticação

**Passos**:
1. Em aba anônima, acesse: `https://wkprodutosdelimpeza.com.br/system/css/style.css`
2. Acesse também: `https://wkprodutosdelimpeza.com.br/system/js/script.js`

**Resultado Esperado**:
- ✅ Arquivos carregam normalmente
- ✅ Não redireciona para login

---

### ✅ Teste 9: Diretórios Protegidos Inacessíveis

**Objetivo**: Verificar se diretórios sensíveis estão protegidos

**Passos**:
1. Tente acessar: `https://wkprodutosdelimpeza.com.br/system/includes/db_connect.php`
2. Tente acessar: `https://wkprodutosdelimpeza.com.br/system/vendor/`

**Resultado Esperado**:
- ✅ Erro 403 (Forbidden) ou 404
- ✅ Não exibe conteúdo sensível

---

### ✅ Teste 10: Múltiplas Abas/Sessões

**Objetivo**: Verificar se múltiplas sessões funcionam corretamente

**Passos**:
1. Abra 2 abas do navegador
2. Na aba 1: Faça login com usuário A
3. Na aba 2: Faça login com usuário B
4. Verifique se cada aba mantém sua própria sessão

**Resultado Esperado**:
- ✅ Cada aba tem sua própria sessão
- ✅ Dados do usuário correto aparecem em cada aba
- ✅ Logout em uma aba não afeta a outra

---

## Checklist de Validação

- [ ] Teste 1: Acesso à raiz sem autenticação
- [ ] Teste 2: Login com credenciais válidas
- [ ] Teste 3: Acesso direto a página protegida
- [ ] Teste 4: Usuário logado acessa login.php
- [ ] Teste 5: Acesso a arquivo de teste sem admin
- [ ] Teste 6: Acesso a arquivo de teste como admin
- [ ] Teste 7: Logout funciona
- [ ] Teste 8: Recursos estáticos acessíveis
- [ ] Teste 9: Diretórios protegidos inacessíveis
- [ ] Teste 10: Múltiplas sessões funcionam

## Notas Importantes

1. **Sessão**: O sistema usa `session_start()` para gerenciar autenticação
2. **Segurança**: Senhas são verificadas com `password_verify()`
3. **Admin**: Alguns arquivos requerem `$_SESSION["nivel_acesso"] == "admin"`
4. **Redirecionamento**: Usa `header("location: ...")` para redirecionar
5. **Logout**: Destrói a sessão completamente com `session_destroy()`

## Troubleshooting

### Problema: Não redireciona para login
- Verifique se `.htaccess` está ativado no servidor
- Verifique se `mod_rewrite` está habilitado no Apache
- Verifique se `index.php` tem a verificação de autenticação

### Problema: Fica em loop de redirecionamento
- Verifique se `login.php` tem a verificação de usuário logado
- Verifique se não há redirecionamento circular

### Problema: Arquivo de teste acessível sem admin
- Verifique se o arquivo tem `session_start()` no início
- Verifique se tem a verificação de `$_SESSION["nivel_acesso"]`

## Contato

Para dúvidas ou problemas, consulte a documentação em `AUTENTICACAO_FLUXO.md`.

