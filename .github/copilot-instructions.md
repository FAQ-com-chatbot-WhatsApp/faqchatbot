# Clinica Go Project Guidelines

## 1. Core Project Documentation

*   [**Product Vision (PRODUCT.md)**](../PRODUCT.md): High-level vision, target users, and core features.
*   [**System Architecture (ARCHITECTURE.md)**](../ARCHITECTURE.md): Overall system architecture, technology stack, and design principles.
*   [**Contributing Guidelines (CONTRIBUTING.md)**](../CONTRIBUTING.md): Git workflow, code standards, and testing requirements.

## 2. Backend Documentation (`/back/docs`)

The backend documentation is organized to mirror the source code structure.

*   [**Services (`/back/docs/services`)**](./back/docs/services/README.md): Detailed documentation for each business logic service (e.g., `AuthService`, `LeadService`). It also includes documentation for foundational, cross-cutting concerns like security, logging, and custom exceptions.
*   [**Architecture Decisions (ADRs)**](./back/docs/architecture/decisions/): Records of key architectural decisions and their rationale.
*   [**Implementation Plan Template**](./.github/prompts/plan-template.md): Use this template for documenting new features or services.

## 3. Key Project Principles

*   **Clean Architecture (Adapted)**: The backend follows a pragmatic adaptation of Clean Architecture. Business logic is in `services`, data access in `repositories`, and HTTP handling in `controllers`.
*   **Documentation First**: New features should be documented following the structure in `plan-template.md`.
*   **English as Standard**: All new code, documentation, and commit messages should be in English.
*   **Security and Compliance**: As a healthcare application, security (MFA, session management) and data privacy (LGPD) are critical.

## 4. 3-Step Workflow (Analyze, Execute, Verify)

To ensure completeness and prevent errors, every task must follow this workflow:

1.  **Analyze**: Before making changes, fully understand the request and the context. Identify all files, directories, and dependencies involved. Formulate a clear plan.
2.  **Execute**: Perform the planned actions, such as creating, editing, or moving files and directories.
3.  **Verify**: After execution, double-check the work. Ensure that all changes were made correctly and that no artifacts (like old files or empty directories) were left behind. Confirm that the final state matches the initial goal.

*Suggest to update these documents if you find any incomplete or conflicting information during your work.*
