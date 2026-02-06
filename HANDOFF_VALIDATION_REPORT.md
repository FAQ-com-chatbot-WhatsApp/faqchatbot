# Relatório de Validação: Sistema de Handoff
**Data**: 2026-02-05  
**Sessão**: Testes e otimizações do sistema de handoff

---

## 🎯 Objetivo

Validar funcionamento dos **handoff triggers** (transferência bot → humano) e otimizar **timings** para melhor UX.

---

## ✅ Descobertas Positivas

### 1. **Handoff Triggers - Funcionando Parcialmente**
- ✅ Palavra "parcelar" dispara handoff corretamente
- ✅ Logs de debug aparecem: `[ORCHESTRATOR]`, `[HANDOFF_CHECK]`, `[HANDOFF_TRIGGER]`
- ✅ Sistema de memory salva `handoff_reason: payment_question`
- ✅ Status da conversa muda para `PENDING_HANDOFF`

**Evidências**:
```
[16:45:53.267] INFO: [HANDOFF] Trigger activated: payment_question
[16:45:53.269] INFO: [HANDOFF_TRIGGER] Payment question detected
[16:45:53.283] INFO: Handoff triggered: conv=e37d3c4c..., reason=bot_confused, score=20
```

### 2. **Sistema de Logs Aprimorados**
- ✅ Adicionados logs em todos os pontos de decisão
- ✅ Visibilidade completa do fluxo de handoff
- ✅ Debug logs mostram keywords detectadas

---

## ❌ Problemas Identificados

### 1. **Keywords de Pagamento Insuficientes** (CRÍTICO)

**Problema**: Mensagens com números não eram detectadas.

**Casos que falharam**:
- ❌ "pode pagar em 3x?" → Não disparou (tinha "pagar em" mas não "3x")
- ❌ "posso pagar em 5 vezes?" → Não disparou (apesar de ter "vezes" e "pagar em")

**Causa Raiz**: Matching por substring não captura padrões com números.

**Solução Implementada**:
```python
# Adicionado regex patterns para detectar:
- r'\d+x'               # 3x, 5x, 10x
- r'\d+\s*vezes?'       # 3 vezes, 5vezes
- r'pagar\s+em\s+\d+'   # pagar em 3, pagar em 5
- r'dividir\s+em\s+\d+' # dividir em 3, dividir em 5
```

**Status**: ✅ CORRIGIDO

---

### 2. **Anti-ban Delays Muito Altos** (CRÍTICO)

**Problema**: Delays de 60-100 segundos deteriorando UX.

**Evidências**:
```
[16:45:53.321] INFO: Anti-ban delay: 71.1s for 162 chars
[16:59:37.809] INFO: Anti-ban delay: 103.3s for 522 chars
[17:04:19.918] INFO: Anti-ban delay: 84.9s for 276 chars
```

**Configurações Encontradas**:
- **Código** (settings.py): `MIN=3, MAX=8` ✅
- **.env**: `MIN=30, MAX=60` ❌ (sobrescrevendo código)
- **Containers**: Usando valores do `.env` antigo

**Causa Raiz**: `.env` com valores antigos sobrescrevendo defaults do código.

**Solução Implementada**:
```bash
# .env atualizado:
WAHA_MIN_DELAY_SECONDS=3
WAHA_MAX_DELAY_SECONDS=8
WAHA_MESSAGES_PER_HOUR=30
```

**Status**: ✅ CORRIGIDO (requer rebuild containers)

---

### 3. **Intent Detection Incorreto** (ALTO)

**Problema**: Keywords de agendamento não reconhecidas.

**Exemplo**:
```
Message: "tem disponibilidade essa semana?"
Expected: AGENDAMENTO
Actual: OUTRO
```

**Impacto**: Handoff de agendamento não funciona (lógica requer `intent == "AGENDAMENTO" OR keywords`).

**Solução Sugerida**: Revisar prompt de intent detection ou ajustar lógica de handoff.

**Status**: ❌ PENDENTE

---

### 4. **Message Debounce Agressivo** (MÉDIO)

**Problema**: 13 mensagens → 3 jobs → 1 resposta (debounce de 5s).

**Causa**: Janela muito larga agrupava mensagens distintas.

**Solução Implementada**:
```python
MESSAGE_DEBOUNCE_SECONDS: 5 → 2
```

**Status**: ✅ CORRIGIDO (requer rebuild)

---

## 📊 Testes Realizados

### Teste 1: Keyword "parcelar"
```bash
curl POST "pode parcelar?"
Result: ✅ Handoff disparado
Delay: 71s (config antiga)
```

### Teste 2: Keyword "cartao"
```bash
curl POST "posso pagar com cartao?"
Status: PENDING_HANDOFF (teste anterior)
```

### Teste 3: Pattern "3x"
```bash
curl POST "pode pagar em 3x?"
Result: ❌ Não disparou (ANTES da correção)
```

### Teste 4: Pattern "vezes"
```bash
curl POST "posso pagar em 5 vezes?"
Result: ❌ Não disparou (ANTES da correção)
Delay: 84.9s (config antiga)
```

---

## 🔧 Correções Implementadas

### 1. **Payment Keywords - Regex Patterns**
**Arquivo**: `conversation_orchestrator.py`  
**Mudança**: Adicionado matching com regex para padrões numéricos
```python
payment_patterns = [
    r'\d+x',
    r'\d+\s*vezes?',
    r'pagar\s+em\s+\d+',
    r'dividir\s+em\s+\d+',
]
```

### 2. **Anti-ban Delays - .env**
**Arquivo**: `.env`  
**Mudança**: Reduzido delays para melhor UX
```bash
WAHA_MIN_DELAY_SECONDS=3   # was 30
WAHA_MAX_DELAY_SECONDS=8   # was 60
WAHA_MESSAGES_PER_HOUR=30  # was 20
```

### 3. **Message Debounce**
**Arquivo**: `settings.py`  
**Mudança**: Reduzido janela de agrupamento
```python
MESSAGE_DEBOUNCE_SECONDS: 2  # was 5
```

### 4. **Enhanced Logging**
**Arquivo**: `conversation_orchestrator.py`  
**Mudança**: Logs em todos os pontos de decisão
```python
logger.debug("[HANDOFF_CHECK] Payment keywords found: %s, patterns: %s", ...)
```

---

## 🚀 Próximos Passos

### Alta Prioridade

1. **Rebuild Containers Completo**
   ```bash
   cd back/
   docker-compose down
   docker-compose build --no-cache
   docker-compose up -d
   ```
   - Garante que `.env` atualizado seja carregado
   - Aplica timings otimizados (3-8s)

2. **Testar Handoffs com Novos Patterns**
   ```bash
   # Test 1: Pattern com número
   curl POST "pode pagar em 3x?"
   # Expected: Handoff dispara
   
   # Test 2: Pattern com "vezes"
   curl POST "posso dividir em 5 vezes?"
   # Expected: Handoff dispara
   ```

3. **Validar Delays Corretos**
   ```bash
   docker logs tic-worker-1 | grep "Anti-ban delay"
   # Expected: 3-8 segundos (não 60-100s)
   ```

### Média Prioridade

4. **Corrigir Intent Detection**
   - Revisar prompt em `prompts.yaml`
   - Adicionar exemplos explícitos: "disponibilidade" → AGENDAMENTO
   - Testar com mensagens reais

5. **Implementar Questions Tracking**
   - Adicionar calls para `add_question()` após LLM gerar perguntas
   - Validar que Redis armazena questions corretamente

6. **Expandir Fact Extraction**
   - Extrair: `has_done_procedure_before`, `preferred_schedule`
   - Salvar em memory persistente

### Baixa Prioridade

7. **Performance Testing**
   - Teste com 50+ mensagens
   - Medir latência do ChromaDB
   - Ajustar limites se necessário

8. **Production Optimization**
   - Reduzir log verbosity (DEBUG→INFO)
   - Disable verbose LLM logs
   - Configurar `VERBOSE_LLM_LOGS=False`

---

## 📈 Métricas de Sucesso

### Antes das Otimizações
- ❌ Handoff: 1/4 casos funcionando (25%)
- ❌ Delays: 60-100 segundos
- ❌ Debounce: 13 msgs → 1 resposta

### Após Otimizações (Esperado)
- ✅ Handoff: 4/4 casos funcionando (100%)
- ✅ Delays: 3-8 segundos
- ✅ Debounce: Mensagens individuais processadas

---

## 🔍 Logs de Referência

### Handoff Bem-Sucedido
```
[timestamp] INFO: [ORCHESTRATOR] About to check handoff triggers: intent=OUTRO, score=20, msg='pode parcelar?'
[timestamp] DEBUG: [HANDOFF_CHECK] Checking triggers: intent=OUTRO, score=20, message='pode parcelar?'
[timestamp] DEBUG: [HANDOFF_CHECK] Payment keywords found: ['parcelar'], patterns: []
[timestamp] INFO: [HANDOFF_TRIGGER] Payment question detected
[timestamp] INFO: Handoff triggered: conv=xxx, reason=bot_confused, score=20
[timestamp] INFO: Automatic handoff triggered: conv=xxx, reason=bot_confused, score=20
```

### Anti-ban Delay (Antigo - Ruim)
```
[16:45:53.321] INFO: Anti-ban delay: 71.1s for 162 chars
[16:59:37.809] INFO: Anti-ban delay: 103.3s for 522 chars
```

### Anti-ban Delay (Esperado - Bom)
```
[timestamp] INFO: Anti-ban delay: 4.2s for 162 chars
[timestamp] INFO: Anti-ban delay: 7.8s for 522 chars
```

---

## 🎓 Lições Aprendidas

1. **Environment Variables Override Code Defaults**
   - `.env` sempre sobrescreve `Field(default=X)`
   - Rebuild containers necessário após mudanças no `.env`

2. **Pattern Matching > Simple Substring**
   - Keywords simples não capturam variações
   - Regex patterns essenciais para robustez

3. **Logging is Critical**
   - Debug logs salvaram tempo massivo
   - Visibilidade em cada ponto de decisão crucial

4. **Test With Real Messages**
   - Casos reais ("3x", "vezes") expõem falhas
   - Testes simulados insuficientes

---

## ✅ Conclusão

Sistema de handoff **funciona** mas requer:
1. ✅ Rebuild containers (aplicar .env)
2. ✅ Keywords expandidas (regex patterns)
3. ❌ Intent detection fix (pending)

**Próxima Ação**: Rebuild + teste completo com delays otimizados.
