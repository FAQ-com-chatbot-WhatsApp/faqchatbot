# GAP ANALYSIS - Clinica Go WhatsApp Bot
**Data:** 2026-02-05  
**Status:** Sistema funcional mas com gaps identificados

---

## 🔴 GAPS CRÍTICOS

### 1. **Debounce Agrupando DEMAIS Mensagens**
**Severidade:** 🔴 ALTA  
**Status:** ⚠️ Comportamento inesperado

**Problema:**
- Configurado: `MESSAGE_DEBOUNCE_SECONDS = 5`
- Comportamento real: Agrupa 5+ mensagens numa única batch
- Resultado: 13 mensagens enviadas → Apenas 3 jobs processados → 1 resposta enviada

**Evidência:**
```
[16:25:11] INFO: process_debounced_message(chat_id='555198098876@c.us')
CLIENT MESSAGE: "oi\nvi a clinica pelo instagram\nme chamo karol\nquero saber sobre terapia hormonal\nme senti mal com os efeitos"
```

**Impacto:**
- ❌ Bot não responde a todas mensagens individualmente
- ❌ Conversa parece "travada" para usuário
- ❌ Experiência ruim (usuário manda 10 mensagens, bot responde 1 vez)

**Causa Raiz:**
O algoritmo de debounce está aguardando 5 segundos APÓS cada mensagem antes de processar. Se múltiplas mensagens chegam em sequência rápida (< 5s intervalo), todas são agrupadas.

**Solução Proposta:**
1. **Opção A (Recomendada):** Reduzir `MESSAGE_DEBOUNCE_SECONDS` para 2 segundos
2. **Opção B:** Implementar "max messages per batch" (limite 3 mensagens por grupo)
3. **Opção C:** Desabilitar debounce exceto para mensagens idênticas (dedup)

**Prioridade:** 🔴 URGENTE - Afeta core UX

---

### 2. **Anti-Ban Delays Muito Longos**
**Severidade:** 🟠 MÉDIA-ALTA  
**Status:** ⚠️ Degradando UX

**Problema:**
- Delays configurados: 30-60 segundos + variável baseado em tamanho da mensagem
- Exemplo real: 62-74 segundos de delay antes de enviar resposta
- Total: Até 1-2 minutos entre usuário enviar mensagem e receber resposta

**Evidência:**
```
[16:22:15] INFO: Anti-ban delay: 74.0s for 280 chars
[16:24:06] INFO: Anti-ban delay: 62.8s for 243 chars
```

**Impacto:**
- ❌ Usuário acha que bot está quebrado
- ❌ Usuário desiste de esperar (abandono da conversa)
- ❌ Experiência muito inferior a atendimento humano

**Considerações:**
- ✅ WhatsApp bane por velocidade excessiva (30+ msgs/hora)
- ⚠️ Mas 1-2min de delay por mensagem é EXCESSIVO
- 💡 Instagram/Messenger bots respondem em 2-5 segundos

**Solução Proposta:**
1. **Produção:** Reduzir delays para 5-15 segundos (ainda seguro)
2. **Dev/Testing:** Desabilitar delays completamente (`WAHA_ANTI_BAN_ENABLED=False`)
3. **Híbrido:** 5s delay fixo + variável 0.1s por caractere (max 15s)

**Cálculo de Segurança:**
- Response médio: 250 chars = 5s + (250 * 0.1s) = 5s + 25s = 30s ❌ Ainda muito
- Response médio: 250 chars = 5s + (250 * 0.02s) = 5s + 5s = 10s ✅ Razoável

**Prioridade:** 🟠 ALTA - Impacta conversão e satisfação

---

### 3. **LID Resolver Não Funciona**
**Severidade:** 🟡 BAIXA (conhecido)  
**Status:** 🐛 Bug documentado

**Problema:**
WhatsApp WAHA não consegue salvar contato quando chat_id é LID (Local Identity Directory) ao invés de phone number real.

**Erro:**
```
[LID RESOLVER] Failed to save contact 555198098876: WAHA API error: 500
Cannot read properties of undefined (reading 'toString')
```

**Impacto:**
- ❌ Contato não é salvo no WhatsApp da empresa
- ✅ Nome salvo corretamente no PostgreSQL
- ✅ Sistema continua funcionando

**Workarounds Atuais:**
1. Salvar manualmente contatos importantes no WhatsApp Business
2. Usar nome do PostgreSQL como fonte de verdade

**Solução Long-term:**
- Integrar com WhatsApp Business API (ao invés de WAHA)
- OU: Implementar resolução LID → phone via serviço externo
- OU: Aceitar limitação e documentar

**Prioridade:** 🟢 BAIXA - Não impede operação

---

## 🟡 GAPS MÉDIOS

### 4. **Handoff Triggers Não Validados**
**Severidade:** 🟡 MÉDIA  
**Status:** ⏳ Aguardando validação completa

**Problema:**
- Código implementado ✅
- Keywords configurados ✅
- Mensagens de teste enviadas ✅
- **MAS:** Nenhum log de `[HANDOFF_TRIGGER]` encontrado

**Possíveis Causas:**
1. Mensagens ainda não processadas (anti-ban delay)
2. Debounce agrupou e descartou
3. Keywords detection não matching (case-sensitive? encoding?)
4. Handoff logic não sendo chamado

**Próximos Passos:**
1. ⏳ Aguardar mais 5-10 min para processar
2. 🔍 Buscar logs com `grep -i "disponibilidade\|parcelar"`
3. 🧪 Enviar mensagem individual: "tem disponibilidade hoje?"
4. 📊 Adicionar logs debug em `_check_handoff_triggers`

**Prioridade:** 🟡 MÉDIA - Core feature mas ainda em teste

---

### 5. **Persistent Memory Incompleta**
**Severidade:** 🟡 MÉDIA  
**Status:** ✅ Funciona mas limitado

**O que funciona:**
- ✅ `patient_name` salvo e recuperado
- ✅ Facts persistidos por 7 dias (TTL)
- ✅ Consulta antes de gerar respostas

**O que falta:**
- ❌ `has_done_procedure_before: bool` - NÃO sendo salvo
- ❌ `preferred_schedule: string` - NÃO implementado
- ❌ `pain_points: list[]` - NÃO implementado
- ❌ `objections: list[]` - NÃO implementado
- ❌ `questions_asked: set[]` - Redis KEYS vazio (não salvando)

**Evidência:**
```bash
$ docker exec redis redis-cli KEYS "questions:*"
(empty array)

$ docker exec redis redis-cli KEYS "facts:*"
facts:e37d3c4c-052b-4f7e-b85b-6e37c748c146

$ docker exec redis redis-cli HGETALL facts:e37d3c4c-052b-4f7e-b85b-6e37c748c146
patient_name
"Karol"
```

**Impacto:**
- ⚠️ Bot AINDA pode repetir perguntas
- ⚠️ Não lembra se paciente já fez procedimento
- ⚠️ Não salva preferências de horário

**Solução:**
1. Implementar `add_question()` sendo chamado após cada pergunta gerada
2. Implementar extração estruturada de facts do LLM
3. Adicionar fact extraction no orchestrator após response generation

**Prioridade:** 🟡 MÉDIA - Afeta qualidade mas não quebra sistema

---

### 6. **Context Builder Não Testado com Alto Volume**
**Severidade:** 🟡 BAIXA-MÉDIA  
**Status:** ⚠️ Validação parcial

**Configuração Atual:**
- Limit: 10 docs
- Max chars: 5000
- Método: Truncate from beginning

**Não testado:**
- 📊 Performance com 50+ mensagens na conversa
- 📊 ChromaDB query latency em escala
- 📊 Relevância dos documentos recuperados
- 📊 Overlap/redundância entre docs

**Risco:**
- 🚨 Após 100+ mensagens, queries podem ficar lentas (> 2s)
- 🚨 5000 chars podem não ser suficientes para conversas longas
- 🚨 Truncar do início pode perder contexto importante

**Próximos Testes:**
1. Simular conversa com 50 mensagens
2. Medir latency de retrievals
3. Ajustar limits se necessário

**Prioridade:** 🟡 BAIXA - Edge case, não afeta MVP

---

## 🟢 GAPS BAIXOS (Nice-to-Have)

### 7. **Logs Excessivos em Produção**
**Severidade:** 🟢 BAIXA  
**Status:** 🔧 Otimização

**Problema:**
- Logs DEBUG incluem prompts completos (1000+ linhas)
- Volume: ~50KB por mensagem processada
- Logs incluem JSON completo do LLM request

**Impacto:**
- 💾 Disco usage alto em produção
- 🔍 Difícil encontrar logs importantes (needle in haystack)
- 💰 Custo storage se escala muito

**Solução:**
1. Produção: Log level INFO ou WARNING
2. Remover logs: `Request options: {'method': 'post'...`
3. Adicionar toggle: `VERBOSE_LLM_LOGS=False` em produção

**Prioridade:** 🟢 BAIXA - Não afeta funcionalidade

---

### 8. **UTF-8 Parsing em Scripts Bash**
**Severidade:** 🟢 BAIXA  
**Status:** 🐛 Bug menor

**Problema:**
Mensagem "já fiz antes mas não deu certo" → erro parsing JSON devido a "ã"

**Solução:**
Usar Python requests ao invés de bash curl para testes

**Prioridade:** 🟢 BAIXA - Apenas afeta scripts de teste

---

### 9. **Métricas e Observability**
**Severidade:** 🟢 BAIXA (mas importante long-term)  
**Status:** 🚧 Não implementado

**Faltando:**
- 📊 Dashboard de handoffs (quantos/dia, razões)
- 📊 Taxa de repetição de perguntas
- 📊 Tempo médio de resposta
- 📊 Taxa de conversão (lead → scheduling)
- 📊 Satisfação do usuário (CSAT)

**Implementação Futura:**
1. Prometheus + Grafana
2. Custom metrics no código
3. A/B testing framework

**Prioridade:** 🟢 BAIXA - MVP não precisa

---

## 📋 RESUMO EXECUTIVO

### Por Severidade
| Severidade | Qtd | Descrição |
|------------|-----|-----------|
| 🔴 CRÍTICO | 2 | Debounce excessivo, Anti-ban delays |
| 🟡 MÉDIO | 4 | Handoff não validado, Memory incompleta, Context não testado, LID bug |
| 🟢 BAIXO | 3 | Logs verbosos, UTF-8 parsing, Métricas |

### Por Prioridade de Fix
1. 🔴 **URGENTE:** Ajustar debounce (2s) e anti-ban delays (5-15s)
2. 🟡 **ALTA:** Validar handoff triggers completamente
3. 🟡 **MÉDIA:** Implementar fact extraction completo
4. 🟢 **BAIXA:** Otimizar logs, adicionar métricas

---

## 🎯 PLANO DE AÇÃO RECOMENDADO

### Sprint 1 (1-2 dias) - CRÍTICO
- [ ] **Ajustar MESSAGE_DEBOUNCE_SECONDS para 2**
- [ ] **Reduzir anti-ban delays: 5s base + 0.02s/char (max 15s)**
- [ ] **Testar com 20 mensagens rápidas**
- [ ] **Validar handoff com mensagens individuais**

### Sprint 2 (3-5 dias) - ALTA
- [ ] Implementar `add_question()` calls no orchestrator
- [ ] Adicionar fact extraction estruturado
- [ ] Logs debug em handoff triggers
- [ ] Teste E2E completo com 50+ mensagens

### Sprint 3 (1-2 semanas) - MÉDIA
- [ ] Refatorar orchestrator (SOLID principles)
- [ ] Implementar max_messages_per_batch
- [ ] Resolver LID bug (ou documentar workaround)
- [ ] Performance test ChromaDB

### Sprint 4 (1+ mês) - BAIXO
- [ ] Implementar métricas e dashboard
- [ ] Reduzir log verbosity em produção
- [ ] A/B testing framework
- [ ] Fine-tune LLM com conversas reais

---

## 💡 DECISÕES ARQUITETURAIS QUESTIONÁVEIS

### 1. Anti-Ban Delays Síncronos
**Problema:** Worker bloqueia por 60-74s antes de enviar resposta  
**Alternativa:** Delay assíncrono em background task separado  
**Trade-off:** Mais complexidade vs melhor UX

### 2. Debounce Ilimitado
**Problema:** Agrupa quantas mensagens chegarem em 5s  
**Alternativa:** Limit batch size (max 3 messages)  
**Trade-off:** Mais processamento vs melhor responsividade

### 3. LLM Prompt Gigante
**Problema:** Prompt template tem 5000+ tokens  
**Alternativa:** Modular prompts, carregar apenas o necessário  
**Trade-off:** Tokens desperdiçados vs simplicidade de manutenção

---

**Documento Gerado em:** 2026-02-05 19:40 UTC  
**Próxima Revisão:** Após implementar fixes críticos
