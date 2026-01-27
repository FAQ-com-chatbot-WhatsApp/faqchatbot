---
status: filled
---
# Tooling & Productivity: Clinica Go


**Status:** filled
**Updated:** 2026-01-27

This document describes the utility scripts, command-line tools, and configurations that enhance productivity when working on the **Clinica Go** project.

---

## 1. Project Scripts

The project includes several pre-configured scripts to automate common development tasks.

### Automation Scripts (Root)
- **`auto-commit.sh` / `auto-commit.ps1`**: A utility to stag, commit, and push changes with standardized messages. Useful for large documentation or context updates.

### Backend Scripts (`back/`)
- **`poetry run alembic`**: Manage database migrations.
- **`pytest`**: Run the backend test suite.
- **`ruff` / `black`**: (If configured) Tooling for code formatting and linting.

### Frontend Scripts (`frontend/`)
- **`npm run dev`**: Start the Next.js development server.
- **`npm run build`**: Production optimized build.
- **`npm run styleguide`**: (If separate script) Launch the UI component playground.

---

## 2. Docker & Infrastructure Tooling

We use `docker-compose` to manage the infrastructure stack.

- **`docker-compose up -d`**: Launch the database (PostgreSQL), memory store (Redis), and WhatsApp bridge (WAHA).
- **`docker-compose logs -f back`**: Tail the logs of the backend container if running in Docker.
- **`docker-compose exec back bash`**: Access the shell of the backend container for running one-off management commands.

---

## 3. IDE Configuration (VS Code)

To ensure consistency, we recommend the following OS/IDE configurations:

- **Tailwind CSS IntelliSense**: For autocompleting CSS classes in the frontend.
- **Pydantic / Python Extension**: For type checking and Pydantic model validation assistance.
- **ESLint/Prettier**: Automatically format code on save according to the project's `.eslintrc` and `.prettierrc`.

---

## 4. Useful Aliases (Optional)

Consider adding the following to your shell profile (`.bashrc` or `.zshrc`):

```bash
alias cg-up="docker-compose up -d"
alias cg-back="cd back && poetry run uvicorn src.robbot.main:app --reload"
alias cg-front="cd frontend && npm run dev"
alias cg-test="cd back && poetry run pytest"
```

---

## 5. Agent-Specific Tooling

For developers using AI agents:
- **`mcp:ai-context`**: The primary tool for managing project context and playbooks within the `.context` directory.
- **Workflow status**: Check `.context/workflow/status.yaml` to see current project progress if using the PREVC workflow.
 Riverside
