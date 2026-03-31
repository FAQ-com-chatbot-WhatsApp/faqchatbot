"use client"

import { cn } from "@/lib/utils"
import { ReactNode } from "react"

interface PageHeaderProps {
  title: string
  subtitle?: string
  actions?: ReactNode
  className?: string
  showTitle?: boolean
}

export function PageHeader({ title, subtitle, actions, className, showTitle = false }: PageHeaderProps) {
  if (!showTitle && !subtitle && !actions) {
    return <h1 className="sr-only">{title}</h1>
  }

  return (
    <div className={cn("flex flex-col sm:flex-row sm:items-center justify-between gap-4", (subtitle || actions) ? "mb-6" : "mb-2", className)}>
      <div>
        {showTitle ? (
          <h1 className="text-3xl font-bold text-slate-900 leading-tight tracking-tight">{title}</h1>
        ) : (
          <h1 className="sr-only">{title}</h1>
        )}
        {subtitle && (
          <p className={cn(showTitle ? "text-muted-foreground mt-1" : "text-xl font-semibold text-slate-800 tracking-tight")}>
            {subtitle}
          </p>
        )}
      </div>
      {actions && <div className="flex-shrink-0">{actions}</div>}
    </div>
  )
}
