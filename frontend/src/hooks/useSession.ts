'use client'

import { useState, useCallback, useEffect, useRef } from 'react'
import {
  listSessions,
  getSessionStatus,
  createSession,
  startSession,
  stopSession,
  restartSession,
} from '@/services/wahaService'
import type { WahaSession, SessionStatus } from '@/types/waha'

interface UseSessionOptions {
  sessionName?: string
  autoRefresh?: boolean
  refreshInterval?: number
}

interface UseSessionReturn {
  sessions: WahaSession[]
  currentSession: SessionStatus | null
  isLoading: boolean
  isPolling: boolean
  error: string | null
  startSession: () => Promise<void>
  stopSession: () => Promise<void>
  restartSession: () => Promise<void>
  refresh: () => Promise<void>
}

/**
 * Hook for managing WhatsApp sessions with smart polling.
 * Automatically intensifies polling when session is in intermediate states (STARTING, SCAN_QR_CODE).
 */
export function useSession(options: UseSessionOptions = {}): UseSessionReturn {
  const {
    sessionName = 'default',
    autoRefresh = false,
    refreshInterval = 30000, // 30s default for stable states
  } = options

  const [sessions, setSessions] = useState<WahaSession[]>([])
  const [currentSession, setCurrentSession] = useState<SessionStatus | null>(
    null
  )
  const [isLoading, setIsLoading] = useState(false)
  const [isPolling, setIsPolling] = useState(false)
  const [error, setError] = useState<string | null>(null)
  
  // Use ref to track current status for the polling interval without closure staleness
  const statusRef = useRef<string | null>(null)
  useEffect(() => {
    statusRef.current = currentSession?.status || null
  }, [currentSession?.status])

  const fetchSessions = useCallback(async () => {
    try {
      const data = await listSessions()
      setSessions(data)
    } catch (err) {
      console.error('[useSession] Failed to fetch sessions:', err)
    }
  }, [])

  const fetchSessionStatus = useCallback(async (isSilent = false) => {
    if (!isSilent) setIsLoading(true)
    try {
      setError(null)
      const status = await getSessionStatus(sessionName)
      setCurrentSession(status)
      return status
    } catch (err: unknown) {
      const errTyped = err as { status?: number } & Error
      
      // Auto-create session if not found (404)
      if (
        errTyped?.status === 404 ||
        (err instanceof Error && err.message.includes('404'))
      ) {
        try {
          console.log(`[useSession] Session '${sessionName}' not found, creating...`)
          await createSession({ name: sessionName })
          const status = await getSessionStatus(sessionName)
          setCurrentSession(status)
          return status
        } catch (createErr) {
          console.error('[useSession] Failed to create session:', createErr)
          setError(createErr instanceof Error ? createErr.message : 'Erro ao criar sessão')
        }
      } else {
        const message = err instanceof Error ? err.message : 'Erro ao buscar status'
        setError(message)
      }
      return null
    } finally {
      if (!isSilent) setIsLoading(false)
    }
  }, [sessionName])

  const handleStartSession = useCallback(async (): Promise<void> => {
    try {
      setError(null)
      setIsLoading(true)
      const status = await startSession(sessionName)
      setCurrentSession(status)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Erro ao iniciar sessão'
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
      const message = err instanceof Error ? err.message : 'Erro ao parar sessão'
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
      const message = err instanceof Error ? err.message : 'Erro ao reiniciar sessão'
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [sessionName, fetchSessionStatus])

  const refresh = useCallback(async (): Promise<void> => {
    setIsLoading(true)
    try {
      await Promise.all([fetchSessions(), fetchSessionStatus(true)])
    } finally {
      setIsLoading(false)
    }
  }, [fetchSessions, fetchSessionStatus])

  // Initial Fetch
  useEffect(() => {
    let mounted = true
    const init = async () => {
      if (mounted) {
        setIsLoading(true)
        await Promise.all([fetchSessions(), fetchSessionStatus(true)])
        setIsLoading(false)
      }
    }
    init()
    return () => { mounted = false }
  }, [fetchSessions, fetchSessionStatus])

  // Smart Polling Logic
  useEffect(() => {
    let intervalId: NodeJS.Timeout | null = null
    
    const startPolling = () => {
      if (intervalId) clearInterval(intervalId)
      
      const poll = async () => {
        setIsPolling(true)
        await fetchSessionStatus(true)
        setIsPolling(false)
        
        // Re-calculate interval based on new status
        const currentStatus = statusRef.current
        let nextInterval = refreshInterval
        
        // Intensify polling for intermediate states
        if (currentStatus === 'STARTING' || currentStatus === 'SCAN_QR_CODE') {
          nextInterval = 5000 // 5 seconds for transition states
        } else if (!autoRefresh && currentStatus === 'WORKING') {
          // If autoRefresh is off, we can stop polling once working
          return 
        } else if (!autoRefresh && currentStatus === 'STOPPED') {
          // If autoRefresh is off, we can stop polling once stopped
          return
        }
        
        intervalId = setTimeout(poll, nextInterval)
      }
      
      intervalId = setTimeout(poll, 5000)
    }

    // Always poll if in intermediate state, OR if autoRefresh is enabled
    const shouldPoll = 
      autoRefresh || 
      statusRef.current === 'STARTING' || 
      statusRef.current === 'SCAN_QR_CODE'

    if (shouldPoll) {
      startPolling()
    }

    return () => {
      if (intervalId) clearTimeout(intervalId)
    }
  }, [autoRefresh, refreshInterval, fetchSessionStatus])

  return {
    sessions,
    currentSession,
    isLoading,
    isPolling,
    error,
    startSession: handleStartSession,
    stopSession: handleStopSession,
    restartSession: handleRestartSession,
    refresh,
  }
}

