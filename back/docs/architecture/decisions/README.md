# Architecture Decision Records (ADRs)

Este diretório contém as decisões arquiteturais importantes do projeto **Clinica Go Backend**.

ADRs documentam o **contexto**, **decisão**, **consequências** e **alternativas** de escolhas técnicas relevantes.

---

## 📋 Índice de ADRs

### ✅ Implementados

| ADR | Título | Data | Status |
|-----|--------|------|--------|
| [ADR-001](ADR-001-credential-separado-de-user.md) | Credential Separado de User | 03/01/2026 | ✅ Aceito |
| [ADR-002](ADR-002-analytics-repository-consolidado.md) | Analytics Repository Consolidado | 03/01/2026 | ✅ Aceito |
| [ADR-003](ADR-003-custom-exceptions-hierarquia.md) | Custom Exceptions com Hierarquia | 30/12/2025 | ✅ Aceito |
| [ADR-004](ADR-004-clean-architecture-adaptado.md) | Clean Architecture Adaptado | 03/01/2026 | ✅ Aceito |
| [ADR-005](ADR-005-analytics-repository-breakdown.md) | Analytics Repository Breakdown | 03/01/2026 | ⚠️ Revertido (ver ADR-007) |
| [ADR-006](ADR-006-major-technical-refactoring-2026-01.md) | Major Technical Refactoring | 13/01/2026 | ✅ Aceito |
| [ADR-007](ADR-007-analytics-repository-consolidation.md) | Analytics Repository Consolidation | 18/01/2026 | ✅ Aceito |
---

## 📝 Template para Novos ADRs

Ao criar um novo ADR, use o seguinte template:

```markdown
# ADR-XXX: [Título da Decisão]

**Status:** [Proposto | Aceito | Rejeitado | Substituído | Obsoleto]  
**Data:** DD/MM/YYYY  
**Decisão Por:** [Time/Pessoa]  
**Contexto:** [Situação que motivou a decisão]

---

## Contexto

[Descrição do problema ou necessidade que levou à decisão]

---

## Decisão

[Descrição clara da decisão tomada]

---

## Consequências

### Positivas ✅
[Lista de benefícios]

### Negativas ❌
[Lista de trade-offs ou custos]

---

## Alternativas Consideradas

### Alternativa 1: [Nome] ❌
- **Prós:** ...
- **Contras:** ...
- **Decisão:** Rejeitado porque...

---

## Implementação

[Como a decisão foi implementada, se aplicável]

---

## Referências

[Links, artigos, documentações relevantes]

---

**Última Atualização:** DD/MM/YYYY
```

---

## 🎯 Próximos ADRs Planejados

- [ ] **ADR-005:** Estratégia de Cache (Redis)
- [ ] **ADR-006:** Vector Database para RAG (ChromaDB)
- [ ] **ADR-007:** Background Jobs com RQ vs Celery
- [ ] **ADR-008:** Autenticação MFA (TOTP)
- [ ] **ADR-009:** Rate Limiting Strategy

---

## 📚 Boas Práticas

### Quando Criar um ADR?

Crie um ADR quando:
- ✅ A decisão tem **impacto significativo** na arquitetura
- ✅ A decisão é **difícil de reverter** (migração complexa)
- ✅ Há **múltiplas alternativas** razoáveis
- ✅ A decisão pode gerar **dúvidas futuras** ("por que fizemos assim?")

### Quando NÃO Criar um ADR?

Não crie para:
- ❌ Decisões triviais ou padrão da linguagem
- ❌ Escolhas temporárias ou experimentais
- ❌ Detalhes de implementação sem impacto arquitetural

### Manutenção

- **Revisar ADRs** ao menos 1x por trimestre
- **Atualizar status** se decisão foi revertida ou substituída
- **Referenciar ADRs** em Pull Requests de mudanças arquiteturais

---

**Última Atualização:** 03/01/2026  
**Total de ADRs:** 4
