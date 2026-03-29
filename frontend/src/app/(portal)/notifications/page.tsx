'use client'

import { useState, useEffect } from 'react'
import { PageHeader } from '@/components/ui/page-header'
import { 
  CheckCircle2, 
  Clock, 
  MessageSquare, 
  Check 
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

// Mock de dados para simular as notificações
const NOTIFICATIONS_MOCK = [
  {
    id: 1,
    user: "Maria Silva",
    message: "Bot finalizou atendimento. Aguardando agendamento.",
    time: "27/03/2026 23:56",
    status: "pending", // pendente
  },
  {
    id: 2,
    user: "(51) 98888-8888",
    message: "Cliente pediu informações sobre pagamento.",
    time: "27/03/2026 23:30",
    status: "resolved", // resolvido
  },
];

export default function NotificationsPage() {
  const [filter, setFilter] = useState("all");

  return (
    <div className="p-6 max-w-7xl">
      {/* Cabeçalho da Página */}
      <PageHeader title="Notificações" />

      {/* Filtros por Abas */}
      <Tabs defaultValue="all" className="space-y-4" onValueChange={setFilter}>
        <TabsList>
          <TabsTrigger value="all">Todas</TabsTrigger>
          <TabsTrigger value="pending">Pendentes</TabsTrigger>
          <TabsTrigger value="resolved">Resolvidas</TabsTrigger>
        </TabsList>
      </Tabs>

      {/* Lista de Notificações */}
      <div className="p-6 max-w-7xl space-y-8">
        {NOTIFICATIONS_MOCK.filter(n => filter === "all" || n.status === filter).map((notification) => (
          <Card key={notification.id} className="overflow-hidden border-slate-200 shadow-sm">
            <CardContent className="p-6">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <div className="flex items-start gap-4">
                  {/* Ícone de Status dinâmico */}
                  <div className="mt-1">
                    {notification.status === "pending" ? (
                      <div className="bg-orange-100 p-2 rounded-full">
                        <Clock className="h-5 w-5 text-orange-600" />
                      </div>
                    ) : (
                      <div className="bg-green-100 p-2 rounded-full">
                        <CheckCircle2 className="h-5 w-5 text-green-600" />
                      </div>
                    )}
                  </div>

                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold text-slate-900">
                        {notification.user}
                      </span>
                    </div>
                    <p className="text-slate-600">
                      {notification.message}
                    </p>
                    <p className="text-xs text-slate-400">
                      {notification.time}
                    </p>
                  </div>
                </div>

                {/* Ações */}
                <div className="flex items-center gap-2 self-end md:self-center">
                  <Button variant="outline" size="sm" className="gap-2 border-slate-300">
                    <MessageSquare className="h-4 w-4 text-slate-500" />
                    Abrir conversa
                  </Button>
                  <Button size="sm" className="gap-2 bg-blue-600 hover:bg-blue-700">
                    <Check className="h-4 w-4" />
                    Marcar como feito
                  </Button>
                </div>

              </div>
            </CardContent>
          </Card>
        ))}

        {/* Estado vazio */}
        {NOTIFICATIONS_MOCK.length === 0 && (
          <div className="py-20 text-center text-slate-500">
            Nenhuma notificação encontrada.
          </div>
        )}
      </div>
    </div>
  );
}