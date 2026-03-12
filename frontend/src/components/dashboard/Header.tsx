'use client'

import { Menu, Search, Bell, MessageSquare, LogOut, User, Settings } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useRouter } from 'next/navigation'
import { useAuth } from '@/hooks/useAuth'

interface HeaderProps {
  onToggleSidebar: () => void
  userName?: string
  userRole?: string
  userAvatar?: string
}

export function Header({
  onToggleSidebar,
  userName = 'User',
  userRole = 'Admin',
  userAvatar
}: HeaderProps) {
  const router = useRouter()
  const { logout } = useAuth()

  const handleLogout = () => {
    logout()
    router.push('/signin')
  }

  return (
    <header className="border-b border-border bg-card sticky top-0 z-10">
      <div className="p-6 flex items-center justify-between">
        <div className="flex items-center gap-4 flex-1">
          <button
            onClick={onToggleSidebar}
            className="p-2 hover:bg-muted rounded-lg"
            aria-label="Toggle sidebar"
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
          <button className="p-2 hover:bg-muted rounded-lg relative" aria-label="Notifications">
            <Bell className="w-5 h-5" />
            <span className="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full"></span>
          </button>
          <button className="p-2 hover:bg-muted rounded-lg relative" aria-label="Messages">
            <MessageSquare className="w-5 h-5" />
            <span className="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full"></span>
          </button>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <button className="flex items-center gap-3 pl-4 border-l border-border hover:bg-muted rounded-lg p-2 transition-colors">
                <Avatar className="h-9 w-9">
                  <AvatarImage src={userAvatar} />
                  <AvatarFallback>{userName.charAt(0)}</AvatarFallback>
                </Avatar>
                <div className="text-sm text-left">
                  <p className="font-medium">{userName}</p>
                  <p className="text-xs text-muted-foreground">{userRole}</p>
                </div>
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuLabel>Minha Conta</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => router.push('/profile')}>
                <User className="mr-2 h-4 w-4" />
                <span>Perfil</span>
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.push('/settings')}>
                <Settings className="mr-2 h-4 w-4" />
                <span>Configurações</span>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={handleLogout} className="text-destructive focus:text-destructive">
                <LogOut className="mr-2 h-4 w-4" />
                <span>Sair</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </header>
  )
}
