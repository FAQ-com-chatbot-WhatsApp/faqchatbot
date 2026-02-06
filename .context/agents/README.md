# AI Agent Personnel Guide: Full-Stack Implementation Manual

The purpose of this playbook is to guide the feature developer agent through the creation and modification of functionality across the `go` application, ensuring strict adherence to the established clean architecture, Dependency Injection (DI) principles, and coding conventions for both the Python backend and the Next.js frontend.

---

## 1. Core Focus Areas and Architectural Layers

The development process is strictly layered. The agent must understand which directories correspond to which architectural layer.

### 1.1. Backend Layers (Python/FastAPI)

All development follows the Repository and Service Layer patterns. Dependency Injection is mandatory for composition.

| Layer | Directory Pattern | Responsibility | Key Principles |
| :--- | :--- | :--- | :--- |
| **Data Models (Persistence)** | `back/src/robbot/infra/db/models` | Defines SQLAlchemy ORM classes. | Requires Alembic migration setup. |
| **Data Contracts (I/O)** | `back/src/robbot/schemas` | Pydantic definitions for request/response bodies and internal data transfer. | Strict typing and validation enforced here. |
| **Repositories (Data Access)** | `back/src/robbot/adapters/repositories` | Implements `IRepository` interface for direct DB interactions. | Must be ignorant of business logic. |
| **Services (Business Logic)** | `back/src/robbot/services` | Encapsulates all domain rules, complex workflows, and orchestration. | **Highest Priority:** Logic resides here. Uses injected Repositories. |
| **Controllers (API)** | `back/src/robbot/api/v1/routers` | Defines FastAPI routes. | Must be thin; delegates execution immediately to injected Services. |

### 1.2. Frontend Layers (Next.js/React)

The frontend uses TypeScript and adheres to a services layer for API communication and a modular component structure.

| Layer | Directory Pattern | Responsibility | Key Principles |
| :--- | :--- | :--- | :--- |
| **API Clients** | `frontend/src/services` | Asynchronous functions mapping to backend endpoints. | **Must** use `fetchApi` utility from `lib/api.ts`. |
| **Views/Pages** | `frontend/src/app/**` | Routes and composition of features. | Integrate client services and UI components. |
| **UI Primitives** | `frontend/src/components/ui` | Reusable, styled components (Shadcn/Radix). | Prioritize reuse and adherence to design system. |
| **Networking Utility** | `frontend/src/lib/api.ts` | Global utility for standardized API calls and error handling. | Reference only; do not modify unless updating global API behavior. |

---

## 2. Standard Feature Implementation Workflow (E2E)

Use this step-by-step sequence for implementing any new feature that requires both backend persistence and a user interface.

### Phase 1: Backend Development (Model to API)

| Step | Action | Focus Area | Notes |
| :--- | :--- | :--- | :--- |
| **B1. Define Data** | Create/Update SQLAlchemy models and Pydantic schemas. | `models`, `schemas` | Generate an Alembic migration if DB schema changes. Define `Input`, `Update`, and `Output` schemas. |
| **B2. Implement Repository** | Implement necessary CRUD operations in a new or existing `*Repository` class. | `adapters/repositories` | If a new entity, ensure it implements `IRepository` methods. Focus purely on data access. |
| **B3. Write Business Logic** | Create or modify the dedicated `*Service` class. | `services` | Inject Repositories via the constructor (DI). Implement validation, transaction logic, and domain rules here. |
| **B4. Define Route** | Create a new endpoint in the relevant API router. | `api/v1/routers` | Inject the Service from Step B3. The handler must be minimal: read input schema, call service method, return output schema. |
| **B5. Test Backend** | Create unit tests for the Service (mocking repos) and API integration tests. | `tests/unit/services`, `tests/api` | Verify service logic correctness and endpoint accessibility/schema compliance. |

### Phase 2: Frontend Development (Service to UI)

| Step | Action | Focus Area | Notes |
| :--- | :--- | :--- | :--- |
| **F1. Create Frontend Service** | Implement the client-side API wrapper function(s). | `frontend/src/services` | Use `fetchApi` (`frontend/src/lib/api.ts`) exclusively. Define TypeScript types matching Pydantic schemas. |
| **F2. Build UI Components** | Assemble or create necessary components for interaction. | `frontend/src/components/ui` | Adhere strictly to the existing component library patterns (e.g., `Button`, `Input`, `Card`). |
| **F3. Implement Page/View** | Integrate the components and the frontend service logic. | `frontend/src/app/**` | Handle loading states, input validation, and display errors normalized by `normalizeApiError`. |

---

## 3. Architectural Principles and Best Practices

### 3.1. Backend Best Practices (Python)

#### Dependency Injection (DI)
*   **Rule:** Repositories, Services, and Utilities **must** be injected into dependent classes via the constructor.
*   **Observation:** The codebase relies on a DI container system (evidenced by `TestDIInControllers`, `TestLeadServiceSessionInjection`). Do not instantiate dependencies directly inside Service or Controller methods.

#### Service Layer Purity
*   The Service layer (`back/src/robbot/services`) is the single source of truth for business rules.
*   **Constraint:** Services may only depend on Repositories or other Services. They must not contain direct I/O logic (e.g., database session handling or request/response details).

#### Testing Conventions
*   **Unit Tests (`tests/unit`):** Use mocking to isolate the logic being tested (especially service logic from repository interaction).
*   **Integration Tests (`tests/api`):** Test the full request lifecycle against the live FastAPI application (or a mocked-up integration stack), verifying status codes and structure.

### 3.2. Frontend Best Practices (TypeScript/React)

#### API Handling
*   **Mandatory Utility:** All API communication *must* flow through `frontend/src/lib/api.ts`.
*   **Error Normalization:** Rely on the `normalizeApiError` function to consistently handle and present errors originating from the backend.

#### Component Structure
*   **Reuse:** Always check `frontend/src/components/ui/` first before creating a new visual component. The goal is consistency and minimal custom styling.
*   **Typing:** Ensure TypeScript types for data structures passed between components and services are precise and match the backend Pydantic schemas.

---

## 4. Key Files and Code Patterns Reference

The agent must be intimately familiar with these files for context and pattern adherence.

| Layer | File Path | Purpose and Pattern |
| :--- | :--- | :--- |
| **Backend Interface** | `back/src/robbot/core/interfaces.py` | Defines the `IRepository` contract. All concrete repositories must adhere to this. |
| **Service Example** | `back/src/robbot/services/user_service.py` | Reference for how Repositories are injected and business methods are structured. |
| **Test Reference** | `back/tests/unit/test_di_controllers.py` | Demonstrates how controllers are tested and how DI works in the test setup. |
| **Repository Example** | `back/src/robbot/adapters/repositories/user_repository.py` | Pattern for mapping domain objects to SQLAlchemy ORM and executing queries. |
| **Frontend Network** | `frontend/src/lib/api.ts` | Contains `fetchApi` and `normalizeApiError`. **Crucial for all client networking.** |
| **Frontend Service** | `frontend/src/services/passwordService.ts` | Example of a standard frontend API wrapper using the `fetchApi` utility. |
| **UI Components** | `frontend/src/components/ui/button.tsx` | Reference point for component styling and utility usage (e.g., `cn` utility for Tailwind). |
| **Business Workflow** | `back/src/robbot/services/message_pipeline.py` | Example of complex orchestration logic encapsulated within the Service layer. |
