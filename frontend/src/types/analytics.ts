/**
 * Analytics Types
 * 
 * Type definitions for analytics and metrics API responses.
 * Based on backend schemas from metrics_schemas.py
 */

// =============================================================================
// COMMON TYPES
// =============================================================================

export interface Period {
  start: string // ISO date
  end: string   // ISO date
}

// =============================================================================
// REALTIME DASHBOARD
// =============================================================================

export interface RealtimeSummary {
  active_conversations: number
  messages_per_minute: number
  avg_response_time_ms: number
  bot_resolution_rate: number
}

export interface ActiveConversation {
  id: string
  chat_id: string
  status: string
  last_message_at: string
  minutes_since_last_message: number
}

export interface QueueStats {
  job_count: number
  worker_count: number
  failed_count: number
}

export interface PerformanceAlerts {
  high_latency_count: number
  high_latency_avg_ms: number
  error_rate: number
  total_interactions_last_hour: number
  failed_interactions: number
}

export interface RealtimeDashboard {
  timestamp: string
  summary: RealtimeSummary
  active_conversations: ActiveConversation[]
  queue_stats: Record<string, QueueStats>
  performance_alerts: PerformanceAlerts
}

// =============================================================================
// DASHBOARD KPIs
// =============================================================================

export interface DashboardKPIs {
  total_leads: number
  converted_leads: number
  conversion_rate: number
  avg_response_time_seconds: number
  total_conversations: number
  active_conversations: number
  total_messages: number
  avg_messages_per_conversation: number
}

export interface DashboardSummary {
  period: Period
  kpis: DashboardKPIs
}

// =============================================================================
// CONVERSION FUNNEL
// =============================================================================

export interface FunnelStage {
  stage: string
  name: string
  count: number
  percentage: number
  drop_off: number
}

export interface ConversionFunnel {
  period: Period
  funnel: {
    stages: FunnelStage[]
  }
}

// =============================================================================
// CONVERSION ANALYTICS
// =============================================================================

export interface TimeToConversionStats {
  avg_hours: number
  median_hours: number
  p75_hours: number
  p90_hours: number
  p95_hours: number
  min_hours: number
  max_hours: number
}

export interface TimeToConversion {
  period: Period
  time_stats: TimeToConversionStats
}

export interface ConversionBySource {
  source: string
  total_leads: number
  converted_leads: number
  conversion_rate: number
}

export interface ConversionBySourceResponse {
  period: Period
  sources: ConversionBySource[]
}

export interface LostLeadsByMaturity {
  maturity_range: string
  count: number
  percentage: number
}

export interface LostLeadsAnalysis {
  total_lost: number
  lost_by_maturity_range: LostLeadsByMaturity[]
  avg_time_before_lost_hours: number
}

export interface LostLeadsResponse {
  period: Period
  lost_leads: LostLeadsAnalysis
}

export interface ConversionTrendDataPoint {
  period: string
  total_leads: number
  converted_leads: number
  conversion_rate: number
}

export interface ConversionTrend {
  period: Period
  granularity: 'day' | 'week' | 'month'
  trend: ConversionTrendDataPoint[]
}

// =============================================================================
// BOT PERFORMANCE
// =============================================================================

export interface BotAutonomyMetrics {
  total_conversations: number
  bot_only: number
  with_handoff: number
  autonomy_rate: number
}

export interface BotAutonomy {
  period: Period
  autonomy: BotAutonomyMetrics
}

export interface BotResponseTimeStats {
  avg_ms: number
  median_ms: number
  p95_ms: number
  p99_ms: number
  min_ms: number
  max_ms: number
  total_interactions: number
}

export interface BotResponseTime {
  period: Period
  bot_response_time: BotResponseTimeStats
}

export interface HandoffRateStats {
  total_conversations: number
  bot_resolved: number
  handoff_required: number
  handoff_rate: number
  auto_resolution_rate: number
}

export interface HandoffRate {
  period: Period
  handoff_stats: HandoffRateStats
}

export interface ConversationStatusDistribution {
  status: string
  count: number
  percentage: number
}

export interface ConversationsByStatus {
  period: Period
  status_distribution: ConversationStatusDistribution[]
}

// =============================================================================
// CONVERSATION ANALYSIS
// =============================================================================

export interface PeakHourDataPoint {
  hour: number // 0-23
  message_count: number
  conversation_count: number
}

export interface PeakHours {
  period: Period
  peak_hours: PeakHourDataPoint[]
}

export interface ActivityHeatmapDataPoint {
  day_of_week: number // 0=sunday, 6=saturday
  hour: number // 0-23
  message_count: number
}

export interface KeywordFrequency {
  keyword: string
  count: number
}

export interface SentimentDistribution {
  positive: number
  negative: number
  neutral: number
  total_messages: number
}

export interface TopicDistribution {
  topic: string
  count: number
  percentage: number
}

export interface ConversationAnalysisReport {
  period_start: string
  period_end: string
  activity_heatmap: ActivityHeatmapDataPoint[]
  top_keywords: KeywordFrequency[]
  sentiment_distribution: SentimentDistribution
  topic_distribution: TopicDistribution[]
}

// =============================================================================
// PERFORMANCE REPORT
// =============================================================================

export interface PerformanceReport {
  period: Period
  bot_response_time: BotResponseTimeStats
  handoff_stats: HandoffRateStats
  peak_hours: PeakHourDataPoint[]
  status_distribution: ConversationStatusDistribution[]
}
