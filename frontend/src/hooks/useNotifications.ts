"use client"

import { useState, useEffect, useCallback, useRef } from "react"
import { getNotifications, getUnreadCount, markNotificationAsRead, type Notification } from "@/services/notificationService"
import { toast } from "sonner"

export function useNotifications() {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [unreadCount, setUnreadCount] = useState(0)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  
  // Usar ref para rastrear a ID da última notificação processada para evitar toast duplicado
  const lastNotifiedId = useRef<string | null>(null)

  const fetchNotifications = useCallback(async () => {
    // Apenas marca erro se falhar, não mudamos isLoading para não ter flicker no polling
    try {
      const [data, countData] = await Promise.all([
        getNotifications({ limit: 10 }),
        getUnreadCount()
      ])
      
      // Detecção de NOVAS notificações do tipo HANDOFF_REQUIRED para o popup
      if (data.length > 0) {
          const newest = data[0]
          
          // Se é uma nova notificação (ID diferente do último toast exibido) e não foi lida
          if (newest && !newest.read && newest.id !== lastNotifiedId.current) {
              lastNotifiedId.current = newest.id
              
              if (newest.type === "HANDOFF_REQUIRED" || newest.type === "HANDOFF_URGENT") {
                  const isUrgent = newest.type === "HANDOFF_URGENT";
                  const toastFn = isUrgent ? toast.error : toast.success;
                  
                  toastFn(newest.title, {
                    description: newest.message,
                    duration: isUrgent ? 30000 : 15000, 
                    action: {
                        label: "Atender",
                        onClick: () => {
                            // Marcar como lida antes de navegar para limpar o header
                            markNotificationAsRead(newest.id).catch(() => {});
                            window.location.href = `/messages?conversationId=${newest.entity_id}`;
                        }
                    }
                  })
              }
          }
      }

      setNotifications(data || [])
      setUnreadCount(countData.count || 0)
    } catch (err: any) {
      setError(err.message || "Erro ao carregar notificações")
      console.error("Erro no polling de notificações:", err)
    }
  }, [])

  const markAsRead = async (id: string) => {
    try {
      await markNotificationAsRead(id)
      setNotifications(prev => 
        prev.map(n => n.id === id ? { ...n, read: true } : n)
      )
      setUnreadCount(prev => Math.max(0, prev - 1))
    } catch (err) {
      console.error("Erro ao marcar como lida", err)
      toast.error("Não foi possível marcar a notificação como lida")
    }
  }

  useEffect(() => {
    setIsLoading(true)
    fetchNotifications().finally(() => setIsLoading(false))
    
    // Polling a cada 10 segundos para dados em tempo real (KISS)
    const interval = setInterval(fetchNotifications, 10000)
    return () => clearInterval(interval)
  }, [fetchNotifications])

  return {
    notifications,
    unreadCount,
    isLoading,
    error,
    markAsRead,
    refresh: fetchNotifications
  }
}
