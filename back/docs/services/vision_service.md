
# Feature: VisionService

## 1. Description

The `VisionService` is an artificial intelligence component focused on image analysis. It serves as a local, cost-free alternative to Gemini Vision, using the open-source `Salesforce/blip-image-captioning-base` (BLIP-2) model. Its main function is to extract contextual information from images sent by patients, such as photos of meals, body areas for treatment, or before-and-after results.

## 2. Architecture and Design

-   **Local Model (BLIP-2):** The decision to use BLIP-2 was strategic to eliminate API costs and ensure data privacy, as images are processed locally. The model is robust for *image captioning* and *Visual Question Answering* (VQA) tasks.
-   **Lazy Loading:** The model, which is approximately 990MB, is loaded into memory only the first time an image needs to be analyzed. This optimizes startup time and application resource consumption.
-   **Multi-Step Analysis Process:**
    1.  **Download and Preparation:** The image is downloaded from a URL and converted to RGB format.
    2.  **Caption Generation:** An initial, concise description of the image is generated.
    3.  **Detailed Analysis (VQA):** The service asks the model "questions" about the image to extract contextual details. The questions are adapted to the context (e.g., "Is this a healthy meal?" for a medical context).
    4.  **Tag Extraction:** Relevant tags are extracted from the generated text to facilitate searches and categorization.
-   **Singleton Pattern:** The service is implemented as a singleton (`get_vision_service`) to ensure that the heavy model is loaded only once and reused throughout the application.

## 3. Data Structure

-   **Input:**
    -   `image_url` (str): URL of the image to be analyzed.
    -   `context` (str): Context of the analysis (e.g., "medical", "fitness").
    -   `questions` (list[str], optional): Specific questions about the image.
-   **Output:** A dictionary containing:
    -   `caption`: Main caption.
    -   `detailed_description`: Enriched description with contextual answers.
    -   `tags`: Extracted tags.
    -   `answers`: Answers to the specific questions.

## 4. Dependencies and Integrations

-   **`transformers` (Hugging Face):** Main library for loading and using the BLIP-2 model.
-   **`Pillow (PIL)`:** For image manipulation (opening, converting).
-   **`httpx`:** For asynchronous image download.
-   **`MessageProcessor`:** This service likely invokes the `VisionService` when an image message is received.
-   **`ConversationOrchestrator`:** Uses the information extracted by the `VisionService` to enrich the conversation context and make decisions.

## 5. Use Cases

-   **Meal Analysis:** A patient in a weight loss program sends a photo of a dish. The `VisionService` describes the food, tries to identify if it is healthy, and extracts tags like "meal," "salad," "nutrition."
-   **Treatment Evaluation:** A patient sends a photo of a skin area to an aesthetic clinic. The service can describe what it sees, helping to triage the case before human intervention.
-   **Progress Tracking:** Patients send "before and after" photos. The analysis can help identify changes and keep the patient engaged.

## 6. Security

-   **100% Local Processing:** No images are sent to external APIs, ensuring total privacy and compliance with LGPD, a critical requirement for health data.
-   **File Cleanup:** Downloaded images are saved in temporary files that are deleted immediately after processing.

## 7. Performance

-   **Loading Cost:** The initial loading of the model is a heavy operation and can consume significant time and memory. `Lazy loading` and the singleton pattern mitigate this by ensuring it happens only once.
-   **CPU Inference:** The model can run on a CPU, but performance will be considerably better in an environment with an available GPU. The current configuration is optimized for CPU.

## 8. Testability

-   The `analyze_image` function can be unit-tested by mocking `httpx` to provide a local image and validating the structure and content of the output dictionary.
-   The synchronous version `analyze_image_sync` facilitates integration with background workers (RQ) that operate synchronously.

## 9. Error Handling

-   **Model Loading Failure:** If the model download or initialization fails (e.g., lack of memory, network problem), an error is logged, and the exception is propagated.
-   **Image Download Failure:** HTTP errors during image download are handled, and the corresponding exception is thrown.
-   **Analysis Errors:** Any failure during the text generation or VQA steps is caught, logged, and the exception is raised to be handled by the upper service layer.
