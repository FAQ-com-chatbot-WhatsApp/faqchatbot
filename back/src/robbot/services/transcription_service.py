"""Serviço de transcrição de áudio usando Faster-Whisper (local, sem custo)."""

import logging
import tempfile
from pathlib import Path

import httpx

from robbot.config.settings import get_settings
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)
settings = get_settings()


class TranscriptionService:
    """
    Serviço de transcrição de áudio usando Faster-Whisper (inferência local).
    
    Faster-Whisper: 4x mais rápido que Whisper original, roda localmente (SEM CUSTO DE API).
    Suporta formatos comuns do WhatsApp: ogg, mp3, mp4, m4a, wav.
    
    Dependência: faster-whisper (instalado via uv add faster-whisper)
    """

    def __init__(self):
        """Inicializar modelo Faster-Whisper (lazy loading)."""
        self.model = None
        self.model_size = getattr(settings, "WHISPER_MODEL", "base")  # tiny, base, small, medium, large
        logger.info("[SUCCESS] TranscriptionService initialized (model=%s, local inference)", self.model_size)

    def _load_model(self):
        """Carregar modelo Faster-Whisper sob demanda (lazy loading)."""
        if self.model is None:
            try:
                from faster_whisper import WhisperModel

                # Load model (first time downloads ~75MB for 'base', then cached)
                self.model = WhisperModel(
                    self.model_size,
                    device="cpu",  # Use "cuda" if GPU available
                    compute_type="int8"  # Optimized for CPU
                )
                logger.info("[SUCCESS] Faster-Whisper model loaded: %s", self.model_size)
            except ImportError as e:
                raise LLMError(
                    "Whisper",
                    "faster-whisper not installed. Run: uv add faster-whisper",
                    original_error=e
                )
            except Exception as e:  # noqa: BLE001
                raise LLMError("Whisper", f"Failed to load model: {e}", original_error=e)

    async def transcribe_audio(self, audio_url: str, language: str = "pt") -> str | None:
        """
        Transcrever áudio de URL usando Faster-Whisper (local, sem custo de API).
        
        Args:
            audio_url: URL do arquivo de áudio (do WAHA ou storage)
            language: Código do idioma (padrão: "pt" para Português)
            
        Returns:
            Texto transcrito ou None se falhar
            
        Raises:
            ExternalAPIError: Se transcrição falhar
        """
        self._load_model()

        try:
            logger.info("🎤 Starting audio transcription from: %s", audio_url)

            # Download audio file
            audio_content = await self._download_audio(audio_url)
            if not audio_content:
                raise LLMError("Whisper", f"Failed to download audio from {audio_url}")

            # Save to temporary file
            with tempfile.NamedTemporaryFile(suffix=".ogg", delete=False) as temp_file:
                temp_file.write(audio_content)
                temp_path = Path(temp_file.name)

            try:
                # Transcribe with Faster-Whisper
                segments, info = self.model.transcribe(
                    str(temp_path),
                    language=language,
                    beam_size=5,
                    vad_filter=True,  # Voice Activity Detection (remove silence)
                )

                # Concatenate all segments
                transcript = " ".join([segment.text for segment in segments]).strip()

                logger.info("[SUCCESS] Audio transcribed (length=%s chars, detected_lang=%s)", len(transcript), info.language)
                return transcript

            finally:
                # Clean up temp file
                temp_path.unlink(missing_ok=True)

        except LLMError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.error(f"[ERROR] Transcription failed: {e}", exc_info=True)
            raise LLMError("Whisper", f"Audio transcription failed: {e}", original_error=e)

    def _download_audio_sync(self, url: str) -> bytes | None:
        """Baixar arquivo de áudio da URL (síncrono)."""
        try:
            with httpx.Client(timeout=30.0) as client:
                response = client.get(url)
                response.raise_for_status()
                return response.content
        except httpx.HTTPError as e:
            logger.error("[ERROR] Failed to download audio from %s: %s", url, e)
            return None
