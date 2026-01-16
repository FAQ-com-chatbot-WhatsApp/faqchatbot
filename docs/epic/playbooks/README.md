# Epic: Playbooks

**Status:** IMPLEMENTADO (CRUD + ChromaDB RAG + Auto-Indexing)  
**Version:** 1.0  
**Owner:** Backend Team  
**Last Updated:** January 2026

## Overview

### Problem Statement

Playbooks are structured conversation scripts for common topics (e.g., "Emagrecimento Saudável"). The system must:
- Organize playbooks by topic
- Store conversation steps with message templates
- Enable semantic search (RAG) to find relevant playbooks
- Auto-index playbooks in ChromaDB for fast retrieval
- Support flexible step ordering and message binding

### What's Implemented

✅ **Topic Management:** create, get, list, update, delete (playbook_service.py:63-96)
✅ **Playbook CRUD:** create, get, list, update, delete (playbook_service.py:102-151)
✅ **Playbook Steps:** add, get, reorder, delete with auto-reindex (playbook_service.py:167-273)
✅ **ChromaDB RAG:** Semantic search on playbooks (playbook_service.py:293-340)
✅ **Auto-Indexing:** Embeddings generated on create/update (playbook_service.py:346-442)

---

## Architecture & Actual Flows (What's Implemented)

### 1. Topic Management

**Service:** `PlaybookService` (playbook_service.py:24-152)

#### create_topic() - Lines 63-76
**Input:**
- name: str (must be unique)
- description: str | None
- category: str | None (for grouping)
- active: bool (default True)

**Process:**
1. Create TopicModel
2. Call topic_repo.create()
3. Return created topic

**Output:** TopicModel

#### get_topic() - Lines 78-80
**Input:** topic_id: str
**Output:** TopicModel | None

#### list_topics() - Lines 82-84
**Input:**
- active_only: bool (default False)
- skip: int (default 0)
- limit: int (default 100)

**Output:** list[TopicModel]

#### update_topic() - Lines 86-88
**Input:**
- topic_id: str
- **kwargs: arbitrary fields to update

**Output:** TopicModel | None

#### delete_topic() - Lines 90-92
**Input:** topic_id: str
**Process:** Cascades to playbooks
**Output:** bool (success)

---

### 2. Playbook Management

#### create_playbook() - Lines 102-127
**Input:**
- topic_id: str
- name: str
- description: str | None
- active: bool (default True)

**Process:**
1. Create PlaybookModel(topic_id, name, description, active)
2. Call playbook_repo.create()
3. Call _generate_playbook_embedding() to index in ChromaDB
4. Log success (or warning if indexing fails)

**Output:** PlaybookModel

**Side Effect:** Playbook indexed in ChromaDB immediately

#### list_playbooks_by_topic() - Lines 133-136
**Input:**
- topic_id: str
- active_only: bool (default False)

**Output:** list[PlaybookModel]

#### update_playbook() - Lines 138-151
**Input:**
- playbook_id: str
- **kwargs: fields to update

**Process:**
1. Update via playbook_repo.update()
2. Call _generate_playbook_embedding() to reindex
3. Log success (or warning if reindexing fails)

**Output:** PlaybookModel | None

**Side Effect:** Playbook reindexed in ChromaDB

#### delete_playbook() - Lines 153-165
**Input:** playbook_id: str

**Process:**
1. Lookup embedding by playbook_id
2. If exists: delete from ChromaDB (by chroma_doc_id)
3. Delete playbook from DB (cascades to steps)

**Output:** bool (success)

---

### 3. Playbook Steps

#### add_step() - Lines 167-200
**Input:**
- playbook_id: str
- message_id: str (UUID of message template)
- step_order: int | None (auto-assign if not provided)
- context_hint: str | None (context for LLM)

**Process:**
1. If step_order not provided: auto-assign next order
2. Create PlaybookStepModel
3. Call step_repo.create()
4. Call _generate_playbook_embedding() to reindex playbook
5. Log success

**Output:** PlaybookStepModel

#### get_playbook_steps() - Lines 202-204
**Input:**
- playbook_id: str
- include_messages: bool (default False)

**Output:** list[PlaybookStepModel]

#### get_playbook_steps_with_details() - Lines 206-258
**Input:** playbook_id: str

**Process:**
1. Get steps with message details
2. For each step:
   - Lookup message by message_id
   - Extract message fields (type, title, description, tags, text, caption, media_url, location)
   - Build detailed dict with step + message data
3. Return list of detailed dicts

**Output:** list[dict]

**Usage:** Passed to Gemini as context for structured responses

#### reorder_steps() - Lines 260-262
**Input:**
- playbook_id: str
- step_id_order: list[tuple[str, int]] (step_id, new_order)

**Output:** bool (success)

#### delete_step() - Lines 264-273
**Input:** step_id: str

**Process:**
1. Lookup step to get playbook_id
2. Delete step from DB
3. Reindex playbook in ChromaDB

**Output:** bool (success)

---

### 4. Semantic Search (RAG) via ChromaDB

#### search_playbooks() - Lines 293-340
**Input:**
- query: str (natural language query, e.g., "botox preço procedimento")
- top_k: int (default 3, number of results)
- active_only: bool (default True)

**Process:**
1. Build ChromaDB filter (where active=true if active_only)
2. Call self.playbooks_collection.query(query_texts=[query], n_results=top_k)
3. For each result:
   - Extract metadata (playbook_id, name, description, topic_name)
   - Extract distance score
   - Convert distance to similarity (1 - distance)
4. Return list of PlaybookSearchResult

**Output:** list[PlaybookSearchResult]

**Model:** Gemini embeddings (cosine similarity)

#### _generate_playbook_embedding() - Lines 346-442
**Input:** playbook_id: str

**Process:**
1. Lookup playbook + topic + steps (with message details)
2. Build rich embedding text from:
   - Topic: name, category, description
   - Playbook: name, description
   - Steps: order, message type, title, description, tags, context hints
3. Build ChromaDB doc ID: f"playbook_{playbook_id}"
4. Check if embedding exists:
   - **If exists:** Update in ChromaDB + DB
   - **If new:** Add to ChromaDB + save reference in DB
5. Log success

**Output:** None (updates database + ChromaDB)

---

## Data Models

**TopicModel:**
- id: UUID
- name: str (unique)
- description: str | None
- category: str | None
- active: bool
- created_at: datetime

**PlaybookModel:**
- id: UUID
- topic_id: UUID (FK)
- name: str
- description: str | None
- active: bool
- created_at: datetime

**PlaybookStepModel:**
- id: UUID
- playbook_id: UUID (FK)
- message_id: UUID (FK)
- step_order: int
- context_hint: str | None
- created_at: datetime

**PlaybookEmbeddingModel:**
- id: UUID
- playbook_id: UUID (FK, unique)
- embedding_text: str (text used for embedding)
- chroma_doc_id: str (ChromaDB document ID)
- created_at: datetime

---

## Known Gaps & Limitations

### 1. Message-Step Binding
- **Status:** IMPLEMENTED but NO VALIDATION
- **Current:** add_step() accepts any message_id
- **Missing:** FK constraint validation on message_id
- **Impact:** Can reference non-existent messages
- **Priority:** MEDIUM

### 2. Embedding Generation Reliability
- **Status:** ASYNC but may fail silently
- **Current:** _generate_playbook_embedding() tries/except (line 367, 428)
- **Issue:** Failures logged as warnings, not retried
- **Missing:** Retry logic, manual reindex operation
- **Impact:** Playbooks not searchable if embedding fails
- **Priority:** MEDIUM

### 3. ChromaDB Persistence
- **Status:** CONFIGURED via settings.CHROMA_PERSIST_DIR
- **Issue:** Single machine persistence, not distributed
- **Missing:** Backup, multi-region sync
- **Priority:** LOW (works for MVP)

### 4. Step Ordering
- **Status:** NUMERIC order but no uniqueness constraint
- **Issue:** Multiple steps can have same order (no unique constraint)
- **Missing:** Enforce unique (playbook_id, step_order) constraint
- **Impact:** Ambiguous ordering
- **Priority:** LOW

### 5. Playbook Versioning
- **Status:** NOT IMPLEMENTED
- **Missing:** Track playbook changes over time (e.g., v1.0, v1.1)
- **Impact:** Can't A/B test different versions
- **Priority:** LOW (not in MVP)

---

## Testing & Validation

### Tests Implemented
- ✅ Playbook CRUD operations
- ✅ Semantic search
- ✅ Step management

### Test Scenarios
1. ✅ Create topic
2. ✅ Create playbook (auto-index)
3. ✅ Add steps to playbook
4. ✅ Get playbook with details
5. ✅ Update playbook (reindex)
6. ✅ Semantic search (query by text)
7. ✅ Delete step (reindex)
8. ⚠️ Message-step binding validation → NOT TESTED
9. ⚠️ Embedding failure recovery → NOT TESTED
10. ⚠️ ChromaDB sync on distributed systems → NOT TESTED

