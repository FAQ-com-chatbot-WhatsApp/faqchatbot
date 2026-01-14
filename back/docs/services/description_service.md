
# Feature: DescriptionService

## 1. Description

The `DescriptionService` is a specialized service for generating metadata (title, description, and tags) for media messages, such as images, videos, and documents. It operates in a hybrid manner: for images, it uses the `VisionService` (with the local BLIP-2 model) for detailed visual analysis; for other file types, or as a fallback, it generates basic metadata from the file name, caption, and extension, at no API cost.

## 2. Architecture and Design

-   **Hybrid Strategy (Vision vs. Metadata):** The main design decision is the ability to switch between an expensive AI analysis (in terms of processing) and a cheap metadata extraction. The `use_vision` parameter controls this behavior.
-   **Robust Fallback:** In case of any error during the analysis with the `VisionService` (e.g., model download failure, processing error), the service automatically resorts to the `generate_file_metadata` method. This ensures that every media message will have, at a minimum, basic metadata, making the system more resilient.
-   **Logic Reuse:** Instead of duplicating the image analysis logic, it consumes the `VisionService` (through the `get_vision_service` singleton), following the single responsibility principle.
-   **Contextual Analysis:** The metadata generation, both basic and advanced, is contextual. It uses a dictionary of keywords (`keyword_tags`) relevant to the clinic's domain (weight loss, health) to suggest more useful tags.

## 3. Data Structure

-   **Input:**
    -   `message_id` (UUID): The ID of the message to be analyzed.
-   **Output:** A dictionary containing:
    -   `generated_title`: A short title (max 50 characters).
    -   `generated_description`: A detailed description.
    -   `suggested_tags`: A string of relevant comma-separated tags.

## 4. Dependencies and Integrations

-   **`MessageRepository`:** Used to fetch the message object from the database using its ID.
-   **`VisionService`:** Main dependency for AI-powered image analysis.
-   **`MessageService` (implied):** This service is likely the primary consumer of the `DescriptionService`, calling it after saving a new media message to enrich it with metadata.

## 5. Use Cases

-   **Patient Image Upload:** A patient sends a photo of their meal. The `MessageService` saves the message and calls the `DescriptionService`. This, in turn, invokes the `VisionService` to analyze the image. The result ("plate with salmon and broccoli") is used to generate a title, a detailed description, and tags like "image, food, meal, healthy". This metadata is saved with the message.
-   **PDF Document Submission:** A patient sends a file named `my_blood_tests.pdf`. The `DescriptionService` is called, but since it is not an image, it uses the `generate_file_metadata` method. It generates the title "my blood tests", the description "File: my_blood_tests.pdf | Type: PDF document", and the tags "document, PDF document, test, result".
-   **Media Search:** The generated metadata (especially the tags) can be used to power a search system, allowing agents to quickly find all "images" related to "tests", for example.

## 6. Security

-   The service operates on data that is already in the system. Security is ensured by the preceding layers that control access to the original message. The use of local models for image analysis enhances privacy, as the data does not leave the application environment.

## 7. Performance

-   Performance critically depends on `use_vision`.
    -   **With Vision:** Image analysis is a slow and CPU/memory-intensive operation. Ideally, it should be executed in a background job to avoid blocking the main thread.
    -   **Without Vision:** Basic metadata generation is extremely fast, as it only involves string manipulation.
-   The automatic fallback to the basic method in case of an error is a good performance and resilience practice.

## 8. Testability

-   The service can be tested in two main scenarios:
    1.  **Vision Scenario:** Mocking the `VisionService` to return an expected result and verifying that the `DescriptionService` processes it correctly.
    2.  **Basic Metadata Scenario:** Testing the `generate_file_metadata` method with different file names and captions to ensure the generated metadata is consistent.
-   It is also important to test the fallback behavior in case of an exception in the `VisionService`.

## 9. Error Handling

-   **`NotFoundException`:** Thrown if the provided `message_id` does not correspond to any message in the database.
-   **Generic Exceptions:** Any exception during analysis is caught. Instead of crashing, the service logs the error and triggers the fallback mechanism for basic metadata generation, ensuring there is always a valid output.

