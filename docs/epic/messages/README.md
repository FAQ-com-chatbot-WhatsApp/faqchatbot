# Epic: Message System & Media Enrichment

**Status:** ✅ IMPLEMENTADO  
**Version:** 1.0  
**Owner:** Backend Team  
**Last Updated:** January 16, 2026

---

## Overview

### Problem Statement

Medical clinics need to manage diverse message types (text, voice, images, videos, documents, locations) with:
- Automatic transcription of voice messages (patients often prefer voice over typing)
- Visual analysis of images (treatment photos, clinic facilities, patient progress)
- Searchable media library (staff need to find specific images/audio quickly)
- LLM context enrichment (bot needs to understand media content for better responses)

### Solution

Unified message system with **zero-cost AI enrichment** using local models:
- **Faster-Whisper**: Audio → text transcription (Portuguese)
- **BLIP-2**: Image → caption + tags (visual understanding)
- **DescriptionService**: Metadata generation from filenames

---

## Architecture

### Message Types

| Type | Fields | AI Enrichment | Use Case |
|------|--------|---------------|----------|
| **text** | text, title, description, tags | ❌ Manual only | Bot responses, playbook messages |
| **voice** | audio_url, transcription | ✅ Faster-Whisper | Patient audio messages |
| **image** | file (url), caption, title, description, tags | ✅ BLIP-2 | Treatment photos, clinic facilities |
| **video** | file (url), caption, transcription | ✅ Audio extraction + Whisper | Educational videos, testimonials |
| **document** | file (url), title, description, tags | ✅ Filename parsing | PDFs, consent forms, prescriptions |
| **location** | latitude, longitude, title | ❌ N/A | Clinic address, patient location |

### Database Schema

**messages table:**
```sql
CREATE TABLE messages (
    id UUID PRIMARY KEY,
    type VARCHAR(50) NOT NULL CHECK (type IN ('text', 'image', 'voice', 'video', 'document', 'location')),
    
    -- Content fields (type-specific)
    text TEXT,                      -- For text messages
    caption TEXT,                   -- For media messages
    
    -- Enrichment fields (AI-generated)
    title VARCHAR(255),             -- Short descriptive title (indexed)
    description TEXT,               -- Detailed content description
    tags VARCHAR(500),              -- Comma-separated keywords (indexed)
    
    -- Audio fields (voice/video)
    has_audio BOOLEAN DEFAULT false,
    audio_url VARCHAR(500),
    transcription TEXT,             -- Faster-Whisper output
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

**message_media table:**
```sql
CREATE TABLE message_media (
    id UUID PRIMARY KEY,
    message_id UUID REFERENCES messages(id) ON DELETE CASCADE,
    mimetype VARCHAR(255) NOT NULL,  -- image/jpeg, audio/ogg, video/mp4, application/pdf
    filename VARCHAR(500) NOT NULL,
    url TEXT NOT NULL                -- Public URL or storage path
);
```

**message_location table:**
```sql
CREATE TABLE message_location (
    id UUID PRIMARY KEY,
    message_id UUID REFERENCES messages(id) ON DELETE CASCADE,
    latitude FLOAT NOT NULL,
    longitude FLOAT NOT NULL,
    title VARCHAR(255)               -- Optional place name
);
```

---

## AI Services

### 1. TranscriptionService (Faster-Whisper)

**Location:** `src/robbot/services/transcription_service.py`

**Purpose:** Convert audio to text for voice/video messages

**Model Details:**
- **Framework:** Faster-Whisper (4x faster than OpenAI Whisper)
- **Model Size:** `base` (74MB, good accuracy/speed balance)
- **Language:** Portuguese (`pt`)
- **Cost:** $0 (local inference)
- **Optimization:** VAD (Voice Activity Detection) removes silence

**API:**
```python
class TranscriptionService:
    def __init__(self):
        self.model_size = "base"  # configurable via env
        self.language = "pt"
    
    async def transcribe_audio(self, audio_url: str) -> str | None:
        """
        Download audio from URL and transcribe to text.
        
        Args:
            audio_url: HTTP(S) URL to audio file (ogg, mp3, mp4, m4a, wav)
        
        Returns:
            Transcribed text in Portuguese or None if failed
        
        Process:
            1. Download audio with 30s timeout
            2. Save to temp file with correct extension
            3. Load Whisper model (cached after first load)
            4. Transcribe with VAD enabled
            5. Concatenate all segments
            6. Clean up temp file
        """
```

**Performance:**
- 30s audio: ~3-5 seconds transcription time (CPU)
- 1-2GB RAM usage during inference
- Model loaded once per worker process (reused)

**Example:**
```python
# Input: voice message URL
audio_url = "https://waha.com/files/audio_123.ogg"

# Output: Portuguese transcription
transcription = await transcription_service.transcribe_audio(audio_url)
# "olá gostaria de saber informações sobre tratamento de emagrecimento"
```

---

### 2. VisionService (BLIP-2)

**Location:** `src/robbot/services/vision_service.py`

**Purpose:** Analyze images and generate captions, descriptions, tags

**Model Details:**
- **Framework:** Salesforce/blip-image-captioning-base
- **Model Size:** ~990MB (downloaded once, cached)
- **License:** BSD-3 (open source, commercial use allowed)
- **Cost:** $0 (local inference)
- **Features:** Image captioning + Visual Question Answering (VQA)

**API:**
```python
class VisionService:
    async def analyze_image(
        self,
        image_url: str,
        context: str = "medical",
        questions: list[str] | None = None
    ) -> dict:
        """
        Analyze image and generate caption, description, tags.
        
        Args:
            image_url: HTTP(S) URL to image
            context: Context hint ("medical", "fitness", "food")
            questions: Optional VQA questions
        
        Returns:
            {
                "caption": "Short description (10-20 words)",
                "detailed_description": "Full analysis (50-100 words)",
                "tags": ["keyword1", "keyword2", ...],
                "answers": {"question": "answer", ...}
            }
        """
```

**Performance:**
- Image analysis: ~3-8 seconds (CPU, depends on resolution)
- 2-3GB RAM during inference
- GPU acceleration: 10x faster if available

**Example:**
```python
# Input: medical clinic image
image_url = "https://example.com/clinic_room.jpg"

# Output: AI-generated analysis
result = await vision_service.analyze_image(
    image_url,
    context="medical",
    questions=["What medical equipment is visible?"]
)

# result = {
#     "caption": "hospital room with a large monitor and medical equipment",
#     "detailed_description": "A modern medical consultation room featuring a wall-mounted monitor, examination table, and various medical instruments...",
#     "tags": ["hospital", "medical", "equipment", "room", "healthcare"],
#     "answers": {
#         "What medical equipment is visible?": "Monitor, examination table, medical instruments"
#     }
# }
```

---

### 3. DescriptionService

**Location:** `src/robbot/services/description_service.py`

**Purpose:** Orchestrate AI enrichment and provide fallbacks

**API:**
```python
class DescriptionService:
    def __init__(self, db: Session):
        self.vision_service = VisionService()
        self.transcription_service = TranscriptionService()
    
    def generate_description(
        self,
        message_id: str,
        use_vision: bool = True
    ) -> dict:
        """
        Generate title, description, tags for a message.
        
        Process:
            1. Fetch message from database
            2. If image + vision enabled:
                → Use BLIP-2 for visual analysis
            3. Else if voice/video:
                → Use Faster-Whisper for transcription
            4. Else (document/text):
                → Extract keywords from filename/caption
        
        Returns:
            {
                "generated_title": str,
                "generated_description": str,
                "suggested_tags": str (comma-separated)
            }
        """
```

**Fallback Strategy:**
- BLIP-2 fails → Use filename + caption
- Faster-Whisper fails → Return None (no transcription)
- Document → Always use filename parsing (no AI)

---

## API Endpoints

### POST /api/v1/messages

**Purpose:** Create new message with automatic enrichment

**Request:**
```json
{
    "type": "voice",
    "file": {
        "mimetype": "audio/ogg",
        "filename": "patient_question.ogg",
        "url": "https://waha.com/files/audio_123.ogg"
    },
    "caption": "Patient asking about treatment"
}
```

**Response:**
```json
{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "voice",
    "file": {
        "mimetype": "audio/ogg",
        "filename": "patient_question.ogg",
        "url": "https://waha.com/files/audio_123.ogg"
    },
    "caption": "Patient asking about treatment",
    "title": null,
    "description": null,
    "tags": null,
    "transcription": "olá gostaria de saber sobre o tratamento de emagrecimento",
    "created_at": "2026-01-16T10:30:00Z",
    "updated_at": "2026-01-16T10:30:00Z"
}
```

**Process:**
1. Create message record (200ms)
2. Enqueue background job for AI enrichment (async)
3. Return immediately (non-blocking)
4. Background worker processes AI (3-5s)
5. Updates message record with transcription/tags

---

### POST /api/v1/messages/{id}/generate-description

**Purpose:** Manually trigger AI enrichment (retry or override)

**Query Params:**
- `use_gemini_vision`: boolean (default: true)

**Request:**
```
POST /api/v1/messages/550e8400-e29b-41d4-a716-446655440000/generate-description?use_gemini_vision=true
```

**Response:**
```json
{
    "message_id": "550e8400-e29b-41d4-a716-446655440000",
    "generated_title": "Consulta médica sobre emagrecimento",
    "generated_description": "Paciente perguntando sobre opções de tratamento para perda de peso saudável, demonstrando interesse em acompanhamento profissional.",
    "suggested_tags": "emagrecimento, consulta, tratamento, saúde, paciente"
}
```

**Use Cases:**
- Retry failed enrichment
- Update tags/description after model improvements
- Override automatic enrichment with better context

---

### GET /api/v1/messages/{id}

**Response includes enrichment fields:**
```json
{
    "id": "...",
    "type": "image",
    "file": {...},
    "caption": "Sala de atendimento da clínica",
    "title": "Sala de consulta médica moderna",
    "description": "Ambiente de atendimento com equipamento médico, maca de exame e iluminação adequada para procedimentos.",
    "tags": "medical, healthcare, consultation, room, equipment",
    "transcription": null,
    "created_at": "...",
    "updated_at": "..."
}
```

---

## Workflow Examples

### Workflow 1: Voice Message Enrichment

```
1. Patient sends WhatsApp voice message
   ↓
2. WAHA webhook receives message + audio URL
   ↓
3. System creates message record
   POST /api/v1/messages {
       type: "voice",
       file: {url: "https://waha.com/audio_123.ogg"}
   }
   ↓
4. MessageService.create_message()
   - Saves message to database (transcription = null)
   - Enqueues AI job: transcribe_audio(message_id)
   - Returns response immediately
   ↓
5. Background worker (ai queue)
   - Downloads audio file (30s timeout)
   - Loads Faster-Whisper model (cached)
   - Transcribes: "olá gostaria de saber sobre emagrecimento"
   - Updates message.transcription in database
   ↓
6. Next API call returns transcription
   GET /api/v1/messages/{id}
   → transcription: "olá gostaria de saber sobre emagrecimento"
   ↓
7. Conversation context includes transcription
   - LLM receives full text for better understanding
   - Bot generates relevant response about weight loss
```

---

### Workflow 2: Image Analysis

```
1. Clinic admin uploads treatment photo
   ↓
2. POST /api/v1/messages {
       type: "image",
       file: {url: "https://cdn.com/treatment_before.jpg"},
       caption: "Paciente antes do tratamento"
   }
   ↓
3. MessageService.create_message()
   - Saves message (title/description/tags = null)
   - Enqueues AI job: analyze_image(message_id)
   ↓
4. Background worker (ai queue)
   - Downloads image
   - Loads BLIP-2 model (990MB, cached)
   - Generates caption: "person standing in front of mirror"
   - Generates description: "Clinical photo showing patient body composition before aesthetic treatment"
   - Extracts tags: ["patient", "clinical", "before", "treatment", "aesthetic"]
   - Updates message record
   ↓
5. Staff searches for images
   GET /api/v1/messages?tags=treatment,before
   → Returns image with AI-generated tags
   ↓
6. LLM uses description in conversation
   "I see you previously shared a before-treatment photo..."
```

---

## Performance Metrics

### AI Processing Times (CPU, 8 cores)

| Operation | Input Size | Time | RAM Usage |
|-----------|-----------|------|-----------|
| Faster-Whisper (30s audio) | 500KB ogg | 3-5s | 1.5GB |
| Faster-Whisper (2min audio) | 2MB ogg | 10-15s | 1.8GB |
| BLIP-2 (800x600 image) | 200KB jpg | 3-4s | 2.5GB |
| BLIP-2 (1920x1080 image) | 1MB jpg | 6-8s | 3GB |
| Filename parsing | N/A | <100ms | <10MB |

### Model Loading (first request only)

| Model | Download Size | Load Time | Disk Cache |
|-------|--------------|-----------|------------|
| Faster-Whisper (base) | 74MB | 2-3s | ~/.cache/huggingface |
| BLIP-2 (base) | 990MB | 10-15s | ~/.cache/huggingface |

**Subsequent requests:** Models stay in memory (reused across requests)

---

## Configuration

### Environment Variables

```bash
# Faster-Whisper
WHISPER_MODEL=base           # tiny, base, small, medium, large
WHISPER_LANGUAGE=pt          # Portuguese
WHISPER_DEVICE=cpu           # cpu or cuda

# BLIP-2
VISION_MODEL=Salesforce/blip-image-captioning-base
VISION_DEVICE=cpu            # cpu or cuda

# Background Jobs
ENABLE_AI_ENRICHMENT=true    # Toggle AI processing
AI_QUEUE_TIMEOUT=60          # Seconds before job timeout
```

### pyproject.toml Dependencies

```toml
[project.dependencies]
faster-whisper = ">=1.0.0"   # Audio transcription
torch = ">=2.9.1"            # PyTorch for ML
torchvision = ">=0.24.1"     # Image processing
transformers = ">=4.57.3"    # HuggingFace models (BLIP-2)
```

---

## Testing

### Unit Tests

**Location:** `tests/unit/services/test_transcription_service.py`

```python
def test_transcribe_audio():
    service = TranscriptionService()
    url = "https://example.com/test_audio.ogg"
    
    result = service.transcribe_audio_sync(url)
    
    assert result is not None
    assert isinstance(result, str)
    assert len(result) > 0
```

### Integration Tests

**Location:** `tests/integration/test_message_enrichment.py`

```python
def test_voice_message_enrichment():
    # Create voice message
    response = client.post("/api/v1/messages", json={
        "type": "voice",
        "file": {"url": "https://example.com/audio.ogg"}
    })
    
    message_id = response.json()["id"]
    
    # Wait for background processing
    time.sleep(10)
    
    # Verify transcription populated
    message = client.get(f"/api/v1/messages/{message_id}").json()
    assert message["transcription"] is not None
```

---

## Known Limitations

### 1. Audio Quality
- **Issue:** Low-quality audio (background noise, poor microphone) reduces transcription accuracy
- **Impact:** 70-80% accuracy instead of 95%+
- **Mitigation:** VAD removes silence, but can't fix poor audio quality

### 2. Video Visual Analysis
- **Status:** NOT IMPLEMENTED
- **Current:** Only audio track transcribed
- **Missing:** Visual content analysis (patient exercises, treatment demonstrations)
- **Priority:** MEDIUM (marked TODO in code)

### 3. Non-Portuguese Languages
- **Issue:** Faster-Whisper configured for Portuguese only
- **Impact:** English/Spanish messages transcribed incorrectly
- **Mitigation:** Add language detection before transcription

### 4. Model Memory Usage
- **Issue:** Both models in memory = ~4GB RAM per worker
- **Impact:** Limited scalability on small servers
- **Mitigation:** Use smaller models (tiny/small) or GPU acceleration

### 5. Synchronous Enrichment Blocking
- **Issue:** Manual `/generate-description` is synchronous (blocks HTTP request)
- **Impact:** 5-10s response time
- **Mitigation:** Use background job for retry (enqueue instead of sync call)

---

## Future Improvements

### Short-term (1-2 months)
- [ ] Add language detection (switch between pt/en/es)
- [ ] GPU acceleration support (10x faster)
- [ ] Streaming transcription (real-time audio processing)
- [ ] Video visual analysis (extract keyframes + BLIP-2)

### Medium-term (3-6 months)
- [ ] Document OCR (prescription/exam recognition)
- [ ] Multi-modal AI (analyze image + caption together)
- [ ] Custom model fine-tuning (medical domain)
- [ ] Batch processing for bulk enrichment

### Long-term (6-12 months)
- [ ] Real-time audio transcription during calls
- [ ] Automatic video summarization
- [ ] Medical entity recognition in transcriptions
- [ ] Integration with DICOM for medical imaging

---

## Related Documentation

- [Epic: AI & Natural Language](../ai/README.md) - Intent detection, name extraction
- [ADR-004: Clean Architecture](../../back/docs/architecture/decisions/ADR-004-clean-architecture-adapted.md)
- [Test Cases: UC-017, UC-018, UC-019](../../back/docs/academic/casos-teste-validacao.md) - Media enrichment validation
