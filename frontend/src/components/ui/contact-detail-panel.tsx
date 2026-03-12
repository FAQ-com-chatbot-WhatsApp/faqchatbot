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
  onCall?: () => void
  onVideo?: () => void
  onMessage?: () => void
  onEdit?: () => void
  onDelete?: () => void
}

export function ContactDetailPanel({
  name,
  avatar,
  initials,
  about,
  onCall,
  onVideo,
  onMessage,
  onEdit,
  onDelete
}: ContactDetailPanelProps) {
  return (
    <Card className="border-l border-border h-full">
      <CardContent className="p-6">
        <div className="flex flex-col items-center gap-4 mb-6">
          <Avatar className="h-24 w-24">
            <AvatarImage src={avatar} alt={name} />
            <AvatarFallback>{initials}</AvatarFallback>
          </Avatar>
          <h2 className="text-xl font-bold text-center">{name}</h2>
        </div>

        <div className="flex justify-center gap-4 mb-6">
          <Button variant="ghost" size="icon" className="rounded-lg" onClick={onCall}>
            <Phone className="w-5 h-5 text-blue-600" />
          </Button>
          <Button variant="ghost" size="icon" className="rounded-lg" onClick={onMessage}>
            <MessageCircle className="w-5 h-5 text-blue-600" />
          </Button>
          <Button variant="ghost" size="icon" className="rounded-lg" onClick={onVideo}>
            <Video className="w-5 h-5 text-blue-600" />
          </Button>
        </div>

        <div className="flex gap-3 mb-6">
          <Button variant="outline" size="sm" className="flex-1" onClick={onEdit}>
            <Edit2 className="w-4 h-4 mr-2" />
            Editar
          </Button>
          <Button
            variant="outline"
            size="sm"
            className="flex-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
            onClick={onDelete}
          >
            <Trash2 className="w-4 h-4 mr-2" />
            Excluir
          </Button>
        </div>

        <div>
          <h3 className="text-sm font-semibold mb-2">Sobre</h3>
          <p className="text-xs text-muted-foreground leading-relaxed">{about}</p>
        </div>
      </CardContent>
    </Card>
  )
}
