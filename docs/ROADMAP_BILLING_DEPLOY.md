# Roadmap, Billing and Deploy Strategy

## 1. Estrutura de Billing (SaaS Monetization)
A arquitetura de faturamento utilizará um Gateway global robusto: Preferencialmente **Stripe** (via plugin Laravel Cashier) com suporte a upgrades/downgrades pro-rata.

### Camadas de Assinatura
O Middleware de Aplicação `CheckSubscriptionLimits` será encarregado de intervir quando o Customer tentar exceder seus limites antes do banco de dados ser estocado inutilmente.

1. **Plano Starter:**
   - Preço acessível (Ex ~$29/mês).
   - Máximo de 1 Projeto (1 GSC Property).
   - Retenção do Histórico (GSC Window): 3 a 6 meses.
   - Updates: A cada 3 dias na coleta; Insights Básicos ativos.

2. **Plano Pro (Destino de Agências menores / Sites grandes):**
   - Preço Middle (Ex ~$99/mês).
   - Até 5 a 10 Projetos.
   - Histórico Integral (Últimos 16 meses).
   - Inteligência de SEO (Insights de "Quick Wins", clustering).
   - Relatórios semanais gerados em PDF (White-Label parcial).

3. **Plano Agency (Empresas, Consultorias):**
   - Alto ticket (Ex ~$299+/mês).
   - 50+ Projetos ou ilimitados baseados num hard limit de M pageviews/clicks combinados.
   - Multi-assentos para equipe (ACLs granulares com 5 roles base).
   - Relatórios White-label Totais.

## 2. Estratégia de Deploy (CI/CD DevOps)
Deploy desenhado com processos em container garantindo agilidade entre os ambientes.
1. **Developer Branch Push:** Abre o P.R gerando hooks no Github Actions.
2. **Github Actions (Integração Contínua):**
   - Syntax Checking (PHP_CodeSniffer), Type Analysis (Larastan level max).
   - Bateria inteira de Testes PEST (In-memory DB testing) simulando faturas e respostas assíncronas do Google mockado.
3. **Build Target (Docker):**
   - Construir imagem da API baseada em `php:8.3-cli-alpine` multi-stage contendo a extensão do Swoole, PCNTL.
4. **Continuous Deployment (CD):**
   - Imagens viajam para registry (AWS ECR/Docker Hub).
   - Orquestrador (VPS Docker Compose, Docker Swarm ou Kubernetes) puxa imagem com tag nova.
   - Migrations ativadas `artisan migrate --force`.
   - Reload graceful do `supervisord` que gerencia o Horizon (para não dar `kill -9` no meio do payload GSC massivo), e reload do Octane para API `SIGUSR1`. Sem downtime aos usuários finais.

## 3. Roadmap Futuro & Evolution (Fase 2)
### Integrações Horizontais
1. **Google Analytics (GA4) API:**
   - Cruzar nativamente na view Dados de Search Orgânico com Sessões geradas, Engajamento médio e Receita/Leads do evento ativado, revelando ROI literal das palavras-chave descobertas e agrupadas via API.
2. **Google PageSpeed Insights (CruX API):**
   - Consultas Semanais agendadas injetadas para extrair métricas de campo do LCP/CLS/INP na interface, correlacionando gargalos lentos na subida da tabela `performance_data` com quedas bruscas de ranqueamento da tabela do GSC.

### Tecnologias de Valor Bruto (Inteligência)
3. **IA Action Plan (LLMs Contextualizados RAG):**
   - Os scores técnicos e de desempenho gerados viram *prompts/contexto RAG* passados em um model gpt-4o / claude 3 backend.
   - O SaaS gera semanalmente o Relatório Prescritivo no Dashboard: "Sua tag ecom_shoes perdeu 34% dos cliques. Recomendamos priorizar a correção canônica da `/loja/nike` e injetar cluster links vindos da query `tenis de corrida x`".
4. **Crawler Focado (Spider Proprietário Python/Go):**
   - Usar um micro-service Crawler para varrer periodicamente as N páginas primordiais do client, validando o título, rel=canonical, schema tags in-loco (ao invés do delay de cobertura do GSC de 4 dias), retroalimentando um Score Técnico em tempo "Semi-real".
