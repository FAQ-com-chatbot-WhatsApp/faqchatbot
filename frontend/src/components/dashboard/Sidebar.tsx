'use client'

import { usePathname } from 'next/navigation'
import Link from 'next/link'
import { Home, Users, MessageSquare, Settings, Library, Bell } from 'lucide-react'

interface NavItem {
  icon: string
  label: string
  path: string
  badge?: number
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
              className={`w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors group ${isActive
                  ? 'bg-blue-100 text-blue-600'
                  : 'text-muted-foreground hover:bg-muted font-medium'
                }`}
            >
              <div className="flex items-center gap-3">
                <div className="relative">
                    {Icon && <Icon className="w-5 h-5" />}
                    {!isOpen && item.badge && (
                        <span className="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white ring-2 ring-card animate-pulse">
                            {item.badge > 9 ? '9+' : item.badge}
                        </span>
                    )}
                </div>
                {isOpen && <span className="text-sm font-medium">{item.label}</span>}
              </div>

              {isOpen && item.badge && (
                <span className="flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-600 px-1.5 text-[10px] font-bold text-white shadow-lg">
                  {item.badge}
                </span>
              )}
            </Link>
          )
        })}
      </nav>
    </aside>
  )
}
