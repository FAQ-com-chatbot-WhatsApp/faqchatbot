import logging
import httpx
from sqlalchemy.orm import Session

from robbot.services.ai.context_service import ContextService
from robbot.infra.persistence.models.topic_model import TopicModel
from robbot.infra.persistence.models.context_model import ContextModel
from robbot.infra.persistence.models.content_model import ContentModel
from robbot.infra.persistence.models.context_item_model import ContextItemModel

logger = logging.getLogger(__name__)

class FaqIntegrationService:
    """
    Service to synchronize No Boss FAQ data with the bot's context system.
    Maps:
    - FAQ Groups -> Topics
    - FAQ Categories -> Contexts
    - FAQ Questions -> Context Items (Content type 'text')
    """

    def __init__(self, db: Session, base_url: str, token: str):
        self.db = db
        self.base_url = base_url.rstrip('/')
        self.token = token if token.startswith('Bearer ') else f"Bearer {token}"
        self.headers = {
            "Accept": "application/json, application/vnd.api+json",
            "Authorization": self.token
        }
        self.context_service = ContextService(db)

    async def fetch_all(self, endpoint: str):
        """Fetch data from No Boss FAQ API."""
        async with httpx.AsyncClient(timeout=30.0) as client:
            url = f"{self.base_url}/v1/nobossfaq/{endpoint}"
            logger.info(f"[FAQ_SYNC] Fetching from {url}")
            response = await client.get(url, headers=self.headers)
            if response.status_code != 200:
                logger.error(f"[FAQ_SYNC] error fetching {endpoint}: {response.status_code} - {response.text}")
                return []
            data = response.json()
            # Normalize response based on No Boss FAQ format
            if isinstance(data, dict) and "data" in data:
                items = data["data"]
                if isinstance(items, dict) and "items" in items:
                    return items["items"]
                return items if isinstance(items, list) else []
            return []

    async def sync_all(self):
        """Perform full synchronization."""
        logger.info("[FAQ_SYNC] Starting full synchronization...")
        # 1. Fetch Groups (Topics)
        groups = await self.fetch_all("groups?state=1&language=pt-BR")
        group_map = {} # external_id -> local_topic_id
        for g in groups:
            ext_id = str(g.get("id"))
            name = g.get("name") or g.get("attributes", {}).get("name") or f"Grupo {ext_id}"
            # Check if topic already exists for this FAQ group
            topic = self.db.query(TopicModel).filter(TopicModel.name == name).first()
            if not topic:
                topic = TopicModel(
                    name=name,
                    description=f"Sincronizado do No Boss FAQ (ID: {ext_id})",
                    category="FAQ Repository",
                    active=True
                )
                self.db.add(topic)
                self.db.flush()
                logger.info(f"[FAQ_SYNC] Created Topic: {name}")
            group_map[ext_id] = topic.id

        # 2. Fetch Categories (Contexts)
        categories = await self.fetch_all("categories?language=pt-BR")
        category_map = {} # external_id -> local_context_id
        for c in categories:
            ext_id = str(c.get("id"))
            ext_group_id = str(c.get("id_faqs_group") or c.get("attributes", {}).get("id_faqs_group"))
            name = c.get("name") or c.get("attributes", {}).get("name") or c.get("title") or c.get("attributes", {}).get("title") or f"Categoria {ext_id}"
            local_topic_id = group_map.get(ext_group_id)
            if not local_topic_id:
                logger.warning(f"[FAQ_SYNC] Skipping category {name} (ID: {ext_id}): Group {ext_group_id} not found.")
                continue
            # Check if context exists
            context = self.db.query(ContextModel).filter(
                ContextModel.name == name,
                ContextModel.topic_id == local_topic_id
            ).first()
            if not context:
                context = ContextModel(
                    topic_id=local_topic_id,
                    name=name,
                    description=f"Sincronizado do No Boss FAQ (ID: {ext_id})",
                    active=True
                )
                self.db.add(context)
                self.db.flush()
                logger.info(f"[FAQ_SYNC] Created Context: {name} in Topic {local_topic_id}")
            category_map[ext_id] = context.id

        # 3. Fetch Questions (Items)
        questions = await self.fetch_all("questions?language=pt-BR")
        for q in questions:
            ext_id = str(q.get("id"))
            ext_cat_id = str(q.get("id_category") or q.get("attributes", {}).get("id_category"))
            question_text = q.get("question") or q.get("attributes", {}).get("question")
            answer_text = q.get("answer") or q.get("attributes", {}).get("answer")
            if not question_text or not answer_text:
                continue
            local_context_id = category_map.get(ext_cat_id)
            if not local_context_id:
                logger.warning(f"[FAQ_SYNC] Skipping question (ID: {ext_id}): Category {ext_cat_id} not found.")
                continue
            # Full text for indexing
            full_content = f"Pergunta: {question_text}\nResposta: {answer_text}"
            # Check if item already exists in this context (by title/question)
            existing_item = self.db.query(ContextItemModel).join(ContentModel).filter(
                ContextItemModel.context_id == local_context_id,
                ContentModel.title == question_text
            ).first()
            if not existing_item:
                # Create Content
                content = ContentModel(
                    type="text",
                    title=question_text,
                    text=full_content,
                    description=f"FAQ Item ID: {ext_id}",
                    tags=f"faq_id:{ext_id},category_id:{ext_cat_id}"
                )
                self.db.add(content)
                self.db.flush()
                # Add to Context
                self.context_service.add_item(
                    context_id=local_context_id,
                    content_id=str(content.id),
                    context_hint=f"Categoria: {category_map.get(ext_cat_id)}"
                )
                logger.info(f"[FAQ_SYNC] Synced Question: {question_text}")

        self.db.commit()
        logger.info("[FAQ_SYNC] Synchronization completed successfully.")
        return True
