"""
AnsweredQuestionsMemory: In-memory tracker for answered user questions, scoped per conversation.

Each conversation has its own isolated set — prevents cross-conversation contamination
when the Orchestrator is used as a singleton.
"""

import re


class AnsweredQuestionsMemory:
    def __init__(self):
        # dict[conversation_id, set[normalized_question]]
        self._store: dict[str, set[str]] = {}

    def _normalize(self, question: str) -> str:
        """Lowercase, strip, remove punctuation for basic normalization."""
        return re.sub(r"[^\w\s]", "", question.strip().lower())

    def add(self, conversation_id: str, question: str) -> None:
        """Record that a question was answered in the given conversation."""
        if conversation_id not in self._store:
            self._store[conversation_id] = set()
        self._store[conversation_id].add(self._normalize(question))

    def was_answered(self, conversation_id: str, question: str) -> bool:
        """Check if this exact question was already answered in this conversation."""
        bucket = self._store.get(conversation_id, set())
        return self._normalize(question) in bucket

    def reset(self, conversation_id: str | None = None) -> None:
        """Clear answered questions. Pass conversation_id to clear only one conversation."""
        if conversation_id is None:
            self._store.clear()
        else:
            self._store.pop(conversation_id, None)
