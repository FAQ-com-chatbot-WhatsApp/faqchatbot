"use client"

import { useState, useMemo, useEffect, useRef } from "react"
import { Search, AlertCircle, MessageSquare, Star } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Card } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { MessageBubble } from "@/components/ui/message-bubble"
import { MessageInput } from "@/components/ui/message-input"
import { ConversationItem } from "@/components/ui/conversation-item"
import { ChatHeader } from "@/components/ui/chat-header"
import { LoadingSpinner } from "@/components/ui/loading-spinner"
import { EmptyState } from "@/components/ui/empty-state"
import { useConversations, useConversationMessages } from "@/hooks/useConversations"
import type { Conversation, ConversationMessage } from "@/services/conversationService"
import { markConversationAsRead, updateConversationStatus } from "@/services/conversationService"
import { sendTextMessage, getContactPicture } from "@/services/wahaService"

type FilterType = "all" | "unread" | "groups" | "favorites"

export default function MessagesPage() {
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null)
  const [searchQuery, setSearchQuery] = useState("")
  const [activeFilter, setActiveFilter] = useState<FilterType>("all")
  const [isSending, setIsSending] = useState(false)
  const [favorites, setFavorites] = useState<Set<string>>(new Set())
  const [localUnreadCounts, setLocalUnreadCounts] = useState<Map<string, number>>(new Map())
  const [avatarCache, setAvatarCache] = useState<Record<string, string>>({})
  const [pendingMessages, setPendingMessages] = useState<ConversationMessage[]>([])
  const messagesEndRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const saved = localStorage.getItem('conversation_favorites')
    if (saved) {
      try {
        setFavorites(new Set(JSON.parse(saved)))
      } catch (error) {
        console.error('Erro ao carregar favoritos:', error)
      }
    }
  }, [])

  useEffect(() => {
    localStorage.setItem('conversation_favorites', JSON.stringify([...favorites]))
  }, [favorites])

  const {
    conversations,
    isLoading: isLoadingConversations,
    error: conversationsError,
    refresh: refreshConversations,
  } = useConversations({
    enabled: true,
    search: searchQuery,
  })

  useEffect(() => {
    if (conversations.length > 0) {
      const counts = new Map<string, number>()
      conversations.forEach(conv => {
        if (!localUnreadCounts.has(conv.id)) {
          counts.set(conv.id, conv.unread_count || 0)
        } else {
          counts.set(conv.id, localUnreadCounts.get(conv.id) || 0)
        }
      })
      setLocalUnreadCounts(counts)
    }
  }, [conversations])

  const loadAvatar = async (phoneNumber: string) => {
    if (avatarCache[phoneNumber]) return avatarCache[phoneNumber]

    const contactId = `${phoneNumber}@c.us`
    const cacheKey = `avatar_${contactId}`

    const cached = localStorage.getItem(cacheKey)
    if (cached) {
      setAvatarCache(prev => ({ ...prev, [phoneNumber]: cached }))
      return cached
    }

    try {
      const response = await getContactPicture(contactId)
      const url = response.url || ''

      if (url) {
        localStorage.setItem(cacheKey, url)
        setAvatarCache(prev => ({ ...prev, [phoneNumber]: url }))
      }
      return url
    } catch (error) {
      console.error('Error fetching avatar:', error)
      return ''
    }
  }

  useEffect(() => {
    conversations.forEach(conv => {
      if (!avatarCache[conv.phone_number]) {
        loadAvatar(conv.phone_number)
      }
    })
  }, [conversations])

  const {
    messages: serverMessages,
    isLoading: isLoadingMessages,
    error: messagesError,
    refresh: refreshMessages,
  } = useConversationMessages({
    conversationId: selectedConversation?.id,
    enabled: !!selectedConversation?.id,
  })

  // Combinar mensagens do servidor com mensagens pendentes
  const messages = useMemo(() => {
    return [...serverMessages, ...pendingMessages].sort(
      (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    )
  }, [serverMessages, pendingMessages])

  const handleSendMessage = async (text: string) => {
    if (!selectedConversation || !text.trim()) return

    setIsSending(true)

    // Criar mensagem otimista para exibir imediatamente
    const optimisticMessage: ConversationMessage = {
      id: Date.now(), // ID temporário único
      direction: "outgoing",
      from_phone: "user",
      to_phone: selectedConversation.phone_number,
      body: text.trim(),
      created_at: new Date().toISOString(),
    }

    try {
      // USAR CHAT_ID REAL DO BANCO (pode ser @lid, @c.us, @g.us)
      const chatId = selectedConversation.chat_id

      // Adicionar mensagem pendente imediatamente à UI
      setPendingMessages(prev => [...prev, optimisticMessage])
      setTimeout(() => scrollToBottom(), 50)

      await sendTextMessage({
        chat_id: chatId,
        text: text.trim(),
      })

      // Backend agora salva a mensagem outbound automaticamente
      // Aguardar um pouco e atualizar com dados reais do servidor
      await new Promise(resolve => setTimeout(resolve, 500))
      await refreshMessages()

      // Remover mensagem pendente após receber do servidor
      setPendingMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id))
      setTimeout(() => scrollToBottom(), 100)
    } catch (error) {
      console.error("Erro ao enviar mensagem:", error)
      alert("Erro ao enviar mensagem. Tente novamente.")
      // Remover mensagem pendente em caso de erro
      setPendingMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id))
    } finally {
      setIsSending(false)
    }
  }

  const handleSelectConversation = (conv: Conversation) => {
    setSelectedConversation(conv)
    setPendingMessages([]) // Limpar mensagens pendentes ao trocar conversa

    setLocalUnreadCounts(prev => {
      const newCounts = new Map(prev)
      newCounts.set(conv.id, 0)
      return newCounts
    })

    // Marcar conversa como lida no backend
    markConversationAsRead(conv.id).catch(err => {
      console.error("Erro ao marcar conversa como lida:", err)
    })

    setTimeout(() => {
      messagesEndRef.current?.scrollIntoView({ behavior: "auto" })
    }, 100)
  }

  const toggleFavorite = (conversationId: string, e?: React.MouseEvent) => {
    e?.stopPropagation()
    setFavorites(prev => {
      const newFavorites = new Set(prev)
      if (newFavorites.has(conversationId)) {
        newFavorites.delete(conversationId)
      } else {
        newFavorites.add(conversationId)
      }
      return newFavorites
    })
  }

  const handleBotToggle = async (isBotEnabled: boolean) => {
    if (!selectedConversation) return

    try {
      const newStatus = isBotEnabled ? "ACTIVE_BOT" : "ACTIVE_HUMAN"
      await updateConversationStatus(selectedConversation.id, newStatus)

      // Atualizar status local
      setSelectedConversation({
        ...selectedConversation,
        status: newStatus,
      })

      // Refresh para obter estado atualizado
      await refreshConversations()
    } catch (error) {
      console.error("Erro ao alternar modo bot:", error)
      alert("Erro ao alternar modo. Tente novamente.")
    }
  }

  const conversationCounts = useMemo(() => {
    const unread = conversations.filter(c => {
      const localCount = localUnreadCounts.get(c.id)
      return (localCount !== undefined ? localCount : c.unread_count || 0) > 0
    }).length
    const groups = 0
    const favoritesCount = conversations.filter(c => favorites.has(c.id)).length

    return { unread, groups, favorites: favoritesCount }
  }, [conversations, favorites, localUnreadCounts])

  const filteredConversations = useMemo(() => {
    switch (activeFilter) {
      case "unread":
        return conversations.filter(c => {
          const localCount = localUnreadCounts.get(c.id)
          return (localCount !== undefined ? localCount : c.unread_count || 0) > 0
        })
      case "groups":
        return []
      case "favorites":
        return conversations.filter(c => favorites.has(c.id))
      default:
        return conversations
    }
  }, [conversations, activeFilter, favorites, localUnreadCounts])

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
  }

  useEffect(() => {
    scrollToBottom()
  }, [messages])

  const getInitials = (name?: string | null) => {
    if (!name) return "??"
    const parts = name.split(" ")
    return parts.length > 1
      ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
      : name.slice(0, 2).toUpperCase()
  }

  const formatPhoneNumber = (phone: string) => {
    const cleaned = phone.replace(/^\+?55/, '')

    if (cleaned.length === 11) {
      return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 7)}-${cleaned.slice(7)}`
    } else if (cleaned.length === 10) {
      return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 6)}-${cleaned.slice(6)}`
    }
    return phone
  }

  const formatTimestamp = (timestamp: string) => {
    // Se o timestamp não tem 'Z' ou '+', adicionar 'Z' para tratar como UTC
    const isoTimestamp = timestamp.includes('Z') || timestamp.includes('+')
      ? timestamp
      : timestamp + 'Z'

    const date = new Date(isoTimestamp)
    const now = new Date()
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

    if (diffInHours < 24) {
      // Exibir hora local (hora:minuto)
      return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'America/Sao_Paulo'
      })
    } else if (diffInHours < 24 * 7) {
      // Menos de uma semana: mostrar dia da semana
      return date.toLocaleDateString('pt-BR', {
        weekday: 'short',
        timeZone: 'America/Sao_Paulo'
      })
    } else {
      // Mais de uma semana: mostrar data
      return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        timeZone: 'America/Sao_Paulo'
      })
    }
  }

  return (
    <div className="flex h-[calc(100vh-4rem)] gap-4 p-4">
      <Card className="w-80 flex flex-col shadow-sm">
        <div className="p-4 border-b space-y-3">
          <div className="relative">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              id="search-conversations"
              name="search"
              placeholder="Buscar conversa..."
              className="pl-9"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>

          <div className="flex gap-2">
            <button
              onClick={() => setActiveFilter("all")}
              className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${activeFilter === "all"
                ? "bg-primary text-primary-foreground"
                : "bg-muted text-muted-foreground hover:bg-muted/80"
                }`}
            >
              Tudo
            </button>
            <button
              onClick={() => setActiveFilter("unread")}
              className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${activeFilter === "unread"
                ? "bg-primary text-primary-foreground"
                : "bg-muted text-muted-foreground hover:bg-muted/80"
                }`}
            >
              Não lidas {conversationCounts.unread > 0 && conversationCounts.unread}
            </button>
            <button
              onClick={() => setActiveFilter("favorites")}
              className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${activeFilter === "favorites"
                ? "bg-primary text-primary-foreground"
                : "bg-muted text-muted-foreground hover:bg-muted/80"
                }`}
            >
              Favoritos {conversationCounts.favorites > 0 && conversationCounts.favorites}
            </button>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto">
          {isLoadingConversations ? (
            <div className="flex items-center justify-center p-8">
              <LoadingSpinner />
            </div>
          ) : conversationsError ? (
            <div className="p-4">
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>{conversationsError}</AlertDescription>
              </Alert>
            </div>
          ) : filteredConversations.length === 0 ? (
            <EmptyState
              icon={MessageSquare}
              message={
                activeFilter === "unread"
                  ? "Nenhuma conversa não lida"
                  : activeFilter === "favorites"
                    ? "Nenhum favorito. Passe o mouse e clique na estrela!"
                    : "Nenhuma conversa encontrada"
              }
            />
          ) : (
            filteredConversations.map((conv) => (
              <div key={conv.id} className="relative group">
                <ConversationItem
                  name={conv.lead_name || formatPhoneNumber(conv.phone_number)}
                  initials={getInitials(conv.lead_name)}
                  avatar={avatarCache[conv.phone_number] || ''}
                  lastMessage={conv.last_message || undefined}
                  unreadCount={localUnreadCounts.get(conv.id) ?? conv.unread_count ?? 0}
                  isActive={selectedConversation?.id === conv.id}
                  isOnline={conv.status === "active"}
                  onClick={() => handleSelectConversation(conv)}
                />
                <button
                  onClick={(e) => toggleFavorite(conv.id, e)}
                  className="absolute top-2 right-2 p-1.5 rounded-full hover:bg-muted/80 transition-colors opacity-0 group-hover:opacity-100"
                  title={favorites.has(conv.id) ? "Remover dos favoritos" : "Adicionar aos favoritos"}
                >
                  <Star
                    className={`h-4 w-4 ${favorites.has(conv.id) ? 'fill-yellow-400 text-yellow-400' : 'text-muted-foreground'}`}
                  />
                </button>
              </div>
            ))
          )}
        </div>
      </Card>

      <Card className="flex-1 flex flex-col shadow-sm">
        {selectedConversation ? (
          <>
            <ChatHeader
              name={selectedConversation.lead_name || formatPhoneNumber(selectedConversation.phone_number)}
              initials={getInitials(selectedConversation.lead_name)}
              avatar={avatarCache[selectedConversation.phone_number] || ''}
              status={selectedConversation.status === "active" ? "online" : "offline"}
              isOnline={selectedConversation.status === "active"}
              isBotActive={selectedConversation.status === "ACTIVE_BOT"}
              onBotToggle={handleBotToggle}
              onMore={refreshMessages}
            />

            {messagesError && (
              <Alert variant="destructive" className="m-4">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>{messagesError}</AlertDescription>
              </Alert>
            )}

            <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
              {isLoadingMessages && messages.length === 0 ? (
                <LoadingSpinner />
              ) : messages.length === 0 ? (
                <EmptyState icon={MessageSquare} message="Nenhuma mensagem ainda" />
              ) : (
                messages.map((msg: ConversationMessage) => {
                  const isInbound = msg.direction === "INBOUND" || msg.direction === "inbound"
                  const leadName = selectedConversation?.lead_name || "Lead"

                  // Determinar status da mensagem
                  const isPending = typeof msg.id === 'number' && msg.id > 1000000000000 // ID temporário (timestamp)
                  const messageStatus = isPending ? "pending" : "sent"

                  return (
                    <MessageBubble
                      key={msg.id}
                      sender={isInbound ? "other" : "user"}
                      message={msg.body}
                      timestamp={formatTimestamp(msg.created_at)}
                      senderName={isInbound ? leadName : undefined}
                      senderInitials={isInbound ? getInitials(leadName) : undefined}
                      senderAvatar={isInbound ? avatarCache[selectedConversation?.phone_number || ''] : undefined}
                      status={messageStatus}
                    />
                  )
                })
              )}
              <div ref={messagesEndRef} />
            </div>

            <MessageInput
              onSend={handleSendMessage}
              placeholder="Digite sua mensagem..."
              disabled={isLoadingMessages || isSending}
              showAttachment
            />
          </>
        ) : (
          <div className="flex-1 flex items-center justify-center">
            <EmptyState
              icon={MessageSquare}
              message="Selecione uma conversa para começar"
            />
          </div>
        )}
      </Card>
    </div>
  )
}
