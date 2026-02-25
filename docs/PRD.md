# Product Requirements Document (PRD) - SaaS SEO

## 1. Visão Geral
O sistema é um SaaS B2B "Enterprise-grade" para gestão de projetos SEO, voltado para profissionais de marketing, agências e donos de sites. A plataforma oferece uma integração oficial via API com o Google Search Console, permitindo a extração automatizada, análise profunda e monitoramento técnico contínuo da saúde SEO dos projetos.

## 2. Objetivos do Produto
- Fornecer insights inteligentes sobre a performance de busca orgânica.
- Classificar e priorizar problemas técnicos de indexação.
- Oferecer uma visão executiva e técnica através de dashboards interativos.
- Criar um sistema monetizável com planos de assinatura (Starter, Pro, Agency).

## 3. Público-Alvo
- Agências de Marketing e SEO.
- Consultores e Especialistas em SEO.
- Gestores de Marketing em Startups e Enterprises.

## 4. Funcionalidades Principais (Core Features)

### 4.1. Gestão Multi-Projeto e Equipes
- Criação e gerenciamento de múltiplos projetos/domínios.
- Definição de país e idioma alvo por projeto.
- Gestão de equipe (convidar membros com diferentes níveis de acesso).

### 4.2. Integração OAuth Google Search Console
- Autenticação OAuth 2.0 segura.
- Listagem e conexão com propriedades do usuário.
- Sincronização automática e agendada de dados em background.

### 4.3. Coleta e Armazenamento Otimizado
- Dados extraídos: cliques, impressões, CTR, posição média, queries, URLs, país, dispositivo, datas.
- Histórico mantido e compactado para até 16 meses.
- Agregação de dados para evitar estouro de armazenamento.

### 4.4. Monitoramento Técnico e Cobertura
- Status de páginas: válidas, excluídas, erro, soft 404, bloqueadas via robots.txt, descobertas/rastreadas e não indexadas.
- Conflitos de canonicals (declarada vs detectada).
- Validação de usabilidade Mobile.

### 4.5. Sistema de Score SEO
Algoritmo de pontuação (0 a 100) baseado em:
- Taxa de indexação (cobertura técnica).
- Volume de erros x URLs com warnings.
- Crescimento percentual de cliques e impressões.
- Estabilidade de posicionamento ao longo do tempo.
- Distribuição de CTR baseada em grupos.

### 4.6. Motor Inteligente de Insights
Serviço autônomo e automatizado que analisa discrepâncias:
- Quedas abruptas em métricas.
- Oportunidades "Low-hanging fruits" (páginas rankeando nas posições 8 a 20).
- Detecção de clusters automáticos de palavras-chave.
- Indícios de canibalização de URLs.

### 4.7. Alertas Automatizados
- Envio de alertas por queda de tráfego, erro abrupto de indexação, sitemap estagnado.
- Canais englobam: App (Dashboard), Email e Webhook.

## 5. Critérios de Sucesso
- Capacidade do backend em processar diariamente lotes de >1 milhão de requisições à API do Google via Filas.
- Baixa latência no acesso ao dashboard, garantindo dados no menor tempo de carregamento possível.
- Zero vazamentos de tokens ou informações cruzadas entre Tenants.

## 6. Marcos do Projeto (Rollout)
- **Fase 1 (Core):** Autenticação, Multi-Tenant, Billing base, Sync Search Console, Monitoramento Técnico, Dashboards básicos, SEO Score.
- **Fase 2 (Scale):** API de Insights, Relatórios em PDF, Webhooks para clientes.
- **Fase 3 (Future):** Integração com GA4, PageSpeed, Google Ads e ferramentas de IA para plano de ação.
