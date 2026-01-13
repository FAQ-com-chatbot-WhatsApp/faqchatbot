---
title: Monorepo Architecture and Documentation Refactor
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Monorepo Architecture and Documentation Refactor

Fix inconsistencies between documentation (instructions.md) and actual implementation (ADRs), simplify backend structure by removing unnecessary nesting, and organize monorepo while keeping Python backend and Node.js frontend completely independent.

## Architecture and design

The Clinica Go project follows Adapted Clean Architecture (ADR-004) with Python/FastAPI backend and Node.js/Next.js frontend completely independent. This plan aims to:

1. **Align documentation with actual implementation** - Remove references to `domain/entities/` and `domain/dtos/` that were consolidated per ADR-006
2. **Simplify API structure** - Remove unnecessary nesting `api/v1/routers/` to `api/routes.py`
3. **Organize monorepo** - Centralize architectural documentation at root, keep specific docs in each subproject
4. **Standardize tooling** - Add `.editorconfig` and root README.md for developers

**Current → Target API Structure:**
```
api/v1/routers/api.py         →  api/routes.py
api/v1/dependencies.py        →  api/dependencies.py
```

**Risks:**
- Breaking ~20-30 imports in controllers when simplifying API - mitigate with grep + tests
- Losing git history - mitigate with `git mv`
- Deployment conflicts - internal changes only, public API unchanged

## Tasks

### Phase 1: Documentation (low risk)
- [ ] Update `back/.github/instructions/instructions.md` removing references to `domain/entities/` and `domain/dtos/`
- [ ] Document that ORM Models serve as entities and Pydantic Schemas as DTOs
- [ ] Update auth example to reflect actual structure

### Phase 2: File reorganization (low risk)
- [ ] Move `back/docs/architecture/` → `docs/architecture/` using `git mv`
- [ ] Move `auto-commit.sh` and `auto-commit.ps1` → `scripts/` using `git mv`
- [ ] Create `docs/README.md` with general documentation index
- [ ] Keep `back/docs/{api,deployment,development,tic}` (backend-specific)

### Phase 3: Monorepo configuration (zero risk)
- [ ] Create root `README.md` documenting project structure
- [ ] Create root `.editorconfig` with standards (Python indent=4, JS/TS indent=2)
- [ ] Document commands to run backend (`docker-compose up`) and frontend (`npm run dev`)

### Phase 4: Cleanup (zero risk)
- [ ] Delete `back/src/robbot/infra/migrations/` (empty folder)
- [ ] Validate root `.gitignore` covers Python and Node.js artifacts

### Phase 5: API simplification (high impact - execute last)
- [ ] Search all `robbot.api.v1` imports using `grep -r "robbot.api.v1" back/src/`
- [ ] Move `api/v1/routers/api.py` → `api/routes.py` with `git mv`
- [ ] Move `api/v1/dependencies.py` → `api/dependencies.py` with `git mv`
- [ ] Update import in `back/src/robbot/main.py`
- [ ] Update imports in all controllers (~20-30 files)
- [ ] Delete empty `back/src/robbot/api/v1/` folder
- [ ] Run `cd back && pytest tests/` for validation

## Open questions

1. **Rename back/ to backend/?** Better monorepo convention or keep `back/` for compatibility with existing configs (Dockerfile, docker-compose.yml, Railway deployments)?

2. **Automated refactoring tool?** The change from `robbot.api.v1` to `robbot.api` affects ~20-30 files - use manual find/replace or automated tool like `rope` or `bowler`?

3. **Document via ADR?** Create ADR-008 (api/v1/ simplification) and ADR-009 (monorepo organization) or just update existing documentation?
