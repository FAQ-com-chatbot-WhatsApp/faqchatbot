"use client"

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { MoreVertical, Phone, Video, Search, Bot, User } from "lucide-react"
import { Switch } from "@/components/ui/switch"

interface ChatHeaderProps {
  name: string
  avatar?: string
  initials: string
  status?: string
  isOnline?: boolean
  isBotActive?: boolean
  onBotToggle?: (enabled: boolean) => void
  onCall?: () => void
  onVideo?: () => void
  onSearch?: () => void
  onMore?: () => void
}

export function ChatHeader({
  name,
  avatar,
  initials,
  status,
  isOnline = false,
  isBotActive = true,
  onBotToggle,
  onCall,
  onVideo,
  onSearch,
  onMore,
}: ChatHeaderProps) {
  return (
    <div className="p-4 border-b flex justify-between items-center bg-muted/30">
      <div className="flex items-center gap-3">
        <div className="relative">
          <Avatar className="h-10 w-10">
            {avatar && <AvatarImage src={avatar} />}
            <AvatarFallback>
              {(/^\d+$/.test(initials) || initials === "??" || !initials) ? (
             <User className="h-6 w-6" />
              ) : (
                initials
              )}
            </AvatarFallback>
          </Avatar>
          {isOnline && (
            <span className="absolute bottom-0 right-0 size-3 rounded-full bg-green-500 border-2 border-background" />
          )}
        </div>
        <div>
          <p className="font-semibold">{name}</p>
          {status && (
            <p className="text-xs text-muted-foreground">{status}</p>
          )}
        </div>
      </div>

      <div className="flex items-center gap-3">
        {onBotToggle && (
          <div className="flex items-center gap-2 px-3 py-1.5 bg-background rounded-lg border">
            <User className={`h-4 w-4 ${!isBotActive ? 'text-primary' : 'text-muted-foreground'}`} />
            <Switch
              id="bot-toggle"
              checked={isBotActive}
              onCheckedChange={onBotToggle}
              aria-label="Alternar modo bot"
            />
            <Bot className={`h-4 w-4 ${isBotActive ? 'text-primary' : 'text-muted-foreground'}`} />
          </div>
        )}
        {onCall && (
          <Button variant="ghost" size="icon-sm" onClick={onCall} aria-label="Ligar">
            <Phone className="h-5 w-5" />
          </Button>
        )}
        {onVideo && (
          <Button variant="ghost" size="icon-sm" onClick={onVideo} aria-label="Chamada de vídeo">
            <Video className="h-5 w-5" />
          </Button>
        )}
        {onSearch && (
          <Button variant="ghost" size="icon-sm" onClick={onSearch} aria-label="Buscar">
            <Search className="h-5 w-5" />
          </Button>
        )}
        {onMore && (
          <Button variant="ghost" size="icon-sm" onClick={onMore} aria-label="Mais opções">
            <MoreVertical className="h-5 w-5" />
          </Button>
        )}
      </div>
    </div>
  )
}
