"use client"

import { cn } from "@/lib/utils"
import { LucideIcon } from "lucide-react"

interface EmptyStateProps {
  icon?: LucideIcon
  message: string
  className?: string
}

export function EmptyState({ icon: Icon, message, className }: EmptyStateProps) {
  return (
    <div className={cn("flex flex-col items-center justify-center h-full text-muted-foreground", className)}>
      {Icon && <Icon className="h-12 w-12 mb-4 opacity-50" />}
      <p>{message}</p>
    </div>
  )
}
