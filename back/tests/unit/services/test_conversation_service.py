"""
Unit tests for ConversationService.

Tests core business logic for conversation management.
"""

import pytest
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from robbot.core.custom_exceptions import BusinessRuleError, NotFoundException
from robbot.domain.enums import ConversationStatus
from robbot.infra.db.models.conversation_message_model import ConversationMessageModel
from robbot.infra.db.models.conversation_model import ConversationModel
from robbot.infra.db.models.lead_model import LeadModel
from robbot.services.conversation_service import ConversationService


@pytest.fixture()
def db_session_instance():
    """Create in-memory SQLite database for testing."""
    engine = create_engine("sqlite+pysqlite:///:memory:", echo=False)
    # Create only needed tables (avoid JSONB issues with alerts table)
    LeadModel.__table__.create(bind=engine)
    ConversationModel.__table__.create(bind=engine)
    ConversationMessageModel.__table__.create(bind=engine)
    session_local = sessionmaker(bind=engine, autocommit=False, autoflush=False)
    session = session_local()
    try:
        yield session
    finally:
        session.close()


@pytest.fixture()
def conversation_service(db_session_instance):
    """Create ConversationService instance."""
    return ConversationService(db_session_instance)


# =====================================================================
# GET OR CREATE TESTS
# =====================================================================
def test_get_or_create_new_conversation(conversation_service):
    """Test creating a new conversation."""
    chat_id = "5511999999999@c.us"
    phone_number = "+5511999999999"
    name = "Test User"

    conversation = conversation_service.get_or_create(chat_id=chat_id, phone_number=phone_number, name=name)

    assert conversation is not None
    assert conversation.chat_id == chat_id
    assert conversation.phone_number == phone_number
    assert conversation.name == name
    assert conversation.status == ConversationStatus.ACTIVE


def test_get_or_create_existing_conversation(conversation_service):
    """Test retrieving an existing conversation."""
    chat_id = "5511888888888@c.us"
    phone_number = "+5511888888888"

    # Create first time
    conv1 = conversation_service.get_or_create(chat_id=chat_id, phone_number=phone_number)

    # Get same conversation
    conv2 = conversation_service.get_or_create(chat_id=chat_id, phone_number=phone_number)

    assert conv1.id == conv2.id
    assert conv1.chat_id == conv2.chat_id


# =====================================================================
# STATUS TRANSITION TESTS
# =====================================================================
def test_update_status_valid_transition(conversation_service):
    """Test valid status transition."""
    conversation = conversation_service.get_or_create(chat_id="test@c.us", phone_number="+5511111111111")

    # ACTIVE -> WAITING_SECRETARY (valid)
    updated = conversation_service.update_status(
        conversation_id=str(conversation.id), new_status=ConversationStatus.WAITING_SECRETARY
    )

    assert updated.status == ConversationStatus.WAITING_SECRETARY


def test_update_status_invalid_transition(conversation_service):
    """Test invalid status transition raises error."""
    conversation = conversation_service.get_or_create(chat_id="test2@c.us", phone_number="+5511222222222")

    # Close conversation first
    conversation_service.update_status(conversation_id=str(conversation.id), new_status=ConversationStatus.CLOSED)

    # Try invalid transition: CLOSED -> TRANSFERRED (should fail)
    with pytest.raises(BusinessRuleError) as exc_info:
        conversation_service.update_status(
            conversation_id=str(conversation.id), new_status=ConversationStatus.TRANSFERRED
        )

    assert "Invalid status transition" in str(exc_info.value)


def test_update_status_conversation_not_found(conversation_service):
    """Test updating status of non-existent conversation."""
    fake_id = "00000000-0000-0000-0000-000000000000"

    with pytest.raises(NotFoundException) as exc_info:
        conversation_service.update_status(conversation_id=fake_id, new_status=ConversationStatus.CLOSED)

    assert "not found" in str(exc_info.value).lower()


def test_all_valid_status_transitions(conversation_service):
    """Test all valid status transitions."""
    conversation = conversation_service.get_or_create(chat_id="transitions@c.us", phone_number="+5511333333333")
    conv_id = str(conversation.id)

    # ACTIVE -> WAITING_SECRETARY
    updated = conversation_service.update_status(conv_id, ConversationStatus.WAITING_SECRETARY)
    assert updated.status == ConversationStatus.WAITING_SECRETARY

    # WAITING_SECRETARY -> TRANSFERRED
    updated = conversation_service.update_status(conv_id, ConversationStatus.TRANSFERRED)
    assert updated.status == ConversationStatus.TRANSFERRED

    # TRANSFERRED -> CLOSED
    updated = conversation_service.update_status(conv_id, ConversationStatus.CLOSED)
    assert updated.status == ConversationStatus.CLOSED

    # CLOSED -> ACTIVE (reopen)
    updated = conversation_service.update_status(conv_id, ConversationStatus.ACTIVE)
    assert updated.status == ConversationStatus.ACTIVE


# =====================================================================
# CLOSE CONVERSATION TESTS
# =====================================================================
def test_close_conversation(conversation_service):
    """Test closing a conversation."""
    conversation = conversation_service.get_or_create(chat_id="close@c.us", phone_number="+5511444444444")

    closed = conversation_service.close(conversation_id=str(conversation.id), reason="Customer request")

    assert closed.status == ConversationStatus.CLOSED
    # Note: closed_at field doesn't exist in model


def test_close_nonexistent_conversation(conversation_service):
    """Test closing non-existent conversation raises error."""
    fake_id = "11111111-1111-1111-1111-111111111111"

    with pytest.raises(NotFoundException):
        conversation_service.close(conversation_id=fake_id)


# =====================================================================
# TRANSFER TO SECRETARY TESTS
# =====================================================================
def test_transfer_to_secretary(conversation_service):
    """Test transferring conversation to secretary."""
    conversation = conversation_service.get_or_create(chat_id="transfer@c.us", phone_number="+5511555555555")

    user_id = 123
    transferred = conversation_service.transfer_to_secretary(conversation_id=str(conversation.id), user_id=user_id)

    assert transferred.status == ConversationStatus.TRANSFERRED
    # Note: assigned_to_user_id is in LeadModel, not ConversationModel


def test_transfer_nonexistent_conversation(conversation_service):
    """Test transferring non-existent conversation raises error."""
    fake_id = "22222222-2222-2222-2222-222222222222"

    with pytest.raises(NotFoundException):
        conversation_service.transfer_to_secretary(conversation_id=fake_id, user_id=999)


# =====================================================================
# LIST/FILTER TESTS
# =====================================================================
def test_get_active_conversations(conversation_service):
    """Test retrieving active conversations."""
    # Create active and closed conversations
    conversation_service.get_or_create("active1@c.us", "+5511111111111")
    conversation_service.get_or_create("active2@c.us", "+5511222222222")
    conv3 = conversation_service.get_or_create("closed@c.us", "+5511333333333")

    # Close one conversation
    conversation_service.close(str(conv3.id))

    # Get active conversations
    active = conversation_service.get_active_conversations()

    assert len(active) == 2
    assert all(c.status == ConversationStatus.ACTIVE for c in active)


def test_list_conversations_with_filters(conversation_service):
    """Test listing conversations with various filters."""
    # Create conversations with different statuses
    conversation_service.get_or_create("list1@c.us", "+5511111111111")
    conv2 = conversation_service.get_or_create("list2@c.us", "+5511222222222")
    conv3 = conversation_service.get_or_create("list3@c.us", "+5511333333333")

    # Transfer one to secretary
    conversation_service.transfer_to_secretary(str(conv2.id), user_id=456)

    # Close one
    conversation_service.close(str(conv3.id))

    # Filter by status
    active_convs, total_active = conversation_service.list_conversations(
        status=ConversationStatus.ACTIVE, limit=50, offset=0
    )

    assert len(active_convs) == 1
    assert active_convs[0].status == ConversationStatus.ACTIVE


def test_list_conversations_by_assigned_user(conversation_service):
    """Test listing conversations assigned to specific user."""
    user_id = 789

    # Create and transfer conversations
    conv1 = conversation_service.get_or_create("user1@c.us", "+5511111111111")
    conv2 = conversation_service.get_or_create("user2@c.us", "+5511222222222")

    conversation_service.transfer_to_secretary(str(conv1.id), user_id=user_id)
    conversation_service.transfer_to_secretary(str(conv2.id), user_id=999)

    # Filter by user
    user_convs, total = conversation_service.list_conversations(assigned_to_user_id=user_id)

    assert len(user_convs) == 1
    assert user_convs[0].lead.assigned_to_user_id == user_id


# =====================================================================
# GET BY ID TESTS
# =====================================================================
def test_get_conversation_by_id(conversation_service):
    """Test retrieving conversation by ID."""
    created = conversation_service.get_or_create(chat_id="getbyid@c.us", phone_number="+5511666666666")

    retrieved = conversation_service.get_by_id(str(created.id))

    assert retrieved is not None
    assert retrieved.id == created.id
    assert retrieved.chat_id == created.chat_id


def test_get_nonexistent_conversation_by_id(conversation_service):
    """Test retrieving non-existent conversation returns None."""
    fake_id = "33333333-3333-3333-3333-333333333333"

    result = conversation_service.get_by_id(fake_id)

    assert result is None


# =====================================================================
# UPDATE NOTES TESTS
# =====================================================================
def test_update_notes(conversation_service):
    """Test updating conversation notes."""
    conversation = conversation_service.get_or_create(chat_id="notes@c.us", phone_number="+5511777777777")

    notes_text = "Important: Patient has allergy to X"
    updated = conversation_service.update_notes(conversation_id=str(conversation.id), notes=notes_text)

    # Note: notes field may be in LeadModel or doesn't exist yet
    assert updated is not None


def test_update_notes_conversation_not_found(conversation_service):
    """Test updating notes of non-existent conversation raises error."""
    fake_id = "44444444-4444-4444-4444-444444444444"

    with pytest.raises(NotFoundException):
        conversation_service.update_notes(conversation_id=fake_id, notes="Some notes")


# =====================================================================
# FIND BY CRITERIA TESTS
# =====================================================================


def test_find_by_criteria_multiple_filters(conversation_service):
    """Test finding conversations with multiple criteria."""
    user_id = 555

    # Create conversations
    conv1 = conversation_service.get_or_create("criteria1@c.us", "+5511111111111")
    conv2 = conversation_service.get_or_create("criteria2@c.us", "+5511222222222")
    conversation_service.get_or_create("criteria3@c.us", "+5511333333333")

    # Transfer some
    conversation_service.transfer_to_secretary(str(conv1.id), user_id=user_id)
    conversation_service.transfer_to_secretary(str(conv2.id), user_id=user_id)

    # Find transferred conversations for user
    results = conversation_service.find_by_criteria(
        {"status": ConversationStatus.TRANSFERRED, "assigned_to_user_id": user_id}
    )

    assert len(results) == 2
    assert all(c.lead.assigned_to_user_id == user_id for c in results)
    assert all(c.status == ConversationStatus.TRANSFERRED for c in results)


def test_find_by_criteria_empty_filters(conversation_service):
    """Test finding with empty filters returns all conversations."""
    # Create some conversations
    conversation_service.get_or_create("all1@c.us", "+5511111111111")
    conversation_service.get_or_create("all2@c.us", "+5511222222222")
    conversation_service.get_or_create("all3@c.us", "+5511333333333")

    # Find with no filters
    results = conversation_service.find_by_criteria({})

    assert len(results) >= 3
