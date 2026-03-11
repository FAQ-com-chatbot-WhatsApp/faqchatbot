"use client"

import { useState } from "react"
import { Send, User, Search, MoreVertical } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"

export default function MessagesPage() {
  // Estado local apenas para simular a digitação por enquanto
  const [message, setMessage] = useState("")

  return (
    <div className="flex h-[calc(100vh-80px)] gap-4 p-4">
      {/* 1. Lista de Conversas */}
      <Card className="w-80 flex flex-col shadow-sm">
        <div className="p-4 border-b">
          <div className="relative">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Buscar conversa..." className="pl-9" />
          </div>
        </div>
        <div className="flex-1 overflow-y-auto">
          {/* Exemplo de item de conversa */}
          <div className="p-4 flex items-center gap-3 hover:bg-accent cursor-pointer border-b">
            <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
              <User className="h-6 w-6 text-primary" />
            </div>
            <div className="flex-1 overflow-hidden">
              <p className="font-medium truncate">Cliente Teste</p>
              <p className="text-xs text-muted-foreground truncate">Olá, gostaria de ver o cardápio!</p>
            </div>
          </div>
        </div>
      </Card>

      {/* 2. Janela de Chat */}
      <Card className="flex-1 flex flex-col shadow-sm relative">
        {/* Cabeçalho do Chat */}
        <div className="p-4 border-b flex justify-between items-center bg-muted/30">
          <div className="flex items-center gap-3">
            <div className="h-8 w-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold">
              CT
            </div>
            <span className="font-semibold">Cliente Teste</span>
          </div>
          <Button variant="ghost" size="icon"><MoreVertical className="h-5 w-5" /></Button>
        </div>

        {/* Área de Mensagens */}
        <div className="flex-1 p-6 overflow-y-auto space-y-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
          {/* Mensagem Recebida */}
          <div className="flex justify-start">
            <div className="bg-card border p-3 rounded-2xl rounded-tl-none max-w-[70%] shadow-sm">
              <p className="text-sm">Oi! Poderia me falar sobre os valores?</p>
              <span className="text-[10px] text-muted-foreground mt-1 block">19:30</span>
            </div>
          </div>

          {/* Mensagem Enviada */}
          <div className="flex justify-end">
            <div className="bg-primary text-primary-foreground p-3 rounded-2xl rounded-tr-none max-w-[70%] shadow-md">
              <p className="text-sm">Com certeza! Qual seu nome?</p>
              <span className="text-[10px] opacity-70 mt-1 block text-right">19:31</span>
            </div>
          </div>
        </div>

        {/* Campo de Envio */}
        <div className="p-4 border-t bg-card">
          <form className="flex gap-2" onSubmit={(e) => e.preventDefault()}>
            <Input 
              placeholder="Digite sua mensagem..." 
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="flex-1"
            />
            <Button type="submit" size="icon">
              <Send className="h-4 w-4" />
            </Button>
          </form>
        </div>
      </Card>
    </div>
  )
}
