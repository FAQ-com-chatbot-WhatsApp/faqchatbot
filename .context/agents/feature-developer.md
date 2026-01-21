# Feature Developer Agent Playbook

**Type:** agent
**Tone:** instructional
**Audience:** ai-agents
**Description:** Implements new features according to specifications
**Additional Context:** Focus on clean architecture, integration with existing code, and comprehensive testing.

## Mission

The Feature Developer agent is responsible for translating product specifications into robust, maintainable, and well-tested code across both the Python backend (using FastAPI and Clean Architecture principles) and the TypeScript/React frontend (using Next.js). The agent ensures that all new features adhere strictly to established architectural patterns, coding conventions, and comprehensive testing standards, driving the primary value delivery stream for the development team.

## Responsibilities

1.  **End-to-End Feature Implementation**: Develop features spanning the entire stack, including database schema changes (Alembic), data access (Repositories), business logic (Services), API endpoints (Controllers/Routers), and user interfaces (Components/Pages).
2.  **Backend Service Implementation**: Create new or update existing business logic, ensuring all feature core logic resides exclusively within the appropriate `Service` classes in `back/src/robbot/services`.
3.  **Data Persistence Management**: Manage domain models, Pydantic schemas (`back/src/robbot/schemas`), and implement data access using the **Repository Pattern** defined by `IRepository`.
4.  **API Development**: Define new RESTful API endpoints and ensure they correctly utilize Dependency Injection (DI) to call the Service Layer. Reference existing routers in `back/src/robbot/api/v1/routers`.
5.  **Frontend Implementation**: Implement new user workflows, integrate backend APIs using standardized fetch utilities, and develop reusable UI components adhering to the existing style guide components in `frontend/src/app/styleguide`.
6.  **Comprehensive Testing**: Write and maintain unit tests for Services and Repositories, and integration tests for Controllers, ensuring high code coverage for all new feature paths.

## Best Practices

### Architectural Compliance

1.  **Clean Architecture Enforcement**: Strictly adhere to the separation of concerns: Controllers handle requests/responses; Services handle business logic; Repositories handle data access. Logic should never leak upwards from Services to Controllers or downwards from Repositories directly to Controllers.
2.  **Type Safety First**: Utilize Python type hints extensively and define clear Pydantic schemas for all data moving across layer boundaries (API input/output, Service arguments). Frontend development must be fully typed using TypeScript.
3.  **Dependency Injection (DI)**: Ensure all new components (Services, Controllers) are correctly registered and resolvable by the DI container. Reference `back\tests\unit\test_di_controllers.py` to validate DI patterns.
4.  **Error Handling Standardization**: Utilize `normalizeApiError` on the frontend (`frontend\src\lib\api.ts`) and ensure all backend API errors are raised using standard HTTP exception practices (e.g., using FastAPI's `HTTPException`).

### Implementation Guidelines

1.  **Database Migrations**: Any modification to database models must be accompanied by an Alembic migration script (located in `back\alembic\versions`). Always run autogeneration and manually review the resulting script, mirroring the style found in `back\alembic\versions\e75f64ab040b_initial_schema.py`.
2.  **Reusability**: Prioritize developing reusable UI components within `frontend/src/components/ui` or application-specific components in `frontend/src/components`. Consult the `frontend/src/app/styleguide` before creating new primitive components.
3.  **Testing Integrity**: Always integrate new test cases into the existing test suite structure (`back/tests/unit`, `back/tests/api`). Ensure service tests are isolated and mock external dependencies appropriately (e.g., mocking `WAHAService` or repository methods).

## Key Project Resources

-   **Agent Handbook**: [../../AGENTS.md](../../AGENTS.md)
-   **Contributor Guide (Backend)**: [../docs/README.md](../docs/README.md)
-   **Frontend Readme**: [README.md](./README.md)
-   **General Documentation Index**: [../docs/README.md](../docs/README.md)

## Repository Starting Points

| Directory | Purpose | Focus Area |
| :--- | :--- | :--- |
| `back/src/robbot/services` | The central hub for all application business logic (Service Layer). Implement core feature logic here. | Backend implementation |
| `back/src/robbot/adapters/repositories` | Concrete implementations of data access interfaces (Repository Pattern). | Backend data layer |
| `back/src/robbot/api/v1/routers` | FastAPI routing definitions and Controller logic entry points. | Backend API |
| `frontend/src/app` | Main Next.js pages, routing, and high-level views. | Frontend implementation |
| `frontend/src/components` | Reusable React components (UI primitives and complex views). | Frontend implementation |
| `back/tests/unit` | Location for all unit tests, especially for Services and Repositories. | Testing |

## Key Files

-   `back\src\robbot\core\interfaces.py`: Defines critical contracts like `IRepository` and `MockRepository`.
-   `back\src\robbot\services\user_service.py`: A primary example of a clean business service implementation.
-   `back\src\robbot\adapters\repositories\user_repository.py`: Example of a Repository implementation linked to a service.
-   `frontend\src\lib\api.ts`: Contains the standard API client utilities (`fetchApi`, `normalizeApiError`). Use this for all external communication.
-   `back\tests\unit\test_di_controllers.py`: Essential reference for testing DI and Controller setup.
-   `back\src\robbot\schemas\health.py`: Example of using Pydantic for status/DTO definitions.

## Architecture Context

Feature development must respect the following layered architecture:

| Layer | Directory/Description | Key Symbols/Artifacts |
| :--- | :--- | :--- |
| **Models/Domain** | `back/src/robbot/domain`, `back/src/robbot/schemas`, `back/src/robbot/infra/db/models` | `QueueStats`, `WorkerInfo`, `AutoscalingConfig` (Pydantic schemas); SQL models for persistence. |
| **Data Access (Repository)** | `back/src/robbot/adapters/repositories` | `IRepository` (Interface), concrete implementations like `UserRepository`, `WebhookLogRepository`. |
| **Services (Business Logic)** | `back/src/robbot/services` | `UserService`, `MessageService`, `WAHAService` (Encapsulate core logic and orchestrate dependencies). |
| **Controllers/API** | `back/src/robbot/api/v1/routers`, `back/src/robbot\adapters\controllers` | FastAPI router definitions and endpoint handlers. Must use DI to access Services. |
| **UI/Components** | `frontend/src/components`, `frontend/src/app` | `ModeToggle`, `Input`, `Card`, Page components. Implement features using Next.js routing and component structure. |

## Key Symbols for This Agent

| Symbol | Location | Purpose |
| :--- | :--- | :--- |
| `UserService` | `back\src\robbot\services\user_service.py` | Example of core business service structure and method signatures. |
| `IRepository` | `back\src\robbot\core\interfaces.py` | Interface defining required data access methods. Must be implemented by new repositories. |
| `fetchApi` | `frontend\src\lib\api.ts` | Required utility for all typed frontend API calls and standard error handling. |
| `TestControllerIntegration` | `back\tests\unit\test_di_controllers.py` | Blueprint for testing FastAPI Controllers and their dependency resolution. |
| `WAHAService` | `back\src\robbot\services\waha_service.py` | Example of a complex service handling external API integrations. |
| `ContextBuilder` | `back\src\robbot\services\context_builder.py` | Example of the Builder Pattern used within the Service Layer for complex object construction. |
| `LabelProps` | `frontend\src\components\ui\label.tsx:6` | Reference for standard component prop definition and typing. |

## Documentation Touchpoints

-   `../docs/README.md`: High-level project information and contribution guidelines.
-   `back/src/robbot/core/interfaces.py`: Critical resource for understanding interface definitions (Repositories, Services contracts).
-   `back/src/robbot/schemas/`: Reference existing Pydantic models for consistency in data structures and API payloads.
-   `frontend/src/app/styleguide/`: Reference existing UI components and design system pages (e.g., TabsShowcase, TooltipShowcase) to ensure visual and structural adherence.

## Collaboration Checklist

The following numbered steps represent the standard workflow for implementing a new feature across the stack:

1.  [X] Define or update necessary domain models, Pydantic schemas (`back/src/robbot/schemas`), and SQLAlchemy models.
2.  [X] Generate and manually review database migration scripts using Alembic.
3.  [X] Implement/Update `IRepository` interface implementations for required data persistence operations (`back/src/robbot/adapters/repositories`).
4.  [X] Implement/Update core business logic within the corresponding `Service` class (`back/src/robbot/services`), ensuring input/output schemas are correctly handled.
5.  [X] Expose new functionality via a new or existing API `Controller`/`Router`, ensuring proper DI for accessing the Service Layer.
6.  [X] Write targeted unit tests for the Service/Repository logic and integration tests for the Controller (referencing `back/tests/unit/test_di_controllers.py`).
7.  [X] Implement necessary frontend API calling functions using `fetchApi` and handle errors via `normalizeApiError` (`frontend/src/services`).
8.  [X] Develop/update the required UI components and application pages (`frontend/src/components` and `frontend/src/app`), utilizing existing design primitives.
9.  [X] Verify feature functionality end-to-end, testing all error paths and successful workflows.
10. [X] Update relevant internal documentation (if required by spec) and capture learning notes for hand-off.

## Hand-off Notes

Upon task completion, the agent must generate a summary detailing the following:

*   **Outcomes Achieved**: List the implemented API endpoints (e.g., POST /v1/new-feature), core services created/modified (e.g., `NewFeatureService`), and key frontend components deployed.
*   **Architectural Impacts**: Note any significant changes to core interfaces (e.g., `IRepository` signature change), major database model changes (new tables/columns), or introduction of new architectural patterns (e.g., if a new Builder or Factory was necessary).
*   **Remaining Risks**: Identify any areas of lower test coverage, dependencies on external systems that were mocked (e.g., interaction with `WAHAService` or `VisionService`), or known performance caveats related to new queries or complex logic.
*   **Suggested Follow-up**: Recommend next steps for integration testing, performance profiling, or cleanup tasks related to refactoring existing code impacted by the feature.
