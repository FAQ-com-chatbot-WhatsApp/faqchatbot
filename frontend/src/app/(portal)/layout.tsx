'use client'

import { useState } from 'react'
import { useAuthRedirect } from '../../hooks/useAuthRedirect'
import { 
  Menu, 
  Search, 
  Bell, 
  MessageSquare,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { usePathname } from 'next/navigation'
import Link from 'next/link'

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const { loading } = useAuthRedirect()
  const [isSidebarOpen, setIsSidebarOpen] = useState(true)
  const pathname = usePathname()

  const navItems = [
    { icon: '🏠', label: 'Dashboard', path: '/dashboard' },
    { icon: '📇', label: 'Contatos', path: '/dashboard/contatos' },
    { icon: '💬', label: 'Mensagens', path: '/dashboard/mensagens' },
    { icon: '⚙️', label: 'Configurações', path: '/dashboard/configuracoes' },
  ]

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <p>Carregando...</p>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen bg-background">
      {/* Sidebar */}
      <aside
        className={`${
          isSidebarOpen ? 'w-64' : 'w-20'
        } border-r border-border bg-card transition-all duration-300 flex flex-col fixed h-screen z-20`}
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
          {navItems.map((item) => {
            const isActive = pathname === item.path
            
            return (
              <Link
                key={item.label}
                href={item.path}
                className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors block ${
                  isActive
                    ? 'bg-blue-100 text-blue-600'
                    : 'text-muted-foreground hover:bg-muted'
                }`}
              >
                <span className="text-xl">{item.icon}</span>
                {isSidebarOpen && <span className="text-sm font-medium">{item.label}</span>}
              </Link>
            )
          })}
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
      <main className={`flex-1 flex flex-col ${isSidebarOpen ? 'ml-64' : 'ml-20'} transition-all duration-300`}>
        {/* Header */}
        <header className="border-b border-border bg-card sticky top-0 z-10">
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
          {children}
        </div>
      </main>
    </div>
  )
}
