# System Architecture Documentation

This document describes the high-level architecture, design patterns, and structure of the application. The system follows a **Modular Monolith** approach informed by **Clean Architecture (Hexagonal)** and **Domain-Driven Design (DDD)** principles, particularly in the Python backend.

The architecture prioritizes maintainability, testability, and clear separation of concerns by ensuring that core business logic remains independent of external frameworks, databases, and UI implementations.

---

## 1. High-Level Topology

The application is split into two primary deployable units:

1.  **Frontend**: A Next.js/React application serving the user interface.
2.  **Backend**: A Python service (FastAPI) exposing the REST API, managing business logic, and handling integrations.

### Component Interaction Diagram

The diagram illustrates the flow of control and dependencies, showing how the client interacts with the API, and how the API layers access core business logic and infrastructure.

```mermaid
graph TD
    subgraph Frontend [Next.js Application]
        FE[Browser / Client]
    end

    subgraph Backend [Python API Service]
        A[API Routers / Controllers]
        B[Services (Business Logic)]
        C[Repositories / Adapters]
    end

    subgraph Infrastructure
        D[Database (SQLAlchemy Models)]
        E[WAHA / External Comm. API]
        F[LLM Providers (e.g., Gemini)]
    end

    FE --> A: HTTP/REST API Calls
    A --> B: Calls Service Layer (Delegation via DI)
    B --> C: Interacts via Repository Interfaces
    C --> D: Persistence Operations (ORM)
    B --> E: External Messaging / Webhooks
    B --> F: AI Processing Requests
```

## 2. Backend Architecture (Clean/Hexagonal Model)

The Python backend enforces the Dependency Inversion Principle, where dependencies flow inward towards the core domain layer.

### 2.1. The Domain and Service Layer (Core)

This layer contains the core business rules and orchestration logic. It is infrastructure-agnostic.

| Layer | Directories | Responsibility | Key Principle |
| :--- | :--- | :--- | :--- |
| **Services** | `back/src/robbot/services` | Orchestrates operations, enforces business invariants, manages transactions, and holds domain logic. Services consume Repository interfaces. | Business Logic, Orchestration |
| **Domain Models** | `back/src/robbot/domain` | Defines core business entities and value objects (the "things" the business cares about). | Domain Entities, Value Objects |

### 2.2. Adapters and Infrastructure Layer (The Ports & Adapters)

This layer contains implementation details and external dependencies.

| Layer | Directories | Responsibility | Example Exports |
| :--- | :--- | :--- | :--- |
| **Controllers (API)** | `back/src/robbot/adapters/controllers`, `back/src/robbot/api/v1/routers` | Entry point for API requests. Handles request validation (Pydantic), authentication, and delegates tasks to the Service layer. | `LeadController`, `HandoffController` |
| **Schemas (DTOs)** | `back/src/robbot/schemas` | Defines the data contracts (Pydantic models) used for input/output across API boundaries. | `AuthSessionResponse`, `AssignRequest` |
| **Repositories** | `back/src/robbot/adapters/repositories` | Concrete implementations of persistence interfaces (e.g., SQLAlchemy queries). Maps database models to domain entities. | `AnalyticsRepository`, `BaseRepository` |
| **Infrastructure (DB)** | `back/src/robbot/infra/db/models` | Defines the actual database schema using SQLAlchemy ORM models. | `AuditLogModel`, `AuthSessionModel` |

### Dependency Injection (DI)

Dependency Injection is crucial for maintaining the architectural boundaries and enabling testability.

*   An internal DI container manages the lifecycle of Services and Repositories.
*   **Benefit 1 (Testability):** When testing a service (e.g., `TestLeadServiceCreation`), external dependencies like repositories can be easily mocked without needing a running database.
*   **Benefit 2 (Flexibility):** Core business logic (`Services`) is decoupled from infrastructure choices (e.g., swapping SQLAlchemy for another ORM would only affect the `Repositories` layer).

## 3. Frontend Architecture (Next.js/React)

The frontend uses the Next.js App Router and focuses on clean separation between UI primitives, client-side logic, and API communication.

### 3.1. Directory Structure

| Directory | Purpose | Key Artifacts |
| :--- | :--- | :--- |
| `frontend/src/app` | Routing, pages, and layouts (e.g., `AuthLayout`). Defines application routes. | `page.tsx`, `(auth)/signin/page.tsx` |
| `frontend/src/components/ui` | Generic, highly reusable, and styling-agnostic UI primitives (often based on shadcn/ui). | `input.tsx`, `button.tsx`, `tabs.tsx` |
| `frontend/src/components` | Complex application-specific components (e.g., forms, navigation). | `mode-toggle.tsx` |
| `frontend/src/services` | Client-side API wrappers responsible for making network requests. | `authService.ts`, `passwordService.ts` |
| `frontend/src/hooks` | Custom React hooks for shared logic, state management, and side effects (e.g., authentication). | `useAuth.ts`, `useFormFeedback.ts` |
| `frontend/src/lib` | General utilities, helper functions (`cn`), base API client, and validation schemas. | `utils.ts`, `api.ts`, `validations/auth.ts` |

### 3.2. Frontend Data Flow Example

1.  A component triggers an action (e.g., a button click).
2.  A custom hook (e.g., `useAuth` running `login`) calls a service function (`authService.loginApi`).
3.  The service function uses the standardized `fetchApi` utility (`frontend/src/lib/api.ts`).
4.  `fetchApi` handles HTTP requests, error normalization (`normalizeApiError`), and communication with the Backend Controller.

## 4. Bounded Contexts and Domain Separation

The backend is conceptually partitioned into Bounded Contexts to manage complexity. This modularity is visible in the dedicated services and controllers.

| Context | Purpose | Key Backend Components | Example Functionality |
| :--- | :--- | :--- | :--- |
| **Authentication/User** | Manages user identity, sessions, permissions, and security features (MFA, blocking). | `AuthService`, `UserService`, `AuthSessionRepository` | `loginApi`, `block_user`, `requestPasswordRecovery` |
| **Conversations/Messaging** | Handles the core message pipeline, state machine transitions, and orchestrating responses. | `ConversationService`, `WAHAService` | `TestMessagePipelineIntegration`, `assign_conversation` |
| **Lead Management** | Manages prospective client data, qualification status, and assignment to agents. | `LeadService`, `LeadController` | `auto_assign_lead`, `assign_lead` |
| **Reporting/Analytics** | Collects, aggregates, and serves performance and usage metrics. | `AnalyticsRepository`, `DashboardController` | `bot_autonomy`, `conversion_rate_report` |
| **Playbooks** | Defines automated workflows and conversation scripts used by the AI engine. | `PlaybookService`, `playbook_step_controller.py` | `add_step` |

## 5. External Dependencies and Integrations

The system relies on specialized external services for its primary communication and AI capabilities.

| Provider | Role in System | Key Integration Files | Communication Type |
| :--- | :--- | :--- | :--- |
| **WAHA (WhatsApp Handler)** | Primary gateway for sending and receiving real-time WhatsApp messages. | `WAHAService`, `waha_controller.py` | Webhooks (inbound) and REST API (outbound) |
| **LLM Providers (e.g., Gemini)** | Core AI for conversation generation, summarization, and task execution. | `VisionService`, `TestPhase6Gemini` | API calls |
| **PostgreSQL** | Primary persistent data store for all application data. | `back/src/robbot/infra/db/models` | SQLAlchemy ORM |

## 6. Development Guidelines

### API Contract and Data Transfer

*   **Enforcement:** All API interactions must use Pydantic models defined in `back/src/robbot/schemas` to ensure strict type checking and reliable contract stability between the client and server.
*   **Internal Data:** `Services` should primarily deal with `Domain Models` or basic data structures, converting DTOs (`Schemas`) upon entry and exit from the Controller layer.

### Testing Strategy

The codebase maintains a robust testing pyramid:

1.  **Unit Tests (`back/tests/unit`)**: Focus on isolating business logic (Services) and confirming component functionality. Dependencies are typically mocked using the DI framework.
2.  **Integration Tests (`back/tests/integration`)**: Verify the interaction between layers (e.g., Service talking to a Repository via an in-memory database session). Essential for verifying DI setup and transaction management.
3.  **API Tests (`back/tests/api`)**: Comprehensive end-to-end testing of the full FastAPI routes using a test client, often focusing on entire user flows (e.g., authentication, lead creation).

### Frontend Component Separation

*   **Primitives:** Use `frontend/src/components/ui` for simple, reusable elements (e.g., `Input`, `Button`).
*   **Application Components:** Use `frontend/src/components` for components that encapsulate application-specific state or complex logic (e.g., `MessageInput`).
*   **Page/Route Logic:** Keep presentation and data fetching logic within the `frontend/src/app` page files.
