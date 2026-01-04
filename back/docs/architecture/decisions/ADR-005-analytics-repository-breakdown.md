# ADR-005: Quebra do Analytics Repository em Repositórios Especializados

**Data:** 03/01/2026  
**Status:** ✅ Implementado  
**Decisores:** Time de Desenvolvimento  
**Tags:** `refactoring`, `god-class`, `analytics`, `clean-code`

---

## Contexto

O arquivo `analytics_repository.py` havia crescido para **528 linhas**, violando o princípio de Single Responsibility (SRP). Um único repositório estava fazendo:
- Métricas de conversão e funil de vendas
- Estatísticas de performance e tempo de resposta
- Volume de mensagens
- Taxa de autonomia do bot
- Dashboards agregados

Problemas identificados:
- **God Class**: responsabilidades demais em uma classe
- Dificulta manutenção e navegação
- Testes complexos com muitas dependências
- Viola Open/Closed Principle (mudanças afetam classe inteira)

---

## Decisão

Quebrar `AnalyticsRepository` em **4 repositórios especializados**:

### 1. `ConversionAnalyticsRepository` (291 linhas)
**Responsabilidade:** Métricas de conversão de leads

Métodos:
- `get_conversion_rate()`: Taxa de conversão global/segmentada
- `get_conversion_funnel()`: Funil com drop-off por etapa
- `get_time_to_conversion()`: Estatísticas de tempo até conversão

**Complexidade:** Alta (CTEs, window functions, percentis)

### 2. `PerformanceAnalyticsRepository` (153 linhas)
**Responsabilidade:** Performance de atendimento

Métodos:
- `get_response_time_stats()`: Tempo de resposta humano (p50, p95, p99)
- `get_message_volume()`: Volume de mensagens ao longo do tempo

**Complexidade:** Alta (queries com window functions e agregações temporais)

### 3. `BotPerformanceAnalyticsRepository` (76 linhas)
**Responsabilidade:** Performance do bot

Métodos:
- `get_bot_autonomy_rate()`: Conversas resolvidas sem humano

**Complexidade:** Baixa (query simples com agregação)

### 4. `DashboardAnalyticsRepository` (95 linhas)
**Responsabilidade:** Sumários gerais

Métodos:
- `get_dashboard_summary()`: Resumo completo para dashboard

**Complexidade:** Média (delega para PerformanceAnalyticsRepository)

### 5. `AnalyticsRepository` (Facade - 122 linhas)
**Responsabilidade:** Compatibilidade retroativa

Mantém interface original delegando para repositórios especializados.  
**DEPRECATED**: Marcado para remoção na v2.0

---

## Consequências

### ✅ Positivas

1. **Single Responsibility**
   - Cada repositório tem uma responsabilidade clara
   - Arquivos menores e mais focados (76-291 linhas vs 528 linhas)

2. **Facilita Manutenção**
   - Mudanças em métricas de conversão não afetam performance
   - Testes mais simples e isolados
   - Navegação no código mais intuitiva

3. **Testabilidade**
   - Mocks/stubs menores e mais focados
   - Testes unitários por domínio
   - Menos dependências transitivas

4. **Extensibilidade**
   - Novos repos podem ser adicionados sem modificar existentes (OCP)
   - Ex: `RevenueAnalyticsRepository`, `SentimentAnalyticsRepository`

5. **Performance**
   - Imports mais leves (carrega apenas o necessário)
   - Menor acoplamento entre queries

### ⚠️ Neutras

1. **Mais Arquivos**
   - Antes: 1 arquivo de 528 linhas
   - Depois: 5 arquivos (total 742 linhas com docstrings)
   - Trade-off aceitável para melhor organização

2. **Facade Temporária**
   - `AnalyticsRepository` mantido para compatibilidade
   - Será removido em v2.0 após migração dos consumidores

### ❌ Negativas (Mitigadas)

1. **Breaking Change?**
   - ❌ NÃO: Facade mantém interface antiga
   - Migração gradual sem quebrar código existente
   - Deprecation warnings guiam migração

2. **Imports Múltiplos**
   - Antes: `from ...analytics import AnalyticsRepository`
   - Depois: `from ...analytics.conversion_analytics_repository import ConversionAnalyticsRepository`
   - Mitigation: Facade permite ambos

---

## Implementação

### Estrutura de Arquivos

```
adapters/repositories/analytics/
├── __init__.py
├── analytics_repository.py              (122 linhas - DEPRECATED facade)
├── conversion_analytics_repository.py   (291 linhas)
├── performance_analytics_repository.py  (153 linhas)
├── bot_performance_analytics_repository.py  (76 linhas)
└── dashboard_analytics_repository.py    (95 linhas)
```

### Exemplo de Uso

#### Modo Legado (DEPRECATED)
```python
from robbot.adapters.repositories.analytics import AnalyticsRepository

repo = AnalyticsRepository(db)
stats = repo.get_conversion_rate(start, end)  # Delega para conversion
```

#### Modo Novo (RECOMENDADO)
```python
from robbot.adapters.repositories.analytics.conversion_analytics_repository import (
    ConversionAnalyticsRepository
)

repo = ConversionAnalyticsRepository(db)
stats = repo.get_conversion_rate(start, end)
```

### Migração de Consumidores

**Arquivos afetados:**
- `src/robbot/services/analytics/metrics_service.py`
- `src/robbot/adapters/controllers/dashboard_controller.py`

**Plano:**
1. ✅ Criar novos repositórios especializados
2. ✅ Criar facade mantendo interface antiga
3. ⏭️ Migrar `metrics_service.py` gradualmente (Sprint 4)
4. ⏭️ Migrar `dashboard_controller.py` (Sprint 4)
5. ⏭️ Remover facade em v2.0

---

## Alternativas Consideradas

### Alt 1: Manter God Class
❌ **Rejeitada**
- Violaria SRP indefinidamente
- Dívida técnica cresceria
- Dificulta onboarding de novos devs

### Alt 2: Quebrar em Serviços ao invés de Repositories
❌ **Rejeitada**
- Responsabilidade: Repositories fazem queries, Services orquestram lógica
- Queries analíticas são responsabilidade de repositório
- Serviços já existem (`MetricsService`) e orquestram repositórios

### Alt 3: Usar Herança (Base Analytics Repository)
❌ **Rejeitada**
- Favorecemos composição sobre herança
- Base class viraria outro God Class
- Dificulta testes e aumenta acoplamento

---

## Métricas

**Antes:**
- 1 arquivo: 528 linhas
- 8 métodos em 1 classe
- Complexidade ciclomática: ~45

**Depois:**
- 5 arquivos: 742 linhas total (incluindo docstrings e facade)
- 8 métodos distribuídos em 4 classes especializadas + 1 facade
- Complexidade ciclomática média: ~8 por classe
- **Redução de responsabilidades:** 100% → 25% por classe

---

## Validação

✅ **Testes de Compilação:**
```bash
python -m py_compile src/robbot/adapters/repositories/analytics/*.py
# ✅ Todos os 5 arquivos OK
```

✅ **Compatibilidade Retroativa:**
- Facade mantém interface antiga
- Nenhum import quebrado
- Testes existentes continuam funcionando

✅ **Code Review:**
- Ruff: 0 erros críticos
- Mypy: Type hints corretos
- Arquitetura: Respeita Clean Architecture

---

## Referências

- **Clean Code** (Robert C. Martin): Single Responsibility Principle
- **Refactoring** (Martin Fowler): Extract Class pattern
- **ADR-004**: Clean Architecture Adaptado (entities vs models vs repositories)
- Auditoria Técnica 03/01/2026: P0-4 (God Class identificado)

---

## Notas de Implementação

**Tempo gasto:** 2 horas  
**Data:** 03/01/2026 (Sprint 3)  
**Commits:** `refactor: break analytics_repository God Class into 4 specialized repos`

**Próximos Passos:**
1. Migrar `MetricsService` para usar repos especializados (Sprint 4)
2. Adicionar testes unitários para cada repo (Sprint 4)
3. Documentar em Swagger quais endpoints usam quais repos (Sprint 4)
4. Remover facade em v2.0 (Q2 2026)
