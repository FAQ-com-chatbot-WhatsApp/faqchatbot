---
title: Playbook Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Playbook Service (`playbook_service.py`)

## Brief description of the requirements and goals of the feature
This service manages the entire "Playbook" system, which allows for the creation of structured, multi-step conversation scripts. It handles the creation, organization, and retrieval of these scripts. A key feature is its integration with a vector database (ChromaDB) to enable semantic search (RAG - Retrieval-Augmented Generation), allowing the bot to find the most relevant playbook for a user's query in real-time.

## Architecture and design
The `PlaybookService` is a comprehensive service that manages three main entities: `Topics`, `Playbooks`, and `PlaybookSteps`. It uses a combination of a relational database (via SQLAlchemy repositories) for structured data and a vector database (ChromaDB) for semantic search.

- **Entity Management (CRUD)**:
    - **Topics**: High-level categories for organizing playbooks (e.g., "Weight Loss," "Facial Treatments").
    - **Playbooks**: The core conversation scripts, each belonging to a topic.
    - **Steps**: The individual messages or actions within a playbook, executed in a specific order.

- **Semantic Search (RAG)**:
    - **`_generate_playbook_embedding`**: This is the core of the RAG system. When a playbook is created or updated, this private method compiles a rich text document containing the playbook's name, description, topic, and details from all its steps. This text is then converted into a vector embedding and stored in ChromaDB.
    - **`search_playbooks`**: This function takes a user's query, converts it into an embedding, and queries ChromaDB to find the playbooks with the most similar embeddings (i.e., the most semantically relevant scripts). It returns a ranked list of playbooks.

- **Indexing**: The service automatically indexes or re-indexes a playbook in ChromaDB whenever it is created, updated, or a step is added/deleted, ensuring the search index is always up-to-date.

- **Retrieval for LLM**:
    - **`get_playbook_steps_with_details`**: When a playbook is selected, this function retrieves all its steps and formats them into a detailed structure that the LLM can easily use to guide the conversation, including message text, media URLs, and other metadata.

## Tasks
- [x] Perform CRUD operations for Topics, Playbooks, and Playbook Steps.
- [x] Automatically generate and store vector embeddings for playbooks in ChromaDB.
- [x] Keep the ChromaDB index synchronized with changes to playbooks.
- [x] Implement a semantic search function to find relevant playbooks based on a natural language query.
- [x] Retrieve and format the full details of a playbook's steps for use by the LLM.
- [x] Handle the deletion of playbooks from both the relational database and the ChromaDB index.

## Open questions
1. How is the `search_playbooks` function integrated into the main conversation flow? Is it called by the `ConversationOrchestrator` at a specific point?
2. The relevance score is calculated as `1 - distance`. Is this cosine similarity, and is it the most effective metric for ranking playbook relevance?
3. What is the underlying embedding model used by ChromaDB in this implementation, and is it aligned with the model used for generating query embeddings?
