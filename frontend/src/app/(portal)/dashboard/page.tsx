'use client'

import { useState, useEffect } from 'react'
import { PageHeader } from '@/components/ui/page-header'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { EmptyState } from '@/components/ui/empty-state'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { MetricCard } from '@/components/ui/metric-card'
import { GaugeChart } from '@/components/ui/gauge-chart'
import { FunnelChart } from '@/components/ui/funnel-chart'
import { PercentileChart } from '@/components/ui/percentile-chart'
import { DonutChart } from '@/components/ui/donut-chart'
import { PeakHoursChart } from '@/components/ui/peak-hours-chart'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AlertCircle,
  Users,
  TrendingUp,
  MessageSquare,
  Clock,
  Activity,
  CheckCircle2,
  Bot,
  BarChart3,
  RefreshCw,
  Filter,
  Settings,
  Smartphone,
} from 'lucide-react'
import { useAnalytics } from '@/hooks/useAnalytics'
import { useSession } from '@/hooks/useSession'
import { getAISettings, type AISettings } from '@/services/settingsService'
import Link from 'next/link'

export default function DashboardPage() {
  const [period, setPeriod] = useState<'7d' | '30d' | '90d'>('30d')
  const [iaSettings, setIaSettings] = useState<AISettings | null>(null)
  const { currentSession } = useSession({ sessionName: 'default' })

  useEffect(() => {
    getAISettings().then(setIaSettings).catch(console.error)
  }, [])

  const {
    dashboard,
    conversionFunnel,
    botAutonomy,
    botResponseTime,
    conversationsByStatus,
    peakHours,
    isLoading,
    error,
    refresh,
  } = useAnalytics({
    period,
    autoRefresh: false,
  })

  if (isLoading && !dashboard) {
    return (
      <div className="p-6 max-w-7xl">
        <PageHeader title="Dashboard" />
        <LoadingSpinner />
      </div>
    )
  }

  if (error && !dashboard) {
    return (
      <div className="p-6 max-w-7xl">
        <PageHeader title="Dashboard" />
        <EmptyState
          icon={AlertCircle}
          message={error || 'Erro ao carregar dados'}
        />
      </div>
    )
  }

  return (
    <div className="p-6 max-w-7xl space-y-8">
      <div className="flex items-center justify-between">
        <PageHeader title="Dashboard" subtitle="Analytics e métricas" />

        <div className="flex items-center gap-3">
          <Tabs value={period} onValueChange={(v) => setPeriod(v as typeof period)}>
            <TabsList>
              <TabsTrigger value="7d">7 dias</TabsTrigger>
              <TabsTrigger value="30d">30 dias</TabsTrigger>
              <TabsTrigger value="90d">90 dias</TabsTrigger>
            </TabsList>
          </Tabs>

          <Button variant="outline" size="icon" onClick={refresh}>
            <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
          </Button>
        </div>
      </div>

      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* UX Banners for Installation/Onboarding */}
      {(!iaSettings?.google_api_key && !iaSettings?.groq_api_key) && iaSettings !== null && (
        <Alert className="border-amber-500 bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-200 shadow-sm relative overflow-hidden">
          <div className="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
          <AlertCircle className="h-5 w-5 !text-amber-600 dark:!text-amber-400" />
          <AlertTitle className="font-semibold text-amber-800 dark:text-amber-200">Inteligência Artificial não configurada</AlertTitle>
          <AlertDescription className="mt-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-700 dark:text-amber-300">
            O assistente não poderá responder mensagens até que uma chave de API (Gemini ou Groq) seja configurada.
            <Button asChild size="sm" className="bg-amber-600 hover:bg-amber-700 text-white shrink-0">
              <Link href="/settings">
                <Settings className="w-4 h-4 mr-2" />
                Configurar IA
              </Link>
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {(currentSession?.status !== 'WORKING') && currentSession !== null && (
        <Alert className="border-blue-500 bg-blue-50 dark:bg-blue-950 text-blue-800 dark:text-blue-200 shadow-sm relative overflow-hidden">
          <div className="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
          <AlertCircle className="h-5 w-5 !text-blue-600 dark:!text-blue-400" />
          <AlertTitle className="font-semibold text-blue-800 dark:text-blue-200">WhatsApp Desconectado</AlertTitle>
          <AlertDescription className="mt-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-blue-700 dark:text-blue-300">
            Você precisa escanear o QR Code ou iniciar a sessão para que o bot envie/receba mensagens.
            <Button asChild size="sm" className="bg-blue-600 hover:bg-blue-700 text-white shrink-0">
              <Link href="/settings">
                <Smartphone className="w-4 h-4 mr-2" />
                Conectar WhatsApp
              </Link>
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {dashboard && (
        <section className="space-y-4">
          <div className="flex items-center gap-2">
            <BarChart3 className="h-5 w-5" />
            <h2 className="text-xl font-semibold">KPIs Principais</h2>
          </div>

          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
              title="Total de Leads"
              value={dashboard.kpis.total_leads.toLocaleString()}
              icon={<Users className="h-4 w-4" />}
            />

            <MetricCard
              title="Leads Convertidos"
              value={dashboard.kpis.converted_leads.toLocaleString()}
              icon={<CheckCircle2 className="h-4 w-4" />}
            />

            <MetricCard
              title="Taxa de Conversão"
              value={`${dashboard.kpis.conversion_rate.toFixed(1)}%`}
              icon={<TrendingUp className="h-4 w-4" />}
              status={
                dashboard.kpis.conversion_rate >= 30
                  ? 'success'
                  : dashboard.kpis.conversion_rate >= 20
                    ? 'warning'
                    : 'danger'
              }
            />

            <MetricCard
              title="Total de Conversas"
              value={dashboard.kpis.total_conversations.toLocaleString()}
              icon={<MessageSquare className="h-4 w-4" />}
            />
          </div>

          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
              title="Conversas Ativas"
              value={dashboard.kpis.active_conversations.toLocaleString()}
              icon={<Activity className="h-4 w-4" />}
            />

            <MetricCard
              title="Total de Mensagens"
              value={dashboard.kpis.total_messages.toLocaleString()}
              icon={<MessageSquare className="h-4 w-4" />}
            />

            <MetricCard
              title="Msgs por Conversa"
              value={dashboard.kpis.avg_messages_per_conversation.toFixed(1)}
              icon={<BarChart3 className="h-4 w-4" />}
            />

            <MetricCard
              title="Tempo de Resposta"
              value={`${dashboard.kpis.avg_response_time_seconds.toFixed(0)}s`}
              icon={<Clock className="h-4 w-4" />}
            />
          </div>
        </section>
      )}

      {conversionFunnel && (
        <section className="space-y-4">
          <div className="flex items-center gap-2">
            <Filter className="h-5 w-5" />
            <h2 className="text-xl font-semibold">Jornada do Lead</h2>
          </div>

          <FunnelChart
            title="Funil de Conversão"
            stages={conversionFunnel.funnel.stages}
          />
        </section>
      )}

      {(botAutonomy || botResponseTime) && (
        <section className="space-y-4">
          <div className="flex items-center gap-2">
            <Bot className="h-5 w-5" />
            <h2 className="text-xl font-semibold">Eficiência do Bot</h2>
          </div>

          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {botAutonomy && (
              <GaugeChart
                title="Taxa de Autonomia"
                value={botAutonomy.autonomy.autonomy_rate}
                target={80}
              />
            )}

            {botResponseTime && (
              <PercentileChart
                title="Tempo de Resposta"
                data={{
                  avg_hours: botResponseTime.bot_response_time.avg_ms / 3600000,
                  median_hours: botResponseTime.bot_response_time.median_ms / 3600000,
                  p75_hours: botResponseTime.bot_response_time.p95_ms / 3600000,
                  p90_hours: botResponseTime.bot_response_time.p95_ms / 3600000,
                  p95_hours: botResponseTime.bot_response_time.p99_ms / 3600000,
                  min_hours: botResponseTime.bot_response_time.min_ms / 3600000,
                  max_hours: botResponseTime.bot_response_time.max_ms / 3600000,
                }}
              />
            )}

            {conversationsByStatus && (() => {
              const total = conversationsByStatus.status_distribution.reduce((sum, item) => sum + item.count, 0)
              return (
                <DonutChart
                  title="Status das Conversas"
                  segments={conversationsByStatus.status_distribution.map((item) => ({
                    label: item.status,
                    value: item.count,
                    percentage: total > 0 ? (item.count / total) * 100 : 0,
                    color:
                      item.status === 'active' ? 'rgb(34, 197, 94)' :
                        item.status === 'resolved' ? 'rgb(59, 130, 246)' :
                          item.status === 'pending' ? 'rgb(251, 191, 36)' :
                            'rgb(156, 163, 175)'
                  }))}
                />
              )
            })()}
          </div>
        </section>
      )}

      {peakHours && (
        <section className="space-y-4">
          <div className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            <h2 className="text-xl font-semibold">Contexto de Uso</h2>
          </div>

          <PeakHoursChart
            title="Horários de Pico"
            data={peakHours.peak_hours}
          />
        </section>
      )}
    </div>
  )
}
