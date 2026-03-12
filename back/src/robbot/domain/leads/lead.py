from dataclasses import dataclass, field
from datetime import datetime
from uuid import uuid4

from robbot.domain.shared.enums import LeadStatus
from robbot.domain.shared.value_objects import LeadScore, PhoneNumber


@dataclass
class Lead:
    """
    Rich Lead entity containing maturity and scoring logic.
    """

    id: str
    name: str
    phone_number: PhoneNumber
    status: LeadStatus = LeadStatus.NEW
    maturity_score: LeadScore = LeadScore(0)
    email: str | None = None
    assigned_to_user_id: int | None = None
    created_at: datetime = field(default_factory=datetime.utcnow)
    updated_at: datetime = field(default_factory=datetime.utcnow)
    converted_at: datetime | None = None
    deleted_at: datetime | None = None

    @classmethod
    def create(cls, name: str, phone_number: str, id: str | None = None) -> "Lead":
        return cls(
            id=id or str(uuid4()),
            name=name,
            phone_number=PhoneNumber(phone_number),
        )

    def update_score(self, new_value: int):
        """Logic to update lead score and potentially its status."""
        self.maturity_score = LeadScore(new_value)
        self._apply_status_rules()
        self.updated_at = datetime.utcnow()

    def adjust_score(self, adjustment: int):
        """Adjust score by a relative amount."""
        self.maturity_score = self.maturity_score.apply_adjustment(adjustment)
        self._apply_status_rules()
        self.updated_at = datetime.utcnow()

    def _apply_status_rules(self):
        """Automatic status transitions based on score."""
        score = self.maturity_score.value

        if score >= 80:
            if self.status not in (LeadStatus.READY, LeadStatus.SCHEDULED):
                self.status = LeadStatus.READY
        elif score >= 50:
            if self.status in (LeadStatus.NEW, LeadStatus.CONTACTED):
                self.status = LeadStatus.ENGAGED
        elif score > 0 and self.status == LeadStatus.NEW:
            self.status = LeadStatus.CONTACTED

    def convert(self):
        """Mark as scheduled/converted."""
        self.status = LeadStatus.SCHEDULED
        self.maturity_score = LeadScore(100)
        self.converted_at = datetime.utcnow()
        self.updated_at = datetime.utcnow()

    def soft_delete(self):
        """Mark as deleted."""
        if not self.deleted_at:
            self.deleted_at = datetime.utcnow()
            self.updated_at = self.deleted_at

    def restore(self):
        """Restore from soft-delete."""
        self.deleted_at = None
        self.updated_at = datetime.utcnow()

    def assign_to(self, user_id: int):
        """Assign to a specific user."""
        self.assigned_to_user_id = user_id
        self.updated_at = datetime.utcnow()
