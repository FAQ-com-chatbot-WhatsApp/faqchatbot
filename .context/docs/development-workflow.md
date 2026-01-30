---
status: filled
---
# Development Workflow: Clinica Go


**Status:** filled
**Updated:** 2026-01-27

This document outlines the standard processes for setting up, developing, testing, and contributing to the **Go** repository.

---

## 1. Prerequisites & Setup

Ensure you have the following installed on your local machine:
- **Python 3.10+** (with `poetry` for dependency management).
- **Node.js 18+** (with `pnpm` or `npm` recommended).
- **Docker & Docker Compose** (for running the database, Redis, and WAHA).

### Local Environment Setup

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/organization/go.git
    cd go
    ```
2.  **Backend Setup:**
    ```bash
    cd back
    poetry install
    cp .env.example .env  # Update variables AS needed
    ```
3.  **Frontend Setup:**
    ```bash
    cd ../frontend
    npm install
    cp .env.example .env.local
    ```
4.  **Infrastructure:**
    ```bash
    docker-compose up -d  # Starts DB, Redis, and WAHA gateway
    ```

---

## 2. Development Cycle

### Backend Development
- **Running the API:** `poetry run uvicorn src.robbot.main:app --reload`
- **Linting & Formatting:** Use `ruff` or `flake8` as configured.
- **Dependency Injection:** When adding new services, register them in the DI container configuration. Ensure controllers receive dependencies via injection, not manual instantiation.

### Frontend Development
- **Running the UI:** `npm run dev`
- **Styleguide:** Access `/styleguide` in your browser to view and test atomic UI components in isolation.
- **API Interaction:** Use the `fetchApi` wrapper. Never call `fetch` directly for internal API requests.

---

## 3. Testing Requirements

Strict adherence to testing is required for all PRs.

- **Backend:** Run `pytest`. Focus on:
  - **Unit Tests:** For services and individual functions (`back/tests/unit`).
  - **Integration Tests:** For database and DI resolution.
  - **API Tests:** For verifying routers and authentication (`back/tests/api`).
- **Frontend:** Run `npm run test` (if configured) or verify components within the styleguide.

---

## 4. Git & Contribution Process

1.  **Branching:** Create a feature branch from `main`: `feature/short-description`.
2.  **Commits:** Follow conventional commits (e.g., `feat:`, `fix:`, `docs:`, `chore:`).
3.  **Pull Requests:**
    - Ensure all tests pass locally.
    - Reference the associated issue in the description.
    - Await approval from at least one reviewer.
4.  **Database Migrations:** If modifying models, generate a migration using Alembic:
    ```bash
    poetry run alembic revision --autogenerate -m "description"
    ```

---

## 5. Collaboration with Agents

When using AI agents (like Antigravity):
- Utilize the **PREVC** (Plan -> Review -> Execute -> Verify -> Complete) workflow for non-trivial tasks.
- Ensure the agent's playbooks in `.context/agents/*.md` are up to date with current project patterns.
- Review agent-generated code with the same scrutiny as human-written code.
