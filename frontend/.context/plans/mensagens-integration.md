# Plano de Integração - Tela de Mensagens

**Status**: CONCLUÍDO  
**Data Início**: 2026-03-11  
**Data Conclusão**: 2026-03-11  
**Commit**: `24c1ce97 - feat(frontend): integrar tela de mensagens com backend WAHA`

---

## Objetivo

Integrar a tela de mensagens (`app/dashboard/mensagens/page.tsx`) com o backend WAHA, removendo dados mockados e implementando comunicação real com a API.

---

## Fase 1: Setup e Mapeamento de Endpoints

### 1.1 Endpoints WAHA Identificados

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/waha/chats/:chat_id/messages` | GET | Listar mensagens de um chat |
| `/waha/messages/text` | POST | Enviar mensagem de texto |
| `/waha/messages/image` | POST | Enviar imagem |
| `/waha/messages/location` | POST | Enviar localização |
| `/waha/chats/:chat_id/messages/:message_id` | PUT | Editar mensagem |
| `/waha/chats/:chat_id/messages/:message_id` | DELETE | Deletar mensagem |

### 1.2 Tipos TypeScript Criados

**Arquivo**: `src/types/waha.ts`

```typescript
export interface WahaMessage {
  id: string
  chatId: string
  type: 'text' | 'image' | 'video' | 'audio' | 'document' | 'location'
  body?: string
  fromMe: boolean
  timestamp: number
  to?: string
  from?: string
}

export interface WahaChat {
  id: string
  name: string
  lastMessage?: string
  timestamp?: number
  unreadCount?: number
}

export interface SendTextMessageRequest {
  chatId: string
  text: string
}

export interface SendImageMessageRequest {
  chatId: string
  image: {
    url: string
  }
  caption?: string
}

export interface SendLocationMessageRequest {
  chatId: string
  latitude: number
  longitude: number
}

export interface GetMessagesResponse {
  messages: WahaMessage[]
}
```

### 1.3 Service Layer Implementado

**Arquivo**: `src/services/wahaService.ts`

**Funções implementadas**:

```typescript
// Listar mensagens de um chat
export async function getChatMessages(chatId: string, limit = 50): Promise<GetMessagesResponse>

// Enviar mensagem de texto
export async function sendTextMessage(request: SendTextMessageRequest): Promise<WahaMessage>

// Enviar imagem
export async function sendImageMessage(request: SendImageMessageRequest): Promise<WahaMessage>

// Enviar localização
export async function sendLocationMessage(request: SendLocationMessageRequest): Promise<WahaMessage>

// Deletar mensagem
export async function deleteMessage(chatId: string, messageId: string): Promise<void>

// Editar mensagem
export async function editMessage(chatId: string, messageId: string, text: string): Promise<WahaMessage>
```

**Características**:
- URL encoding com `encodeURIComponent` para segurança
- Reutiliza `fetchApi` do `lib/api.ts`
- Error handling delegado para camada central
- Tipos TypeScript fortes

---

## Fase 2: Implementação de Hooks e UI

### 2.1 Custom Hook Criado

**Arquivo**: `src/hooks/useMessages.ts`

**API do Hook**:

```typescript
const {
  messages,        // WahaMessage[]
  isLoading,       // boolean
  error,           // string | null
  sendMessage,     // (chatId: string, text: string) => Promise<void>
  deleteMsg,       // (chatId: string, msgId: string) => Promise<void>
  editMsg,         // (chatId: string, msgId: string, text: string) => Promise<void>
  refresh          // () => Promise<void>
} = useMessages(chatId)
```

**Características**:
- Auto-loading na montagem do componente
- Estados de loading, error, messages
- Funções otimizadas com `useCallback`
- Optimistic updates na UI
- Error handling com mensagens amigáveis

### 2.2 Componente Refatorado

**Arquivo**: `src/app/dashboard/mensagens/page.tsx`

**Mudanças implementadas**:

1. **Remoção de mocks**: Dados hardcoded substituídos por `useMessages`
2. **Loading states**: Spinner durante carregamento
3. **Error states**: Alert vermelho com mensagem de erro
4. **Empty states**: Mensagem quando não há mensagens
5. **Feedback visual**: Indicadores de ações (enviar, deletar, editar)
6. **Formatação de timestamps**: `new Date(msg.timestamp).toLocaleString()`

**Estrutura do componente**:

```typescript
'use client'

export default function MensagensPage() {
  const [chatId, setChatId] = useState('default-chat')
  const [message, setMessage] = useState('')
  
  const {
    messages,
    isLoading,
    error,
    sendMessage,
    deleteMsg,
    editMsg,
    refresh
  } = useMessages(chatId)

  // Loading state
  if (isLoading) return <Spinner />

  // Error state
  if (error) return <Alert variant="destructive">{error}</Alert>

  // Empty state
  if (messages.length === 0) return <EmptyState />

  // Normal render com lista de mensagens
  return <MessagesUI />
}
```

---

## Fase 3: Revisão e Validação

### 3.1 Arquitetura Revisada

**Documento**: [mensagens-integration-review.md](./mensagens-integration-review.md)

**Checklist de conformidade**:
- SOLID: Single Responsibility aplicado
- DRY: Reutilização de código (fetchApi, tipos)
- KISS: Soluções simples e diretas
- Clean Code: Nomes descritivos, funções pequenas
- Clean Architecture: Camadas bem separadas (types, services, hooks, UI)

### 3.2 Validação Completa

**Documento**: [mensagens-integration-validation.md](./mensagens-integration-validation.md)

**Itens validados**:
- 6 endpoints mapeados
- 6 tipos TypeScript criados
- 6 funções no service layer
- Hook customizado completo
- Componente refatorado
- Feedback visual implementado
- Zero erros TypeScript
- Código commitado no git

---

## Resultados

### Arquivos Criados/Modificados

| Arquivo | Status | LOC |
|---------|--------|-----|
| `src/types/waha.ts` | Criado | ~50 |
| `src/services/wahaService.ts` | Criado | ~80 |
| `src/hooks/useMessages.ts` | Criado | ~100 |
| `src/app/dashboard/mensagens/page.tsx` | Refatorado | ~200 |

### Métricas de Qualidade

- **0 erros TypeScript**
- **100% dos endpoints mapeados**
- **Separação clara de responsabilidades**
- **Código totalmente tipado**
- **Error handling completo**
- **Loading states implementados**

---

## Lições Aprendidas

1. **Separação de camadas** facilita manutenção
2. **Hooks customizados** encapsulam lógica complexa
3. **Tipagem forte** previne bugs em tempo de desenvolvimento
4. **Service layer** centraliza lógica de API
5. **Feedback visual** melhora UX drasticamente

---

## Próximos Passos

- [ ] Implementar integração de **Contatos**
- [ ] Implementar integração de **Configurações**
- [ ] Implementar integração de **Dashboard**
- [ ] Adicionar testes unitários para hooks
- [ ] Adicionar testes de integração para services
