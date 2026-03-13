"use client"

import * as React from "react"
import { cn } from "@/lib/utils"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Check, CheckCheck } from "lucide-react"

type MessageStatus = "pending" | "sent" | "delivered" | "read"

interface MessageBubbleProps extends React.HTMLAttributes<HTMLDivElement> {
  sender?: "user" | "other"
  message: string
  timestamp?: string
  senderName?: string
  senderInitials?: string
  senderAvatar?: string
  unread?: boolean
  status?: MessageStatus
}

export function MessageBubble({
  sender = "other",
  message,
  timestamp,
  senderName,
  senderInitials = "JD",
  senderAvatar,
  unread = false,
  status = "sent",
  className,
  ...props
}: MessageBubbleProps) {
  const isUser = sender === "user"

  // Renderizar ícone de status para mensagens do usuário
  const renderStatusIcon = () => {
    if (!isUser || !timestamp) return null

    const iconClasses = cn(
      "size-3.5 inline-block ml-1",
      status === "pending" && "text-primary-foreground/60",
      status === "sent" && "text-primary-foreground/80",
      (status === "delivered" || status === "read") && "text-primary-foreground"
    )

    if (status === "pending") {
      return <Check className={iconClasses} />
    }

    // Checkmark duplo para enviado/entregue/lido
    return <CheckCheck className={iconClasses} />
  }

  return (
    <div
      className={cn(
        "flex gap-3 items-start",
        isUser && "flex-row-reverse",
        className
      )}
      {...props}
    >
      {!isUser && (
        <Avatar className="size-8 shrink-0">
          {senderAvatar && <AvatarImage src={senderAvatar} alt={senderName} />}
          <AvatarFallback className="text-xs">{senderInitials}</AvatarFallback>
        </Avatar>
      )}

      <div className={cn("flex flex-col gap-1 max-w-[70%]", isUser && "items-end")}>
        {!isUser && senderName && (
          <span className="text-xs font-medium text-muted-foreground px-1">
            {senderName}
          </span>
        )}

        <div
          className={cn(
            "px-3 py-1.5 rounded-lg relative",
            isUser
              ? "bg-primary text-primary-foreground rounded-tr-none shadow-sm"
              : "bg-muted text-foreground rounded-tl-none shadow-sm",
            unread && !isUser && "ring-2 ring-primary/50"
          )}
        >
          {/* Mensagem com espaço reservado para timestamp */}
          <p className="text-[14px] leading-[1.4] pr-16 break-words">
            {message}
          </p>

          {/* Timestamp e status dentro do balão, canto inferior direito */}
          {timestamp && (
            <span className={cn(
              "absolute bottom-1 right-2 flex items-center gap-0.5 text-[11px] whitespace-nowrap",
              isUser ? "text-primary-foreground/70" : "text-muted-foreground"
            )}>
              {timestamp}
              {renderStatusIcon()}
            </span>
          )}
        </div>
      </div>
    </div>
  )
}
