# Tooling & Productivity Guide

Maintaining a high level of efficiency and code quality requires consistent tooling across the development team. This guide outlines the essential tools, recommended automation scripts, and editor configurations designed to streamline the contribution process and ensure adherence to project standards.

By utilizing the tools and practices detailed below, developers can minimize context switching, automate repetitive tasks, and catch errors early in the development cycle.

## Required Tooling

These tools are mandatory for setting up the environment and running the application components (Backend Python services and Frontend Next.js application).

| Tool | Version Requirement | Purpose | Installation/Setup |
| :--- | :--- | :--- | :--- |
| **Git** | 2.30+ | Source control management. | Standard installation (check system requirements). |
| **Python** | 3.11+ | Runtime environment for the Backend services (Robbot). | Ensure `python3` and `pip` or `pipx` are available. |
| **Poetry** | 1.7+ | Python dependency management and packaging for the `back` directory. | `pip install poetry` (or via `pipx`). |
| **Node.js** | 18+ LTS | Runtime environment for the Frontend. | Standard installation. |
| **pnpm** | 8.x+ | Frontend dependency manager. Used in the `frontend` directory. | `npm install -g pnpm` |
| **Docker & Compose** | Latest Stable | Orchestration and environment parity for running databases, services, and the full stack locally. | Standard installation and verification of `docker compose` command availability. |

## Recommended Automation

The project uses several automation scripts and pre-commit hooks to maintain code consistency, run tests, and manage the local environment efficiently.

### Pre-Commit Hooks

We leverage `pre-commit` to automatically run formatters and linters on staged files before a commit is finalized. This ensures that only properly formatted and linted code enters the repository history.

**Setup:**
1. Ensure Poetry is installed (see Required Tooling).
2. Install development dependencies and hooks in the repository root:
   ```bash
   poetry install  # Installs development dependencies including pre-commit
   pre-commit install
   ```

**Automated Checks:**
Once installed, the following checks run automatically on `git commit`:
*   **Black:** Uncompromising Python code formatting.
*   **isort:** Sorts Python imports alphabetically and separates them by type.
*   **ESLint/Prettier:** Formats and lints TypeScript/JavaScript files in the `frontend` directory.

### Environment Management (Makefile Shortcuts)

Key automation shortcuts are provided via the root `Makefile` to simplify common development tasks.

| Command | Description |
| :--- | :--- |
| `make up` | Builds and starts all services (backend, frontend dev server, database) using Docker Compose. |
| `make test` | Runs all unit and integration tests for both the `back` (Pytest) and `frontend` (Playwright/Jest) components. |
| `make lint` | Manually runs all formatters and linters across the codebase (useful before large merges or running CI). |
| `make clean` | Stops and removes all local Docker containers and volumes. |

### Watch Mode for Development

To facilitate fast iteration, use the dedicated watch commands for hot reloading, typically run outside of the Docker environment for rapid feedback.

#### Frontend Development

To run the Next.js frontend in development mode with hot reloading:

```bash
cd frontend
pnpm dev
```

#### Backend Development

For local Python development, run the FastAPI application with a hot-reloading server (e.g., Uvicorn) using the development environment dependencies managed by Poetry:

```bash
cd back
poetry run uvicorn robbot.api.main:app --reload
```

## IDE / Editor Setup

We highly recommend using Visual Studio Code (VS Code) as the primary editor due to its robust support for polyglot environments (Python, TypeScript, React) and extensive extension ecosystem.

### Essential VS Code Extensions

Install the following extensions to ensure proper linting, formatting, and type-checking are performed directly within the editor:

| Extension Name | Publisher | Purpose |
| :--- | :--- | :--- |
| **Python** | Microsoft | Provides core Python support, debugging, and IntelliSense. |
| **Pylance / Pyright** | Microsoft | Advanced type checking and language server support for Python. |
| **ESLint** | dbaeumer | Integrates JavaScript/TypeScript linting rules defined in `frontend/.eslintrc.json`. |
| **Prettier - Code formatter** | esbenp | Ensures consistent code styling across JS/TS, HTML, and Markdown files. |
| **Tailwind CSS IntelliSense** | bradlc | Provides auto-completion, syntax highlighting, and linting for Tailwind utility classes. |
| **Black Formatter** | ms-python | Provides integration for the Python code formatter. |

### Workspace Settings

To ensure consistency, developers should use the project's formatting tools on save. The following configuration should be present in `.vscode/settings.json` and helps integrate the tools correctly:

```json
{
    // Global setting to enable auto-formatting on save
    "editor.formatOnSave": true,

    // Specify default formatters for specific languages
    "[typescript]": {
        "editor.defaultFormatter": "esbenp.prettier-vscode"
    },
    "[typescriptreact]": {
        "editor.defaultFormatter": "esbenp.prettier-vscode"
    },
    "[json]": {
        "editor.defaultFormatter": "esbenp.prettier-vscode"
    },
    "[python]": {
        "editor.defaultFormatter": "ms-python.black-formatter"
    },

    // Integration with Poetry environments for the Python Extension
    "python.venvPath": "${workspaceFolder}/back/.venv",
    "python.poetryPath": "/path/to/poetry/executable" // Only configure if Poetry is not on PATH
}
```

## Productivity Tips

### Terminal Aliases

Setting up aliases for frequently used project commands can save significant time. Add these to your shell configuration (`.zshrc`, `.bashrc`, etc.):

| Alias | Command | Purpose |
| :--- | :--- | :--- |
| `cgup` | `make up` | Start the entire stack quickly. |
| `cgtest` | `make test` | Run all tests. |
| `cglint` | `make lint` | Manually format and lint everything. |
| `cgdev` | `cd frontend && pnpm dev` | Start the frontend development server directly. |

### Container Workflow Shortcuts

When needing to run specific commands or access tools within a running Docker container (e.g., Alembic migrations or database CLI), use `docker compose exec`.

**Accessing the Backend Shell:**

```bash
docker compose exec back /bin/bash
# Inside the container, you can run environment-specific tools:
# alembic upgrade head
# poetry run pytest back/tests/unit/
```

Use `docker compose logs -f <service_name>` (e.g., `docker compose logs -f back`) to follow service output in real-time.

### Fast Dependency Installation

For rapid onboarding or branch switching:

1.  **Backend (Poetry):** If `poetry.lock` is unchanged, `poetry install --no-root` is generally fast, relying on the existing virtual environment cache.
2.  **Frontend (pnpm):** Due to `pnpm`'s content-addressable storage, subsequent installations are fast as packages are symlinked from a global cache. Ensure you run `pnpm install` in the `frontend/` directory.

---
## Related Resources

- [Development Workflow](./development-workflow.md): Detailed steps for setting up the environment, running services, and the contribution cycle.
- [Architecture Overview](../architecture.md): Understanding how the backend and frontend components interact.
