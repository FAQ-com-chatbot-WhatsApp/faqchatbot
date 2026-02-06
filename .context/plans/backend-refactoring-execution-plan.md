# Backend Refactoring Execution Plan

## Goal and Scope
Redesign and refactor the backend system to align with **Clean Architecture** and **SOLID** principles, addressing the technical debt identified in the `backend-refactoring-plan.md`. The primary focus is on semantic clarity, separation of concerns, and consolidating redundant logic, specifically in the LLM provider system and context repository.

**Scope:**
- **LLM Provider System**: Consolidate redundant abstraction layers and unified interface.
- **Domain Layer**: Extract business rules into rich domain entities (`Lead`, `Conversation`, `MaturityScore`).
- **Context Repository**: Rename "Playbook" concepts to "Context" for semantic alignment with MCP.
- **Service Layer**: Decompose God classes (`ConversationOrchestrator`) into specialized, testable services.
- **Database**: Perform rename migrations for semantic clarity.

---

## Phase 1: Foundation & LLM Provider Consolidation
**Objective:** Eliminate redundant abstraction layers and unify LLM provider logic.

### Steps
1. **Unify Interface**: Create a single `LLMProvider` abstract interface in `back/src/robbot/core/interfaces.py` that supports text generation, structured output, and embeddings.
2. **Implementation Consolidation**: 
   - Move `GeminiProvider` and `GroqProvider` from `adapters/external/providers/` to implement the unified interface.
   - Remove `GeminiLLMProvider` (redundant wrapper).
   - Rename `GeminiClient` to `LLMClient` (or similar) and refactor it to be a clean factory/manager of providers.
3. **Dependency Update**: Update all services to inject the unified `LLMProvider` interface instead of concrete clients.
4. **Remove Dead Logic**: Identify and delete unused provider stubs or legacy fallback logic that doesn't fit the new manager pattern.

**Primary Agent:** `refactoring-specialist`
**Reviewer:** `architect-specialist`
**Git Commit:** `refactor: unify LLM provider interface and consolidate implementations`

---

## Phase 2: Domain Layer Implementation
**Objective:** Transition business logic from services and infra to pure domain entities.

### Steps
1. **Value Objects**: Implement `MaturityScore`, `PhoneNumber`, and `SpinPhase` as immutable value objects in `domain/value_objects/`.
2. **Rich Entities**: 
   - Create `Lead` entity with scoring logic (`apply_intent_adjustment`).
   - Create `Conversation` entity with state management rules.
3. **Repository interfaces**: Define interfaces for searching and persisting these entities independently of SQLAlchemy.
4. **Unit Testing**: Write comprehensive tests for domain logic without any database dependencies.

**Primary Agent:** `backend-specialist`
**Reviewer:** `test-writer`
**Git Commit:** `feat: implement rich domain layer with value objects and entities`

---

## Phase 3: Context Repository Rename & Migration
**Objective:** Align naming with industry standards (RAG/MCP) and clarify "Playbook" vs "Context".

### Steps
1. **Database Rename Migration**:
   - `playbooks` → `contexts`
   - `playbook_steps` → `context_items`
   - `messages` (knowledge) → `contents`
2. **Code Rename Refactoring**: 
   - Systematically update all references in services, repositories, and controllers.
   - Update variable names: `playbook_id` → `context_id`, `message_id` → `content_id`.
3. **API Update**: Update route aliases to support `/contexts` instead of `/playbooks` (with backward compatibility if needed).
4. **Auto-Send Logic**: Implement the refined logic where the bot autonomously decides to send content based on `usage_hint`.

**Primary Agent:** `database-specialist`
**Reviewer:** `backend-specialist`
**Git Commit:** `refactor: rename playbook system to context repository and migrate schema`

---

## Phase 4: Service Layer Decomposition (The "God Class" Split)
**Objective:** Break down `ConversationOrchestrator` into specialized handlers.

### Steps
1. **Extraction**: Extract `InboundMessageHandler`, `ResponseGenerator`, `HandoffService`, and `InteractionLogger`.
2. **Orchestration**: Refactor `ConversationOrchestrator` to be a thin coordinator of these services.
3. **Dependency Injection**: Use the `container.py` to manage these new dependencies cleanly.
4. **Verification**: Execute integration tests to ensure the complete message flow still works correctly.

**Primary Agent:** `refactoring-specialist`
**Reviewer:** `code-reviewer`
**Git Commit:** `refactor: decompose ConversationOrchestrator into specialized services`

---

## Phase 5: Verification & Quality Gate
**Objective:** Ensure high quality, performance, and documentation completeness.

### Steps
1. **Test Coverage**: Ensure at least 80% coverage on new domain and service components.
2. **Documentation**: Update `README.md`, technical specs, and `.context` docs to reflect the new architecture.
3. **MCP Readiness Check**: Verify that `Context` items can be easily exposed as MCP resources.
4. **Final Refinement**: Remove any remaining "logicas mortas" identified during the process.

**Primary Agent:** `documentation-writer`
**Reviewer:** `qa`
**Git Commit:** `docs: update architecture documentation and final refactoring cleanup`

---

## Success Criteria
- **Clean Architecture**: `domain/` has NO dependencies on `infra/` or external packages.
- **SOLID**: `ConversationOrchestrator` reduced by 50%+ and follows SRP.
- **KISS/DRY**: LLM provider logic consolidated into a single clean manager/factory.
- **Performance**: Latency for response generation remains unaffected or improves.
- **Maintainability**: Clear naming convention (Context/Content) used throughout the project.

---

## Execution History

> Last updated: 2026-02-06T18:00:00Z | Progress: 100%

### Phase 1: LLM Provider Consolidation [COMPLETED]
- **Finished:** 2026-02-06T16:00:00Z
- **Outcome:** Unified `LLMProvider` interface, consolidated Gemini and Groq providers, refactored `LLMClient`.

### Phase 3: Context Repository & Semantic Alignment [COMPLETED]
- **Finished:** 2026-02-06T16:45:00Z
- **Outcome:** Renamed all "Playbook" to "Context" and "Message" to "Content" (Schemas, Services, Repositories, Models, Controllers, and Tools).

### Phase 2: Domain Layer & Rich Entities [COMPLETED]
- **Finished:** 2026-02-06T17:15:00Z
- **Outcome:** Implemented `Lead` and `Conversation` rich entities with business logic, added domain mappers, and updated Models/Services to use them.

### Phase 4: Service Decomposition [COMPLETED]
- **Finished:** 2026-02-06T17:30:00Z
- **Outcome:** Decomposed `ConversationOrchestrator` into `ConversationPipeline` (ingestion/analysis) and `ResponseDispatcher` (delivery/logging).

### Phase 5: Verification & Quality Gate [COMPLETED]
- **Finished:** 2026-02-06T18:00:00Z
- **Outcome:** Unified test coverage with green suite, updated architectural docs, and verified cross-database compatibility (JSON vs JSONB).


## Rollback Plan

### Triggers
- Core conversation flow broken (WhatsApp integration failure).
- Critical data loss during database migrations.
- Significant increase in LLM latency (>2s increase).

### Procedures
- **Database**: Use pre-migration snapshot/backup.
- **Code**: Use `git revert` on the specific phase branch.
- **Status**: Notify developers and mark current phase as "failed" in workflow tracking.
