#!/usr/bin/env python
"""
Script de teste do sistema de fallback de modelos Gemini.
Testa se o cliente consegue alternar entre modelos quando um atinge quota.
"""

import sys
sys.path.insert(0, 'src')

from dotenv import load_dotenv
load_dotenv()

from robbot.adapters.external.gemini_client import GeminiClient, FALLBACK_MODELS
import logging

# Configurar logging para ver os detalhes
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

def test_fallback():
    """Testa sistema de fallback"""
    print("\n=== TESTE DO SISTEMA DE FALLBACK GEMINI ===\n")
    
    print(f"Modelos configurados para fallback:")
    for i, model in enumerate(FALLBACK_MODELS, 1):
        print(f"  {i}. {model}")
    
    print("\n--- Inicializando GeminiClient ---")
    client = GeminiClient()
    
    print(f"\nModelo primário: {client.primary_model}")
    print(f"Modelo atual: {client.current_model}")
    
    print("\n--- Testando geração de resposta ---")
    try:
        response = client.generate_response(
            prompt="Responda apenas 'OK' se você está funcionando.",
            context=None
        )
        
        print(f"\n✅ SUCESSO!")
        print(f"   Modelo usado: {response['model']}")
        print(f"   Latência: {response['latency_ms']}ms")
        print(f"   Resposta: {response['response'][:100]}")
        
    except Exception as e:
        print(f"\n❌ ERRO: {e}")
        print(f"   Tipo: {type(e).__name__}")

if __name__ == "__main__":
    test_fallback()
