---
title: Context Builder
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Context Builder

## Brief description of the requirements and goals of the feature
This service manages the conversational context using ChromaDB. It is responsible for retrieving the history of a conversation to provide context to the AI and for saving new interactions to maintain a coherent dialogue. Its main goal is to give the chatbot a "memory" of the current conversation.

## Architecture and design
The `ContextBuilder` uses a `ChromaClient` to interact with the ChromaDB vector database, which stores conversation history as embeddings for semantic search.

- **`get_conversation_context`**: This function queries ChromaDB for the most recent and relevant messages for a given `conversation_id`. It retrieves a limited number of past interactions and formats them into a single string. This string is then passed to the LLM to provide context for generating the next response.
- **`save_to_chroma`**: After a user-bot interaction pair is complete, this function takes the text (`User: ... Bot: ...`) and associated metadata (like intent and score) and saves it as a new document in ChromaDB. This keeps the conversation history up-to-date for future context retrieval.

## Tasks
- [x] Retrieve conversation history from ChromaDB based on a conversation ID.
- [x] Save new user/bot interactions and their metadata to ChromaDB.
- [x] Format the retrieved context into a simple text block for the LLM.
- [ ] Implement a more sophisticated context formatting strategy if needed (e.g., summarizing older messages).

## Open questions
1. What is the optimal number of past interactions (`limit`) to retrieve for providing sufficient context without overwhelming the LLM's context window? (Currently set to 5)
2. Is the current method of joining context parts with "---" the most effective for the LLM to understand the conversation flow?
3. Should there be a mechanism to periodically summarize very long conversations to reduce the size of the retrieved context?
