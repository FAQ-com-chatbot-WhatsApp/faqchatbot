# Bot → Human Handoff

## Scope
Manage escalation lifecycle when bot should pass control to a human and when to return.

## Operations (handoff_service)
- `trigger_handoff(conversation_id, reason)`: marks conversation for human intervention; persists status change; logs audit (via underlying repos) and returns handoff record.
- `assign_to_human(handoff_id, user_id)`: assigns a human agent to the active handoff; updates status and agent reference; sends transition message to conversation (currently contains emojis in codebase; TODO: sanitize).
- `mark_as_completed(handoff_id, resolution_note=None)`: closes handoff, records resolution note, updates conversation status back to bot-ready, and logs metrics via analytics service.
- `return_to_bot(handoff_id, note=None)`: clears active handoff and places conversation back under bot control with a transition message (also currently contains emojis; TODO cleanup).

## Behaviors / Side Effects
- Conversation state is updated alongside handoff records; ensures future messages route to the right actor.
- Metrics service invoked on completion to track handoff outcomes.
- Transition messages are sent to the customer; current content includes emojis—should be replaced per logging guidelines.

## Gaps / Risks
- No explicit SLA timers; upstream orchestration must decide when to call `trigger_handoff` (e.g., urgency, high score, complaints).
- No rate limiting for repeated handoffs; caller responsible.
- Transition message copy needs audit for tone/compliance; remove emojis.
