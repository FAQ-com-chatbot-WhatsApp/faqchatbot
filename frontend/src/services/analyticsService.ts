/**
 * Analytics Service
 * 
 * Service layer for analytics and metrics API communication.
 * Organized by analytics categories following Single Responsibility Principle.
 */

import { fetchApi } from '@/lib/api'
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

interface DateRangeParams {
  start_date?: string
  end_date?: string
  period?: '7d' | '30d' | '90d'
}

interface GranularityParams extends DateRangeParams {
  granularity?: 'day' | 'week' | 'month'
}

// Helper to build query string
function buildQueryString(params: Record<string, any>): string {
  const filtered = Object.entries(params)
    .filter(([, value]) => value !== undefined && value !== null)
    .map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`)
  
  return filtered.length > 0 ? `?${filtered.join('&')}` : ''
}

// =============================================================================
// REALTIME ANALYTICS
// =============================================================================

export const realtimeAnalytics = {
  /**
   * Get real-time dashboard with live metrics
   * Auto-refresh every 30 seconds recommended
   */
  getDashboard: (): Promise<RealtimeDashboard> => {
    return fetchApi('/api/v1/dashboard/realtime/dashboard')
  },
}

// =============================================================================
// DASHBOARD ANALYTICS
// =============================================================================

export const dashboardAnalytics = {
  /**
   * Get dashboard summary with KPIs
   * Cache: 5 minutes
   */
  getSummary: (params: DateRangeParams = {}): Promise<DashboardSummary> => {
    return fetchApi(`/api/v1/dashboard/dashboard${buildQueryString(params)}`)
  },
}

// =============================================================================
// CONVERSION ANALYTICS
// =============================================================================

export const conversionAnalytics = {
  /**
   * Get conversion funnel with 5 stages + drop-off
   * Cache: 15 minutes
   */
  getFunnel: (params: DateRangeParams = {}): Promise<ConversionFunnel> => {
    return fetchApi(`/api/v1/dashboard/conversion-funnel${buildQueryString(params)}`)
  },

  /**
   * Get time to conversion statistics (extended with p75, p90)
   * Cache: 15 minutes
   */
  getTimeToConversion: (params: DateRangeParams = {}): Promise<TimeToConversion> => {
    return fetchApi(`/api/v1/dashboard/conversion/time-to-conversion-extended${buildQueryString(params)}`)
  },

  /**
   * Get conversion by source/channel (direct, group)
   * Cache: 15 minutes
   */
  getBySource: (params: DateRangeParams = {}): Promise<ConversionBySourceResponse> => {
    return fetchApi(`/api/v1/dashboard/conversion/by-source${buildQueryString(params)}`)
  },

  /**
   * Get lost leads analysis by maturity range
   * Cache: 15 minutes
   */
  getLostLeads: (params: DateRangeParams = {}): Promise<LostLeadsResponse> => {
    return fetchApi(`/api/v1/dashboard/conversion/lost-leads${buildQueryString(params)}`)
  },

  /**
   * Get conversion trend over time
   * Cache: 15 minutes
   */
  getTrend: (params: GranularityParams = {}): Promise<ConversionTrend> => {
    return fetchApi(`/api/v1/dashboard/conversion/trend${buildQueryString(params)}`)
  },
}

// =============================================================================
// BOT PERFORMANCE ANALYTICS
// =============================================================================

export const botAnalytics = {
  /**
   * Get bot autonomy rate (Admin only)
   * Cache: 15 minutes
   */
  getAutonomy: (params: DateRangeParams = {}): Promise<BotAutonomy> => {
    return fetchApi(`/api/v1/dashboard/bot-autonomy${buildQueryString(params)}`)
  },

  /**
   * Get bot response time statistics
   * Cache: 15 minutes
   */
  getResponseTime: (params: DateRangeParams = {}): Promise<BotResponseTime> => {
    return fetchApi(`/api/v1/dashboard/performance/bot-response-time${buildQueryString(params)}`)
  },

  /**
   * Get handoff rate (bot vs human)
   * Cache: 15 minutes
   */
  getHandoffRate: (params: DateRangeParams = {}): Promise<HandoffRate> => {
    return fetchApi(`/api/v1/dashboard/performance/handoff-rate${buildQueryString(params)}`)
  },
}

// =============================================================================
// CONVERSATION ANALYTICS
// =============================================================================

export const conversationAnalytics = {
  /**
   * Get conversations by status distribution
   * Cache: 15 minutes
   */
  getByStatus: (params: DateRangeParams = {}): Promise<ConversationsByStatus> => {
    return fetchApi(`/api/v1/dashboard/performance/conversations-by-status${buildQueryString(params)}`)
  },

  /**
   * Get peak hours analysis
   * Cache: 15 minutes
   */
  getPeakHours: (params: DateRangeParams = {}): Promise<PeakHours> => {
    return fetchApi(`/api/v1/dashboard/performance/peak-hours${buildQueryString(params)}`)
  },

  /**
   * Get activity heatmap (day x hour)
   * Cache: 15 minutes
   */
  getActivityHeatmap: (params: DateRangeParams = {}): Promise<{ data: any }> => {
    return fetchApi(`/api/v1/dashboard/conversation/activity-heatmap${buildQueryString(params)}`)
  },

  /**
   * Get top keywords
   * Cache: 15 minutes
   */
  getKeywords: (params: DateRangeParams & { limit?: number } = {}): Promise<{ keywords: any[] }> => {
    return fetchApi(`/api/v1/dashboard/conversation/keywords${buildQueryString(params)}`)
  },

  /**
   * Get sentiment distribution
   * Cache: 15 minutes
   */
  getSentiment: (params: DateRangeParams = {}): Promise<{ sentiment: any }> => {
    return fetchApi(`/api/v1/dashboard/conversation/sentiment${buildQueryString(params)}`)
  },

  /**
   * Get topics distribution
   * Cache: 15 minutes
   */
  getTopics: (params: DateRangeParams = {}): Promise<{ topics: any[] }> => {
    return fetchApi(`/api/v1/dashboard/conversation/topics${buildQueryString(params)}`)
  },

  /**
   * Get complete conversation analysis report
   * Cache: 30 minutes
   */
  getReport: (params: DateRangeParams = {}): Promise<ConversationAnalysisReport> => {
    return fetchApi(`/api/v1/dashboard/conversation/report${buildQueryString(params)}`)
  },
}

// =============================================================================
// PERFORMANCE REPORTS
// =============================================================================

export const performanceAnalytics = {
  /**
   * Get complete performance report
   * Cache: 15 minutes
   */
  getReport: (params: DateRangeParams = {}): Promise<PerformanceReport> => {
    return fetchApi(`/api/v1/dashboard/performance/report${buildQueryString(params)}`)
  },

  /**
   * Export performance report as PDF
   */
  exportPDF: async (params: DateRangeParams = {}): Promise<Blob> => {
    const response = await fetch(
      `/api/v1/dashboard/performance/report/export/pdf?${new URLSearchParams(params as any)}`,
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
      }
    )
    
    if (!response.ok) {
      throw new Error('Falha ao exportar PDF')
    }
    
    return response.blob()
  },

  /**
   * Export performance report as Excel
   */
  exportExcel: async (params: DateRangeParams = {}): Promise<Blob> => {
    const response = await fetch(
      `/api/v1/dashboard/performance/report/export/excel?${new URLSearchParams(params as any)}`,
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
      }
    )
    
    if (!response.ok) {
      throw new Error('Falha ao exportar Excel')
    }
    
    return response.blob()
  },
}

// =============================================================================
// DEFAULT EXPORT - Aggregated Service
// =============================================================================

export const analyticsService = {
  realtime: realtimeAnalytics,
  dashboard: dashboardAnalytics,
  conversion: conversionAnalytics,
  bot: botAnalytics,
  conversation: conversationAnalytics,
  performance: performanceAnalytics,
}
