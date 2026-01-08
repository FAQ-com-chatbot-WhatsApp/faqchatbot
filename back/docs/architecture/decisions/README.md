# Architecture Decision Records (ADRs)

Este diretorio contem as decisoes arquiteturais importantes do projeto **Clinica Go Backend**.

ADRs documentam o **contexto**, **decisao**, **consequencias** e **alternativas** de escolhas tecnicas relevantes.

---

## Indice de ADRs

### Implementados

| ADR | Titulo | Data | Status |
|-----|--------|------|--------|
| [ADR-001](ADR-001-credential-separado-de-user.md) | Credential Separado de User | 03/01/2026 | Aceito |
| [ADR-002](ADR-002-analytics-repository-consolidado.md) | Analytics Repository Consolidado | 03/01/2026 | Aceito |
| [ADR-003](ADR-003-custom-exceptions-hierarquia.md) | Custom Exceptions com Hierarquia | 30/12/2025 | Aceito |
| [ADR-004](ADR-004-clean-architecture-adaptado.md) | Clean Architecture Adaptado | 03/01/2026 | Aceito |
| [ADR-005](ADR-005-analytics-repository-breakdown.md) | Analytics Repository Breakdown | 03/01/2026 | Revertido (ver ADR-007) |
| [ADR-006](ADR-006-major-technical-refactoring-2026-01.md) | Major Technical Refactoring | 13/01/2026 | Aceito |
| [ADR-007](ADR-007-analytics-repository-consolidation.md) | Analytics Repository Consolidation | 18/01/2026 | Aceito |
---

## Template para Novos ADRs

Ao criar um novo ADR, use o seguinte template:

```markdown
# ADR-XXX: [Titulo da Decisao]

**Status:** [Proposto | Aceito | Rejeitado | Substituido | Obsoleto] 
**Data:** DD/MM/YYYY 
**Decisao Por:** [Time/Pessoa] 
**Contexto:** [Situacao que motivou a decisao]

---

## Contexto

[Descricao do problema ou necessidade que levou a decisao]

---

## Decisao

[Descricao clara da decisao tomada]

---

## Consequencias

### Positivas 
[Lista de beneficios]

### Negativas 
[Lista de trade-offs ou custos]

---

## Alternativas Consideradas

### Alternativa 1: [Nome] 
- **Pros:** ...
- **Contras:** ...
- **Decisao:** Rejeitado porque...

---

## Implementacao

[Como a decisao foi implementada, se aplicavel]

---

## Referencias

[Links, artigos, documentacoes relevantes]

---

**Ultima Atualizacao:** DD/MM/YYYY
```

---

## Proximos ADRs Planejados

- **ADR-005:** Estrategia de Cache (Redis)
- **ADR-006:** Vector Database para RAG (ChromaDB)
- **ADR-007:** Background Jobs com RQ vs Celery
- **ADR-008:** Autenticacao MFA (TOTP)
- **ADR-009:** Rate Limiting Strategy

---

## Boas Praticas

### Quando Criar um ADR?

Crie um ADR quando:
- A decisao tem **impacto significativo** na arquitetura
- A decisao e **dificil de reverter** (migracao complexa)
- Ha **multiplas alternativas** razoaveis
- A decisao pode gerar **duvidas futuras** ("por que fizemos assim?")

### Quando NAO Criar um ADR?

Nao crie para:
- Decisoes triviais ou padrao da linguagem
- Escolhas temporarias ou experimentais
- Detalhes de implementacao sem impacto arquitetural

### Manutencao

- **Revisar ADRs** ao menos 1x por trimestre
- **Atualizar status** se decisao foi revertida ou substituida
- **Referenciar ADRs** em Pull Requests de mudancas arquiteturais

---

**Ultima Atualizacao:** 03/01/2026 
**Total de ADRs:** 4
