'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Plus, AlertCircle, Loader2 } from 'lucide-react'
import { ContactCard } from '@/components/ui/contact-card'
import { ContactDetailPanel } from '@/components/ui/contact-detail-panel'
import { PageHeader } from '@/components/ui/page-header'
import { useLeads } from '@/hooks/useLeads'
import { useRouter } from 'next/navigation'
import type { Lead } from '@/services/leadService'

export default function ContatosPage() {
  const router = useRouter()
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)

  const { leads, isLoading, error, total, refresh } = useLeads({ enabled: true })

  const getInitials = (name?: string, phone?: string) => {
    if (name) {
      const parts = name.split(' ')
      return parts.length > 1
        ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
        : name.slice(0, 2).toUpperCase()
    }
    return phone?.slice(-2) || '??'
  }

  const getAvatarUrl = (phone: string) => {
    return `https://api.dicebear.com/7.x/avataaars/svg?seed=${phone}`
  }

  const handleCall = (phone: string) => {
    console.log('Calling', phone)
  }

  const handleVideo = (phone: string) => {
    console.log('Video calling', phone)
  }

  const handleMessage = (phone: string) => {
    router.push(`/messages?chatId=${encodeURIComponent(phone)}@c.us`)
  }

  const handleEdit = (lead: Lead) => {
    setSelectedLead(lead)
    console.log('Editing', lead.name || lead.phone_number)
  }

  const handleDelete = (lead: Lead) => {
    if (!confirm('Tem certeza que deseja remover este contato?')) return
    console.log('Deleting', lead.id)
  }

  return (
    <div className="p-6 h-full">
      <PageHeader
        title={`${total} Contatos`}
        actions={
          <Button className="bg-blue-600 hover:bg-blue-700">
            <Plus className="w-4 h-4 mr-2" />
            Novo Contato
          </Button>
        }
      />

      {error && (
        <Alert variant="destructive" className="mb-4">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {isLoading && leads.length === 0 ? (
        <div className="flex items-center justify-center h-64">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : leads.length === 0 ? (
        <div className="flex flex-col items-center justify-center h-64 text-muted-foreground">
          <AlertCircle className="h-12 w-12 mb-4" />
          <p className="text-lg">Nenhum contato encontrado</p>
          <p className="text-sm">Envie uma mensagem no WhatsApp para começar</p>
        </div>
      ) : (
        <div className="flex gap-6 h-[calc(100vh-180px)]">
          {/* Lista de Contatos - Esquerda */}
          <div className="flex-1 overflow-auto pr-2">
            <div className="grid grid-cols-2 gap-4">
              {leads.map((lead) => (
                <ContactCard
                  key={lead.id}
                  name={lead.name || lead.phone_number}
                  avatar={getAvatarUrl(lead.phone_number)}
                  initials={getInitials(lead.name ?? undefined, lead.phone_number)}
                  isOnline={false}
                  onCall={() => handleCall(lead.phone_number)}
                  onVideo={() => handleVideo(lead.phone_number)}
                  onMessage={() => handleMessage(lead.phone_number)}
                  onEdit={() => handleEdit(lead)}
                />
              ))}
            </div>
          </div>

          {/* Painel de Detalhes - Direita */}
          {selectedLead && (
            <div className="w-80 sticky top-0">
              <ContactDetailPanel
                name={selectedLead.name || selectedLead.phone_number}
                avatar={getAvatarUrl(selectedLead.phone_number)}
                initials={getInitials(selectedLead.name ?? undefined, selectedLead.phone_number)}
                about={`Pontuação: ${selectedLead.maturity_score}/100`}
                onCall={() => handleCall(selectedLead.phone_number)}
                onVideo={() => handleVideo(selectedLead.phone_number)}
                onMessage={() => handleMessage(selectedLead.phone_number)}
                onEdit={() => handleEdit(selectedLead)}
                onDelete={() => handleDelete(selectedLead)}
              />
            </div>
          )}
        </div>
      )}
    </div>
  )
}
