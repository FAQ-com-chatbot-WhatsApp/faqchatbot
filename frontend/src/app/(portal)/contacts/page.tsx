'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, Loader2 } from 'lucide-react'
import { ContactCard } from '@/components/ui/contact-card'
import { ContactDetailPanel } from '@/components/ui/contact-detail-panel'
import { PageHeader } from '@/components/ui/page-header'
import { useLeads } from '@/hooks/useLeads'
import { useRouter } from 'next/navigation'
import type { Lead } from '@/services/leadService'
import { updateLead } from '@/services/leadService'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'sonner'

export default function ContatosPage() {
  const router = useRouter()
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null)
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false)
  const [editName, setEditName] = useState('')
  const [isSaving, setIsSaving] = useState(false)

  const { leads, isLoading, error, total, refresh } = useLeads({ enabled: true })

  const getInitials = (name?: string | null) => {
    if (!name) return "??"
    
    // REGEX: Se o nome contém pelo menos uma letra (a-z), é um nome real
    const hasLetters = /[a-zA-Z]/.test(name);
    
    if (hasLetters) {
      const parts = name.trim().split(' ')
      return parts.length > 1
        ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
        : name.slice(0, 2).toUpperCase()
    }
    
    return "??" // Vai disparar o ícone de silhueta no ContactCard
  }

  const handleMessage = (phone: string) => {
    // Navigate to messages with the correct chatId format
    router.push(`/messages?chatId=${encodeURIComponent(phone)}@c.us`)
  }

  const handleEdit = (lead: Lead) => {
    setSelectedLead(lead)
    setEditName(lead.name || '')
    setIsEditDialogOpen(true)
  }

  const handleSaveName = async () => {
    if (!selectedLead) return

    setIsSaving(true)
    try {
      await updateLead(selectedLead.id, { name: editName })
      toast.success('Nome atualizado com sucesso')
      setIsEditDialogOpen(false)
      refresh() // Atualizar lista
      
      // Atualizar lead selecionado
      setSelectedLead({
          ...selectedLead,
          name: editName
      })
    } catch (err) {
      console.error('Erro ao salvar nome:', err)
      toast.error('Erro ao atualizar nome')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="p-6 h-full">
      <PageHeader
        title={`${total} Contatos`}
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
          <p className="text-sm">Os leads aparecerão aqui conforme as novas conversas iniciarem.</p>
        </div>
      ) : (
        <div className="flex gap-6 h-[calc(100vh-180px)]">
          {/* Lista de Contatos - Esquerda */}
          <div className="flex-1 overflow-auto pr-2">
            <div className="grid grid-cols-2 lg:grid-cols-3 gap-4">
              {leads.map((lead) => (
                <ContactCard
                  key={lead.id}
                  name={lead.name || lead.phone_number}
                  initials={getInitials(lead.name)}
                  isOnline={false}
                  isSelected={selectedLead?.id === lead.id}
                  onMessage={() => handleMessage(lead.phone_number)}
                  onEdit={() => handleEdit(lead)}
                />
              ))}
            </div>
          </div>

          {/* Painel de Detalhes - Direita */}
          {selectedLead && (
            <div className="w-80 sticky top-0 border rounded-xl overflow-hidden bg-card">
              <ContactDetailPanel
                name={selectedLead.name || selectedLead.phone_number}
                initials={getInitials(selectedLead.name)}
                about={`Score: ${selectedLead.maturity_score}/100 - Lead Status: ${selectedLead.status || 'Ativo'}`}
                onMessage={() => handleMessage(selectedLead.phone_number)}
                onEdit={() => handleEdit(selectedLead)}
              />
            </div>
          )}
        </div>
      )}

      {/* Modal de Edição de Nome */}
      <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Editar Nome do Contato</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-4 items-center gap-4">
              <Label htmlFor="name" className="text-right">
                Nome
              </Label>
              <Input
                id="name"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                className="col-span-3"
                placeholder="Digite o nome real"
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsEditDialogOpen(false)} disabled={isSaving}>
              Cancelar
            </Button>
            <Button onClick={handleSaveName} disabled={isSaving || !editName.trim()}>
              {isSaving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
              Salvar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
