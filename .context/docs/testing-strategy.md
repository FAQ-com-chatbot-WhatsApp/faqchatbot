---
status: filled
---
# Testing Strategy: Clinica Go


**Status:** filled
**Updated:** 2026-01-27

This document defines the levels of testing, frameworks used, and guidance for maintaining the high reliability of the **Go** ecosystem.

---

## 1. Testing Pyramid

We follow a classic testing pyramid, emphasizing a large volume of fast unit tests, a significant layer of integration tests, and targeted end-to-end API tests.

### 1.1. Unit Tests
- **Backend:** Located in `back/tests/unit`. Target individual functions, models, and **Services** in isolation.
- **Frontend:** Located in `frontend/src/__tests__` (or component-adjacent). Target utility functions (`src/lib`) and atomic UI components.
- **Requirement:** 100% logic branch coverage for critical services.

### 1.2. Integration Tests
- **Backend:** Located in `back/tests/integration`. Verify the interaction between the service layer and the repository layer using an in-memory database or a test-dedicated container.
- **Frontend:** Verifying component groups within the **Styleguide** to ensure cohesive state management.

### 1.3. API / E2E Tests
- **Backend:** Located in `back/tests/api`. Use FastAPI's `TestClient` to perform request/response validation across the entire stack (Router -> Controller -> Service -> DB).
- **Patterns:** See `back/tests/api/test_15_waha_full.py` for examples of complex Multi-step conversational flows.

---

## 2. Tools & Frameworks

| Layer | Tool | Purpose |
| :--- | :--- | :--- |
| **Backend** | `pytest` | Core testing framework. |
| **Backend** | `pytest-mock` | Orchestrating mocks and spies. |
| **Frontend** | `npm run test` (Vitest/Jest) | UI logic and utility testing. |
| **Frontend** | `/styleguide` | Manual and visual verification of components. |

---

## 3. Dependency Injection (DI) & Mocking

Testing the backend requires active use of the dependency injection framework to override production services with mocks.

**Best Practice:**
Always inherit from `TestDIInControllers` (see `back/tests/unit/test_di_controllers.py`) when writing controller tests. This ensures that the database session and external clients (like Google Gemini or WAHA) are appropriately mocked and don't leak into the system.

---

## 4. Specific Test Targets

- **Conversation Pipelines:** Must test `OrchestratorService` with mocked AI responses to ensure correct routing.
- **Authentication Flow:** Must cover MFA, session expiration, and role-based access rejection.
- **Webhooks:** Use `WAHAMessagePayload` schemas to simulate incoming WhatsApp messages.

---

## 5. Running Tests

### Backend
```bash
cd back
poetry run pytest
```

### Frontend
```bash
cd frontend
npm run test
```
