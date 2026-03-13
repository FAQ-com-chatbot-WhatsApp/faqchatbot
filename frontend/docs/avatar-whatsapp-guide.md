# Guia: Como Buscar Avatares do WhatsApp

A aplicação já possui integração com a API do WhatsApp (via WAHA) para buscar fotos de perfil dos contatos.

## API Disponível

### Backend Endpoint
```
GET /api/v1/waha/contact-picture?contact_id={PHONE}@c.us
```

**Exemplo:**
```
GET /api/v1/waha/contact-picture?contact_id=5511999999999@c.us
```

**Resposta:**
```json
{
  "profilePictureURL": "https://...",
  "eurl": "base64_image_data..."
}
```

## Como Implementar no Frontend

### 1. Criar Hook para Cache de Avatares

```typescript
// hooks/useContactAvatars.ts
import { useState, useEffect } from 'react'
import { getContactPicture } from '@/services/wahaService'

export function useContactAvatar(phoneNumber: string) {
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    async function fetchAvatar() {
      if (!phoneNumber) return
      
      const contactId = `${phoneNumber}@c.us`
      const cacheKey = `avatar_${contactId}`
      
      // Check cache first
      const cached = localStorage.getItem(cacheKey)
      if (cached) {
        setAvatarUrl(cached)
        return
      }

      setLoading(true)
      try {
        const response = await getContactPicture(contactId)
        const url = response.profilePictureURL || response.eurl
        
        if (url) {
          localStorage.setItem(cacheKey, url)
          setAvatarUrl(url)
        }
      } catch (error) {
        console.error('Error fetching avatar:', error)
      } finally {
        setLoading(false)
      }
    }

    fetchAvatar()
  }, [phoneNumber])

  return { avatarUrl, loading }
}
```

### 2. Usar no Componente de Conversa

```typescript
// Exemplo de uso no ConversationItem
const { avatarUrl } = useContactAvatar(conversation.phone_number)

<Avatar>
  {avatarUrl ? (
    <AvatarImage src={avatarUrl} alt={conversation.lead_name} />
  ) : (
    <AvatarFallback>{initials}</AvatarFallback>
  )}
</Avatar>
```

### 3. Adicionar Serviço no wahaService.ts

```typescript
export async function getContactPicture(contactId: string): Promise<ContactPictureResponse> {
  return fetchApi<ContactPictureResponse>(
    `${WAHA_BASE}/contact-picture?contact_id=${encodeURIComponent(contactId)}`
  )
}
```

## Considerações de Performance

1. **Cache Local**: Use `localStorage` para evitar buscar o mesmo avatar repetidamente
2. **Lazy Loading**: Busque avatares apenas quando necessário (scroll infinito)
3. **Fallback**: Sempre tenha um fallback com iniciais caso a API falhe
4. **Expiration**: Considere adicionar TTL ao cache (ex: 24 horas)

## Exemplo Completo

```typescript
// messages/page.tsx
const [avatarCache, setAvatarCache] = useState<Record<string, string>>({})

const loadAvatar = async (phoneNumber: string) => {
  if (avatarCache[phoneNumber]) return avatarCache[phoneNumber]

  const contactId = `${phoneNumber}@c.us`
  try {
    const response = await getContactPicture(contactId)
    const url = response.profilePictureURL || response.eurl
    
    setAvatarCache(prev => ({ ...prev, [phoneNumber]: url }))
    return url
  } catch (error) {
    return null
  }
}

// No render
<ConversationItem
  avatar={avatarCache[conv.phone_number] || ''}
  onVisible={() => loadAvatar(conv.phone_number)}
/>
```

## Notas Importantes

- A API só funciona se houver uma sessão WhatsApp ativa
- Alguns contatos podem não ter foto de perfil
- Respeite as configurações de privacidade do WhatsApp
- Use cache para evitar sobrecarga na API WAHA
