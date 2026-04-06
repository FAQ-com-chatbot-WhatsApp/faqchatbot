# 🗺️ PLAN-faq-hub-fixes.md

## 🎯 Objetivo
Habilitar a sincronização funcional entre o Repositório de FAQ (Joomla) e o Cérebro do Bot, e implementar uma busca global baseada no banco de dados interno.

---

## 🏗️ Arquitetura das Mudanças

### Phase 1: Correção de Mapeamento (Backend)
- **Arquivo:** `back/src/robbot/services/integration/faq_integration_service.py`
- **Ação:** Atualizar chaves de dicionário:
  - `id_faqs_group` → `group_id`
  - `id_category` → `category_id`

### Phase 2: Endpoint de Busca Global (Backend)
- **Serviço:** Criar `FaqIntegrationService.search_questions(query: str)` em `back/src/robbot/services/integration/faq_integration_service.py`.
- **Router:** Adicionar `/search` em `back/src/robbot/adapters/controllers/faq_controller.py`.

### Phase 3: Integração Frontend (Dashboard)
- **Serviço:** `frontend/src/app/(portal)/faq/faqService.ts` → Adicionar `searchGlobal(query)`.
- **UI:** No `page.tsx`, o `onChange` do input de busca deve alternar para busca global quando houver texto.

---

## 🛡️ Checklist de Verificação

- [ ] Sincronização funcional (sem erros de "Group not found").
- [ ] Busca Global retorna resultados do banco interno.
- [ ] UX do Dashboard atualiza com resultados da busca.
