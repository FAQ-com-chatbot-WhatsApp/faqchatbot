# Script de demonstração - Queries formatadas para apresentação (PowerShell)
# Uso: .\demo-queries.ps1

Write-Host "🎯 QUERIES DE DEMONSTRAÇÃO - BOT WHATSAPP" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Última conversa completa (formatada)
Write-Host "📱 1. ÚLTIMA CONVERSA COMPLETA" -ForegroundColor Yellow
Write-Host "------------------------------" -ForegroundColor Yellow
docker-compose exec -T db psql -U dba -d BotDB -c @"
SELECT 
  ROW_NUMBER() OVER (ORDER BY created_at) as ""#"",
  CASE direction 
    WHEN 'INBOUND' THEN '>> Cliente'
    WHEN 'OUTBOUND' THEN '<< Bot'
  END as ""Origem"",
  SUBSTRING(REPLACE(body, E'\n', ' '), 1, 100) || 
    CASE WHEN LENGTH(body) > 100 THEN '...' ELSE '' END as ""Mensagem"",
  TO_CHAR(created_at, 'HH24:MI:SS') as ""Hora""
FROM conversation_messages 
WHERE conversation_id = (
  SELECT id FROM conversations ORDER BY updated_at DESC LIMIT 1
)
ORDER BY created_at;
"@
Write-Host ""
Read-Host "Pressione ENTER para continuar"

# 2. Estatísticas das últimas 24h
Write-Host "`n📊 2. ESTATÍSTICAS - ÚLTIMAS 24H" -ForegroundColor Yellow
Write-Host "--------------------------------" -ForegroundColor Yellow
docker-compose exec -T db psql -U dba -d BotDB -c @"
SELECT 
  COUNT(*) FILTER (WHERE direction = 'INBOUND') as ""Msgs Recebidas"",
  COUNT(*) FILTER (WHERE direction = 'OUTBOUND') as ""Msgs Enviadas"",
  COUNT(DISTINCT conversation_id) as ""Conversas Ativas"",
  TO_CHAR(MIN(created_at), 'HH24:MI') as ""Primeira"",
  TO_CHAR(MAX(created_at), 'HH24:MI') as ""Última""
FROM conversation_messages
WHERE created_at > NOW() - INTERVAL '24 hours';
"@
Write-Host ""
Read-Host "Pressione ENTER para continuar"

# 3. Últimas 5 mensagens (todas conversas)
Write-Host "`n💬 3. ÚLTIMAS 5 MENSAGENS (TODAS CONVERSAS)" -ForegroundColor Yellow
Write-Host "-------------------------------------------" -ForegroundColor Yellow
docker-compose exec -T db psql -U dba -d BotDB -c @"
SELECT 
  CASE direction 
    WHEN 'INBOUND' THEN '>> Cliente'
    WHEN 'OUTBOUND' THEN '<< Bot'
  END as ""Dir"",
  from_phone as ""Telefone"",
  SUBSTRING(REPLACE(body, E'\n', ' '), 1, 60) || '...' as ""Mensagem"",
  TO_CHAR(created_at, 'DD/MM HH24:MI') as ""Quando""
FROM conversation_messages 
ORDER BY created_at DESC 
LIMIT 5;
"@
Write-Host ""
Read-Host "Pressione ENTER para continuar"

# 4. Leads criados hoje
Write-Host "`n👥 4. LEADS CRIADOS HOJE" -ForegroundColor Yellow
Write-Host "------------------------" -ForegroundColor Yellow
docker-compose exec -T db psql -U dba -d BotDB -c @"
SELECT 
  name as ""Nome"",
  phone_number as ""Telefone"",
  status as ""Status"",
  maturity_score as ""Score"",
  TO_CHAR(created_at, 'HH24:MI') as ""Criado""
FROM leads
WHERE created_at::date = CURRENT_DATE
ORDER BY created_at DESC;
"@
Write-Host ""
Read-Host "Pressione ENTER para continuar"

# 5. Fila de jobs RQ
Write-Host "`n⚙️  5. FILA DE PROCESSAMENTO (RQ)" -ForegroundColor Yellow
Write-Host "---------------------------------" -ForegroundColor Yellow
Write-Host "Mensagens na fila:"
docker-compose exec -T redis redis-cli LLEN rq:queue:messages
Write-Host "`nJobs AI na fila:"
docker-compose exec -T redis redis-cli LLEN rq:queue:ai
Write-Host "`nEscalações na fila:"
docker-compose exec -T redis redis-cli LLEN rq:queue:escalation
Write-Host ""
Read-Host "Pressione ENTER para continuar"

# 6. Conversas por fase SPIN
Write-Host "`n📈 6. MATURIDADE - DISTRIBUIÇÃO SPIN" -ForegroundColor Yellow
Write-Host "------------------------------------" -ForegroundColor Yellow
docker-compose exec -T db psql -U dba -d BotDB -c @"
SELECT 
  CASE 
    WHEN maturity_score < 30 THEN 'SITUATION (0-29)'
    WHEN maturity_score < 50 THEN 'PROBLEM (30-49)'
    WHEN maturity_score < 75 THEN 'IMPLICATION (50-74)'
    WHEN maturity_score < 85 THEN 'NEED-PAYOFF (75-84)'
    ELSE 'READY (85-100)'
  END as ""Fase SPIN"",
  COUNT(*) as ""Quantidade"",
  ROUND(AVG(maturity_score), 1) as ""Score Médio""
FROM leads
WHERE created_at > NOW() - INTERVAL '7 days'
GROUP BY 
  CASE 
    WHEN maturity_score < 30 THEN 1
    WHEN maturity_score < 50 THEN 2
    WHEN maturity_score < 75 THEN 3
    WHEN maturity_score < 85 THEN 4
    ELSE 5
  END
ORDER BY 1;
"@

Write-Host "`n✅ DEMONSTRAÇÃO CONCLUÍDA!" -ForegroundColor Green
Write-Host ""
