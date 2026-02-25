# General System Architecture & Scalability

## 1. Visão Arquitetural
A arquitetura adota um princípio de **Camadas Desacopladas**.
1. **Frontend (App):** React / Vue.js SPA interagindo por REST API. Deploy estático CDN / Vercel.
2. **Backend API:** Laravel Octane (PHP) provisionado via Nginx/Docker.
3. **Queue Workers:** Instâncias do Laravel Horizon exclusivamente voltadas para processamento de background jobs pesados.
4. **Data Layer:** PostgreSQL para persistência primária e estruturada.
5. **Cache Layer:** Redis (essencial p/ Rate Limit API, Caching de Aggregates de reports front-end e gestão do queue memory).

## 2. Multi-Tenant Structure
Formato: **Single Database, Tenant Scope**. Opta-se por isso visando facilidade de infraestrutura mantendo segurança aplicativa. Será gerenciado via `spatie/laravel-multitenancy`.

**Regras Essenciais:**
- Toda tabela (salvo globals como configs de sistema ou o próprio tenant e subscriptions) da parte SaaS como `projects`, `performance_data`, `url_status` deve conter a foreign key `tenant_id`.
- Chaves estrangeiras obrigando cascata logica, `ON DELETE CASCADE`.
- As consultas usando Eloquent utilizarão o *Global Scope Trait* do multi-tenant (para que `Project::all()` obrigatoriamente se restrinja ao Tenant contextual no Request e não vaze dados).

## 3. Estratégia de Escalabilidade (Scale Strategy)
Como estruturamos para um modelo de crescimento horizontal:
- **Stateless API:** O Laravel não armazenará estado em arquivo. Usuários logados validam na database com `SanctumTokens` ou via JWT de Redis. Arquivos uploadados irão obrigatoriamente pro S3.
- **Worker Auto Scaling:** Uma conta corporativa pode incluir um site gigante gerando milhões de registros históricos GSC. O Horizon deve poder estar containerizado em instâncas cloud onde workers possam crescer dependentes do tamanho de filas ativas (Ex. AWS EC2 Auto-scale Group atrelado à metrica Redis ou k8s HPA/KEDA pods deployment).
- **Separation of Concerns (Compute Resources):** A mesma VM Web/API NUNCA fará o CRON do Google Crawler no background. Computações são logicamente e fisicamente divididas para a API nunca sofrer degradação de Load por culpa dos workers da madrugada.

## 4. Monitoramento e Diagnóstico (Observabilidade)
- **Error Tracking (Sentry/Flare):** Captura em tempo real de exception frames, timeouts no Guzzle GSC e limit exceeded events.
- **Performance Tracing:** Laravel Pulse ou Datadog integrado (Trace das queries pesadas em `performance_data`).
- **Log Management:** Logs estruturados salvos em JSON lines, push para EFK stack/ELK Stack p/ monitorar retries the GSC e rate limiting em graficos.

## 5. Caching e Otimização de Resposta
- Agregados exibidos em Dashboard (Performance Chart dos ultimos anos) serão cacheados pelo Redis a noite. Acesso diurno pelo web é < 50ms read time.
- Queries gigantes sobre milhões de impressões evitarão Eloquent Collections iteráveis, e priorizarão `DB::statement()` brutos, View Postgres materializados caso justificado, e índicações compostas avançadas no RDBMS.
