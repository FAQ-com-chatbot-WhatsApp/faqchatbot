---
title: Intent Detector
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Intent Detector

## Brief description of the requirements and goals of the feature
This service is responsible for understanding the user's messages. It detects the primary intent (e.g., asking for a price, scheduling an appointment), identifies if the message is urgent, attempts to extract the user's name, and updates the lead's maturity score. It also plays a key role in deciding when a conversation should be escalated to a human agent.

## Architecture and design
The `IntentDetector` relies on the `GeminiClient` to make calls to the AI model and uses `PromptTemplates` to format the requests for different tasks.

The main functions are:
- **`detect_intent`**: Sends the user message and conversation context to Gemini to classify it into one of the predefined intents (`INTERESSE_PRODUTO`, `ORCAMENTO`, etc.).
- **`detect_urgency`**: Asks the AI to determine if the message contains urgent language and why.
- **`try_extract_name`**: Uses a specific prompt to ask the AI to find a person's name in the message with a certain confidence level. If found, it updates the lead's information.
- **`generate_name_request`**: Decides if it's an appropriate time in the conversation to ask for the user's name and generates a natural-sounding question.
- **`update_maturity_score`**: Increases the lead's score based on the detected intent. For example, asking to schedule an appointment (`AGENDAMENTO`) adds more points than a general product question (`INTERESSE_PRODUTO`).
- **`check_escalation_needed`**: Implements the business logic for handoff. It triggers an escalation if the lead's score is very high, if the user explicitly asks to speak to a human, or if the bot repeatedly fails to understand the intent.

## Tasks
- [x] Detect the user's intent from a message.
- [x] Detect urgency in a user's message.
- [x] Intelligently extract the user's name from the conversation.
- [x] Generate a natural request for the user's name when appropriate.
- [x] Update the lead's maturity score based on the detected intent.
- [x] Check for conditions that require escalating the conversation to a human agent.
- [ ] Implement a counter for consecutive "OUTRO" intents to improve the "bot confused" escalation trigger.

## Open questions
1. What is the ideal confidence threshold for name extraction to avoid errors? (Currently 70%)
2. Should the score deltas for each intent be configurable instead of hardcoded?
3. How many consecutive "OUTRO" intents should trigger an escalation?
