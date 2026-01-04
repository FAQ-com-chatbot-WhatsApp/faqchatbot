"""
VisionService para análise de imagens usando BLIP-2 (open source, local, SEM CUSTO).

Alternativa local ao Gemini Vision.
Usa Salesforce BLIP-2 para image captioning e visual question answering.
"""

import logging
import os
import tempfile

import httpx
from PIL import Image
from transformers import BlipForConditionalGeneration, BlipProcessor

logger = logging.getLogger(__name__)
class VisionService:
    """
    Serviço para análise de imagens usando BLIP-2 (local, sem custo).

    Modelo: Salesforce/blip-image-captioning-base
    - Open source (BSD-3 License)
    - Roda localmente (CPU ou GPU)
    - ~990MB download inicial
    - Zero custo de API
    - Qualidade boa para captioning

    Funcionalidades:
    - Gerar descrição automática de imagens
    - Visual Question Answering (VQA)
    - Análise contextual para saúde/emagrecimento
    """

    def __init__(self):
        """Inicializar modelo BLIP-2 (lazy loading)."""
        self.processor = None
        self.model = None
        self.model_name = "Salesforce/blip-image-captioning-base"

    def _load_model(self):
        """Carregar modelo BLIP-2 sob demanda."""
        if self.model is None:
            logger.info("🔄 Carregando modelo BLIP-2: %s (~990MB)...", self.model_name)
            try:
                self.processor = BlipProcessor.from_pretrained(self.model_name)
                self.model = BlipForConditionalGeneration.from_pretrained(self.model_name)
                logger.info("[SUCCESS] Modelo BLIP-2 carregado com sucesso!")
            except Exception as e:  # noqa: BLE001 (blind exception)
                logger.error("[ERROR] Erro ao carregar BLIP-2: %s", e)
                raise

    async def analyze_image(
        self,
        image_url: str,
        context: str = "medical",
        questions: list[str] | None = None
    ) -> dict[str, str]:
        """
        Analisar imagem e gerar descrição detalhada.

        Args:
            image_url: URL da imagem
            context: Contexto da análise (medical, fitness, food, etc)
            questions: Perguntas específicas para VQA

        Returns:
            Dict com:
            - caption: Descrição principal da imagem
            - detailed_description: Análise detalhada
            - tags: Tags relevantes
            - answers: Respostas para perguntas (se fornecidas)
        """
        self._load_model()

        try:
            # 1. Baixar imagem
            logger.info("📥 Baixando imagem de: %s", image_url)
            image = await self._download_image(image_url)

            # 2. Gerar caption básico
            caption = await self._generate_caption(image)
            logger.info("[SUCCESS] Caption gerado: %s", caption)

            # 3. Gerar descrição detalhada com perguntas contextuais
            detailed_description = await self._generate_detailed_description(
                image,
                caption,
                context
            )

            # 4. Extrair tags da descrição
            tags = self._extract_tags(caption, detailed_description, context)

            # 5. Responder perguntas customizadas (VQA)
            answers = {}
            if questions:
                for question in questions:
                    answer = await self._answer_question(image, question)
                    answers[question] = answer

            return {
                "caption": caption,
                "detailed_description": detailed_description,
                "tags": tags,
                "answers": answers
            }

        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error("[ERROR] Erro ao analisar imagem: %s", e)
            raise

    async def _download_image(self, url: str) -> Image.Image:
        """Baixar imagem de URL."""
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.get(url)
            response.raise_for_status()

            # Salvar temporariamente
            with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as tmp:
                tmp.write(response.content)
                tmp_path = tmp.name

            # Abrir com PIL
            image = Image.open(tmp_path).convert('RGB')

            # Limpar arquivo temporário
            os.unlink(tmp_path)

            return image

    async def _generate_caption(self, image: Image.Image) -> str:
        """Gerar caption básico da imagem."""
        # Process image
        inputs = self.processor(image, return_tensors="pt")

        # Gerar caption
        output = self.model.generate(**inputs, max_new_tokens=50)
        return self.processor.decode(output[0], skip_special_tokens=True)


    async def _generate_detailed_description(
        self,
        image: Image.Image,
        caption: str,
        context: str
    ) -> str:
        """
        Gerar descrição detalhada com perguntas contextuais.

        Para contexto médico/emagrecimento, faz perguntas como:
        - "What type of food is shown?"
        - "Is this a healthy meal?"
        - "What activities are visible?"
        """
        if context == "medical":
            questions = [
                "What is the main subject of this image?",
                "What details are visible?",
                "What colors and objects are present?"
            ]
        else:
            questions = [
                "What is shown in this image?",
                "What are the key details?"
            ]

        descriptions = [caption]

        for question in questions:
            answer = await self._answer_question(image, question)
            descriptions.append(answer)

        return " ".join(descriptions)

    async def _answer_question(self, image: Image.Image, question: str) -> str:
        """Responder pergunta sobre a imagem (VQA)."""
        # BLIP suporta conditional generation com prompt
        prompt = f"Question: {question} Answer:"

        inputs = self.processor(image, text=prompt, return_tensors="pt")
        output = self.model.generate(**inputs, max_new_tokens=30)
        answer = self.processor.decode(output[0], skip_special_tokens=True)

        # Remover o prompt da resposta
        return answer.replace(prompt, "").strip()


    def _extract_tags(self, caption: str, description: str, context: str) -> str:
        """Extrair tags relevantes da descrição."""
        text = f"{caption} {description}".lower()

        # Tags base
        tags = ["imagem", "análise visual"]

        # Palavras-chave contextuais para saúde/emagrecimento
        health_keywords = {
            'food': 'alimentação',
            'meal': 'refeição',
            'plate': 'prato',
            'salad': 'salada',
            'fruit': 'fruta',
            'vegetable': 'vegetal',
            'exercise': 'exercício',
            'workout': 'treino',
            'gym': 'academia',
            'running': 'corrida',
            'walking': 'caminhada',
            'person': 'pessoa',
            'woman': 'mulher',
            'man': 'homem',
            'healthy': 'saudável',
            'diet': 'dieta',
            'nutrition': 'nutrição',
        }

        for keyword, tag in health_keywords.items():
            if keyword in text:
                tags.append(tag)

        # Adicionar contexto
        if context == "medical":
            tags.append("contexto médico")

        return ", ".join(tags[:10])  # Máximo 10 tags

    def analyze_image_sync(
        self,
        image_url: str,
        context: str = "medical"
    ) -> dict[str, str]:
        """
        Versão síncrona de analyze_image.

        Útil para jobs em background (RQ).
        """
        import asyncio

        try:
            loop = asyncio.get_event_loop()
        except RuntimeError:
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)

        return loop.run_until_complete(
            self.analyze_image(image_url, context)
        )
# Singleton para reutilizar modelo carregado
_vision_service_instance: VisionService | None = None
def get_vision_service() -> VisionService:
    """Obter instância singleton do VisionService."""
    global _vision_service_instance

    if _vision_service_instance is None:
        _vision_service_instance = VisionService()

    return _vision_service_instance
