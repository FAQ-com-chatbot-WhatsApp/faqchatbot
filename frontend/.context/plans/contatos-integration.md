# Plano de Integração: Contatos

**Status**: CONCLUÍDO  
**Data**: 2026-03-12  
**Objetivo**: Integrar tela de contatos com backend WAHA

---

## 1. Mapeamento de Endpoints

### Endpoints WAHA Disponíveis

| Método | Endpoint | Descrição | Status |
|--------|----------|-----------|--------|
| GET | `/waha/check-number?phone=X` | Verifica se número está no WhatsApp | Implementado |
| GET | `/waha/contact-about?contact_id=X` | Informações sobre do contato | Implementado |
| GET | `/waha/contact-picture?contact_id=X` | Foto de perfil do contato | Implementado |
| POST | `/waha/contact/block` | Bloquear contato | Implementado |
| POST | `/waha/contact/unblock` | Desbloquear contato | Implementado |

### Limitações Identificadas

- **Não existe endpoint para listar todos os contatos**
- Workaround: Usar dados mock até backend implementar `GET /waha/contacts`
- Futura implementação: Integrar com chats para derivar lista de contatos

---

## 2. Tipos TypeScript

### Arquivo: `types/waha.ts`

```typescript
export interface WahaContact {
  id: string
  name: string  
  phone: string
  avatar?: string
  about?: string
  isBlocked?: boolean
  lastSeen?: number
}

export interface ContactAboutResponse {
  about: string | null
}

export interface ContactPictureResponse {
  url: string | null
}

export interface CheckNumberResponse {
  exists: boolean
  jid?: string
}

export interface BlockContactRequest {
  contact_id: string
}
```

**Status**: CONCLUÍDO

---

## 3. Service Layer

### Arquivo: `services/wahaService.ts`

Funções implementadas:

```typescript
// Verificar se número existe no WhatsApp
export async function checkNumberExists(phone: string): Promise<CheckNumberResponse>

// Obter informações sobre do contato
export async function getContactAbout(contactId: string): Promise<ContactAboutResponse>

// Obter foto de perfil
export async function getContactPicture(contactId: string): Promise<ContactPictureResponse>

// Bloquear contato
export async function blockContact(contactId: string): Promise<void>

// Desbloquear contato
export async function unblockContact(contactId: string): Promise<void>
```

**Status**: CONCLUÍDO

---

## 4. Custom Hook

### Arquivo: `hooks/useContacts.ts`

Interface do Hook:

```typescript
interface UseContactsReturn {
  contacts: WahaContact[]
  isLoading: boolean
  error: string | null
  checkNumber: (phone: string) => Promise<boolean>
  getAbout: (contactId: string) => Promise<string | null>
  getPicture: (contactId: string) => Promise<string | null>
  block: (contactId: string) => Promise<void>
  unblock: (contactId: string) => Promise<void>
  refresh: () => Promise<void>
}
```

**Funcionalidades**:
- Estado centralizado para contatos
- Tratamento de erros consistente
- Loading states
- Métodos para todas as operações WAHA

**Status**: CONCLUÍDO

---

## 5. Componentes UI Criados

### `components/ui/contact-card.tsx`
- Card compacto para grid de contatos
- Avatar com indicador online
- Botões de ação: Call, Video, Message, Edit, Delete

### `components/ui/contact-detail-panel.tsx`
- Painel lateral com detalhes do contato
- Avatar grande (24x24)
- Botões de ação principais
- Seção "Sobre" com texto descritivo

**Status**: CONCLUÍDO

---

## 6. Integração da Página

### Arquivo: `app/(portal)/contacts/page.tsx`

**Implementações**:
- [x] Importar hook `useContacts`
- [x] Handlers reais para ações:
  - `handleCall` - TODO: Implementar chamada
  - `handleVideo` - TODO: Implementar videochamada
  - `handleMessage` - Navega para `/messages?chatId=X`
  - `handleEdit` - TODO: Abrir modal de edição
  - `handleDelete` - Bloqueia contato via API WAHA
- [x] Feedback visual de erro com Alert
- [x] Usar dados mock até backend implementar listagem
- [x] Navegação para mensagens funcionando

**Status**: CONCLUÍDO

---

## 7. Princípios Aplicados

### SOLID
- **Single Responsibility**: Hook gerencia apenas lógica de contatos
- **Open/Closed**: Service extensível para novos endpoints
- **Dependency Injection**: Hook recebe options, services isolados

### DRY
- Componentes reutilizáveis (ContactCard, ContactDetailPanel)
- Service layer centralizado
- Tipos compartilhados

### KISS
- Interface simples e intuitiva
- Separação clara de responsabilidades
- Código legível

### Clean Architecture
```
UI Layer (page.tsx) 
    ↓ usa
Hook Layer (useContacts) 
    ↓ chama
Service Layer (wahaService) 
    ↓ chama
API Layer (fetchApi)
```

---

## 8. Próximos Passos

### Backend
- [ ] Implementar `GET /waha/contacts` para listar todos os contatos
- [ ] Considerar derivar contatos de chats ativos

### Frontend
- [ ] Implementar modal de criação/edição de contato
- [ ] Implementar funcionalidade de chamada (integração com telefone)
- [ ] Implementar funcionalidade de videochamada
- [ ] Adicionar busca/filtro de contatos
- [ ] Implementar paginação quando houver muitos contatos
- [ ] Carregar foto real do contato via `getContactPicture`
- [ ] Carregar "sobre" real do contato via `getContactAbout`

---

## 9. Testes Manuais

### Checklist de Validação
- [x] Página carrega sem erros TypeScript
- [x] Grid de contatos renderiza corretamente
- [x] Painel de detalhes atualiza ao selecionar contato
- [x] Botão "Mensagem" navega para página de mensagens
- [x] Botão "Delete" chama API de bloqueio (com confirmação)
- [x] Feedback de erro exibido quando API falha
- [ ] Testar com backend real quando disponível

---

## 10. Conclusão

Integração de contatos concluída seguindo arquitetura Clean Code e SOLID. Funcionalidade parcial devido à limitação de backend (sem endpoint de listagem). Próxima integração: **Settings/Configurações**.
