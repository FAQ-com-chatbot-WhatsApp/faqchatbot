"""
PHASE 5: Conversations and Leads Tests

Test Cases: UC-021 to UC-025
"""

import time


class TestPhase5Conversations:
    """Phase 5: Conversations and Leads."""

    test_phone = "5551999887766"
    conversation_id = None
    lead_id = None

    def test_uc021_simulate_webhook_inbound(self, api_base_url):
        """UC-021: Simulate WAHA Webhook (Inbound Message)."""
        import requests

        response = requests.post(f"{api_base_url}/webhooks/waha",
            headers={"X-WAHA-Event": "message"},
            json={
                "event": "message",
                "session": "test",
                "payload": {
                    "id": f"msg_{int(time.time())}",
                    "timestamp": int(time.time()),
                    "from": f"{self.test_phone}@c.us",
                    "body": "Gostaria de informações sobre emagrecimento",
                    "hasMedia": False
                }
            }
        )

        assert response.status_code == 202  # Accepted
        time.sleep(10)  # Wait for async processing

    def test_uc022_verify_conversation_created(self, api_client):
        """UC-022: Verify Conversation Created."""
        response = api_client.get(
            "/conversations",
            params={"phone_number": self.test_phone}
        )

        assert response.status_code == 200
        data = response.json()

        assert isinstance(data, dict)
        assert "conversations" in data
        assert len(data["conversations"]) > 0

        conv = data["conversations"][0]
        assert conv["phone_number"] == self.test_phone
        assert conv["status"] == "active"

        TestPhase5Conversations.conversation_id = conv["id"]
        TestPhase5Conversations.lead_id = conv["lead_id"]

    def test_uc023_get_conversation_messages(self, api_client):
        """UC-023: Get Conversation Messages."""
        response = api_client.get(f"/conversations/{self.conversation_id}/messages")

        assert response.status_code == 200
        data = response.json()

        assert isinstance(data, list)
        assert len(data) >= 2  # inbound + outbound

    def test_uc024_get_lead_data(self, api_client):
        """UC-024: Get Lead Data."""
        response = api_client.get(
            "/leads",
            params={"phone_number": self.test_phone}
        )

        assert response.status_code == 200
        data = response.json()

        assert isinstance(data, dict)
        assert "leads" in data
        assert len(data["leads"]) > 0
        
        lead = data["leads"][0]
        assert lead["id"] == self.lead_id
        assert lead["phone_number"] == self.test_phone

    def test_uc025_verify_lead_interactions(self, api_client):
        """UC-025: Verify Lead Interactions."""
        response = api_client.get(f"/leads/{self.lead_id}/interactions")

        assert response.status_code == 200
        data = response.json()

        assert isinstance(data, list)
        assert len(data) > 0
