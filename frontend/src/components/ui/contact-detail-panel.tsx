"use client"

import { Card, CardContent } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Phone, Video, MessageCircle, Edit2, Trash2 } from "lucide-react"

interface ContactDetailPanelProps {
  name: string
  avatar?: string
  initials: string
  about: string
  onMessage?: () => void
  onEdit?: () => void
}

export function ContactDetailPanel({
  name,
  avatar,
  initials,
  about,
  onMessage,
  onEdit,
}: ContactDetailPanelProps) {
  return (
    <Card className="border-l border-border h-full shadow-none border-0">
      <CardContent className="p-6">
        <div className="flex flex-col items-center gap-4 mb-6">
          <Avatar className="h-24 w-24">
            <AvatarImage src={avatar} alt={name} />
            <AvatarFallback>{initials}</AvatarFallback>
          </Avatar>
          <div className="space-y-1 text-center">
            <h2 className="text-xl font-bold">{name}</h2>
            <div className="inline-flex items-center rounded-full border border-primary/20 bg-primary/5 px-2.5 py-0.5 text-xs font-semibold text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
              Contato
            </div>
          </div>
        </div>

        <div className="flex justify-center gap-4 mb-8">
          {onMessage && (
            <Button
              variant="outline"
              size="icon"
              className="rounded-full size-12 border-blue-100 bg-blue-50/50 hover:bg-blue-100 hover:text-blue-600 transition-all"
              onClick={onMessage}
              title="Ir para mensagens"
            >
              <MessageCircle className="w-5 h-5 text-blue-600" />
            </Button>
          )}
          {onEdit && (
            <Button
              variant="outline"
              size="icon"
              className="rounded-full size-12 border-blue-100 bg-blue-50/50 hover:bg-blue-100 hover:text-blue-600 transition-all"
              onClick={onEdit}
              title="Editar nome"
            >
              <Edit2 className="w-5 h-5 text-blue-600" />
            </Button>
          )}
        </div>

        <div className="pt-6 border-t">
          <h3 className="text-sm font-semibold mb-3 text-muted-foreground uppercase tracking-wider">Anotações / Score</h3>
          <div className="bg-muted/50 rounded-xl p-4">
            <p className="text-sm leading-relaxed">{about}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
