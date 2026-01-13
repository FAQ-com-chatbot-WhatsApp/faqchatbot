# Contributing to Clinica Go

Thank you for your interest in contributing! This guide will help you get started quickly.

## Getting Started

### Prerequisites
- **Backend:** Python 3.12+, uv package manager, Docker
- **Frontend:** Node.js 18+, npm
- **Infrastructure:** Docker & Docker Compose

### Local Setup

1. **Clone and setup environment:**
   ```bash
   git clone <repository-url>
   cd clinica_go
   ```

2. **Backend development:**
   ```bash
   cd back
   docker-compose up --build  # Starts API, workers, DB, Redis, WAHA
   # API available at http://localhost:3333
   # Docs at http://localhost:3333/docs
   ```

3. **Frontend development:**
   ```bash
   cd frontend
   npm install
   npm run dev  # Starts Next.js at http://localhost:3000
   ```

## Development Guidelines

### Code Architecture

Follow the **Adapted Clean Architecture** pattern (see [ADR-004](back/docs/architecture/decisions/ADR-004-clean-architecture-adapted.md)):

- **Controllers** → Handle HTTP requests/responses
- **Services** → Business logic and orchestration
- **Repositories** → Data access abstraction using `BaseRepository`
- **Models** → SQLAlchemy ORM (serve as both entities and persistence)

### Code Standards

**Backend (Python):**
- Follow existing patterns in `src/robbot/services/` and `src/robbot/api/`
- Use type hints for all function signatures
- Services use constructor dependency injection
- Repositories extend `BaseRepository[ModelType]`
- Logging: ASCII-safe, structured (see [logging guidelines](back/docs/development/logging-guidelines.md))
- Linting: `ruff check .` and `ruff format .`

**Frontend (TypeScript):**
- Use Next.js App Router patterns
- Components follow shadcn/ui conventions
- Tailwind CSS v4 for styling
- Type-safe with strict TypeScript

### Testing Requirements

**Backend:**
```bash
cd back
pytest                          # Run all tests
pytest tests/unit/              # Unit tests only
pytest tests/integration/       # Integration tests only
pytest --cov=src/robbot         # With coverage
```

**Frontend:**
```bash
cd frontend
npm run test                    # Playwright tests
```

**Minimum Coverage:** Ensure new services/controllers have corresponding unit tests.

## Pull Request Process

1. **Create a feature branch:** `git checkout -b feature/your-feature-name`
2. **Make atomic commits:** Small, focused changes with clear messages
3. **Write tests:** Cover new functionality with unit/integration tests
4. **Update documentation:** Modify ARCHITECTURE.md or PRODUCT.md if changing core behavior
5. **Run tests locally:** Ensure all tests pass before pushing
6. **Create PR:** Reference any related issues in the description
7. **Code review:** Address feedback promptly

### Commit Message Format
```
<type>: <short summary>

<optional detailed description>
```

Types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`

## Key Documentation

- [PRODUCT.md](PRODUCT.md) - Product vision, features, and user workflows
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture and technical decisions
- [ADRs](back/docs/architecture/decisions/) - Architecture Decision Records
- [API Docs](http://localhost:3333/docs) - Interactive OpenAPI documentation (when running)

## Need Help?

- Review existing code in similar modules
- Check ADRs for architectural context
- Ask questions in PR comments or discussions

## Important Notes

- **No emojis in code/logs** - Maintained for ASCII compatibility
- **BaseRepository pattern** - Use generic repository for data access (ADR-006)
- **Direct ORM usage** - No separate domain entities (ADR-006)
- **Security first** - Never commit credentials or API keys

---

By contributing, you agree to maintain the project's code quality standards and architectural principles.
