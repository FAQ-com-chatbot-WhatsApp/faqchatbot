"use client"

import { useState, useMemo, useEffect } from "react"
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
import { sendTextMessage } from "@/services/wahaService"

type FilterType = "all" | "unread" | "groups" | "favorites"

export default function MessagesPage() {
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null)
  const [searchQuery, setSearchQuery] = useState("")
  const [activeFilter, setActiveFilter] = useState<FilterType>("all")
  const [isSending, setIsSending] = useState(false)
  const [favorites, setFavorites] = useState<Set<string>>(new Set())

  // Carregar favoritos do localStorage ao iniciar
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

  // Salvar favoritos no localStorage sempre que mudar
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

  const {
    messages,
    isLoading: isLoadingMessages,
    error: messagesError,
    refresh: refreshMessages,
  } = useConversationMessages({
    conversationId: selectedConversation?.id,
    enabled: !!selectedConversation?.id,
  })

  const handleSendMessage = async (text: string) => {
    if (!selectedConversation || !text.trim()) return

    setIsSending(true)
    try {
      const chatId = `${selectedConversation.phone_number}@c.us`
      await sendTextMessage({
        chat_id: chatId,
        text: text.trim(),
      })

      // Atualizar mensagens após envio
      await refreshMessages()
    } catch (error) {
      console.error("Erro ao enviar mensagem:", error)
      alert("Erro ao enviar mensagem. Tente novamente.")
    } finally {
      setIsSending(false)
    }
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

  // Contadores de conversas por tipo - ATUALIZA DINAMICAMENTE
  const conversationCounts = useMemo(() => {
    const unread = conversations.filter(c => (c.unread_count || 0) > 0).length
    const groups = 0 // TODO: Implementar quando tivermos grupos
    const favoritesCount = conversations.filter(c => favorites.has(c.id)).length

    return { unread, groups, favorites: favoritesCount }
  }, [conversations, favorites])

  // Filtrar conversas baseado no filtro ativo - FUNCIONA DE VERDADE
  const filteredConversations = useMemo(() => {
    switch (activeFilter) {
      case "unread":
        return conversations.filter(c => (c.unread_count || 0) > 0)
      case "groups":
        return [] // TODO: Implementar filtro de grupos
      case "favorites":
        return conversations.filter(c => favorites.has(c.id))
      default:
        return conversations
    }
  }, [conversations, activeFilter, favorites])

  const getInitials = (name?: string) => {
    if (!name) return "??"
    const parts = name.split(" ")
    return parts.length > 1
      ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
      : name.slice(0, 2).toUpperCase()
  }

  const formatPhoneNumber = (phone: string) => {
    // Remove prefixo de país se tiver
    const cleaned = phone.replace(/^\+?55/, '')

    // Formata como (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
    if (cleaned.length === 11) {
      return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 7)}-${cleaned.slice(7)}`
    } else if (cleaned.length === 10) {
      return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 6)}-${cleaned.slice(6)}`
    }
    return phone
  }

  const formatTimestamp = (timestamp: string) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

    if (diffInHours < 24) {
      return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
    } else {
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
    }
  }

  return (
    <div className="flex h-[calc(100vh-4rem)] gap-4 p-4">
      {/* 1. Lista de Conversas */}
      <Card className="w-80 flex flex-col shadow-sm">
        <div className="p-4 border-b space-y-3">
          <div className="relative">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar conversa..."
              className="pl-9"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>

          {/* Filtros estilo WhatsApp - FUNCIONAM DE VERDADE */}
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
                    ? "Nenhum favorito ainda. Passe o mouse e clique na estrela!"
                    : "Nenhuma conversa encontrada"
              }
            />
          ) : (
            filteredConversations.map((conv) => (
              <div key={conv.id} className="relative group">
                <ConversationItem
                  name={conv.lead_name || formatPhoneNumber(conv.phone_number)}
                  initials={conv.phone_number.slice(-2)}
                  lastMessage={conv.last_message || undefined}
                  unreadCount={conv.unread_count || 0}
                  isActive={selectedConversation?.id === conv.id}
                  isOnline={conv.status === "active"}
                  onClick={() => setSelectedConversation(conv)}
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

      {/* 2. Área de Chat */}
      <Card className="flex-1 flex flex-col shadow-sm">
        {selectedConversation ? (
          <>
            <ChatHeader
              name={selectedConversation.lead_name || formatPhoneNumber(selectedConversation.phone_number)}
              initials={selectedConversation.phone_number.slice(-2)}
              status={selectedConversation.status === "active" ? "online" : "offline"}
              isOnline={selectedConversation.status === "active"}
              onMore={refreshMessages}
            />

            {/* Feedback de Erro */}
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
                messages.map((msg: ConversationMessage) => (
                  <MessageBubble
                    key={msg.id}
                    sender={msg.direction === "OUTBOUND" ? "user" : "other"}
                    message={msg.body}
                    timestamp={formatTimestamp(msg.created_at)}
                    senderName={msg.direction === "INBOUND" ? msg.from_phone : undefined}
                  />
                ))
              )}
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
