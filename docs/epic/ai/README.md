# Epic: AI & Natural Language

**Status:** IMPLEMENTADO (Intent Detection + Image Analysis + Transcription + Description)  
**Version:** 1.0  
**Owner:** Backend Team  
**Last Updated:** January 2026

## Overview

### Problem Statement

AI capabilities enable the bot to:
- Detect patient intent from messages (7 categories)
- Analyze images (vision analysis, VQA)
- Transcribe audio to text
- Generate metadata descriptions
- Extract patient names from conversations

### What's Implemented

✅ **Intent Detection:** 7 intents with confidence scoring (intent_detector.py:35-277)
✅ **Urgency Detection:** Medical urgency from messages (intent_detector.py:64-87)
✅ **Name Extraction:** Passive + active name detection (intent_detector.py:89-171)
✅ **Image Analysis:** BLIP-2 vision (local, open source) (vision_service.py:45-257)
✅ **Audio Transcription:** Faster-Whisper (local, 0 cost) (transcription_service.py:30-100+)
✅ **Description Generation:** Media metadata (description_service.py:30-226)
✅ **Maturity Score Updates:** Intent-based scoring (intent_detector.py:173-232)
✅ **Escalation Detection:** Multi-factor triggers (intent_detector.py:234-277)

---

## Architecture & Actual Flows (What's Implemented)

### 1. Intent Detection

**Service:** `IntentDetector` (intent_detector.py:1-317)

**Intent Categories (7 total):**
1. INTERESSE_PRODUTO - Asking about treatments/procedures
2. ORCAMENTO - Pricing inquiries
3. AGENDAMENTO - Schedule or appointment requests
4. DUVIDA_TECNICA - Technical/procedure questions
5. RECLAMACAO - Complaints or problems
6. AGRADECIMENTO - Thanks or gratitude
7. OUTRO - Anything else (fallback)

#### detect_intent() - Lines 35-62
**Input:**
- message: str (patient message)
- context: str (conversation history)

**Process:**
1. Format prompt with message + context
2. Call Gemini API
3. Parse response + uppercase
4. Validate against 7 intent constants
5. Default to "OUTRO" if invalid

**Output:** str (one of 7 intents)

**Raises:** LLMError if Gemini fails

#### detect_urgency() - Lines 64-87
**Input:**
- message: str
- context: str

**Process:**
1. Format urgency detection prompt
2. Call Gemini API
3. Parse JSON → extract "urgent": bool + "reason": str
4. Return boolean

**Output:** bool (True if urgent)

**Fallback:** Returns False silently if JSON parsing fails (line 84)

#### try_extract_name() - Lines 89-137
**Input:**
- session: SQLAlchemy session
- message: str
- context: str
- conversation: ConversationModel

**Process:**
1. Format name extraction prompt
2. Call Gemini API
3. Parse JSON → extract "name" + "confidence" (0-100)
4. **If confidence >= 70%:**
   - Update conversation.lead.name
   - Call lead_repo.update()
   - session.flush()
5. Log result

**Output:** None (updates database in-place)

**Error Handling:** Catches all exceptions, logs warnings, no raise

**Confidence Threshold:** 70% (line 130)

#### generate_name_request() - Lines 139-171
**Input:**
- context: str (conversation history)
- maturity_score: int (0-100)

**Process:**
1. Format name request prompt
2. Call Gemini API
3. Parse JSON → extract "should_ask": bool + "name_request": str
4. Return str or None

**Output:** str (natural language request) OR None

**Logic:** Decides WHETHER to ask based on score + context

#### update_maturity_score() - Lines 173-232
**Input:**
- session: SQLAlchemy session
- conversation: ConversationModel
- message: str
- intent: str

**Process:**
1. Verify conversation has a lead
2. Get current score
3. Apply intent-based delta (line 196-201):
   - INTERESSE_PRODUTO: +5
   - DUVIDA_TECNICA: +3
   - ORCAMENTO: +15
   - AGENDAMENTO: +20
   - RECLAMACAO: +0
   - AGRADECIMENTO: +1
   - Default: +0
4. new_score = min(100, current + delta)
5. Update lead.maturity_score
6. Call lead_repo.update()
7. session.flush()

**Output:** int (new score, always 0-100)

**Raises:** DatabaseError if update fails

#### check_escalation_needed() - Lines 234-277
**Input:**
- conversation: ConversationModel
- intent: str
- message: str
- score: int (0-100)

**Process:** Evaluate 3 escalation criteria

**Criteria:**
1. **High Score (line 250-256):**
   - If score >= 85 → return True (escalate)
   
2. **Human Keywords (line 258-268):**
   - Check message for:
     - "falar com alguém"
     - "atendente"
     - "pessoa de verdade"
     - "humano"
     - "gerente"
     - "supervisor"
   - If any match → return True

3. **Bot Confusion (line 270-277):**
   - If intent == "OUTRO" → log warning
   - TODO (line 276): "Implementar contador de OUTRO consecutivos"
   - Single OUTRO doesn't trigger escalation yet

**Output:** bool (True = escalate, False = continue)

---

### 2. Image Analysis (BLIP-2)

**Service:** `VisionService` (vision_service.py:1-257)

**Model Details:**
- **Framework:** Salesforce/blip-image-captioning-base
- **License:** BSD-3 (open source)
- **Download:** ~990MB (one-time, cached)
- **Cost:** $0 (local inference, CPU/GPU)
- **Features:** Image captioning + Visual QA (VQA)

#### analyze_image() - Lines 45-89
**Input:**
- image_url: str (HTTP(S) URL)
- context: str (e.g., "medical", "fitness", "food")
- questions: list[str] | None (custom VQA questions)

**Process:**
1. Load BLIP-2 model (lazy loading, once per process)
2. Download image from URL
3. Generate basic caption (what's in image)
4. Generate detailed description (context-aware analysis)
5. Extract tags from caption + description
6. Answer custom questions (if provided)

**Output:** dict with keys:
- caption: str (short description)
- detailed_description: str (full analysis)
- tags: str or list (keywords)
- answers: dict (question → answer mapping)

**Error Handling:** Exceptions logged, not re-raised

#### _load_model() - Lines 21-36
**Input:** None

**Process:**
1. Check if model already loaded (lazy loading)
2. If not:
   - Download model from HuggingFace
   - Initialize BlipProcessor + BlipForConditionalGeneration
   - Log success

**Output:** None (populates self.model, self.processor)

---

### 3. Audio Transcription (Faster-Whisper)

**Service:** `TranscriptionService` (transcription_service.py:1-150+)

**Model Details:**
- **Framework:** Faster-Whisper (4x faster than Whisper original)
- **Cost:** $0 (local inference)
- **Download:** ~75MB for 'base' model (first time, then cached)
- **Supported Formats:** ogg, mp3, mp4, m4a, wav
- **Language:** Portuguese ("pt" default, configurable)
- **Optimization:** VAD (Voice Activity Detection) removes silence

#### transcribe_audio() - Lines 30-68
**Input:**
- audio_url: str (HTTP(S) URL)
- language: str (default "pt" for Portuguese)

**Process:**
1. Load Faster-Whisper model (lazy loading)
2. Download audio file from URL (30s timeout)
3. Save to temp file
4. Run Whisper with VAD enabled
5. Extract segments + concatenate
6. Clean up temp file

**Output:** str (complete transcript) OR None

**Raises:** LLMError if download or transcription fails

**Timeout:** 30s per download (httpx.Client)

---

### 4. Description & Metadata Generation

**Service:** `DescriptionService` (description_service.py:1-226)

**Responsibility:** Generate title, description, tags for media

#### generate_description() - Lines 36-79
**Input:**
- message_id: UUID
- use_vision: bool (default True, uses BLIP-2)

**Process:**
1. Lookup message by ID
2. Extract filename, caption, media URL
3. **If image + vision enabled:**
   - Call analyze_image_with_blip()
   - Return BLIP-2 results
4. **Else:**
   - Call generate_file_metadata() (basic approach)
   - Use filename + caption + type

**Output:** dict with keys:
- generated_title: str
- generated_description: str
- suggested_tags: str

**Raises:** NotFoundException if message not found

#### analyze_image_with_blip() - Lines 81-127
**Input:**
- image_url: str
- caption: str (user-provided caption, optional)

**Process:**
1. Get VisionService instance
2. Call analyze_image() with "medical" context
3. Build title from caption OR BLIP caption
4. Build description = caption + BLIP analysis
5. Extract tags from BLIP results

**Output:** dict (title, description, tags)

**Error Handling:** Falls back to generate_file_metadata() on failure

#### generate_file_metadata() - Lines 129-226
**Input:**
- filename: str
- caption: str
- file_type: str (e.g., "image", "document")

**Process:**
1. Parse filename extension
2. Extract base name
3. Build title from caption OR filename
4. Generate basic description
5. Extract tags from all metadata

**Output:** dict (title, description, tags)

**Usage:** Fallback when vision is disabled OR BLIP fails

---

## Known Gaps & Limitations

### 1. OUTRO Consecutive Counter
- **Status:** INCOMPLETE
- **Current:** Single OUTRO detected and warned (intent_detector.py:270-277)
- **Missing:** Consecutive counter not implemented (TODO line 276)
- **Impact:** Bot confusion not escalated after N failures
- **Priority:** MEDIUM

### 2. BLIP-2 Async/Sync Mixing
- **Status:** MIXED PATTERNS
- **Issue:** analyze_image() is async but internal ops (_generate_caption) may be sync
- **Missing:** Consistent async implementation
- **Priority:** LOW (not in critical path)

### 3. Video Visual Analysis
- **Status:** INCOMPLETE
- **Current:** Audio transcribed only
- **Missing:** Visual description (marked TODO in message_processor.py:63)
- **Impact:** Video not analyzed for visual content
- **Priority:** MEDIUM

### 4. Transcription Model Selection
- **Status:** CONFIGURABLE but defaults to 'base'
- **Options:** tiny, base, small, medium, large
- **Trade-off:** Larger = better accuracy but slower + more memory
- **Priority:** LOW (already configurable)

### 5. No Sentiment Analysis
- **Status:** NOT IMPLEMENTED
- **Missing:** Detect patient sentiment (happy, angry, confused)
- **Impact:** Can't adjust tone based on patient mood
- **Priority:** LOW

### 6. No Emotion Detection
- **Status:** NOT IMPLEMENTED
- **Missing:** Detect medical emotions (anxiety, pain level)
- **Impact:** Can't escalate urgent emotional cases
- **Priority:** MEDIUM

### 7. Language Support
- **Status:** Portuguese only
- **Current:** Hard-coded "pt" language for Whisper
- **Missing:** Multi-language support
- **Priority:** LOW (MVP is Portuguese)

### 8. Context Window Limits
- **Status:** NO TRUNCATION
- **Issue:** Full conversation context sent to Gemini
- **Missing:** Sliding window for large conversations (4k token limit)
- **Impact:** Large conversations may fail
- **Priority:** MEDIUM

---

## Testing & Validation

### Tests Implemented
- ✅ Intent detection (7 categories)
- ✅ Name extraction
- ✅ Urgency detection
- ✅ Maturity score updates
- ✅ Image analysis
- ✅ Audio transcription

### Test Scenarios
1. ✅ detect_intent() → 7 categories validated
2. ✅ detect_urgency() → medical urgency detected
3. ✅ try_extract_name() → name extracted + DB updated
4. ✅ generate_name_request() → prompt generated
5. ✅ update_maturity_score() → score updated correctly
6. ✅ check_escalation_needed() → triggers on high score + keywords
7. ✅ analyze_image() → BLIP-2 caption + description generated
8. ✅ transcribe_audio() → audio → text
9. ⚠️ OUTRO consecutive counter → NOT TESTED (not implemented)
10. ⚠️ Video visual analysis → NOT TESTED (not implemented)
11. ⚠️ Multi-language → NOT TESTED (not supported)

