---
title: Conversation Orchestrator
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Conversation Orchestrator

## Brief description of the requirements and goals of the feature
This service is the central coordinator for the entire conversation flow. It receives inbound messages, coordinates specialized components like `MessageProcessor`, `ContextBuilder`, and `IntentDetector`, manages the conversation state, handles handoffs to human agents, and logs all interactions. It's the brain of the chatbot's conversation logic.

## Architecture and design
The `ConversationOrchestrator` acts as a central hub that delegates tasks to other specialized services. It uses the Gemini client for generating AI responses, the `WAHAClient` for communicating with the WhatsApp API, and various repositories for database interactions.

The high-level flow is as follows:
1.  Receive an inbound message.
2.  Retrieve or create a `Conversation` and associated `Lead`.
3.  If a human agent is active, silence the bot.
4.  Use `MessageProcessor` to handle media (audio/video).
5.  Use `ContextBuilder` to fetch conversation history from ChromaDB.
6.  Use `IntentDetector` to determine the user's intent and urgency.
7.  Generate a response using the Gemini client, based on the message, context, and intent.
8.  Update the lead's maturity score.
9.  Check if the conversation needs to be escalated to a human agent.
10. Save the new context to ChromaDB.
11. Send the response via the `WAHAClient`.
12. Persist all interactions and logs to the database.

## Tasks
- [x] Process inbound messages (text, audio, video).
- [x] Get or create a conversation and associated lead.
- [x] Silence the bot if a human is handling the conversation.
- [x] Delegate media processing to `MessageProcessor`.
- [x] Delegate context retrieval to `ContextBuilder`.
- [x] Delegate intent detection to `IntentDetector`.
- [x] Generate contextual responses using Gemini.
- [x] Update lead maturity score based on intent.
- [x] Handle handoff to human agents when necessary.
- [x] Save conversation context for future interactions.
- [x] Send responses via WhatsApp.
- [x] Log all messages and AI interactions for analytics and auditing.

## Open questions
1. How should the WebSocket notification to the human agent be implemented when the bot is silenced?
2. What is the specific strategy for the fallback response when the primary AI model fails?
3. Are there other conditions besides a high score or bot confusion that should trigger a handoff?
