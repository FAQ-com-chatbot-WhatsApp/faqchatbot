---
status: planned
generated: 2026-01-27
title: "Reescrita do Documento de Casos de Teste e Validação"
summary: "Reescrever e atualizar o documento casos-testes-validacao.md como fonte única de verdade para validação do projeto"
agents:
  - type: "test-writer"
    role: "Definir estrutura de casos de teste e cenários de validação"
  - type: "documentation-writer"
    role: "Criar documentação clara, padronizada e completa"
  - type: "backend-specialist"
    role: "Validar endpoints e fluxos da API"
  - type: "security-auditor"
    role: "Revisar cenários de segurança e autenticação"
docs:
  - "testing-strategy.md"
  - "architecture.md"
  - "glossary.md"
phases:
  - id: "P"
    name: "Plan - Análise e Planejamento"
    prevc: "P"
  - id: "R"
    name: "Review - Revisão de Estrutura"
    prevc: "R"
  - id: "E"
    name: "Execute - Reescrita do Documento"
    prevc: "E"
  - id: "V"
    name: "Verify - Validação e Qualidade"
    prevc: "V"
---

# Plano: Reescrita do Documento de Casos de Teste e Validação

## 1. Visão Geral e Contexto

### Objetivo
Reescrever completamente o documento `casos-testes-validacao.md` para servir como **fonte única de verdade** para validação do sistema Clinica GO WhatsApp Bot, atendendo aos seguintes propósitos:

1. **Referência para testes manuais** (Postman, Swagger UI)
2. **Base para testes automatizados** (Pytest)
3. **Instrumento de verificação** do comportamento esperado do sistema

### Análise do Documento Atual

**Pontos Positivos:**
- Ampla cobertura (120 casos de teste, 161 endpoints)
- Organização por fases lógicas (18 fases)
- Exemplos de payloads JSON e respostas esperadas
- Validações claras por caso de teste

**Pontos a Melhorar:**
- Inconsistência de idioma (português/inglês misturado)
- Falta de cenários de erro sistematizados
- Ausência de pré-condições detalhadas
- Casos de borda não exaustivamente cobertos
- Formatação não padronizada entre seções
- Scripts de teste inline que devem ser removidos
- Falta de rastreabilidade entre casos de teste e requisitos

### Escopo da Melhoria

| Aspecto | Estado Atual | Estado Desejado |
|---------|--------------|-----------------|
| Total de Casos | 120 | ~200+ (incluindo cenários de erro) |
| Cobertura de Endpoints | 161 endpoints | 100% com fluxos felizes + erros |
| Idioma | Misto PT/EN | Português (PT-BR) consistente |
| Cenários de Erro | Parcial | Completo por endpoint |
| Estrutura | Variável | Padronizada (template único) |
| Scripts/Comandos | Presentes | Removidos |

---

## 2. Inventário de Endpoints e Funcionalidades

### Módulos Identificados

| Módulo | Controller | Endpoints | Status |
|--------|------------|-----------|--------|
| **Health** | health_controller.py | 1 | ✅ Coberto |
| **Auth** | auth_controller.py | 15+ | ✅ Parcial |
| **Users** | user_controller.py | 5+ | ✅ Parcial |
| **Leads** | lead_controller.py | 10+ | ✅ Coberto |
| **Conversations** | conversation_controller.py | 12+ | ✅ Coberto |
| **Messages** | message_controller.py | 5+ | ✅ Coberto |
| **Playbooks** | playbook_controller.py | 6+ | ✅ Coberto |
| **Topics** | topic_controller.py | 4+ | ✅ Coberto |
| **Tags** | tag_controller.py | 4+ | ✅ Coberto |
| **Webhooks** | webhook_controller.py | 2+ | ✅ Coberto |
| **WAHA** | waha_controller.py | 44+ | ✅ Coberto |
| **Dashboard/Metrics** | dashboard_controller.py | 21+ | ✅ Coberto |
| **Notifications** | notification_controller.py | 4+ | ✅ Coberto |
| **Queues** | queue_controller.py | 3+ | ✅ Coberto |
| **Jobs** | job_controller.py | 3+ | ✅ Coberto |
| **AI** | ai_controller.py | 3+ | ✅ Coberto |
| **Audit** | audit_controller.py | 2+ | ✅ Coberto |
| **Handoff** | handoff_controller.py | 5+ | ✅ Coberto |

---

## 3. Estrutura Padronizada de Caso de Teste

### Template para Cada Caso de Teste

```markdown
### CT-XXX: [Título Descritivo do Caso]

**Endpoint**: [MÉTODO] /api/v1/[rota]  
**Módulo**: [Nome do Módulo]  
**Prioridade**: Alta | Média | Baixa  
**Tipo**: Funcional | Segurança | Performance | Integração  

#### Objetivo
[Descrição clara do que está sendo validado]

#### Pré-condições
1. [Condição 1 - ex: Usuário autenticado com role ADMIN]
2. [Condição 2 - ex: Sessão WAHA ativa]
3. [Condição N]

#### Dados de Entrada

**Headers**:
```
Authorization: Bearer {{auth_token}}
Content-Type: application/json
```

**Path Parameters**:
| Parâmetro | Tipo | Obrigatório | Descrição | Exemplo |
|-----------|------|-------------|-----------|---------|
| id | UUID | Sim | ID do recurso | uuid-123 |

**Query Parameters**:
| Parâmetro | Tipo | Obrigatório | Descrição | Exemplo |
|-----------|------|-------------|-----------|---------|
| limit | int | Não | Limite de resultados | 20 |

**Body (Payload)**:
```json
{
  "campo": "valor"
}
```

#### Resultado Esperado

**Status Code**: 200 | 201 | 204 | 4xx | 5xx

**Response Body**:
```json
{
  "campo": "valor esperado"
}
```

#### Validações
- [ ] Status code correto
- [ ] Campos obrigatórios presentes
- [ ] Formato de dados correto
- [ ] Regras de negócio aplicadas
- [ ] Efeitos colaterais verificados

#### Cenários de Erro

| Cenário | Input | Status | Mensagem |
|---------|-------|--------|----------|
| Token inválido | Bearer invalid | 401 | "Invalid token" |
| Recurso não encontrado | id=uuid-inexistente | 404 | "Resource not found" |
| Dados inválidos | body vazio | 422 | "Validation error" |

#### Notas Adicionais
[Observações relevantes, dependências, comportamentos especiais]
```

---

## 4. Organização do Documento Final

### Estrutura Proposta

```
# PLANO DE TESTES - Clinica GO WhatsApp Bot

## 1. Visão Geral
   - 1.1 Objetivo do Documento
   - 1.2 Escopo de Testes
   - 1.3 Ferramentas Utilizadas
   - 1.4 Glossário de Termos

## 2. Premissas e Dependências
   - 2.1 Ambiente de Testes
   - 2.2 Dados de Teste
   - 2.3 Configurações Necessárias

## 3. Fluxo Geral da Aplicação
   - 3.1 Diagrama de Fluxo Principal
   - 3.2 Estados de Conversa
   - 3.3 Estados de Lead
   - 3.4 Fluxo de Handoff

## 4. Casos de Teste por Módulo
   - 4.1 Infraestrutura (Health Check)
   - 4.2 Autenticação e Autorização
   - 4.3 Gestão de Usuários
   - 4.4 Integrações WAHA (WhatsApp)
   - 4.5 Playbooks e Steps
   - 4.6 Tópicos
   - 4.7 Mensagens e Mídia
   - 4.8 Conversas
   - 4.9 Leads e Interações
   - 4.10 Tags
   - 4.11 Webhooks
   - 4.12 Gemini AI
   - 4.13 Notificações
   - 4.14 Handoff (Bot → Humano)
   - 4.15 Métricas e Dashboard
   - 4.16 Filas e Jobs
   - 4.17 Auditoria

## 5. Matriz de Rastreabilidade
   - 5.1 Casos × Endpoints
   - 5.2 Casos × Requisitos

## 6. Critérios de Aceitação
   - 6.1 Performance
   - 6.2 Qualidade
   - 6.3 Segurança

## 7. Procedimentos de Execução
   - 7.1 Preparação do Ambiente
   - 7.2 Ordem de Execução
   - 7.3 Registro de Resultados

## 8. Anexos
   - 8.1 Payloads de Referência
   - 8.2 Variáveis de Ambiente
```

---

## 5. Categorias de Cenários de Teste

### Por Tipo de Fluxo

| Categoria | Descrição | Exemplo |
|-----------|-----------|---------|
| **Fluxo Feliz** | Caminho principal esperado | Login com credenciais válidas |
| **Fluxo Alternativo** | Variações aceitas | Login com email alternativo |
| **Fluxo de Erro** | Tratamento de erros | Login com senha incorreta |
| **Caso de Borda** | Valores limite | Lista com 0 resultados |
| **Caso de Segurança** | Validação de permissões | Acesso sem token |

### Cenários de Erro Obrigatórios por Endpoint

Para **cada endpoint** documentar:

1. **401 Unauthorized** - Token ausente/inválido/expirado
2. **403 Forbidden** - Permissão insuficiente (role)
3. **404 Not Found** - Recurso não existe
4. **422 Validation Error** - Dados de entrada inválidos
5. **429 Too Many Requests** - Rate limiting
6. **500 Internal Server Error** - Fallback de erro

---

## 6. Cronograma de Execução

### Fase 1: Análise (P) - 2h
- [ ] Mapear todos os endpoints não documentados
- [ ] Identificar cenários de erro ausentes
- [ ] Definir prioridades de cobertura
- [ ] Validar template de caso de teste

### Fase 2: Revisão (R) - 1h
- [ ] Revisar estrutura proposta com stakeholders
- [ ] Aprovar template padronizado
- [ ] Confirmar escopo final

### Fase 3: Execução (E) - 8h
- [ ] Reescrever Módulos 4.1 a 4.6 (Infraestrutura até Tópicos)
- [ ] Reescrever Módulos 4.7 a 4.11 (Mensagens até Webhooks)
- [ ] Reescrever Módulos 4.12 a 4.17 (AI até Auditoria)
- [ ] Adicionar cenários de erro sistematizados
- [ ] Remover scripts e comandos inline
- [ ] Padronizar idioma (PT-BR)

### Fase 4: Verificação (V) - 2h
- [ ] Revisar cobertura (100% endpoints)
- [ ] Validar consistência de idioma
- [ ] Verificar formatação markdown
- [ ] Confirmar remoção de scripts
- [ ] Executar testes de sanidade

---

## 7. Critérios de Sucesso

### Métricas de Qualidade

| Critério | Meta | Peso |
|----------|------|------|
| Cobertura de Endpoints | 100% | 25% |
| Cenários de Erro Documentados | ≥5 por endpoint crítico | 20% |
| Consistência de Idioma | 100% PT-BR | 15% |
| Padronização de Estrutura | 100% seguindo template | 15% |
| Clareza e Objetividade | Autoavaliação ≥8/10 | 15% |
| Utilidade para QA | Verificável em testes | 10% |

### Checklist Final de Validação

- [ ] Todos os 161+ endpoints documentados
- [ ] Nenhum script/comando inline no documento
- [ ] Template padronizado em 100% dos casos
- [ ] Cenários de borda identificados
- [ ] Fluxos de erro exaustivos
- [ ] Pré-condições claramente definidas
- [ ] Validações objetivas e verificáveis
- [ ] Índice atualizado e navegável

---

## 8. Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Endpoints não mapeados | Média | Alto | Varredura completa nos controllers |
| Inconsistência técnica | Baixa | Médio | Revisão por dev backend |
| Documento muito extenso | Alta | Baixo | Índice navegável + busca |
| Falta de tempo | Média | Alto | Priorizar endpoints críticos |

---

## 9. Artefatos Produzidos

| Artefato | Localização | Status |
|----------|-------------|--------|
| Documento reescrito | `back/docs/academic/casos-teste-validacao.md` | 🔄 Em andamento |
| Backup do original | `back/docs/academic/casos-teste-validacao.backup.md` | ⏳ Pendente |
| Matriz de rastreabilidade | Seção 5 do documento | ⏳ Pendente |

---

## 10. Próximas Ações

1. **Aprovar plano** - Revisar este documento com stakeholders
2. **Backup do original** - Criar cópia do documento atual
3. **Iniciar reescrita** - Seguir cronograma da Fase 3
4. **Revisão contínua** - Validar módulo a módulo
5. **Entrega final** - Documento completo e validado
