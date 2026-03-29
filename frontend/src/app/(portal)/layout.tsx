'use client'

import { useState } from 'react'
import { useAuthRedirect } from '../../hooks/useAuthRedirect'
import { useUser } from '../../hooks/useUser'
import { Sidebar } from '@/components/dashboard/Sidebar'
import { Header } from '@/components/dashboard/Header'

export default function PortalLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const { loading } = useAuthRedirect()
  const { user } = useUser()
  const [isSidebarOpen, setIsSidebarOpen] = useState(true)

  const navItems = [
    { icon: 'Home', label: 'Dashboard', path: '/dashboard' },
    { icon: 'Library', label: 'Repositório', path: '/faq' },
    { icon: 'Users', label: 'Contatos', path: '/contacts' },
    { icon: 'MessageSquare', label: 'Mensagens', path: '/messages' },
    { icon: 'Bell', label: 'Notificações', path: '/notifications' },
    { icon: 'Settings', label: 'Configurações', path: '/settings' },
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
      <Sidebar isOpen={isSidebarOpen} navItems={navItems} />

      <main className={`flex-1 flex flex-col ${isSidebarOpen ? 'ml-64' : 'ml-20'} transition-all duration-300`}>
        <Header
          onToggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)}
          userName={user?.full_name || user?.email || "Usuário"}
          userRole={user?.role || "User"}
          userAvatar="https://github.com/shadcn.png"
        />

        <div className="flex-1 overflow-auto">
          {children}
        </div>
      </main>
    </div>
  )
}
