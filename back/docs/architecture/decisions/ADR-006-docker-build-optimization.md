# ADR-006: Docker Build Optimization

**Status:** Proposto  
**Data:** 06/01/2026  
**Autores:** Equipe Backend  

## Contexto

O tempo de build do Docker está inaceitável para desenvolvimento:
- **Build atual:** 25 minutos (1509s)
- **Impacto:** Desenvolvimento lento, iterações demoradas
- **Problema crítico:** Durante build anterior, disco ficou cheio e Docker Desktop crashou

### Análise de Tempo (Build Atual)

| Etapa | Tempo | % Total | Gargalo |
|-------|-------|---------|---------|
| Dependencies stage (UV install) | 304s (5min) | 20% | ML libraries |
| Runtime COPY site-packages | 282s (4.7min) | 18% | Tamanho total |
| **Exporting layers** | **540s (9min)** | **36%** | **MAIOR GARGALO** |
| **Unpacking to daemon** | **333s (5.5min)** | **22%** | **SEGUNDO MAIOR** |
| Build essentials | 45s | 3% | OK |
| Outros | 5s | 1% | OK |
| **TOTAL** | **1509s (25min)** | **100%** | |

### Causadores do Problema

1. **ML Dependencies Gigantes:**
   - `torch`: 858MB (CPU+CUDA)
   - `transformers`: ~500MB
   - `faster-whisper`: ~300MB
   - **Total ML:** ~1.7GB de 3GB totais

2. **Exporting/Unpacking Lento:**
   - Windows (Docker Desktop) tem overhead significativo
   - Layers grandes demoram para serem escritas no daemon
   - Sem paralelização de stages

3. **Cache Não Otimizado:**
   - Dependências leves e pesadas juntas
   - Rebuild completo quando pyproject.toml muda
   - Sem cache mounts BuildKit

## Decisão

Implementar otimizações em 4 frentes:

### 1. PyTorch CPU-Only (858MB → ~200MB)

**Trade-off:** Perde capacidade GPU, mas:
- ✅ Aplicação não usa GPU em produção (Railway não tem GPU)
- ✅ Vision service (BLIP) funciona em CPU
- ✅ Redução de 76% no tamanho do torch

```dockerfile
RUN uv pip install --system \
    torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cpu
```

### 2. BuildKit Cache Mounts

Aproveita cache de downloads entre builds:

```dockerfile
# syntax=docker/dockerfile:1.4
RUN --mount=type=cache,target=/root/.cache/uv \
    --mount=type=cache,target=/root/.cache/pip \
    uv pip install --system -e .
```

**Benefício:** Downloads não são refeitos, apenas descompactados.

### 3. Separação de Dependências (Parallel Stages)

```dockerfile
# Stage 1: Light dependencies (rápido)
FROM python:3.11-slim AS dependencies-light
RUN uv pip install <deps sem ML>

# Stage 2: ML dependencies (pesado, mas paralelo)
FROM python:3.11-slim AS dependencies-ml
RUN uv pip install torch transformers faster-whisper

# Stage 3: Runtime
FROM python:3.11-slim
COPY --from=dependencies-light ...
COPY --from=dependencies-ml ...
```

**Benefício:** BuildKit pode construir stages 1 e 2 em paralelo.

### 4. .dockerignore Otimizado

Já implementado, mas validar:
```
**/__pycache__
**/*.pyc
.pytest_cache/
.mypy_cache/
.coverage
htmlcov/
.venv/
```

## Consequências

### Positivas

- ✅ **Build time:** 25min → 8-10min (60-70% mais rápido)
- ✅ **Image size:** ~3GB → 1.5-2GB (33-50% menor)
- ✅ **Cache hit rate:** 30% → 70% (melhor aproveitamento)
- ✅ **Disco:** Menos problemas de espaço
- ✅ **Deploy:** Railway builds mais rápidos

### Negativas

- ❌ **GPU support:** Removido (aceitável para nosso caso)
- ⚠️ **Complexity:** Dockerfile mais complexo (stages separados)
- ⚠️ **Windows:** Cache mounts podem ter overhead no Docker Desktop

### Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Vision service quebra sem GPU | Baixa | Alto | Testar endpoint de image description |
| Cache mounts não funcionam no Windows | Média | Baixo | Degradação graciosa (sem cache) |
| Stages paralelos falham | Baixa | Médio | Fallback para Dockerfile original |

## Implementação

### Fase 1: Validação (30min)
1. ✅ Criar `Dockerfile.optimized`
2. ⏭️ Build test: `docker build -f Dockerfile.optimized -t tic-api:optimized .`
3. ⏭️ Medir tempo de build
4. ⏭️ Testar vision service: `POST /api/v1/vision/describe`

### Fase 2: Rollout (15min)
1. Se validação OK: `mv Dockerfile Dockerfile.old && mv Dockerfile.optimized Dockerfile`
2. Rebuild: `docker compose build`
3. Deploy: `docker compose up -d --force-recreate`

### Fase 3: Documentação (15min)
1. Atualizar README.md com build otimizado
2. Adicionar troubleshooting guide
3. Atualizar Railway deployment docs

## Métricas de Sucesso

| Métrica | Antes | Meta | Crítico |
|---------|-------|------|---------|
| Build Time (clean) | 25min | 8-10min | < 15min |
| Build Time (cached) | 56s | 30-45s | < 2min |
| Image Size | ~3GB | 1.5-2GB | < 2.5GB |
| PyTorch Size | 858MB | ~200MB | < 500MB |
| Containers Healthy | 7/8 | 8/8 | 8/8 |

## Rollback Plan

Se houver problemas:
```bash
# Restaurar Dockerfile original
mv Dockerfile.old Dockerfile

# Rebuild
docker compose build --no-cache

# Recreate
docker compose up -d --force-recreate
```

## Referências

- [Docker BuildKit Cache Mounts](https://docs.docker.com/build/cache/)
- [PyTorch CPU-only Install](https://pytorch.org/get-started/locally/)
- [Multi-stage Build Best Practices](https://docs.docker.com/build/building/multi-stage/)

## Notas Adicionais

**Alternativa considerada mas rejeitada:**
- ❌ Usar apenas chromadb sem transformers: Vision service é funcionalidade importante
- ❌ Dividir em 2 images (api + ml): Complexidade operacional não justifica ganho marginal
- ❌ GPU support opcional: Overhead de manter 2 Dockerfiles não compensa

**Decisão final:** Torch CPU-only + BuildKit caching + parallel stages é a melhor relação custo/benefício.
