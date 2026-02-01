---
status: filled
generated: 2026-02-01
agents:
  - type: "code-reviewer"
    role: "Review code changes for quality, style, and best practices"
  - type: "bug-fixer"
    role: "Analyze bug reports and error messages"
  - type: "feature-developer"
    role: "Implement new features according to specifications"
  - type: "refactoring-specialist"
    role: "Identify code smells and improvement opportunities"
  - type: "test-writer"
    role: "Write comprehensive unit and integration tests"
  - type: "documentation-writer"
    role: "Create clear, comprehensive documentation"
  - type: "performance-optimizer"
    role: "Identify performance bottlenecks"
  - type: "security-auditor"
    role: "Identify security vulnerabilities"
  - type: "backend-specialist"
    role: "Design and implement server-side architecture"
  - type: "frontend-specialist"
    role: "Design and implement user interfaces"
  - type: "architect-specialist"
    role: "Design overall system architecture and patterns"
  - type: "devops-specialist"
    role: "Design and maintain CI/CD pipelines"
  - type: "database-specialist"
    role: "Design and optimize database schemas"
  - type: "mobile-specialist"
    role: "Develop native and cross-platform mobile applications"
docs:
  - "project-overview.md"
  - "architecture.md"
  - "development-workflow.md"
  - "testing-strategy.md"
  - "glossary.md"
  - "data-flow.md"
  - "security.md"
  - "tooling.md"
phases:
  - id: "phase-1"
    name: "Discovery & Alignment"
    prevc: "P"
  - id: "phase-2"
    name: "Implementation & Iteration"
    prevc: "E"
  - id: "phase-3"
    name: "Validation & Handoff"
    prevc: "V"
---

# Implementar Polling de Mensagens WAHA Plan

> Substituir dependência de webhooks não-funcionais do WEBJS por sistema de polling robusto baseado na API oficial do WAHA para garantir recepção de todas as mensagens

## Task Snapshot
- **Primary goal:** Implementar polling para mensagens do WAHA, evitando o endpoint `/chats` quebrado no WEBJS e garantindo captura e processamento das mensagens do `DEV_PHONE_NUMBER`.
- **Success signal:** Mensagens enviadas do `DEV_PHONE_NUMBER` aparecem no polling e são processadas pelo pipeline de mensagens, com latência máxima de 10 segundos.

## Current Status
- Polling ativo com endpoint `/chats/{chatId}/messages` em DEV_MODE.
- Mensagens do `DEV_PHONE_NUMBER` são enfileiradas e processadas (Gemini inicializa, conversa registrada).
- WAHA mock desativado em DEV_MODE via `WAHA_MOCK_REQUESTS=false`.
- Idempotência adicionada no polling via Redis (`waha:processed:{message_id}` com TTL).
- Resposta outbound ainda precisa ser validada/envio confirmado.
- **Key references:**
  - [WAHA Official Documentation](https://waha.devlike.pro/docs/)
  - [WAHA Swagger API](https://waha.devlike.pro/swagger/)
  - [Data Flow Documentation](../docs/data-flow.md)
  - [Architecture Notes](../docs/architecture.md)

## Codebase Context
- **Total files analyzed:** 304
- **Total symbols discovered:** 985
- **Architecture layers:** Config, Components, Services, Utils, Controllers, Models, Repositories
- **Detected patterns:** Singleton, Repository, Service Layer, Builder

### Key Components
**Core Classes:**
- `TestDIInControllers` — D:\_projects\clinica_go\back\tests\unit\test_di_controllers.py:59
- `TestDIErrorHandling` — D:\_projects\clinica_go\back\tests\unit\test_di_controllers.py:137
- `TestControllerIntegration` — D:\_projects\clinica_go\back\tests\unit\test_di_controllers.py:164
- `TestDIContainerInitialization` — D:\_projects\clinica_go\back\tests\unit\test_di_container.py:43
- `TestDIContainerDependencies` — D:\_projects\clinica_go\back\tests\unit\test_di_container.py:67

**Key Interfaces:**
- `NavItem` — D:\_projects\clinica_go\frontend\src\app\styleguide\navigation.ts:1
- `NavSection` — D:\_projects\clinica_go\frontend\src\app\styleguide\navigation.ts:6
- `LabelProps` — D:\_projects\clinica_go\frontend\src\components\ui\label.tsx:6
## Agent Lineup
| Agent | Role in this plan | Playbook | First responsibility focus |
| --- | --- | --- | --- |
| Code Reviewer | TODO: Describe why this agent is involved. | [Code Reviewer](../agents/code-reviewer.md) | Review code changes for quality, style, and best practices |
| Bug Fixer | TODO: Describe why this agent is involved. | [Bug Fixer](../agents/bug-fixer.md) | Analyze bug reports and error messages |
| Feature Developer | TODO: Describe why this agent is involved. | [Feature Developer](../agents/feature-developer.md) | Implement new features according to specifications |
| Refactoring Specialist | TODO: Describe why this agent is involved. | [Refactoring Specialist](../agents/refactoring-specialist.md) | Identify code smells and improvement opportunities |
| Test Writer | TODO: Describe why this agent is involved. | [Test Writer](../agents/test-writer.md) | Write comprehensive unit and integration tests |
| Documentation Writer | TODO: Describe why this agent is involved. | [Documentation Writer](../agents/documentation-writer.md) | Create clear, comprehensive documentation |
| Performance Optimizer | TODO: Describe why this agent is involved. | [Performance Optimizer](../agents/performance-optimizer.md) | Identify performance bottlenecks |
| Security Auditor | TODO: Describe why this agent is involved. | [Security Auditor](../agents/security-auditor.md) | Identify security vulnerabilities |
| Backend Specialist | TODO: Describe why this agent is involved. | [Backend Specialist](../agents/backend-specialist.md) | Design and implement server-side architecture |
| Frontend Specialist | TODO: Describe why this agent is involved. | [Frontend Specialist](../agents/frontend-specialist.md) | Design and implement user interfaces |
| Architect Specialist | TODO: Describe why this agent is involved. | [Architect Specialist](../agents/architect-specialist.md) | Design overall system architecture and patterns |
| Devops Specialist | TODO: Describe why this agent is involved. | [Devops Specialist](../agents/devops-specialist.md) | Design and maintain CI/CD pipelines |
| Database Specialist | TODO: Describe why this agent is involved. | [Database Specialist](../agents/database-specialist.md) | Design and optimize database schemas |
| Mobile Specialist | TODO: Describe why this agent is involved. | [Mobile Specialist](../agents/mobile-specialist.md) | Develop native and cross-platform mobile applications |

## Documentation Touchpoints
| Guide | File | Primary Inputs |
| --- | --- | --- |
| Project Overview | [project-overview.md](../docs/project-overview.md) | Roadmap, README, stakeholder notes |
| Architecture Notes | [architecture.md](../docs/architecture.md) | ADRs, service boundaries, dependency graphs |
| Development Workflow | [development-workflow.md](../docs/development-workflow.md) | Branching rules, CI config, contributing guide |
| Testing Strategy | [testing-strategy.md](../docs/testing-strategy.md) | Test configs, CI gates, known flaky suites |
| Glossary & Domain Concepts | [glossary.md](../docs/glossary.md) | Business terminology, user personas, domain rules |
| Data Flow & Integrations | [data-flow.md](../docs/data-flow.md) | System diagrams, integration specs, queue topics |
| Security & Compliance Notes | [security.md](../docs/security.md) | Auth model, secrets management, compliance requirements |
| Tooling & Productivity Guide | [tooling.md](../docs/tooling.md) | CLI scripts, IDE configs, automation workflows |

## Risk Assessment
Identify potential blockers, dependencies, and mitigation strategies before beginning work.

### Identified Risks
| Risk | Probability | Impact | Mitigation Strategy | Owner |
| --- | --- | --- | --- | --- |
| WEBJS engine falha no endpoint `/chats` (500 interno) | High | High | Usar endpoint direto `/chats/{chatId}/messages` em DEV_MODE e recomendar migração para NOWEB/CHROMIUM | Backend |
| Processamento duplicado da mesma mensagem | Medium | Medium | Implementar idempotência por `message_id` e/ou janela de tempo | Backend |
| Falta de resposta automática ao usuário | Medium | High | Validar pipeline de envio e logs de resposta | Backend |

### Dependencies
- **Internal:** Worker RQ, pipeline de processamento de mensagens, configuração de `DEV_MODE`
- **External:** WAHA API (engine WEBJS), Gemini API
- **Technical:** Sessão WAHA `default` em estado WORKING, `WAHA_API_KEY` configurada

### Assumptions
- WAHA responde ao endpoint `/chats/{chatId}/messages` com mensagens recentes.
- `DEV_MODE=true` com `DEV_PHONE_NUMBER` configurado.
- Se WEBJS falhar, migração para NOWEB/CHROMIUM será necessária.

## Resource Estimation

### Time Allocation
| Phase | Estimated Effort | Calendar Time | Team Size |
| --- | --- | --- | --- |
| Phase 1 - Discovery | TODO: e.g., 2 person-days | 3-5 days | 1-2 people |
| Phase 2 - Implementation | TODO: e.g., 5 person-days | 1-2 weeks | 2-3 people |
| Phase 3 - Validation | TODO: e.g., 2 person-days | 3-5 days | 1-2 people |
| **Total** | **TODO: total** | **TODO: total** | **-** |

### Required Skills
- TODO: List required expertise (e.g., "React experience", "Database optimization", "Infrastructure knowledge")
- TODO: Identify skill gaps and training needs

### Resource Availability
- **Available:** TODO: List team members and their availability
- **Blocked:** TODO: Note any team members with conflicting priorities
- **Escalation:** TODO: Name of person to contact if resources are insufficient

## Working Phases
### Phase 1 — Discovery & Alignment
**Steps**
1. Confirmar falha do endpoint `/chats` no WEBJS e validar resposta 500.
2. Validar funcionamento do endpoint `/chats/{chatId}/messages` para o número de DEV.

**Status**
- Concluído.

**Commit Checkpoint**
- After completing this phase, capture the agreed context and create a commit (for example, `git commit -m "chore(plan): complete phase 1 discovery"`).

### Phase 2 — Implementation & Iteration
**Steps**
1. Alterar polling para usar `/chats/{chatId}/messages` em DEV_MODE.
2. Confirmar enqueue e processamento via RQ.

**Status**
- Concluído.

**Commit Checkpoint**
- Summarize progress, update cross-links, and create a commit documenting the outcomes of this phase (for example, `git commit -m "chore(plan): complete phase 2 implementation"`).

### Phase 3 — Validation & Handoff
**Steps**
1. Enviar mensagem real do `DEV_PHONE_NUMBER` e validar logs de processamento.
2. Verificar envio de resposta ao usuário (pipeline outbound).

**Status**
- Em andamento (falta confirmar envio outbound).

**Commit Checkpoint**
- Record the validation evidence and create a commit signalling the handoff completion (for example, `git commit -m "chore(plan): complete phase 3 validation"`).

## Rollback Plan
Document how to revert changes if issues arise during or after implementation.

### Rollback Triggers
When to initiate rollback:
- Critical bugs affecting core functionality
- Performance degradation beyond acceptable thresholds
- Data integrity issues detected
- Security vulnerabilities introduced
- User-facing errors exceeding alert thresholds

### Rollback Procedures
#### Phase 1 Rollback
- Action: Discard discovery branch, restore previous documentation state
- Data Impact: None (no production changes)
- Estimated Time: < 1 hour

#### Phase 2 Rollback
- Action: TODO: Revert commits, restore database to pre-migration snapshot
- Data Impact: TODO: Describe any data loss or consistency concerns
- Estimated Time: TODO: e.g., 2-4 hours

#### Phase 3 Rollback
- Action: TODO: Full deployment rollback, restore previous version
- Data Impact: TODO: Document data synchronization requirements
- Estimated Time: TODO: e.g., 1-2 hours

### Post-Rollback Actions
1. Document reason for rollback in incident report
2. Notify stakeholders of rollback and impact
3. Schedule post-mortem to analyze failure
4. Update plan with lessons learned before retry

## Evidence & Follow-up

List artifacts to collect (logs, PR links, test runs, design notes). Record follow-up actions or owners.
