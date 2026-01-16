# ARCHITECTURE.md

## System Overview

**Go Robot** is an intelligent WhatsApp chatbot system designed for medical clinics to automate patient engagement, lead management, and conversion tracking. The system combines artificial intelligence (Google Gemini), conversation management, and analytics to provide a complete solution for automated customer service and lead nurturing.

### Core Technology Stack

**Backend (Python)**
- Framework: FastAPI 0.121.1+
- Database: PostgreSQL 18
- Cache/Queue: Redis 7
- ORM: SQLAlchemy 2.0+ with Alembic migrations
- AI: Google Gemini API
- Vector Database: ChromaDB (conversation context)
- Background Jobs: Redis Queue (RQ)
- WhatsApp API: WAHA (WhatsApp HTTP API)
- Package Manager: uv

**Frontend (TypeScript)**
- Framework: Next.js 16.1.1 (App Router)
- React: 19.2.3
- Styling: Tailwind CSS v4
- Components: shadcn/ui (Radix UI primitives)
- Testing: Playwright
- Package Manager: npm

## Architecture Pattern: Clean Architecture (Adapted)

The backend follows an adapted Clean Architecture pattern documented in ADR-004, with pragmatic adjustments for the FastAPI ecosystem:

### Layer Structure

```
Backend Architecture:
├── API Layer (FastAPI)
│   ├── routes.py - Endpoint definitions
│   ├── dependencies.py - DI and middleware
│   └── controllers/ - HTTP request handlers
│
├── Business Logic Layer
│   └── services/ - Business rules and orchestration
│       ├── conversation_orchestrator.py - Main conversation flow
│       ├── intent_detector.py - AI-powered intent detection
│       ├── lead_service.py - Lead management
│       └── [25+ specialized services]
│
├── Domain Layer
│   └── enums.py - Business enums (ConversationStatus, LeadStatus, Role)
│
├── Infrastructure Layer
│   ├── repositories/ - Data access abstraction
│   ├── external/ - External API clients (Gemini, WAHA)
│   ├── db/models/ - SQLAlchemy ORM models
│   ├── redis/ - Cache and queue management
│   ├── vectordb/ - ChromaDB integration
│   └── jobs/ - Background workers
│
└── Cross-Cutting Concerns
    ├── core/ - Security, exceptions, logging
    ├── schemas/ - Pydantic DTOs (API contracts)
    └── common/ - Shared utilities
```

### Key Architectural Decisions (ADRs)

1. **No Domain Entities** (ADR-006): ORM Models serve as both entities and persistence layer, avoiding unnecessary duplication
2. **BaseRepository Pattern** (ADR-006): Generic repository with TypeVar for type-safe CRUD operations
3. **Dependency Injection**: Explicit constructor injection for testability
4. **Separation of Concerns**: Controllers → Services → Repositories → Database

## System Components

### 1. WhatsApp Integration (WAHA)

WAHA (WhatsApp HTTP API) provides the bridge between WhatsApp and the application:

```
Client WhatsApp Message
    ↓
WAHA Gateway (HTTP API)
    ↓
Webhook POST /api/v1/webhooks/waha
    ↓
Validation & Enqueue (Redis)
    ↓
Background Worker Processing
```

### 2. Conversation Orchestrator

Core component managing the conversation lifecycle:

**Process Flow:**
1. Receive inbound message via webhook
2. Create/fetch Lead and Conversation
3. Save message to database
4. Retrieve conversation context (ChromaDB vector search)
5. Detect intent using Gemini AI (10 categories)
6. Generate contextual response using SPIN Selling methodology
7. Extract patient name (passive or active extraction)
8. Update lead maturity score
9. Send response via WAHA
10. Check for escalation triggers

**SPIN Selling Phases:**
- SITUATION (score 0-25): Gather information
- PROBLEM (score 25-40): Identify pain points
- IMPLICATION (score 40-60): Explore consequences
- NEED-PAYOFF (score 60+): Present benefits, schedule appointment

### 3. Lead Management System

Tracks patient journey from first contact to conversion:

**Lead Lifecycle:**
```
NEW → ENGAGED → INTERESTED → READY → SCHEDULED → CONVERTED/LOST
```

**Maturity Scoring:**
- Score 0-100 based on conversation quality
- Incremented by intent detection (+5 to +25)
- Triggers different conversation strategies
- Automatic handoff when score > 70

### 4. Background Job Processing

Redis Queue (RQ) with three specialized queues:

- **messages**: Primary conversation processing
- **ai**: AI-intensive operations (Gemini calls)
- **escalation**: Urgent matters requiring human intervention

**Worker Architecture:**
- 2 workers running in Docker containers
- Automatic retry on failure
- Job result persistence in Redis

### 5. Analytics & Reporting

Comprehensive metrics system tracking:

**Conversion Metrics:**
- Overall conversion rate
- Conversion by source (Instagram, Facebook, Google Ads)
- Lost leads analysis with reasons
- Conversion trends over time

**Conversation Metrics:**
- Keyword frequency analysis
- Sentiment analysis
- Topic clustering
- Conversation heatmaps (time-based patterns)
- Average response time

**Real-time Dashboard:**
- WebSocket integration for live updates
- Active conversations
- Recent conversions
- System health metrics

### 6. Security & Authentication

Multi-layered security approach:

**Authentication:**
- JWT-based access tokens (15 min expiry)
- Refresh tokens (7 days, stored with rotation)
- Multi-factor authentication (TOTP)
- Email verification for new accounts

**Authorization:**
- Role-based access control (ADMIN, AGENT, VIEWER)
- Session management with device tracking
- Rate limiting (Redis-backed)
- CORS configuration for frontend integration

**Data Security:**
- Bcrypt password hashing
- Credentials separated from user model (ADR-001)
- Audit logging for sensitive operations
- Environment-based configuration

### 7. Vector Database (ChromaDB)

Semantic search for conversation context:

**Storage:**
- Message embeddings using Gemini embedding model
- Metadata: chat_id, timestamp, message direction
- Automatic embedding on message save

**Retrieval:**
- Top-K similar messages for context building
- Relevance scoring
- Conversation history assembly

### 8. Playbook System

Structured conversation flows for specific topics:

**Components:**
- Topics (categories like "Emagrecimento", "Tratamento Facial")
- Playbooks (conversation scripts with multiple steps)
- Playbook Steps (individual messages in sequence)
- Embeddings (semantic search for playbook matching)

**Gemini Function Calling:**
- `search_playbooks(query)` - Find relevant playbooks
- `get_playbook_messages(playbook_id)` - Retrieve playbook content
- `send_playbook_message()` - Send structured responses

## Database Schema

### Core Tables

**Users & Authentication:**
- users (id, username, email, role, is_active)
- credentials (user_id, password_hash, mfa_secret)
- sessions (session_id, user_id, device_info, expires_at)
- refresh_tokens (token, user_id, revoked, expires_at)

**Conversations & Leads:**
- leads (id, phone_number, name, email, status, maturity_score)
- conversations (id, chat_id, phone_number, lead_id, status)
- conversation_messages (id, conversation_id, direction, content)
- tags (id, name, color) + conversation_tags (many-to-many)

**Content & Playbooks:**
- topics (id, name, description)
- playbooks (id, topic_id, name, description)
- playbook_steps (id, playbook_id, step_number, message)
- playbook_embeddings (id, playbook_id, embedding_vector)

**Analytics:**
- interactions (id, lead_id, type, channel, notes)
- audit_logs (id, user_id, action, resource, ip_address)

## Frontend Architecture

**Status:** Currently a design system showcase (not integrated with backend)

**Structure:**
- App Router with Server Components by default
- shadcn/ui component library
- Tailwind CSS v4 for styling
- Playwright for E2E testing

**Planned Integration:**
- OpenAPI-generated TypeScript types
- Type-safe API client using openapi-fetch
- Authentication with JWT tokens
- Real-time dashboard using WebSocket

## Deployment

### Development Environment

**Docker Compose Services:**
- api: FastAPI application
- worker: RQ background workers (2 instances)
- db: PostgreSQL 18
- redis: Redis 7
- waha: WhatsApp HTTP API

**Commands:**
```bash
docker-compose up --build
# API: http://localhost:3333
# Docs: http://localhost:3333/docs
```

### Production Environment

**Platform:** Railway.app

**Configuration:**
- Multi-stage Dockerfile (api/worker targets)
- Environment variables via Railway secrets
- Automatic deployments on git push
- Health check endpoint: /health

**Services:**
- API instance (4 workers)
- Worker instance (RQ processing)
- Managed PostgreSQL
- Managed Redis

## System Monitoring

**Health Checks:**
- /health - Overall system status
- Database connectivity
- Redis connectivity
- WAHA API availability
- Worker queue status

**Logging:**
- Structured JSON logging
- ASCII-safe format (no emojis)
- Log levels: INFO, WARNING, ERROR
- Centralized via Python logging

## Performance Considerations

**Optimizations:**
- Generic BaseRepository reduces code duplication (835 lines saved)
- Direct ORM usage (no entity conversions)
- Redis caching for frequent queries
- Background job processing for AI operations
- Connection pooling (SQLAlchemy + Redis)

**Scalability:**
- Horizontal scaling via multiple worker instances
- Stateless API design
- Session storage in Redis (not memory)
- Queue-based architecture for async processing

## Testing Strategy

**Test Coverage:**
- Unit tests: Service layer with mocked repositories
- Integration tests: End-to-end conversation flows
- Test framework: pytest with async support
- 160 tests covering core functionality

**Test Organization:**
```
tests/
├── unit/
│   ├── services/ - Business logic tests
│   └── controllers/ - API endpoint tests
└── integration/
    ├── test_conversation_analysis_l3.py
    ├── test_mfa_login_flow.py
    └── test_performance_reports_l1.py
```
