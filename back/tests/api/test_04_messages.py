"""
PHASE 4: Messages and Media Tests

Test Cases: UC-016 to UC-021
"""
import pytest


class TestPhase4Messages:
    """Phase 4: Messages and Media (CRUD + enrichment schema)."""

    def test_uc016_create_text_message(self, api_client):
        """UC-016: Create Text Message (schema-aligned)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "text",
                "text": "Gostaria de informações sobre emagrecimento",
                "title": "Pergunta sobre emagrecimento",
                "description": "Lead perguntando sobre tratamentos de emagrecimento",
                "tags": "emagrecimento,pergunta"
            }
        )

        assert response.status_code == 201, response.text
        data = response.json()

        assert data["type"] == "text"
        assert "emagrecimento" in data["text"]
        assert "title" in data  # may be filled by enrichment or stay None

    def test_uc017_create_voice_message(self, api_client):
        """UC-017: Create Voice Message (Faster-Whisper path)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "voice",
                "file": {
                    "url": "https://example.com/audio.ogg",
                    "mimetype": "audio/ogg",
                    "filename": "audio.ogg"
                },
                "caption": "Áudio do paciente sobre consulta"
            }
        )

        assert response.status_code == 201, response.text
        data = response.json()

        assert data["type"] == "voice"
        assert data["file"]["mimetype"] == "audio/ogg"

    def test_uc018_create_image_message(self, api_client):
        """UC-018: Create Image Message (BLIP-2 path)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "image",
                "file": {
                    "url": "https://example.com/image.jpg",
                    "mimetype": "image/jpeg",
                    "filename": "image.jpg"
                },
                "caption": "Antes e depois do procedimento"
            }
        )

        assert response.status_code == 201, response.text
        data = response.json()

        assert data["type"] == "image"
        assert data["file"]["mimetype"] == "image/jpeg"

    def test_uc019_create_video_message(self, api_client):
        """UC-019: Create Video Message (transcription + metadata path)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "video",
                "file": {
                    "url": "https://example.com/video.mp4",
                    "mimetype": "video/mp4",
                    "filename": "video.mp4"
                },
                "caption": "Vídeo explicando o procedimento"
            }
        )

        assert response.status_code == 201, response.text
        data = response.json()

        assert data["type"] == "video"
        assert data["file"]["mimetype"] == "video/mp4"

    def test_uc020_create_document_message(self, api_client):
        """UC-020: Create Document Message (metadata generation)."""
        response = api_client.post(
            "/messages",
            json={
                "type": "document",
                "file": {
                    "url": "https://example.com/tabela_precos.pdf",
                    "mimetype": "application/pdf",
                    "filename": "tabela_precos.pdf"
                },
                "caption": "Tabela de preços atualizada"
            }
        )

        assert response.status_code == 201, response.text
        data = response.json()

        assert data["type"] == "document"
        assert data["file"]["mimetype"] == "application/pdf"

    def test_uc021_create_location_message(self, api_client):
        """UC-021: Create Location Message."""
        response = api_client.post(
            "/messages",
            json={
                "type": "location",
                "latitude": -29.5838212,
                "longitude": -51.0869905,
                "title": "Clínica GO"
            }
        )

        assert response.status_code == 201, response.text
        data = response.json()

        assert data["type"] == "location"
        assert data["latitude"] == -29.5838212
