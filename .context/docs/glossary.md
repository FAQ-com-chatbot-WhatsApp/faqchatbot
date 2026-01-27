---
status: filled
---
# Glossary & Domain Concepts: Clinica Go


**Status:** filled
**Updated:** 2026-01-27

This document defines the key domain terms, technical concepts, and architectural patterns used throughout the **Clinica Go** project.

---

## 1. Core Domain Concepts

| Term | Definition |
| :--- | :--- |
| **Lead** | A potential customer or patient who has initiated contact via a messaging channel (e.g., WhatsApp). |
| **Conversation** | The ongoing interaction history between a Lead and the system (or a human agent). |
| **Playbook** | A predefined, structured sequence of steps or rules that the AI follows to interact with a lead (e.g., qualification, scheduling). |
| **Lead Maturity** | A status or score indicating how close a lead is to conversion (e.g., "COLD", "WARM", "HOT"). |
| **Handoff** | The process of transferring a conversation from an automated AI agent to a human agent. |
| **Escalation** | A specific type of handoff triggered when the AI detects an intent it cannot handle or a direct request for human assistance. |

---

## 2. Technical System Terms

| Term | Definition |
| :--- | :--- |
| **WAHA** | The WhatsApp HTTP API gateway used to bridge the system with the WhatsApp messaging platform. |
| **Orchestrator** | The central service (`OrchestratorService`) that coordinates message ingestion, context retrieval, AI processing, and response delivery. |
| **DI (Dependency Injection)** | The pattern used to manage service lifetimes and promote testability by injecting dependencies into components. |
| **Alembic** | The database migration tool used for versioning the SQLAlchemy relational schema. |
| **Pydantic** | The library used for data validation and settings management on the backend. |
| **Zod** | The schema validation library used for form and API data validation on the frontend. |

---

## 3. Frontend Specific Terms

| Term | Definition |
| :--- | :--- |
| **Styleguide** | A living document (accessible at `/styleguide`) that showcases all UI atoms and molecules in isolation. |
| **`fetchApi`** | The standardized wrapper for all frontend-to-backend HTTP requests. |
| **Normalize API Error** | The process of converting various backend error formats into a consistent frontend UI-friendly structure. |
| **Shadcn UI** | The component library foundation used for building accessible and themed UI primitives. |

---

## 4. AI & NLP Specifics

| Term | Definition |
| :--- | :--- |
| **Gemini** | The Google Generative AI model used for intent detection and generating conversational responses. |
| **Intent Detector** | A logic component that classifies a lead's message into specific categories (e.g., "SKEPTIC", "READY_TO_BUY"). |
| **Bot Autonomy** | A metric indicating the percentage of conversations handled successfully by the AI without human intervention. |
