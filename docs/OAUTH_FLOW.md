# Google Search Console OAuth 2.0 Flow

## 1. Configuração do App no Google Cloud Console
1. Criar Projeto no Google Cloud Platform associado à Empresa.
2. Ativar a "Google Search Console API" no painel de APIs.
3. Configurar a "OAuth Consent Screen".
    - Deve conter link para Termos de Serviço e Política de Privacidade do SaaS (Obrigatório pro Google aprovar o App).
    - Tipo: External.
4. Escopos necessários configurados: `https://www.googleapis.com/auth/webmasters.readonly`. Somente leitura é o suficiente.

## 2. Fluxo de Autenticação na Aplicação (Vue/React <-> Laravel)
1. **Frontend:** Usuário logado clica no CTA "Conectar Conta / Google Search Console".
2. **Backend:** Endpoint redireciona para o provedor Socialite do Google.
    - É CRUCIAL enviar os paramêtros `access_type=offline` e `prompt=consent`. Isso força o Google a entregar o `refresh_token` na primeira vez.
3. **Google:** O usuário vê a tela de permissão, aceita ler os dados do GSC, e o Google redireciona para a `callback_url` da API.
4. **Backend (Callback):**
   - Recebe o código de autorização (`code`) do Google.
   - O Socialite troca o code: `$googleUser = Socialite::driver('google')->stateless()->user();`.
   - Extrai o `$googleUser->token` e `$googleUser->refreshToken`.

## 3. Armazenamento Seguro
- Recebidos os tokens, salvar na tabela respectiva amarrado ao `project_id` instanciado pelo usuário.
- **Requisito de Segurança:** Os tokens NUNCA devem residir no banco de dados em plain-text. Utilizar `Illuminate\Support\Facades\Crypt`.
- Ao ler o Token, decriptografar em runtime.

## 4. Lifecycle das Requisições da API GSC e Renovação Automática
A autenticação do usuário com o GSC em background é essencialmente "Headless".
- O CRON Diário invoca chamadas através do Token guardado. Cada acesso de API GSC obriga injetar o Token como Bearer.
- Access tokens expiram geralmente a cada ~1 hora.
- Se o Guzzle Http Client tentar a requisição e tomar `401 Unauthorized` ou prever expiração checando a flag temporal do token gravada na base:
   1. Usar o Refresh Token que está fixo e guardado no DB.
   2. Bater no endpoint `/token` do Google usando as Credenciais Oauth2 (client id / client secret originais do SaaS).
   3. Receber o `new_access_token`.
   4. Salvar transparente (`Crypt()`) na base.
   5. Refazer a chamada pro Search Console e obter o Analytics.
- *Caso Limite:* Se refresh token falhar com erro 400 'invalid_grant' (Usuário ativamente entrou na conta Google pessoal e revogou manualmente nossa permissão do app), registrar evento log, marcar integração como `disconnected` e disparar alerta de tela na próxima vez que ele logar ou webhook para re-autorizar.
