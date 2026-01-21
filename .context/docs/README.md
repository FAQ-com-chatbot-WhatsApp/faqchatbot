# Clinica Go Repository Documentation Index

Welcome to the **Clinica Go** repository knowledge base. This documentation serves as the central hub for understanding the system's architecture, development practices, and key concepts.

This index is generated from the `docs/README.md` file and provides links and summaries for all foundational documentation pages, as well as an overview of the codebase structure and key architectural components.

Start with the **Core Guides** to get a broad overview of the project, then dive into specific topics related to the frontend, backend, testing, and system operations.

---

## 1. Core Guides

These foundational documents provide essential context for anyone working on the codebase, outlining the 'why' and 'how' of the project.

| Guide | Description | File |
| :--- | :--- | :--- |
| **Project Overview** | High-level summary of the product goals, target audience, and current roadmap status. | [`project-overview.md`](./project-overview.md) |
| **Architecture Notes** | Detailed explanation of the system's structure, service boundaries, and design patterns (e.g., DDD, Dependency Injection in the Python backend). | [`architecture.md`](./architecture.md) |
| **Development Workflow** | Guidelines for setting up the local environment, branching strategy, commit conventions, and pull request procedures. | [`development-workflow.md`](./development-workflow.md) |
| **Testing Strategy** | Defines the levels of testing (unit, integration, API, end-to-end), testing frameworks used, and guidance on writing effective tests. | [`testing-strategy.md`](./testing-strategy.md) |
| **Glossary & Domain Concepts** | A dictionary of domain-specific terminology (e.g., Leads, Conversations, Playbooks, Tags) and key business rules. | [`glossary.md`](./glossary.md) |
| **Data Flow & Integrations** | Diagrams and descriptions detailing how data moves through the system, focusing on external integrations (like WhatsApp/WAHA) and internal queues/topics. | [`data-flow.md`](./data-flow.md) |
| **Security & Compliance Notes** | Overview of the authentication model, authorization roles, secrets management, and notes on critical security considerations. | [`security.md`](./security.md) |
| **Tooling & Productivity Guide** | Instructions for using utility scripts (`auto-commit.sh/ps1`), IDE configurations, and efficiency tips. | [`tooling.md`](./tooling.md) |

---

## 2. Codebase Structure

The repository is divided into two primary directories, representing the core application layers: `back/` (Python/FastAPI) and `frontend/` (Next.js/React).

### Backend (`back/`)

The Python backend implements a Hexagonal/Clean Architecture pattern using FastAPI and relies heavily on Dependency Injection (DI) for decoupling.

| Component Type | Path | Purpose | Key Exports/Examples |
| :--- | :--- | :--- | :--- |
| **Controllers** | `back/src/robbot/adapters/controllers`, `back/src/robbot/api/v1/routers` | Entry points for API requests; handles routing and delegates tasks to services. | `add_step`, `assign_conversation` |
| **Services** | `back/src/robbot/services` | Contains core business logic, orchestration, and domain coordination. | `AuthService`, `WAHAService`, `WorkerAnalyticsService` |
| **Repositories** | `back/src/robbot/adapters/repositories` | Abstracts database (DB) interaction, mapping models to domain entities. | `AnalyticsRepository`, `BaseRepository` |
| **Models & Schemas** | `back/src/robbot/domain`, `back/src/robbot/schemas` | Defines domain entities and Pydantic schemas for request/response validation. | `AuditLogEntry`, `BotAutonomyMetricsSchema` |
| **Testing** | `back/tests/unit`, `back/tests/integration`, `back/tests/api` | Comprehensive test suites. Note the DI tests (`test_di_controllers.py`, `test_di_container.py`). | `TestPhase1Auth`, `TestMfaLoginFlow` |

### Frontend (`frontend/`)

The frontend is built using Next.js, React, and Tailwind CSS. It follows standard Next.js conventions with a focus on reusable UI components and a dedicated style guide.

| Component Type | Path | Purpose | Key Exports/Examples |
| :--- | :--- | :--- | :--- |
| **Pages** | `frontend/src/app` | Defines routes and top-level application structure. | `Home`, `ResetPasswordPage`, `AuthLayout` |
| **UI Components** | `frontend/src/components/ui` | Reusable, styled primitives built on top of [shadcn/ui] (e.g., `Button`, `Input`). | `Checkbox`, `DropdownMenu`, `Separator` |
| **Styleguide** | `frontend/src/app/styleguide` | Dedicated pages for showcasing components and design documentation. Essential for verifying visual consistency. | `AvatarShowcase`, `ProgressShowcase` |
| **Services** | `frontend/src/services` | Contains functions for interacting with the backend API. | `loginApi`, `requestPasswordRecovery` |
| **Hooks** | `frontend/src/hooks` | Custom React hooks for global state and logic. | `useAuth`, `useFormFeedback` |

---

## 3. Key Concepts & Exports

The codebase exposes numerous classes and functions critical to the application's function. Below are a few high-impact examples to help you navigate the domain structure.

### Authentication & Users

| Export | Location | Type | Description |
| :--- | :--- | :--- | :--- |
| `AuthService` | `back/src/robbot/services/auth_services.py` | Class | Handles user creation, session management, and credential verification. |
| `AuthLayout` | `frontend/src/app\(auth)\layout.tsx` | Function | The structural layout component wrapping sign-in and sign-up pages. |
| `AuditLogEntry` | `back/src/robbot/schemas/auth.py` | Schema | Pydantic schema defining records of user actions for security tracking. |
| `AuthSessionModel` | `back/src/robbot/infra/db/models/auth_session_model.py` | Class | SQLAlchemy model for persistent user session data. |

### Metrics & Analytics

The analytics layer is crucial for performance monitoring and reporting.

| Export | Location | Type | Description |
| :--- | :--- | :--- | :--- |
| `AnalyticsRepository` | `back/src/robbot/adapters/repositories/analytics_repository.py` | Class | Abstraction layer for querying aggregated performance and usage data. |
| `BotAutonomyMetricsSchema` | `back/src/robbot/schemas/metrics_schemas.py` | Schema | Defines metrics reporting on AI handling capabilities without human intervention. |
| `ActivityHeatmapDataPointSchema` | `back/src/robbot/schemas/metrics_schemas.py` | Schema | Data structure used for visualizing user or bot activity over time. |
| `bot_response_time_report` | `back/src/robbot/adapters/controllers/dashboard_controller.py` | Function | Controller endpoint for generating AI response time reports. |

### Integrations (WAHA/WhatsApp)

Handling external communication via WhatsApp is managed by the WAHA integration layer.

| Export | Location | Type | Description |
| :--- | :--- | :--- | :--- |
| `WAHAService` | `back/src/robbot/services/waha_service.py` | Class | Central service for interacting with the WAHA gateway. |
| `block_contact` | `back/src/robbot/adapters/controllers/waha_controller.py` | Function | API endpoint handler for blocking a contact via the WhatsApp integration. |
| `WAHAMessagePayload` | `back/src/robbot/schemas/webhooks.py` | Schema | The Pydantic schema used to validate incoming WhatsApp webhook payloads. |

### Frontend Utilities

| Export | Location | Type | Description |
| :--- | :--- | :--- | :--- |
| `cn` | `frontend/src/lib/utils.ts` | Function | Utility function using `clsx` for conditionally combining Tailwind CSS class names. |
| `fetchApi` | `frontend/src/lib/api.ts` | Function | Wrapper around the native `fetch` API, adding standardized error handling and authorization logic. |
| `ModeToggle` | `frontend/src/components/mode-toggle.tsx` | Component | UI component allowing users to switch between light and dark themes. |

---

## 4. Getting Started

To begin working on the project, follow these steps:

1.  Read the **Project Overview** and **Architecture Notes** to grasp the fundamental concepts.
2.  Consult the **Development Workflow** for local environment setup instructions.
3.  Use the structure guides above to locate relevant code:
    *   For UI changes, consult `frontend/src/app/styleguide/`.
    *   For business logic, investigate `back/src/robbot/services/`.
    *   For API structure, review `back/src/robbot/api/v1/routers`.
