#!/bin/bash
# Script para testar enriquecimento de mídia manualmente via API

API_BASE="http://localhost:3333/api/v1"

echo "=========================================="
echo "TESTE DE ENRIQUECIMENTO DE MÍDIA"
echo "=========================================="
echo ""

# 1. Login
echo "1. Fazendo login..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }')

echo "Login response: $LOGIN_RESPONSE"
echo ""

# 2. Criar mensagem de voz (Faster-Whisper)
echo "2. Criando mensagem de VOZ (transcrição automática)..."
VOICE_RESPONSE=$(curl -s -X POST "$API_BASE/messages" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "type": "voice",
    "file": {
      "url": "https://www2.cs.uic.edu/~i101/SoundFiles/BabyElephantWalk60.wav",
      "mimetype": "audio/wav",
      "filename": "teste_audio.wav"
    },
    "caption": "Teste de transcrição"
  }')

echo "Voice message response:"
echo "$VOICE_RESPONSE" | python3 -m json.tool
echo ""
echo "Transcription: $(echo $VOICE_RESPONSE | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("transcription", "N/A"))')"
echo ""

# 3. Criar mensagem de imagem (BLIP-2)
echo "3. Criando mensagem de IMAGEM (análise BLIP-2)..."
IMAGE_RESPONSE=$(curl -s -X POST "$API_BASE/messages" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "type": "image",
    "file": {
      "url": "https://images.unsplash.com/photo-1516549655169-df83a0774514?w=800",
      "mimetype": "image/jpeg",
      "filename": "clinica.jpg"
    },
    "caption": "Imagem da clínica"
  }')

echo "Image message response:"
echo "$IMAGE_RESPONSE" | python3 -m json.tool
echo ""
echo "Title: $(echo $IMAGE_RESPONSE | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("title", "N/A"))')"
echo "Description: $(echo $IMAGE_RESPONSE | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("description", "N/A"))')"
echo "Tags: $(echo $IMAGE_RESPONSE | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("tags", "N/A"))')"
echo ""

# 4. Criar mensagem de documento (metadata)
echo "4. Criando mensagem de DOCUMENTO (metadata extraction)..."
DOC_RESPONSE=$(curl -s -X POST "$API_BASE/messages" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "type": "document",
    "file": {
      "url": "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf",
      "mimetype": "application/pdf",
      "filename": "orientacoes_consulta.pdf"
    },
    "caption": "Documento de orientações"
  }')

echo "Document message response:"
echo "$DOC_RESPONSE" | python3 -m json.tool
echo ""
echo "Title: $(echo $DOC_RESPONSE | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("title", "N/A"))')"
echo "Tags: $(echo $DOC_RESPONSE | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("tags", "N/A"))')"
echo ""

echo "=========================================="
echo "TESTES CONCLUÍDOS"
echo "=========================================="
