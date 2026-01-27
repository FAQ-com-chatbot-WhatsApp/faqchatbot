# Plan: Cleanup Legacy Auth and Regression Tests

This plan outlines the systematic removal of the legacy `get_current_user` implementation and the execution of a full regression test suite to ensure system stability.

## 1. Goals & Scope

### Goals
*   Eliminate redundant and legacy authentication logic in `robbot.core.security`.
*   Unify authentication dependency usage across all controllers using `robbot.api.v1.dependencies.get_current_user`.
*   Validate system integrity through a full execution of the automated test suite.

### Scope
*   **Backend**: `back/src/robbot/core/security.py`, `back/src/robbot/adapters/controllers/*.py`
*   **Tests**: All `pytest` files in `back/tests/`

## 2. Implementation Phases

### Phase 1: Discovery & Analysis (Developer: refactoring-specialist)
**Objective**: Identify all remaining usages of the legacy `get_current_user`.
1.  Search the entire codebase for imports from `robbot.core.security` referencing `get_current_user`.
2.  Analyze if any external webhooks or legacy scripts still rely on Bearer token authentication in the header.
3.  **Deliverable**: A list of files still using the legacy function.
**Commit**: `docs(plan): phase 1 discovery complete`

### Phase 2: Refactoring & Cleanup (Developer: feature-developer)
**Objective**: Remove the legacy code and update dependents.
1.  Update any remaining controllers found in Phase 1 to use the cookie-based dependency.
2.  Remove `get_current_user` and any related legacy Bearer logic from `back/src/robbot/core/security.py`.
3.  Verify that `back/src/robbot/api/v1/dependencies.py` is the sole source of truth for user authentication.
**Commit**: `refactor(auth): remove legacy get_current_user and update controllers`

### Phase 3: Validation & Testing (Developer: test-writer)
**Objective**: Run the full test suite and verify no regressions.
1.  Run `pytest` inside the `api` container: `docker exec api pytest`.
2.  Fix any tests that break due to the authentication change (e.g., tests that expect Bearer tokens instead of cookies).
3.  Perform a manual sanity check on `/leads` and `/conversations` using the browser or a script.
**Commit**: `test(auth): validation and regression tests passed`

## 3. Agent Lineup
- **refactoring-specialist**: Handles Phase 1 discovery.
- **feature-developer**: Performs the code removal and refactoring.
- **test-writer**: Executes and fixes the test suite.

## 4. Documentation Touchpoints
- Update `back/docs/academic/casos-teste-validacao.md` if any test procedures change.
- Ensure `ARCH.md` (if exists) reflects cookie-based authentication.

## 5. Success Criteria
- [ ] No occurrences of `from robbot.core.security import get_current_user` in the codebase.
- [ ] `back/src/robbot/core/security.py` is cleaned up.
- [ ] 100% of the `pytest` suite passes (or known failures are documented).
- [ ] Protected endpoints (`/leads`, `/conversations`) remain accessible via cookies.
