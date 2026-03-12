"use client"

import { useState, useEffect, useCallback } from "react"
import { getLeads, type Lead } from "@/services/leadService"

interface UseLeadsOptions {
  page?: number
  size?: number
  maturity_level?: string
  search?: string
  enabled?: boolean
}

interface UseLeadsReturn {
  leads: Lead[]
  isLoading: boolean
  error: string | null
  total: number
  pages: number
  refresh: () => Promise<void>
}

export function useLeads(options: UseLeadsOptions = {}): UseLeadsReturn {
  const { page = 1, size = 50, maturity_level, search, enabled = true } = options

  const [leads, setLeads] = useState<Lead[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [total, setTotal] = useState(0)
  const [pages, setPages] = useState(0)

  const loadLeads = useCallback(async () => {
    if (!enabled) return

    setIsLoading(true)
    setError(null)

    try {
      const response = await getLeads({ page, size, maturity_level, search })
      setLeads(response.items || [])
      setTotal(response.total || 0)
      setPages(response.pages || 0)
    } catch (err: any) {
      setError(err.message || "Erro ao carregar leads")
      console.error("Erro ao carregar leads:", err)
    } finally {
      setIsLoading(false)
    }
  }, [page, size, maturity_level, search, enabled])

  useEffect(() => {
    loadLeads()
  }, [loadLeads])

  const refresh = useCallback(async () => {
    await loadLeads()
  }, [loadLeads])

  return {
    leads,
    isLoading,
    error,
    total,
    pages,
    refresh,
  }
}
