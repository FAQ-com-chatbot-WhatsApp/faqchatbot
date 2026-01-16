# Feature: Playbook Semantic Search (RAG)

**Epic:** Playbooks  
**Status:** MVP Complete (Core Implemented)  
**Owner:** Backend & AI Team  
**Implementation:** 442 lines (playbook_service.py)  
**Tests:** [back/tests/unit/services/test_playbook_service.py](back/tests/unit/services/test_playbook_service.py)

## What's Implemented ✅

### 1. ChromaDB Integration (Vector Database)
- **Initialize**: Create/get "playbooks" collection
  - Vector space: Cosine similarity (semantic search)
  - Persistence: Local disk storage
  - Collection: All playbook embeddings indexed
  - Metadata: For filtering by topic, category

### 2. Playbook Creation with Indexing
- **create_playbook()**: Create playbook + auto-index
  - Input: topic_id, name, description, enabled flag
  - Create PlaybookModel (database)
  - Return playbook entity
  - Trigger: Auto-index embeddings (async)

### 3. Playbook Steps Management
- **create_step()**: Add step to playbook
  - Input: playbook_id, step_number, message (text)
  - Create PlaybookStepModel
  - Store message content
  - Link to playbook

### 4. Semantic Search (Core RAG Feature)
- **search_playbooks(query: str)**: Find matching playbooks
  - Input: query string (user message or intent)
  - Vectorize query using Gemini embeddings
  - ChromaDB similarity search (cosine distance)
  - Return top-K playbooks (default K=3)
  - Include: playbook_id, name, score (relevance), steps

### 5. Playbook Retrieval for LLM
- **get_playbook_with_steps()**: Fetch full playbook content
  - Input: playbook_id
  - Query: PlaybookModel + PlaybookStepModel (all steps)
  - Return: Complete playbook with ordered steps
  - Used by Gemini to generate responses

### 6. Embedding Management
- **index_playbook()**: Generate + store embeddings
  - Input: playbook content (description + steps)
  - Call Gemini embedding API
  - Create PlaybookEmbeddingModel
  - Add to ChromaDB collection
  - Store both DB + vector store

### 7. Topic Management (Organization)
- **create_topic()**: Create topic hierarchy
  - Input: name, description, category
  - Group playbooks by topic (e.g., "Weight Loss", "Facial Treatments")
  - Filter playbooks by topic when searching

### 8. CRUD Operations
- **list_playbooks()**: Get all playbooks (paginated)
- **get_playbook()**: Fetch single playbook
- **update_playbook()**: Modify playbook details
- **delete_playbook()**: Remove playbook + cascade delete steps

## Code References

**PlaybookService:** [back/src/robbot/services/playbook_service.py](back/src/robbot/services/playbook_service.py) (442 lines)
- `search_playbooks()` - Lines ~150-200: Semantic search
- `get_playbook_with_steps()` - Lines ~200-220: Fetch full playbook
- `create_playbook()` - Lines ~100-130: Create + index
- `create_step()` - Lines ~130-150: Add playbook step
- `index_playbook()` - Lines ~300-350: Generate embeddings

**ChromaDB Integration:**
- Collection name: "playbooks"
- Vector space: Cosine similarity
- Persistence: `settings.CHROMA_PERSIST_DIR`
- Client: chromadb.Client

**Embeddings:**
- Model: Gemini embeddings API
- Dimension: 768 (standard)
- Cached: PlaybookEmbeddingModel

**Repositories:**
- [PlaybookRepository](back/src/robbot/adapters/repositories/playbook_repository.py) - Playbook CRUD
- [PlaybookStepRepository](back/src/robbot/adapters/repositories/playbook_step_repository.py) - Steps CRUD
- [PlaybookEmbeddingRepository](back/src/robbot/adapters/repositories/playbook_embedding_repository.py) - Embedding storage
- [TopicRepository](back/src/robbot/adapters/repositories/topic_repository.py) - Topic CRUD

**Models:**
- [PlaybookModel](back/src/robbot/infra/db/models/playbook_model.py)
  - id, topic_id (FK), name, description, enabled
- [PlaybookStepModel](back/src/robbot/infra/db/models/playbook_step_model.py)
  - id, playbook_id (FK), step_number, message, instructions
- [PlaybookEmbeddingModel](back/src/robbot/infra/db/models/playbook_embedding_model.py)
  - id, playbook_id (FK), embedding_vector (768d)

## Gaps ❌

| Gap | Priority | Impact |
|-----|----------|--------|
| Playbook versioning | MEDIUM | Cannot track playbook history |
| A/B testing playbooks | MEDIUM | Cannot test different response strategies |
| Playbook analytics (which used) | LOW | Don't know which playbooks get used most |
| Automatic playbook suggestions | LOW | Bot can't recommend playbooks (manual only) |
| Playbook translation | LOW | Only Portuguese/English |

## RAG (Retrieval Augmented Generation) Flow

```
User message: "I want to lose weight and I'm diabetic"
  ↓
Vectorize message (Gemini embeddings)
  ↓
search_playbooks("I want to lose weight...")
  ↓
ChromaDB similarity search:
  Query embedding vs all playbook embeddings
  Top-3 results:
    1. "Weight Loss Program" (score 0.95)
    2. "Dietary Management" (score 0.82)
    3. "Fitness Basics" (score 0.71)
  ↓
Fetch full playbook: "Weight Loss Program"
  ↓
get_playbook_with_steps(playbook_id="pb-123")
  Returns:
    - Topic: "Weight Loss"
    - Name: "Weight Loss Program"
    - Steps: [
        Step 1: "Understand patient goals (weight target)"
        Step 2: "Medical evaluation (health conditions)"
        Step 3: "Nutrition plan (personalized diet)"
        Step 4: "Exercise recommendations"
        Step 5: "Follow-up schedule"
      ]
  ↓
Pass to Gemini (RAG context):
  Prompt: "User wants weight loss (diabetic). Here's a structured approach: [playbook steps]"
  ↓
Gemini generates response:
  "I see you're interested in our weight loss program. Since you mentioned you're diabetic,
   we'll need a medical evaluation first to ensure the program is safe for you.
   Have you had any previous treatments or medications for diabetes?"
  ↓
Send response to user
```

## Playbook Structure Example

```
Topic: "Emagrecimento Saudável" (Healthy Weight Loss)
  ├─ Playbook: "Initial Consultation"
  │   ├─ Step 1: "Understand patient goals (weight target, timeline)"
  │   ├─ Step 2: "Ask about previous weight loss attempts"
  │   ├─ Step 3: "Screen for medical conditions (diabetes, etc.)"
  │   └─ Step 4: "Explain our comprehensive approach"
  │
  ├─ Playbook: "Medical Evaluation"
  │   ├─ Step 1: "Order blood tests (cholesterol, glucose)"
  │   ├─ Step 2: "Metabolic assessment"
  │   └─ Step 3: "Medication review"
  │
  └─ Playbook: "Nutrition Planning"
      ├─ Step 1: "Assess current diet"
      ├─ Step 2: "Set caloric target"
      ├─ Step 3: "Create meal plan"
      └─ Step 4: "Supplement recommendations"
```

## Integration Points

**Called by:**
- ConversationOrchestrator (search for playbooks in process_inbound_message)
- Gemini function calling (retrieve playbooks via tool)

**Calls:**
- PlaybookRepository (CRUD)
- ChromaDB client (embeddings storage + retrieval)
- Gemini API (generate embeddings)

**Used in:**
- Response generation (playbook steps provide context)
- Intent detection (playbook helps classify intent)
- SPIN Selling phase selection (playbooks define phases)

## Testing Strategy

### Unit Tests

```python
def test_search_playbooks_returns_sorted():
    """Search returns playbooks by relevance score"""
    
def test_search_playbooks_empty_query():
    """Empty query returns default playbooks"""
    
def test_search_playbooks_topic_filter():
    """Search filtered by topic works"""
    
def test_create_playbook_indexes_embeddings():
    """New playbook automatically indexed in ChromaDB"""
    
def test_get_playbook_with_steps_ordered():
    """Steps returned in correct order"""
    
def test_playbook_deletion_cascades():
    """Deleting playbook removes all steps + embeddings"""
    
def test_embedding_storage_retrieval():
    """Embeddings stored and retrieved correctly"""
```

### Integration Tests

```python
def test_playbook_search_complete_flow():
    """Create playbook → search → retrieve steps"""
    
def test_playbook_rag_in_conversation():
    """Playbook used in response generation"""
```

## Security Considerations

1. **Authorization**: Only admins can create/edit playbooks
2. **Input Validation**: Playbook content sanitized (no injection)
3. **Audit Trail**: All playbook changes logged
4. **Embedding Privacy**: Embeddings not exposed to users
5. **ChromaDB Security**: Local storage (no cloud exposure)

## Performance Considerations

**Search Performance:**
- ChromaDB similarity search: ~10-50ms
- Top-K retrieval (default K=3): Fast
- Embedding generation: ~100-200ms (cached)

**Storage:**
- Each embedding: ~3KB (768 dimensions x 4 bytes)
- 100 playbooks: ~300KB embeddings
- Scales well (even 1000 playbooks = 3MB)

## Next Steps (Roadmap)

### High Priority
1. **Playbook versioning** - Track changes over time
2. **Playbook analytics** - Track which playbooks used most
3. **Admin UI** - Manage playbooks visually

### Medium Priority
1. A/B testing (compare different playbook versions)
2. Automatic playbook suggestions (bot recommends playbooks)
3. Playbook translation (multi-language)

### Low Priority
1. Playbook collaboration (team editing)
2. Playbook templates (quick setup)
3. Playbook performance metrics (conversion by playbook)
