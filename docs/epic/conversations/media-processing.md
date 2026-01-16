# Media Processing (Audio/Video/Text)

## Purpose
Handle inbound multimedia so downstream intent/routing logic always receives text: transcribe audio, mark video, and persist messages.

## Workflow
1) Entry via `MessageProcessor.process_media_message(message_text, has_audio, audio_url, has_video, video_url)`
- If video with `video_url`: `_process_video` transcribes audio track via `TranscriptionService.transcribe_audio(language="pt")`. Vision description is TODO; current return is a marker string `[Vídeo recebido]` plus transcription or failure text.
- If audio with `audio_url`: `_process_audio` transcribes via Faster-Whisper; wraps success as `[Áudio transcrito]: <text>` or returns failure markers.
- Fallback: plain text returned unchanged.

2) Transcription engine (`TranscriptionService`)
- Lazy-loads Faster-Whisper (local, zero API cost). Model size configurable via `WHISPER_MODEL`, CPU int8 by default.
- Downloads media with `httpx`, writes temp file, runs `model.transcribe` with VAD and beam search, concatenates segments.
- Errors surface as `LLMError`; temp files cleaned up.

3) Persistence helpers
- `save_inbound_message(session, conversation_id, text)` and `save_outbound_message(...)` insert `ConversationMessageModel` with `MessageDirection` and UTC timestamp using `ConversationMessageRepository`; on failure raise `DatabaseError`.

## Notes / Gaps
- Video visual description is TODO; only audio transcription + marker is returned.
- Return strings still include Portuguese markers; align language if needed.
- Ensure Faster-Whisper dependency (`faster-whisper`) present in env.
- Consider rate limiting / size limits upstream; not handled here.
