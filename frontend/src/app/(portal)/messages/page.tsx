"use client"

import { useState, FormEvent } from "react"
import { Send, User, Search, MoreVertical, Loader2, AlertCircle } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { useMessages } from "@/hooks/useMessages"
import type { WahaMessage } from "@/types/waha"

export default function MessagesPage() {
  const [message, setMessage] = useState("")
  const [selectedChatId, setSelectedChatId] = useState<string>("5511999999999@c.us")
  const [isSending, setIsSending] = useState(false)

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

  const handleSendMessage = async (e: FormEvent) => {
    e.preventDefault()
    if (!message.trim() || isSending) return

    setIsSending(true)
    try {
      await sendMessage(message.trim())
      setMessage("")
    } catch (err) {
      console.error("Erro ao enviar mensagem:", err)
    } finally {
      setIsSending(false)
    }
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
          {/* Exemplo de item de conversa */}
          <div 
            className="p-4 flex items-center gap-3 hover:bg-accent cursor-pointer border-b"
            onClick={() => setSelectedChatId("5511999999999@c.us")}
          >
            <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
              <User className="h-6 w-6 text-primary" />
            </div>
            <div className="flex-1 overflow-hidden">
              <p className="font-medium truncate">Cliente Teste</p>
              <p className="text-xs text-muted-foreground truncate">
                {messages.length > 0 ? messages[messages.length - 1].body : "Sem mensagens"}
              </p>
            </div>
          </div>
        </div>
      </Card>

      {/* 2. Janela de Chat */}
      <Card className="flex-1 flex flex-col shadow-sm relative">
        {/* Cabeçalho do Chat */}
        <div className="p-4 border-b flex justify-between items-center bg-muted/30">
          <div className="flex items-center gap-3">
            <div className="h-8 w-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold">
              CT
            </div>
            <span className="font-semibold">Cliente Teste</span>
          </div>
          <Button variant="ghost" size="icon" onClick={refresh} disabled={isLoading}>
            {isLoading ? (
              <Loader2 className="h-5 w-5 animate-spin" />
            ) : (
              <MoreVertical className="h-5 w-5" />
            )}
          </Button>
        </div>

        {/* Feedback de Erro */}
        {error && (
          <Alert variant="destructive" className="m-4">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        {/* Área de Mensagens */}
        <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
          {isLoading && messages.length === 0 ? (
            <div className="flex items-center justify-center h-full">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : messages.length === 0 ? (
            <div className="flex items-center justify-center h-full text-muted-foreground">
              Nenhuma mensagem ainda
            </div>
          ) : (
            messages.map((msg: WahaMessage) => (
              <div
                key={msg.id}
                className={`flex ${msg.fromMe ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`p-3 rounded-2xl max-w-[70%] shadow-sm ${
                    msg.fromMe
                      ? "bg-primary text-primary-foreground rounded-tr-none shadow-md"
                      : "bg-card border rounded-tl-none"
                  }`}
                >
                  <p className="text-sm">{msg.body}</p>
                  <span
                    className={`text-[10px] mt-1 block ${
                      msg.fromMe ? "opacity-70 text-right" : "text-muted-foreground"
                    }`}
                  >
                    {formatTimestamp(msg.timestamp)}
                  </span>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Campo de Envio */}
        <div className="p-4 border-t bg-card">
          <form className="flex gap-2" onSubmit={handleSendMessage}>
            <Input
              placeholder="Digite sua mensagem..."
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="flex-1"
              disabled={isSending || isLoading}
            />
            <Button type="submit" size="icon" disabled={isSending || isLoading || !message.trim()}>
              {isSending ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Send className="h-4 w-4" />
              )}
            </Button>
          </form>
        </div>
      </Card>
    </div>
  )
}
