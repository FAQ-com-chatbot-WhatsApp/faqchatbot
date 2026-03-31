/**
 * DonutChart Component
 * 
 * Simple donut chart for displaying distribution data.
 * Used for status distribution, sentiment analysis, etc.
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface DonutChartSegment {
  label: string
  value: number
  percentage: number
  color: string
}

interface DonutChartProps {
  title: string
  subtitle?: string
  segments: DonutChartSegment[]
  centerLabel?: string
  centerValue?: string | number
  className?: string
}

export function DonutChart({
  title,
  subtitle,
  segments,
  centerLabel,
  centerValue,
  className,
}: DonutChartProps) {
  if (!segments || segments.length === 0) {
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

  // Calculate SVG path for each segment using reduce to avoid post-render mutation
  const radius = 80
  const centerX = 100
  const centerY = 100
  const strokeWidth = 24

  const paths = segments.reduce<(DonutChartSegment & { pathData: string })[]>(
    (acc, segment) => {
      const currentAngle = acc.length === 0
        ? -90
        : acc.reduce((sum, s) => {
            const prevAngle = (s.percentage / 100) * 360
            return sum + prevAngle
          }, -90)

      const angle = (segment.percentage / 100) * 360
      const endAngle = currentAngle + angle

      const startRad = (currentAngle * Math.PI) / 180
      const endRad = (endAngle * Math.PI) / 180

      const x1 = centerX + radius * Math.cos(startRad)
      const y1 = centerY + radius * Math.sin(startRad)
      const x2 = centerX + radius * Math.cos(endRad)
      const y2 = centerY + radius * Math.sin(endRad)

      const largeArc = angle > 180 ? 1 : 0

      const pathData = [
        `M ${x1} ${y1}`,
        `A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2}`,
      ].join(' ')

      return [...acc, { ...segment, pathData }]
    },
    []
  )

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {subtitle && <CardDescription>{subtitle}</CardDescription>}
      </CardHeader>
      <CardContent>
        <div className="flex flex-col md:flex-row items-center gap-6">
          {/* Donut Chart SVG */}
          <div className="relative w-48 h-48 flex-shrink-0">
            <svg
              className="w-full h-full transform -rotate-90"
              viewBox="0 0 200 200"
              xmlns="http://www.w3.org/2000/svg"
            >
              {paths.map((path, index) => (
                <path
                  key={index}
                  d={path.pathData}
                  fill="none"
                  stroke={path.color}
                  strokeWidth={strokeWidth}
                  strokeLinecap="round"
                  className="transition-all duration-300 hover:opacity-80"
                />
              ))}
            </svg>

            {/* Center label */}
            {(centerLabel || centerValue) && (
              <div className="absolute inset-0 flex flex-col items-center justify-center">
                {centerValue && (
                  <div className="text-3xl font-bold">{centerValue}</div>
                )}
                {centerLabel && (
                  <div className="text-xs text-muted-foreground">{centerLabel}</div>
                )}
              </div>
            )}
          </div>

          {/* Legend */}
          <div className="flex-1 space-y-2">
            {segments.map((segment, index) => (
              <div key={index} className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-2">
                  <div
                    className="w-3 h-3 rounded-full flex-shrink-0"
                    style={{ backgroundColor: segment.color }}
                  />
                  <span className="text-sm">{segment.label}</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-sm text-muted-foreground">
                    {segment.value.toLocaleString()}
                  </span>
                  <span className="text-sm font-medium min-w-[3rem] text-right">
                    {segment.percentage.toFixed(1)}%
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
