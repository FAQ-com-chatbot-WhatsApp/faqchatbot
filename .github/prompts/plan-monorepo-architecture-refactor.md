# Plan: Adequação Arquitetura e Documentação Monorepo

Corrigir inconsistências entre documentação (instructions.md) e implementação real (ADRs), simplificar estrutura backend removendo aninhamentos desnecessários, e organizar monorepo mantendo backend Python e frontend Node.js completamente independentes.

## Steps

### 1. Corrigir instructions.md vs ADRs

Atualizar `back/.github/instructions/instructions.md` removendo referências a `domain/entities/` (deletado em ADR-006), remover `domain/dtos/` (não existe), atualizar mapeamento para refletir que Models ORM servem como entities, Schemas servem como DTOs, documentar estrutura real pós-refatoração Janeiro 2026.

**Arquivos afetados:**
- `back/.github/instructions/instructions.md`

**Mudanças necessárias:**
- Remover seção `domain/entities/*.py`
- Remover seção `domain/dtos/*.py`
- Atualizar seção Domain para mencionar apenas `domain/enums.py`
- Adicionar nota: "Models ORM (infra/db/models/) servem como entities"
- Adicionar nota: "Schemas Pydantic servem como DTOs"
- Atualizar exemplo concreto de auth para não mencionar entities

### 2. Simplificar estrutura API backend

Achatar `back/src/robbot/api/v1/` para `back/src/robbot/api/`, mover routers/api.py para routes.py, mover dependencies.py para raiz de api/, atualizar todos os imports em main.py e controllers (estimado 20-30 arquivos), deletar pasta v1/ vazia.

**Estrutura atual:**
```
api/
└── v1/
    ├── routers/
    │   └── api.py
    └── dependencies.py
```

**Estrutura alvo:**
```
api/
├── routes.py        (era routers/api.py)
└── dependencies.py  (movido da v1/)
```

**Arquivos a modificar:**
- `back/src/robbot/main.py` - atualizar import de `robbot.api.v1.routers.api` para `robbot.api.routes`
- Todos os controllers em `back/src/robbot/adapters/controllers/` - verificar se importam algo de api.v1
- `back/src/robbot/api/v1/routers/api.py` - mover para `back/src/robbot/api/routes.py`
- `back/src/robbot/api/v1/dependencies.py` - mover para `back/src/robbot/api/dependencies.py`

**Padrão de atualização de imports:**
```python
# ANTES
from robbot.api.v1.dependencies import get_current_user
from robbot.api.v1.routers.api import api_router

# DEPOIS
from robbot.api.dependencies import get_current_user
from robbot.api.routes import api_router
```

### 3. Reorganizar documentação e scripts

Mover `back/docs/architecture/` para root `docs/architecture/` (decisões são do projeto inteiro), mover `auto-commit.sh` e `auto-commit.ps1` para root `scripts/`, manter back/docs/api/ e back/docs/deployment/ (específicos do backend).

**Movimentações:**
```
back/docs/architecture/          → docs/architecture/
auto-commit.sh                   → scripts/auto-commit.sh
auto-commit.ps1                  → scripts/auto-commit.ps1
```

**Manter no backend:**
```
back/docs/api/                   (Postman collection - específico backend)
back/docs/deployment/            (Railway deploy - específico backend)
back/docs/development/           (Logging guidelines - específico backend)
back/docs/tic/                   (Documentação acadêmica - específico backend)
back/docs/README.md              (Índice docs backend)
```

**Criar novo arquivo:**
- `docs/README.md` - Índice geral da documentação do projeto

### 4. Criar configuração monorepo root

Criar root README.md documentando estrutura (backend Python independente, frontend Node independente), criar root .editorconfig para consistência de código, documentar como rodar cada projeto separadamente, adicionar seção de ADRs e arquitetura.

**Arquivos a criar:**

**README.md (root):**
```markdown
# Clinica Go

Monorepo com backend Python (FastAPI) e frontend Node.js (Next.js) independentes.

## Estrutura

- `back/` - Backend Python (FastAPI + PostgreSQL + Redis)
- `frontend/` - Frontend Next.js (React 19 + Tailwind v4)
- `docs/` - Documentação arquitetural (ADRs)
- `scripts/` - Scripts utilitários do projeto

## Backend

Tecnologias: Python 3.11+, FastAPI, PostgreSQL, Redis, SQLAlchemy, Alembic

Ver: [back/README.md](back/README.md)

## Frontend

Tecnologias: TypeScript, Next.js 16, React 19, Tailwind CSS v4, shadcn/ui

Ver: [frontend/README.md](frontend/README.md)

## Arquitetura

Decisões arquiteturais documentadas em [docs/architecture/decisions/](docs/architecture/decisions/)

## Como Rodar

### Backend
```bash
cd back
docker-compose up
```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

## Estrutura Técnica

- Backend e Frontend são completamente independentes
- Comunicação via HTTP REST API
- Backend expõe API em `/api/v1/`
- OpenAPI spec disponível em `/openapi.json`
```

**.editorconfig (root):**
```ini
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true

[*.{js,jsx,ts,tsx,json,css}]
indent_style = space
indent_size = 2

[*.{py}]
indent_style = space
indent_size = 4

[*.{md,yml,yaml}]
indent_style = space
indent_size = 2
```

### 5. Limpeza backend

Deletar `back/src/robbot/infra/migrations/` (pasta vazia, Alembic em `back/alembic/` já gerencia), validar que `.gitignore` root cobre artifacts de ambos projetos, rodar testes backend para garantir que mudanças de imports não quebraram nada.

**Ações:**
- Deletar diretório `back/src/robbot/infra/migrations/`
- Revisar `.gitignore` root (já está adequado)
- Executar `pytest` em back/ após mudanças de imports

**Comando de validação:**
```bash
cd back
pytest tests/
```

## Further Considerations

1. **Renomear back/ para backend/**: Melhor convenção para monorepo ou manter back/ para compatibilidade com configs existentes (Dockerfile, docker-compose, Railway)?

2. **Atualizar imports**: Mudança de `robbot.api.v1` para `robbot.api` afeta aproximadamente 20-30 arquivos - fazer manualmente ou usar ferramenta de refactoring automatizada?

3. **ADRs novos**: Criar ADR-008 documentando simplificação de api/v1/ e ADR-009 para organização monorepo ou apenas atualizar documentação existente?

4. **Frontend integration**: Manter frontend como design system standalone ou planejar integração futura com backend via HTTP API (OpenAPI spec já existe)?

## Riscos e Mitigações

### Risco: Quebrar imports ao remover api/v1/
- **Mitigação**: Usar grep/search para encontrar todos os imports antes de modificar
- **Validação**: Rodar pytest completo após mudanças

### Risco: Perder histórico git ao mover arquivos
- **Mitigação**: Usar `git mv` ao invés de copiar/deletar
- **Comando**: `git mv back/docs/architecture docs/architecture`

### Risco: Conflito com deployments existentes
- **Mitigação**: Mudanças são internas, API pública não muda
- **Validação**: Verificar que Railway/Docker configs não quebram

## Ordem de Execução

1. Step 1 (instructions.md) - Documentação apenas, zero risco
2. Step 3 (mover docs/scripts) - Apenas movimentação, baixo risco
3. Step 4 (criar configs root) - Criação de novos arquivos, zero risco
4. Step 5 (limpeza) - Deletar pasta vazia, zero risco
5. Step 2 (api/v1) - Maior impacto, fazer por último com testes
