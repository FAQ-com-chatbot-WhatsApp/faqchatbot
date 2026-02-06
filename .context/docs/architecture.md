---
status: filled
---
# System Architecture: Clinica Go


**Status:** filled
**Version:** 1.0.0

## 1. Architectural Philosophy

Clinica Go is designed as a modular, resilient, and highly testable platform. It adheres to **Clean Architecture** principles on the backend and a **Feature-Driven** approach on the frontend. The system focuses on strict separation of concerns, utilizing Dependency Injection (DI) to decouple business logic from infrastructure.

---

## 2. System Overview

The application follows a monorepo structure with two primary decoupled systems:

1.  **Backend (API & Workers):** A Python/FastAPI ecosystem responsible for the core business logic, AI orchestration, and data persistence.
2.  **Frontend (Web UI):** A Next.js application that provides the user interface for administrators, agents, and lead management.

---

## 3. Backend Architecture (`back/`)

The backend is built with **FastAPI** and implements a hexagonal-inspired layout.

### Layers and Responsibility

| Layer | Path | Responsibility |
| :--- | :--- | :--- |
| **API / Routers** | `src/robbot/api/v1` | Entry points, path routing, and HTTP status codes. |
| **Controllers** | `src/robbot/adapters/controllers` | Request/Response orchestration, input validation (Pydantic), and calling services. |
| **Services** | `src/robbot/services/` | **Core Business Logic Coordinator**. Organized by domain context: |
| ↳ Bot | `services/bot/` | Conversation orchestration, pipeline, and state management. |
| ↳ Leads | `services/leads/` | Lead management, scoring, and conversion logic. |
| ↳ AI | `services/ai/` | Persistent memory, intent detection, context building. |
| ↳ Communication | `services/communication/` | Transcription, text sanitization, message processing. |
| ↳ Handoff | `services/handoff/` | Human-bot transition management. |
| **Domain** | `src/robbot/domain/` | **Rich Domain Entities & Value Objects**: |
| ↳ Leads | `domain/leads/` | Lead entity, LeadMapper, status rules. |
| ↳ Conversations | `domain/conversations/` | Conversation entity, ConversationMapper. |
| ↳ Shared | `domain/shared/` | Enums, Value Objects (LeadScore, PhoneNumber, SpinPhase). |
| **Repositories** | `src/robbot/infra/persistence/repositories/` | Data access abstraction. Maps SQL models to domain. |
| **Infrastructure** | `src/robbot/infra/` | External integrations and persistence: |
| ↳ Persistence | `infra/persistence/models/` | SQLAlchemy database models. |
| ↳ Integrations | `infra/integrations/` | WAHA, LLM clients, Vector store (Chroma). |

### Dependency Injection (DI)
The backend uses a central container to manage dependencies. This allows for:
- Easy swapping of implementations (e.g., using a MockRepository in tests).
- Automated management of SQLAlchemy sessions (Unit of Work pattern).
- Lifecycle control for singleton services like the `WAHAService`.

---

## 4. Frontend Architecture (`frontend/`)

The frontend is a **Next.js** application utilizing **React Server Components** and **Client Components** where appropriate.

### Design System and Components
- **Shadcn UI + Tailwind CSS:** Core UI library for accessible, styled components.
- **Styleguide (`/styleguide`):** A living documentation area for testing and visualizing all UI primitives (`Button`, `Card`, `Input`, etc.).
- **Centralized API Client:** The `fetchApi` utility in `src/lib/api.ts` handles authorization, logging, and standardized error normalization via `normalizeApiError`.

---

## 5. Technology Stack

| Component | Technology | Role |
| :--- | :--- | :--- |
| **API Framework** | FastAPI | High-performance async API server. |
| **Database** | PostgreSQL | Relational data store via SQLAlchemy. |
| **Migrations** | Alembic | Database schema versioning. |
| **AI/LLM** | Google Gemini | Core AI engine for intent detection and response generation. |
| **Messaging** | WAHA | WhatsApp gateway integration. |
| **Background Jobs** | RQ (Redis Queue) | Handling asynchronous tasks and webhooks. |

---

## 6. Development & Testing

- **Testing Strategy:** The project maintains a robust suite including Unit (Services), Integration (DI & DB), and API (End-to-End) tests.
- **Verification Patterns:** Tests in `back/tests/unit/test_di_controllers.py` serve as a reference for setting up test contexts with dependency overrides.
- **Validation:** Input is validated using Pydantic on the backend and Zod on the frontend, ensuring contract consistency.
