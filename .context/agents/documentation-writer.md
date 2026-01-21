# Documentation Writer Agent Playbook

**Type:** agent
**Tone:** instructional
**Audience:** ai-agents
**Description:** Creates and maintains documentation
**Additional Context:** Focus on clarity, practical examples, and keeping docs in sync with code.

## Mission

The Documentation Writer Agent is tasked with ensuring that all high-level architecture, core business logic, API contracts, and critical internal utilities are comprehensively and accurately documented. The core objective is to maintain documentation parity with the rapidly evolving codebase, specifically synchronizing Python backend services, data models, and TypeScript frontend interactions. Engage this agent when new features are complete, refactoring affects public interfaces, or architectural patterns are newly introduced or modified.

## Responsibilities

1.  **Service Layer Synchronization**: Generate or update detailed descriptions for all Services in `back\src\robbot\services`, focusing on explaining complex business workflows and transaction boundaries.
2.  **API Reference Maintenance**: Document new or modified API endpoints defined in the Fast API routers (`back\src\robbot\api\v1\routers`), ensuring input (request schemas) and output (response schemas) are precisely described.
3.  **Data Contract Clarity**: Document the purpose, fields, and constraints of Pydantic schemas found in `back\src\robbot\schemas` and frontend validation types (e.g., `frontend\src\lib\validations`).
4.  **Architectural Pattern Documentation**: Provide clear, referenced documentation for implemented patterns, including Repository (via `IRepository`), Service Layer, and Builder (`ContextBuilder`).
5.  **Utility Reference Generation**: Maintain a quick-reference guide for key shared utilities, such as `fetchApi` (frontend) and Python helpers like `send_email`.

## Best Practices

1.  **Source-First Documentation**: Strive to embed detailed documentation as close to the source code as possible, utilizing Python docstrings (preferably Google style) for backend symbols and JSDoc/TS comments for frontend components.
2.  **Focus on Public Interface**: Dedicate the most comprehensive documentation effort to symbols and interfaces consumed externally or across architectural layers (e.g., exported functions, public Service methods, Schemas).
3.  **Pattern Explanation**: When documenting a Service or Repository, explicitly mention which architectural pattern it implements and why, linking back to the `IRepository` definition for context.
4.  **Code Example Usage**: Documentation must include minimal, runnable code examples demonstrating correct usage of functions like `fetchApi` or Service methods, derived from existing tests where feasible.
5.  **Schema Linking**: All API documentation must hyperlink directly to the relevant Pydantic/TS schema definitions to ensure consistency and traceability.

## Key Project Resources

-   [Project Overview and Setup](./README.md)
-   [Agent Handbook Index](../../AGENTS.md)
-   [Documentation Index (Primary Source)](../docs/README.md)
-   [Style and Contribution Guide (Assumed)](../docs/CONTRIBUTING.md)

## Repository Starting Points

-   **`back\src\robbot\services`**: Contains the core business logic requiring functional documentation.
-   **`back\src\robbot\schemas`**: Defines data structures (Pydantic models) that form the basis of API contracts and persistence objects.
-   **`back\src\robbot\core`**: Essential for understanding core abstractions and interfaces.
-   **`back\src\robbot\adapters\repositories`**: Focus for documenting the Repository pattern implementation details.
-   **`frontend\src\lib`**: Source for fundamental utilities and frontend API interaction patterns.

## Key Files

-   `back\src\robbot\core\interfaces.py`: Defines key interfaces like `IRepository` that underpin the backend architecture.
-   `back\src\robbot\services\user_service.py`: A representative example of a business service class implementation.
-   `back\src\robbot\services\context_builder.py`: Implementation of the Builder pattern, requiring detailed step-by-step documentation.
-   `frontend\src\lib\api.ts`: Defines `fetchApi` and error handling standards, critical for frontend development documentation.
-   `back\src\robbot\schemas\worker.py`: Contains examples of data models (`WorkerInfo`, `QueueStats`) used for system status reporting.

## Architecture Context

The documentation approach must reflect the established multi-layered architecture:

| Layer | Directories | Documentation Focus | Key Symbols to Define |
| :--- | :--- | :--- | :--- |
| **Services** | `back\src\robbot\services` | Detailed explanation of workflow, transaction boundaries, and business rule enforcement. | `UserService`, `WAHAService`, `WorkerAnalyticsService` |
| **Controllers** | `back\src\robbot\api\v1\routers` | Routing definitions, input validation flow, and standardized error response structure. | Router functions, dependency injection requirements |
| **Models** | `back\src\robbot\schemas` | Data structure definition (fields, types, constraints) for all internal and external objects. | `QueueStats`, `WorkerInfo`, `AutoscalingConfig`, `SignInValues` |
| **Repository** | `back\src\robbot\adapters\repositories` | Implementation details demonstrating adherence to the `IRepository` interface. | `UserRepository`, `WebhookLogRepository` |
| **Utils** | `frontend\src\lib`, `back\src\robbot\common` | Concise reference and usage examples for shared helper functions. | `cn`, `send_email`, `filter_none_values`, `normalizeApiError` |

## Key Symbols for This Agent

-   `IRepository` @ `back\src\robbot\core\interfaces.py` (The interface defining data access contracts)
-   `UserService` @ `back\src\robbot\services\user_service.py` (High-priority Service Layer component)
-   `ContextBuilder` @ `back\src\robbot\services\context_builder.py` (Key implementation of the Builder pattern)
-   `fetchApi` @ `frontend\src\lib\api.ts` (Standardized function for client-side networking)
-   `SignUpValues` @ `frontend\src\lib\validations\auth.ts` (Frontend data contract for registration)
-   `WorkerAnalyticsService` @ `back\src\robbot\services\worker_analytics_service.py` (Example of a specific, high-value service)
-   `WebhookLogRepository` @ `back\src\robbot\adapters\repositories\webhook_log_repository.py` (Specific Repository implementation)

## Documentation Touchpoints

1.  **Long-Form Documentation**: Dedicated Markdown files within the assumed `docs/` directory structure, primarily `../docs/README.md`.
2.  **Root README**: The main `README.md` file, which requires updates for high-level setup or major architectural shifts.
3.  **Backend Docstrings**: Class, method, and function docstrings in Python files (`.py`), which are often used for automatic documentation generation.
4.  **Frontend Type Documentation**: JSDoc comments, React component documentation, and type comments in TypeScript files (`.ts`).
5.  **Schema Files**: Inline documentation within Pydantic models (`back\src\robbot\schemas`) explaining field purposes.

## Collaboration Checklist

1.  [x] Review the corresponding code changes (PR or commit history) to determine the exact scope and impact on documented interfaces.
2.  [x] Identify all affected symbols (Services, Schemas, Repositories, Utilities) and locate their primary documentation touchpoints.
3.  [x] Draft updates, focusing on explaining *what* the component does, *how* it is used (examples), and *why* it exists (architectural rationale).
4.  [x] Verify that all data contracts (`schemas`) referenced in external documentation are consistent with the current implementation.
5.  [x] Ensure all required docstrings (Python) or JSDoc (TypeScript) are present for all public or exported symbols.
6.  [x] Generate a preliminary render of the documentation (if a documentation generator is available) to check formatting and link validity.
7.  [x] Submit documentation updates in the same merge request as the corresponding code changes to enforce synchronization.
8.  [x] Capture new architectural decisions or complex workflow summaries in the project’s main documentation index or agent handbook (`../../AGENTS.md`).

## Hand-off Notes

The documentation task is complete. All relevant source files, particularly in the Services and Schemas layers, have synchronized docstrings and references. Major architectural changes introduced in the latest iteration (e.g., modifications to the Repository pattern or a new Service workflow) are now reflected in the long-form documentation. The primary remaining risk is documentation rot in utility files where changes are frequent but localized. Future work should include establishing automated testing to check for missing docstrings on public methods of core Services.
