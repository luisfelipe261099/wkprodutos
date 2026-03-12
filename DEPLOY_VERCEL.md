# Deploy na Vercel (PHP)

## 1) Variaveis de ambiente na Vercel
Configure em Project Settings -> Environment Variables:

- `DB_HOST`
- `DB_PORT` (ex.: `3306`)
- `DB_USER`
- `DB_PASSWORD`
- `DB_NAME`
- `APP_ENV=production`

Opcional (alternativa):
- `DATABASE_URL` no formato `mysql://usuario:senha@host:3306/banco`

## 2) Runtime PHP
Este projeto ja inclui `vercel.json` com build usando `vercel-php`.

## 3) Sessao e uploads
- O projeto usa `session_start()` em varias paginas.
- Em ambiente serverless, sessoes por arquivo podem ser volateis entre invocacoes.
- Para estabilidade em producao, considere migrar sessao para Redis/DB.

Uploads:
- O filesystem da Vercel e efemero.
- Arquivos enviados localmente podem nao persistir.
- Para producao, use armazenamento externo (S3, Cloudinary, Supabase Storage etc.).

## 4) Banco de dados
A conexao foi ajustada para ler variaveis de ambiente no arquivo de conexao.

## 5) Deploy
1. Conecte o repositorio no painel da Vercel.
2. Configure as variaveis.
3. Execute o deploy.
