import { useState, useEffect, useCallback } from "react"
import type { WahaMessage, SendTextMessageRequest } from "@/types/waha"
import {
  getChatMessages,
  sendTextMessage,
  deleteMessage,
  editMessage,
} from "@/services/wahaService"

interface UseMessagesProps {
  chatId: string
  enabled?: boolean
}

interface UseMessagesReturn {
  messages: WahaMessage[]
  isLoading: boolean
  error: string | null
  sendMessage: (text: string) => Promise<void>
  deleteMsg: (messageId: string) => Promise<void>
  editMsg: (messageId: string, text: string) => Promise<void>
  refresh: () => Promise<void>
}

export function useMessages({
  chatId,
  enabled = true,
}: UseMessagesProps): UseMessagesReturn {
  const [messages, setMessages] = useState<WahaMessage[]>([])
  const [isLoading, setIsLoading] = useState<boolean>(false)
  const [error, setError] = useState<string | null>(null)

  const loadMessages = useCallback(async () => {
    if (!enabled || !chatId) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await getChatMessages(chatId)
      setMessages(response.messages || [])
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "Erro ao carregar mensagens")
      console.error("Erro ao carregar mensagens:", err)
    } finally {
      setIsLoading(false)
    }
  }, [chatId, enabled])

  useEffect(() => {
    loadMessages()
  }, [loadMessages])

  const sendMessage = useCallback(
    async (text: string) => {
      if (!text.trim()) return

      setError(null)
      try {
        const request: SendTextMessageRequest = {
          chat_id: chatId,
          text,
        }
        const newMessage = await sendTextMessage(request)
        setMessages((prev) => [...prev, newMessage])
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : "Erro ao enviar mensagem")
        throw err
      }
    },
    [chatId]
  )

  const deleteMsg = useCallback(
    async (messageId: string) => {
      setError(null)
      try {
        await deleteMessage(chatId, messageId)
        setMessages((prev) => prev.filter((msg) => msg.id !== messageId))
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : "Erro ao deletar mensagem")
        throw err
      }
    },
    [chatId]
  )

  const editMsg = useCallback(
    async (messageId: string, text: string) => {
      setError(null)
      try {
        const updatedMessage = await editMessage(chatId, messageId, text)
        setMessages((prev) =>
          prev.map((msg) => (msg.id === messageId ? updatedMessage : msg))
        )
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : "Erro ao editar mensagem")
        throw err
      }
    },
    [chatId]
  )

  const refresh = useCallback(async () => {
    await loadMessages()
  }, [loadMessages])

  return {
    messages,
    isLoading,
    error,
    sendMessage,
    deleteMsg,
    editMsg,
    refresh,
  }
}
