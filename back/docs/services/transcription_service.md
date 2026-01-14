
# Feature: TranscriptionService

## 1. Description

The `TranscriptionService` is responsible for converting WhatsApp audio messages into text. It uses the `Faster-Whisper` model to perform transcription locally, which ensures privacy and eliminates costs associated with third-party APIs. The service is optimized to run on a CPU and supports the main audio formats sent by WhatsApp.

## 2. Architecture and Design

-   **Local Model (Faster-Whisper):** The choice of `Faster-Whisper` is due to its efficiency (4x faster than the original Whisper) and its ability to run locally, avoiding network latency and API costs. The model is loaded on demand (`lazy loading`) to optimize memory usage.
-   **Asynchronous Processing:** The audio download is done asynchronously to avoid blocking the application.
-   **Temporary File Management:** The downloaded audio is saved to a temporary file, which is processed by the model and then automatically deleted, ensuring no data accumulates on the disk.
-   **Voice Activity Detection (VAD):** The service uses VAD to remove silent segments from the audio, improving the quality and accuracy of the transcription.

## 3. Data Structure

The service primarily deals with:
-   **Input:** URL of the audio file (`str`).
-   **Output:** Transcribed text (`str`).
-   **Internal:** Audio content in bytes and temporary files on the file system.

## 4. Dependencies and Integrations

-   **`faster-whisper`:** Python library for optimized inference of the Whisper model.
-   **`httpx`:** For asynchronously downloading the audio file from the provided URL (usually from the WAHA gateway).
-   **`LLMError`:** Custom exception to encapsulate transcription-related errors, facilitating handling and logging.

## 5. Use Cases

-   **Receiving an Audio Message:** When a patient sends an audio message, the `MessageProcessor` triggers the `TranscriptionService` to convert the audio to text.
-   **Intent Analysis:** The transcribed text is then used by the `IntentDetector` for sentiment analysis, intent extraction, and updating the lead's maturity score.

## 6. Security

-   **Local Processing:** Transcription occurs entirely on the application server, without sending audio data to external services, ensuring the privacy of patient conversations.
-   **Data Cleanup:** Temporary audio files are removed immediately after processing to leave no traces.

## 7. Performance

-   **Lazy Loading:** The transcription model is only loaded into memory when the first audio message is received, saving resources at system startup.
-   **CPU Optimization:** The `compute_type="int8"` setting allows for efficient execution on CPUs, making the solution viable even in environments without a GPU.
-   **VAD (Voice Activity Detection):** By filtering silence, VAD reduces processing time and improves model accuracy.

## 8. Testability

-   The service can be tested in isolation by mocking the `httpx` dependency to simulate downloading audio files and validating that the text output matches the expected result for a given test audio.

## 9. Error Handling

-   **Download Failure:** If the audio download fails, an `LLMError` is thrown with details, and the error is logged.
-   **Transcription Failure:** Errors during model loading or the transcription process are caught and encapsulated in an `LLMError`, indicating the source of the problem (Whisper).
-   **Dependency Not Installed:** If the `faster-whisper` library is not present, an informative `LLMError` is thrown, instructing the developer to install it.
