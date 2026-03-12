/**
 * PeakHoursChart Component
 * 
 * Bar chart showing message/conversation volume by hour of day.
 * Helps identify peak traffic times.
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { PeakHourDataPoint } from '@/types/analytics'

interface PeakHoursChartProps {
  title: string
  subtitle?: string
  data: PeakHourDataPoint[]
  className?: string
}

export function PeakHoursChart({ title, subtitle, data, className }: PeakHoursChartProps) {
  if (!data || data.length === 0) {
    return (
      <Card className={className}>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {subtitle && <CardDescription>{subtitle}</CardDescription>}
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground text-center py-8">
            Sem dados disponíveis
          </p>
        </CardContent>
      </Card>
    )
  }

  const maxMessages = Math.max(...data.map(d => d.message_count))
  const maxConversations = Math.max(...data.map(d => d.conversation_count))

  const formatHour = (hour: number) => {
    if (hour === 0) return '00h'
    if (hour === 12) return '12h'
    return `${hour}h`
  }

  // Find top 3 peak hours
  const topHours = [...data]
    .sort((a, b) => b.message_count - a.message_count)
    .slice(0, 3)
    .map(d => d.hour)

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {subtitle && <CardDescription>{subtitle}</CardDescription>}
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {data.map((hourData) => {
            const isPeak = topHours.includes(hourData.hour)
            const messageHeightPercentage = (hourData.message_count / maxMessages) * 100

            return (
              <div key={hourData.hour} className="flex items-center gap-3">
                <div className="w-12 text-xs text-muted-foreground text-right">
                  {formatHour(hourData.hour)}
                </div>

                <div className="flex-1 space-y-1">
                  <div className="relative h-6 bg-gray-100 dark:bg-gray-800 rounded-md overflow-hidden">
                    <div
                      className={`absolute inset-y-0 left-0 transition-all duration-500 ${isPeak
                          ? 'bg-gradient-to-r from-blue-600 to-blue-700'
                          : 'bg-gradient-to-r from-blue-400 to-blue-500'
                        }`}
                      style={{ width: `${messageHeightPercentage}%` }}
                    >
                      {messageHeightPercentage > 15 && (
                        <span className="absolute inset-0 flex items-center justify-end pr-2 text-xs font-medium text-white">
                          {hourData.message_count}
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                <div className="w-16 text-sm text-right">
                  <span className="text-muted-foreground text-xs">
                    {hourData.conversation_count} conv
                  </span>
                </div>
              </div>
            )
          })}
        </div>

        {/* Legend */}
        <div className="flex items-center gap-4 mt-4 pt-4 border-t text-xs text-muted-foreground">
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 bg-blue-600 rounded" />
            <span>Horários de pico</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 bg-blue-400 rounded" />
            <span>Horários normais</span>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
