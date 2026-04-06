from fastapi import APIRouter, Depends
from pydantic import BaseModel
from sqlalchemy.orm import Session

from robbot.api.v1.dependencies import get_db, require_role
from robbot.config.settings import settings
from robbot.infra.integrations.llm.llm_client import get_llm_client
from robbot.infra.persistence.repositories.system_setting_repository import SystemSettingRepository

router = APIRouter()


# ========== SCHEMAS ==========


class AISettingsUpdate(BaseModel):
    """Schema for updating AI settings."""

    google_api_key: str | None = None
    gemini_model: str | None = None
    gemini_max_tokens: int | None = None
    gemini_temperature: float | None = None

    groq_api_key: str | None = None
    groq_model: str | None = None
    groq_max_tokens: int | None = None
    groq_temperature: float | None = None

    llm_primary_provider: str | None = None
    llm_enable_fallback: bool | None = None


class AISettingsResponse(AISettingsUpdate):
    """Schema for AI settings response."""

    pass


class ModelOption(BaseModel):
    """Schema for a model option."""

    id: str
    name: str


class ModelListResponse(BaseModel):
    """Schema for list of models."""

    models: list[ModelOption]


# ========== ENDPOINTS ==========


@router.get("/ai", response_model=AISettingsResponse)
async def get_ai_settings(
    db: Session = Depends(get_db),
    user=Depends(require_role("admin", "manager")),
):
    """Get dynamic AI settings from DB (with env fallback)."""
    repo = SystemSettingRepository(db)
    db_settings = repo.get_all_settings()

    # Merge DB settings with Env settings (DB takes priority)
    return AISettingsResponse(
        google_api_key=db_settings.get("GOOGLE_API_KEY", settings.GOOGLE_API_KEY),
        gemini_model=db_settings.get("GEMINI_MODEL", settings.GEMINI_MODEL),
        gemini_max_tokens=int(db_settings.get("GEMINI_MAX_TOKENS", settings.GEMINI_MAX_TOKENS)),
        gemini_temperature=float(db_settings.get("GEMINI_TEMPERATURE", settings.GEMINI_TEMPERATURE)),
        groq_api_key=db_settings.get("GROQ_API_KEY", settings.GROQ_API_KEY),
        groq_model=db_settings.get("GROQ_MODEL", settings.GROQ_MODEL),
        groq_max_tokens=int(db_settings.get("GROQ_MAX_TOKENS", settings.GROQ_MAX_TOKENS)),
        groq_temperature=float(db_settings.get("GROQ_TEMPERATURE", settings.GROQ_TEMPERATURE)),
        llm_primary_provider=db_settings.get("LLM_PRIMARY_PROVIDER", settings.LLM_PRIMARY_PROVIDER),
        llm_enable_fallback=db_settings.get("LLM_ENABLE_FALLBACK", str(settings.LLM_ENABLE_FALLBACK)).lower() == "true",
    )


@router.put("/ai", response_model=AISettingsResponse)
async def update_ai_settings(
    update: AISettingsUpdate,
    db: Session = Depends(get_db),
    user=Depends(require_role("admin")),
):
    """Update dynamic AI settings in DB."""
    repo = SystemSettingRepository(db)

    fields = update.model_dump(exclude_unset=True)
    for key, value in fields.items():
        # Convert to DB key format (UPPER_CASE)
        db_key = key.upper()
        repo.set_value(db_key, str(value))

    # Refresh LLM Client to apply changes
    get_llm_client().refresh()

    return await get_ai_settings(db, user)

@router.get("/ai/models/{provider}", response_model=ModelListResponse)
async def list_ai_models(
    provider: str,
    db: Session = Depends(get_db),
    user=Depends(require_role("admin", "manager")),
):
    """List available models for a provider using configured API keys."""
    repo = SystemSettingRepository(db)
    db_settings = repo.get_all_settings()

    models = []
    try:
        if provider == "gemini":
            from google import genai
            api_key = db_settings.get("GOOGLE_API_KEY", settings.GOOGLE_API_KEY)
            if api_key:
                client = genai.Client(api_key=api_key)
                # Filter to only show generating models
                for m in client.models.list():
                    supported_actions = getattr(m, "supported_actions", [])
                    if "generateContent" in supported_actions:
                        # Normalize name: models/gemini-1.5-flash -> gemini-1.5-flash
                        model_id = m.name.replace("models/", "")
                        models.append(ModelOption(id=model_id, name=m.display_name or m.name))

        elif provider == "groq":
            from groq import Groq
            api_key = db_settings.get("GROQ_API_KEY", settings.GROQ_API_KEY)
            if api_key:
                client = Groq(api_key=api_key)
                for m in client.models.list().data:
                    models.append(ModelOption(id=m.id, name=m.id))

    except Exception:
        # Fallback to hardcoded common models if API fails
        if provider == "gemini":
            models = [
                ModelOption(id="gemini-1.5-flash", name="Gemini 1.5 Flash"),
                ModelOption(id="gemini-1.5-pro", name="Gemini 1.5 Pro"),
                ModelOption(id="gemini-2.0-flash-exp", name="Gemini 2.0 Flash (Experimental)"),
            ]
        elif provider == "groq":
            models = [
                ModelOption(id="llama-3.3-70b-versatile", name="Llama 3.3 70B Versatile"),
                ModelOption(id="llama3-70b-8192", name="Llama 3 70B"),
                ModelOption(id="llama3-8b-8192", name="Llama 3 8B"),
                ModelOption(id="mixtral-8x7b-32768", name="Mixtral 8x7B"),
            ]

    # Return at least the common ones if empty
    if not models:
        if provider == "gemini":
            models = [ModelOption(id="gemini-1.5-flash", name="Gemini 1.5 Flash")]
        elif provider == "groq":
            models = [ModelOption(id="llama-3.3-70b-versatile", name="Llama 3.3 70B Versatile")]

    return ModelListResponse(models=models)
