---
title: "Base Repository (base_repository.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Base Repository (`base_repository.py`)

## 1. Description
The `BaseRepository` is a generic class that provides a standard set of CRUD (Create, Read, Update, Delete) operations for any SQLAlchemy model. It is a core component of the project's data access layer, designed to reduce boilerplate code and enforce a consistent pattern for database interactions.

## 2. Architecture and Design
- **Generic Implementation:** The class uses Python's `typing.Generic` and `TypeVar("ModelType")` to create a reusable implementation that can work with any SQLAlchemy model class.
- **Dependency Injection:** It is initialized with a database session (`db: Session`) and the specific model class it will manage (`model_class: type[ModelType]`). This follows the Dependency Inversion Principle, as the repository depends on abstractions (`Session`, `type`) rather than concrete implementations.
- **SQLAlchemy 2.0 Syntax:** The repository uses the modern `select()`-based syntax from SQLAlchemy 2.0, which is more explicit and less prone to ambiguity than the older `query()`-based syntax.

## 3. Data Structure
The `BaseRepository` itself does not define any Pydantic schemas. It operates directly on SQLAlchemy `ModelType` objects.

## 4. Provided Methods
The `BaseRepository` provides the following core methods:
- **`get_by_id(self, entity_id: int) -> ModelType | None`**: Retrieves a single entity by its primary key.
- **`get_all(self, skip: int = 0, limit: int = 100) -> list[ModelType]`**: Retrieves a paginated list of all entities.
- **`create(self, obj: ModelType) -> ModelType`**: Adds a new entity to the database, flushes the session to persist it, and refreshes the object to get any database-generated values (like the primary key).
- **`update(self, obj: ModelType) -> ModelType`**: Flushes the session to persist any changes to an existing entity and refreshes it.
- **`delete(self, obj: ModelType) -> None`**: Marks an entity for deletion and flushes the session.
- **`count(self) -> int`**: Returns the total number of entities for the given model.

## 5. Use Cases
- **Use Case 1: Creating a Concrete Repository:** A developer needs to create a repository for a new `Product` model. They create a `ProductRepository` class that inherits from `BaseRepository[Product]` and in their service layer, they instantiate it like so: `product_repo = ProductRepository(db_session, Product)`.
- **Use Case 2: Retrieving Data in a Service:** A service needs to get a user by their ID. It calls `user_repository.get_by_id(user_id)`. The `BaseRepository` handles the underlying database query.
- **Use Case 3: Adding a Custom Method:** The `LeadRepository` needs a specific method to find leads by phone number. The developer adds a `find_by_phone(phone)` method to the `LeadRepository` class, which contains a custom SQLAlchemy query, while still inheriting all the basic CRUD methods from `BaseRepository`.

## 6. Security
The `BaseRepository` itself does not handle authentication or authorization. These concerns are managed at a higher level (in the controllers or service layer) before any repository methods are called.

## 7. Performance
- The `get_all` method includes pagination (`skip`, `limit`) by default, which is a crucial performance best practice to prevent fetching large amounts of data from the database at once.
- The use of `db.flush()` in `create`, `update`, and `delete` ensures that changes are sent to the database within the current transaction, but it does not commit the transaction. The transaction management (commit, rollback) is handled at the service layer or by a FastAPI dependency, which is the correct separation of concerns.

## 8. Testability
The `BaseRepository` is highly testable. Because it is initialized with a database session, it can be easily tested with an in-memory SQLite database or a test database session, completely isolated from the production database.

## 9. Error Handling
The `BaseRepository` does not handle exceptions. It assumes that database-related exceptions (like `IntegrityError` for duplicate keys) will be caught and handled by the calling service layer, which can then translate them into appropriate application-level errors or HTTP exceptions.
