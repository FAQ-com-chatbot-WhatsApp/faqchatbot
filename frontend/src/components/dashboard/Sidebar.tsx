'use client'

import { usePathname } from 'next/navigation'
import { Button } from '@/components/ui/button'
import Link from 'next/link'
import { Home, Users, MessageSquare, Settings, Library, Bell } from 'lucide-react'

interface NavItem {
  icon: string
  label: string
  path: string
}

interface SidebarProps {
  isOpen: boolean
  navItems: NavItem[]
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  Home,
  Users,
  MessageSquare,
  Bell,
  Settings,
  Library,
}

export function Sidebar({ isOpen, navItems }: SidebarProps) {
  const pathname = usePathname()

  return (
    <aside
      className={`${isOpen ? 'w-64' : 'w-20'
        } border-r border-border bg-card transition-all duration-300 flex flex-col fixed h-screen z-20`}
    >
      <div className="p-6 border-b border-border">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold">
            GO
          </div>
          {isOpen && <span className="font-bold text-lg">Clínica GO.</span>}
        </div>
      </div>

      <nav className="flex-1 p-4 space-y-2">
        {navItems.map((item) => {
          const isActive = pathname === item.path
          const Icon = iconMap[item.icon]

          return (
            <Link
              key={item.label}
              href={item.path}
              className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors block ${isActive
                  ? 'bg-blue-100 text-blue-600'
                  : 'text-muted-foreground hover:bg-muted'
                }`}
            >
              {Icon && <Icon className="w-5 h-5" />}
              {isOpen && <span className="text-sm font-medium">{item.label}</span>}
            </Link>
          )
        })}
      </nav>
    </aside>
  )
}
