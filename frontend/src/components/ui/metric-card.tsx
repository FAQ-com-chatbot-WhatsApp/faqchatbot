/**
 * MetricCard Component
 * 
 * Displays a single metric with optional trend indicator and comparison.
 * Follows single responsibility principle - only for metric display.
 */

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ArrowUp, ArrowDown, Minus } from 'lucide-react'

interface MetricCardProps {
  title: string
  value: string | number
  subtitle?: string
  trend?: {
    value: number // percentage: +12.5, -5.2
    label?: string // "vs período anterior"
  }
  status?: 'success' | 'warning' | 'danger' | 'neutral'
  icon?: React.ReactNode
  className?: string
}

export function MetricCard({
  title,
  value,
  subtitle,
  trend,
  status,
  icon,
  className,
}: MetricCardProps) {
  const getTrendIcon = (trendValue: number) => {
    if (trendValue > 0) return <ArrowUp className="h-3 w-3" />
    if (trendValue < 0) return <ArrowDown className="h-3 w-3" />
    return <Minus className="h-3 w-3" />
  }

  const getTrendColor = (trendValue: number) => {
    if (trendValue > 0) return 'text-green-600 dark:text-green-400'
    if (trendValue < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
  }

  const getStatusColor = () => {
    switch (status) {
      case 'success':
        return 'border-green-500/50 bg-green-50 dark:bg-green-950/20'
      case 'warning':
        return 'border-yellow-500/50 bg-yellow-50 dark:bg-yellow-950/20'
      case 'danger':
        return 'border-red-500/50 bg-red-50 dark:bg-red-950/20'
      default:
        return ''
    }
  }

  return (
    <Card className={`${getStatusColor()} ${className || ''}`}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">
          {title}
        </CardTitle>
        {icon && (
          <div className="text-muted-foreground">
            {icon}
          </div>
        )}
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>

        {(subtitle || trend) && (
          <div className="flex items-center gap-2 mt-1">
            {trend && (
              <div className={`flex items-center gap-1 text-xs font-medium ${getTrendColor(trend.value)}`}>
                {getTrendIcon(trend.value)}
                <span>{Math.abs(trend.value)}%</span>
              </div>
            )}

            {subtitle && (
              <p className="text-xs text-muted-foreground">
                {trend?.label || subtitle}
              </p>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
