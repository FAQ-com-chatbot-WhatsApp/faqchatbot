# Plano de Implementação: Sistema de Notificações Reais (Bot → Atendimento)

Este plano descreve a transição do sistema de notificações de dados mocados para dados reais, integrando o gatilho de handoff do bot com a interface da secretária.

## 📝 Visão Geral
Transformar a tela de notificações em um painel operacional que alerta em tempo real quando um lead está pronto para agendamento (Handoff), incluindo persistência no banco de dados e popups (toasts) no frontend.

## 🏗️ Fase 1: Backend - Persistência e Gatilhos
**Objetivo:** Garantir que cada Handoff gere um registro real na tabela `notifications`.

1.  **Refatoração do `HandoffService`**:
    *   Injetar a lógica de criação de `NotificationModel` dentro de `trigger_handoff`.
    *   Determinar os destinatários: Notificar o `assigned_to_user_id` do Lead ou, se nulo, criar notificações para todos os usuários com role `ADMIN` ou `USER`.
2.  **Criação do `NotificationController`**:
    *   Implementar endpoint `GET /api/v1/notifications` para listar notificações do usuário logado.
    *   Implementar endpoint `PATCH /api/v1/notifications/{id}/read` para marcar como lida.
3.  **Lógica de "Bot Desativado"**:
    *   Confirmar que `PENDING_HANDOFF` mantém o bot silenciado (já verificado).

## 🎨 Fase 2: Frontend - Notificações Reais e Toasts
**Objetivo:** Substituir mocks por chamadas de API e implementar o alerta visual.

1.  **Serviço de API (`notificationService.ts`)**:
    *   Criar métodos para buscar notificações e marcar como lidas.
2.  **Hook `useNotifications`**:
    *   Substituir os dados estáticos por uma chamada ao backend com polling (ou WebSocket, se preferível para tempo real absoluto).
3.  **Componente de Toast**:
    *   Integrar a biblioteca de toast (ex: `sonner`) para exibir o popup: *"O cliente {nome} está pronto para o agendamento! 🎯"*.
4.  **Interface de Notificação**:
    *   Atualizar a tela de notificações para renderizar a lista real vinda do banco.

## 📱 Fase 3: UX/UI e Coerência
**Objetivo:** Garantir acessibilidade e indicadores visuais.

1.  **Indicador de Bot Ativo**: 
    *   Garantir que na tela de mensagens o botão informativo mostre corretamente que o bot está "Pausado (Aguardando Atendimento)".
2.  **Acessibilidade**:
    *   Garantir labels ARIA para os toasts e contraste adequado nas notificações urgentes.

## ✅ Checklist de Verificação
- [ ] O registro é criado na tabela `notifications` quando o score do lead >= 85?
- [ ] O bot para de responder automaticamente após o handoff?
- [ ] O popup aparece na tela da secretária em menos de 5 segundos?
- [ ] Ao marcar como lida, o contador de notificações diminui no header?

---
**Próximos Passos:**
- Revisar este plano em `docs/PLAN-notification-system.md`.
- Executar `/create` para iniciar a implementação técnica.
