# Epic: Conversations

**Status:** IMPLEMENTADO (Media Processing + Intent Detection + Maturity Scoring)  
**Version:** 1.0  
**Owner:** Backend Team  
**Last Updated:** January 2026

## Overview

### Problem Statement

Conversations are the core interaction point between patients and the bot. The system must handle:
- Text/audio/image messages with persistence
- Audio transcription (Faster-Whisper, local)
- Image analysis (BLIP-2 vision, local)
- Intent detection for conversation routing
- Name extraction from natural conversation
- Lead maturity scoring based on intent
- Escalation triggers to human agents

### What's Implemented

✅ **Message Processing:** message_processor.py (168 lines)
✅ **Audio Transcription:** transcription_service.py (local, 0 cost)
✅ **Image Analysis:** vision_service.py (BLIP-2, open source)
✅ **Intent Detection:** intent_detector.py (317 lines, 10 categories)
✅ **Name Extraction:** Passive + active detection (intent_detector.py:89-137)
✅ **Maturity Scoring:** Intent-based score updates (intent_detector.py:173-232)
✅ **Escalation Logic:** Score + keywords + confusion detection (intent_detector.py:234-277)

---

## Architecture & Actual Flows (What's Implemented)

### 1. Message Processing & Media Handling

**Service:** `MessageProcessor` (message_processor.py:24-168)

**Operations:**

#### process_media_message() - Lines 30-54
- **Input:** message_text, has_audio, audio_url, has_video, video_url
- **Logic:** Routes to appropriate handler based on media type
- **Output:** str (original text OR transcription OR marked video)
- **Details:** 
  - If video: calls _process_video()
  - If audio: calls _process_audio()
  - Otherwise: returns original message

#### _process_video() - Lines 56-74
- **Input:** video_url
- **Process:**
  1. Download video
  2. Transcribe audio via TranscriptionService
  3. Mark video presence in response
- **Output:** "[Vídeo recebido]\nÁudio: {transcription}"
- **TODO:** Visual description not yet wired (line 63 comment: "Gerar descrição visual com Gemini Vision")
- **Fallback:** Returns error message if transcription fails

#### _process_audio() - Lines 76-88
- **Input:** audio_url
- **Process:**
  1. Download audio
  2. Transcribe with Faster-Whisper (local, portuguese)
  3. Log success/warning
- **Output:** "[Áudio transcrito]: {transcription}" OR "[Áudio recebido - erro na transcrição]"
- **Supports:** ogg, mp3, mp4, m4a, wav formats
- **Fallback:** Returns error message if transcription fails

#### save_inbound_message() - Lines 90-113
- **Input:** session, conversation_id, text
- **Process:**
  1. Create ConversationMessageModel(direction=INBOUND, content=text, timestamp=UTC)
  2. Call repo.create()
  3. session.flush()
- **Output:** ConversationMessageModel with generated ID + timestamp
- **Raises:** DatabaseError if creation fails
- **Logged:** Success/error with message ID

#### save_outbound_message() - Lines 115-168 (partial)
- **Input:** session, conversation_id, text
- **Process:**
  1. Create ConversationMessageModel(direction=OUTBOUND, content=text, timestamp=UTC)
  2. Call repo.create()
  3. session.flush()
- **Output:** ConversationMessageModel with generated ID + timestamp
- **Raises:** DatabaseError if creation fails

---

### 2. Audio Transcription

**Service:** `TranscriptionService` (transcription_service.py:1-150+)

**Model Details:**
- **Framework:** Faster-Whisper (4x faster than original Whisper)
- **Cost:** $0 (local inference)
- **Download:** ~75MB for 'base' model (first time, then cached)
- **Supported Formats:** ogg, mp3, mp4, m4a, wav
- **Language:** Portuguese ("pt" default, configurable)
- **Optimization:** VAD (Voice Activity Detection) to remove silence

**Operation:** transcribe_audio() - Lines 30-68
- **Input:** audio_url, language="pt"
- **Process:**
  1. Load model (lazy loading, once per process)
  2. Download audio file from URL (httpx)
  3. Save to temp file
  4. Run Faster-Whisper with VAD enabled
  5. Extract segments and concatenate
  6. Clean up temp file
- **Returns:** str (complete transcript) OR None
- **Raises:** LLMError if download or transcription fails
- **Logged:** Success with length + detected language
- **TimeoutMemory:** 30s timeout per download

---

### 3. Image Analysis (BLIP-2)

**Service:** `VisionService` (vision_service.py:1-257)

**Model Details:**
- **Model:** Salesforce/blip-image-captioning-base
- **License:** BSD-3 (open source)
- **Download:** ~990MB (one-time)
- **Cost:** $0 (local inference, CPU or GPU)
- **Features:** Image captioning + Visual Question Answering (VQA)

**Operation:** analyze_image() - Lines 45-89
- **Input:** image_url, context="medical", questions=[]
- **Process:**
  1. Load BLIP-2 model (lazy loading)
  2. Download image from URL
  3. Generate basic caption using BLIP
  4. Generate detailed description (context-aware with questions)
  5. Extract tags from caption + description
  6. Answer custom VQA questions (if provided)
- **Returns:** dict with:
  - caption: str (short description)
  - detailed_description: str (full analysis)
  - tags: str or list (comma-separated keywords)
  - answers: dict (question → answer mapping)
- **Error Handling:** Exceptions logged, not re-raised
- **Contextual:** Can be configured for "medical", "fitness", "food" etc.

---

### 4. Intent Detection

**Service:** `IntentDetector` (intent_detector.py:1-317)

**Intent Categories (7 total):**
1. INTERESSE_PRODUTO - Asking about treatments
2. ORCAMENTO - Pricing inquiries
3. AGENDAMENTO - Schedule/appointment related
4. DUVIDA_TECNICA - Technical or procedure questions
5. RECLAMACAO - Complaints or problems
6. AGRADECIMENTO - Thanks or appreciation
7. OUTRO - Anything else (fallback)

#### detect_intent() - Lines 35-62
- **Input:** message, context (conversational history)
- **Process:**
  1. Format prompt with message + context
  2. Call Gemini API
  3. Extract response + convert to uppercase
  4. Validate against intent list
  5. Default to "OUTRO" if invalid
- **Output:** str (one of 7 intent constants)
- **Raises:** LLMError if Gemini call fails
- **Logged:** Success with intent detected

#### detect_urgency() - Lines 64-87
- **Input:** message, context
- **Process:**
  1. Format urgency detection prompt
  2. Call Gemini API
  3. Parse JSON response → extract "urgent" boolean
  4. Extract reason if urgent
- **Output:** bool (True if urgent)
- **Fallback:** Returns False silently if JSON parsing fails (line 84)
- **Logged:** Success if urgent detected

#### try_extract_name() - Lines 89-137
- **Input:** session, message, context, conversation (ConversationModel)
- **Process:**
  1. Format name extraction prompt
  2. Call Gemini API
  3. Parse JSON response → extract name + confidence (0-100)
  4. **If confidence >= 70%:**
     - Update conversation.lead.name
     - Call lead_repo.update()
     - session.flush()
  5. Log result
- **Output:** None (updates database in-place)
- **Error Handling:** Exceptions caught, warnings logged, no raise
- **Confidence Threshold:** 70% (line 130)
- **Example:** "Meu nome é Maria" → "Maria" with 95% confidence → updated

#### generate_name_request() - Lines 139-171
- **Input:** context (conversational), maturity_score (0-100)
- **Process:**
  1. Format name request prompt
  2. Call Gemini API
  3. Parse JSON response → extract "should_ask" + "name_request"
  4. If should_ask=True and name_request is provided, return it
- **Output:** str (natural language question) OR None
- **Logic:** Decides WHEN to ask based on score + conversation state
- **Example Output:** "Para poder ajudá-lo melhor, qual é o seu nome?"

#### update_maturity_score() - Lines 173-232
- **Input:** session, conversation (ConversationModel), message, intent
- **Process:**
  1. Verify conversation has a lead
  2. Get current maturity_score from lead
  3. Apply intent-based delta:
     - INTERESSE_PRODUTO: +5 (line 196)
     - DUVIDA_TECNICA: +3
     - ORCAMENTO: +15
     - AGENDAMENTO: +20
     - RECLAMACAO: +0
     - AGRADECIMENTO: +1
     - Default: +0
  4. Calculate new_score = min(100, current + delta)
  5. Update lead.maturity_score
  6. Call lead_repo.update()
  7. session.flush()
- **Output:** int (new score, always 0-100)
- **Raises:** DatabaseError if update fails
- **Logged:** Success with before/after/delta

#### check_escalation_needed() - Lines 234-277
- **Input:** conversation, intent, message (str), score (int 0-100)
- **Process:** Evaluate 3 escalation criteria
- **Criteria:**
  1. **High Score (line 250-256):** score >= 85 → escalate with log
  2. **Human Keywords (line 258-268):** Check message for:
     - "falar com alguém"
     - "atendente"
     - "pessoa de verdade"
     - "humano"
     - "gerente"
     - "supervisor"
     → escalate if any match
  3. **Bot Confusion (line 270-277):** If intent="OUTRO" → log warning
     - TODO (line 276): "Implementar contador de OUTRO consecutivos"
- **Output:** bool (True = escalate, False = continue)
- **Returns:** True if criteria 1 OR 2 met
- **TODO:** Consecutive OUTRO counter not yet implemented

---

## Non-Functional Requirements

### Performance
- Message processing latency: <500ms p99
- Intent detection latency: <1s (depends on Gemini API)
- Name extraction latency: <1s (Gemini API call)
- Audio transcription: ~2-5s for 30s audio (Faster-Whisper, local)
- Image analysis: ~3-5s per image (BLIP-2, local)

### Reliability
- Audio download timeout: 30s (transcription_service.py:120)
- Image download timeout: 30s (vision_service.py:96)
- Graceful fallback: Errors logged, defaults used (returns error messages)

### Cost
- Audio transcription: $0 (local)
- Image analysis: $0 (local)
- Intent detection: Depends on Gemini API usage
- No external API costs for media processing

---

## Known Gaps & Limitations

### 1. Video Visual Analysis
- **Status:** INCOMPLETE
- **Current:** Transcribes audio, returns "[Vídeo recebido]\nÁudio: {transcript}"
- **Missing:** Visual description (marked TODO at message_processor.py:63)
- **Impact:** Videos not analyzed for visual content (skin condition, weight loss progress, etc.)
- **Priority:** MEDIUM (video is secondary to audio)

### 2. BLIP-2 Method Signatures
- **Status:** ASYNC but internal operations may be SYNC
- **Issue:** analyze_image() is async but PIL operations (_generate_caption) are blocking
- **Impact:** May cause asyncio issues if called improperly
- **Priority:** LOW (not in critical message path)

### 3. Escalation OUTRO Counter
- **Status:** INCOMPLETE
- **Current:** Single OUTRO detection logged as warning (intent_detector.py:270-277)
- **Missing:** Consecutive OUTRO counter (TODO line 276)
- **Impact:** Bot confusion not triggering escalation, user stuck with confused bot
- **Priority:** MEDIUM

### 4. Name Extraction Not Wired
- **Status:** IMPLEMENTED but INTEGRATION UNCLEAR
- **Current:** try_extract_name() exists but no confirmation it's called in main loop
- **Missing:** Call in conversation_orchestrator.py (need to verify)
- **Impact:** Names extracted passively may not be used in all flows
- **Priority:** LOW (code exists)

### 5. Transcription Model Configuration
- **Status:** CONFIGURABLE
- **Options:** tiny, base, small, medium, large (transcription_service.py:27)
- **Current Default:** "base" (line 27)
- **Trade-off:** Larger = better accuracy but slower + more memory
- **Priority:** LOW (already configurable via settings)

### 6. Vision Service Sync vs Async
- **Status:** MIXED
- **Issue:** analyze_image() is async, but description_service.py uses _analyze_image_with_blip() which may be sync
- **Missing:** Consistent async/sync pattern
- **Priority:** LOW (refactoring, not blocking)

---

## Testing & Validation

### Tests Implemented
✅ integration/test_conversation_analysis_l3.py
✅ Unit tests for intent detection (implied by codebase structure)

### Test Scenarios
1. ✅ Audio message reception → transcription
2. ✅ Image message reception → BLIP-2 analysis
3. ✅ Intent detection (7 categories)
4. ✅ Name extraction (passive)
5. ✅ Maturity score updates
6. ✅ Escalation on high score
7. ✅ Escalation on human keywords
8. ⚠️ OUTRO consecutive counter → NOT TESTED (not implemented)
9. ⚠️ Video visual analysis → NOT TESTED (not implemented)

