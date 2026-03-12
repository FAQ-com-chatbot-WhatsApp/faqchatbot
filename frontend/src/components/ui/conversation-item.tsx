"use client"

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { cn } from "@/lib/utils"

interface ConversationItemProps {
  name: string
  avatar?: string
  initials: string
  lastMessage?: string
  timestamp?: string
  unreadCount?: number
  isActive?: boolean
  isOnline?: boolean
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
  onClick,
}: ConversationItemProps) {
  return (
    <div
      className={cn(
        "p-4 flex items-center gap-3 hover:bg-accent cursor-pointer border-b transition-colors",
        isActive && "bg-accent"
      )}
      onClick={onClick}
    >
      <div className="relative flex-shrink-0">
        <Avatar className="h-12 w-12">
          {avatar && <AvatarImage src={avatar} />}
          <AvatarFallback>{initials}</AvatarFallback>
        </Avatar>
        {isOnline && (
          <span className="absolute bottom-0 right-0 size-3 rounded-full bg-green-500 border-2 border-background" />
        )}
      </div>

      <div className="flex-1 overflow-hidden">
        <div className="flex items-center justify-between mb-1">
          <p className="font-medium truncate">{name}</p>
          {timestamp && (
            <span className="text-xs text-muted-foreground flex-shrink-0">
              {timestamp}
            </span>
          )}
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
