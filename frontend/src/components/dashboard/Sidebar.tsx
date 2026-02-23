'use client'

import { useRouter, usePathname } from 'next/navigation'
import { Menu } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useState } from 'react'

interface NavItem {
  icon: string
  label: string
  path: string
}

export function Sidebar() {
  const router = useRouter()
  const pathname = usePathname()
  const [isSidebarOpen, setIsSidebarOpen] = useState(true)

  const navItems: NavItem[] = [
    { icon: '🏠', label: 'Dashboard', path: '/dashboard' },
    { icon: '📇', label: 'Contatos', path: '/dashboard/contatos' },
    { icon: '💬', label: 'Mensagens', path: '/dashboard/mensagens' },
    { icon: '⚙️', label: 'Configurações', path: '/dashboard/configuracoes' },
  ]

  return (
    <>
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
          {navItems.map((item) => {
            const isActive = pathname === item.path || (pathname === '/dashboard' && item.path === '/dashboard')
            
            return (
              <button
                key={item.label}
                onClick={() => router.push(item.path)}
                className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
                  isActive
                    ? 'bg-blue-100 text-blue-600'
                    : 'text-muted-foreground hover:bg-muted'
                }`}
              >
                <span className="text-xl">{item.icon}</span>
                {isSidebarOpen && <span className="text-sm font-medium">{item.label}</span>}
              </button>
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

      {/* Toggle Button (exposed for header) */}
      <input
        type="hidden"
        id="sidebar-state"
        value={isSidebarOpen ? 'open' : 'closed'}
        onChange={() => setIsSidebarOpen(!isSidebarOpen)}
      />
    </>
  )
}
