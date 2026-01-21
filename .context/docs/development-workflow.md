# Development Workflow Guide

This document outlines the standard development workflow for the `clinica_go` repository, covering branching strategy, local environment setup, code review expectations, and onboarding guidelines.

---

## I. Day-to-Day Development Process

The workflow follows a standard feature-branch model, emphasizing clear requirements, mandatory testing, and thorough code review.

### 1. Planning and Initiation

1.  **Issue Creation:** All work must be initiated via a defined issue or task in the project management system.
2.  **Branching:** Create a new branch off the `main` branch.

    **Branch Naming Convention:**
    *   `feat/descriptive-name`: For implementing new features.
    *   `fix/descriptive-name`: For resolving bugs.
    *   `chore/descriptive-name`: For maintenance, refactoring, or tooling updates (e.g., CI/CD configuration).

### 2. Local Implementation

1.  **Setup:** Ensure both the frontend (Next.js) and backend (Python/FastAPI) environments are running locally (see [Local Development](#iii-local-development)).
2.  **Implementation:** Write code, ensuring adherence to established standards and conventions. Refer to the [Tooling Guide](./tooling.md) for details on linters and formatters.

### 3. Testing and Committing

1.  **Testing:** Write or update unit, integration, and API tests to cover all changes. **All tests must pass** before opening a Pull Request (PR). Detailed guidelines are found in the [Testing Strategy Guide](./testing-strategy.md).
2.  **Committing:** Commit frequently. Commits should be atomic, descriptive, and address a single logical unit of work.

### 4. Pull Request (PR)

1.  **Open PR:** Push your branch and open a PR targeting the `main` branch.
2.  **Description:** The PR description must clearly:
    *   Summarize the changes.
    *   Link back to the original issue/task ID.
    *   Include necessary testing evidence (e.g., screenshots for UI changes, or confirmation of passing tests).

---

## II. Branching & Releases

We use a modified Trunk-Based Development approach where `main` is always stable and deployable.

### Branch Stability

| Branch Name | Purpose | Stability |
| :--- | :--- | :--- |
| `main` | Production ready. The source of truth for the latest release. | Highly stable |
| `feat/*` / `fix/*` | Development of new features or bug fixes. Short-lived branches. | Volatile |

### Release Cadence

Releases are performed based on significant feature completion or urgent bug fixes, generally on a weekly or bi-weekly cycle.

### Tagging Conventions (Semantic Versioning)

Releases are marked using Semantic Versioning tags (`vX.Y.Z`) applied directly to the `main` branch:

*   **`X` (Major):** Breaking changes or significant, non-backward compatible rewrites.
*   **`Y` (Minor):** New features added in a backward-compatible manner.
*   **`Z` (Patch):** Backward-compatible bug fixes or minor hotfixes.

---

## III. Local Development

The project is a monorepo consisting of a Python backend (`back/`) and a Next.js/React frontend (`frontend/`). Both must run concurrently.

### Prerequisites

*   Python (3.10+)
*   Node.js/npm (LTS version)

### 1. Backend Setup (`back/`)

It is strongly recommended to use a virtual environment for Python dependencies.

```bash
# Navigate to the backend directory
cd back

# 1. Create and activate a virtual environment
python -m venv venv
source venv/bin/activate

# 2. Install dependencies (Requires requirements.txt to be accurate)
pip install -r requirements.txt

# 3. Run database migrations (using Alembic)
alembic upgrade head

# 4. Start the backend server (FastAPI with Uvicorn)
uvicorn robbot.main:app --reload
```
The backend server will typically run on `http://127.0.0.1:8000`.

### 2. Frontend Setup (`frontend/`)

The frontend uses standard Node.js tools and Next.js.

```bash
# Navigate to the frontend directory
cd frontend

# 1. Install dependencies
npm install

# 2. Run the frontend development server
npm run dev
```
The frontend server will typically run on `http://localhost:3000`.

### 3. Running Tests

Always run tests locally to verify changes.

| Component | Directory | Command |
| :--- | :--- | :--- |
| **Backend (Python)** | `back/` | `pytest` |
| **Frontend (Next.js)** | `frontend/` | `npm run test` |

---

## IV. Code Review Expectations

All Pull Requests (PRs) require formal review before merging into `main`. The goal is to ensure high code quality, consistency, and shared knowledge.

### Required Reviewer Checklist

Reviewers must explicitly confirm the following points:

1.  **Functionality:** Does the code correctly solve the intended problem? (Verified locally or via a CI deployment.)
2.  **Test Coverage:** Are new features or fixes adequately covered by unit, integration, and API tests? (See [testing-strategy.md](./testing-strategy.md).)
3.  **Style & Clarity:** Does the code adhere to style guides (e.g., Black/Flake8 for Python, ESLint/Prettier for TypeScript)? Is the logic clear, well-structured, and appropriately documented?
4.  **Error Handling:** Are edge cases considered? Is error handling graceful and informative?
5.  **Security:** Have known security risks (e.g., input validation, access control, rate limiting) been addressed?
6.  **Performance:** Are there any obvious performance bottlenecks or excessive resource usage (e.g., N+1 database queries)?

### Approval Process

A PR requires at least **one approval from a non-author team member** before it can be merged. An approval signifies readiness for production deployment.

### Collaboration with Agents

If AI development agents are used, ensure that the PR clearly labels or discusses agent-generated code. Agent outputs must be reviewed with the same level of scrutiny as human-written code, particularly focusing on security and logical correctness.

---

## V. Onboarding Tasks

Welcome aboard! Use these tasks to familiarize yourself with the codebase and workflow:

1.  **Environment Setup:** Successfully complete the [Local Development](#iii-local-development) steps and confirm you can run the full application stack.
2.  **Explore the Styleguide:** Run the frontend and navigate to the styleguide (typically accessible at `/styleguide` on the local development URL). This is the best way to understand the shared UI component library, including components like `AvatarShowcase`, `ButtonShowcase`, and various utility components (e.g., `TooltipShowcase`, `TabsShowcase`).
3.  **Run All Tests:** Execute the full test suites in both the `back` and `frontend` directories (`pytest` and `npm run test`) to ensure your environment is configured correctly.
4.  **Find a Starter Task:** Look for open issues labeled `good first issue` or contribute by improving existing documentation or tooling configurations.

---

## Related Resources

*   [Testing Strategy Guide](./testing-strategy.md)
*   [Tooling Guide](./tooling.md)
*   AGENTS.md (Internal Guide)
