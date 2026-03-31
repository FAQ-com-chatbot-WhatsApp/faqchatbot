/**
 * GaugeChart Component
 * 
 * Circular gauge for displaying percentage metrics (0-100%).
 * Used for autonomy rate, conversion rate, etc.
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface GaugeChartProps {
  title: string
  value: number // 0-100
  subtitle?: string
  target?: number // Optional target value
  label?: string
  className?: string
}

export function GaugeChart({
  title,
  value,
  subtitle,
  target,
  label,
  className,
}: GaugeChartProps) {
  // Clamp value between 0-100
  const normalizedValue = Math.max(0, Math.min(100, value))

  // Determine color based on value
  const getColor = () => {
    if (normalizedValue >= 80) return 'text-green-600 dark:text-green-400'
    if (normalizedValue >= 60) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-600 dark:text-red-400'
  }

  const getStrokeColor = () => {
    if (normalizedValue >= 80) return 'stroke-green-600 dark:stroke-green-400'
    if (normalizedValue >= 60) return 'stroke-yellow-600 dark:stroke-yellow-400'
    return 'stroke-red-600 dark:stroke-red-400'
  }

  // Calculate SVG arc path for the gauge
  const radius = 90
  const circumference = Math.PI * radius
  const fillPercentage = (normalizedValue / 100) * circumference

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {subtitle && <CardDescription>{subtitle}</CardDescription>}
      </CardHeader>
      <CardContent className="flex flex-col items-center">
        <div className="relative w-48 h-24">
          <svg
            className="w-full h-full"
            viewBox="0 0 200 100"
            xmlns="http://www.w3.org/2000/svg"
          >
            {/* Background arc */}
            <path
              d="M 10 100 A 90 90 0 0 1 190 100"
              fill="none"
              stroke="currentColor"
              strokeWidth="12"
              className="text-gray-200 dark:text-gray-700"
            />

            {/* Value arc */}
            <path
              d="M 10 100 A 90 90 0 0 1 190 100"
              fill="none"
              strokeWidth="12"
              strokeLinecap="round"
              strokeDasharray={`${fillPercentage} ${circumference}`}
              className={getStrokeColor()}
            />

            {/* Target indicator (if provided) */}
            {target !== undefined && (
              <circle
                cx={10 + (target / 100) * 180}
                cy={100 - Math.sin(Math.acos((10 + (target / 100) * 180 - 100) / 90)) * 90}
                r="4"
                className="fill-blue-500"
              />
            )}
          </svg>

          {/* Center value */}
          <div className="absolute inset-0 flex flex-col items-center justify-end pb-2">
            <div className={`text-4xl font-bold ${getColor()}`}>
              {normalizedValue.toFixed(0)}%
            </div>
            {label && (
              <div className="text-xs text-muted-foreground mt-1">
                {label}
              </div>
            )}
          </div>
        </div>

        {target !== undefined && (
          <div className="text-xs text-muted-foreground mt-4">
            Meta: {target}%
          </div>
        )}
      </CardContent>
    </Card>
  )
}
