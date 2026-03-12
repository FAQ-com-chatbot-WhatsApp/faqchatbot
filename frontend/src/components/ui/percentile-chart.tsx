/**
 * PercentileChart Component
 * 
 * Displays percentile statistics for time-based metrics.
 * Shows bars for p50 (median), p75, p90, p95 to visualize distribution.
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface PercentileChartProps {
  title: string
  subtitle?: string
  data: {
    median_hours?: number
    p75_hours?: number
    p90_hours?: number
    p95_hours?: number
    avg_hours?: number
    min_hours?: number
    max_hours?: number
  }
  className?: string
}

export function PercentileChart({ title, subtitle, data, className }: PercentileChartProps) {
  const percentiles = [
    { label: 'Mediana (p50)', value: data.median_hours, color: 'bg-blue-500' },
    { label: 'p75', value: data.p75_hours, color: 'bg-purple-500' },
    { label: 'p90', value: data.p90_hours, color: 'bg-orange-500' },
    { label: 'p95', value: data.p95_hours, color: 'bg-red-500' },
  ].filter(p => p.value !== undefined)

  const maxValue = Math.max(...percentiles.map(p => p.value || 0))

  const formatHours = (hours: number) => {
    if (hours < 1) {
      return `${Math.round(hours * 60)}min`
    }
    if (hours < 24) {
      return `${hours.toFixed(1)}h`
    }
    const days = Math.floor(hours / 24)
    const remainingHours = Math.round(hours % 24)
    return `${days}d ${remainingHours}h`
  }

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {subtitle && <CardDescription>{subtitle}</CardDescription>}
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Percentile bars */}
        <div className="space-y-3">
          {percentiles.map((percentile) => (
            <div key={percentile.label} className="space-y-1">
              <div className="flex items-center justify-between text-sm">
                <span className="font-medium">{percentile.label}</span>
                <span className="text-muted-foreground">
                  {formatHours(percentile.value || 0)}
                </span>
              </div>
              <div className="relative h-8 bg-gray-100 dark:bg-gray-800 rounded-md overflow-hidden">
                <div
                  className={`absolute inset-y-0 left-0 ${percentile.color} transition-all duration-500`}
                  style={{ width: `${((percentile.value || 0) / maxValue) * 100}%` }}
                />
              </div>
            </div>
          ))}
        </div>

        {/* Summary stats */}
        {(data.avg_hours !== undefined || data.min_hours !== undefined || data.max_hours !== undefined) && (
          <div className="grid grid-cols-3 gap-4 pt-4 border-t">
            {data.avg_hours !== undefined && (
              <div>
                <div className="text-xs text-muted-foreground">Média</div>
                <div className="text-sm font-medium">{formatHours(data.avg_hours)}</div>
              </div>
            )}
            {data.min_hours !== undefined && (
              <div>
                <div className="text-xs text-muted-foreground">Mínimo</div>
                <div className="text-sm font-medium">{formatHours(data.min_hours)}</div>
              </div>
            )}
            {data.max_hours !== undefined && (
              <div>
                <div className="text-xs text-muted-foreground">Máximo</div>
                <div className="text-sm font-medium">{formatHours(data.max_hours)}</div>
              </div>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
