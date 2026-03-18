'use client'

import { useState, useCallback, useEffect } from 'react'
import {
  listSessions,
  getSessionStatus,
  createSession,
  startSession,
  stopSession,
  restartSession,
} from '@/services/wahaService'
import type { WahaSession, SessionStatus, SessionCreate } from '@/types/waha'

interface UseSessionOptions {
  sessionName?: string
  autoRefresh?: boolean
  refreshInterval?: number
}

interface UseSessionReturn {
  sessions: WahaSession[]
  currentSession: SessionStatus | null
  isLoading: boolean
  error: string | null
  startSession: () => Promise<void>
  stopSession: () => Promise<void>
  restartSession: () => Promise<void>
  refresh: () => Promise<void>
}

export function useSession(options: UseSessionOptions = {}): UseSessionReturn {
  const {
    sessionName = 'default',
    autoRefresh = false,
    refreshInterval = 5000,
  } = options

  const [sessions, setSessions] = useState<WahaSession[]>([])
  const [currentSession, setCurrentSession] = useState<SessionStatus | null>(
    null
  )
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchSessions = useCallback(async () => {
    try {
      setError(null)
      const data = await listSessions()
      setSessions(data)
    } catch (err) {
      const message =
        err instanceof Error ? err.message : 'Erro ao carregar sessões'
      setError(message)
    }
  }, [])

  const fetchSessionStatus = useCallback(async () => {
    try {
      setError(null)
      const status = await getSessionStatus(sessionName)
      setCurrentSession(status)
    } catch (err: any) {
      // Se a sessão não existe (404), criar automaticamente
      if (
        err?.status === 404 ||
        (err instanceof Error && err.message.includes('404'))
      ) {
        try {
          console.log(
            `[useSession] Session '${sessionName}' not found, creating...`
          )
          await createSession({
            name: sessionName,
          })
          // Retentar buscar status após criação
          const status = await getSessionStatus(sessionName)
          setCurrentSession(status)
          console.log(
            `[useSession] Session '${sessionName}' created successfully`
          )
          return
        } catch (createErr) {
          console.error('[useSession] Failed to create session:', createErr)
          const message =
            createErr instanceof Error
              ? createErr.message
              : 'Erro ao criar sessão WhatsApp'
          setError(message)
          setCurrentSession(null)
          return
        }
      }
      const message =
        err instanceof Error ? err.message : 'Erro ao carregar status da sessão'
      setError(message)
    }
  }, [sessionName])

  const handleStartSession = useCallback(async (): Promise<void> => {
    try {
      setError(null)
      setIsLoading(true)
      const status = await startSession(sessionName)
      setCurrentSession(status)
    } catch (err) {
      const message =
        err instanceof Error ? err.message : 'Erro ao iniciar sessão'
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [sessionName])

  const handleStopSession = useCallback(async (): Promise<void> => {
    try {
      setError(null)
      setIsLoading(true)
      await stopSession(sessionName)
      await fetchSessionStatus()
    } catch (err) {
      const message =
        err instanceof Error ? err.message : 'Erro ao parar sessão'
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [sessionName, fetchSessionStatus])

  const handleRestartSession = useCallback(async (): Promise<void> => {
    try {
      setError(null)
      setIsLoading(true)
      await restartSession(sessionName)
      await fetchSessionStatus()
    } catch (err) {
      const message =
        err instanceof Error ? err.message : 'Erro ao reiniciar sessão'
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [sessionName, fetchSessionStatus])

  const refresh = useCallback(async (): Promise<void> => {
    setIsLoading(true)
    try {
      await Promise.all([fetchSessions(), fetchSessionStatus()])
    } finally {
      setIsLoading(false)
    }
  }, [fetchSessions, fetchSessionStatus])

  useEffect(() => {
    refresh()
  }, [refresh])

  // Auto-refresh genérico (se habilitado)
  useEffect(() => {
    if (!autoRefresh) return

    const interval = setInterval(() => {
      fetchSessionStatus()
    }, refreshInterval)

    return () => clearInterval(interval)
  }, [autoRefresh, refreshInterval, fetchSessionStatus])

  // Auto-refresh específico para QR Code (sempre ativo quando status = SCAN_QR_CODE)
  useEffect(() => {
    if (currentSession?.status !== 'SCAN_QR_CODE') return

    // QR expira em ~90 segundos, atualizar a cada 15s
    const interval = setInterval(() => {
      console.log('[useSession] Refreshing QR code...')
      fetchSessionStatus()
    }, 15000)

    return () => clearInterval(interval)
  }, [currentSession?.status, fetchSessionStatus])

  return {
    sessions,
    currentSession,
    isLoading,
    error,
    startSession: handleStartSession,
    stopSession: handleStopSession,
    restartSession: handleRestartSession,
    refresh,
  }
}
