'use client'

import { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Phone, Video, MessageCircle, Edit2, Trash2, Plus } from 'lucide-react'

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
  const [contatoSelecionado, setContatoSelecionado] = useState<Contato>(contatosMock[0])

  return (
    <div className="p-6 h-full">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-3xl font-bold">{contatosMock.length} Contatos</h1>
        <Button className="bg-blue-600 hover:bg-blue-700">
          <Plus className="w-4 h-4 mr-2" />
          Novo Contato
        </Button>
      </div>
      
      <div className="flex gap-6 h-[calc(100vh-180px)]">
        {/* Lista de Contatos - Esquerda */}
        <div className="flex-1 overflow-auto pr-2">
          <div className="grid grid-cols-2 gap-1">
            {contatosMock.map((contato) => (
              <Card
                key={contato.id}
                className={`cursor-pointer overflow-visible transition-all hover:shadow-md ${
                  contatoSelecionado.id === contato.id
                    ? 'border-2 border-blue-500 bg-blue-50 dark:bg-blue-950'
                    : ''
                }`}
                onClick={() => setContatoSelecionado(contato)}
              >
                <CardContent className="p-1">
                  <div className="flex flex-col items-center gap-1">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={contato.imagem} alt={contato.nome} />
                      <AvatarFallback>{contato.iniciais}</AvatarFallback>
                    </Avatar>
                    <div className="text-center w-full">
                      <h3 className="font-semibold text-sm truncate">{contato.nome}</h3>
                    </div>
                    <div className="flex gap-1">
                      <button className="p-1 hover:bg-muted rounded transition-colors">
                        <Phone className="w-4 h-4 text-blue-600" />
                      </button>
                      <button className="p-1 hover:bg-muted rounded transition-colors">
                        <Video className="w-4 h-4 text-blue-600" />
                      </button>
                      <button className="p-1 hover:bg-muted rounded transition-colors">
                        <MessageCircle className="w-4 h-4 text-blue-600" />
                      </button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Painel de Detalhes - Direita */}
        <div className="w-80 sticky top-0">
          <Card className="border-l border-border">
            <CardContent className="p-6">
              {/* Avatar e Nome */}
              <div className="flex flex-col items-center gap-4 mb-6">
                <Avatar className="h-24 w-24">
                  <AvatarImage
                    src={contatoSelecionado.imagem}
                    alt={contatoSelecionado.nome}
                  />
                  <AvatarFallback>{contatoSelecionado.iniciais}</AvatarFallback>
                </Avatar>
                <div className="text-center">
                  <h2 className="text-xl font-bold">{contatoSelecionado.nome}</h2>
                </div>
              </div>

              {/* Ícones de Ação */}
              <div className="flex justify-center gap-4 mb-6">
                <button className="p-3 hover:bg-muted rounded-lg transition-colors">
                  <Phone className="w-5 h-5 text-blue-600" />
                </button>
                <button className="p-3 hover:bg-muted rounded-lg transition-colors">
                  <MessageCircle className="w-5 h-5 text-blue-600" />
                </button>
                <button className="p-3 hover:bg-muted rounded-lg transition-colors">
                  <Video className="w-5 h-5 text-blue-600" />
                </button>
              </div>

              {/* Botões de Ação */}
              <div className="flex gap-3 mb-6">
                <Button
                  variant="outline"
                  size="sm"
                  className="flex-1"
                >
                  <Edit2 className="w-4 h-4 mr-2" />
                  Editar
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  className="flex-1 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                >
                  <Trash2 className="w-4 h-4 mr-2" />
                  Excluir
                </Button>
              </div>

              {/* Seção About */}
              <div>
                <h3 className="text-sm font-semibold mb-2">Sobre</h3>
                <p className="text-xs text-muted-foreground leading-relaxed">
                  {contatoSelecionado.sobre}
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}
