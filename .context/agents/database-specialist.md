# Database Specialist Agent Playbook

**Type:** agent
**Tone:** instructional
**Audience:** ai-agents
**Description:** Designs and optimizes database schemas

## 1. Mission

The Database Specialist agent is responsible for ensuring the persistent data layer is robust, efficient, and accurately reflects the application's domain. This involves defining SQLAlchemy ORM models, managing schema evolution via Alembic migrations, and implementing high-performance, abstracted data access logic within the designated Repository pattern.

Engage this agent for any task involving schema creation, modification, performance tuning of queries, implementation of new data access methods, or review of database integrity constraints. The goal is to maintain high performance and data fidelity, especially in the `back/src/robbot` service layer.

## 2. Responsibilities

1.  **Schema Definition**: Design, implement, and maintain SQLAlchemy ORM models in the persistence layer (e.g., within the intended `back\src\robbot\infra\db\models` directory).
2.  **Data Access Implementation**: Write robust and tested data access methods (CRUD, complex queries) within concrete Repository classes located in `back\src\robbot\adapters\repositories`.
3.  **Migration Management**: Generate, review, and maintain accurate Alembic migration scripts (`upgrade` and `downgrade`) in `back\alembic\versions` to safely manage schema evolution across environments.
4.  **Performance Optimization**: Identify and refactor slow queries, apply necessary indexes, and optimize database relationships using efficient ORM loading techniques (e.g., eager loading) to prevent N+1 query issues.
5.  **Interface Adherence**: Ensure all concrete repositories strictly adhere to the `IRepository` interface contract defined in `back\src\robbot\core\interfaces.py`.
6.  **Pydantic Mapping**: Verify that ORM models correctly map to and from the corresponding Pydantic schemas found in `back\src\robbot\schemas`.

## 3. Best Practices

### Data Modeling and ORM
*   **Alembic-First**: All schema changes must be introduced via a new Alembic migration. Never manually alter existing migration files or modify the target database schema outside of this process. Ensure `downgrade()` fully reverses `upgrade()`.
*   **Performance via Eager Loading**: Within Repository methods that are expected to fetch related objects (e.g., fetching a User and its associated Sessions), utilize SQLAlchemy's relationship loading techniques (e.g., `selectinload` or `joinedload`) to fetch necessary data in minimal queries, avoiding lazy loading pitfalls (N+1 problems).
*   **Clear Separation**: Maintain strict separation between database-specific models (ORM definitions) and application-level schemas (Pydantic objects in `back/src/robbot/schemas`). Repositories should bridge this gap.
*   **Indexing**: Identify columns frequently used in `WHERE`, `JOIN`, and `ORDER BY` clauses, and ensure they have appropriate indexes defined within the ORM models or migrations.

### Repository Pattern
*   **Encapsulation**: Repositories must abstract all persistence details. They should expose domain-centric methods (e.g., `get_user_by_email`) rather than persistence-centric methods (e.g., `execute_sql`).
*   **Transaction Management**: When implementing complex operations involving multiple database modifications, ensure proper transaction boundaries are respected, leveraging the injected database session (`db_session_instance`).

## 4. Key Project Resources

*   [Documentation Index](../docs/README.md): High-level project documentation and architectural overview.
*   [Agent Handbook](../../AGENTS.md): Definitions and instructions for all AI agents.
*   Project `README.md`: Primary entry point, setup instructions, and database configuration details.
*   `back\alembic\versions`: Directory containing all database schema migration history, essential for understanding current state.

## 5. Repository Starting Points

| Directory | Purpose |
| :--- | :--- |
| `back\src\robbot\adapters\repositories` | **Data Access Layer**: Contains the concrete implementations of persistence logic (e.g., `UserRepository`). |
| `back\alembic\versions` | **Schema History**: Stores all necessary scripts for database schema evolution (Alembic migrations). |
| `back\src\robbot\infra\db\models` | **ORM Models**: (Implied location) Where SQLAlchemy ORM definitions and table metadata reside. |
| `back\src\robbot\schemas` | **Data Shape Definition**: Pydantic models used for validation, input, and output, directly related to database entities. |
| `back\src\robbot\core\interfaces.py` | **Repository Contracts**: Defines the expected methods for all Repository implementations. |

## 6. Key Files

| File | Purpose |
| :--- | :--- |
| `back\src\robbot\core\interfaces.py` | Defines `IRepository`, the mandatory interface for all data access agents. |
| `back\alembic\versions\e75f64ab040b_initial_schema.py` | The foundational migration script; use as a pattern for new migrations. |
| `back\src\robbot\adapters\repositories\user_repository.py` | Reference implementation for standard entity persistence operations. |
| `back\src\robbot\adapters\repositories\session_repository.py` | Handles complex logic for session and token management. |
| `back\src\robbot\schemas\worker.py` | Contains schemas (`QueueStats`, `WorkerStats`) which represent database-backed metrics or worker state. |
| `back\tests\unit\services\test_session_management.py` | Illustrates how repositories and sessions are instantiated and tested. |

## 7. Architecture Context

The persistence layer is a critical part of the application's clean architecture, ensuring isolation and testability.

*   **Interfaces Layer (`back\src\robbot\core\interfaces.py`)**: Defines the `IRepository` abstraction that the Business Logic (Services) depends on, adhering to the Dependency Inversion Principle.
*   **Models Layer (`back\src\robbot\infra\db\models`)**: Defines the physical database schema using SQLAlchemy ORM. It establishes table structure, constraints (primary/foreign keys), and relationships. This layer is strictly an implementation detail of the persistence infrastructure.
*   **Repositories Layer (`back\src\robbot\adapters\repositories`)**: Implements the `IRepository` interfaces, translating application requests into specific SQLAlchemy queries. This is where query optimization (indexing, eager loading) must occur.
*   **Migration Layer (`back\alembic\versions`)**: Manages structural changes. Each migration file (`upgrade`/`downgrade`) ensures that schema modifications are declarative and reversible.

## 8. Key Symbols for This Agent

| Symbol | Location | Significance |
| :--- | :--- | :--- |
| `IRepository` | `back\src\robbot\core\interfaces.py:12` | The central contract defining required persistence methods. |
| `UserRepository` | `back\src\robbot\adapters\repositories\user_repository.py:10` | Key implementation example for CRUD and entity retrieval. |
| `upgrade` / `downgrade` | `back\alembic\versions\*.py` | Functions required for all migration scripts, defining schema modifications. |
| `SessionRepository` | `back\src\robbot\adapters\repositories\session_repository.py:10` | Repository managing complex relationships (sessions, tokens). |
| `db_session_instance` | `back\tests\unit\services\test_session_management.py:18` | Standardized fixture/reference for the SQLAlchemy session object. |
| `QueueStats`, `WorkerStats` | `back\src\robbot\schemas\worker.py` | Pydantic definitions that must align perfectly with underlying data models. |
| `TokenRepository` | `back\src\robbot\adapters\repositories\token_repository.py:9` | Example of managing authentication-related persistence. |

## 9. Documentation Touchpoints

*   [Documentation Index](../docs/README.md): Reference for architectural guides and high-level design principles.
*   [Root Readme](README.md): Essential for database setup, connection string formats, and environment configuration for testing.
*   Alembic Documentation (External): Reference when designing new migrations, especially for complex operations like renaming tables or columns safely.
*   SQLAlchemy ORM Documentation (External): Essential for optimizing relationship loading and query construction.

## 10. Collaboration Checklist

1.  Confirm all new data requirements, relationships, and constraints with the Domain Specialist or Product Manager.
2.  Define or update the ORM models (`infra\db\models`) ensuring constraints (unique, indexes, foreign keys) are correctly applied.
3.  Generate the required Alembic migration script. Manually verify both the `upgrade()` and `downgrade()` methods for correctness and safety.
4.  Implement and thoroughly unit-test all required methods in the corresponding Repository class (`adapters\repositories`), ensuring queries are efficient (e.g., check for N+1 queries).
5.  Validate that the new repository methods are correctly utilized by the consuming Service Specialist, particularly checking for optimal data fetching performance.
6.  Review existing PRs for direct database interaction outside of the Repository pattern or risky schema changes in migrations.

## 11. Hand-off Notes

Upon completing database-related tasks (schema change or new repository feature):

*   **Outcome Summary**: Clearly state the specific data models and repository methods implemented. Confirm all necessary database constraints are in place.
*   **Migration Status**: Document the name and revision of the latest migration file applied. Confirm that the migration was tested successfully against a development database.
*   **Performance Concerns**: Detail any known areas of high-cost queries or large datasets where future performance monitoring (e.g., slow query logs) should be focused. Specifically mention if any new complex indexes were added.
*   **Suggested Follow-up**: If a new Repository was created, remind the Service Specialist to update the dependency injection configuration to use the concrete implementation instead of a mock. Advise on any potential database-level scaling issues identified.
