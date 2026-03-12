/**
 * FunnelChart Component
 * 
 * Displays a conversion funnel with stages and drop-off rates.
 * Each stage shows count and percentage of initial total.
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import type { FunnelStage } from '@/types/analytics'

interface FunnelChartProps {
  title: string
  subtitle?: string
  stages: FunnelStage[]
  className?: string
}

export function FunnelChart({ title, subtitle, stages, className }: FunnelChartProps) {
  if (!stages || stages.length === 0) {
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

  const maxCount = stages[0]?.count || 1

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {subtitle && <CardDescription>{subtitle}</CardDescription>}
      </CardHeader>
      <CardContent className="space-y-3">
        {stages.map((stage, index) => {
          const widthPercentage = (stage.count / maxCount) * 100

          return (
            <div key={stage.stage} className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                  <span className="font-medium">{stage.name}</span>
                  {stage.drop_off > 0 && index > 0 && (
                    <Badge variant="destructive" className="text-xs">
                      -{stage.drop_off.toFixed(1)}% drop-off
                    </Badge>
                  )}
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-muted-foreground">
                    {stage.count.toLocaleString()}
                  </span>
                  <span className="font-medium text-primary">
                    {stage.percentage.toFixed(1)}%
                  </span>
                </div>
              </div>

              <div className="relative h-10 bg-gray-100 dark:bg-gray-800 rounded-md overflow-hidden">
                <div
                  className="absolute inset-y-0 left-0 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 flex items-center justify-center transition-all duration-500"
                  style={{ width: `${widthPercentage}%` }}
                >
                  {widthPercentage > 20 && (
                    <span className="text-xs font-medium text-white">
                      {stage.percentage.toFixed(0)}%
                    </span>
                  )}
                </div>
              </div>
            </div>
          )
        })}
      </CardContent>
    </Card>
  )
}
