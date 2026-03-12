"use client"

import { useState, useCallback, useEffect } from "react"
import { getCurrentUser, updateCurrentUser } from "@/services/userService"
import type { User, UserUpdate } from "@/types/user"

interface UseUserReturn {
  user: User | null
  isLoading: boolean
  error: string | null
  updateUser: (data: UserUpdate) => Promise<void>
  refresh: () => Promise<void>
}

export function useUser(): UseUserReturn {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchUser = useCallback(async () => {
    try {
      setError(null)
      setIsLoading(true)
      const userData = await getCurrentUser()
      setUser(userData)
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao carregar dados do usuário"
      setError(message)
    } finally {
      setIsLoading(false)
    }
  }, [])

  const updateUser = useCallback(async (data: UserUpdate): Promise<void> => {
    try {
      setError(null)
      setIsLoading(true)
      const updatedUser = await updateCurrentUser(data)
      setUser(updatedUser)
    } catch (err) {
      const message = err instanceof Error ? err.message : "Erro ao atualizar dados do usuário"
      setError(message)
      throw err
    } finally {
      setIsLoading(false)
    }
  }, [])

  const refresh = useCallback(async (): Promise<void> => {
    await fetchUser()
  }, [fetchUser])

  useEffect(() => {
    fetchUser()
  }, [fetchUser])

  return {
    user,
    isLoading,
    error,
    updateUser,
    refresh,
  }
}
