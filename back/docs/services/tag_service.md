
# Feature: TagService

## 1. Description

The `TagService` is a CRUD (Create, Read, Update, Delete) service for managing "tags" in the system. Tags are used to categorize and organize other entities, such as conversations, leads, or messages, allowing for more efficient filtering and searching. Each tag has a name and a color for easy visual identification in the user interface.

## 2. Architecture and Design

-   **Centralized Business Logic:** The service encapsulates business rules related to tags, such as validating name uniqueness and color format.
-   **Standard Service Layer:** It follows the project's standard architecture, acting as a service layer that uses a `TagRepository` to interact with the database.
-   **Dependency Injection:** It receives the SQLAlchemy session (`session`) as a dependency, which allows it to participate in larger transactions orchestrated by other services and facilitates testing.

## 3. Data Structure

The service operates on the `TagModel`, which has a simple structure:
-   `id` (int): Unique identifier for the tag.
-   `name` (str): The name of the tag (e.g., "Urgent", "Scheduling", "Price Inquiry"). Must be unique.
-   `color` (str): A hexadecimal color code (e.g., `#FF0000`) for the visual representation of the tag.

## 4. Dependencies and Integrations

-   **`TagRepository`:** The data access layer that executes CRUD operations in the database for the `TagModel`.
-   **Other Services/Models:** The `TagModel` likely has many-to-many relationships with other models, such as `Conversation` or `Lead`. Services like `ConversationService` or `LeadService` can use the `TagService` to associate or disassociate tags from their respective entities.
-   **API Endpoints:** The API exposes endpoints so that the frontend can create new tags and list existing ones to display them in selection menus, for example.

## 5. Use Cases

-   **Creating a New Tag:** A system administrator accesses a settings area on the dashboard and creates a new tag called "Post-sale" with the color "#0000FF" to categorize follow-up conversations.
-   **Assigning a Tag to a Conversation:** An agent, during a conversation, realizes the patient has a question about the price and applies the "Price Inquiry" tag to the conversation so it can be easily found later.
-   **Filtering by Tag:** A marketing manager wants to see all conversations that were tagged with "Interest in Botox" to analyze the effectiveness of a campaign. They use a filter in the interface that, behind the scenes, queries for conversations associated with that tag.

## 6. Security

-   Creating and deleting tags should be operations restricted to users with administrator permissions, as they affect the entire categorization system. Permission validation should be done in the API layer that consumes this service.

## 7. Performance

-   Operations in this service are generally very fast, as they involve simple queries on a small table.
-   It is crucial that the `name` column in the tags table has a unique index (`UNIQUE INDEX`) to ensure both uniqueness and performance of the search by name (`get_by_name`).

## 8. Testability

-   The service is easily testable. The `TagRepository` can be mocked to test the business rules:
    -   Verify that `ValueError` is thrown when trying to create a tag with a name that already exists.
    -   Verify that `ValueError` is thrown when providing a color code in an invalid format.
    -   Verify that the repository's `delete` method is called when `delete_tag` is invoked with a valid ID.

## 9. Error Handling

-   **`ValueError`:** Thrown in two situations:
    1.  If the name of the tag being created already exists in the database.
    2.  If the format of the provided color is not a valid 7-character hexadecimal code (e.g., `#RRGGBB`).
-   **Boolean Return in `delete_tag`:** The delete method returns `True` on success and `False` if the tag with the provided ID is not found, allowing the calling layer to know the result of the operation without needing an exception.

