# Playbooks CRUD + Indexing

## Purpose
Manage playbooks/steps and keep semantic index (ChromaDB) in sync for RAG and function-calling flows.

## Playbook CRUD
- `create_playbook(topic_id, name, description=None, active=True)`: stores `PlaybookModel`; immediately calls `_generate_playbook_embedding` to index; logs warning if indexing fails.
- `get_playbook(id)`, `list_playbooks_by_topic(topic_id, active_only=False)`: simple retrievals.
- `update_playbook(id, **kwargs)`: updates then reindexes; warns if indexing fails.
- `delete_playbook(id)`: removes Chroma doc via `embedding_repo` then cascades delete in DB.

## Steps
- `add_step(playbook_id, message_id, step_order=None, context_hint=None)`: auto-assigns order if missing, creates `PlaybookStep`, reindexes playbook.
- `get_playbook_steps(playbook_id, include_messages=False)`: ordered list of steps.
- `get_playbook_steps_with_details(playbook_id)`: returns step dicts enriched with message fields (type/text/media url/caption/tags). Skips steps whose messages are missing and logs warning.

## Indexing
- `_generate_playbook_embedding(playbook_id)` (called on create/update/step add) builds embedding in ChromaDB via `playbooks_collection`; stored in `embedding_repo` with `chroma_doc_id`.
- Delete removes from Chroma; no partial reindex.

## Orchestration Hook
- Playbook function-calling mixin (`PlaybookOrchestrationMixin`) registers tool declarations and handles a loop for tool execution. Current function-call extraction is stubbed (returns None) so Gemini tool calling is not fully wired yet; prompt already teaches LLM when/how to call playbook tools.

## Gaps
- Video frame analysis TODO in description/vision path; indexing assumes steps/messages are present and valid.
- Tool-calling integration is stubbed; when wired, replace `_extract_function_call` logic.
