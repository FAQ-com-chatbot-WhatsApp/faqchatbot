# Services

This directory contains the documentation for the various services that make up the business logic of the application. Each service is responsible for a specific domain and encapsulates the business rules and orchestration for that domain.

## Core Services
- **[Authentication and Authorization](auth_services.md)**: Manages user authentication, session management, and authorization.
- **[Conversation Orchestrator](conversation_orchestrator.md)**: The main orchestrator for handling the conversation flow.
- **[Lead Service](lead_service.md)**: Manages the lead lifecycle, including creation, updating, and scoring.
- **[Message Processor](message_processor.md)**: Processes incoming messages and triggers the appropriate actions.
- **[User Service](user_service.md)**: Manages user accounts and profiles.

## Supporting Services
- **[Audit Service](audit_service.md)**: Logs critical actions within the application.
- **[Context Builder](context_builder.md)**: Builds the context for the conversation.
- **[Conversation Service](conversation_service.md)**: Manages conversations and messages.
- **[Credential Service](credential_service.md)**: Manages user credentials.
- **[Description Service](description_service.md)**: Provides descriptions for various entities.
- **[Email Verification Service](email_verification_service.md)**: Handles email verification for new users.
- **[Export Service](export_service.md)**: Exports data to various formats.
- **[Handoff Service](handoff_service.md)**: Manages the handoff of conversations to human agents.
- **[Health Service](health_service.md)**: Provides health check endpoints for the application.
- **[Intent Detector](intent_detector.md)**: Detects the intent of user messages.
- **[Message Service](message_service.md)**: Manages the sending and receiving of messages.
- **[MFA Service](mfa_service.md)**: Manages multi-factor authentication.
- **[Notification Service](notification_service.md)**: Sends notifications to users.
- **[Playbook Orchestration](playbook_orchestration.md)**: Orchestrates the execution of playbooks.
- **[Playbook Service](playbook_service.md)**: Manages playbooks and their steps.
- **[Playbook Tools](playbook_tools.md)**: Provides tools for working with playbooks.
- **[Queue Service](queue_service.md)**: Manages the queuing of background jobs.
- **[Tag Service](tag_service.md)**: Manages tags for categorizing entities.
- **[Transcription Service](transcription_service.md)**: Transcribes audio messages to text.
- **[Vision Service](vision_service.md)**: Analyzes images to extract information.
- **[WAHA Service](waha_service.md)**: Manages the connection to the WhatsApp HTTP API.
- **[Worker Analytics Service](worker_analytics_service.md)**: Provides analytics for the background workers.

## Analytics Services
- **[Forecast Service](analytics/forecast_service.md)**: Provides predictive analysis and forecasting.
- **[Metrics Service](analytics/metrics_service.md)**: Calculates and presents business and application performance metrics.
