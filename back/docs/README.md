# 📚 Documentação Técnica - Clinica Go Backend

**Projeto:** Sistema de atendimento automatizado com IA para clínica  
**Stack:** FastAPI + PostgreSQL + Redis + Gemini AI + WAHA + LangChain + ChromaDB  
**Última Atualização:** 03/01/2026

---

## 🎯 Visão Geral

Este projeto implementa um bot de WhatsApp com inteligência artificial para atendimento automatizado de clínica médica, incluindo:
- Respostas automáticas com Gemini AI
- Sistema de leads e conversão
- Handoff para atendimento humano
- Analytics e métricas completas
- Autenticação robusta com MFA

---

## 📖 Índice de Documentação

### 🏗️ Arquitetura e Design

| Documento | Descrição | Status |
|-----------|-----------|--------|
| [Arquitetura Técnica](tcc/arquitetura-tecnica.md) | Visão completa da arquitetura, camadas, fluxos e integrações | ✅ Completo |
| [ADRs - Architecture Decision Records](architecture/decisions/) | Decisões arquiteturais importantes documentadas | ✅ 6 ADRs |
| [Roadmap de Desenvolvimento](tcc/roadmap-desenvolvimento.md) | Planejamento, status atual e próximos passos | ✅ Atualizado |
| [Logging Guidelines](development/logging-guidelines.md) | Padrões e boas práticas de logging estruturado | ✅ Completo |

### 🔧 APIs e Integrações

| Documento | Descrição | Status |
|-----------|-----------|--------|
| [Postman Collection](api/postman/WPP_Bot_API.postman_collection.json) | Coleção completa de endpoints para testes | ✅ Completo |
| [Postman Environment](api/postman/WPP_Bot_API.postman_environment.json) | Variáveis de ambiente para dev/prod | ✅ Completo |
| [README API](api/postman/README.md) | Instruções de uso da API | ✅ Completo |

### 🚀 Deployment e Infraestrutura

| Documento | Descrição | Status |
|-----------|-----------|--------|
| [Railway Deployment](deployment/railway.md) | Guia de deploy em produção na Railway | ✅ Completo |
| [Docker Compose](../docker-compose.yml) | Configuração de containers locais | ✅ Completo |
| [Dockerfile](../Dockerfile) | Imagem Docker unificada (API + Worker targets) | ✅ Consolidado |

### 📝 Documentação Acadêmica (TCC)

| Documento | Descrição | Status |
|-----------|-----------|--------|
| [Casos de Teste e Validação](tcc/casos-teste-validacao.md) | Cenários de teste e validação funcional | ✅ Completo |
| [Slides da Apresentação](tcc/slides.md) | Slides para apresentação do TCC | ✅ Completo |

### 🎓 Architecture Decision Records (ADRs)

| ADR | Título | Data | Status |
|-----|--------|------|--------|
| [ADR-001](architecture/decisions/ADR-001-credential-separado-de-user.md) | Credential Separado de User | 03/01/2026 | ✅ Implementado |
| [ADR-002](architecture/decisions/ADR-002-analytics-repository-consolidado.md) | Analytics Repository Consolidado | 03/01/2026 | ✅ Implementado |
| [ADR-003](architecture/decisions/ADR-003-custom-exceptions-hierarquia.md) | Custom Exceptions com Hierarquia | 30/12/2025 | ✅ Implementado |
| [ADR-004](architecture/decisions/ADR-004-clean-architecture-adaptado.md) | Clean Architecture Adaptado | 03/01/2026 | ✅ Implementado |
| [ADR-005](architecture/decisions/ADR-005-quebra-analytics-repository.md) | Quebra do Analytics Repository (God Class) | 03/01/2026 | ✅ Implementado |
analytics-repository-breakdown.md) | Quebra do Analytics Repository (God Class) | 03/01/2026 | ✅ Implementado |
| [ADR-006](architecture/decisions/ADR-006-major-technical-refactoring-2026-01.md) | Docker Optimization e Refatoração Técnica | 05
---

## 🗂️ Estrutura do Projeto

```
back/
├── alembic/                    # Migrations do banco de dados
│   └── versions/               # 22 migrations (19 originais + 3 novas)
├── docs/                       # Documentação completa
│   ├── architecture/           # ADRs e decisões arquiteturais
│   ├── api/postman/            # Coleções Postman
│   ├── deployment/             # Guias de deploy
│   └── tcc/                    # Documentação acadêmica
├── scripts/                    # Scripts utilitários
│   ├── entrypoint.sh           # Entrypoint do container
│   └── wait-for-db.sh          # Aguarda DB estar pronto
├── src/robbot/                 # Código-fonte principal
│   ├── adapters/               # Controllers, Repositories, External APIs
│   ├── api/                    # Routers FastAPI
│   ├── common/                 # Utilitários compartilhados
│   ├── config/                 # Configurações e prompts
│   ├── core/                   # Exceptions, security, logging
│   ├── domain/                 # Entities e Enums de domínio
│   ├── infra/                  # DB Models, Jobs, Redis, VectorDB
│   ├── schemas/                # Pydantic DTOs
│   ├── services/               # Lógica de negócio
│   └── workers/                # Workers RQ
└── tests/                      # Testes automatizados
    ├── integration/            # Testes de integração
    └── unit/                   # Testes unitários
```

---

## 🚀 Quick Start

### 1. Setup Inicial

```bash
# Clonar repositório
cd back/

# Criar ambiente virtual
python -m venv .venv
source .venv/bin/activate  # Linux/Mac
# ou
.venv\Scripts\activate     # Windows

# Instalar dependências
pip install -r requirements.txt
```

### 2. Configurar Ambiente

```bash
# Copiar .env de exemplo
cp .env.example .env

# Editar variáveis necessárias:
# - DATABASE_URL
# - REDIS_URL
# - GEMINI_API_KEY
# - WAHA_API_URL
```

### 3. Executar Migrations

```bash
# Aplicar migrations
alembic upgrade head
```

### 4. Rodar Aplicação

```bash
# Desenvolvimento
uvicorn robbot.main:app --reload --port 8000

# Produção
uvicorn robbot.main:app --host 0.0.0.0 --port 8000 --workers 4
```

### 5. Rodar Workers

```bash
# Em terminal separado
python -m robbot.workers.worker
```

---

## ⚙️ Autoscaling de Workers (RQ)

Parâmetros configuráveis (via `.env` ou `docker-compose.yml`):

- `AUTOSCALER_MIN_WORKERS`: mínimo de workers ativos. Default: `2`.
- `AUTOSCALER_MAX_WORKERS`: máximo de workers. Default: `5`.
- `AUTOSCALER_SCALE_UP_THRESHOLD`: jobs por worker para escalar para cima. Default: `5`.
- `AUTOSCALER_SCALE_DOWN_THRESHOLD`: condição de jobs para reduzir. Default: `0`.
- `AUTOSCALER_IDLE_TIME_THRESHOLD`: tempo ocioso (segundos) para considerar redução. Default: `300`.

Comportamento:

- Mantém sempre `workers >= AUTOSCALER_MIN_WORKERS`.
- Escala para cima quando `jobs/worker > AUTOSCALER_SCALE_UP_THRESHOLD` até `AUTOSCALER_MAX_WORKERS`.
- Reduz apenas se todas as filas sem pendências e todos os workers ociosos, nunca abaixo do mínimo.

Validação rápida:

```bash
# Rebuild (o autoscaler roda na imagem da API)
docker compose build --no-cache api

# Reiniciar autoscaler
docker compose up -d autoscaler

# Checar recomendação/ação
docker compose exec autoscaler python scripts/autoscale_workers.py
docker compose logs --no-log-prefix autoscaler
docker compose ps
```

Arquivos relacionados:

- Compose: `back/docker-compose.yml` (serviço `autoscaler` com variáveis expostas)
- Serviço: `back/src/robbot/services/worker_analytics_service.py` (leitura das variáveis e regras)

---

## 🧪 Testes

### Rodar Todos os Testes

```bash
pytest tests/ -v
```

### Testes por Categoria

```bash
# Unit tests
pytest tests/unit/ -v

# Integration tests
pytest tests/integration/ -v

# Testes de autenticação
pytest tests/unit/services/test_auth_service.py -v
pytest tests/integration/test_mfa_login_flow.py -v
```

### Coverage

```bash
pytest tests/ --cov=src/robbot --cov-report=html
```

---

## 📊 Status Atual do Projeto

**Nota Geral:** 9.0/10 🎯 **EXCELENTE**

### Funcionalidades Implementadas

- ✅ Autenticação completa (JWT + MFA + Sessions)
- ✅ Integração WhatsApp (WAHA)
- ✅ IA Conversacional (Gemini AI + RAG)
- ✅ Sistema de filas (Redis Queue)
- ✅ Dashboard e métricas
- ✅ Handoff para humanos
- ✅ Audit logs completo

### Correções Recentes (03/01/2026)

- ✅ Credential separado de User (violação arquitetural corrigida)
- ✅ Repositórios consolidados em `adapters/`
- ✅ Código ML não usado removido (-200 linhas)
- ✅ `lead_status` normalizado
- ✅ God Class eliminado (528→122 linhas)
- ✅ 5 ADRs documentados
- ✅ Documentação unificada criada

### Pendências

- ⏸️ 36 testes falhando (planejado Sprint 4)
- ⏸️ Migrar MetricsService para repos especializados (Sprint 4)
- ⏸️ Coverage 60% → meta 80%

---

## 🔗 Links Importantes

### Ambientes

- **Dev Local:** http://localhost:8000
- **API Docs (Swagger):** http://localhost:8000/docs
- **Redoc:** http://localhost:8000/redoc
- **Health Check:** http://localhost:8000/api/v1/health

### Ferramentas

- **MailDev (Email):** http://localhost:1080
- **Redis Commander:** http://localhost:8081 (se configurado)

### Repositórios

- **GitHub:** (adicionar URL quando disponível)
- **Railway:** (adicionar URL de produção)

---

## 👥 Time e Contribuição

**Projeto Acadêmico:** Análise e Desenvolvimento de Sistemas  
**Ano:** 2025-2026

### Como Contribuir

1. Ler [ADRs](architecture/decisions/) para entender decisões
2. Seguir estrutura de Clean Architecture
3. Escrever testes para novas features
4. Documentar decisões importantes em novos ADRs

---

## 📞 Suporte

Para dúvidas ou problemas:

1. Consultar [Arquitetura Técnica](tcc/arquitetura-tecnica.md)
2. Revisar [ADRs](architecture/decisions/)
3. Verificar [Issues no GitHub]() (quando disponível)
4. Consultar documentação no código (docstrings)

---

**Última Atualização:** 03/01/2026  
**Versão:** 1.0.0  
**Status:** 🟢 Produção (com melhorias contínuas)
