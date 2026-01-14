
# Feature: Playbook Tools (Function Calling)

## 1. Description

`playbook_tools.py` defines a set of "tools" that can be used by a large language model (LLM), such as Google Gemini, through *Function Calling*. These tools allow the AI to autonomously interact with the Playbook system, searching for information, retrieving content, and sending structured messages to the patient. Essentially, it exposes the functionalities of `PlaybookService` and other services in a way that the AI can understand and invoke.

## 2. Architecture and Design

-   **Function Calling Pattern:** The architecture is entirely based on the *Function Calling* paradigm popularized by modern LLMs. Each tool is defined by two parts:
    1.  **Declaration:** A JSON dictionary (`..._DECLARATION`) that describes the tool to the AI: its name, what it does, what parameters it accepts, and which are mandatory. The description is written in natural language and is crucial for the AI to know *when* and *how* to use the tool.
    2.  **Implementation (Tool Function):** The Python function (`..._tool`) that executes the actual logic when the AI decides to invoke the tool.
-   **Tool Registry:** A central registry (`PLAYBOOK_TOOLS_DECLARATIONS` and `execute_playbook_tool`) is used to group all tool declarations and to dispatch the execution to the correct function based on the tool name. This simplifies integration with the `ConversationOrchestrator`.
-   **Service Abstraction:** The tool functions act as a façade, abstracting the complexity of the underlying services (`PlaybookService`, `MessageService`, `ConversationService`). The AI does not need to know about repositories or database sessions; it just calls the tool with the described parameters.

## 3. Data Structure

-   **Tool Declarations:** Python dictionaries that follow a specific structure (similar to JSON Schema) with `name`, `description`, and `parameters`.
-   **Arguments and Returns:** The tool functions receive simple arguments (strings, integers) and return dictionaries or lists of dictionaries, which are formats easily serializable to JSON and understandable by the AI.

## 4. Dependencies and Integrations

-   **`PlaybookService`:** Used by the `search_playbooks_tool` for semantic search and by the `get_playbook_steps_tool` to get the content of a playbook.
-   **`MessageService` and `ConversationService`:** Used by the `send_playbook_message_tool` to get the details of the message to be sent and the target conversation.
-   **`ConversationOrchestrator`:** This is the main consumer. The orchestrator passes the list of tool declarations (`PLAYBOOK_TOOLS_DECLARATIONS`) to the Gemini API in each conversation turn. If Gemini decides to use a tool, the orchestrator receives the tool name and arguments, calls `execute_playbook_tool`, and sends the result back to Gemini so it can formulate the final response to the user.
-   **`waha_service` (implied):** The `send_clinic_location_tool` depends on a function that, in turn, uses the WAHA service to send the location message to WhatsApp.

## 5. Use Cases

-   **Autonomous Content Search:**
    1.  Patient: "Do you do lip fillers?"
    2.  Gemini (AI) sees the question and decides to use the `search_playbooks` tool with the query "lip filler".
    3.  The tool returns a list of relevant playbooks, including one called "Lip Filler Procedure".
    4.  Gemini then uses the `get_playbook_steps` tool with the ID of the "Lip Filler Procedure" playbook to see the available messages.
    5.  Gemini chooses an introductory message from the playbook and sends it using `send_playbook_message`, perhaps with a custom introduction like "Of course! I found some material here about our lip filler procedure."
-   **Sending Location:**
    1.  Patient: "Where is the clinic located?"
    2.  Gemini recognizes the location intent and invokes the `send_clinic_location_tool`, passing the conversation's `chat_id`.
    3.  The tool sends a map with the location pin directly to the patient's WhatsApp.

## 6. Security

-   Tools are a powerful entry point for the AI to interact with the system. Security lies in designing tools that perform well-defined and safe actions. No tool in this file performs destructive operations or exposes sensitive data directly.

## 7. Performance

-   The performance of the `search_playbooks` tool depends on the efficiency of ChromaDB.
-   The other tools are generally fast, as they rely on ID-based lookups in the database. The `send_playbook_message_tool` has a pending implementation for the actual sending, which would be a network operation.

## 8. Testability

-   Each tool function can be tested individually by mocking the services it consumes.
-   It is possible to test `execute_playbook_tool` to ensure it calls the correct function based on the `tool_name`.

## 9. Error Handling

-   Each tool function is wrapped in a generic `try...except` block. In case of an error, it logs the exception and returns a structured error response (e.g., `{"success": False, "error": "..."}`), which can be processed by the AI to try a different approach or inform the user about the problem.

