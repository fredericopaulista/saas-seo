# Database Modeling (PostgreSQL)

## 1. Estrutura do Banco Relacional
A modelagem é projetada para o padrão **Single Database com Tenant Isolation**. O banco ideal é PostgreSQL 15+ para melhor suporte nativo a JSONB e partições.

### 1.1 Tabelas Globais (SaaS Management)
Nestas tabelas não existe o conceito de isolamento, controlam o SaaS em si.
- **`tenants`**: `id`, `name`, `domain`, `created_at`
- **`users`**: `id`, `name`, `email`, `password`, `created_at`
- **`tenant_user`**: Tabela Pivot para acesso de múltiplos usuários ao tenant associado (Role based via Spatie).
- **`subscriptions`**: `id`, `tenant_id`, `stripe_id`, `plan_id`, `status` (active/past_due/canceled), `ends_at`
- **`plans`**: `id`, `name`, `slug`, `stripe_price_id`, `features_json` (JSON definindo limits p/ queries rápidas)

### 1.2 Tabelas Isoladas (App Data)
**Regra Ouro:** Obrigatoriamente possuem `tenant_id` e usam Global Scope no Eloquent, ou Row-Level Security (RLS) no PostgreSQL.
- **`projects`**: `id`, `tenant_id`, `name`, `domain`, `country`, `language`, `gsc_property_url`, `created_at`
- **`search_console_tokens`**: `id`, `project_id`, `access_token` (crypt), `refresh_token` (crypt), `expires_in`
- **`performance_data`**: (Tabela de alto volume/Big Data do sistema)
  - `id`
  - `project_id`
  - `date` (DATE)
  - `page` (VARCHAR 2048)
  - `query` (VARCHAR)
  - `clicks` (INT)
  - `impressions` (INT)
  - `ctr` (FLOAT)
  - `position` (FLOAT)
  - `device` (VARCHAR)
  - `country` (VARCHAR)
- **`url_status`** (Monitoramento Técnico Diario):
  - `id`, `project_id`, `url`, `coverage_status` (Indexado), `index_status`, `canonical_declared`, `canonical_google`, `mobile_usable`, `last_crawled`
- **`seo_scores`** (Histórico de pontuação):
  - `id`, `project_id`, `score`, `technical_score`, `performance_score`, `index_score`, `created_at`
- **`insights`** (Alertas e Oportunidades criadas pela Engine):
  - `id`, `project_id`, `type` (enum), `severity` (INFO, WARNING, CRITICAL), `title`, `description`, `metadata` (jsonb), `created_at`, `resolved_at`

## 2. Índices e Otimização
Devido à tabela `performance_data` acumular dezenas de milhões de linhas, usaremos:
- **Índice Composto BTREE:** `(project_id, date)` para a imensa maioria dos agrupamentos das Dashboards.
- **Partitioning Declarativo PostgreSQL:** Por RANGE de `date` mensal caso a volumetria exija retenção longa sem degradar queries curtas (Fase Scale).

## 3. Retenção de Dados
- Planos menores (Ex: Starter) terão um cron mensal executando limpeza em cascata nas rows de `performance_data` mais velhas do que 3 ou 6 meses, visando segurar custo de Database storage. Planos maiores mantêm os 16 meses (Limites API do Google).
