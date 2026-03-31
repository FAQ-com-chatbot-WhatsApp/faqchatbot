"use client"

import { useState, useCallback } from "react"
import {
  getContactAbout,
  getContactPicture,
  checkNumberExists,
  blockContact,
  unblockContact,
} from "@/services/wahaService"
import type { WahaContact } from "@/types/waha"

interface UseContactsOptions {
  enabled?: boolean
}

interface UseContactsReturn {
  contacts: WahaContact[]
  isLoading: boolean
  error: string | null
  checkNumber: (phone: string) => Promise<boolean>
  getAbout: (contactId: string) => Promise<string | null>
  getPicture: (contactId: string) => Promise<string | null>
  block: (contactId: string) => Promise<void>
  unblock: (contactId: string) => Promise<void>
  refresh: () => Promise<void>
}

export function useContacts(options: UseContactsOptions = {}): UseContactsReturn {
  const { enabled = true } = options

  const [contacts, setContacts] = useState<WahaContact[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const checkNumber = useCallback(async (phone: string): Promise<boolean> => {
    if (!enabled) return false
    
    try {
      setError(null)
      const response = await checkNumberExists(phone)
      return response.exists
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao verificar número"
      setError(message)
      throw err
    }
  }, [enabled])

  const getAbout = useCallback(async (contactId: string): Promise<string | null> => {
    if (!enabled) return null
    
    try {
      setError(null)
      const response = await getContactAbout(contactId)
      return response.about
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao buscar informações do contato"
      setError(message)
      return null
    }
  }, [enabled])

  const getPicture = useCallback(async (contactId: string): Promise<string | null> => {
    if (!enabled) return null
    
    try {
      setError(null)
      const response = await getContactPicture(contactId)
      return response.url
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao buscar foto do contato"
      setError(message)
      return null
    }
  }, [enabled])

  const block = useCallback(async (contactId: string): Promise<void> => {
    if (!enabled) return
    
    try {
      setError(null)
      setIsLoading(true)
      await blockContact(contactId)
      
      // Update local state
      setContacts(prev => 
        prev.map(c => c.id === contactId ? { ...c, isBlocked: true } : c)
      )
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao bloquear contato"
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [enabled])

  const unblock = useCallback(async (contactId: string): Promise<void> => {
    if (!enabled) return
    
    try {
      setError(null)
      setIsLoading(true)
      await unblockContact(contactId)
      
      // Update local state
      setContacts(prev => 
        prev.map(c => c.id === contactId ? { ...c, isBlocked: false } : c)
      )
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao desbloquear contato"
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [enabled])

  const refresh = useCallback(async (): Promise<void> => {
    if (!enabled) return
    
    try {
      setError(null)
      setIsLoading(true)
      // TODO: Implement when backend has list contacts endpoint
      // For now, contacts come from the component's mock data
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao atualizar contatos"
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [enabled])

  return {
    contacts,
    isLoading,
    error,
    checkNumber,
    getAbout,
    getPicture,
    block,
    unblock,
    refresh,
  }
}
