/**
 * useAnalytics Hook
 * 
 * React hook for analytics and metrics data management.
 * Follows DRY principle with reusable fetch logic.
 * 
 * @example
 * ```tsx
 * const { realtime, dashboard, conversion, isLoading, error } = useAnalytics({
 *   period: '30d',
 *   autoRefresh: true
 * })
 * ```
 */

import { useState, useEffect, useCallback, useRef } from 'react'
import { analyticsService } from '@/services/analyticsService'
import type {
  RealtimeDashboard,
  DashboardSummary,
  ConversionFunnel,
  TimeToConversion,
  ConversionBySourceResponse,
  LostLeadsResponse,
  ConversionTrend,
  BotAutonomy,
  BotResponseTime,
  HandoffRate,
  ConversationsByStatus,
  PeakHours,
  ConversationAnalysisReport,
  PerformanceReport,
} from '@/types/analytics'

// =============================================================================
// TYPES
// =============================================================================

interface UseAnalyticsOptions {
  period?: '7d' | '30d' | '90d'
  startDate?: string
  endDate?: string
  autoRefresh?: boolean
  refreshInterval?: number // milliseconds
}

interface AnalyticsState {
  // Realtime
  realtime: RealtimeDashboard | null
  
  // Dashboard
  dashboard: DashboardSummary | null
  
  // Conversion
  conversionFunnel: ConversionFunnel | null
  timeToConversion: TimeToConversion | null
  conversionBySource: ConversionBySourceResponse | null
  lostLeads: LostLeadsResponse | null
  conversionTrend: ConversionTrend | null
  
  // Bot Performance
  botAutonomy: BotAutonomy | null
  botResponseTime: BotResponseTime | null
  handoffRate: HandoffRate | null
  
  // Conversations
  conversationsByStatus: ConversationsByStatus | null
  peakHours: PeakHours | null
  conversationReport: ConversationAnalysisReport | null
  
  // Performance
  performanceReport: PerformanceReport | null
}

// =============================================================================
// HOOK
// =============================================================================

export function useAnalytics(options: UseAnalyticsOptions = {}) {
  const {
    period = '30d',
    startDate,
    endDate,
    autoRefresh = false,
    refreshInterval = 30000, // 30 seconds default
  } = options

  // State
  const [state, setState] = useState<AnalyticsState>({
    realtime: null,
    dashboard: null,
    conversionFunnel: null,
    timeToConversion: null,
    conversionBySource: null,
    lostLeads: null,
    conversionTrend: null,
    botAutonomy: null,
    botResponseTime: null,
    handoffRate: null,
    conversationsByStatus: null,
    peakHours: null,
    conversationReport: null,
    performanceReport: null,
  })

  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Refs for auto-refresh
  const refreshTimerRef = useRef<NodeJS.Timeout | null>(null)

  // Build params helper (DRY)
  const buildParams = useCallback(() => {
    return {
      start_date: startDate,
      end_date: endDate,
      period: !startDate && !endDate ? period : undefined,
    }
  }, [startDate, endDate, period])

  // =============================================================================
  // REALTIME DATA
  // =============================================================================

  const fetchRealtimeDashboard = useCallback(async () => {
    try {
      const data = await analyticsService.realtime.getDashboard()
      setState(prev => ({ ...prev, realtime: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch realtime dashboard'
      setError(message)
      throw err
    }
  }, [])

  // =============================================================================
  // DASHBOARD DATA
  // =============================================================================

  const fetchDashboardSummary = useCallback(async () => {
    try {
      const data = await analyticsService.dashboard.getSummary(buildParams())
      setState(prev => ({ ...prev, dashboard: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch dashboard summary'
      setError(message)
      throw err
    }
  }, [buildParams])

  // =============================================================================
  // CONVERSION DATA
  // =============================================================================

  const fetchConversionFunnel = useCallback(async () => {
    try {
      const data = await analyticsService.conversion.getFunnel(buildParams())
      setState(prev => ({ ...prev, conversionFunnel: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch conversion funnel'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchTimeToConversion = useCallback(async () => {
    try {
      const data = await analyticsService.conversion.getTimeToConversion(buildParams())
      setState(prev => ({ ...prev, timeToConversion: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch time to conversion'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchConversionBySource = useCallback(async () => {
    try {
      const data = await analyticsService.conversion.getBySource(buildParams())
      setState(prev => ({ ...prev, conversionBySource: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch conversion by source'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchLostLeads = useCallback(async () => {
    try {
      const data = await analyticsService.conversion.getLostLeads(buildParams())
      setState(prev => ({ ...prev, lostLeads: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch lost leads'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchConversionTrend = useCallback(async (granularity: 'day' | 'week' | 'month' = 'day') => {
    try {
      const data = await analyticsService.conversion.getTrend({ ...buildParams(), granularity })
      setState(prev => ({ ...prev, conversionTrend: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch conversion trend'
      setError(message)
      throw err
    }
  }, [buildParams])

  // =============================================================================
  // BOT PERFORMANCE DATA
  // =============================================================================

  const fetchBotAutonomy = useCallback(async () => {
    try {
      const data = await analyticsService.bot.getAutonomy(buildParams())
      setState(prev => ({ ...prev, botAutonomy: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch bot autonomy'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchBotResponseTime = useCallback(async () => {
    try {
      const data = await analyticsService.bot.getResponseTime(buildParams())
      setState(prev => ({ ...prev, botResponseTime: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch bot response time'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchHandoffRate = useCallback(async () => {
    try {
      const data = await analyticsService.bot.getHandoffRate(buildParams())
      setState(prev => ({ ...prev, handoffRate: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch handoff rate'
      setError(message)
      throw err
    }
  }, [buildParams])

  // =============================================================================
  // CONVERSATION DATA
  // =============================================================================

  const fetchConversationsByStatus = useCallback(async () => {
    try {
      const data = await analyticsService.conversation.getByStatus(buildParams())
      setState(prev => ({ ...prev, conversationsByStatus: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch conversations by status'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchPeakHours = useCallback(async () => {
    try {
      const data = await analyticsService.conversation.getPeakHours(buildParams())
      setState(prev => ({ ...prev, peakHours: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch peak hours'
      setError(message)
      throw err
    }
  }, [buildParams])

  const fetchConversationReport = useCallback(async () => {
    try {
      const data = await analyticsService.conversation.getReport(buildParams())
      setState(prev => ({ ...prev, conversationReport: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch conversation report'
      setError(message)
      throw err
    }
  }, [buildParams])

  // =============================================================================
  // PERFORMANCE REPORT
  // =============================================================================

  const fetchPerformanceReport = useCallback(async () => {
    try {
      const data = await analyticsService.performance.getReport(buildParams())
      setState(prev => ({ ...prev, performanceReport: data }))
      setError(null)
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch performance report'
      setError(message)
      throw err
    }
  }, [buildParams])

  // =============================================================================
  // AGGREGATE LOADERS (DRY - avoid multiple calls)
  // =============================================================================

  /**
   * Load all dashboard data (KPIs + Conversion + Bot + Status + Peak Hours)
   * Use this for initial dashboard load
   */
  const loadDashboardData = useCallback(async () => {
    setIsLoading(true)
    setError(null)

    try {
      await Promise.all([
        fetchDashboardSummary(),
        fetchConversionFunnel(),
        fetchBotAutonomy(),
        fetchBotResponseTime(),
        fetchConversationsByStatus(),
        fetchPeakHours(),
      ])
    } catch (err) {
      console.error('Failed to load dashboard data:', err)
    } finally {
      setIsLoading(false)
    }
  }, [fetchDashboardSummary, fetchConversionFunnel, fetchBotAutonomy, fetchBotResponseTime, fetchConversationsByStatus, fetchPeakHours])

  /**
   * Load all conversion analytics
   */
  const loadConversionData = useCallback(async () => {
    setIsLoading(true)
    setError(null)

    try {
      await Promise.all([
        fetchConversionFunnel(),
        fetchTimeToConversion(),
        fetchConversionBySource(),
        fetchLostLeads(),
        fetchConversionTrend(),
      ])
    } catch (err) {
      console.error('Failed to load conversion data:', err)
    } finally {
      setIsLoading(false)
    }
  }, [
    fetchConversionFunnel,
    fetchTimeToConversion,
    fetchConversionBySource,
    fetchLostLeads,
    fetchConversionTrend,
  ])

  /**
   * Load all bot performance analytics
   */
  const loadBotPerformanceData = useCallback(async () => {
    setIsLoading(true)
    setError(null)

    try {
      await Promise.all([
        fetchBotAutonomy(),
        fetchBotResponseTime(),
        fetchHandoffRate(),
        fetchConversationsByStatus(),
      ])
    } catch (err) {
      console.error('Failed to load bot performance data:', err)
    } finally {
      setIsLoading(false)
    }
  }, [fetchBotAutonomy, fetchBotResponseTime, fetchHandoffRate, fetchConversationsByStatus])

  /**
   * Refresh all data
   */
  const refresh = useCallback(async () => {
    setIsLoading(true)
    setError(null)

    try {
      if (autoRefresh) {
        await fetchRealtimeDashboard()
      }
      await loadDashboardData()
    } catch (err) {
      console.error('Failed to refresh analytics:', err)
    } finally {
      setIsLoading(false)
    }
  }, [autoRefresh, fetchRealtimeDashboard, loadDashboardData])

  // =============================================================================
  // EFFECTS
  // =============================================================================

  // Initial load
  useEffect(() => {
    loadDashboardData()
  }, [loadDashboardData])

  // Auto-refresh for realtime data
  useEffect(() => {
    if (autoRefresh) {
      fetchRealtimeDashboard()

      refreshTimerRef.current = setInterval(() => {
        fetchRealtimeDashboard()
      }, refreshInterval)

      return () => {
        if (refreshTimerRef.current) {
          clearInterval(refreshTimerRef.current)
        }
      }
    }
  }, [autoRefresh, refreshInterval, fetchRealtimeDashboard])

  // =============================================================================
  // RETURN
  // =============================================================================

  return {
    // State
    ...state,
    isLoading,
    error,

    // Individual fetchers
    fetchRealtimeDashboard,
    fetchDashboardSummary,
    fetchConversionFunnel,
    fetchTimeToConversion,
    fetchConversionBySource,
    fetchLostLeads,
    fetchConversionTrend,
    fetchBotAutonomy,
    fetchBotResponseTime,
    fetchHandoffRate,
    fetchConversationsByStatus,
    fetchPeakHours,
    fetchConversationReport,
    fetchPerformanceReport,

    // Aggregate loaders
    loadDashboardData,
    loadConversionData,
    loadBotPerformanceData,

    // Utility
    refresh,
  }
}
