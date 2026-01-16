# Intent, Urgency, Name Extraction, Scoring

## Responsibilities
Detect intent/urgency via Gemini, extract patient name, and update maturity score accordingly.

## Pipeline (IntentDetector)
- `detect_intent(message, context)`: builds prompt via `PromptTemplates.format_intent_detection_prompt`; calls `GeminiClient.generate_response`; normalizes to upper; accepted intents: INTERESSE_PRODUTO, ORCAMENTO, AGENDAMENTO, DUVIDA_TECNICA, RECLAMACAO, AGRADECIMENTO, OUTRO (fallback).
- `detect_urgency(message, context)`: prompt for urgency; parses JSON `{"urgent": bool, "reason": ...}`; on parse/LLM errors returns False.
- `try_extract_name(session, message, context, conversation)`: prompt for name; expects JSON with `name`, `confidence`; if confidence >= 70, updates `conversation.lead.name` via `LeadRepository` and flushes.
- `generate_name_request(context, maturity_score)`: prompt to decide if bot should ask for name; expects JSON with `should_ask` and `name_request`; returns custom prompt or None.
- `update_maturity_score(session, conversation, message, intent)`: score deltas: INTERESSE_PRODUTO +5, DUVIDA_TECNICA +3, ORCAMENTO +15, AGENDAMENTO +20, RECLAMACAO +0, AGRADECIMENTO +1, OUTRO +0; caps at 100; writes via `LeadRepository`.
- `check_escalation_needed(conversation, intent, is_urgent)`: (logic continues past snippet) used to decide handoff triggers based on intent/urgency/score thresholds.

## Error Handling
- LLM errors rethrown as `LLMError` for intent; urgency/name flows fall back to safe defaults (no urgency, no name change).
- Score update failures raise `DatabaseError`.

## Notes
- Prompts/templates drive behavior; adjust in `PromptTemplates` for new intents or thresholds.
- Name extraction writes immediately; ensure caller coordinates with audit if needed.
