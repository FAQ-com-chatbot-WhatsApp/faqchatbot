import logging
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from pydantic import BaseModel

from robbot.api.v1.dependencies import get_current_user, get_db
from robbot.services.integration.faq_integration_service import FaqIntegrationService

router = APIRouter()
logger = logging.getLogger(__name__)

class FaqSyncRequest(BaseModel):
    base_url: str = "/faq-api"
    token: str = "Bearer c2hhMjU2OjI6ZTkwMjY4ODFhMmYzNzA1NDMyMWE5YWYzY2NlOWY4NTM1MWZkYTIzZDJmNDJkOGU0ZGZmM2E1ODllMmZiMTNlMg"

class SyncResponse(BaseModel):
    message: str
    success: bool

class SearchResponse(BaseModel):
    items: list
    total: int

@router.post("/sync", response_model=SyncResponse)
async def sync_faq_with_bot(
    payload: FaqSyncRequest,
    db: Session = Depends(get_db),
    current_user: dict = Depends(get_current_user),
):
    """
    Sync all FAQ groups, categories, and questions from the external No Boss FAQ API
    to the bot's internal knowledge base (Topics, Contexts, and Embeddings).
    """
    try:
        # Use the provided base_url and token (fallback to defaults if not in prod env)
        # In a real scenario, these would come from encrypted system settings.
        service = FaqIntegrationService(
            db=db, 
            base_url="https://www.faq.extensions-joomla.com/api/index.php" if payload.base_url == "/faq-api" else payload.base_url, 
            token=payload.token
        )
        
        # Note: If base_url is /faq-api, the frontend is proxied. 
        # The backend needs the ACTUAL Joomla URL.
        # FIXME: These should be in environment variables.
        
        success = await service.sync_all()
        
        if success:
            return SyncResponse(message="Sincronização concluída com sucesso!", success=True)
            
        return SyncResponse(message="Falha na sincronização.", success=False)
        
    except Exception as e:
        logger.error(f"[FAQ_CONTROLLER] Error during sync: {e}", exc_info=True)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Erro ao sincronizar FAQ: {str(e)}"
        )

@router.get("/search", response_model=SearchResponse)
async def search_faq(
    q: str,
    db: Session = Depends(get_db),
    current_user: dict = Depends(get_current_user),
):
    """Search FAQ items in the internal database."""
    try:
        service = FaqIntegrationService(db=db, base_url="", token="")
        items = await service.search_internal(q)
        return SearchResponse(items=items, total=len(items))
    except Exception as e:
        logger.error(f"[FAQ_CONTROLLER] Error during search: {e}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Erro ao pesquisar FAQ"
        )
