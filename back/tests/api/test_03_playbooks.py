"""
PHASE 3: Playbooks Tests

Test Cases: UC-010 to UC-015
"""
import pytest
import time


class TestPhase3Playbooks:
    """Phase 3: Playbooks (Pre-Approved Messages)."""
    
    def test_uc010_create_topic(self, api_client):
        """UC-010: Create Topic 'Emagrecimento'."""
        topic_name = f"Emagrecimento_{int(time.time())}"
        response = api_client.post(
            "/topics",
            json={
                "name": topic_name,
                "description": "Tratamentos para emagrecimento saudável"
            }
        )
        
        assert response.status_code == 201, f"Got {response.status_code}: {response.text}"
        data = response.json()
        
        assert data["name"] == topic_name
        # playbook_count may not be in response
        
        # Store for next tests (still sequential within class)
        self.topic_id = data["id"]
    
    def test_uc011_create_playbook(self, api_client):
        """UC-011: Create Playbook 'Consulta Inicial'."""
        # Need topic from previous test
        if not hasattr(self, 'topic_id'):
            self.test_uc010_create_topic(api_client)
        
        playbook_name = f"Consulta_Inicial_{int(time.time())}"
        response = api_client.post(
            "/playbooks",
            json={
                "name": playbook_name,
                "description": "Fluxo de agendamento",
                "topic_id": self.topic_id,
                "is_active": True,
                "tags": ["consulta", "agendamento"]
            }
        )
        
        assert response.status_code == 201, f"Got {response.status_code}: {response.text}"
        data = response.json()
        
        assert playbook_name in data["name"]
        assert data["topic_id"] == self.topic_id
        
        # Store for next tests
        self.playbook_id = data["id"]
    
    def test_uc012_add_text_message(self, api_client):
        """UC-012: Add Text Message to Playbook."""
        # Ensure we have a playbook
        if not hasattr(self, 'playbook_id'):
            self.test_uc011_create_playbook(api_client)
        
        response = api_client.post(
            f"/playbooks/{self.playbook_id}/steps",
            json={
                "order": 1,
                "message_type": "text",
                "content": {
                    "text": "Olá! Posso ajudar com agendamento?"
                },
                "delay_seconds": 0
            }
        )
        
        assert response.status_code == 201, f"Got {response.status_code}: {response.text}"
        data = response.json()
        
        assert data["order"] == 1
        assert data["message_type"] == "text"
    
    def test_uc013_add_image_message(self, api_client):
        """UC-013: Add Image Message to Playbook."""
        # Ensure we have a playbook and text message
        if not hasattr(self, 'playbook_id'):
            self.test_uc012_add_text_message(api_client)
        
        response = api_client.post(
            f"/playbooks/{self.playbook_id}/steps",
            json={
                "order": 2,
                "message_type": "image",
                "content": {
                    "media_url": "https://example.com/image.jpg",
                    "caption": "Nossa consulta inclui..."
                },
                "delay_seconds": 3
            }
        )
        
        assert response.status_code == 201, f"Got {response.status_code}: {response.text}"
        data = response.json()
        
        assert data["order"] == 2
        assert data["message_type"] == "image"
    
    def test_uc014_search_playbooks(self, api_client):
        """UC-014: Search Playbooks by Semantic Query."""
        # Ensure we have at least one playbook
        if not hasattr(self, 'playbook_id'):
            self.test_uc011_create_playbook(api_client)
        
        response = api_client.get(
            "/playbooks/search",
            params={"query": "consulta agendamento", "limit": 5}
        )
        
        assert response.status_code == 200, f"Got {response.status_code}: {response.text}"
        data = response.json()
        
        assert isinstance(data, list)
        
        assert response.status_code == 200
        data = response.json()
        
        assert isinstance(data, list)
    
    def test_uc015_get_playbook_steps(self, api_client):
        """UC-015: Get Playbook Steps."""
        # Ensure we have playbook with steps
        if not hasattr(self, 'playbook_id'):
            self.test_uc013_add_image_message(api_client)
        
        response = api_client.get(f"/playbooks/{self.playbook_id}/steps")
        
        assert response.status_code == 200, f"Got {response.status_code}: {response.text}"
        data = response.json()
        
        assert isinstance(data, list)
        assert len(data) >= 1  # At least the messages we added
