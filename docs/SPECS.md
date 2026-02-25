# Technical Specifications (SPECS)

## 1. Stack Tecnológica
- **Backend:** Laravel 12, PHP 8.3+
- **Servidor:** Laravel Octane (Swoole ou RoadRunner) para máxima performance.
- **Database:** PostgreSQL (padrão principal), Redis (para filas de jobs, cache e Rate Limiting).
- **Processamento Assíncrono:** Laravel Horizon via Redis.
- **Autenticação:** Laravel Sanctum (SPA authentication). `spatie/laravel-permission` para ACL e `spatie/laravel-multitenancy` para isolamento Tenant.
- **Integração OAuth:** Laravel Socialite extendido para Google.

## 2. Estrutura de Pastas (Enterprise Pattern)
O sistema não utilizará a estrutura básica do Laravel. A camada de negócios (Domain Driven Design leve) será centralizada.

```text
app/
 ├── Actions/           # Ações de negócio single-responsibility
 ├── DTOs/              # Data Transfer Objects
 ├── Enums/             # Enums do PHP 8.1+ para status, limites
 ├── Exceptions/        # Exceções customizadas estruturadas
 ├── Http/
 │    ├── Controllers/  # Mapeia requests para Services/Actions
 │    ├── Middleware/   # Middlewares e checagem de tenant/billing
 │    ├── Requests/     # Form requests rígidos
 │    └── Resources/    # API Resources e Data Wrappers
 ├── Jobs/              # Processamento de syncs no Horizon
 ├── Models/            # Modelos do Eloquent
 ├── Repositories/      # Isolamento das lógicas de queries SQL pesadas
 ├── Services/
 │    ├── Google/       # Serviços da API GSC
 │    ├── SEO/          # Lógicas de cálculo de Score e Ranking
 │    ├── Billing/      # Stripe/MercadoPago logic
 │    └── Insights/     # Motor de regras para oportunidades e Alertas
 ├── Traits/
 └── ValueObjects/      # Representação de valores imutáveis
```

## 3. Padrões de Design e Código
- **API First:** O Laravel proverá inteiramente uma API REST JSON. O backend não processa views de front.
- **Single Responsibility e Fat Services:** Os Controllers devem ter no máximo 4-5 linhas de código, relegando a complexidade pros "Services" ou "Actions".
- **Repository Pattern:** Consultas agregadas do GSC que batem no PostgreSQL devem ficar encapsuladas.
- **DTOs Typesafe:** As devoluções da interface de API do GSC e entradas passarão por DTOs, blindando a mudança de interfaces.

## 4. Assincronismo e Filas (Queue Management)
Nenhuma requisição lenta acontece via HTTP em tempo real (Wait for Google API). Tudo usará a fila `horizon` rodando via Redis.
- **SyncProjectDataJob:** Despacha processos menores para drivers.
- **FetchSearchAnalyticsJob:** Consulta e insere cliques, impressões GSC.
- **FetchUrlInspectionJob:** Dispara inspeção on-demand.

## 5. Estrutura de Testes Automatizados
Código focado em cobertura contínua e CI garantido (GitHub Actions).
- `Feature Tests:` (Pest PHP ou PHPUnit) -> Bateria p/ validar endpoints de CRUD, tenant security e rotinas billing.
- `Unit Tests:` Foco nas classes geradoras do SEO Score, Insights Engine, DTO parsers e Form Requests.
- `Mocks:` A API do Google Search Console e Payment Gateways deverão ser 100% Mockadas nos testes usando `Http::fake()`.
- `Seeders/Factories:` Usados obrigatoriamente para fornecer ambientes instantâneos populadores em massa de métricas de SEO para testes de carga e dev local.
