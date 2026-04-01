"""Utilities for sanitizing outbound WhatsApp messages."""

import re


def enforce_whatsapp_style(text: str, max_paragraphs: int = 2) -> str:
    """
    Garante que a resposta tenha no máximo max_paragraphs parágrafos e remove racionalizações/metatextos.
    Aprimorado para capturar vazamentos de tags SPIN e notas técnicas do LLM.
    """
    # 1. Remover Tags de Cabeçalho (Fases SPIN e Racionalizações)
    header_patterns = [
        r"^\*?RACIONALIZAÇÃO DA RESPOSTA:?\*?\s*",
        r"^\*?Racionalização:?\s*",
        r"^\*?Rationalization:?\s*",
        r"^\*?Explicação:?\s*",
        r"^\*?Explanation:?\s*",
        r"^\*?RESPOSTA:?\s*",
        r"^\*?RESPONSE:?\s*",
        r"^\*?Resposta:?\s*",
        r"^\*?Response:?\s*",
        r"^\*\*RESPOSTA\*\*:?\s*",
        # Captura variações como **SITUATION**, **Situatção**, etc.
        r"^\*\*?(SITUATION|SITUA[TÇ]?[ÃA]O|PROBLEM|IMPLICATION|NEED[_ ]PAYOFF|SITUATION REVISITED|SPIN RESPONSE|RESULT|ANALYSIS)\*\*?:?\s*",
        r"^#+\s*(SITUATION|SITUA[TÇ]?[ÃA]O|PROBLEM|IMPLICATION|NEED[_ ]PAYOFF)\b\s*",
        r"^\*\s*(SITUATION|SITUA[TÇ]?[ÃA]O|PROBLEM|IMPLICATION|NEED[_ ]PAYOFF)\b\s*",
        r"^Phase:\s*(SITUATION|SITUA[TÇ]?[ÃA]O|PROBLEM|IMPLICATION|NEED[_ ]PAYOFF)\b\s*",
        # Captura tags brasileiras ou genéricas entre asteriscos
        r"^\*?(Questão natural|Questão|Pergunta|Análise|Fase).*?\*?\s*",
        r"^\*?Natural Response Following SPIN Methodology:?\*?\s*",
        r"^\*?Context Analysis:?\*?\s*",
    ]

    for pat in header_patterns:
        # re.MULTILINE permite que '^' combine com o início de cada linha dentro da string
        text = re.sub(pat, "", text, flags=re.IGNORECASE | re.MULTILINE).strip()

    # 2. Remover Notas e Metadados finais ou no meio (Trailers e Inline Tags)
    # Ex: "(Note: Response includes empathy...)", "(SITUATION)", "(OPEN QUESTION)"
    cleanup_patterns = [
        r"\s*\(Note:.*?\)\s*",
        r"\s*\(Nota:.*?\)\s*",
        r"\s*\(Obs:.*?\)\s*",
        r"\s*\(Observação:.*?\)\s*",
        r"\s*\((SITUATION|SITUA[TÇ]?[ÃA]O|PROBLEM|IMPLICATION|NEED[_ ]PAYOFF|OPEN QUESTION|SPIN)\)\s*",
        r"\s*Situat[çc]ão\*\*.*?\s*",  # Caso específico reportado
        r"\s*\*?Context Analysis:?\*?\s*",
        r"\s*\*?Natural Response Following SPIN Methodology:?\*?\s*",
    ]
    for pat in cleanup_patterns:
        # re.DOTALL caso a nota tenha quebras de linha
        text = re.sub(pat, " ", text, flags=re.IGNORECASE | re.DOTALL).strip()

    # 3. Remover variáveis de template vazadas
    template_tokens = [
        "{message}",
        "{context}",
        "{history}",
        "{patient_info}",
        "{user_message}",
        "{response}",
        "{intent}",
        "{spin_phase}",
        "{maturity_score}",
        "{lead_status}",
        "{last_interaction}",
        "{questions_asked}",
        "{conversation_summary}",
    ]
    for token in template_tokens:
        text = text.replace(token, "")

    # 4. Limpeza final de marcadores residuais
    text = text.strip()
    # Remove aspas duplas residuais no início/fim (LLMs as vezes citam a si mesmos)
    text = re.sub(r'^["\']|["\']$', "", text)
    # Remove qualquer início de linha que ainda tenha estrelas de negrito orfãs
    text = re.sub(r"^\*\*?\s*", "", text, flags=re.MULTILINE)

    # 5. Limita a quantidade de parágrafos
    paragraphs = [p.strip() for p in text.split("\n\n") if p.strip()]
    if len(paragraphs) > max_paragraphs:
        text = "\n\n".join(paragraphs[:max_paragraphs])

    return text.strip()
