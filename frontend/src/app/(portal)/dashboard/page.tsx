'use client'

import { useState } from 'react'
import { 
  TrendingUp,
  Users,
  CheckSquare,
  MessageCircle,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'

export default function DashboardPage() {
  const [selectedTab, setSelectedTab] = useState('monthly')

  // Sample data
  const stats = [
    {
      title: 'Novas sessões iniciadas (último mês)',
      value: '78',
      icon: 'briefcase',
      color: 'bg-blue-100',
    },
    {
      title: 'Contatos que você tem',
      value: '214',
      icon: 'users',
      color: 'bg-purple-100',
    },
    {
      title: 'Consultas agendadas (último mês)',
      value: '93',
      icon: 'tasks',
      color: 'bg-orange-100',
    },
    {
      title: 'Mensagens não lidas',
      value: '12',
      icon: 'message',
      color: 'bg-green-100',
    },
  ]

  return (
    <div className="p-6 max-w-7xl">
      <h1 className="text-3xl font-bold mb-6">Dashboard</h1>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {stats.map((stat, idx) => (
          <Card key={idx}>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-muted-foreground text-sm mb-1">
                    {stat.title}
                  </p>
                  <p className="text-3xl font-bold">{stat.value}</p>
                </div>
                <div className={`w-12 h-12 ${stat.color} rounded-lg flex items-center justify-center`}>
                  {stat.icon === 'briefcase' && <TrendingUp className="w-6 h-6 text-blue-600" />}
                  {stat.icon === 'users' && <Users className="w-6 h-6 text-purple-600" />}
                  {stat.icon === 'tasks' && <CheckSquare className="w-6 h-6 text-orange-600" />}
                  {stat.icon === 'message' && <MessageCircle className="w-6 h-6 text-green-600" />}
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content Area */}
        <div className="lg:col-span-2 space-y-6">
          {/* Project Created Chart */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Alcance de novos pacientes</CardTitle>
                <Tabs value={selectedTab} onValueChange={setSelectedTab} className="w-auto">
                  <TabsList className="grid w-auto grid-cols-3">
                    <TabsTrigger value="daily" className="text-xs">
                      Diário
                    </TabsTrigger>
                    <TabsTrigger value="weekly" className="text-xs">
                      Semanal
                    </TabsTrigger>
                    <TabsTrigger value="monthly" className="text-xs">
                      Mensal
                    </TabsTrigger>
                  </TabsList>
                </Tabs>
              </div>
              <p className="text-sm text-muted-foreground">
                <span className="text-green-600">↑ Último mês +20</span>
              </p>
            </CardHeader>
            <CardContent>
              <div className="h-48 bg-muted rounded-lg flex items-center justify-center">
                <p className="text-muted-foreground">
                  Colocar gráfico de alcance de novos pacientes aqui 
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}

