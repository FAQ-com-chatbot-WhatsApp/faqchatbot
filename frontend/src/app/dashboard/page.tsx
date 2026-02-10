'use client'

import { useState } from 'react'
import { 
  Menu, 
  Search, 
  Bell, 
  MessageSquare, 
  MoreVertical,
  TrendingUp,
  Users,
  CheckSquare,
  MessageCircle,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Checkbox } from '@/components/ui/checkbox'
import { Progress } from '@/components/ui/progress'

export default function DashboardPage() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(true)
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

  const upcomingProjects = [
    {
      id: 1,
      title: 'Consulta com Dr. Andrea - Maria Silva ',
      status: 'Criado em 08 de fevereiro de 2025',
      deadline: 'Terça-feira, 17 de fevereiro de 2025 as 14:00',
    },
    {
      id: 2,
      title: 'Consulta com Dr. Andrea - Daniela Costa',
      status: 'Criado em 08 de fevereiro de 2025',
      deadline: 'Quinta-feira, 19 de fevereiro de 2025 as 10:00',
    },
    {
      id: 3,
      title: 'Consulta com Dr. Andrea - Paula Pereira',
      status: 'Criado em 09 de fevereiro de 2025',
      deadline: 'Sexta-feira, 20 de fevereiro de 2025 as 16:00',
    },
  ]

  const getBadgeColor = (category: string) => {
    if (category === 'Graphic Designer') return 'bg-emerald-100 text-emerald-800'
    if (category === 'Digital Marketing') return 'bg-blue-100 text-blue-800'
    if (category === 'Programmer') return 'bg-orange-100 text-orange-800'
    return 'bg-gray-100 text-gray-800'
  }

  return (
    <div className="flex min-h-screen bg-background">
      {/* Sidebar */}
      <aside
        className={`${
          isSidebarOpen ? 'w-64' : 'w-20'
        } border-r border-border bg-card transition-all duration-300 flex flex-col`}
      >
        {/* Logo */}
        <div className="p-6 border-b border-border">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold">
              GO
            </div>
            {isSidebarOpen && <span className="font-bold text-lg"> Clínica GO.</span>}
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 p-4 space-y-2">
          {[
            { icon: '🏠', label: 'Dashboard', active: true },
            { icon: '📁', label: 'Inicio/Fim de Sessão', active: false },
            { icon: '📇', label: 'Contatos', active: false },
            { icon: '📅', label: 'Calendário', active: false },
            { icon: '💬', label: 'Mensagens', active: false },
            { icon: '⚙️', label: 'Configurações', active: false },
          ].map((item) => (
            <button
              key={item.label}
              className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
                item.active
                  ? 'bg-blue-100 text-blue-600'
                  : 'text-muted-foreground hover:bg-muted'
              }`}
            >
              <span className="text-xl">{item.icon}</span>
              {isSidebarOpen && <span className="text-sm font-medium">{item.label}</span>}
            </button>
          ))}
        </nav>

        {/* New Project Button */}
        {isSidebarOpen && (
          <div className="p-4 border-t border-border">
            <Button className="w-full bg-blue-600 hover:bg-blue-700" size="sm">
              + Novo Agendamento
            </Button>
          </div>
        )}
      </aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col">
        {/* Header */}
        <header className="border-b border-border bg-card">
          <div className="p-6 flex items-center justify-between">
            <div className="flex items-center gap-4 flex-1">
              <button
                onClick={() => setIsSidebarOpen(!isSidebarOpen)}
                className="p-2 hover:bg-muted rounded-lg"
              >
                <Menu className="w-5 h-5" />
              </button>
              <div className="flex-1 max-w-sm">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                  <Input
                    placeholder="Pesquisar"
                    className="pl-10"
                  />
                </div>
              </div>
            </div>

            <div className="flex items-center gap-4">
              <button className="p-2 hover:bg-muted rounded-lg relative">
                <Bell className="w-5 h-5" />
                <span className="absolute top-1 right-1 w-2 h-2"></span>
              </button>
              <button className="p-2 hover:bg-muted rounded-lg relative">
                <MessageSquare className="w-5 h-5" />
                <span className="absolute top-1 right-1 w-2 h-2 "></span>
              </button>
              <div className="flex items-center gap-3 pl-4 border-l border-border">
                <Avatar className="h-9 w-9">
                  <AvatarImage src="https://github.com/shadcn.png" />
                  <AvatarFallback>PP</AvatarFallback>
                </Avatar>
                <div className="text-sm">
                  <p className="font-medium">Karollini Moraes</p>
                  <p className="text-xs text-muted-foreground">Admin</p>
                </div>
              </div>
            </div>
          </div>
        </header>

        {/* Page Content */}
        <div className="flex-1 overflow-auto">
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

              {/* Right Panel */}
              <div className="space-y-6">
                {/* Upcoming Projects */}
                <Card>
                  <CardHeader>
                    <div className="flex items-center justify-between">
                      <CardTitle className="text-lg">Próximos Agendamentos</CardTitle>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {upcomingProjects.map((project) => (
                      <div key={project.id} className="pb-4 border-b last:border-b-0 last:pb-0">
                        <h4 className="font-medium text-sm mb-1">{project.title}</h4>
                        <p className="text-xs text-muted-foreground mb-2">{project.status}</p>
                        <div className="flex items-center gap-1 text-xs text-blue-600 font-medium">
                          ⏱️ {project.deadline}
                        </div>
                      </div>
                    ))}
                  </CardContent>
                </Card>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
    )
}
