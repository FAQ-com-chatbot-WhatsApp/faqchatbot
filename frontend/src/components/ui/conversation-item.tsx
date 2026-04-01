"use client"

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { cn } from "@/lib/utils"
import { User } from "lucide-react"

interface ConversationItemProps {
  name: string
  avatar?: string
  initials: string
  lastMessage?: string
  timestamp?: string
  unreadCount?: number
  isActive?: boolean
  isOnline?: boolean
  status?: string
  onClick?: () => void
}

export function ConversationItem({
  name,
  avatar,
  initials,
  lastMessage,
  timestamp,
  unreadCount = 0,
  isActive = false,
  isOnline = false,
  status,
  onClick,
}: ConversationItemProps) {
  return (
    <div
      className={cn(
        "p-4 flex items-center gap-3 hover:bg-primary/5 cursor-pointer border-b transition-colors",
        isActive && "bg-primary/10 border-l-4 border-l-primary"
      )}
      onClick={onClick}
    >
      <div className="relative flex-shrink-0">
        <Avatar className="h-12 w-12">
          {avatar && <AvatarImage src={avatar} />}
          <AvatarFallback className={cn(
            isActive && "bg-primary text-primary-foreground font-semibold"
          )}>
            {initials === "??" ? <User className="h-6 w-6" /> : initials}
          </AvatarFallback>
        </Avatar>
        {isOnline && (
          <span className="absolute bottom-0 right-0 size-3 rounded-full bg-green-500 border-2 border-background" />
        )}
      </div>

      <div className="flex-1 overflow-hidden">
        <div className="flex items-center justify-between mb-1">
          <p className="font-medium truncate flex-1">{name}</p>
          <div className="flex items-center gap-2 flex-shrink-0">
            {status === "ACTIVE_BOT" && <span className="text-[10px] bg-blue-100 text-blue-700 px-1 rounded font-bold">BOT</span>}
            {status === "ACTIVE_HUMAN" && <span className="text-[10px] bg-green-100 text-green-700 px-1 rounded font-bold">HUMANO</span>}
            {status === "PENDING_HANDOFF" && <span className="text-[10px] bg-orange-100 text-orange-700 px-1 rounded font-bold animate-pulse">AGUARDANDO</span>}
            {timestamp && (
              <span className="text-xs text-muted-foreground">
                {timestamp}
              </span>
            )}
          </div>
        </div>
        <div className="flex items-center justify-between">
          <p className="text-xs text-muted-foreground truncate flex-1">
            {lastMessage || "Sem mensagens"}
          </p>
          {unreadCount > 0 && (
            <span className="ml-2 flex-shrink-0 bg-primary text-primary-foreground text-xs rounded-full h-5 min-w-5 px-1.5 flex items-center justify-center font-medium">
              {unreadCount > 99 ? "99+" : unreadCount}
            </span>
          )}
        </div>
      </div>
    </div>
  )
}
