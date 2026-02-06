---
status: filled
---
# Project Overview: Clinica Go
 (Conversational CRM Platform)

This document provides a comprehensive overview of the **Go** codebase, outlining its purpose, high-level architecture, technology stack, and core development patterns.

## 1. Project Summary and Goals

Clinica Go is a sophisticated clinical or customer relationship management (CRM) platform designed to automate lead interactions, manage conversations, and provide robust, real-time analytics.

**Key Objectives:**

*   **Conversational AI:** Implement advanced features for automated lead qualification and communication via external platforms like WhatsApp (WAHA).
*   **Workflow Automation:** Orchestrate complex conversation flows (Contexts) and manage lead handoffs and escalations (e.g., `ContextService`, `HandoffController`).
*   **Data and Analytics:** Offer real-time dashboards, performance reports, and conversion metrics for administrators and agents (e.g., `AnalyticsRepository`, `DashboardController`).
*   **Security:** Ensure secure access via robust authentication, Multi-Factor Authentication (MFA), and comprehensive audit logging (e.g., `AuthService`, `MfaService`, `AuditLogRepository`).

The system benefits clinical staff, agents, and administrators by centralizing communications and automating high-volume tasks, leading to improved efficiency and data-driven decision-making.

## 2. High-Level Architecture

The project utilizes a modern **monorepo structure** divided into a Python-based backend API (`back/`) and a Next.js frontend application (`frontend/`).

### Monorepo Structure

| Directory | Component | Primary Technology | Description |
| :--- | :--- | :--- | :--- |
| `back/` | **Backend API & Services** | Python, FastAPI, SQLAlchemy | Contains all business logic, data persistence, AI services, and external integrations (e.g., WAHA). This is where the core logic resides. |
| `frontend/` | **Web Application** | Next.js, TypeScript, React | The user interface, handling routing, state management, and interaction with the backend API. |
| `back/alembic/` | Database Migrations | Python, Alembic | Manages schema changes for the relational database. |

### Core Architectural Layers (Backend - `back/src/robbot/`)

The backend implements the **Clean Architecture** model, utilizing Dependency Injection (DI) to enforce strict separation of concerns and high testability.

| Layer | Directories | Key Exports/Examples | Responsibility |
| :--- | :--- | :--- | :--- |
| **Controllers** | `adapters/controllers`, `api/v1` | `UserController`, `ConversationController` | Handles HTTP request/response cycle, delegates work, and handles input validation. |
| **Services** | `services/` | `LeadService`, `WAHAService`, `PlaybookService` | Contains the core business logic and orchestrates domain entities and external resources. |
| **Domain/Schemas** | `domain/`, `schemas/` | `Conversation`, `UserIn`, `MetricData` | Defines core business entities, value objects, and Pydantic schemas for data validation and transfer. |
| **Infrastructure/Data** | `infra/db/models`, `adapters/repositories` | `UserModel`, `AnalyticsRepository` | Handles persistence logic (SQLAlchemy models) and abstracts database access using the Repository pattern. |
| **Utils/Common** | `common/` | Utility functions for various tasks. | Shared utilities and helpers. |

### Frontend Structure (`frontend/src/`)

The frontend follows Next.js conventions, organizing code based on features and functionality.

| Directory | Description |
| :--- | :--- |
| `app/` | Root routing, page components (e.g., `(auth)`, `styleguide`, `reset`). |
| `components/` | Reusable React components. `components/ui` houses generalized components (`Button`, `Input`). |
| `hooks/` | Custom hooks for state management and side effects (e.g., `useAuth`, `useFormFeedback`). |
| `lib/` | Core utilities, configuration, and API interaction wrappers (e.g., `api.ts`, `utils.ts`, `validations/`). |
| `services/` | Wrappers for interacting with specific parts of the backend API (e.g., `authService.ts`). |

## 3. Technology Stack

| Component | Technology | Key Files/Examples | Notes |
| :--- | :--- | :--- | :--- |
| **Backend Framework** | FastAPI (Python) | `back/src/robbot/main.py` | High-performance API server. |
| **Data Validation** | Pydantic (Python) | `back/src/robbot/schemas/*` | Ensures data integrity for all inputs and outputs. |
| **Database** | SQLAlchemy (Python) | `back/src/robbot/infra/db/models/*` | ORM for persistent data management. |
| **Frontend Framework** | Next.js (React/TS) | `frontend/src/app/` | Handles routing, server components, and overall application structure. |
| **Language** | TypeScript | Entire `frontend/` | Used extensively for type safety and code quality. |
| **Styling** | Tailwind CSS | `frontend/src/lib/utils.ts` (`cn` function) | Utility-first styling framework. |
| **API Client (FE)** | Custom `fetchApi` | `frontend/src/lib/api.ts` | Standardizes API communication, authorization, and error handling. |

## 4. Key Development Patterns

### Dependency Injection (DI) in the Backend

The backend utilizes a comprehensive Dependency Injection container to manage the instantiation and lifecycle of services, controllers, and repositories.

*   **Principle:** Dependencies are requested, not created, within components.
*   **Benefits:** Highly improved unit testing, easier maintenance, and better control over service lifetimes (e.g., database sessions).
*   **Verification:** Integration tests such as `back/tests/unit/test_di_container.py` are dedicated to verifying the correct setup and integrity of the DI container.

### API Interaction and Error Handling (Frontend)

The frontend standardizes how it communicates with the API to ensure consistency.

*   **Centralized Fetching:** The `fetchApi` function in `frontend/src/lib/api.ts` is the primary mechanism for all network requests.
*   **Error Normalization:** `normalizeApiError` ensures that various backend error formats are transformed into a standard, consumer-friendly format, often used in conjunction with the `useFormFeedback` hook.

```typescript
// frontend/src/lib/api.ts
export function fetchApi<T>(url: string, options: RequestInit = {}): Promise<T> {
  // Handles token injection, base URL, and standardized error parsing
}
```

### Component Driven Development (Frontend)

The `styleguide` is central to frontend development, serving as a living documentation and testing ground for UI components.

*   **Location:** `frontend/src/app/styleguide/`
*   **Purpose:** To showcase component variations, usage examples, and design tokens, ensuring visual consistency across the application. Developers should reference this area when building new features.

## 5. Core System Functionalities

| Functionality Area | Description | Key Modules/Components |
| :--- | :--- | :--- |
| **Conversation Orchestration** | Manages the complex, multi-step flow of customer interactions, utilizing AI tools and contexts. | `MessagePipeline`, `IntentDetector`, `ResponseGenerator`, `ContextOrchestrationMixin` |
| **Lead Management** | Handling lead assignment, qualification, and status changes. | `LeadService`, `LeadController`, `LeadModel` |
| **Authentication & Users** | User signup, sign-in, session management, MFA, and user blocking/roles. | `AuthService`, `MfaService`, `UserController`, `BlockUserRequest` |
| **External Integration** | Interface with external messaging platforms, specifically WhatsApp (WAHA). | `WAHAService`, `WahaController` |
| **Reporting & Metrics** | Real-time and historical analytics on performance, conversion, and bot autonomy. | `AnalyticsRepository`, `DashboardController`, `metrics_schemas.py` |

## 6. How to Locate Code

Developers new to the project can use the following directory mapping to quickly find relevant code sections:

| Goal | Relevant Directories | Example Exports |
| :--- | :--- | :--- |
| **Understand API endpoints** | `back/src/robbot/api/v1/routers/` | `TestPhase1Auth`, `TestPhase5Conversations` |
| **Inspect database models** | `back/src/robbot/infra/db/models/` | `AuditLogModel`, `AuthSessionModel` |
| **Implement business logic** | `back/src/robbot/services/` | `UserService`, `QueueService` |
| **Check frontend validation** | `frontend/src/lib/validations/` | `SignInValues`, `SignUpValues` |
| **Build a UI component** | `frontend/src/components/ui/` | `Input`, `Checkbox`, `Select` |
| **See usage of dependencies** | `back/tests/unit/test_di_controllers.py` | (Tests showing how services are injected into controllers) |
| **Configure prompts/AI** | `back/src/robbot/config/prompts` | (Configuration files defining AI conversational parameters) |
