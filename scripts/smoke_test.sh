#!/usr/bin/env bash
set -euo pipefail

BASE_URL="http://localhost:8080/api"
USERNAME="lmswill"
PASSWORD="admin123"

echo "1) Login"
TOKEN_JSON=$(curl -s -X POST "$BASE_URL/auth/login" -H "Content-Type: application/json" -d '{"username":"'$USERNAME'","password":"'$PASSWORD'"}')
TOKEN=$(echo "$TOKEN_JSON" | sed -n 's/.*"access_token"\s*:\s*"\([^"]*\)".*/\1/p')
if [ -z "$TOKEN" ]; then
	echo "ERROR: failed to obtain token" >&2
	echo "$TOKEN_JSON" >&2
	exit 2
fi
echo "  token length: ${#TOKEN}"

echo "2) GET /flows"
curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/flows?page=1&per_page=5" | jq '.' || true

echo "3) Create contact (smoke)"
CREATE_CONTACT_RESP=$(curl -s -X POST "$BASE_URL/contacts" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"name":"Smoke Contact","phone":"+5511999000333"}')
echo "$CREATE_CONTACT_RESP" | jq '.' || true
CONTACT_ID=$(echo "$CREATE_CONTACT_RESP" | sed -n 's/.*"contact_id"\s*:\s*"\([^"]*\)".*/\1/p')

echo "4) Create conversation"
CONV_RESP=$(curl -s -X POST "$BASE_URL/conversations" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"contact_phone":"+5511999000333","contact_name":"Smoke Contact","flow_id":"6e253d66-5bcb-4f2b-93c3-618ef7d81477"}')
echo "$CONV_RESP" | jq '.' || true
CONV_ID=$(echo "$CONV_RESP" | sed -n 's/.*"conversation_id"\s*:\s*"\([^"]*\)".*/\1/p')

echo "5) Post message"
MSG_RESP=$(curl -s -X POST "$BASE_URL/messages" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"conversation_id":"'"$CONV_ID"'","sender":"user","content":"Teste smoke"}')
echo "$MSG_RESP" | jq '.' || true

echo "6) Next step"
curl -s -X POST "$BASE_URL/conversations/$CONV_ID/next" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{}' | jq '.' || true

echo "7) Delete contact"
curl -s -X DELETE "$BASE_URL/contacts/$CONTACT_ID" -H "Authorization: Bearer $TOKEN" -i || true

echo "Smoke test completed"

exit 0