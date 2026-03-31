'use client'

import { useState } from 'react'
import { PageHeader } from '@/components/ui/page-header'
import { 
  CheckCircle2, 
  Clock, 
  MessageSquare, 
  Check,
  AlertCircle,
  Loader2
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useNotifications } from '@/hooks/useNotifications';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import { useRouter } from 'next/navigation';

export default function NotificationsPage() {
  const router = useRouter();
  const [filter, setFilter] = useState("all");
  const { notifications, isLoading, error, markAsRead } = useNotifications();

  const filteredNotifications = notifications.filter(n => {
    if (filter === "pending") return !n.read;
    if (filter === "resolved") return n.read;
    return true;
  });

  const handleOpenConversation = () => {
    // Por simplicidade, leva o usuário para a tela de mensagens
    router.push('/messages');
  };

  if (error) {
    return (
      <div className="p-6 text-center">
        <AlertCircle className="mx-auto h-12 w-12 text-destructive mb-4" />
        <h2 className="text-xl font-semibold mb-2">Erro ao carregar notificações</h2>
        <p className="text-muted-foreground">{error}</p>
      </div>
    );
  }

  return (
    <div className="p-6 max-w-7xl">
      {/* Cabeçalho da Página */}
      <PageHeader title="Notificações" />

      {/* Filtros por Abas */}
      <Tabs defaultValue="all" className="space-y-4 mb-6" onValueChange={setFilter}>
        <TabsList className="bg-muted/50 p-1">
          <TabsTrigger value="all">Todas</TabsTrigger>
          <TabsTrigger value="pending">Pendentes</TabsTrigger>
          <TabsTrigger value="resolved">Resolvidas</TabsTrigger>
        </TabsList>
      </Tabs>

      {/* Lista de Notificações */}
      <div className="space-y-4">
        {isLoading && notifications.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-primary mb-4" />
            <p className="text-muted-foreground">Buscando notificações reais...</p>
          </div>
        ) : filteredNotifications.length === 0 ? (
          <div className="py-20 text-center border-2 border-dashed rounded-xl border-slate-200">
            <AlertCircle className="mx-auto h-12 w-12 text-slate-300 mb-4" />
            <p className="text-lg text-slate-500 font-medium">Nenhuma notificação encontrada.</p>
            <p className="text-sm text-slate-400">Você será avisado quando novos leads estiverem prontos.</p>
          </div>
        ) : (
          filteredNotifications.map((notification) => (
            <Card 
                key={notification.id} 
                className={`overflow-hidden transition-all border-slate-200 shadow-sm ${!notification.read ? 'border-l-4 border-l-blue-600 bg-blue-50/10' : ''}`}
            >
              <CardContent className="p-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                  
                  <div className="flex items-start gap-4">
                    {/* Ícone de Status dinâmico */}
                    <div className="mt-1">
                      {!notification.read ? (
                        <div className="bg-orange-100 p-2 rounded-full ring-4 ring-orange-50">
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
                        <span className="font-bold text-slate-900 text-lg">
                          {notification.title}
                        </span>
                        {!notification.read && (
                          <span className="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Novo</span>
                        )}
                      </div>
                      <p className="text-slate-600 leading-relaxed max-w-2xl">
                        {notification.message}
                      </p>
                      <p className="text-xs text-slate-400 font-medium flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {format(new Date(notification.created_at), "dd/MM/yyyy 'às' HH:mm", { locale: ptBR })}
                      </p>
                    </div>
                  </div>

                  {/* Ações */}
                  <div className="flex items-center gap-2 self-end md:self-center">
                    <Button 
                      variant="outline" 
                      size="sm" 
                      onClick={handleOpenConversation}
                      className="gap-2 border-slate-300 hover:bg-slate-50 font-medium"
                    >
                      <MessageSquare className="h-4 w-4 text-slate-500" />
                      Abrir conversa
                    </Button>
                    <Button 
                      size="sm" 
                      className={`gap-2 font-bold transition-all ${notification.read ? 'bg-slate-100 text-slate-500 hover:bg-slate-200' : 'bg-blue-600 hover:bg-blue-700 text-white'}`}
                      onClick={() => !notification.read && markAsRead(notification.id)}
                      disabled={notification.read}
                    >
                      <Check className="h-4 w-4" />
                      {notification.read ? "Concluído" : "Marcar como feito"}
                    </Button>
                  </div>

                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}