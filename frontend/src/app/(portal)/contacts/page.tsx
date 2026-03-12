'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Plus, AlertCircle } from 'lucide-react'
import { ContactCard } from '@/components/ui/contact-card'
import { ContactDetailPanel } from '@/components/ui/contact-detail-panel'
import { PageHeader } from '@/components/ui/page-header'
import { useContacts } from '@/hooks/useContacts'
import { useRouter } from 'next/navigation'

interface Contato {
  id: number
  nome: string
  imagem: string
  iniciais: string
  sobre: string
}

const contatosMock: Contato[] = [
  {
    id: 1,
    nome: 'Abdul Kean',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Abdul',
    iniciais: 'AK',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 2,
    nome: 'Angela Moss',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Angela',
    iniciais: 'AM',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 3,
    nome: 'Afiff Skunder',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Afiff',
    iniciais: 'AS',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 4,
    nome: 'Abigail Smurt',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Abigail',
    iniciais: 'AS',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 5,
    nome: 'Bella Syuqr',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Bella',
    iniciais: 'BS',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 6,
    nome: 'Benny Gacu',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Benny',
    iniciais: 'BG',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 7,
    nome: 'Chloe Simatup',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Chloe',
    iniciais: 'CS',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 8,
    nome: 'Denny Juan',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Denny',
    iniciais: 'DJ',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 9,
    nome: 'Franklin CS',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Franklin',
    iniciais: 'FC',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 10,
    nome: 'Fanny Saragih',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Fanny',
    iniciais: 'FS',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 11,
    nome: 'Hermanto',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Hermanto',
    iniciais: 'HM',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
  {
    id: 12,
    nome: 'Lulu Salam',
    imagem: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Lulu',
    iniciais: 'LS',
    sobre: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  },
]

export default function ContatosPage() {
  const router = useRouter()
  const [contatoSelecionado, setContatoSelecionado] = useState<Contato>(contatosMock[0])
  
  const { error, block, unblock, isLoading } = useContacts({ enabled: true })

  const handleCall = (phone: string) => {
    // TODO: Implement call functionality
    console.log('Calling', phone)
  }

  const handleVideo = (phone: string) => {
    // TODO: Implement video call functionality
    console.log('Video calling', phone)
  }

  const handleMessage = (phone: string) => {
    // Navigate to messages page with selected contact
    router.push(`/messages?chatId=${encodeURIComponent(phone)}@c.us`)
  }

  const handleEdit = (contato: Contato) => {
    setContatoSelecionado(contato)
    // TODO: Open edit modal/dialog
    console.log('Editing', contato.nome)
  }

  const handleDelete = async (contactId: string) => {
    if (!confirm('Tem certeza que deseja bloquear este contato?')) return
    
    try {
      await block(contactId)
      console.log('Contact blocked successfully')
    } catch (err) {
      console.error('Failed to block contact', err)
    }
  }

  return (
    <div className="p-6 h-full">
      <PageHeader
        title={`${contatosMock.length} Contatos`}
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

      <div className="flex gap-6 h-[calc(100vh-180px)]">
        {/* Lista de Contatos - Esquerda */}
        <div className="flex-1 overflow-auto pr-2">
          <div className="grid grid-cols-2 gap-4">
            {contatosMock.map((contato) => (
              <ContactCard
                key={contato.id}
                name={contato.nome}
                avatar={contato.imagem}
                initials={contato.iniciais}
                isOnline={Math.random() > 0.5}
                onCall={() => handleCall(`5511${contato.id}99999999`)}
                onVideo={() => handleVideo(`5511${contato.id}99999999`)}
                onMessage={() => handleMessage(`5511${contato.id}99999999`)}
                onEdit={() => handleEdit(contato)}
              />
            ))}
          </div>
        </div>

        <div className="w-80 sticky top-0">
          <ContactDetailPanel
            name={contatoSelecionado.nome}
            avatar={contatoSelecionado.imagem}
            initials={contatoSelecionado.iniciais}
            about={contatoSelecionado.sobre}
            onCall={() => handleCall(`5511${contatoSelecionado.id}99999999`)}
            onVideo={() => handleVideo(`5511${contatoSelecionado.id}99999999`)}
            onMessage={() => handleMessage(`5511${contatoSelecionado.id}99999999`)}
            onEdit={() => handleEdit(contatoSelecionado)}
            onDelete={() => handleDelete(`5511${contatoSelecionado.id}99999999@c.us`)}
          />
        </div>
      </div>
    </div>
  )
}
