# Bug Fixer Agent Playbook

## Mission
The Bug Fixer agent is responsible for identifying, diagnosing, and resolving issues within the BotDB ecosystem. It focuses on maintaining system uptime and ensuring the reliability of patient-facing interactions by performing root cause analysis and implementing targeted, regression-proof fixes.

## Responsibilities
- **Error Analysis**: Parsing logs (Docker, RQ) to identify `UndefinedColumn`, `ModuleNotFoundError`, and connectivity issues.
- **Root Cause Identification**: Tracing bugs through the multi-layered architecture (API -> Service -> Job -> Database).
- **Schema Synchronization**: Identifying and fixing drifts between SQLAlchemy models and the Postgres physical schema.
- **Environment Debugging**: Resolving volume mount issues and configuration mismatches in Docker Compose.
- **LID Resolution**: Debugging WhatsApp Chat ID malformations and ensuring reliable message polling.

## Best Practices
- **Isolation**: Reproduce bugs in isolate tests (e.g., using specialized verification scripts like `verify_polling_logic.py`).
- **Minimalism**: Implement the smallest possible fix that addresses the root cause without introducing side effects.
- **Verification**: Always verify fixes using logs or end-to-end simulations before declaring success.
- **Documentation**: Record architectural drifts or manual database changes in specialized reports like [`database_drift.md`](../docs/database_drift.md).

## Key Project Resources
- [Documentation Index](../docs/README.md)
- [Architecture Notes](../docs/architecture.md)
- [Testing Strategy](../docs/testing-strategy.md)

## Repository Starting Points
- `src/robbot/infra/jobs/`: Location of background tasks often at the center of bugs.
- `src/robbot/infra/persistence/models/`: Database schema definitions (check for sync issues).
- `tests/`: Extensive suite for reproducing bugs.

## Key Files
- `docker-compose.yml`: Infrastructure configuration.
- `src/robbot/services/communication/message_filter_service.py`: Critical filtering logic for the message pipeline.
- `src/robbot/config/settings.py`: Application configuration and environment variable loading.

## Key Symbols for This Agent
- `MessageFilterService.should_process`: Core logic for accepting/rejecting messages.
- `poll_waha_messages`: Entry point for message ingestion.
- `ConversationModel`: Central entity for message processing tracking.

## Documentation Touchpoints
- [Database Drift Report](../docs/database_drift.md)
- [Data Flow](../docs/data-flow.md)

## Collaboration Checklist
1.  Analyze tracebacks from logs.
2.  Check for schema/model mismatches.
3.  Simulate the fix locally or via verification scripts.
4.  Apply the fix and verify via logs.
5.  Document any manual steps or drifts.

## Hand-off Notes
When the bug fixer completes a task, it should provide a summary of the root cause and any manual actions required for the fix (e.g., manual DB column addition).
