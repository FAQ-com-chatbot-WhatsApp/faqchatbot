"use client"

import { useState } from "react"
import { Search, AlertCircle, MessageSquare } from "lucide-react"
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

export default function MessagesPage() {
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null)
  const [searchQuery, setSearchQuery] = useState("")

  console.log('[MessagesPage] selectedConversation:', selectedConversation?.id)

  const {
    conversations,
    isLoading: isLoadingConversations,
    error: conversationsError,
    refresh: refreshConversations,
  } = useConversations({
    enabled: true,
    search: searchQuery,
  })

  console.log('[MessagesPage] conversations:', conversations.length)

  const {
    messages,
    isLoading: isLoadingMessages,
    error: messagesError,
    refresh: refreshMessages,
  } = useConversationMessages({
    conversationId: selectedConversation?.id,
    enabled: !!selectedConversation?.id,
  })

  console.log('[MessagesPage] messages:', messages.length, messages)

  const handleSendMessage = async (text: string) => {
    // TODO: Implementar envio via API backend
    console.log("Enviar mensagem:", text)
  }

  const formatTimestamp = (isoString: string) => {
    const date = new Date(isoString)
    return date.toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  const getInitials = (name?: string) => {
    if (!name) return "??"
    const parts = name.split(" ")
    return parts.length > 1
      ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
      : name.slice(0, 2).toUpperCase()
  }

  const selectedChatId = selectedConversation?.phone_number
    ? `${selectedConversation.phone_number}@c.us`
    : null

  return (
    <div className="flex h-[calc(100vh-80px)] gap-4 p-4">
      {/* 1. Lista de Conversas */}
      <Card className="w-80 flex flex-col shadow-sm">
        <div className="p-4 border-b">
          <div className="relative">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar conversa..."
              className="pl-9"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
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
          ) : conversations.length === 0 ? (
            <EmptyState icon={MessageSquare} message="Nenhuma conversa encontrada" />
          ) : (
            conversations.map((conv) => (
              <ConversationItem
                key={conv.id}
                name={conv.phone_number}
                initials={getInitials(conv.phone_number)}
                lastMessage={undefined}
                unreadCount={0}
                isActive={selectedConversation?.id === conv.id}
                isOnline={conv.status === "active"}
                onClick={() => setSelectedConversation(conv)}
              />
            ))
          )}
        </div>
      </Card>

      {/* 2. Área de Chat */}
      <Card className="flex-1 flex flex-col shadow-sm">
        {selectedConversation ? (
          <>
            <ChatHeader
              name={selectedConversation.phone_number}
              initials={getInitials(selectedConversation.phone_number)}
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
              disabled={isLoadingMessages}
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
