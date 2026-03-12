"""
Abstract Repository interfaces for domain entities.
Follows the Dependency Inversion Principle.
"""

from abc import ABC, abstractmethod

from robbot.domain.leads.lead import Conversation, Lead


class LeadRepository(ABC):
    @abstractmethod
    def save(self, lead: Lead) -> None:
        pass

    @abstractmethod
    def get_by_id(self, lead_id: str) -> Lead | None:
        pass

    @abstractmethod
    def get_by_phone(self, phone: str) -> Lead | None:
        pass

    @abstractmethod
    def list_active(self) -> list[Lead]:
        pass


class ConversationRepositoryInterface(ABC):
    @abstractmethod
    def save(self, conversation: Conversation) -> None:
        pass

    @abstractmethod
    def get_by_id(self, conversation_id: str) -> Conversation | None:
        pass

    @abstractmethod
    def get_by_chat_id(self, chat_id: str) -> Conversation | None:
        pass
