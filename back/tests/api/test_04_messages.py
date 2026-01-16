"""
PHASE 4: Messages and Media Tests

Test Cases: UC-016 to UC-020
"""
import pytest


class TestPhase4Messages:
    """Phase 4: Messages and Media."""
    
    def test_uc016_create_text_message(self, api_client):
        """UC-016: Create Text Message."""
        response = api_client.post(
            "/messages",
            json={
                "type": "text",
                "text": "Gostaria de informações sobre emagrecimento"
            }
        )
        
        assert response.status_code == 201
        data = response.json()
        
        assert data["type"] == "text"
        assert "emagrecimento" in data["text"]
    
    def test_uc017_create_audio_message(self, api_client):
        """UC-017: Create Audio Message (Faster-Whisper)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "voice",
                "media_url": "https://example.com/audio.ogg",
                "mime_type": "audio/ogg",
                "duration": 15,
                "auto_transcribe": True
            }
        )
        
        assert response.status_code == 201
        data = response.json()
        
        assert data["type"] == "voice"
    
    def test_uc018_create_image_message(self, api_client):
        """UC-018: Create Image Message (BLIP-2)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "image",
                "media_url": "https://example.com/image.jpg",
                "mime_type": "image/jpeg",
                "caption": "Minha refeição"
            }
        )
        
        assert response.status_code == 201
        data = response.json()
        
        assert data["type"] == "image"
    
    def test_uc019_create_video_message(self, api_client):
        """UC-019: Create Video Message."""
        response = api_client.post(
            "/messages",
            json={
                "type": "video",
                "media_url": "https://example.com/video.mp4",
                "mime_type": "video/mp4",
                "duration": 30
            }
        )
        
        assert response.status_code == 201
        data = response.json()
        
        assert data["type"] == "video"
    
    def test_uc020_create_location_message(self, api_client):
        """UC-020: Create Location Message."""
        response = api_client.post(
            "/messages",
            json={
                "type": "location",
                "latitude": -29.5838212,
                "longitude": -51.0869905,
                "title": "Clínica GO"
            }
        )
        
        assert response.status_code == 201
        data = response.json()
        
        assert data["type"] == "location"
        assert data["latitude"] == -29.5838212
