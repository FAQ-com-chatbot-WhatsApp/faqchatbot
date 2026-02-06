---
status: filled
generated: 2026-02-06
agents:
  - type: "architect-specialist"
    role: "Design the new modular directory structure and ensure SRP adherence."
  - type: "refactoring-specialist"
    role: "Execute file movements and perform global import refactoring."
  - type: "test-writer"
    role: "Verify system integrity by running and updating test suites."
  - type: "documentation-writer"
    role: "Update architectural docs and data flow diagrams."
---
# Architectural Reorganization & Modularization

## Objective
Reorganize the `back/src/robbot/` codebase into context-aware subdirectories. This modularization aims to solve the "flat structure" bottleneck, improve developer navigation, and strictly isolate business domains (Leads, Bot, AI, Communication).

## Success Criteria
- **Zero Regression**: 100% of existing tests must pass after reorganization.
- **Improved Cohesion**: Files are grouped by functional context rather than generic layers when appropriate.
- **Clean Imports**: All `import` statements are updated to the new paths without any broken dependencies.
- **Documentation Sync**: `architecture.md` and `data-flow.md` reflect the new structure.

## Risks & Mitigations
- **Import Cycles**: Moving files between folders can introduce circular dependencies. *Mitigation: Perform static analysis before moving and use `__init__.py` exports.*
- **Broken Tests**: Tests rely on relative/absolute imports. *Mitigation: Use global search/replace with regex to update imports project-wide.*
- **IDE Disruption**: Large movements can confuse IDE indices. *Mitigation: Perform changes in a single atomic phase and re-index.*

---

## Phase 1: Planning (P) [COMPLETED]
**Objective:** Map dependencies and define the exact movement matrix.

**Steps**
1. [x] **Dependency Mapping**: Map all cross-file imports using `grep` or static analysis to identify potential circularities. (Owner: architect-specialist)
2. [x] **Target Structure Definition**: Finalize the list of new directories and which files go where. (Owner: architect-specialist)
3. [x] **Draft Plan Review**: Submit the movement matrix for user approval. (Owner: architect-specialist)

**Deliverables**
- A clear table of [Old Path] -> [New Path] migrations.

---

## Phase 2: Execution (E) [COMPLETED]
**Objective:** Perform the physical reorganization and refactor imports.

**Steps**
1. [x] **Directory Creation**: Create the new subdirectory tree in `src/robbot/domain/`, `src/robbot/services/`, and `src/robbot/infra/`. (Owner: refactoring-specialist)
2. [x] **File Migration**: Move files according to the Phase 1 matrix. (Owner: refactoring-specialist)
3. [x] **Global Import Update**: Perform a global string/regex search and replace to update imports (e.g., `from robbot.services.lead_service` -> `from robbot.services.leads.lead_service`). (Owner: refactoring-specialist)
4. [x] **Init File Refactoring**: Ensure `__init__.py` in each subfolder correctly exports necessary symbols for public use. (Owner: refactoring-specialist)

---

## Phase 3: Validation (V) [IN PROGRESS]
**Objective:** Ensure the system is fully functional and documentation is current.

**Steps**
1. [x] **Fix Critical Imports**: Investigate and resolve circular imports and broken references (specifically `message_repository`, `ContextTools`, `message_processor`, `lid_resolver_service`). (Owner: refactoring-specialist)
2. [ ] **Unit Verification**: Run all unit tests in `back/tests/unit/` (Key services verified: Leads, Conversation). (Owner: test-writer)
3. [ ] **Integration Verification**: Run integration tests to verify DB and external client connectivity. (Owner: test-writer)
4. [ ] **Documentation Update**: Update `architecture.md` and `data-flow.md` with the new folder tree. (Owner: documentation-writer)
5. [ ] **Cleanup**: Remove any empty directories and old `__pycache__` artifacts. (Owner: refactoring-specialist)

**Deliverables**
- Passing test report.
- Updated technical documentation in `.context/docs/`.

---

## Rollback Plan
### Triggers
- More than 20% of tests failing with unresolvable import errors.
- Discovery of critical circular dependencies that break the application startup.

### Procedures
- **Git Revert**: Use `git checkout .` or `git reset --hard` to revert to the pre-execution state.
- **Status Reset**: Mark the workflow phase as "failed" and re-evaluate the dependency map.
