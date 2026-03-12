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
import { useMessages } from "@/hooks/useMessages"
import type { WahaMessage } from "@/types/waha"

export default function MessagesPage() {
  const [selectedChatId, setSelectedChatId] = useState<string>("5511999999999@c.us")

  const {
    messages,
    isLoading,
    error,
    sendMessage,
    refresh,
  } = useMessages({
    chatId: selectedChatId,
    enabled: !!selectedChatId,
  })

  const handleSendMessage = async (text: string) => {
    await sendMessage(text)
  }

  const formatTimestamp = (timestamp: number) => {
    const date = new Date(timestamp * 1000)
    return date.toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  return (
    <div className="flex h-[calc(100vh-80px)] gap-4 p-4">
      {/* 1. Lista de Conversas */}
      <Card className="w-80 flex flex-col shadow-sm">
        <div className="p-4 border-b">
          <div className="relative">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Buscar conversa..." className="pl-9" />
          </div>
        </div>
        <div className="flex-1 overflow-y-auto">
          <ConversationItem
            name="Cliente Teste"
            initials="CT"
            lastMessage={messages.length > 0 ? messages[messages.length - 1].body : undefined}
            unreadCount={3}
            isActive={selectedChatId === "5511999999999@c.us"}
            isOnline
            onClick={() => setSelectedChatId("5511999999999@c.us")}
          />
        </div>
      </Card>

      {/* 2. Área de Chat */}
      <Card className="flex-1 flex flex-col shadow-sm">
        <ChatHeader
          name="Cliente Teste"
          initials="CT"
          status="online"
          isOnline
          onMore={refresh}
        />

        {/* Feedback de Erro */}
        {error && (
          <Alert variant="destructive" className="m-4">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
          {isLoading && messages.length === 0 ? (
            <LoadingSpinner />
          ) : messages.length === 0 ? (
            <EmptyState icon={MessageSquare} message="Nenhuma mensagem ainda" />
          ) : (
            messages.map((msg: WahaMessage) => (
              <MessageBubble
                key={msg.id}
                sender={msg.fromMe ? "user" : "other"}
                message={msg.body}
                timestamp={formatTimestamp(msg.timestamp)}
                senderName={msg.fromMe ? undefined : "Cliente Teste"}
              />
            ))
          )}
        </div>

        <MessageInput
          onSend={handleSendMessage}
          placeholder="Digite sua mensagem..."
          disabled={isLoading}
          showAttachment
        />
      </Card>
    </div>
  )
}
