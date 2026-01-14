---
title: Message Processor
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Message Processor

## Brief description of the requirements and goals of the feature
This service is responsible for handling all types of incoming messages, including text, audio, and video. Its primary goal is to convert any non-text message into a text format that the rest of the system can understand. It also handles the persistence of all inbound and outbound messages to the database.

## Architecture and design
The `MessageProcessor` is a specialized component that sits at the beginning of the conversation processing pipeline.

- **`process_media_message`**: This is the main entry point. It checks if a message contains audio or video.
    - If it's a video, it calls `_process_video`.
    - If it's an audio message, it calls `_process_audio`.
    - If it's plain text, it returns the text directly.
- **`_process_audio`**: This method uses the `TranscriptionService` (which likely uses a model like Whisper) to convert the audio file from a URL into text.
- **`_process_video`**: This method also uses the `TranscriptionService` to extract and transcribe the audio from the video. Currently, it does not analyze the visual content but is designed to be extended for that purpose (e.g., using Gemini Vision).
- **`save_inbound_message` / `save_outbound_message`**: These methods use the `ConversationMessageRepository` to save the message content, direction (inbound/outbound), and timestamp to the database, linking it to the correct conversation.

## Tasks
- [x] Process plain text messages.
- [x] Transcribe audio messages into text.
- [x] Transcribe the audio track from video messages.
- [x] Save inbound (user) messages to the database.
- [x] Save outbound (bot) messages to the database.
- [ ] Implement asynchronous visual description for video messages using a vision model.

## Open questions
1. How should the system handle transcriptions that are empty or have low confidence? (Currently returns a generic "transcription failed" message).
2. What is the best strategy for handling very long audio or video files to avoid long processing times?
3. When visual description for videos is implemented, should it be a blocking call or an asynchronous job that updates the message later?
