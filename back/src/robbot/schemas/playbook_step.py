"""PlaybookStep schemas for API requests and responses."""

from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, ConfigDict, Field


# Create schemas
class PlaybookStepCreate(BaseModel):
    """Schema for creating playbook steps."""

    playbook_id: str = Field(..., description="Associated playbook ID")
    message_id: UUID = Field(..., description="Associated message ID")
    step_order: int | None = Field(None, description="Sequential order (auto-assigned if None)")
    context_hint: str | None = Field(None, description="When to use this step (LLM guidance)")


# Update schemas
class PlaybookStepUpdate(BaseModel):
    """Schema for updating playbook steps."""

    step_order: int | None = None
    context_hint: str | None = None


class PlaybookStepReorder(BaseModel):
    """Schema for reordering multiple steps."""

    step_id_order: list[tuple[str, int]] = Field(
        ...,
        description="List of (step_id, new_order) tuples",
        examples=[[("step1", 1), ("step2", 2), ("step3", 3)]]
    )


# Output schemas
class PlaybookStepOut(BaseModel):
    """Response schema for playbook steps."""

    id: str
    playbook_id: str
    message_id: str
    step_order: int
    context_hint: str | None
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class PlaybookStepWithMessage(PlaybookStepOut):
    """Response schema for playbook steps with message details."""

    message_type: str
    message_title: str | None
    message_description: str | None


class PlaybookStepList(BaseModel):
    """Response schema for listing playbook steps."""

    steps: list[PlaybookStepOut]
    total: int
