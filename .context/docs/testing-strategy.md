# Testing Strategy

Quality assurance for the **Clinica Go** project is maintained through a robust, multi-layered automated testing strategy. This strategy is essential for ensuring code reliability, preventing regressions, and providing rapid feedback during the development cycle.

All development work, including new features and significant bug fixes, must be accompanied by appropriate test coverage.

## Core Principles

1.  **Isolation (Backend):** We heavily utilize Dependency Injection (DI) on the backend, facilitating easy mocking of repositories, services, and external APIs to achieve high-quality unit tests for core business logic.
2.  **Coverage:** Automated tests are enforced via Continuous Integration (CI) pipelines, with strict quality gates applied to maintain a high standard of coverage and reliability.
3.  **Realism (E2E):** API tests simulate full user flows against the live application structure (using dedicated HTTP clients), verifying the integration between controllers, services, and database layers.
4.  **User Focus (Frontend):** Frontend tests focus on validating component behavior, user interactions, accessibility, and correct state management, often interacting with a mocked API layer.

## Test Architecture and Types

We categorize tests based on their scope and purpose, utilizing specific frameworks for each platform.

| Type | Platform | Focus | Frameworks / Tooling | Location & Naming Convention |
| :--- | :--- | :--- | :--- | :--- |
| **Unit Tests** | Backend (Python) | Isolating business logic, services, utilities, and controller methods. Dependencies are mocked using Pytest fixtures. | Pytest, standard Python mocking | `back/tests/unit/`. Files: `test_*.py`. |
| **Unit Tests** | Frontend (React/TS) | UI component rendering, custom hooks, and utility functions in isolation. | Jest, React Testing Library (RTL) | `frontend/src/**/*.test.ts(x)`. |
| **Integration Tests** | Backend (Python) | Validating interactions between internal components (e.g., service layer to repository layer, complex database transactions). | Pytest, specialized database fixtures | `back/tests/integration/`. Files: `test_*.py`. |
| **E2E / API Tests** | Backend (Python) | Simulating full external API interactions (HTTP requests) against the running application instance. Verifies routes, security, and data flow. | Pytest (using API client fixtures like `api_client`, `auth_headers`). | `back/tests/api/`. Files: `test_*.py`. |

## Running Tests

Testing is managed through unified NPM scripts, making it simple to execute test suites across the monorepo from the root directory.

| Goal | Command | Description |
| :--- | :--- | :--- |
| **Run All Tests** | `npm run test` | Executes all unit, integration, and API test suites for both frontend and backend systems. |
| **Run Backend Tests** | `npm run test:backend` | Executes all Pytest suites (unit, integration, api) in the `back/` directory. |
| **Run Frontend Tests** | `npm run test:frontend` | Executes Jest/RTL tests in the `frontend/` directory. |
| **Watch Mode (Frontend)** | `npm run test:frontend -- --watch` | Runs frontend tests continuously, re-running affected tests on file changes for rapid development feedback. |
| **Generate Coverage Report** | `npm run test -- --coverage` | Executes all tests and generates a detailed coverage report (usually outputting HTML reports to a dedicated coverage directory). |
| **Run Specific Backend File** | `npm run test:backend -- back/tests/api/test_01_auth.py` | Runs tests only in a specified file. |

## Quality Gates

All Pull Requests (PRs) must adhere to the following automated quality checks enforced by the CI pipeline before merging:

1.  **Mandatory Test Success:** All Unit, Integration, and API tests must pass successfully.
2.  **Code Coverage Enforcement:** A minimum code coverage of **80%** is required for all backend application code (`back/src`). Frontend coverage is tracked but not strictly enforced as a hard gate.
3.  **Static Analysis:** The codebase must pass all static analysis checks, including linting (`eslint`, `flake8`, `mypy`) and code formatting (`prettier`).
4.  **Security Review:** No new critical security warnings or dependency vulnerabilities should be introduced.

## Best Practices and Conventions

### Backend Testing

*   **Fixtures:** Leverage Pytest fixtures defined in `conftest.py` files (especially `back/tests/api/conftest.py`) for setup, including authenticated API clients (`api_client`, `auth_headers`, `authenticated_admin`), database sessions, and mocked external services.
*   **API Test Structure:** API tests (`back/tests/api/`) should be ordered logically (e.g., `test_01_auth.py`, `test_02_waha.py`) to simulate progressive system usage, though tests must remain idempotent and isolated where state changes are required.
*   **Dependency Injection (DI):** When testing controllers or services, use the DI container to override real dependencies with mock objects, ensuring genuine unit isolation.

    *Example of overriding a service dependency in a test:*
    The codebase uses classes like `TestDIInControllers` and `TestDIContainerInitialization` to manage dependency injection in tests (see `back/tests/unit/test_di_controllers.py` and `back/tests/unit/test_di_container.py`).

### Frontend Testing

*   **RTL Philosophy:** Use React Testing Library (RTL) to test components from a user's perspective (e.g., querying elements by roles, labels, or visible text), rather than focusing on internal implementation details.
*   **Mocking API Calls:** Utilize tools like `msw` (Mock Service Worker) or simple Jest mocks to intercept network requests (e.g., `loginApi` or generic `fetchApi` from `frontend/src/lib/api.ts`) and simulate API responses, ensuring components handle success, failure, and loading states correctly.

## Troubleshooting Tests

### Dealing with Flakiness

Flaky tests introduce uncertainty and slow down development. They are treated as high-priority bugs.

1.  **Isolation Check:** Ensure the test is fully isolated. For backend tests, check that database state is reliably reset between tests. For concurrent systems (like the message pipeline), ensure proper synchronization or use controlled mocks.
2.  **Time Mocking:** If a test relies on specific time progression (e.g., audit log timestamps, idle timeouts), use dedicated mocking libraries like `freezegun` (Python) to stabilize the environment.

### Performance Optimization

Slow tests increase CI time and developer frustration.

1.  **Identify Slowness:** Use the Pytest duration report to find the culprits:

    ```bash
    npm run test:backend -- --durations=10
    ```

2.  **Minimize I/O:** Reduce the number of external calls, especially database queries. In integration tests, pre-populate necessary data in fixtures rather than relying on complex setup functions within the test body.
3.  **Parallel Execution:** Ensure Pytest is configured to maximize parallelism. Avoid using shared resources (like single-instance mock containers) that prevent tests from running concurrently.
