"use client"

import { Card, CardContent } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { MessageSquare, Edit2, User } from "lucide-react"

interface ContactCardProps {
  name: string
  company?: string
  initials: string
  avatar?: string
  isOnline?: boolean
  isSelected?: boolean
  description?: string
  onMessage?: () => void
  onEdit?: () => void
}

export function ContactCard({
  name,
  company,
  initials,
  avatar,
  isOnline = false,
  isSelected = false,
  description,
  onMessage,
  onEdit,
}: ContactCardProps) {
  return (
    <Card className={`transition-all hover:shadow-md ${isSelected ? 'ring-2 ring-primary' : ''}`}>
      <CardContent className="p-6 space-y-4">
        <div className="flex flex-col items-center gap-3">
          <div className="relative">
            <Avatar className="size-20">
              {avatar && <AvatarImage src={avatar} />}
              <AvatarFallback className="text-lg bg-blue-50 text-blue-600 font-semibold">
                {(/^\d+$/.test(initials) || initials === "??") ? <User className="size-10" /> : initials}
              </AvatarFallback>
            </Avatar>
            {isOnline && (
              <span className="absolute bottom-0 right-0 size-4 rounded-full bg-green-500 border-2 border-background" />
            )}
          </div>

          <div className="text-center">
            <h3 className="font-semibold text-base">{name}</h3>
            {company && <p className="text-sm text-muted-foreground">{company}</p>}
            {description && (
              <p className="text-xs text-muted-foreground mt-2 line-clamp-2">
                {description}
              </p>
            )}
          </div>
        </div>

        <div className="flex items-center justify-center gap-2">
          {onMessage && (
            <Button variant="ghost" size="icon-sm" onClick={onMessage} aria-label="Mensagem">
              <MessageSquare className="size-4" />
            </Button>
          )}
          {onEdit && (
            <Button variant="ghost" size="icon-sm" onClick={onEdit} aria-label="Editar">
              <Edit2 className="size-4" />
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
