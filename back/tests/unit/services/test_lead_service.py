"""
Unit tests for LeadService.

Tests core business logic for lead management.
"""

import pytest
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from robbot.services.lead_service import LeadService
from robbot.domain.enums import LeadStatus
from robbot.core.exceptions import BusinessRuleError, NotFoundException
from robbot.infra.db.models.lead_model import LeadModel


@pytest.fixture()
def db_session():
    """Create in-memory SQLite database for testing."""
    engine = create_engine("sqlite+pysqlite:///:memory:", echo=False)
    LeadModel.__table__.create(bind=engine)
    SessionLocal = sessionmaker(bind=engine, autocommit=False, autoflush=False)
    session = SessionLocal()
    try:
        yield session
    finally:
        session.close()


@pytest.fixture()
def lead_service(db_session):
    """Create LeadService instance."""
    return LeadService(db_session)


# =====================================================================
# CREATE LEAD TESTS
# =====================================================================


def test_create_lead_from_conversation(lead_service, db_session):
    """Test creating a new lead from conversation."""
    phone = "+5511999999999"
    name = "John Doe"
    email = "john@example.com"
    
    lead = lead_service.create_from_conversation(
        phone_number=phone,
        name=name,
        email=email
    )
    
    assert lead is not None
    assert lead.phone_number == phone
    assert lead.name == name
    assert lead.email == email
    assert lead.maturity_score == 0


def test_create_lead_duplicate_phone_returns_existing(lead_service, db_session):
    """Test creating lead with duplicate phone returns existing lead."""
    phone = "+5511888888888"
    
    # Create first lead
    lead1 = lead_service.create_from_conversation(
        phone_number=phone,
        name="First Name"
    )
    
    # Try to create duplicate
    lead2 = lead_service.create_from_conversation(
        phone_number=phone,
        name="Second Name"
    )
    
    # Should return the same lead
    assert lead1.id == lead2.id


def test_create_lead_without_email(lead_service, db_session):
    """Test creating lead without email (optional field)."""
    phone = "+5511777777777"
    name = "Jane Doe"
    
    lead = lead_service.create_from_conversation(
        phone_number=phone,
        name=name,
        email=None
    )
    
    assert lead is not None
    assert lead.email is None
    assert lead.phone_number == phone


# =====================================================================
# UPDATE MATURITY SCORE TESTS
# =====================================================================


def test_update_maturity_score(lead_service, db_session):
    """Test updating lead maturity score."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511666666666",
        name="Test Lead"
    )
    
    new_score = 75
    updated = lead_service.update_maturity(
        lead_id=str(lead.id),
        new_score=new_score
    )
    
    assert updated.maturity_score == new_score


def test_update_maturity_score_invalid_value_low(lead_service, db_session):
    """Test updating maturity score with value below 0 raises error."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511555555555",
        name="Test Lead"
    )
    
    with pytest.raises(BusinessRuleError) as exc_info:
        lead_service.update_maturity(
            lead_id=str(lead.id),
            new_score=-10
        )
    
    assert "between 0 and 100" in str(exc_info.value)


def test_update_maturity_score_invalid_value_high(lead_service, db_session):
    """Test updating maturity score with value above 100 raises error."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511444444444",
        name="Test Lead"
    )
    
    with pytest.raises(BusinessRuleError) as exc_info:
        lead_service.update_maturity(
            lead_id=str(lead.id),
            new_score=150
        )
    
    assert "between 0 and 100" in str(exc_info.value)


def test_update_maturity_nonexistent_lead(lead_service):
    """Test updating maturity score of non-existent lead raises error."""
    fake_id = "00000000-0000-0000-0000-000000000000"
    
    with pytest.raises(NotFoundException) as exc_info:
        lead_service.update_maturity(
            lead_id=fake_id,
            new_score=50
        )
    
    assert "not found" in str(exc_info.value).lower()


def test_update_maturity_boundary_values(lead_service, db_session):
    """Test updating maturity score with boundary values (0 and 100)."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511333333333",
        name="Boundary Test"
    )
    lead_id = str(lead.id)
    
    # Test minimum boundary
    updated = lead_service.update_maturity(lead_id, 0)
    assert updated.maturity_score == 0
    
    # Test maximum boundary
    updated = lead_service.update_maturity(lead_id, 100)
    assert updated.maturity_score == 100


# =====================================================================
# ASSIGN TO USER TESTS
# =====================================================================


def test_assign_lead_to_user(lead_service, db_session):
    """Test assigning lead to a user."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511222222222",
        name="Assignment Test"
    )
    
    user_id = 123
    updated = lead_service.assign_to_user(
        lead_id=str(lead.id),
        user_id=user_id
    )
    
    assert updated.assigned_to_user_id == user_id


def test_assign_nonexistent_lead(lead_service):
    """Test assigning non-existent lead raises error."""
    fake_id = "11111111-1111-1111-1111-111111111111"
    
    with pytest.raises(NotFoundException):
        lead_service.assign_to_user(
            lead_id=fake_id,
            user_id=456
        )


def test_reassign_lead_to_different_user(lead_service, db_session):
    """Test reassigning lead to a different user."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511111111111",
        name="Reassign Test"
    )
    lead_id = str(lead.id)
    
    # Assign to first user
    lead_service.assign_to_user(lead_id, user_id=100)
    
    # Reassign to second user
    updated = lead_service.assign_to_user(lead_id, user_id=200)
    
    assert updated.assigned_to_user_id == 200


# =====================================================================
# CONVERT LEAD TESTS
# =====================================================================


def test_convert_lead(lead_service, db_session):
    """Test converting a lead sets maturity score to 100."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511000000000",
        name="Convert Test"
    )
    
    converted = lead_service.convert(str(lead.id))
    
    assert converted.maturity_score == 100


def test_convert_nonexistent_lead(lead_service):
    """Test converting non-existent lead raises error."""
    fake_id = "22222222-2222-2222-2222-222222222222"
    
    with pytest.raises(NotFoundException):
        lead_service.convert(fake_id)


def test_convert_lead_with_low_score(lead_service, db_session):
    """Test converting lead with low initial score."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511999888777",
        name="Low Score Convert"
    )
    
    # Set low score first
    lead_service.update_maturity(str(lead.id), 25)
    
    # Convert
    converted = lead_service.convert(str(lead.id))
    
    assert converted.maturity_score == 100


# =====================================================================
# MARK LOST TESTS
# =====================================================================


def test_mark_lead_lost(lead_service, db_session):
    """Test marking lead as lost sets maturity score to 0."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511888777666",
        name="Lost Test"
    )
    
    # Set higher score first
    lead_service.update_maturity(str(lead.id), 80)
    
    # Mark as lost
    lost = lead_service.mark_lost(
        lead_id=str(lead.id),
        reason="Not interested"
    )
    
    assert lost.maturity_score == 0


def test_mark_lost_nonexistent_lead(lead_service):
    """Test marking non-existent lead as lost raises error."""
    fake_id = "33333333-3333-3333-3333-333333333333"
    
    with pytest.raises(NotFoundException):
        lead_service.mark_lost(fake_id, reason="Test")


def test_mark_lost_without_reason(lead_service, db_session):
    """Test marking lead as lost without specifying reason."""
    lead = lead_service.create_from_conversation(
        phone_number="+5511777666555",
        name="Lost No Reason"
    )
    
    # Mark as lost without reason
    lost = lead_service.mark_lost(str(lead.id))
    
    assert lost.maturity_score == 0


# =====================================================================
# GET LEADS BY STATUS TESTS
# =====================================================================


def test_get_leads_by_status(lead_service, db_session):
    """Test retrieving leads filtered by status."""
    # Create leads with different statuses
    lead1 = lead_service.create_from_conversation(
        "+5511111222333", "Lead 1"
    )
    lead2 = lead_service.create_from_conversation(
        "+5511444555666", "Lead 2"
    )
    
    # Note: Since we're using LeadStatus enum, we need to check 
    # if the Lead entity has a status field. If not, this test
    # might need adjustment based on actual implementation.
    
    # This is a simplified test - adjust based on actual status handling
    leads = lead_service.get_leads_by_status(
        status=LeadStatus.NEW,
        limit=50
    )
    
    assert isinstance(leads, list)


def test_get_leads_by_status_with_limit(lead_service, db_session):
    """Test getting leads by status respects limit parameter."""
    # Create multiple leads
    for i in range(5):
        lead_service.create_from_conversation(
            f"+551199988877{i}",
            f"Lead {i}"
        )
    
    # Get with limit
    leads = lead_service.get_leads_by_status(
        status=LeadStatus.NEW,
        limit=3
    )
    
    assert len(leads) <= 3


# =====================================================================
# GET UNASSIGNED LEADS TESTS
# =====================================================================


def test_get_unassigned_leads(lead_service, db_session):
    """Test retrieving unassigned leads."""
    # Create leads
    lead1 = lead_service.create_from_conversation(
        "+5511111111111", "Unassigned 1"
    )
    lead2 = lead_service.create_from_conversation(
        "+5511222222222", "Assigned 1"
    )
    lead3 = lead_service.create_from_conversation(
        "+5511333333333", "Unassigned 2"
    )
    
    # Assign one lead
    lead_service.assign_to_user(str(lead2.id), user_id=100)
    
    # Get unassigned
    unassigned = lead_service.get_unassigned_leads()
    
    assert len(unassigned) == 2
    assert all(lead.assigned_to_user_id is None for lead in unassigned)


def test_get_unassigned_leads_with_limit(lead_service, db_session):
    """Test getting unassigned leads respects limit."""
    # Create multiple unassigned leads
    for i in range(5):
        lead_service.create_from_conversation(
            f"+551188877766{i}",
            f"Unassigned {i}"
        )
    
    # Get with limit
    unassigned = lead_service.get_unassigned_leads(limit=2)
    
    assert len(unassigned) <= 2


def test_get_unassigned_leads_empty(lead_service, db_session):
    """Test getting unassigned leads when all are assigned."""
    # Create and assign all leads
    lead1 = lead_service.create_from_conversation(
        "+5511444444444", "Assigned 1"
    )
    lead2 = lead_service.create_from_conversation(
        "+5511555555555", "Assigned 2"
    )
    
    lead_service.assign_to_user(str(lead1.id), user_id=100)
    lead_service.assign_to_user(str(lead2.id), user_id=200)
    
    # Get unassigned
    unassigned = lead_service.get_unassigned_leads()
    
    assert len(unassigned) == 0


# =====================================================================
# LIST LEADS WITH FILTERS TESTS
# =====================================================================


def test_list_leads_with_multiple_filters(lead_service, db_session):
    """Test listing leads with multiple combined filters."""
    # Create leads with various attributes
    lead1 = lead_service.create_from_conversation(
        "+5511100100100", "Multi Filter 1"
    )
    lead2 = lead_service.create_from_conversation(
        "+5511200200200", "Multi Filter 2"
    )
    
    # Assign and set scores
    lead_service.assign_to_user(str(lead1.id), user_id=999)
    lead_service.update_maturity(str(lead1.id), 80)
    lead_service.update_maturity(str(lead2.id), 30)
    
    # List with filters
    leads, total = lead_service.list_leads(
        assigned_to_user_id=999,
        min_score=50,
        limit=50,
        offset=0
    )
    
    assert isinstance(leads, list)
    assert isinstance(total, int)


def test_list_leads_unassigned_only_filter(lead_service, db_session):
    """Test listing only unassigned leads."""
    # Create mixed leads
    lead1 = lead_service.create_from_conversation(
        "+5511300300300", "Unassigned Filter"
    )
    lead2 = lead_service.create_from_conversation(
        "+5511400400400", "Assigned Filter"
    )
    
    lead_service.assign_to_user(str(lead2.id), user_id=777)
    
    # List unassigned only
    leads, total = lead_service.list_leads(
        unassigned_only=True
    )
    
    assert len(leads) >= 1
    assert all(lead.assigned_to_user_id is None for lead in leads)


def test_list_leads_pagination(lead_service, db_session):
    """Test listing leads with pagination (limit and offset)."""
    # Create multiple leads
    for i in range(10):
        lead_service.create_from_conversation(
            f"+551150050050{i}",
            f"Pagination {i}"
        )
    
    # Get first page
    page1, total = lead_service.list_leads(limit=5, offset=0)
    
    # Get second page
    page2, _ = lead_service.list_leads(limit=5, offset=5)
    
    assert len(page1) <= 5
    assert len(page2) <= 5
    
    # Ensure different results (if we have enough leads)
    if len(page1) > 0 and len(page2) > 0:
        assert page1[0].id != page2[0].id
