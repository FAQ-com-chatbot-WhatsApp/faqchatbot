# Glossary and Domain Concepts

This document provides definitions for key terms, concepts, acronyms, and architectural components within the `clinica_go` platform, which is internally referred to as "Robbot." Understanding this vocabulary is essential for working with the backend services, frontend components, and domain models.

---

## I. Core Domain Entities

These are the primary objects managed and tracked by the system.

| Term | Definition | Related Code/Artifacts |
| :--- | :--- | :--- |
| **Lead (Contact)** | A potential customer or end-user whose information has been captured. The lead is the subject of the conversation and is tracked through various stages of qualification (**Lead Maturity**). | `lead_controller.py`, `AssignRequest`, `LeadMaturity` (Enum in `back\src\robbot\domain\lead.py`) |
| **Conversation** | The complete history of interaction between a Lead and the system (Bot or Agent). It includes all messages, state changes, and metadata (like tags, assignment status). | `handoff_controller.py`, `ActiveConversationSchema`, `ConversationStage` (Enum) |
| **Playbook** | A structured, predefined, and sequential workflow used by the Bot to manage a conversation. Playbooks dictate the flow of questions, information gathering, and qualification steps. | `playbook_step_controller.py`, `PlaybookService`, `TestPhase3Playbooks` (API Tests) |
| **Message** | A single piece of communication, either **Inbound** (from the contact) or **Outbound** (from the Bot or Agent), often transported via **WAHA**. | `MessageService`, `MessageDirection` (Enum), `TestPhase4Messages` (API Tests) |
| **Agent / Secretary** | A human user responsible for handling escalated conversations, managing leads, and performing manual **Handoffs**. This role typically interacts with the frontend dashboard and holds specific `UserRole` permissions. | `UserRole` (Enum), `authenticated_secretary` (Fixture), `UserService` |
| **WAHA** | **W**hats**A**pp **H**andler/API. The external service or module responsible for real-time integration with the WhatsApp Business API for sending and receiving messages. | `waha_controller.py`, `WAHAService`, `TestPhase2WAHA` (API Tests) |

## II. System Operations and States

These terms describe key processes or states within the conversational flow and platform management.

| Term | Definition | Related Code/Artifacts |
| :--- | :--- | :--- |
| **Handoff** | The process of transferring control of an active **Conversation** from the automated **Bot** to a human **Agent**. This typically occurs when the Bot's logic is exhausted or when the Lead requests human intervention. | `handoff_controller.py`, `AssignConversationRequest`, `TestPhase14Handoff` |
| **Bot Autonomy** | A crucial system **Metric** measuring the percentage of conversations successfully resolved by the **Bot** without requiring a **Handoff** to a human Agent. This is a key performance indicator (KPI). | `bot_autonomy` (Controller method), `BotAutonomyMetricsSchema` |
| **Lead Maturity** | A state assigned to a **Lead** indicating their qualification level (e.g., `COLD`, `WARM`, `HOT`). This status guides the subsequent steps in a **Playbook** and dictates Agent priorities. | `back\src\robbot\domain\lead.py` (Domain Model), `LeadService` |
| **Audit Log** | A secure, chronological record of significant user actions (e.g., login attempts, configuration changes, user blocking) used for security monitoring and compliance. | `AuditLogEntry`, `AuditService`, `AuditLogModel`, `AuditLogRepository` |
| **Autoscaling** | The mechanism used to monitor and dynamically adjust the number of background workers processing asynchronous tasks (e.g., sending messages or executing parts of a Playbook) based on current queue load. | `AutoscalingRecommendation`, `AutoscalingConfig` (in `back\src\robbot\schemas\worker.py`), `WorkerAnalyticsService` |

## III. Architectural & Technical Concepts

These concepts relate to the structure and underlying technologies of the platform.

| Term | Definition | Related Code/Artifacts |
| :--- | :--- | :--- |
| **Metrics** | Quantitative data points used to assess system performance, covering areas like response time, conversion rates, and bot autonomy. These reports are often generated for the dashboard. | `metrics_schemas.py`, `BasePercentileSchema`, `AnalyticsRepository`, `TestPhase9Metrics` |
| **DI (Dependency Injection)** | A fundamental architectural pattern in the Python backend used to manage dependencies between controllers, services, and repositories, making components easily testable and replaceable. | `TestDIInControllers`, `TestDIContainerIntegration` (Unit Tests) |
| **Repository** | An abstraction layer between the application logic (services/controllers) and the persistence layer (database models). Repositories handle all data storage and retrieval operations, adhering to the repository pattern. | `BaseRepository`, `AnalyticsRepository`, `AuthSessionRepository`, `back\src\robbot\adapters\repositories` |
| **Schema (Pydantic)** | Data structure definitions used in the Python backend (FastAPI) to enforce data integrity, validate incoming request bodies, and standardize API response formats. | `back\src\robbot\schemas\*` (Extensive usage across all API endpoints) |
| **MFA** | **M**ulti-**F**actor **A**uthentication. A security measure requiring users to provide two or more verification factors to gain access, specifically for enhanced user login flows. | `MfaService`, `TestMfaLoginFlow`, `BackupCodesResponse` |
| **LLM** | **L**arge **L**anguage **M**odel (e.g., Gemini). The AI component responsible for interpreting user input, generating nuanced responses, and performing conversational analysis tasks (e.g., intent detection). | `TestPhase6Gemini`, `AIStatsResponse`, `IntentDetector`, `ResponseGenerator` |

## IV. Frontend Concepts

Terms specific to the Next.js/React user interface located in the `frontend/` directory.

| Term | Definition | Related Code/Artifacts |
| :--- | :--- | :--- |
| **Styleguide** | A collection of UI components, design patterns, and usage examples, serving as a single source of truth for frontend design and development standards, accessible via `/styleguide`. | `frontend\src\app\styleguide\page.tsx`, `AvatarShowcase`, `NavSection`, `MessageBubbleShowcase` |
| **`cn` utility** | A utility function (often shorthand for "class names") utilized for conditionally concatenating and managing CSS class strings in React components, typically wrapping a library like `clsx` or `tailwind-merge`. | `frontend\src\lib\utils.ts` |
| **Form Validation Types** | TypeScript types and corresponding Zod schemas defining the expected structure and validation rules for critical user forms, such as sign-in and sign-up, ensuring data consistency. | `SignInValues`, `SignUpValues` (in `frontend\src\lib\validations\auth.ts`) |
| **`useAuth`** | A custom React hook built around React Context that provides access to the current user's authentication state and exposes methods like `login` and `signup` for application-wide authentication control. | `frontend\src\hooks\useAuth.ts`, `AuthLayout` |
| **UI Components** | The set of reusable React components (often built with Shadcn UI) located under `frontend/src/components/ui` that serve as the fundamental building blocks for the application interface (e.g., `Button`, `Input`, `Select`). | `frontend\src\components\ui\*`, `InputShowcase`, `DropdownMenuShowcase` |
