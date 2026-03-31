"use client"

import { useState, useEffect, useCallback } from "react"
import { getConversations, getConversationMessages, type Conversation, type ConversationMessage } from "@/services/conversationService"

interface UseConversationsOptions {
  page?: number
  size?: number
  status?: string
  search?: string
  enabled?: boolean
}

interface UseConversationsReturn {
  conversations: Conversation[]
  isLoading: boolean
  error: string | null
  total: number
  pages: number
  refresh: () => Promise<void>
}

export function useConversations(options: UseConversationsOptions = {}): UseConversationsReturn {
  const { page = 1, size = 50, status, search, enabled = true } = options

  const [conversations, setConversations] = useState<Conversation[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [total, setTotal] = useState(0)
  const [pages, setPages] = useState(0)

  const loadConversations = useCallback(async (isPolling = false) => {
    if (!enabled) return;

    if (!isPolling) setIsLoading(true)
    setError(null)

    try {
      const response = await getConversations({ page, size, status, search })
      setConversations(response.conversations || [])
      setTotal(response.total || 0)
      setPages(Math.ceil((response.total || 0) / size))
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "Erro ao carregar conversas")
      console.error("Erro ao carregar conversas:", err)
    } finally {
      if (!isPolling) setIsLoading(false)
    }
  }, [page, size, status, search, enabled])

  useEffect(() => {
    loadConversations()
    
    // Polling setup: refresh every 5 seconds
    const interval = setInterval(() => {
      loadConversations(true)
    }, 5000)

    return () => clearInterval(interval)
  }, [loadConversations])

  const refresh = useCallback(async () => {
    await loadConversations()
  }, [loadConversations])

  return {
    conversations,
    isLoading,
    error,
    total,
    pages,
    refresh,
  }
}

interface UseConversationMessagesOptions {
  conversationId?: string
  enabled?: boolean
}

interface UseConversationMessagesReturn {
  messages: ConversationMessage[]
  isLoading: boolean
  error: string | null
  total: number
  refresh: () => Promise<void>
}

export function useConversationMessages(options: UseConversationMessagesOptions = {}): UseConversationMessagesReturn {
  const { conversationId, enabled = true } = options

  const [messages, setMessages] = useState<ConversationMessage[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [total, setTotal] = useState(0)

  const loadMessages = useCallback(async (isPolling = false) => {
    if (!enabled || !conversationId) return;

    if (!isPolling) setIsLoading(true)
    setError(null)

    try {
      const messagesData = await getConversationMessages(conversationId)
      setMessages(messagesData || [])
      setTotal(messagesData.length)
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "Erro ao carregar mensagens")
      console.error("Erro ao carregar mensagens:", err)
    } finally {
      if (!isPolling) setIsLoading(false)
    }
  }, [conversationId, enabled])

  useEffect(() => {
    loadMessages()

    // Polling setup: refresh every 5 seconds
    const interval = setInterval(() => {
      loadMessages(true)
    }, 5000)

    return () => clearInterval(interval)
  }, [loadMessages])

  const refresh = useCallback(async () => {
    await loadMessages()
  }, [loadMessages])

  return {
    messages,
    isLoading,
    error,
    total,
    refresh,
  }
}
