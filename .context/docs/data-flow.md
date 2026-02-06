---
status: filled
---
# Data Flow and System Architecture


The Clinica Go application employs a modern, layered architecture designed for reliability, scalability, and maintainability, crucial for handling high-volume, asynchronous conversational data. The system enforces strict boundaries between layers using Dependency Injection (DI) and centers its operations around the **Conversation** entity.

## Architectural Layers

The backend follows a classic layered structure where dependencies flow inward: Controllers depend on Services, which depend on Repositories, which in turn interface with the Infrastructure (Database, External APIs).

| Layer | Directories | Responsibilities | Key Components |
| :--- | :--- | :--- | :--- |
| **API/Presentation** | `back/src/robbot/api` | Handles HTTP routing, request parsing, input validation (using Pydantic Schemas), and delegating tasks to the Service layer. | API Routers, Controllers (`user_controller.py`, `dashboard_controller.py`) |
| **Business/Service** | `back/src/robbot/services` | Contains core business logic, orchestrates complex workflows, manages transactional boundaries, and enforces business rules. | `ConversationService`, `LeadService`, `ConversationOrchestrator` |
| **Data/Repository** | `back/src/robbot/adapters/repositories` | Abstracts data persistence operations (CRUD), translating between Domain Models and Infrastructure Models. | `ConversationRepository`, `UserRepository`, `AnalyticsRepository` |
| **Domain/Models** | `back/src/robbot/domain` | Defines core business entities (e.g., `Conversation`, `Lead`) and their rules, independent of persistence technology. | Domain Models, Value Objects |
| **Infrastructure** | `back/src/robbot/infra` | Contains concrete implementations for external concerns: Database Models, Job Runners, and configuration files. | Database Models (`db/models`), `BaseJob` |

---

## Data Ingress Points

Data enters the system through two main channels, each handled by specific controllers and middleware.

### 1. Frontend API (Synchronous)

This path handles typical user-initiated actions (logins, settings updates, retrieving dashboard metrics).

| Action Type | Frontend Path | Backend Path | Notes |
| :--- | :--- | :--- | :--- |
| **Authentication** | `frontend/src/services/authService.ts` | `back/src/robbot/api/v1/auth_router.py` | Uses `AuthService` for session and token management. |
| **Data Retrieval** | `frontend/src/lib/api.ts` (`fetchApi`) | Various controllers | Standard RESTful requests, validated against Pydantic schemas in `back/src/robbot/schemas`. |
| **Validation** | `frontend/src/lib/validations` | Pydantic Schemas in backend | Input is validated both on the frontend (using Zod) and strictly on the backend (using Pydantic). |

### 2. External Messaging Webhooks (Asynchronous)

This path is critical for conversational data flow, where the system reacts to incoming messages from platforms like WhatsApp.

**Flow:**

1.  An external platform sends a message webhook.
2.  The request hits the `WAHAController` (`waha_controller.py`).
3.  The controller performs minimal validation, converts the raw payload into an internal DTO (Data Transfer Object), and immediately passes the DTO to the core service layer, typically the `OrchestratorService`, for complex, asynchronous processing.
4.  A fast, synchronous acknowledgment is sent back to the messaging platform.

---

## The Core Conversational Pipeline

The **Orchestrator Service** (`orchestrator_service.py`) is the central brain for processing inbound messages and determining the correct automated response or escalation path.

### Detailed Message Processing Flow

| Step | Component | Action | Dependencies/Outputs |
| :--- | :--- | :--- | :--- |
| **1. Ingestion** | `WAHA Controller` | Receives raw webhook, sanitizes, and prepares initial message DTO. | Calls `OrchestratorService` |
| **2. Context Retrieval** | `ConversationService` | Fetches/Creates rich domain entities (`Lead`, `Conversation`) with full history. | `ConversationRepository`, `LeadRepository` |
| **3. Analysis Pipeline** | `ConversationPipeline` | Handles media, builds context, detects intent and urgency, and updates domain state (scoring). | `TranscriptionService`, `LLMClient`, `Chroma` |
| **4. Response Generation** | `OrchestratorService` | Uses LLM to generate response based on enriched context and SPIN methodology. | `LLMClient`, `PersistentMemory` |
| **5. Handoff Check** | `Domain Rules` | Business logic inside `Conversation` and `Lead` entities decides if human intervention is needed. | `entities.py` logic |
| **6. Dispatch & Log** | `ResponseDispatcher` | Delivers message via WAHA, logs interaction, and writes LLM audit trails. | `WAHAClient`, `AnalyticsRepository` |

### State Management

Conversational state transitions (e.g., `ACTIVE` -> `ESCALATED` or `ACTIVE` -> `CLOSED`) are strictly managed within the `ConversationService` to ensure data integrity.

---

## Reliability and Resilience

### Dependency Injection (DI)

The system uses a robust DI container to manage service dependencies and lifecycle, which is extensively tested in `back/tests/unit/test_di_controllers.py` and `test_di_container.py`.

*   **Benefit:** Enables loose coupling, allowing for easy substitution of dependencies (e.g., swapping a production database repository with a mock repository for testing).
*   **Mechanism:** Ensures that database sessions are managed as 'unit-of-work' objects, guaranteeing a fresh, isolated session for each API request or background job execution.

### Asynchronous Background Processing

The application uses a separate worker framework for heavy or scheduled tasks.

*   **Jobs:** Defined by inheriting `BaseJob` (`back/src/robbot/infra/jobs/base_job.py`).
*   **Monitoring:** The `WorkerAnalyticsService` (`worker_analytics_service.py`) provides telemetry on worker performance and queue health.
*   **Failure Handling:** Jobs utilize automatic retries for transient errors. Persistent failures are moved to a Dead-Letter Queue (DLQ) to prevent process stalling and allow post-mortem analysis.

### Observability

1.  **Auditing:** The `AuditService` ensures all critical administrative changes (user management, security settings) are recorded immutably in the `AuditLogModel`.
2.  **Metrics:** The `MetricsService` and `AnalyticsRepository` constantly calculate and aggregate key performance indicators (KPIs) like Bot Autonomy, response times, and conversion rates, providing real-time operational feedback used by the Dashboard UI.
3.  **Error Handling:** Custom exceptions (e.g., `AuthException` from `custom_exceptions.py`) ensure that failures are handled gracefully at the Controller layer, translating internal errors into appropriate HTTP response codes.
