# Roadmap de Migração: Joomla Legacy → API REST WhatsBot

## Visão Geral

Transformação gradual do sistema FAQ baseado em Joomla para plataforma conversacional API-first, mantendo compatibilidade e minimizando interrupções.

---

## FASE 1: FUNDAÇÃO API (✅ CONCLUÍDA)

**Objetivo**: Estabelecer infraestrutura básica da API REST

**Entregas**:

- ✅ Arquitetura da API REST definida
- ✅ Autenticação JWT implementada
- ✅ Endpoints básicos: `/health/ping`, `/auth/login`
- ✅ Middleware de autenticação e validação
- ✅ CORS e rate-limiting configurados
- ✅ Roles mapeadas do Joomla (`bak_lepgs_users`, `bak_lepgs_usergroups`)
- ✅ Schema conversacional criado (contacts, flows, conversations, messages)
- ✅ Docker environment configurado (botdb, webcore, skynet)

**Status**: ✅ Completo

---

## FASE 2: DOMÍNIO CONVERSACIONAL (🔄 EM ANDAMENTO)

**Objetivo**: Implementar core conversacional e fluxos

### 2.1 Endpoints Conversacionais

- [ ] `POST /conversations` - Criar conversa para contato
- [ ] `POST /messages` - Adicionar mensagem a conversa
- [ ] `GET /conversations/:id` - Buscar conversa com mensagens
- [ ] `GET /conversations` - Listar conversas (paginado)
- [ ] `PATCH /conversations/:id` - Atualizar status (active/paused/closed)

### 2.2 Gestão de Fluxos

- [ ] `POST /flows` - Criar fluxo conversacional
- [ ] `GET /flows` - Listar fluxos ativos
- [ ] `GET /flows/:id` - Detalhes do fluxo
- [ ] `PUT /flows/:id` - Atualizar fluxo
- [ ] `DELETE /flows/:id` - Desativar fluxo (soft delete)

### 2.3 Contatos

- [ ] `POST /contacts` - Criar contato manualmente
- [ ] `GET /contacts/:id` - Buscar contato
- [ ] `PATCH /contacts/:id` - Atualizar dados do contato

**Prazo estimado**: 2 semanas  
**Dependências**: Schema criado, JWT funcionando  
**Status**: 🔄 Endpoints criados, pendente integração

---

## FASE 3: MIGRAÇÃO DE CONTEÚDO FAQ

**Objetivo**: Transformar conteúdo FAQ do Joomla em fluxos conversacionais

### 3.1 Mapeamento FAQ → Flows

- [ ] Analisar categorias FAQ (`bak_lepgs_categories`)
- [ ] Mapear perguntas/respostas para flows e mensagens
- [ ] Script de migração SQL/PHP
- [ ] Criar flows padrão por categoria

### 3.2 Adaptador FAQ

- [ ] Endpoint `GET /faq/categories` (leitura do Joomla)
- [ ] Endpoint `GET /faq/questions` (leitura do Joomla)
- [ ] Converter FAQ em flow conversacional automaticamente
- [ ] Manter sincronização temporária (dual-write se necessário)

### 3.3 Interface Admin

- [ ] Reutilizar admin Joomla para gestão de FAQ (temporário)
- [ ] Planejar migração futura para admin React/Vue

**Prazo estimado**: 3 semanas  
**Dependências**: Fase 2 completa

---

## FASE 4: INTEGRAÇÃO WHATSAPP

**Objetivo**: Conectar API com WhatsApp Business API ou alternativa

### 4.1 Webhooks WhatsApp

- [ ] `POST /webhooks/whatsapp` - Receber mensagens do WhatsApp
- [ ] Validação de assinatura webhook
- [ ] Parser de mensagens WhatsApp → formato interno
- [ ] Rate limiting específico para webhooks

### 4.2 Envio de Mensagens

- [ ] Integração com WhatsApp Business API / Twilio / Evolution API
- [ ] Template de mensagens
- [ ] Fila de envio (background jobs)
- [ ] Retry logic e tratamento de erros

### 4.3 Gestão de Sessões

- [ ] TTL de conversas (timeout configurável)
- [ ] Retomada de conversa interrompida
- [ ] Histórico de conversas por contato

**Prazo estimado**: 4 semanas  
**Dependências**: Fase 3 completa, escolha da plataforma WhatsApp

---

## FASE 5: INTELIGÊNCIA E CONTEXTO

**Objetivo**: Adicionar processamento inteligente e contexto conversacional

### 5.1 Processamento de Linguagem

- [ ] Integração OpenAI/Anthropic (opcional)
- [ ] Detecção de intenção (keyword matching simples ou NLP)
- [ ] Sugestões de respostas baseadas em histórico

### 5.2 Analytics Conversacional

- [ ] Métricas de engajamento (taxas resposta, abandono)
- [ ] Dashboard de conversas ativas/concluídas
- [ ] Relatórios por flow

### 5.3 Gestão de Estado

- [ ] State machine para flows complexos
- [ ] Variáveis de contexto (nome, preferências)
- [ ] Branches condicionais nos flows

**Prazo estimado**: 5 semanas  
**Dependências**: Fase 4 completa

---

## FASE 6: OTIMIZAÇÃO E ESCALA

**Objetivo**: Preparar sistema para produção e alta carga

### 6.1 Performance

- [ ] Implementar Redis para cache de sessões
- [ ] Queue system (Redis/RabbitMQ) para mensagens assíncronas
- [ ] Índices otimizados no banco
- [ ] Lazy loading de mensagens antigas

### 6.2 Observabilidade

- [ ] Logging estruturado (Monolog)
- [ ] APM (Application Performance Monitoring)
- [ ] Alertas de erros e latência
- [ ] Healthchecks avançados

### 6.3 Segurança

- [ ] Audit log de ações administrativas
- [ ] Criptografia de dados sensíveis
- [ ] Rotação de tokens JWT
- [ ] Proteção contra ataques (SQL injection, XSS, CSRF)

**Prazo estimado**: 3 semanas  
**Dependências**: Fase 5 completa

---

## FASE 7: DESCONTINUAÇÃO JOOMLA (FUTURO)

**Objetivo**: Remover dependência do Joomla CMS

### 7.1 Admin Standalone

- [ ] Frontend admin em React/Vue
- [ ] CRUD completo de flows, contacts, messages
- [ ] Dashboard analytics

### 7.2 Migração Final

- [ ] Migrar tabelas `bak_lepgs_*` para schema limpo
- [ ] Remover código legacy Joomla
- [ ] Documentação de APIs finais
- [ ] Deploy em produção

**Prazo estimado**: 8+ semanas  
**Dependências**: Todas as fases anteriores completas

---

## Estratégia de Compatibilidade

### Durante a Transição

1. **Dual Operation**: Joomla continua servindo site FAQ público
2. **API Paralela**: Nova API opera independentemente para conversas
3. **Shared Database**: Mesmo banco `botdb` com prefixo `bak_lepgs_` para Joomla
4. **Gradual Cutover**: Endpoints migrados um por um

### Testes e Validação

- **Staging Environment**: Réplica exata de produção
- **Testes Automatizados**: Suite de testes para cada endpoint
- **Rollback Plan**: Capacidade de reverter qualquer fase
- **Monitoramento**: Logs e métricas em cada deploy

---

## Riscos e Mitigações

| Risco                       | Impacto | Mitigação                                    |
| --------------------------- | ------- | -------------------------------------------- |
| Perda de dados na migração  | Alto    | Backups automáticos, testes extensivos       |
| Downtime durante deploy     | Médio   | Blue-green deployment, rollback rápido       |
| Incompatibilidade de schema | Médio   | Migrations versionadas, testes de integração |
| Performance degradada       | Médio   | Load testing, cache agressivo                |
| Bugs em produção            | Alto    | Canary releases, feature flags               |

---

## Próximos Passos Imediatos

1. ✅ Completar implementação endpoints `/conversations` e `/messages`
2. ✅ Testar fluxo completo: criar conversa → adicionar mensagens → buscar histórico
3. [ ] Criar flow de exemplo no banco para testes
4. [ ] Documentar APIs no Postman collection
5. [ ] Implementar testes automatizados básicos

---

**Última atualização**: 06/11/2025  
**Fase atual**: 2 - Domínio Conversacional  
**Progresso geral**: ~20%
